<?php
/**
 * Bin Station - Manage Deleted Compliance Reports
 * Allows users to restore or permanently delete soft-deleted reports
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Verify user is logged in
requireLogin();
$current_user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bin Station - Deleted Items Management</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/image/LOGO.png">
    <link rel="shortcut icon" type="image/png" href="../assets/image/LOGO.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/financial-reporting.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid px-4">
            <div class="logo-section">
                <div class="logo-circle">
                    <img src="../assets/image/LOGO.png" alt="Evergreen Logo" class="logo-img">
                </div>
                <div class="logo-text">
                    <h1>EVERGREEN</h1>
                    <p>Secure. Invest. Achieve</p>
                </div>
            </div>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../core/dashboard.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="modulesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-th-large me-1"></i>Modules
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="modulesDropdown">
                            <li><a class="dropdown-item" href="general-ledger.php"><i class="fas fa-book me-2"></i>General Ledger</a></li>
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-chart-line me-2"></i>Financial Reporting</a></li>
                            <li><a class="dropdown-item active" href="loan-accounting.php"><i class="fas fa-hand-holding-usd me-2"></i>Loan Accounting</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="transaction-reading.php"><i class="fas fa-exchange-alt me-2"></i>Transaction Reading</a></li>
                            <li><a class="dropdown-item" href="expense-tracking.php"><i class="fas fa-receipt me-2"></i>Expense Tracking</a></li>
                            <li><a class="dropdown-item" href="payroll-management.php"><i class="fas fa-users me-2"></i>Payroll Management</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-alt me-1"></i>Reports
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="reportsDropdown">
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-chart-bar me-2"></i>Financial Statements</a></li>
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-money-bill-wave me-2"></i>Cash Flow Report</a></li>
                            <li><a class="dropdown-item" href="expense-tracking.php"><i class="fas fa-clipboard-list me-2"></i>Expense Summary</a></li>
                            <li><a class="dropdown-item" href="payroll-management.php"><i class="fas fa-wallet me-2"></i>Payroll Report</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="settingsDropdown">
                            <li><a class="dropdown-item" href="bin-station.php"><i class="fas fa-trash-alt me-2"></i>Bin Station</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="database-settings.php"><i class="fas fa-database me-2"></i>Database Settings</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Notifications -->
                <div class="dropdown d-none d-md-block">
                    <a class="nav-icon-btn" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom notifications-dropdown" aria-labelledby="notificationsDropdown">
                        <li class="dropdown-header">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-item text-center text-muted"><small>Loading notifications...</small></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="activity-log.php">View All Notifications</a></li>
                    </ul>
                </div>
                
                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <a class="user-profile-btn" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <span class="d-none d-lg-inline"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                        <i class="fas fa-chevron-down ms-2 d-none d-lg-inline"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom" aria-labelledby="userDropdown">
                        <li class="dropdown-header">
                            <div class="user-dropdown-header">
                                <i class="fas fa-user-circle fa-2x"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($current_user['full_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($current_user['username']); ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="activity-log.php"><i class="fas fa-history me-2"></i>Activity Log</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../core/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Beautiful Page Header -->
    <div class="beautiful-page-header mb-5">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="header-content">
                        <h1 class="page-title-beautiful">
                            <i class="fas fa-trash-alt me-3"></i>
                            Bin Station
                        </h1>
                        <p class="page-subtitle-beautiful">
                            Manage all deleted items across the system. Restore accidentally deleted items or permanently remove them.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="header-info-card">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Database Status</div>
                                <div class="info-value status-connected">Connected</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Current Period</div>
                                <div class="info-value"><?php echo date('F Y'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-actions mt-3">
                <a href="../core/dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Bin Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-danger">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div class="stats-content">
                        <h3 id="totalDeleted">0</h3>
                        <p>Total Deleted</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-content">
                        <h3 id="recentDeleted">0</h3>
                        <p>Deleted Today</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stats-content">
                        <h3 id="oldestDeleted">-</h3>
                        <p>Oldest Deleted</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="stats-content">
                        <h3 id="spaceSaved">0 MB</h3>
                        <p>Space Saved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bin Management -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Deleted Items</h4>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-success" onclick="restoreAll()">
                            <i class="fas fa-undo me-2"></i>Restore All
                        </button>
                        <button class="btn btn-outline-danger" onclick="emptyBin()">
                            <i class="fas fa-trash me-2"></i>Empty Bin
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="binItems">
                    <div class="text-center py-5">
                        <div class="loading-spinner"></div>
                        <p class="mt-3">Loading deleted reports...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/notifications.js"></script>

    <script>
        // Load bin data on page load
        $(document).ready(function() {
            loadBinData();
        });

        /**
         * Load bin data
         */
        function loadBinData() {
            console.log('Loading bin data...');
            const container = document.getElementById('binItems');
            
            // Show loading state
            container.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border spinner-border-lg text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading deleted items...</p>
                </div>
            `;
            
            // Load compliance reports, transactions, and loans from bin
            $.when(
                $.ajax({
                    url: 'api/compliance-reports.php',
                    method: 'GET',
                    data: { action: 'get_all_bin_items' },
                    dataType: 'json',
                    timeout: 10000
                }).catch(function(xhr, status, error) {
                    console.error('Error loading compliance reports:', error);
                    return [{success: false, data: []}];
                }),
                $.ajax({
                    url: 'api/transaction-data.php',
                    method: 'GET',
                    data: { action: 'get_bin_items' },
                    dataType: 'json',
                    timeout: 10000
                }).catch(function(xhr, status, error) {
                    console.error('Error loading transaction data:', error);
                    return [{success: false, data: []}];
                }),
                $.ajax({
                    url: 'api/loan-data.php',
                    method: 'GET',
                    data: { action: 'get_bin_items' },
                    dataType: 'json',
                    timeout: 10000
                }).catch(function(xhr, status, error) {
                    console.error('Error loading loan data:', error);
                    return [{success: false, data: []}];
                })
            ).done(function(complianceResponse, transactionResponse, loanResponse) {
                console.log('Compliance response:', complianceResponse);
                console.log('Transaction response:', transactionResponse);
                
                const complianceData = complianceResponse[0] && complianceResponse[0].success ? complianceResponse[0].data : [];
                const transactionData = transactionResponse[0] && transactionResponse[0].success ? transactionResponse[0].data : [];
                const loanData = loanResponse[0] && loanResponse[0].success ? loanResponse[0].data : [];
                
                console.log('Compliance data:', complianceData);
                console.log('Transaction data:', transactionData);
                console.log('Loan data:', loanData);
                
                // Combine all bin items
                const allItems = [...complianceData, ...transactionData, ...loanData];
                
                console.log('Total bin items:', allItems.length);
                
                updateBinDisplay(allItems);
                updateBinStats(allItems);
            }).fail(function(xhr, status, error) {
                console.error('Failed to load bin data:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                showBinError('Connection error: ' + (error || 'Unable to load bin data. Please check your database connection.'));
            });
        }

        /**
         * Update bin display
         */
        function updateBinDisplay(items) {
            console.log('updateBinDisplay called with', items);
            const container = document.getElementById('binItems');
            
            if (!items || items.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-trash-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Bin is Empty</h5>
                        <p class="text-muted">No deleted items found.</p>
                    </div>
                `;
                return;
            }
            
            console.log('Displaying', items.length, 'items');
            
            let html = '';
            items.forEach(item => {
                const deletedDate = item.deleted_at ? new Date(item.deleted_at).toLocaleString() : 'Unknown';
                const itemTypeLabel = getItemTypeLabel(item.item_type);
                const itemIcon = getItemTypeIcon(item.item_type);
                const title = item.title || item.description || item.journal_no || item.loan_number || 'Item';
                
                html += `
                    <div class="bin-item border border-light rounded p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas ${itemIcon} me-2 text-primary fa-lg"></i>
                                    <div>
                                        <strong>${itemTypeLabel}</strong>
                                        <br><small class="text-muted">${title}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">
                                    ${item.period_start ? formatDate(item.period_start) : (item.entry_date ? formatDate(item.entry_date) : (item.start_date ? formatDate(item.start_date) : 'N/A'))}
                                </small>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">
                                    ${item.score ? item.score + '%' : 
                                      (item.total_debit ? '₱' + parseFloat(item.total_debit).toFixed(2) : 
                                      (item.loan_amount ? '₱' + parseFloat(item.loan_amount).toFixed(2) : 'N/A'))}
                                </small>
                            </div>
                            <div class="col-md-2">
                                <span class="badge bg-danger">
                                    <i class="fas fa-trash me-1"></i>
                                    ${deletedDate}
                                </span>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-success" onclick="restoreItem('${item.item_type}', ${item.id})" title="Restore Item">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="permanentDeleteItem('${item.item_type}', ${item.id})" title="Permanently Delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        /**
         * Update bin statistics
         */
        function updateBinStats(reports) {
            const totalDeleted = reports.length;
            const today = new Date().toDateString();
            const recentDeleted = reports.filter(r => new Date(r.deleted_at).toDateString() === today).length;
            
            const oldestDeleted = reports.length > 0 ? 
                reports.reduce((oldest, current) => 
                    new Date(current.deleted_at) < new Date(oldest.deleted_at) ? current : oldest
                ) : null;
            
            document.getElementById('totalDeleted').textContent = totalDeleted;
            document.getElementById('recentDeleted').textContent = recentDeleted;
            document.getElementById('oldestDeleted').textContent = oldestDeleted ? 
                formatDate(oldestDeleted.deleted_at) : '-';
            document.getElementById('spaceSaved').textContent = (totalDeleted * 0.5).toFixed(1) + ' MB';
        }

        /**
         * Restore report
         */
        function restoreReport(reportId) {
            if (!confirm('Are you sure you want to restore this compliance report?')) {
                return;
            }

            $.ajax({
                url: 'api/compliance-reports.php',
                method: 'POST',
                data: { 
                    action: 'restore_report',
                    report_id: reportId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Report restored successfully!', 'success');
                        loadBinData(); // Refresh bin
                    } else {
                        showNotification('Restore failed: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Restore failed: ' + error, 'error');
                }
            });
        }

        /**
         * Permanently delete report
         */
        function permanentDeleteReport(reportId) {
            if (!confirm('Are you sure you want to permanently delete this compliance report? This action cannot be undone.')) {
                return;
            }

            $.ajax({
                url: 'api/compliance-reports.php',
                method: 'POST',
                data: { 
                    action: 'permanent_delete_report',
                    report_id: reportId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Report permanently deleted!', 'success');
                        loadBinData(); // Refresh bin
                    } else {
                        showNotification('Delete failed: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Delete failed: ' + error, 'error');
                }
            });
        }

        /**
         * Restore all reports
         */
        function restoreAll() {
            if (!confirm('Are you sure you want to restore ALL deleted items? This will move all items back to their active state.')) {
                return;
            }

            // Show loading state
            const button = event.target;
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Restoring...';
            button.disabled = true;

            $.ajax({
                url: 'api/transaction-data.php',
                method: 'POST',
                data: { 
                    action: 'restore_all_transactions'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification(`Successfully restored ${response.restored_count} items!`, 'success');
                        loadBinData(); // Refresh bin
                        
                        // Show any errors if they occurred
                        if (response.errors && response.errors.length > 0) {
                            console.warn('Some items failed to restore:', response.errors);
                        }
                    } else {
                        showNotification('Restore all failed: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Restore all failed: ' + error, 'error');
                },
                complete: function() {
                    // Reset button state
                    button.innerHTML = originalContent;
                    button.disabled = false;
                }
            });
        }

        /**
         * Empty bin (permanently delete all)
         */
        function emptyBin() {
            if (!confirm('WARNING: Are you sure you want to PERMANENTLY DELETE ALL items in the bin? This action cannot be undone and will permanently remove all deleted items.')) {
                return;
            }

            // Double confirmation for safety
            if (!confirm('This is your final warning. Click OK to permanently delete ALL items in the bin. This cannot be undone.')) {
                return;
            }

            // Show loading state
            const button = event.target;
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
            button.disabled = true;

            $.ajax({
                url: 'api/transaction-data.php',
                method: 'POST',
                data: { 
                    action: 'empty_bin_transactions'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification(`Successfully permanently deleted ${response.deleted_count} items!`, 'success');
                        loadBinData(); // Refresh bin
                    } else {
                        showNotification('Empty bin failed: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Empty bin failed: ' + error, 'error');
                },
                complete: function() {
                    // Reset button state
                    button.innerHTML = originalContent;
                    button.disabled = false;
                }
            });
        }

        /**
         * Refresh bin
         */
        function refreshBin() {
            loadBinData();
        }

        /**
         * Show bin error
         */
        function showBinError(message) {
            const container = document.getElementById('binItems');
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger">Error Loading Bin</h5>
                    <p class="text-muted">${message}</p>
                    <button class="btn btn-primary" onclick="loadBinData()">
                        <i class="fas fa-refresh me-2"></i>Try Again
                    </button>
                </div>
            `;
        }

        /**
         * Show notification
         */
        function showNotification(message, type = 'info') {
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'error' ? 'alert-danger' : 'alert-info';
            
            const notification = document.createElement('div');
            notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        // Helper functions
        function getItemTypeLabel(type) {
            const labels = {
                'compliance_report': 'Compliance Report',
                'transaction': 'Transaction',
                'journal_entry': 'Journal Entry',
                'expense': 'Expense',
                'payroll': 'Payroll Record',
                'loan': 'Loan'
            };
            return labels[type] || 'Unknown Item';
        }
        
        function getItemTypeIcon(type) {
            const icons = {
                'compliance_report': 'fa-chart-line',
                'transaction': 'fa-exchange-alt',
                'journal_entry': 'fa-book',
                'expense': 'fa-receipt',
                'payroll': 'fa-users',
                'loan': 'fa-hand-holding-usd'
            };
            return icons[type] || 'fa-file';
        }
        
        function restoreItem(itemType, itemId) {
            if (itemType === 'compliance_report') {
                restoreReport(itemId);
            } else if (itemType === 'journal_entry') {
                restoreTransaction(itemId);
            } else if (itemType === 'loan') {
                restoreLoan(itemId);
            } else {
                showNotification('Restore functionality for ' + itemType + ' not yet implemented.', 'info');
            }
        }
        
        function permanentDeleteItem(itemType, itemId) {
            if (itemType === 'compliance_report') {
                permanentDeleteReport(itemId);
            } else if (itemType === 'journal_entry') {
                permanentDeleteTransaction(itemId);
            } else if (itemType === 'loan') {
                permanentDeleteLoan(itemId);
            } else {
                showNotification('Permanent delete functionality for ' + itemType + ' not yet implemented.', 'info');
            }
        }
        
        function restoreReport(reportId) {
            if (!confirm('Are you sure you want to restore this report? It will be moved back to active compliance reports.')) {
                return;
            }
            $.ajax({
                url: 'api/compliance-reports.php',
                method: 'POST',
                data: { action: 'restore_report', report_id: reportId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Report restored successfully!', 'success');
                        loadBinData();
                    } else {
                        showNotification('Restore failed: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Restore failed: ' + error, 'error');
                }
            });
        }
        
        function permanentDeleteReport(reportId) {
            if (!confirm('WARNING: Are you sure you want to permanently delete this report? This action cannot be undone.')) {
                return;
            }
            $.ajax({
                url: 'api/compliance-reports.php',
                method: 'POST',
                data: { action: 'permanent_delete_report', report_id: reportId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Report permanently deleted!', 'success');
                        loadBinData();
                    } else {
                        showNotification('Permanent delete failed: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Permanent delete failed: ' + error, 'error');
                }
            });
        }
        
        function refreshBin() {
            loadBinData();
        }
        
        // Note: showNotification is already defined above, so this is a duplicate
        // Keeping the better version above and removing this
        // function showNotification(message, type = 'info') {
        //     // Simple notification - you can enhance this
        //     alert(message);
        // }
        
        function getReportTypeLabel(type) {
            const labels = {
                'gaap': 'GAAP Compliance',
                'sox': 'SOX Compliance', 
                'bir': 'BIR Compliance',
                'ifrs': 'IFRS Compliance'
            };
            return labels[type] || type.toUpperCase();
        }

        function getReportTypeIcon(type) {
            const icons = {
                'gaap': 'fa-balance-scale',
                'sox': 'fa-shield-alt',
                'bir': 'fa-file-invoice',
                'ifrs': 'fa-globe'
            };
            return icons[type] || 'fa-file-alt';
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            try {
                return new Date(dateString).toLocaleDateString();
            } catch (e) {
                return dateString;
            }
        }

        function formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            try {
                return new Date(dateString).toLocaleString();
            } catch (e) {
                return dateString;
            }
        }

        // Note: restoreTransaction is already defined in the restoreItem function context
        // This is the dedicated transaction restoration function called by restoreItem
        function restoreTransaction(transactionId) {
            if (!confirm('Are you sure you want to restore this transaction? It will be moved back to active transactions.')) {
                return;
            }

            $.ajax({
                url: 'api/transaction-data.php',
                method: 'POST',
                data: { 
                    action: 'restore_transaction',
                    transaction_id: transactionId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Transaction restored successfully!', 'success');
                        loadBinData(); // Refresh bin
                    } else {
                        showNotification('Restore failed: ' + (response.error || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Restore transaction error:', error);
                    showNotification('Restore failed: ' + error, 'error');
                }
            });
        }

        /**
         * Permanently delete transaction
         */
        function permanentDeleteTransaction(transactionId) {
            if (!confirm('WARNING: Are you sure you want to permanently delete this transaction? This action cannot be undone.')) {
                return;
            }

            $.ajax({
                url: 'api/transaction-data.php',
                method: 'POST',
                data: { 
                    action: 'permanent_delete_transaction',
                    transaction_id: transactionId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Transaction permanently deleted!', 'success');
                        loadBinData(); // Refresh bin
                    } else {
                        showNotification('Permanent delete failed: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Permanent delete failed: ' + error, 'error');
                }
            });
        }

        /**
         * Restore loan from bin
         */
        function restoreLoan(loanId) {
            if (!confirm('Are you sure you want to restore this loan? It will be moved back to active loans.')) {
                return;
            }

            $.ajax({
                url: 'api/loan-data.php',
                method: 'POST',
                data: { 
                    action: 'restore_loan',
                    loan_id: loanId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Loan restored successfully!', 'success');
                        loadBinData(); // Refresh bin
                    } else {
                        showNotification('Restore failed: ' + (response.error || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Restore loan error:', error);
                    showNotification('Restore failed: ' + error, 'error');
                }
            });
        }

        /**
         * Permanently delete loan from bin
         */
        function permanentDeleteLoan(loanId) {
            if (!confirm('WARNING: Are you sure you want to permanently delete this loan? This action cannot be undone.')) {
                return;
            }

            $.ajax({
                url: 'api/loan-data.php',
                method: 'POST',
                data: { 
                    action: 'permanent_delete_loan',
                    loan_id: loanId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Loan permanently deleted!', 'success');
                        loadBinData(); // Refresh bin
                    } else {
                        showNotification('Permanent delete failed: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Permanent delete failed: ' + error, 'error');
                }
            });
        }
    </script>
</body>
</html>
