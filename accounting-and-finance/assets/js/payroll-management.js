/**
 * Payroll Management Module - JavaScript
 * Handles client-side interactivity, filtering, and export functions
 */

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Payroll Management module initialized');
    
    // Initialize date inputs with current date
    initializeDateFilters();
    
    // Add event listeners for tab changes
    addTabEventListeners();
    
    // Initialize employee selector
    initializeEmployeeSelector();
    
    // Initialize attendance filters
    initializeAttendanceFilters();
});

/**
 * Initialize date filters with current date range
 */
function initializeDateFilters() {
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };
    
    // Set default date ranges
    const expenseFrom = document.getElementById('expense-date-from');
    const expenseTo = document.getElementById('expense-date-to');
    const transactionFrom = document.getElementById('transaction-date-from');
    const transactionTo = document.getElementById('transaction-date-to');
    const loanFrom = document.getElementById('loan-date-from');
    const loanTo = document.getElementById('loan-date-to');
    
    if (expenseFrom) expenseFrom.value = formatDate(firstDayOfMonth);
    if (expenseTo) expenseTo.value = formatDate(today);
    if (transactionFrom) transactionFrom.value = formatDate(firstDayOfMonth);
    if (transactionTo) transactionTo.value = formatDate(today);
    if (loanFrom) loanFrom.value = formatDate(firstDayOfMonth);
    if (loanTo) loanTo.value = formatDate(today);
}

/**
 * Add event listeners to tabs for tracking
 */
function addTabEventListeners() {
    const tabs = document.querySelectorAll('.payroll-nav-tabs .nav-link');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const tabId = event.target.getAttribute('data-bs-target');
            console.log('Switched to tab:', tabId);
        });
    });
}

/**
 * Initialize employee selector
 */
function initializeEmployeeSelector() {
    const employeeSelect = document.getElementById('employee-select');
    if (employeeSelect) {
        console.log('Employee selector initialized');
    }
    
    // Initialize filters toggle
    initializeFiltersToggle();
}

/**
 * Initialize filters toggle functionality
 */
function initializeFiltersToggle() {
    const toggleBtn = document.querySelector('.btn-toggle-filters');
    const filtersContent = document.getElementById('filters-content');
    
    if (toggleBtn && filtersContent) {
        // Check if filters are already applied (URL has parameters)
        const urlParams = new URLSearchParams(window.location.search);
        const hasFilters = urlParams.has('search') || urlParams.has('position') || 
                          urlParams.has('department') || urlParams.has('type');
        
        if (hasFilters) {
            // Show filters if they're already applied
            filtersContent.classList.add('show');
            toggleBtn.classList.add('active');
        }
        
        console.log('Filters toggle initialized');
    }
}

/**
 * Initialize attendance filters
 */
function initializeAttendanceFilters() {
    const monthFilter = document.getElementById('attendance-month-filter');
    if (monthFilter) {
        monthFilter.addEventListener('change', filterAttendanceByMonth);
        console.log('Attendance filters initialized');
    }
}

/**
 * Toggle filters visibility
 */
function toggleFilters() {
    const toggleBtn = document.querySelector('.btn-toggle-filters');
    const filtersContent = document.getElementById('filters-content');
    
    if (toggleBtn && filtersContent) {
        const isVisible = filtersContent.classList.contains('show');
        
        if (isVisible) {
            filtersContent.classList.remove('show');
            toggleBtn.classList.remove('active');
        } else {
            filtersContent.classList.add('show');
            toggleBtn.classList.add('active');
        }
    }
}

/**
 * Change employee selection
 */
function changeEmployee() {
    const employeeSelect = document.getElementById('employee-select');
    const selectedEmployee = employeeSelect.value;
    
    // Get current payroll period parameters
    const payrollMonth = document.getElementById('payroll-month-select')?.value || '';
    const payrollPeriod = document.getElementById('payroll-period-select')?.value || '';
    
    // Redirect to same page with employee and payroll period parameters
    const currentUrl = new URL(window.location);
    
    if (selectedEmployee) {
        currentUrl.searchParams.set('employee', selectedEmployee);
    } else {
        currentUrl.searchParams.delete('employee');
    }
    
    // Preserve payroll period parameters
    if (payrollMonth) {
        currentUrl.searchParams.set('payroll_month', payrollMonth);
    }
    if (payrollPeriod) {
        currentUrl.searchParams.set('payroll_period', payrollPeriod);
    }
    
    window.location.href = currentUrl.toString();
}

/**
 * Change payroll period selection
 */
