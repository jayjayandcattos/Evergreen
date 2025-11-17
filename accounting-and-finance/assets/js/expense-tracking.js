// ========================================
// EXPENSE TRACKING MODULE JAVASCRIPT
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    initializeExpenseTracking();
});

function initializeExpenseTracking() {
    // Initialize filter toggle
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.style.display = 'none';
    }
    
    // Add smooth scrolling for better UX
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Initialize tooltips for action buttons
    initializeTooltips();
    
    // Add loading states for buttons
    initializeLoadingStates();
}

// Toggle filter form visibility
function toggleFilters() {
    const filterForm = document.getElementById('filterForm');
    const toggleBtn = document.querySelector('.btn-toggle-filters i');
    
    if (filterForm.style.display === 'none' || filterForm.style.display === '') {
        filterForm.style.display = 'block';
        filterForm.classList.add('show');
        toggleBtn.classList.remove('fa-chevron-down');
        toggleBtn.classList.add('fa-chevron-up');
    } else {
        filterForm.style.display = 'none';
        filterForm.classList.remove('show');
        toggleBtn.classList.remove('fa-chevron-up');
        toggleBtn.classList.add('fa-chevron-down');
    }
}

// View expense details
function viewExpense(expenseId) {
    showLoading('Loading expense details...');
    
    fetch(`../modules/api/expense-data.php?action=get_expense_details&expense_id=${expenseId}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                displayExpenseModal(data.data);
            } else {
                showNotification(data.error || 'Failed to load expense details', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('Failed to load expense details', 'error');
        });
}

// Display expense details in modal
function displayExpenseModal(expense) {
    const modalBody = document.getElementById('expenseModalBody');
    
    modalBody.innerHTML = `
        <div class="expense-details">
            <div class="detail-grid">
                <div class="detail-group">
                    <label>Transaction Number:</label>
                    <span class="detail-value">${expense.claim_no}</span>
                </div>
                <div class="detail-group">
                    <label>Employee:</label>
                    <span class="detail-value">${expense.employee_name}</span>
                </div>
                <div class="detail-group">
                    <label>Expense Date:</label>
                    <span class="detail-value">${formatDate(expense.expense_date)}</span>
                </div>
                <div class="detail-group">
                    <label>Amount:</label>
                    <span class="detail-value amount">₱${formatCurrency(expense.amount)}</span>
                </div>
                <div class="detail-group">
                    <label>Category:</label>
                    <span class="detail-value">${expense.category}</span>
                </div>
                <div class="detail-group">
                    <label>Account:</label>
                    <span class="detail-value">${expense.account_code} - ${expense.account_name}</span>
                </div>
                <div class="detail-group">
                    <label>Status:</label>
                    <span class="status-badge status-${expense.status}">${capitalizeFirst(expense.status)}</span>
                </div>
                <div class="detail-group">
                    <label>Description:</label>
                    <span class="detail-value">${expense.description}</span>
                </div>
                <div class="detail-group">
                    <label>Created By:</label>
                    <span class="detail-value">${expense.created_by}</span>
                </div>
                <div class="detail-group">
                    <label>Created At:</label>
                    <span class="detail-value">${formatDateTime(expense.created_at)}</span>
                </div>
                ${expense.approved_by ? `
                <div class="detail-group">
                    <label>Approved By:</label>
                    <span class="detail-value">${expense.approved_by}</span>
                </div>
                <div class="detail-group">
                    <label>Approved At:</label>
                    <span class="detail-value">${formatDateTime(expense.approved_at)}</span>
                </div>
                ` : ''}
            </div>
        </div>
    `;
    
    showModal('expenseModal');
}

// View audit trail
function viewAuditTrail(expenseId) {
    showLoading('Loading audit trail...');
    
    fetch(`../modules/api/expense-data.php?action=get_audit_trail&expense_id=${expenseId}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                displayAuditModal(data.data);
            } else {
                showNotification(data.error || 'Failed to load audit trail', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('Failed to load audit trail', 'error');
        });
}

