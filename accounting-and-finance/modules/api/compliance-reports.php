<?php
/**
 * Compliance Reports API
 * Handles compliance report generation and audit trail operations
 * 
 * Database Tables Used:
 * - compliance_reports: Generated compliance reports
 * - audit_logs: Audit trail tracking
 * - journal_entries: Financial data for compliance checks
 * - accounts: Chart of accounts for compliance validation
 * - users: User information
 */

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';

header('Content-Type: application/json');

// Verify user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}
$current_user = getCurrentUser();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'generate_compliance_report':
            generateComplianceReport();
            break;
        
        case 'get_compliance_reports':
            getComplianceReports();
            break;
        
        case 'get_compliance_report':
            getComplianceReport();
            break;
        
        case 'export_compliance_report':
            exportComplianceReport();
            break;
        
        case 'delete_compliance_report':
            deleteComplianceReport();
            break;
        
        case 'get_compliance_status':
            getComplianceStatus();
            break;
        
        case 'get_audit_trail':
            getAuditTrail();
            break;
        
        case 'log_audit_action':
            logAuditAction();
            break;
        
        case 'get_audit_log':
            getAuditLog();
            break;
        
        case 'export_audit_log':
            exportAuditLog();
            break;
        
        case 'get_all_bin_items':
            getAllBinItems();
            break;
        
        case 'restore_report':
            restoreReport();
            break;
        
        case 'restore_all_items':
            restoreAllItems();
            break;
        
        case 'empty_bin':
            emptyBin();
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Generate compliance report
 */