function changePayrollPeriod() {
    const payrollMonth = document.getElementById('payroll-month-select')?.value || '';
    const payrollPeriod = document.getElementById('payroll-period-select')?.value || '';
    const selectedEmployee = document.getElementById('employee-select')?.value || '';
    
    // Redirect to same page with payroll period parameters
    const currentUrl = new URL(window.location);
    
    // Preserve employee selection
    if (selectedEmployee) {
        currentUrl.searchParams.set('employee', selectedEmployee);
    }
    
    // Set payroll period parameters
    if (payrollMonth) {
        currentUrl.searchParams.set('payroll_month', payrollMonth);
    } else {
        currentUrl.searchParams.delete('payroll_month');
    }
    
    if (payrollPeriod) {
        currentUrl.searchParams.set('payroll_period', payrollPeriod);
    } else {
        currentUrl.searchParams.delete('payroll_period');
    }
    
    window.location.href = currentUrl.toString();
}

/**
 * Filter expense history based on date range
 */
function filterExpenses() {
    const fromDate = document.getElementById('expense-date-from').value;
    const toDate = document.getElementById('expense-date-to').value;
    
    if (!fromDate || !toDate) {
        alert('Please select both From and To dates');
        return;
    }
    
    console.log('Filtering expenses from', fromDate, 'to', toDate);
    
    // Filter table rows
    const tableBody = document.getElementById('expense-table-body');
    const rows = tableBody.getElementsByTagName('tr');
    
    let visibleCount = 0;
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const dateCell = row.cells[0];
        
        if (dateCell && dateCell.textContent) {
            const rowDate = dateCell.textContent.trim();
            
            if (rowDate >= fromDate && rowDate <= toDate) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
    }
    
    // Show message if no results
    if (visibleCount === 0) {
        showNoResultsMessage(tableBody, 5, 'No expenses found for the selected date range');
    } else {
        removeNoResultsMessage(tableBody);
    }
}

/**
 * Filter transaction history based on date range
 */
function filterTransactions() {
    const fromDate = document.getElementById('transaction-date-from').value;
    const toDate = document.getElementById('transaction-date-to').value;
    
    if (!fromDate || !toDate) {
        alert('Please select both From and To dates');
        return;
    }
    
    console.log('Filtering transactions from', fromDate, 'to', toDate);
    
    // Filter table rows
    const tableBody = document.getElementById('transaction-table-body');
    const rows = tableBody.getElementsByTagName('tr');
    
    let visibleCount = 0;
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const dateCell = row.cells[0];
        
        if (dateCell && dateCell.textContent) {
            const rowDate = dateCell.textContent.trim();
            
            if (rowDate >= fromDate && rowDate <= toDate) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
    }
    
    // Show message if no results
    if (visibleCount === 0) {
        showNoResultsMessage(tableBody, 6, 'No transactions found for the selected date range');
    } else {
        removeNoResultsMessage(tableBody);
    }
}

/**
 * Filter loan history based on date range
 */
function filterLoans() {
    const fromDate = document.getElementById('loan-date-from').value;
    const toDate = document.getElementById('loan-date-to').value;
    
    if (!fromDate || !toDate) {
        alert('Please select both From and To dates');
        return;
    }
    
    console.log('Filtering loans from', fromDate, 'to', toDate);
    
    // Filter table rows
    const tableBody = document.getElementById('loan-table-body');
    const rows = tableBody.getElementsByTagName('tr');
    
    let visibleCount = 0;
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const dateCell = row.cells[0];
        
        if (dateCell && dateCell.textContent) {
            const rowDate = dateCell.textContent.trim();
            
            if (rowDate >= fromDate && rowDate <= toDate) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
    }
    
    // Show message if no results
    if (visibleCount === 0) {
        showNoResultsMessage(tableBody, 6, 'No loans found for the selected date range');
    } else {
        removeNoResultsMessage(tableBody);
    }
}

/**
 * Show "no results" message in table
 */
function showNoResultsMessage(tableBody, colspan, message) {
    removeNoResultsMessage(tableBody);
    
    const row = document.createElement('tr');
    row.className = 'no-results-row';
    row.innerHTML = `<td colspan="${colspan}" class="text-center text-muted py-4">${message}</td>`;
    tableBody.appendChild(row);
}

/**
 * Remove "no results" message from table
 */
function removeNoResultsMessage(tableBody) {
    const existingMessage = tableBody.querySelector('.no-results-row');
    if (existingMessage) {
        existingMessage.remove();
    }
}

/**
 * Export expenses to Excel (placeholder)
 */
