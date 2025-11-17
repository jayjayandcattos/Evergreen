/**
 * Loan Accounting Module JavaScript
 * Handles filtering, export, print, and audit trail functionality
 */

(function() {
    'use strict';

    let dataTable = null;
    let currentLoanId = null;

    /**
     * Initialize when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Check if table has actual data rows (not the "no data" message)
        const hasData = document.querySelector('#loanTable tbody tr') !== null;
        const isEmpty = document.querySelector('.empty-state') !== null;
        
        console.log('Has data:', hasData);
        console.log('Is empty:', isEmpty);
        
        if (hasData && !isEmpty) {
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

        const table = $('#loanTable');
        if (table.length && !$.fn.DataTable.isDataTable('#loanTable')) {
            // Count actual columns in the table
            const columnCount = table.find('thead th').length;
            console.log('Table column count:', columnCount);
            
            // Only initialize if we have the expected number of columns
            if (columnCount === 10) {
                dataTable = table.DataTable({
                    responsive: true,
                    pageLength: 25,
                    order: [[2, 'desc']], // Order by start date descending
                    language: {
                        info: "Showing _START_ to _END_ of _TOTAL_ loans",
                        infoEmpty: "Showing 0 to 0 of 0 loans",
                        infoFiltered: "(filtered from _MAX_ total loans)",
                        lengthMenu: "Show _MENU_ loans per page",
                        search: "Search loans:",
                        zeroRecords: "No matching loans found",
                        emptyTable: "No loan data available"
                    },
                    columnDefs: [
                        { orderable: false, targets: [9] }, // Actions column not sortable
                        { type: 'date', targets: [2, 3] }, // Date columns
                        { className: 'text-end', targets: [4, 6] }, // Amount columns right-aligned
                        { className: 'text-center', targets: [5, 7, 8] } // Rate, Type, Status centered
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
        const table = document.getElementById('loanTable');
        const rows = table ? table.querySelectorAll('tbody tr') : [];
        
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
        const statusFilter = urlParams.get('status') || '';
        const accountFilter = urlParams.get('account_number') || '';
        const hasFilters = dateFrom || dateTo || statusFilter || accountFilter;
        
        // Calculate totals
        let totalLoans = 0;
        let totalLoanAmount = 0;
        let totalOutstanding = 0;
        const statusCounts = {};
        const loans = [];
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 9) {
                const loanNo = cells[0].textContent.trim();
                const borrower = cells[1].textContent.trim();
                const loanType = cells[2].textContent.trim();
                const startDate = cells[3].textContent.trim();
                const maturityDate = cells[4].textContent.trim();
                const loanAmountStr = cells[5].textContent.trim().replace(/[₱,]/g, '');
                const interestRate = cells[6].textContent.trim();
                const outstandingStr = cells[7].textContent.trim().replace(/[₱,]/g, '');
                const status = cells[8].textContent.trim();
                
                const loanAmount = parseFloat(loanAmountStr) || 0;
                const outstanding = parseFloat(outstandingStr) || 0;
                
                totalLoans++;
                totalLoanAmount += loanAmount;
                totalOutstanding += outstanding;
                
                statusCounts[status] = (statusCounts[status] || 0) + 1;
                
                loans.push({
                    loanNo,
                    borrower,
                    loanType,
                    startDate,
                    maturityDate,
                    loanAmount,
                    interestRate,
                    outstanding,
                    status
                });
            }
        });
        
        // Build professional CSV content
        let csvContent = '';
        
        // Header Section
        csvContent += 'EVERGREEN ACCOUNTING & FINANCE SYSTEM\n';
        csvContent += 'LOAN ACCOUNTING REPORT\n';
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
            if (statusFilter) csvContent += `  Status: ${statusFilter.toUpperCase()}\n`;
            if (accountFilter) csvContent += `  Loan Number: ${accountFilter}\n`;
        } else {
            csvContent += '  All Loans (No Filters Applied)\n';
        }
        csvContent += '\n';
        csvContent += '\n';
        
        // Executive Summary Section
        csvContent += 'EXECUTIVE SUMMARY\n';
        csvContent += '\n';
        csvContent += 'Metric,Value\n';
        csvContent += `Total Loans,${totalLoans}\n`;
        csvContent += `Total Loan Amount,${totalLoanAmount.toFixed(2)}\n`;
        csvContent += `Total Outstanding Balance,${totalOutstanding.toFixed(2)}\n`;
        csvContent += `Total Amount Paid,${(totalLoanAmount - totalOutstanding).toFixed(2)}\n`;
        csvContent += `Average Loan Size,${(totalLoans > 0 ? (totalLoanAmount / totalLoans).toFixed(2) : '0.00')}\n`;
        csvContent += '\n';
        
        // Summary by Status
        csvContent += 'SUMMARY BY STATUS\n';
        csvContent += '\n';
        csvContent += 'Status,Count,Percentage (%)\n';
        Object.keys(statusCounts).sort().forEach(status => {
            const count = statusCounts[status];
            const percentage = ((count / totalLoans) * 100).toFixed(2);
            csvContent += `"${status.toUpperCase()}",${count},${percentage}%\n`;
        });
        csvContent += '\n';
        csvContent += '\n';
        csvContent += '\n';
        
        // Loan Details Section
        csvContent += 'LOAN DETAILS\n';
        csvContent += '\n';
        csvContent += 'Loan No.,Borrower,Loan Type,Start Date,Maturity Date,Loan Amount,Interest Rate,Outstanding Balance,Status\n';
        
        loans.forEach(loan => {
            csvContent += `"${loan.loanNo}",`;
            csvContent += `"${loan.borrower.replace(/"/g, '""')}",`;
            csvContent += `"${loan.loanType.replace(/"/g, '""')}",`;
            csvContent += `"${loan.startDate}",`;
            csvContent += `"${loan.maturityDate}",`;
            csvContent += `${loan.loanAmount.toFixed(2)},`;
            csvContent += `"${loan.interestRate}",`;
            csvContent += `${loan.outstanding.toFixed(2)},`;
            csvContent += `"${loan.status.toUpperCase()}"\n`;
        });
        
        // Totals Row
        csvContent += '\n';
        csvContent += '"TOTAL","","","","",';
        csvContent += `${totalLoanAmount.toFixed(2)},"",`;
        csvContent += `${totalOutstanding.toFixed(2)},""\n`;
        csvContent += '\n';
        csvContent += '\n';
        
        // Footer Section
        csvContent += 'REPORT INFORMATION\n';
        csvContent += '\n';
        csvContent += '"This report was generated by the Evergreen Accounting & Finance System"\n';
        csvContent += `"Report Period: ${hasFilters ? 'Filtered Data' : 'All Available Data'}"\n`;
        csvContent += `"Total Loans: ${totalLoans} loan(s)"\n`;
        csvContent += `"Total Loan Amount: ${totalLoanAmount.toFixed(2)}"\n`;
        csvContent += `"Total Outstanding: ${totalOutstanding.toFixed(2)}"\n`;
        csvContent += `"Collection Rate: ${totalLoanAmount > 0 ? (((totalLoanAmount - totalOutstanding) / totalLoanAmount) * 100).toFixed(2) : '0.00'}%"\n`;
        csvContent += `"Export Date: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}"\n`;
        csvContent += '\n';
        csvContent += '"© ' + new Date().getFullYear() + ' Evergreen Accounting & Finance. All rights reserved."\n';
        
        // Create and download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        const dateStr = new Date().toISOString().split('T')[0];
        const filename = `loan_report_${dateStr}.csv`;
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
     * Print loan table
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
     * View application details
     */
    window.viewApplicationDetails = function(applicationId) {
        currentLoanId = applicationId;
        
        const modal = new bootstrap.Modal(document.getElementById('loanDetailsModal'));
        const modalBody = document.getElementById('loanDetailsBody');
        
        // Update modal title
        const modalTitle = document.querySelector('#loanDetailsModal .modal-title');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-file-alt me-2"></i>Loan Application Details';
        }
        
        // Show loading state
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading application details...</p></div>';
        
        modal.show();
        
        // Fetch application details from API
        fetch('api/loan-data.php?action=get_application_details&id=' + applicationId)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    displayApplicationDetails(data.data);
                } else {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Error:</strong> ${data.error || 'Failed to load application details'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching application details:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Error Loading Application Details</strong>
                        <p class="mb-2 mt-2">Unable to fetch application details from the server.</p>
                        <p class="mb-0"><small>Error: ${error.message}</small></p>
                    </div>
                `;
            });
    };
    
    /**
     * Display application details in modal
     */
    function displayApplicationDetails(app) {
        const modalBody = document.getElementById('loanDetailsBody');
        
        const loanAmount = parseFloat(app.loan_amount || 0);
        const monthlyPayment = parseFloat(app.monthly_payment || 0);
        
        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="loan-detail-section">
                        <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Applicant Information</h6>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Application Number:</span>
                            <span class="loan-detail-value"><strong>${app.application_number || 'APP-' + app.application_id || 'N/A'}</strong></span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Full Name:</span>
                            <span class="loan-detail-value">${app.full_name || app.borrower_name || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Account Number:</span>
                            <span class="loan-detail-value">${app.account_number || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Contact Number:</span>
                            <span class="loan-detail-value">${app.contact_number || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Email:</span>
                            <span class="loan-detail-value">${app.email || app.user_email || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Job/Position:</span>
                            <span class="loan-detail-value">${app.job || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Monthly Salary:</span>
                            <span class="loan-detail-value">${app.monthly_salary ? '₱' + parseFloat(app.monthly_salary).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'N/A'}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="loan-detail-section">
                        <h6 class="text-primary mb-3"><i class="fas fa-file-contract me-2"></i>Loan Application Details</h6>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Loan Type:</span>
                            <span class="loan-detail-value">${app.loan_type_name || app.loan_type || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Loan Terms:</span>
                            <span class="loan-detail-value">${app.loan_terms || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Loan Amount:</span>
                            <span class="loan-detail-value"><strong class="text-primary">₱${loanAmount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Monthly Payment:</span>
                            <span class="loan-detail-value"><strong class="text-success">₱${monthlyPayment.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Due Date:</span>
                            <span class="loan-detail-value">${app.due_date ? formatDate(app.due_date) : 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Next Payment Due:</span>
                            <span class="loan-detail-value">${app.next_payment_due ? formatDate(app.next_payment_due) : 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Purpose:</span>
                            <span class="loan-detail-value">${app.purpose || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Status:</span>
                            <span class="loan-detail-value"><span class="badge status-${(app.status || '').toLowerCase()}">${(app.status || 'N/A').toUpperCase()}</span></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="loan-detail-section">
                <h6 class="text-primary mb-3"><i class="fas fa-clipboard-check me-2"></i>Application Workflow</h6>
                <div class="row">
                    <div class="col-md-6">
                        ${app.approved_by || app.approved_at ? `
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Approved By:</span>
                            <span class="loan-detail-value">${app.approved_by_name || app.approved_by || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Approved At:</span>
                            <span class="loan-detail-value">${app.approved_at ? new Date(app.approved_at).toLocaleString('en-US') : 'N/A'}</span>
                        </div>
                        ` : ''}
                        ${app.remarks ? `
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Remarks:</span>
                            <span class="loan-detail-value">${app.remarks}</span>
                        </div>
                        ` : ''}
                    </div>
                    <div class="col-md-6">
                        ${app.rejected_by || app.rejected_at ? `
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Rejected By:</span>
                            <span class="loan-detail-value">${app.rejected_by_name || app.rejected_by || 'N/A'}</span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Rejected At:</span>
                            <span class="loan-detail-value">${app.rejected_at ? new Date(app.rejected_at).toLocaleString('en-US') : 'N/A'}</span>
                        </div>
                        ${app.rejection_remarks ? `
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Rejection Remarks:</span>
                            <span class="loan-detail-value text-danger">${app.rejection_remarks}</span>
                        </div>
                        ` : ''}
                        ` : ''}
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Created At:</span>
                            <span class="loan-detail-value"><small>${app.created_at ? new Date(app.created_at).toLocaleString('en-US') : 'N/A'}</small></span>
                        </div>
                    </div>
                </div>
            </div>
            
            ${(app.proof_of_income || app.coe_document || app.file_name || app.pdf_path) ? `
            <div class="loan-detail-section mt-4">
                <h6 class="text-primary mb-3"><i class="fas fa-file-upload me-2"></i>Supporting Documents</h6>
                <div class="row">
                    ${app.file_name ? `
                    <div class="col-md-6 mb-2">
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Valid ID:</span>
                            <span class="loan-detail-value">
                                <a href="../../LoanSubsystem/${app.file_name}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file me-1"></i>View File
                                </a>
                            </span>
                        </div>
                    </div>
                    ` : ''}
                    ${app.proof_of_income ? `
                    <div class="col-md-6 mb-2">
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Proof of Income:</span>
                            <span class="loan-detail-value">
                                <a href="../../LoanSubsystem/${app.proof_of_income}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file me-1"></i>View File
                                </a>
                            </span>
                        </div>
                    </div>
                    ` : ''}
                    ${app.coe_document ? `
                    <div class="col-md-6 mb-2">
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Certificate of Employment:</span>
                            <span class="loan-detail-value">
                                <a href="../../LoanSubsystem/${app.coe_document}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file me-1"></i>View File
                                </a>
                            </span>
                        </div>
                    </div>
                    ` : ''}
                    ${app.pdf_path ? `
                    <div class="col-md-6 mb-2">
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Application PDF:</span>
                            <span class="loan-detail-value">
                                <a href="../../LoanSubsystem/${app.pdf_path}" target="_blank" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-file-pdf me-1"></i>View PDF
                                </a>
                            </span>
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}
        `;
        
        modalBody.innerHTML = html;
    }

    /**
     * View loan details
     */
    window.viewLoanDetails = function(loanId) {
        currentLoanId = loanId;
        
        const modal = new bootstrap.Modal(document.getElementById('loanDetailsModal'));
        const modalBody = document.getElementById('loanDetailsBody');
        
        // Update modal title
        const modalTitle = document.querySelector('#loanDetailsModal .modal-title');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-file-invoice-dollar me-2"></i>Loan Details';
        }
        
        // Show loading state
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading loan details...</p></div>';
        
        modal.show();
        
        // Fetch loan details from API
        fetch('api/loan-data.php?action=get_loan_details&id=' + loanId)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    displayLoanDetails(data.data);
                } else {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Error:</strong> ${data.error || 'Failed to load loan details'}
                        </div>
                        <p class="text-muted">Please check the console for more details.</p>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching loan details:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Error Loading Loan Details</strong>
                        <p class="mb-2 mt-2">Unable to fetch loan details from the server.</p>
                        <p class="mb-0"><small>Error: ${error.message}</small></p>
                    </div>
                    <div class="mt-3">
                        <p class="text-muted">Please ensure:</p>
                        <ul class="text-muted">
                            <li>The database is properly connected</li>
                            <li>The loan record exists in the database</li>
                            <li>You have proper permissions to view loan details</li>
                        </ul>
                    </div>
                `;
            });
    };

    /**
     * Display loan details in modal
     */
    function displayLoanDetails(loan) {
        const modalBody = document.getElementById('loanDetailsBody');
        
        // Calculate values safely
        const loanAmount = parseFloat(loan.loan_amount || 0);
        const outstandingBalance = parseFloat(loan.outstanding_balance || 0);
        const interestRate = parseFloat(loan.interest_rate || 0);
        const amountPaid = loanAmount - outstandingBalance;
        const paymentPercentage = loanAmount > 0 ? ((amountPaid / loanAmount) * 100).toFixed(2) : '0.00';
        
        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
            <div class="loan-detail-section">
                        <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Loan Information</h6>
                <div class="loan-detail-row">
                    <span class="loan-detail-label">Loan Number:</span>
                            <span class="loan-detail-value"><strong>${loan.loan_number || loan.loan_no || 'N/A'}</strong></span>
                </div>
                <div class="loan-detail-row">
                    <span class="loan-detail-label">Borrower Name:</span>
                            <span class="loan-detail-value">${loan.borrower_name || loan.borrower_external_no || 'N/A'}</span>
                </div>
                <div class="loan-detail-row">
                    <span class="loan-detail-label">Loan Type:</span>
                            <span class="loan-detail-value">${loan.loan_type_name || loan.loan_type || 'N/A'}</span>
                        </div>
                        ${loan.loan_type_description ? `
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Description:</span>
                            <span class="loan-detail-value"><small>${loan.loan_type_description}</small></span>
                </div>
                        ` : ''}
                        ${loan.account_code || loan.account_name ? `
                <div class="loan-detail-row">
                    <span class="loan-detail-label">Account:</span>
                            <span class="loan-detail-value">${loan.account_code || ''} ${loan.account_code && loan.account_name ? '- ' : ''}${loan.account_name || ''}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                <div class="col-md-6">
            <div class="loan-detail-section">
                        <h6 class="text-primary mb-3"><i class="fas fa-file-contract me-2"></i>Loan Terms</h6>
                <div class="loan-detail-row">
                    <span class="loan-detail-label">Loan Amount:</span>
                            <span class="loan-detail-value"><strong class="text-primary">₱${loanAmount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></span>
                </div>
                <div class="loan-detail-row">
                    <span class="loan-detail-label">Interest Rate:</span>
                            <span class="loan-detail-value">${interestRate.toFixed(2)}% per annum</span>
                </div>
                <div class="loan-detail-row">
                    <span class="loan-detail-label">Loan Term:</span>
                            <span class="loan-detail-value">${loan.loan_term || loan.term_months || 'N/A'} months</span>
                </div>
                <div class="loan-detail-row">
                    <span class="loan-detail-label">Start Date:</span>
                            <span class="loan-detail-value">${loan.start_date ? formatDate(loan.start_date) : 'N/A'}</span>
                </div>
                <div class="loan-detail-row">
                    <span class="loan-detail-label">Maturity Date:</span>
                            <span class="loan-detail-value">${loan.maturity_date ? formatDate(loan.maturity_date) : 'N/A'}</span>
                        </div>
                        ${loan.created_by_name ? `
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Created By:</span>
                            <span class="loan-detail-value">${loan.created_by_name}</span>
                        </div>
                        ` : ''}
                        ${loan.created_at ? `
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Created At:</span>
                            <span class="loan-detail-value"><small>${new Date(loan.created_at).toLocaleString('en-US')}</small></span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            
            <div class="loan-detail-section">
                <h6 class="text-primary mb-3"><i class="fas fa-chart-line me-2"></i>Payment Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Total Paid:</span>
                            <span class="loan-detail-value"><strong class="text-success">₱${(loan.total_paid || amountPaid || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Remaining Balance:</span>
                            <span class="loan-detail-value"><strong class="text-danger">₱${outstandingBalance.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Last Payment Date:</span>
                            <span class="loan-detail-value">${loan.last_payment_date ? formatDate(loan.last_payment_date) : 'No payments yet'}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Payment Status:</span>
                            <span class="loan-detail-value">
                                <span class="badge ${getPaymentStatusBadge(loan.payment_status || loan.status)}">
                                    ${loan.payment_status || (loan.status === 'paid' ? 'Fully Paid' : loan.status === 'defaulted' ? 'Overdue' : 'Active')}
                                </span>
                            </span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Loan Status:</span>
                            <span class="loan-detail-value"><span class="badge status-${(loan.status || '').toLowerCase()}">${(loan.status || 'N/A').toUpperCase()}</span></span>
                        </div>
                        <div class="loan-detail-row">
                            <span class="loan-detail-label">Payment Progress:</span>
                            <span class="loan-detail-value">${paymentPercentage}%</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add payment history section - ALWAYS show, even for paid loans
        html += `
            <div class="loan-detail-section mt-4">
                <h6 class="text-primary mb-3"><i class="fas fa-history me-2"></i>Payment History</h6>
        `;
        
        // Check if payment_schedule exists and has data
        if (loan.payment_schedule && Array.isArray(loan.payment_schedule) && loan.payment_schedule.length > 0) {
            html += `
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover payment-schedule-table">
                        <thead class="table-light">
                            <tr>
                                <th>Payment Date</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Interest</th>
                                <th class="text-end">Total Payment</th>
                                <th class="text-end">Remaining Balance</th>
                                <th class="text-center">Status</th>
                                ${loan.payment_schedule.some(p => p.payment_reference) ? '<th>Reference</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            let totalPrincipal = 0;
            let totalInterest = 0;
            let totalPayment = 0;
            
            loan.payment_schedule.forEach(payment => {
                const principal = parseFloat(payment.principal_amount || payment.principal || 0);
                const interest = parseFloat(payment.interest_amount || payment.interest || 0);
                const total = parseFloat(payment.total_amount || payment.total_payment || 0);
                const balance = parseFloat(payment.balance || 0);
                
                totalPrincipal += principal;
                totalInterest += interest;
                totalPayment += total;
                
                html += `
                    <tr>
                        <td>${payment.payment_date || payment.due_date ? formatDate(payment.payment_date || payment.due_date) : 'N/A'}</td>
                        <td class="text-end">₱${principal.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td class="text-end">₱${interest.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td class="text-end"><strong>₱${total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                        <td class="text-end">₱${balance.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td class="text-center"><span class="badge bg-success">PAID</span></td>
                        ${payment.payment_reference ? `<td><small class="text-muted">${payment.payment_reference}</small></td>` : ''}
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-end">₱${totalPrincipal.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</th>
                                <th class="text-end">₱${totalInterest.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</th>
                                <th class="text-end">₱${totalPayment.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</th>
                                <th class="text-end">-</th>
                                <th></th>
                                ${loan.payment_schedule.some(p => p.payment_reference) ? '<th></th>' : ''}
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else {
            html += `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>No Payment History Found</strong>
                    <p class="mb-0 mt-2">This loan has no recorded payments in the system yet.</p>
                </div>
            `;
        }
        
        html += `</div>`;
        
        // Add transaction history if available
        if (loan.transactions && loan.transactions.length > 0) {
            html += `
                <div class="loan-detail-section mt-4">
                    <h6 class="text-primary mb-3"><i class="fas fa-history me-2"></i>Transaction History</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                    <th>Description</th>
                                    <th>Processed By</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            loan.transactions.forEach(transaction => {
                html += `
                    <tr>
                        <td>${transaction.transaction_date ? formatDate(transaction.transaction_date) : 'N/A'}</td>
                        <td><span class="badge badge-type-${(transaction.transaction_type || 'other').toLowerCase()}">${(transaction.transaction_type || 'N/A').toUpperCase()}</span></td>
                        <td class="text-end">₱${parseFloat(transaction.amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td>${transaction.description || '-'}</td>
                        <td>${transaction.processed_by_name || 'System'}</td>
                        <td class="text-center"><span class="badge bg-${(transaction.status || '').toLowerCase() === 'completed' ? 'success' : 'warning'}">${(transaction.status || 'Pending').toUpperCase()}</span></td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
        
        modalBody.innerHTML = html;
    }
    
    /**
     * Get badge class for payment status
     */
    function getPaymentStatusBadge(status) {
        if (!status) return 'bg-secondary';
        const statusLower = status.toLowerCase();
        if (statusLower.includes('fully paid') || statusLower === 'paid') {
            return 'bg-success';
        } else if (statusLower.includes('overdue') || statusLower === 'defaulted') {
            return 'bg-danger';
        } else if (statusLower === 'active') {
            return 'bg-primary';
        }
        return 'bg-secondary';
    }

    /**
     * Format date to readable format
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    /**
     * View audit trail for current/all loans
     */
    window.viewAuditTrail = function(loanId) {
        const modal = new bootstrap.Modal(document.getElementById('auditTrailModal'));
        const modalBody = document.getElementById('auditTrailBody');
        
        // Show loading state
        modalBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading audit trail...</p></td></tr>';
        
        modal.show();
        
        // Build URL with optional loan ID filter
        let url = 'api/loan-data.php?action=get_audit_trail';
        if (loanId) {
            url += '&id=' + loanId;
        }
        
        // Fetch audit trail from API
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    displayAuditTrail(data.data);
                } else if (data.success && data.data.length === 0) {
                    modalBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                                <p>No audit trail records found.</p>
                            </td>
                        </tr>
                    `;
                } else {
                    modalBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-danger py-4">
                                <i class="fas fa-exclamation-circle fa-2x mb-3 d-block"></i>
                                <p>Error: ${data.error || 'Failed to load audit trail'}</p>
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching audit trail:', error);
                modalBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-3 d-block"></i>
                            <p>Audit trail data will be available when connected to database.</p>
                            <small>The system will track all changes made to loans including creates, updates, payments, and deletions.</small>
                        </td>
                    </tr>
                `;
            });
    };

    /**
     * Display audit trail in modal
     */
    function displayAuditTrail(logs) {
        const modalBody = document.getElementById('auditTrailBody');
        let html = '';
        
        logs.forEach(log => {
            // Format details - try to parse JSON, otherwise show plain text
            let detailsHtml = '-';
            if (log.additional_info || log.details) {
                const detailsText = log.additional_info || log.details;
                try {
                    // Try to parse as JSON
                    const parsed = JSON.parse(detailsText);
                    if (typeof parsed === 'object') {
                        // Format as readable list
                        const detailItems = Object.entries(parsed)
                            .map(([key, value]) => `<strong>${key.charAt(0).toUpperCase() + key.slice(1)}:</strong> ${value}`)
                            .join('<br>');
                        detailsHtml = `<small>${detailItems}</small>`;
                    } else {
                        detailsHtml = detailsText;
                    }
                } catch (e) {
                    // Not JSON, show as plain text
                    detailsHtml = detailsText;
                }
            }
            
            // Format action - uppercase and clean
            const action = (log.action || '').toUpperCase().replace(/_/g, ' ');
            
            // Format loan number - show loan number if available, otherwise object ID
            const loanNo = log.loan_number || (log.object_id ? `#${log.object_id}` : '-');
            
            html += `
                <tr>
                    <td>${formatDateTime(log.created_at)}</td>
                    <td>${log.full_name || log.username || 'System'}</td>
                    <td><span class="badge bg-${getActionBadgeColor(log.action)}">${action}</span></td>
                    <td>${loanNo}</td>
                    <td>${detailsHtml}</td>
                    <td><small class="text-muted">${log.ip_address || '-'}</small></td>
                </tr>
            `;
        });
        
        modalBody.innerHTML = html;
    }

    /**
     * Format datetime to readable format
     */
    function formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Get badge color for action type
     */
    function getActionBadgeColor(action) {
        if (!action) return 'secondary';
        
        const actionUpper = action.toUpperCase();
        const actionColors = {
            'CREATE': 'success',
            'CREATED': 'success',
            'UPDATE': 'info',
            'UPDATED': 'info',
            'DELETE': 'danger',
            'DELETED': 'danger',
            'RESTORE': 'warning',
            'RESTORED': 'warning',
            'PAYMENT': 'primary',
            'PAID': 'primary',
            'DISBURSEMENT': 'info',
            'DISBURSED': 'info',
            'POST': 'primary',
            'POSTED': 'primary',
            'VOID': 'dark',
            'VOIDED': 'dark',
            'REVERSE': 'warning',
            'REVERSED': 'warning'
        };
        return actionColors[actionUpper] || 'secondary';
    }

    /**
     * Export audit trail
     */
    window.exportAuditTrail = function() {
        const modalBody = document.getElementById('auditTrailBody');
        const rows = modalBody.querySelectorAll('tr');
        
        if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td[colspan]'))) {
            showNotification('No audit trail data to export', 'warning');
            return;
        }
        
        // Build CSV content
        let csvContent = '';
        
        // Header Section
        csvContent += 'EVERGREEN ACCOUNTING & FINANCE SYSTEM\n';
        csvContent += 'LOAN AUDIT TRAIL REPORT\n';
        csvContent += '\n';
        csvContent += `Report Generated: ${new Date().toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        })}\n`;
        csvContent += '\n';
        
        // Column Headers
        csvContent += 'Date & Time,User,Action,Loan No.,Details,IP Address\n';
        
        // Data Rows
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 6) {
                const dateTime = cells[0].textContent.trim().replace(/,/g, '');
                const user = cells[1].textContent.trim().replace(/,/g, '');
                const action = cells[2].textContent.trim().replace(/,/g, '');
                const loanNo = cells[3].textContent.trim().replace(/,/g, '');
                // Get plain text from details (remove HTML)
                const detailsText = cells[4].textContent.trim().replace(/,/g, ';').replace(/\n/g, ' ');
                const ipAddress = cells[5].textContent.trim().replace(/,/g, '');
                
                csvContent += `"${dateTime}","${user}","${action}","${loanNo}","${detailsText}","${ipAddress}"\n`;
            }
        });
        
        csvContent += '\n';
        csvContent += '"This report was generated by the Evergreen Accounting & Finance System"\n';
        csvContent += `"Export Date: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}"\n`;
        csvContent += '\n';
        csvContent += '"© ' + new Date().getFullYear() + ' Evergreen Accounting & Finance. All rights reserved."\n';
        
        // Create and download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        const dateStr = new Date().toISOString().split('T')[0];
        const filename = `loan_audit_trail_${dateStr}.csv`;
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        showNotification('Audit trail exported successfully!', 'success');
    };

    /**
     * Delete application (for applications)
     */
    window.deleteApplication = function(applicationId) {
        console.log('Delete application called with ID:', applicationId);
        
        if (!applicationId || applicationId === 'undefined' || applicationId === 'null') {
            showNotification('Error: Invalid application ID', 'error');
            return;
        }
        
        if (!confirm('Are you sure you want to delete this loan application? This action cannot be undone.')) {
            return;
        }

        showLoading('Deleting application...');

        fetch('api/loan-data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_application&application_id=${applicationId}`
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    let errorMessage = `HTTP error! status: ${response.status}`;
                    try {
                        const errorData = JSON.parse(text);
                        if (errorData.error) {
                            errorMessage += ` - ${errorData.error}`;
                        }
                    } catch (e) {
                        if (text.length > 0) {
                            errorMessage += ` - ${text.substring(0, 200)}`;
                        }
                    }
                    throw new Error(errorMessage);
                });
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification(data.message || 'Application deleted successfully!', 'success');
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
            showNotification('Delete failed: ' + error.message, 'error');
        });
    };

    /**
     * Delete loan (soft delete - move to bin)
     */
    window.deleteLoan = function(loanId) {
        console.log('Delete loan called with ID:', loanId);
        
        if (!loanId || loanId === 'undefined' || loanId === 'null') {
            showNotification('Error: Invalid loan ID', 'error');
            return;
        }
        
        if (!confirm('Are you sure you want to delete this loan? It will be moved to the bin station where you can restore it later.')) {
            return;
        }

        // Show loading
        showLoading('Moving loan to bin...');

        // Make AJAX call to delete loan
        const url = 'api/loan-data.php';
        const data = `action=soft_delete_loan&loan_id=${loanId}`;
        
        console.log('Making delete request to:', url);
        console.log('With data:', data);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data
        })
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error response:', text);
                    let errorMessage = `HTTP error! status: ${response.status}`;
                    try {
                        const errorData = JSON.parse(text);
                        if (errorData.error) {
                            errorMessage += ` - ${errorData.error}`;
                        }
                    } catch (e) {
                        if (text.length > 0) {
                            errorMessage += ` - ${text.substring(0, 200)}`;
                        }
                    }
                    throw new Error(errorMessage);
                });
            }
            
            return response.text().then(text => {
                console.log('Response text:', text);
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Response data:', data);
            hideLoading();
            if (data.success) {
                showNotification(data.message || 'Loan deleted successfully!', 'success');
                
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
            showNotification('Delete failed: ' + error.message, 'error');
        });
    };

    /**
     * Get current filter parameters
     */
    function getCurrentFilters() {
        const params = new URLSearchParams();
        
        const dateFrom = document.querySelector('input[name="date_from"]');
        const dateTo = document.querySelector('input[name="date_to"]');
        const transactionType = document.querySelector('select[name="transaction_type"]');
        const status = document.querySelector('select[name="status"]');
        const accountNumber = document.querySelector('input[name="account_number"]');
        
        if (dateFrom && dateFrom.value) params.append('date_from', dateFrom.value);
        if (dateTo && dateTo.value) params.append('date_to', dateTo.value);
        if (transactionType && transactionType.value) params.append('transaction_type', transactionType.value);
        if (status && status.value) params.append('status', status.value);
        if (accountNumber && accountNumber.value) params.append('account_number', accountNumber.value);
        
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

})();