function generateComplianceReport() {
    global $conn, $current_user;
    
    $reportType = $_POST['report_type'] ?? '';
    $periodStart = $_POST['period_start'] ?? '';
    $periodEnd = $_POST['period_end'] ?? '';
    
    if (empty($reportType) || empty($periodStart) || empty($periodEnd)) {
        throw new Exception('Missing required parameters');
    }
    
    // Validate report type
    $validTypes = ['gaap', 'sox', 'bir', 'ifrs'];
    if (!in_array($reportType, $validTypes)) {
        throw new Exception('Invalid report type');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert compliance report record
        $stmt = $conn->prepare("
            INSERT INTO compliance_reports 
            (report_type, period_start, period_end, generated_by, status) 
            VALUES (?, ?, ?, ?, 'generating')
        ");
        $stmt->bind_param('sssi', $reportType, $periodStart, $periodEnd, $current_user['id']);
        $stmt->execute();
        $reportId = $conn->insert_id;
        
        // Generate compliance data based on type
        $complianceData = generateComplianceData($reportType, $periodStart, $periodEnd);
        
        // Update report with data
        $reportData = json_encode($complianceData);
        $stmt = $conn->prepare("
            UPDATE compliance_reports 
            SET report_data = ?, status = 'completed', compliance_score = ?
            WHERE id = ?
        ");
        $stmt->bind_param('sdi', $reportData, $complianceData['compliance_score'], $reportId);
        $stmt->execute();
        
        // Log audit action
        logAuditActionToDB('Generate Compliance Report', 'compliance_report', $reportId, [
            'report_type' => $reportType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'compliance_score' => $complianceData['compliance_score']
        ]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'report_id' => $reportId,
                'report_type' => $reportType,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'compliance_score' => $complianceData['compliance_score'],
                'status' => 'completed',
                'generated_date' => date('Y-m-d H:i:s'),
                'issues_found' => $complianceData['issues_found'] ?? []
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Generate compliance data based on report type
 */
function generateComplianceData($reportType, $periodStart, $periodEnd) {
    global $conn;
    
    $data = [
        'report_type' => $reportType,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'compliance_score' => 0,
        'issues_found' => []
    ];
    
    switch ($reportType) {
        case 'gaap':
            $data = generateGAAPCompliance($periodStart, $periodEnd);
            break;
        case 'sox':
            $data = generateSOXCompliance($periodStart, $periodEnd);
            break;
        case 'bir':
            $data = generateBIRCompliance($periodStart, $periodEnd);
            break;
        case 'ifrs':
            $data = generateIFRSCompliance($periodStart, $periodEnd);
            break;
    }
    
    return $data;
}

/**
 * Generate GAAP compliance data
 */
function generateGAAPCompliance($periodStart, $periodEnd) {
    global $conn;
    
    $data = [
        'report_type' => 'gaap',
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'compliance_score' => 0,
        'issues_found' => []
    ];
    
    // Check if books are balanced
    $stmt = $conn->prepare("
        SELECT 
            SUM(jl.debit) as total_debits,
            SUM(jl.credit) as total_credits
        FROM journal_lines jl
        INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
        WHERE je.entry_date BETWEEN ? AND ?
        AND je.status = 'posted'
    ");
    $stmt->bind_param('ss', $periodStart, $periodEnd);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $isBalanced = abs($result['total_debits'] - $result['total_credits']) < 0.01;
    
    if ($isBalanced) {
        $data['compliance_score'] += 40;
    } else {
        $data['issues_found'][] = 'Books are not balanced - Debits: ' . number_format($result['total_debits'], 2) . ', Credits: ' . number_format($result['total_credits'], 2);
    }
    
    // Check for proper account classifications
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM accounts a
        INNER JOIN account_types at ON a.type_id = at.id
        WHERE a.is_active = 1
        AND at.category IN ('asset', 'liability', 'equity', 'revenue', 'expense')
    ");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $data['compliance_score'] += 30;
    } else {
        $data['issues_found'][] = 'No properly classified accounts found';
    }
    
    // Check for proper documentation
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM journal_entries je
        WHERE je.entry_date BETWEEN ? AND ?
        AND je.description IS NOT NULL
        AND je.description != ''
        AND je.status = 'posted'
    ");
    $stmt->bind_param('ss', $periodStart, $periodEnd);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $data['compliance_score'] += 30;
    } else {
        $data['issues_found'][] = 'Journal entries lack proper documentation';
    }
    
    return $data;
}

/**
 * Generate SOX compliance data
 */
function generateSOXCompliance($periodStart, $periodEnd) {
    global $conn;
    
    $data = [
        'report_type' => 'sox',
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'compliance_score' => 0,
        'issues_found' => []
    ];
    
    // Check for segregation of duties (different users for creation and approval)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM journal_entries je
        WHERE je.entry_date BETWEEN ? AND ?
        AND je.created_by != je.posted_by
        AND je.status = 'posted'
    ");
    $stmt->bind_param('ss', $periodStart, $periodEnd);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $data['compliance_score'] += 50;
    } else {
        $data['issues_found'][] = 'Segregation of duties not properly implemented';
    }
    
    // Check for audit trail completeness
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM audit_logs al
        WHERE al.created_at BETWEEN ? AND ?
        AND al.object_type = 'journal_entry'
    ");
    $stmt->bind_param('ss', $periodStart, $periodEnd);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $data['compliance_score'] += 50;
    } else {
        $data['issues_found'][] = 'Insufficient audit trail documentation';
    }
    
    return $data;
}

/**
 * Generate BIR compliance data
 */
function generateBIRCompliance($periodStart, $periodEnd) {
    global $conn;
    
    $data = [
        'report_type' => 'bir',
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'compliance_score' => 0,
        'issues_found' => []
    ];
    
    // Check for proper tax account setup
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM accounts a
        WHERE a.is_active = 1
        AND (a.name LIKE '%tax%' OR a.name LIKE '%VAT%' OR a.name LIKE '%withholding%')
    ");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $data['compliance_score'] += 40;
    } else {
        $data['issues_found'][] = 'Tax accounts not properly configured';
    }
    
    // Check for proper documentation
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM journal_entries je
        WHERE je.entry_date BETWEEN ? AND ?
        AND je.reference_no IS NOT NULL
        AND je.reference_no != ''
        AND je.status = 'posted'
    ");
    $stmt->bind_param('ss', $periodStart, $periodEnd);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $data['compliance_score'] += 60;
    } else {
        $data['issues_found'][] = 'Journal entries lack proper reference numbers';
    }
    
    return $data;
}