function exportExpenses() {
    console.log('Exporting expenses...');
    
    // Get visible rows
    const tableBody = document.getElementById('expense-table-body');
    const rows = tableBody.getElementsByTagName('tr');
    
    let data = [];
    data.push(['Date', 'Description', 'Category', 'Amount', 'Status']);
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        if (row.style.display !== 'none' && !row.classList.contains('no-results-row')) {
            const cells = row.cells;
            if (cells.length >= 5) {
                data.push([
                    cells[0].textContent.trim(),
                    cells[1].textContent.trim(),
                    cells[2].textContent.trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim()
                ]);
            }
        }
    }
    
    if (data.length > 1) {
        // Convert to CSV
        const csv = data.map(row => row.join(',')).join('\n');
        
        // Create download link
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `expense_history_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        console.log('Exported', data.length - 1, 'expense records');
    } else {
        alert('No data to export');
    }
}

/**
 * Export transactions to Excel (placeholder)
 */
function exportTransactions() {
    console.log('Exporting transactions...');
    
    const tableBody = document.getElementById('transaction-table-body');
    const rows = tableBody.getElementsByTagName('tr');
    
    let data = [];
    data.push(['Date', 'Type', 'Account', 'Description', 'Amount', 'Status']);
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        if (row.style.display !== 'none' && !row.classList.contains('no-results-row')) {
            const cells = row.cells;
            if (cells.length >= 6) {
                data.push([
                    cells[0].textContent.trim(),
                    cells[1].textContent.trim(),
                    cells[2].textContent.trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim(),
                    cells[5].textContent.trim()
                ]);
            }
        }
    }
    
    if (data.length > 1) {
        const csv = data.map(row => row.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `transaction_history_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        console.log('Exported', data.length - 1, 'transaction records');
    } else {
        alert('No data to export');
    }
}

/**
 * Export loans to Excel (placeholder)
 */
function exportLoans() {
    console.log('Exporting loans...');
    
    const tableBody = document.getElementById('loan-table-body');
    const rows = tableBody.getElementsByTagName('tr');
    
    let data = [];
    data.push(['Date', 'Account Number', 'Type', 'Amount', 'Status', 'Description']);
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        if (row.style.display !== 'none' && !row.classList.contains('no-results-row')) {
            const cells = row.cells;
            if (cells.length >= 6) {
                data.push([
                    cells[0].textContent.trim(),
                    cells[1].textContent.trim(),
                    cells[2].textContent.trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim(),
                    cells[5].textContent.trim()
                ]);
            }
        }
    }
    
    if (data.length > 1) {
        const csv = data.map(row => row.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `loan_history_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        console.log('Exported', data.length - 1, 'loan records');
    } else {
        alert('No data to export');
    }
}

/**
 * Format currency for display
 */
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const year = date.getFullYear();
    return `${month}/${day}/${year}`;
}

/**
 * Print payslip
 */
function printPayslip() {
    // Add a class to body to indicate printing
    document.body.classList.add('printing');
    
    // Print the page
    window.print();
    
    // Remove the printing class after a short delay
    setTimeout(() => {
        document.body.classList.remove('printing');
    }, 1000);
}

/**
 * Filter attendance by month
 */
function filterAttendanceByMonth() {
    const monthFilter = document.getElementById('attendance-month-filter');
    if (monthFilter) {
        const selectedMonth = monthFilter.value;
        console.log('Filtering attendance for month:', selectedMonth);
        
        // Reload page with month parameter
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('attendance_month', selectedMonth);
        window.location.href = currentUrl.toString();
    }
}

/**
 * View expense details
 */
function viewExpense(expenseId) {
    console.log('Viewing expense:', expenseId);
    // TODO: Implement expense detail modal
    alert('View expense details for ID: ' + expenseId);
}

/**
 * Edit expense
 */
function editExpense(expenseId) {
    console.log('Editing expense:', expenseId);
    // TODO: Implement expense edit modal
    alert('Edit expense for ID: ' + expenseId);
}

/**
 * View transaction details
 */
function viewTransaction(transactionId) {
    console.log('Viewing transaction:', transactionId);
    // TODO: Implement transaction detail modal
    alert('View transaction details for ID: ' + transactionId);
}

/**
 * View loan details
 */
function viewLoan(loanId) {
    console.log('Viewing loan:', loanId);
    // TODO: Implement loan detail modal
    alert('View loan details for ID: ' + loanId);
}

// Export functions for global access
window.filterExpenses = filterExpenses;
window.filterTransactions = filterTransactions;
window.filterLoans = filterLoans;
window.exportExpenses = exportExpenses;
window.exportTransactions = exportTransactions;
window.exportLoans = exportLoans;
window.printPayslip = printPayslip;
window.changeEmployee = changeEmployee;
window.changePayrollPeriod = changePayrollPeriod;
window.toggleFilters = toggleFilters;
window.filterAttendanceByMonth = filterAttendanceByMonth;
window.viewExpense = viewExpense;
window.editExpense = editExpense;
window.viewTransaction = viewTransaction;
window.viewLoan = viewLoan;

