/**
 * Transaction Reading Module JavaScript
 * Handles filtering, export, print, and audit trail functionality
 */

(function() {
    'use strict';

    let dataTable = null;
    window.currentTransactionId = null; // Make it globally accessible

    /**
     * Initialize when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Check if table has actual data rows (not the "no data" message)
        const hasData = document.querySelector('#transactionTable tbody tr:not([colspan])') !== null;
        const hasNoDataMessage = document.querySelector('#transactionTable tbody tr[colspan]') !== null;
        
        console.log('Has data:', hasData);
        console.log('Has no data message:', hasNoDataMessage);
        
        if (hasData) {
            // Initialize DataTables with existing data
            initDataTable();
        } else {
            console.log('No data found, skipping DataTable initialization');
        }
        
        initEventHandlers();
        checkUrlFilters();
    });

    /**
     * Initialize DataTable with enhanced features
     * Only initialized if table has data rows
     */
    function initDataTable() {
        if (typeof $.fn.dataTable === 'undefined') {
            console.warn('DataTables not loaded');
            return;
        }

        const table = $('#transactionTable');
        if (table.length && !$.fn.DataTable.isDataTable('#transactionTable')) {
            // Count actual columns in the table
            const columnCount = table.find('thead th').length;
            console.log('Table column count:', columnCount);
            
            // Only initialize if we have the expected number of columns
            if (columnCount === 10) {
                dataTable = table.DataTable({
                    responsive: true,
                    pageLength: 25,
                    order: [[1, 'desc']], // Order by date descending
                    language: {
                        info: "Showing _START_ to _END_ of _TOTAL_ transactions",
                        infoEmpty: "Showing 0 to 0 of 0 transactions",
                        infoFiltered: "(filtered from _MAX_ total transactions)",
                        lengthMenu: "Show _MENU_ transactions per page",
                        search: "Search transactions:",
                        zeroRecords: "No matching transactions found",
                        emptyTable: "No transaction data available"
                    },
                    columnDefs: [
                        { orderable: false, targets: [9] }, // Actions column not sortable
                        { type: 'date', targets: [1] }, // Date column
                        { className: 'text-end', targets: [5, 6] }, // Debit and Credit columns right-aligned
                        { className: 'text-center', targets: [7] } // Status column centered
                    ],
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
                });
                console.log('DataTable initialized successfully');
            } else {
                console.warn('Column count mismatch. Expected 10, found:', columnCount);
                console.log('Skipping DataTable initialization to prevent errors');
            }
        }
    }

    /**
     * Initialize all event handlers
     */
    function initEventHandlers() {
        // Show/Hide Filter Panel
        const btnShowFilters = document.getElementById('btnShowFilters');
        const filterPanel = document.getElementById('filterPanel');
        
        if (btnShowFilters && filterPanel) {
            btnShowFilters.addEventListener('click', function() {
                if (filterPanel.style.display === 'none' || !filterPanel.style.display) {
                    filterPanel.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-times me-1"></i>Hide Filters';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-secondary');
                } else {
                    filterPanel.style.display = 'none';
                    this.innerHTML = '<i class="fas fa-filter me-1"></i>Apply Filters';
                    this.classList.remove('btn-secondary');
                    this.classList.add('btn-primary');
                }
            });
        }

        // Set max date for date inputs to today
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.setAttribute('max', today);
        });
    }

    /**
     * Check if filters are applied via URL and show filter panel
     */
    function checkUrlFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('apply_filters')) {
            const filterPanel = document.getElementById('filterPanel');
            const btnShowFilters = document.getElementById('btnShowFilters');
            
            if (filterPanel && btnShowFilters) {
                filterPanel.style.display = 'block';
                btnShowFilters.innerHTML = '<i class="fas fa-times me-1"></i>Hide Filters';
                btnShowFilters.classList.remove('btn-primary');
                btnShowFilters.classList.add('btn-secondary');
            }
        }
    }

    /**
     * Clear all filters and reload page
     */
    window.clearFilters = function() {
        window.location.href = window.location.pathname;
    };

    /**
     * Export table data to Excel with professional formatting
     */
    window.exportToExcel = function() {
        // Get table data
        const table = document.getElementById('transactionTable');
        const rows = table.querySelectorAll('tbody tr:not([colspan])');
        
        if (rows.length === 0) {
            showNotification('No data available to export', 'warning');
            return;
        }
        
        // Show loading
        showLoading('Preparing Excel export...');
        
        // Get filter information
        const urlParams = new URLSearchParams(window.location.search);
        const dateFrom = urlParams.get('date_from') || '';
        const dateTo = urlParams.get('date_to') || '';
        const typeFilter = urlParams.get('type') || '';
        const statusFilter = urlParams.get('status') || '';
        const accountFilter = urlParams.get('account') || '';
        const hasFilters = dateFrom || dateTo || typeFilter || statusFilter || accountFilter;
        
        // Calculate totals
        let totalDebit = 0;
        let totalCredit = 0;
        const statusCounts = {};
        const typeCounts = {};
        const transactions = [];
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 9) {
                const journalNo = cells[0].textContent.trim();
                const date = cells[1].textContent.trim();
                const typeFull = cells[2].textContent.trim().replace(/\n/g, ' ').replace(/\s+/g, ' ');
                const typeCode = typeFull.split(' ')[0];
                const typeName = typeFull.substring(typeCode.length).trim();
                const description = cells[3].textContent.trim();
                const reference = cells[4].textContent.trim();
                const debitStr = cells[5].textContent.trim().replace(/,/g, '');
                const creditStr = cells[6].textContent.trim().replace(/,/g, '');
                const status = cells[7].textContent.trim();
                const createdBy = cells[8].textContent.trim();
                
                const debit = parseFloat(debitStr) || 0;
                const credit = parseFloat(creditStr) || 0;
                
                totalDebit += debit;
                totalCredit += credit;
                
                statusCounts[status] = (statusCounts[status] || 0) + 1;
                typeCounts[typeCode] = (typeCounts[typeCode] || 0) + 1;
                
                transactions.push({
                    journalNo,
                    date,
                    typeCode,
                    typeName,
                    description,
                    reference,
                    debit,
                    credit,
                    status,
                    createdBy
                });
            }
        });
        
        // Build professional CSV content
        let csvContent = '';
        
        // Header Section
        csvContent += 'EVERGREEN ACCOUNTING & FINANCE SYSTEM\n';
        csvContent += 'TRANSACTION RECORDING REPORT\n';
        csvContent += '\n';
        csvContent += `Report Generated: ${new Date().toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        })}\n`;
        csvContent += '\n';
        
        // Report Parameters Section
        csvContent += 'REPORT PARAMETERS\n';
        csvContent += '\n';
        if (hasFilters) {
            csvContent += 'Filter Criteria Applied:\n';
            if (dateFrom) csvContent += `  Date From: ${dateFrom}\n`;
            if (dateTo) csvContent += `  Date To: ${dateTo}\n`;
            if (typeFilter) csvContent += `  Transaction Type: ${typeFilter}\n`;
            if (statusFilter) csvContent += `  Status: ${statusFilter.toUpperCase()}\n`;
            if (accountFilter) csvContent += `  Account: ${accountFilter}\n`;
        } else {
            csvContent += '  All Transactions (No Filters Applied)\n';
        }
        csvContent += '\n';
        csvContent += '\n';
        
        // Executive Summary Section
        csvContent += 'EXECUTIVE SUMMARY\n';
        csvContent += '\n';
        csvContent += 'Metric,Value\n';
        csvContent += `Total Transactions,${rows.length}\n`;
        csvContent += `Total Debit Amount,${totalDebit.toFixed(2)}\n`;
        csvContent += `Total Credit Amount,${totalCredit.toFixed(2)}\n`;
        csvContent += `Balance Difference,${(totalDebit - totalCredit).toFixed(2)}\n`;
        csvContent += '\n';
        
        // Summary by Status
        csvContent += 'SUMMARY BY STATUS\n';
        csvContent += '\n';
        csvContent += 'Status,Count,Percentage (%)\n';
        Object.keys(statusCounts).sort().forEach(status => {
            const count = statusCounts[status];
            const percentage = ((count / rows.length) * 100).toFixed(2);
            csvContent += `"${status.toUpperCase()}",${count},${percentage}%\n`;
        });
        csvContent += '\n';
        
        // Summary by Type
        csvContent += 'SUMMARY BY TRANSACTION TYPE\n';
        csvContent += '\n';
        csvContent += 'Type Code,Count,Percentage (%)\n';
        Object.keys(typeCounts).sort().forEach(typeCode => {
            const count = typeCounts[typeCode];
            const percentage = ((count / rows.length) * 100).toFixed(2);
            csvContent += `"${typeCode}",${count},${percentage}%\n`;
        });
        csvContent += '\n';
        csvContent += '\n';
        csvContent += '\n';
        
        // Transaction Details Section
        csvContent += 'TRANSACTION DETAILS\n';
        csvContent += '\n';
        csvContent += 'Journal No.,Date,Type Code,Type Name,Description,Reference No.,Debit Amount,Credit Amount,Status,Created By\n';
        
        transactions.forEach(trans => {
            csvContent += `"${trans.journalNo}",`;
            csvContent += `"${trans.date}",`;
            csvContent += `"${trans.typeCode}",`;
            csvContent += `"${trans.typeName.replace(/"/g, '""')}",`;
            csvContent += `"${trans.description.replace(/"/g, '""')}",`;
            csvContent += `"${trans.reference || '-'}",`;
            csvContent += `${trans.debit.toFixed(2)},`;
            csvContent += `${trans.credit.toFixed(2)},`;
            csvContent += `"${trans.status.toUpperCase()}",`;
            csvContent += `"${trans.createdBy}"\n`;
        });
        
        // Totals Row
        csvContent += '\n';
        csvContent += '"TOTAL","","","","","",';
        csvContent += `${totalDebit.toFixed(2)},`;
        csvContent += `${totalCredit.toFixed(2)},`;
        csvContent += '"",""\n';
        csvContent += '\n';
        csvContent += '\n';
        
        // Footer Section
        csvContent += 'REPORT INFORMATION\n';
        csvContent += '\n';
        csvContent += '"This report was generated by the Evergreen Accounting & Finance System"\n';
        csvContent += `"Report Period: ${hasFilters ? 'Filtered Data' : 'All Available Data'}"\n`;
        csvContent += `"Total Transactions: ${rows.length} transaction(s)"\n`;
        csvContent += `"Total Debit: ${totalDebit.toFixed(2)}"\n`;
        csvContent += `"Total Credit: ${totalCredit.toFixed(2)}"\n`;
        csvContent += `"Balance Check: ${totalDebit === totalCredit ? 'BALANCED' : 'UNBALANCED'}"\n`;
        csvContent += `"Export Date: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}"\n`;
        csvContent += '\n';
        csvContent += '"© ' + new Date().getFullYear() + ' Evergreen Accounting & Finance. All rights reserved."\n';
        
        // Create and download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        const dateStr = new Date().toISOString().split('T')[0];
        const filename = `transaction_report_${dateStr}.csv`;
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        hideLoading();
        showNotification('Excel file exported successfully!', 'success');
    };

    /**
     * Print transaction table
     */
    window.printTable = function() {
        // Hide filter panel before printing
        const filterPanel = document.getElementById('filterPanel');
        const originalDisplay = filterPanel ? filterPanel.style.display : '';
        
        if (filterPanel) {
            filterPanel.style.display = 'none';
        }

        // Print
        window.print();

        // Restore filter panel display
        if (filterPanel) {
            filterPanel.style.display = originalDisplay;
        }
    };

    /**
     * View transaction details
     */
    window.viewTransactionDetails = function(transactionId) {
        window.currentTransactionId = transactionId;
        
        const modal = new bootstrap.Modal(document.getElementById('transactionDetailsModal'));
        const modalBody = document.getElementById('transactionDetailsBody');
        
        // Show loading state
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading transaction details...</p></div>';
        
        modal.show();
        
        // Fetch transaction details from API
        fetch(`api/transaction-data.php?action=get_transaction_details&id=${transactionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const trans = data.data;
                    const statusClass = {
                        'draft': 'status-draft',
                        'posted': 'status-posted',
                        'reversed': 'status-reversed',
                        'voided': 'status-voided'
                    }[trans.status] || 'badge-secondary';
                    
                    // Build journal lines table
                    let linesHtml = '';
                    if (trans.lines && trans.lines.length > 0) {
                        let totalDebit = 0;
                        let totalCredit = 0;
                        
                        trans.lines.forEach(line => {
                            totalDebit += parseFloat(line.debit_amount || 0);
                            totalCredit += parseFloat(line.credit_amount || 0);
                            
                            linesHtml += `
                                <tr>
                                    <td>${line.account_code}</td>
                                    <td>${line.account_name}</td>
                                    <td class="text-end">${parseFloat(line.debit_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                    <td class="text-end">${parseFloat(line.credit_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                    <td>${line.description || '-'}</td>
                                </tr>
                            `;
                        });
                        
                        linesHtml += `
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Total:</td>
                                <td class="text-end">${totalDebit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end">${totalCredit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td></td>
                            </tr>
                        `;
                    } else {
                        linesHtml = '<tr><td colspan="5" class="text-center text-muted">No journal lines found</td></tr>';
                    }
                    
                    modalBody.innerHTML = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Transaction Information</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">Journal No:</dt>
                                    <dd class="col-sm-7"><strong>${trans.journal_no}</strong></dd>
                                    
                                    <dt class="col-sm-5">Type:</dt>
                                    <dd class="col-sm-7"><span class="badge bg-secondary">${trans.type_code}</span> ${trans.type_name}</dd>
                                    
                                    <dt class="col-sm-5">Entry Date:</dt>
                                    <dd class="col-sm-7">${new Date(trans.entry_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'})}</dd>
                                    
                                    <dt class="col-sm-5">Status:</dt>
                                    <dd class="col-sm-7"><span class="badge ${statusClass}">${trans.status.toUpperCase()}</span></dd>
                                    
                                    <dt class="col-sm-5">Reference No:</dt>
                                    <dd class="col-sm-7">${trans.reference_no || '-'}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>User Information</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">Created By:</dt>
                                    <dd class="col-sm-7">${trans.created_by_name}</dd>
                                    
                                    <dt class="col-sm-5">Created At:</dt>
                                    <dd class="col-sm-7">${new Date(trans.created_at).toLocaleString('en-US')}</dd>
                                    
                                    ${trans.posted_by ? `
                                        <dt class="col-sm-5">Posted By:</dt>
                                        <dd class="col-sm-7">${trans.posted_by_name}</dd>
                                        
                                        <dt class="col-sm-5">Posted At:</dt>
                                        <dd class="col-sm-7">${new Date(trans.posted_at).toLocaleString('en-US')}</dd>
                                    ` : ''}
                                    
                                    <dt class="col-sm-5">Fiscal Period:</dt>
                                    <dd class="col-sm-7">${trans.fiscal_period || 'Not specified'}</dd>
                                </dl>
                            </div>
                        </div>
                        
                        ${trans.description ? `
                            <div class="mb-3">
                                <h6 class="text-primary"><i class="fas fa-comment me-2"></i>Description</h6>
                                <p class="mb-0">${trans.description}</p>
                            </div>
                        ` : ''}
                        
                        <div class="mt-4">
                            <h6 class="text-primary mb-3"><i class="fas fa-list me-2"></i>Journal Entries</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account Code</th>
                                            <th>Account Name</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${linesHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.error || 'Unable to load transaction details. Please try again.'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading transaction details:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading transaction details: ${error.message}
                    </div>
                `;
            });
    };

    /**
     * View audit trail for current/all transactions
     */
    window.viewAuditTrail = function(transactionId) {
        if (transactionId) {
            window.currentTransactionId = transactionId;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('auditTrailModal'));
        const modalBody = document.getElementById('auditTrailBody');
        const modalTitle = document.getElementById('auditTrailModalLabel');
        
        // Update modal title
        if (transactionId) {
            modalTitle.innerHTML = '<i class="fas fa-history me-2"></i>Audit Trail - Transaction #' + transactionId;
        } else {
            modalTitle.innerHTML = '<i class="fas fa-history me-2"></i>Audit Trail - All Transactions';
        }
        
        // Show loading state
        modalBody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading audit trail...</p></td></tr>';
        
        modal.show();
        
        // Fetch audit trail from API
        const url = transactionId 
            ? `api/transaction-data.php?action=get_audit_trail&id=${transactionId}`
            : `api/transaction-data.php?action=get_audit_trail`;
            
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(log => {
                        const timestamp = new Date(log.created_at).toLocaleString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        const actionBadge = {
                            'create': 'badge bg-success',
                            'update': 'badge bg-info',
                            'delete': 'badge bg-danger',
                            'post': 'badge bg-primary',
                            'reverse': 'badge bg-warning',
                            'void': 'badge bg-dark'
                        }[log.action] || 'badge bg-secondary';
                        
                        html += `
                            <tr>
                                <td>${timestamp}</td>
                                <td>${log.full_name || log.username || 'System'}</td>
                                <td><span class="${actionBadge}">${log.action.toUpperCase()}</span></td>
                                <td>${log.object_type}</td>
                                <td>#${log.object_id}</td>
                                <td>${log.ip_address || '-'}</td>
                                <td><small>${log.details || '-'}</small></td>
                            </tr>
                        `;
                    });
                    modalBody.innerHTML = html;
                } else {
                    modalBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                                <p>No audit trail entries found${transactionId ? ' for this transaction' : ''}.</p>
                                <small>Audit logs will appear here when actions are performed on transactions.</small>
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading audit trail:', error);
                modalBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-circle fa-2x mb-3 d-block"></i>
                            <p>Error loading audit trail: ${error.message}</p>
                            <small>Please try again or contact system administrator.</small>
                        </td>
                    </tr>
                `;
            });
    };

    /**
     * Export audit trail
     */
    window.exportAuditTrail = function() {
        showNotification('Audit trail export feature will be available when connected to database', 'info');
    };

    /**
     * Delete transaction (soft delete - move to bin)
     */
    window.deleteTransaction = function(transactionId) {
        console.log('Delete transaction called with ID:', transactionId);
        console.log('Transaction ID type:', typeof transactionId);
        console.log('Transaction ID value:', transactionId);
        
        if (!transactionId || transactionId === 'undefined' || transactionId === 'null') {
            showNotification('Error: Invalid transaction ID', 'error');
            return;
        }
        
        if (!confirm('Are you sure you want to delete this transaction? It will be moved to the bin station where you can restore it later.')) {
            return;
        }

        // Show loading
        showLoading('Moving transaction to bin...');

        // Make AJAX call to delete transaction
        const url = 'api/transaction-data.php';
        const data = `action=soft_delete_transaction&transaction_id=${transactionId}`;
        
        console.log('===== DELETE REQUEST DETAILS =====');
        console.log('Making request to:', url);
        console.log('With data:', data);
        console.log('Full URL will be:', window.location.origin + window.location.pathname.replace('transaction-reading.php', '') + url);
        console.log('==================================');
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data
        })
        .then(response => {
            console.log('===== RESPONSE RECEIVED =====');
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            console.log('Response type:', response.type);
            console.log('Response URL:', response.url);
            
            // Check if response is ok before trying to parse JSON
            if (!response.ok) {
                // Try to get error message from response
                return response.text().then(text => {
                    console.error('ERROR RESPONSE TEXT:', text);
                    let errorMessage = `HTTP error! status: ${response.status}`;
                    try {
                        const errorData = JSON.parse(text);
                        console.error('Parsed error data:', errorData);
                        if (errorData.error) {
                            errorMessage += ` - ${errorData.error}`;
                        }
                    } catch (e) {
                        console.error('Could not parse error as JSON:', e);
                        // If not JSON, include raw text
                        if (text.length > 0) {
                            errorMessage += ` - ${text.substring(0, 200)}`;
                        }
                    }
                    throw new Error(errorMessage);
                });
            }
            
            // Get response text first to debug
            return response.text().then(text => {
                console.log('Raw response text:', text);
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text that failed to parse:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Response data:', data);
            hideLoading();
            if (data.success) {
                // Show appropriate message based on what actually happened
                let message = data.message || 'Transaction processed successfully!';
                let notificationType = 'success';
                
                // If soft delete is not available, explain what happened
                if (data.soft_delete_available === false) {
                    message = 'Transaction voided successfully! It has been moved to the bin station where you can restore it later.';
                    notificationType = 'info';
                } else {
                    message = 'Transaction deleted successfully! It has been moved to the bin station where you can restore it later.';
                }
                
                showNotification(message, notificationType);
                
                // Reload the page to refresh the table
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('Delete failed: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            hideLoading();
            
            // Try to get more specific error information
            if (error.message.includes('HTTP error! status: 400')) {
                showNotification('Delete failed: Bad request (400) - Check console for details', 'error');
            } else {
                showNotification('Delete failed: ' + error.message, 'error');
            }
        });
    };

    /**
     * Get current filter parameters
     */
    function getCurrentFilters() {
        const params = new URLSearchParams();
        
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');
        const type = document.getElementById('type');
        const status = document.getElementById('status');
        const account = document.getElementById('account');
        
        if (dateFrom && dateFrom.value) params.append('date_from', dateFrom.value);
        if (dateTo && dateTo.value) params.append('date_to', dateTo.value);
        if (type && type.value) params.append('type', type.value);
        if (status && status.value) params.append('status', status.value);
        if (account && account.value) params.append('account', account.value);
        
        return params.toString();
    }

    /**
     * Show loading overlay
     */
    function showLoading(message = 'Loading...') {
        const overlay = document.createElement('div');
        overlay.className = 'spinner-overlay';
        overlay.id = 'loadingOverlay';
        overlay.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-white mt-3">${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    /**
     * Hide loading overlay
     */
    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Show notification toast
     */
    function showNotification(message, type = 'success') {
        const alertClass = type === 'error' ? 'alert-danger' : 
                          type === 'warning' ? 'alert-warning' :
                          type === 'info' ? 'alert-info' : 'alert-success';
        
        const iconClass = type === 'error' ? 'fa-exclamation-circle' :
                         type === 'warning' ? 'fa-exclamation-triangle' :
                         type === 'info' ? 'fa-info-circle' : 'fa-check-circle';
        
        const toast = document.createElement('div');
        toast.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        toast.style.zIndex = '9999';
        toast.style.minWidth = '300px';
        toast.innerHTML = `
            <i class="fas ${iconClass} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.remove();
        }, 5000);
    }

    // Note: Transaction data is now loaded via PHP server-side rendering
    // No need for AJAX data loading

    /**
     * Sample function to populate transaction data (for demo)
     * In production, this would be replaced with actual database queries
     */
    window.loadSampleData = function() {
        showNotification('Sample data loading is disabled. Connect to database to load actual transactions.', 'info');
    };

    /**
     * Sync bank transactions to journal entries
     */
    window.syncBankTransactions = function() {
        const btn = document.getElementById('btnSyncBankTransactions');
        const originalText = btn.innerHTML;
        
        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Syncing...';
        
        showLoading('Syncing bank transactions to journal entries...');
        
        fetch('../modules/api/transaction-data.php?action=sync_bank_transactions', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (data.success) {
                const message = data.synced_count > 0 
                    ? `Successfully synced ${data.synced_count} bank transaction(s) to journal entries.`
                    : 'No new bank transactions to sync.';
                
                showNotification(message, 'success');
                
                if (data.has_errors && data.errors.length > 0) {
                    console.warn('Sync errors:', data.errors);
                    setTimeout(() => {
                        showNotification('Some transactions had errors. Check console for details.', 'warning');
                    }, 2000);
                }
                
                // Reload page after 2 seconds to show new transactions
                if (data.synced_count > 0) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            } else {
                showNotification('Error: ' + (data.error || 'Failed to sync bank transactions'), 'error');
            }
        })
        .catch(error => {
            hideLoading();
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error('Sync error:', error);
            showNotification('Error syncing bank transactions: ' + error.message, 'error');
        });
    };

})();