/**
 * Generate IFRS compliance data
 */
function generateIFRSCompliance($periodStart, $periodEnd) {
    global $conn;
    
    $data = [
        'report_type' => 'ifrs',
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'compliance_score' => 0,
        'issues_found' => []
    ];
    
    // Check for proper asset classification
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM accounts a
        INNER JOIN account_types at ON a.type_id = at.id
        WHERE a.is_active = 1
        AND at.category = 'asset'
        AND (a.name LIKE '%current%' OR a.name LIKE '%non-current%' OR a.name LIKE '%fixed%')
    ");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $data['compliance_score'] += 50;
    } else {
        $data['issues_found'][] = 'Asset accounts not properly classified for IFRS';
    }
    
    // Check for proper revenue recognition
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM accounts a
        INNER JOIN account_types at ON a.type_id = at.id
        WHERE a.is_active = 1
        AND at.category = 'revenue'
    ");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $data['compliance_score'] += 50;
    } else {
        $data['issues_found'][] = 'Revenue accounts not properly configured';
    }
    
    return $data;
}

/**
 * Get compliance reports (only non-deleted)
 */
function getComplianceReports() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                cr.*,
                u.full_name as generated_by_name
            FROM compliance_reports cr
            LEFT JOIN users u ON cr.generated_by = u.id
            WHERE cr.deleted_at IS NULL
            ORDER BY cr.created_at DESC
            LIMIT 50
        ");
        
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $reports
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get compliance status
 */
function getComplianceStatus() {
    global $conn;
    
    // Get latest compliance scores for each type
    $stmt = $conn->prepare("
        SELECT 
            report_type,
            compliance_score,
            issues_found,
            generated_date
        FROM compliance_reports
        WHERE status = 'completed'
        AND id IN (
            SELECT MAX(id) 
            FROM compliance_reports 
            WHERE status = 'completed'
            GROUP BY report_type
        )
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $status = [];
    while ($row = $result->fetch_assoc()) {
        $status[$row['report_type']] = [
            'score' => $row['compliance_score'],
            'issues' => $row['issues_found'],
            'last_checked' => $row['generated_date']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $status
    ]);
}

/**
 * Get audit trail
 */
function getAuditTrail() {
    global $conn;
    
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $userFilter = $_GET['user_filter'] ?? '';
    $actionFilter = $_GET['action_filter'] ?? '';
    
    $sql = "SELECT 
                al.*,
                u.username,
                u.full_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($dateFrom)) {
        $sql .= " AND DATE(al.created_at) >= ?";
        $params[] = $dateFrom;
        $types .= 's';
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND DATE(al.created_at) <= ?";
        $params[] = $dateTo;
        $types .= 's';
    }
    
    if (!empty($userFilter) && $userFilter !== 'All Users') {
        $sql .= " AND u.username = ?";
        $params[] = $userFilter;
        $types .= 's';
    }
    
    if (!empty($actionFilter) && $actionFilter !== 'All Actions') {
        $sql .= " AND al.action LIKE ?";
        $params[] = '%' . $actionFilter . '%';
        $types .= 's';
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $logs
    ]);
}

/**
 * Log audit action
 */
function logAuditAction() {
    global $conn, $current_user;
    
    $action = $_POST['action'] ?? '';
    $objectType = $_POST['object_type'] ?? '';
    $objectId = $_POST['object_id'] ?? '';
    $additionalInfo = $_POST['additional_info'] ?? '';
    
    if (empty($action) || empty($objectType)) {
        throw new Exception('Missing required parameters');
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO audit_logs 
        (user_id, ip_address, action, object_type, object_id, additional_info) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $additionalInfoJson = !empty($additionalInfo) ? json_encode($additionalInfo) : null;
    $stmt->bind_param('isssss', $current_user['id'], $ipAddress, $action, $objectType, $objectId, $additionalInfoJson);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Audit action logged successfully'
    ]);
}