// Display audit trail in modal
function displayAuditModal(auditData) {
    const modalBody = document.getElementById('auditModalBody');
    
    let auditHtml = '<div class="audit-trail">';
    auditHtml += '<div class="audit-header"><h4>Audit Trail History</h4></div>';
    auditHtml += '<div class="audit-timeline">';
    
    auditData.forEach((entry, index) => {
        auditHtml += `
            <div class="audit-entry ${index === 0 ? 'latest' : ''}">
                <div class="audit-icon">
                    <i class="fas fa-${getAuditIcon(entry.action)}"></i>
                </div>
                <div class="audit-content">
                    <div class="audit-action">${entry.action}</div>
                    <div class="audit-details">
                        <span class="audit-user">${entry.user}</span>
                        <span class="audit-time">${formatDateTime(entry.timestamp)}</span>
                    </div>
                    <div class="audit-changes">${entry.changes}</div>
                    <div class="audit-meta">
                        <small>IP: ${entry.ip_address}</small>
                    </div>
                </div>
            </div>
        `;
    });
    
    auditHtml += '</div></div>';
    modalBody.innerHTML = auditHtml;
    
    showModal('auditModal');
}

// Show general audit trail
function showAuditTrail() {
    showLoading('Loading audit trail...');
    
    fetch(`../modules/api/expense-data.php?action=get_audit_trail&general=true`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                displayAuditModal(data.data);
            } else {
                showNotification(data.error || 'Failed to load audit trail', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('Failed to load audit trail', 'error');
        });
}