/**
 * Get single compliance report details
 */
function getComplianceReport() {
    global $conn;
    
    $reportId = $_GET['report_id'] ?? '';
    
    if (empty($reportId)) {
        throw new Exception('Report ID is required');
    }
    
    $stmt = $conn->prepare("
        SELECT cr.*, u.full_name as generated_by_name
        FROM compliance_reports cr
        LEFT JOIN users u ON cr.generated_by = u.id
        WHERE cr.id = ? AND cr.deleted_at IS NULL
    ");
    $stmt->bind_param('i', $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Report not found');
    }
    
    $report = $result->fetch_assoc();
    
    // Parse report data if it exists, otherwise use issues_found field
    if ($report['report_data']) {
        $reportData = json_decode($report['report_data'], true);
        $report['issues_found'] = $reportData['issues_found'] ?? [];
    } else {
        // If no report_data, parse issues_found field
        $report['issues_found'] = $report['issues_found'] ? 
            explode('. ', trim($report['issues_found'], '.')) : [];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $report
    ]);
}

/**
 * Export compliance report
 */
function exportComplianceReport() {
    global $conn, $current_user;
    
    $reportId = $_GET['report_id'] ?? $_POST['report_id'] ?? '';
    $format = $_GET['format'] ?? $_POST['format'] ?? 'pdf';
    
    if (empty($reportId)) {
        throw new Exception('Report ID is required');
    }
    
    $validFormats = ['pdf', 'excel', 'csv'];
    if (!in_array($format, $validFormats)) {
        throw new Exception('Invalid export format');
    }
    
    // Get report data
    $stmt = $conn->prepare("
        SELECT cr.*, u.full_name as generated_by_name
        FROM compliance_reports cr
        LEFT JOIN users u ON cr.generated_by = u.id
        WHERE cr.id = ? AND cr.deleted_at IS NULL
    ");
    $stmt->bind_param('i', $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Report not found');
    }
    
    $report = $result->fetch_assoc();
    
    // Generate filename
    $filename = sprintf(
        'compliance_report_%s_%s_%s.%s',
        $report['report_type'],
        date('Y-m-d', strtotime($report['period_start'])),
        date('Y-m-d', strtotime($report['period_end'])),
        $format
    );
    
    // Generate export data
    $exportData = generateExportData($report, $format);
    
    // For direct download, set headers and output content
    header('Content-Type: ' . getContentType($format));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($exportData));
    
    // Log audit action
    logAuditActionToDB('Export Compliance Report', 'compliance_report', $reportId, [
        'format' => $format,
        'filename' => $filename
    ]);
    
    echo $exportData;
    exit;
}

/**
 * Soft delete compliance report (move to bin)
 */
function deleteComplianceReport() {
    global $conn, $current_user;
    
    $reportId = $_POST['report_id'] ?? '';
    
    if (empty($reportId)) {
        throw new Exception('Report ID is required');
    }
    
    // Check if report exists and is not already deleted
    $stmt = $conn->prepare("SELECT id FROM compliance_reports WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param('i', $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Report not found or already deleted');
    }
    
    // Soft delete the report
    $stmt = $conn->prepare("UPDATE compliance_reports SET deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $reportId);
    $stmt->execute();
    
    // Log audit action
    logAuditActionToDB('Soft Delete Compliance Report', 'compliance_report', $reportId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Report moved to bin successfully'
    ]);
}

/**
 * Get content type for export format
 */
function getContentType($format) {
    switch ($format) {
        case 'pdf':
            return 'application/pdf';
        case 'excel':
            return 'application/vnd.ms-excel';
        case 'csv':
            return 'text/csv';
        default:
            return 'text/plain';
    }
}

/**
 * Generate export data based on format
 */