// Export to Excel
function exportToExcel() {
    showNotification('Exporting expenses to Excel...', 'info');
    
    // Get current table data
    const table = document.getElementById('expenseTable');
    if (!table) {
        showNotification('No data to export', 'warning');
        return;
    }
    
    const rows = table.querySelectorAll('tbody tr');
    if (rows.length === 0) {
        showNotification('No expense data to export', 'warning');
        return;
    }
    
    // Group expenses by category
    const expensesByCategory = {};
    const categoryTotals = {};
    let grandTotal = 0;
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 8) {
            // Map actual table columns: 0=Transaction#, 1=Date, 2=Employee, 3=Category, 4=Account, 5=Amount, 6=Status, 7=Description, 8=Actions
            const transactionNo = cells[0].textContent.trim();
            const date = cells[1].textContent.trim();
            const employee = cells[2].textContent.trim();
            const category = cells[3].textContent.trim();
            const accountInfo = cells[4].textContent.trim();
            const amountText = cells[5].textContent.trim();
            const amount = parseFloat(amountText.replace(/[₱,]/g, '')) || 0;
            const status = cells[6].textContent.trim();
            const description = cells[7].textContent.trim();
            
            if (!expensesByCategory[category]) {
                expensesByCategory[category] = [];
                categoryTotals[category] = 0;
            }
            
            expensesByCategory[category].push({
                transactionNo,
                date,
                employee,
                amount,
                status,
                description,
                accountInfo
            });
            
            categoryTotals[category] += amount;
            grandTotal += amount;
        }
    });
    
    // Create beautifully formatted CSV content for Excel
    let csvContent = "EVERGREEN ACCOUNTING & FINANCE SYSTEM\n";
    csvContent += "EXPENSE TRACKING REPORT\n";
    csvContent += "\n";
    csvContent += `Report Generated: ${new Date().toLocaleString()}\n`;
    csvContent += "\n";
    csvContent += "\n";
    
    // Summary section with better formatting
    csvContent += "EXECUTIVE SUMMARY\n";
    csvContent += "\n";
    csvContent += "Metric,Value\n";
    csvContent += `Total Expenses,${rows.length}\n`;
    csvContent += `Grand Total (PHP),${grandTotal.toFixed(2)}\n`;
    csvContent += `Number of Categories,${Object.keys(categoryTotals).length}\n`;
    csvContent += `Average per Expense (PHP),${rows.length > 0 ? (grandTotal / rows.length).toFixed(2) : '0.00'}\n`;
    csvContent += "\n";
    csvContent += "\n";
    csvContent += "\n";
    
    // Summary by Category with better formatting
    csvContent += "SUMMARY BY CATEGORY\n";
    csvContent += "\n";
    csvContent += "Category,Total Amount (PHP),Percentage (%),Number of Expenses\n";
    
    Object.keys(categoryTotals).sort().forEach(category => {
        const total = categoryTotals[category];
        const percentage = grandTotal > 0 ? ((total / grandTotal) * 100).toFixed(2) : '0.00';
        const expenseCount = expensesByCategory[category].length;
        csvContent += `"${category}",${total.toFixed(2)},${percentage}%,${expenseCount}\n`;
    });
    
    csvContent += "\n";
    csvContent += "\n";
    csvContent += "\n";
    
    // Detailed expenses grouped by category with better formatting
    Object.keys(expensesByCategory).sort().forEach((category, catIndex) => {
        if (catIndex > 0) {
            csvContent += "\n";
            csvContent += "\n";
        }
        
        csvContent += `"CATEGORY: ${category.toUpperCase()}"\n`;
        csvContent += "\n";
        csvContent += "Transaction #,Date,Employee,Description,Account Code & Name,Amount (PHP),Status\n";
        
        expensesByCategory[category].forEach((expense, index) => {
            csvContent += `"${expense.transactionNo}","${expense.date}","${expense.employee}","${expense.description.replace(/"/g, '""')}","${expense.accountInfo.replace(/"/g, '""')}",${expense.amount.toFixed(2)},"${expense.status}"\n`;
        });
        
        csvContent += "\n";
        csvContent += `"TOTAL FOR ${category.toUpperCase()}","","","","",${categoryTotals[category].toFixed(2)},""\n`;
    });
    
    csvContent += "\n";
    csvContent += "\n";
    csvContent += "\n";
    
    // Footer section
    csvContent += "REPORT INFORMATION\n";
    csvContent += "\n";
    csvContent += `"This report was generated by the Evergreen Accounting & Finance System"\n`;
    csvContent += `"Report Period: All Available Data"\n`;
    csvContent += `"Total Expenses: ${rows.length} expense(s)"\n`;
    csvContent += `"Grand Total Amount: PHP ${grandTotal.toFixed(2)}"\n`;
    csvContent += `"Report Date: ${new Date().toLocaleDateString()}"\n`;
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `expense_report_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showNotification('Expense report exported successfully!', 'success');
}

// Print report
function printReport() {
    showNotification('Preparing to print expense report...', 'info');
    
    // Get current table data
    const table = document.getElementById('expenseTable');
    if (!table) {
        showNotification('No data to print', 'warning');
        return;
    }
    
    const rows = table.querySelectorAll('tbody tr');
    if (rows.length === 0) {
        showNotification('No expense data to print', 'warning');
        return;
    }
    
    // Group expenses by category
    const expensesByCategory = {};
    const categoryTotals = {};
    let grandTotal = 0;
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 8) {
            // Map actual table columns: 0=Transaction#, 1=Date, 2=Employee, 3=Category, 4=Account, 5=Amount, 6=Status, 7=Description, 8=Actions
            const transactionNo = cells[0].textContent.trim();
            const date = cells[1].textContent.trim();
            const employee = cells[2].textContent.trim();
            const category = cells[3].textContent.trim();
            const accountInfo = cells[4].textContent.trim();
            const amountText = cells[5].textContent.trim();
            const amount = parseFloat(amountText.replace(/[₱,]/g, '')) || 0;
            const status = cells[6].textContent.trim();
            const description = cells[7].textContent.trim();
            
            if (!expensesByCategory[category]) {
                expensesByCategory[category] = [];
                categoryTotals[category] = 0;
            }
            
            expensesByCategory[category].push({
                transactionNo,
                date,
                employee,
                amount,
                status,
                description,
                accountInfo
            });
            
            categoryTotals[category] += amount;
            grandTotal += amount;
        }
    });
    
    // Create print window
    const printWindow = window.open('', '_blank');
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Expense Tracking Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .company-name { font-size: 24px; font-weight: bold; color: #2c3e50; }
                .report-title { font-size: 18px; color: #7f8c8d; margin-top: 10px; }
                .report-info { font-size: 12px; color: #95a5a6; margin-top: 5px; }
                .summary { background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .summary h3 { margin-top: 0; color: #2c3e50; }
                .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
                .summary-item { text-align: center; }
                .summary-value { font-size: 18px; font-weight: bold; color: #28a745; }
                .summary-label { font-size: 12px; color: #6c757d; }
                .category-section { margin: 30px 0; page-break-inside: avoid; }
                .category-header { background-color: #e9ecef; padding: 10px; font-weight: bold; color: #495057; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .text-right { text-align: right; }
                .amount { font-weight: bold; }
                .status-approved { color: #28a745; font-weight: bold; }
                .status-pending { color: #ffc107; font-weight: bold; }
                .status-rejected { color: #dc3545; font-weight: bold; }
                .status-submitted { color: #17a2b8; font-weight: bold; }
                .status-draft { color: #6c757d; font-weight: bold; }
                .category-total { background-color: #e9ecef; font-weight: bold; }
                .footer { margin-top: 30px; font-size: 12px; color: #95a5a6; text-align: center; }
                @media print {
                    body { margin: 0; padding: 10px; }
                    .no-print { display: none; }
                    .category-section { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-name">EVERGREEN</div>
                <div class="report-title">Expense Tracking Report</div>
                <div class="report-info">Generated: ${new Date().toLocaleString()}</div>
            </div>
            
            <div class="summary">
                <h3>Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value">${rows.length}</div>
                        <div class="summary-label">Total Expenses</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">₱${grandTotal.toFixed(2)}</div>
                        <div class="summary-label">Grand Total</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">${Object.keys(categoryTotals).length}</div>
                        <div class="summary-label">Categories</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">₱${rows.length > 0 ? (grandTotal / rows.length).toFixed(2) : '0.00'}</div>
                        <div class="summary-label">Average per Expense</div>
                    </div>
                </div>
            </div>
            
            <div class="summary">
                <h3>Summary by Category</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-right">Total Amount</th>
                            <th class="text-right">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${Object.keys(categoryTotals).sort().map(category => {
                            const total = categoryTotals[category];
                            const percentage = grandTotal > 0 ? ((total / grandTotal) * 100).toFixed(2) : '0.00';
                            return `
                                <tr>
                                    <td>${category}</td>
                                    <td class="text-right amount">₱${total.toFixed(2)}</td>
                                    <td class="text-right">${percentage}%</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            
            ${Object.keys(expensesByCategory).sort().map(category => `
                <div class="category-section">
                    <div class="category-header">CATEGORY: ${category.toUpperCase()}</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction #</th>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Description</th>
                                <th>Account</th>
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${expensesByCategory[category].map(expense => {
                                const statusClass = expense.status.toLowerCase().includes('approved') ? 'status-approved' : 
                                                  expense.status.toLowerCase().includes('pending') ? 'status-pending' : 
                                                  expense.status.toLowerCase().includes('submitted') ? 'status-submitted' :
                                                  expense.status.toLowerCase().includes('rejected') ? 'status-rejected' : 
                                                  expense.status.toLowerCase().includes('draft') ? 'status-draft' : '';
                                
                                return `
                                    <tr>
                                        <td>${expense.transactionNo}</td>
                                        <td>${expense.date}</td>
                                        <td>${expense.employee}</td>
                                        <td>${expense.description}</td>
                                        <td>${expense.accountInfo}</td>
                                        <td class="text-right amount">₱${expense.amount.toFixed(2)}</td>
                                        <td class="${statusClass}">${expense.status}</td>
                                    </tr>
                                `;
                            }).join('')}
                            <tr class="category-total">
                                <td colspan="5"><strong>TOTAL FOR ${category.toUpperCase()}</strong></td>
                                <td class="text-right amount"><strong>₱${categoryTotals[category].toFixed(2)}</strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `).join('')}
            
            <div class="footer">
                <p>This report was generated by the Evergreen Accounting & Finance System</p>
                <p>Total Expenses: ${rows.length} | Grand Total: ₱${grandTotal.toFixed(2)}</p>
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Wait a bit for content to load, then print
    setTimeout(() => {
        printWindow.focus();
        printWindow.print();
        // Don't close immediately - let user see print preview
    }, 250);
    
    showNotification('Expense report prepared for printing!', 'success');
}