function generateExportData($report, $format) {
    $data = '';
    
    switch ($format) {
        case 'pdf':
            // Simple text format for PDF (in production, use TCPDF or similar)
            $data = "COMPLIANCE REPORT\n";
            $data .= "================\n\n";
            $data .= "Report Type: " . strtoupper($report['report_type']) . "\n";
            $data .= "Period: " . $report['period_start'] . " to " . $report['period_end'] . "\n";
            $data .= "Generated: " . $report['generated_date'] . "\n";
            $data .= "Status: " . $report['status'] . "\n";
            $data .= "Compliance Score: " . $report['compliance_score'] . "%\n\n";
            
            if ($report['issues_found']) {
                $issues = json_decode($report['issues_found'], true);
                if (is_array($issues) && !empty($issues)) {
                    $data .= "Issues Found:\n";
                    foreach ($issues as $issue) {
                        $data .= "- " . $issue . "\n";
                    }
                }
            }
            break;
            
        case 'excel':
            // CSV format for Excel compatibility
            $data = "Report Type,Period Start,Period End,Generated Date,Status,Compliance Score,Issues Found\n";
            $data .= '"' . strtoupper($report['report_type']) . '",';
            $data .= '"' . $report['period_start'] . '",';
            $data .= '"' . $report['period_end'] . '",';
            $data .= '"' . $report['generated_date'] . '",';
            $data .= '"' . $report['status'] . '",';
            $data .= '"' . $report['compliance_score'] . '%",';
            $data .= '"' . str_replace('"', '""', $report['issues_found']) . '"';
            break;
            
        case 'csv':
            // CSV format
            $data = "Report Type,Period Start,Period End,Generated Date,Status,Compliance Score,Issues Found\n";
            $data .= '"' . strtoupper($report['report_type']) . '",';
            $data .= '"' . $report['period_start'] . '",';
            $data .= '"' . $report['period_end'] . '",';
            $data .= '"' . $report['generated_date'] . '",';
            $data .= '"' . $report['status'] . '",';
            $data .= '"' . $report['compliance_score'] . '%",';
            $data .= '"' . str_replace('"', '""', $report['issues_found']) . '"';
            break;
    }
    
    return $data;
}

/**
 * Get single audit log details
 */
function getAuditLog() {
    global $conn;
    
    $logId = $_GET['log_id'] ?? '';
    
    if (empty($logId)) {
        throw new Exception('Log ID is required');
    }
    
    $stmt = $conn->prepare("
        SELECT al.*, u.full_name, u.username
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.id = ?
    ");
    $stmt->bind_param('i', $logId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Audit log not found');
    }
    
    $log = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => $log
    ]);
}

/**
 * Export audit log
 */