// Utility functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.classList.remove('show');
        modal.style.display = 'none';
    });
    document.body.style.overflow = '';
}

function showLoading(message = 'Loading...') {
    // Create loading overlay
    const loadingOverlay = document.createElement('div');
    loadingOverlay.id = 'loadingOverlay';
    loadingOverlay.innerHTML = `
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">${message}</div>
        </div>
    `;
    loadingOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    document.body.appendChild(loadingOverlay);
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.title;
    tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
        z-index: 1000;
        pointer-events: none;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

function initializeLoadingStates() {
    const buttons = document.querySelectorAll('button');
    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('btn-primary') || this.classList.contains('btn-secondary')) {
                this.style.opacity = '0.7';
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 1000);
            }
        });
    });
}

// Helper functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatCurrency(amount) {
    return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDateForFilename(date) {
    return date.toISOString().split('T')[0];
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getAuditIcon(action) {
    const icons = {
        'Created': 'plus',
        'Updated': 'edit',
        'Approved': 'check',
        'Rejected': 'times',
        'Deleted': 'trash',
        'Login': 'sign-in-alt',
        'Logout': 'sign-out-alt',
        'Filter Applied': 'filter',
        'Export': 'download',
        'Print': 'print'
    };
    return icons[action] || 'info';
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function getNotificationColor(type) {
    const colors = {
        'success': '#28A745',
        'error': '#DC3545',
        'warning': '#FFC107',
        'info': '#17A2B8'
    };
    return colors[type] || '#17A2B8';
}

function tableToCSV(table) {
    const rows = Array.from(table.querySelectorAll('tr'));
    return rows.map(row => {
        const cells = Array.from(row.querySelectorAll('th, td'));
        return cells.map(cell => {
            // Remove HTML tags and clean text
            let text = cell.textContent.trim();
            // Escape commas and quotes
            text = text.replace(/"/g, '""');
            return `"${text}"`;
        }).join(',');
    }).join('\n');
}

function downloadCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .loading-content {
        text-align: center;
        color: white;
    }
    
    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top: 4px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .loading-text {
        font-size: 1.1rem;
        font-weight: 500;
    }
    
    .expense-details {
        padding: 1rem 0;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    
    .detail-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .detail-group label {
        font-weight: 600;
        color: #0A3D3D;
        font-size: 0.9rem;
    }
    
    .detail-value {
        color: #495057;
        font-size: 1rem;
    }
    
    .audit-trail {
        padding: 1rem 0;
    }
    
    .audit-header h4 {
        color: #0A3D3D;
        margin-bottom: 1.5rem;
        font-size: 1.2rem;
    }
    
    .audit-timeline {
        position: relative;
        padding-left: 2rem;
    }
    
    .audit-timeline::before {
        content: '';
        position: absolute;
        left: 1rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #E8E8E8;
    }
    
    .audit-entry {
        position: relative;
        margin-bottom: 2rem;
        padding-left: 2rem;
    }
    
    .audit-entry.latest .audit-icon {
        background: #C17817;
        color: white;
    }
    
    .audit-icon {
        position: absolute;
        left: -1.5rem;
        top: 0.5rem;
        width: 2rem;
        height: 2rem;
        background: #E8E8E8;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        color: #6C757D;
    }
    
    .audit-content {
        background: #F8F9FA;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid #C17817;
    }
    
    .audit-action {
        font-weight: 600;
        color: #0A3D3D;
        margin-bottom: 0.5rem;
    }
    
    .audit-details {
        display: flex;
        gap: 1rem;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        color: #6C757D;
    }
    
    .audit-changes {
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .audit-meta {
        font-size: 0.8rem;
        color: #6C757D;
    }
`;
document.head.appendChild(style);