function exportAuditLog() {
    global $conn;
    
    $logId = $_GET['log_id'] ?? '';
    
    if (empty($logId)) {
        throw new Exception('Log ID is required');
    }
    
    // Get audit log data
    $stmt = $conn->prepare("
        SELECT al.*, u.full_name, u.username
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.id = ?
    ");
    $stmt->bind_param('i', $logId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Audit log not found');
    }
    
    $log = $result->fetch_assoc();
    
    // Generate export content
    $content = "AUDIT LOG EXPORT\n";
    $content .= "================\n\n";
    $content .= "Log ID: " . $log['id'] . "\n";
    $content .= "Action: " . $log['action'] . "\n";
    $content .= "User: " . ($log['full_name'] ?: $log['username']) . "\n";
    $content .= "Timestamp: " . $log['created_at'] . "\n";
    $content .= "IP Address: " . $log['ip_address'] . "\n";
    $content .= "Object Type: " . $log['object_type'] . "\n";
    $content .= "Object ID: " . $log['object_id'] . "\n";
    
    if ($log['additional_info']) {
        $additionalInfo = json_decode($log['additional_info'], true);
        $content .= "\nAdditional Information:\n";
        $content .= "----------------------\n";
        foreach ($additionalInfo as $key => $value) {
            $content .= $key . ": " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    }
    
    // Set headers for download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="audit_log_' . $logId . '.txt"');
    header('Content-Length: ' . strlen($content));
    
    echo $content;
    exit;
}

/**
 * Get all deleted items from bin (compliance reports, transactions, etc.)
 */
function getAllBinItems() {
    global $conn;
    
    $binItems = [];
    
    // Get deleted compliance reports
    $stmt = $conn->prepare("
        SELECT 
            'compliance_report' as item_type,
            cr.id,
            cr.report_type as title,
            cr.period_start,
            cr.period_end,
            cr.deleted_at,
            u.full_name as deleted_by_name,
            cr.compliance_score as score,
            cr.status
        FROM compliance_reports cr
        LEFT JOIN users u ON cr.generated_by = u.id
        WHERE cr.deleted_at IS NOT NULL
        ORDER BY cr.deleted_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $binItems[] = $row;
    }
    
    // Add other deleted item types here in the future
    // Example: deleted transactions, deleted journal entries, etc.
    
    echo json_encode([
        'success' => true,
        'data' => $binItems
    ]);
}

/**
 * Get reports in bin (soft deleted) - DEPRECATED, use getAllBinItems
 */
function getBinReports() {
    getAllBinItems();
}

/**
 * Restore report from bin
 */
function restoreReport() {
    global $conn, $current_user;
    
    $reportId = $_POST['report_id'] ?? '';
    
    if (empty($reportId)) {
        throw new Exception('Report ID is required');
    }
    
    // Check if report exists and is deleted
    $stmt = $conn->prepare("SELECT id FROM compliance_reports WHERE id = ? AND deleted_at IS NOT NULL");
    $stmt->bind_param('i', $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Report not found in bin');
    }
    
    // Restore the report
    $stmt = $conn->prepare("UPDATE compliance_reports SET deleted_at = NULL WHERE id = ?");
    $stmt->bind_param('i', $reportId);
    $stmt->execute();
    
    // Log audit action
    logAuditActionToDB('Restore Compliance Report', 'compliance_report', $reportId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Report restored successfully'
    ]);
}

/**
 * Restore all items from bin
 */
function restoreAllItems() {
    global $conn, $current_user;
    
    $totalRestoredCount = 0;
    $errors = [];
    
    // Restore compliance reports
    $stmt = $conn->prepare("SELECT id FROM compliance_reports WHERE deleted_at IS NOT NULL");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $complianceRestoredCount = 0;
    while ($row = $result->fetch_assoc()) {
        try {
            // Restore each report
            $restoreStmt = $conn->prepare("UPDATE compliance_reports SET deleted_at = NULL WHERE id = ?");
            $restoreStmt->bind_param('i', $row['id']);
            $restoreStmt->execute();
            
            if ($restoreStmt->affected_rows > 0) {
                $complianceRestoredCount++;
                
                // Log audit action
                logAuditActionToDB('Restore All - Compliance Report', 'compliance_report', $row['id']);
            }
        } catch (Exception $e) {
            $errors[] = "Failed to restore compliance report ID {$row['id']}: " . $e->getMessage();
        }
    }
    
    // Restore transactions (journal entries)
    $stmt = $conn->prepare("SELECT id FROM journal_entries WHERE status = 'deleted'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactionRestoredCount = 0;
    while ($row = $result->fetch_assoc()) {
        try {
            // Restore each transaction
            $restoreStmt = $conn->prepare("UPDATE journal_entries SET status = 'draft', deleted_at = NULL, deleted_by = NULL WHERE id = ?");
            $restoreStmt->bind_param('i', $row['id']);
            $restoreStmt->execute();
            
            if ($restoreStmt->affected_rows > 0) {
                $transactionRestoredCount++;
                
                // Log audit action
                logAuditActionToDB('Restore All - Transaction', 'journal_entry', $row['id']);
            }
        } catch (Exception $e) {
            $errors[] = "Failed to restore transaction ID {$row['id']}: " . $e->getMessage();
        }
    }
    
    $totalRestoredCount = $complianceRestoredCount + $transactionRestoredCount;
    
    // Log bulk restore action
    logAuditActionToDB('Restore All Items', 'bin_operation', null, [
        'compliance_restored' => $complianceRestoredCount,
        'transaction_restored' => $transactionRestoredCount,
        'total_restored' => $totalRestoredCount,
        'errors' => $errors
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully restored {$totalRestoredCount} items ({$complianceRestoredCount} reports, {$transactionRestoredCount} transactions)",
        'restored_count' => $totalRestoredCount,
        'compliance_restored' => $complianceRestoredCount,
        'transaction_restored' => $transactionRestoredCount,
        'errors' => $errors
    ]);
}

/**
 * Empty bin (permanently delete all items)
 */
function emptyBin() {
    global $conn, $current_user;
    
    $totalDeletedCount = 0;
    $deletedItems = [];
    
    // Get all deleted compliance reports for logging
    $stmt = $conn->prepare("SELECT id, report_type FROM compliance_reports WHERE deleted_at IS NOT NULL");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $complianceDeletedItems = [];
    while ($row = $result->fetch_assoc()) {
        $complianceDeletedItems[] = $row;
    }
    
    // Permanently delete all compliance reports in bin
    $deleteStmt = $conn->prepare("DELETE FROM compliance_reports WHERE deleted_at IS NOT NULL");
    $deleteStmt->execute();
    $complianceDeletedCount = $deleteStmt->affected_rows;
    
    // Get all deleted transactions for logging
    $stmt = $conn->prepare("SELECT id, journal_no FROM journal_entries WHERE status = 'deleted'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactionDeletedItems = [];
    while ($row = $result->fetch_assoc()) {
        $transactionDeletedItems[] = $row;
    }
    
    // Permanently delete all transactions in bin
    // First delete journal_lines (foreign key constraint)
    $deleteLinesStmt = $conn->prepare("DELETE jl FROM journal_lines jl INNER JOIN journal_entries je ON jl.journal_entry_id = je.id WHERE je.status = 'deleted'");
    $deleteLinesStmt->execute();
    $linesDeletedCount = $deleteLinesStmt->affected_rows;
    
    // Then delete journal_entries
    $deleteStmt = $conn->prepare("DELETE FROM journal_entries WHERE status = 'deleted'");
    $deleteStmt->execute();
    $transactionDeletedCount = $deleteStmt->affected_rows;
    
    $totalDeletedCount = $complianceDeletedCount + $transactionDeletedCount;
    
    // Log bulk delete action
    logAuditActionToDB('Empty Bin - Permanent Delete All', 'bin_operation', null, [
        'compliance_deleted' => $complianceDeletedCount,
        'transaction_deleted' => $transactionDeletedCount,
        'journal_lines_deleted' => $linesDeletedCount,
        'total_deleted' => $totalDeletedCount,
        'deleted_compliance_items' => $complianceDeletedItems,
        'deleted_transaction_items' => $transactionDeletedItems
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully permanently deleted {$totalDeletedCount} items ({$complianceDeletedCount} reports, {$transactionDeletedCount} transactions)",
        'deleted_count' => $totalDeletedCount,
        'compliance_deleted' => $complianceDeletedCount,
        'transaction_deleted' => $transactionDeletedCount
    ]);
}

/**
 * Log audit action to database (internal function)
 */
function logAuditActionToDB($action, $objectType, $objectId, $additionalInfo = []) {
    global $conn, $current_user;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO audit_logs 
        (user_id, ip_address, action, object_type, object_id, additional_info) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $additionalInfoJson = !empty($additionalInfo) ? json_encode($additionalInfo) : null;
    $stmt->bind_param('isssss', $current_user['id'], $ipAddress, $action, $objectType, $objectId, $additionalInfoJson);
    $stmt->execute();
}
