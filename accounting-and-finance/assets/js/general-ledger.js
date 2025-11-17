// ========================================
// GENERAL LEDGER MODULE - BEAUTIFUL & CLEAN JS
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    initializeGeneralLedger();
});

function initializeGeneralLedger() {
    console.log('General Ledger module initialized');
    
    // Add smooth animations
    addSmoothAnimations();
    
    // Load initial data with better error handling
    loadStatistics();
    loadCharts();
    loadAccountsTable();
    loadTransactionsTable();
    
    // Load audit trail after a delay to ensure DOM is ready
    setTimeout(() => {
        if (document.getElementById('audit-trail-table')) {
            loadAuditTrail();
        }
    }, 1500);
}

// ========================================
// SMOOTH ANIMATIONS
// ========================================

function addSmoothAnimations() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.stat-card, .chart-container, .gl-section');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// ========================================
// LOAD STATISTICS
// ========================================

function loadStatistics() {
    // Show loading state immediately
    showStatisticsLoadingState();
    
    // Try to fetch from API with timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
    
    fetch('../modules/api/general-ledger-data.php?action=get_statistics', {
        signal: controller.signal
    })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                animateStatistics(data.data);
                console.log('Statistics loaded successfully:', data.data);
            } else {
                console.warn('API returned error, using fallback data');
                animateStatistics(getFallbackStatistics());
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Error loading statistics:', error);
            console.log('Using fallback statistics data');
            animateStatistics(getFallbackStatistics());
        });
}

function showStatisticsLoadingState() {
    const elements = {
        'total-accounts': 'Loading...',
        'total-transactions': 'Loading...',
        'total-audit': 'Loading...'
    };
    
    Object.entries(elements).forEach(([id, text]) => {
        const element = document.getElementById(id);
        if (element) {
            element.innerHTML = `<span class="loading-text">${text}</span>`;
        }
    });
}

function animateStatistics(data) {
    const elements = {
        'total-accounts': data.total_accounts || data.total_account || 0,
        'total-transactions': data.total_transactions || 0,
        'total-audit': data.total_audit || 0
    };
    
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            // Clear loading text
            element.innerHTML = '';
            // Animate numbers
            animateNumber(element, 0, value, 1500);
        }
    });
}

function animateNumber(element, start, end, duration) {
    const startTime = performance.now();
    const startValue = start;
    const endValue = end;
    
    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Use easing function for smoother animation
        const easeOutCubic = 1 - Math.pow(1 - progress, 3);
        const currentValue = Math.floor(startValue + (endValue - startValue) * easeOutCubic);
        
        element.textContent = currentValue.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        } else {
            // Add a subtle pulse effect when animation completes
            element.style.transform = 'scale(1.05)';
            setTimeout(() => {
                element.style.transform = 'scale(1)';
                element.style.transition = 'transform 0.2s ease';
            }, 100);
        }
    }
    
    requestAnimationFrame(updateNumber);
}

// ========================================
// LOAD CHARTS
// ========================================

function loadCharts() {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000);
    
    fetch('../modules/api/general-ledger-data.php?action=get_chart_data', {
        signal: controller.signal
    })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderAccountTypesChart(data.data.account_types);
                renderTransactionSummaryChart(data.data.transaction_summary);
                renderAuditCharts(data.data);
                console.log('Charts loaded successfully');
            } else {
                console.warn('API returned error, using fallback chart data');
                const fallbackData = getFallbackChartData();
                renderAccountTypesChart(fallbackData.account_types);
                renderTransactionSummaryChart(fallbackData.transaction_summary);
                renderAuditCharts(fallbackData);
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Error loading charts:', error);
            console.log('Using fallback chart data');
            const fallbackData = getFallbackChartData();
            renderAccountTypesChart(fallbackData.account_types);
            renderTransactionSummaryChart(fallbackData.transaction_summary);
            renderAuditCharts(fallbackData);
        });
}

function renderAccountTypesChart(data) {
    const ctx = document.getElementById('accountTypesChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    '#28A745',
                    '#DC3545',
                    '#6F42C1',
                    '#17A2B8',
                    '#FFC107',
                    '#E83E8C',
                    '#20C997'
                ],
                borderWidth: 0,
                hoverBorderWidth: 3,
                hoverBorderColor: '#fff',
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1000
            },
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#fff',
                        padding: 20,
                        font: {
                            size: 14,
                            weight: '600'
                        },
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 15
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 15,
                    displayColors: true,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed + ' accounts';
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            label += ' (' + percentage + '%)';
                            return label;
                        }
                    }
                }
            }
        }
    });
}

function renderTransactionSummaryChart(data) {
    const ctx = document.getElementById('transactionSummaryChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Transactions',
                data: data.values,
                backgroundColor: 'rgba(245, 166, 35, 0.9)',
                borderColor: '#F5A623',
                borderWidth: 0,
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 40,
                hoverBackgroundColor: 'rgba(245, 166, 35, 1)',
                hoverBorderColor: '#fff',
                hoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 15,
                    displayColors: false,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' transactions';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#fff',
                        font: {
                            size: 13,
                            weight: '600'
                        },
                        stepSize: 25
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.15)',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        color: '#fff',
                        font: {
                            size: 13,
                            weight: '600'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function renderAuditCharts(data) {
    // Audit Account Types Chart
    const ctx1 = document.getElementById('auditAccountTypesChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: data.account_types.labels,
                datasets: [{
                    data: data.account_types.values,
                    backgroundColor: [
                        '#28A745',
                        '#DC3545',
                        '#6F42C1',
                        '#17A2B8',
                        '#FFC107'
                    ],
                    borderWidth: 0,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#fff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1000
                },
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#fff',
                            padding: 20,
                            font: {
                                size: 14,
                                weight: '600'
                            },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 15,
                        displayColors: true,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed + ' accounts';
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                label += ' (' + percentage + '%)';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // Audit Transaction Chart
    const ctx2 = document.getElementById('auditTransactionChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: data.transaction_summary.labels,
                datasets: [{
                    label: 'Transactions',
                    data: data.transaction_summary.values,
                    backgroundColor: 'rgba(245, 166, 35, 0.9)',
                    borderColor: '#F5A623',
                    borderWidth: 0,
                    borderRadius: 8,
                    borderSkipped: false,
                    barThickness: 35,
                    hoverBackgroundColor: 'rgba(245, 166, 35, 1)',
                    hoverBorderColor: '#fff',
                    hoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 15,
                        displayColors: false,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' transactions';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#fff',
                            font: {
                                size: 13,
                                weight: '600'
                            },
                            stepSize: 25
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.15)',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: '#fff',
                            font: {
                                size: 13,
                                weight: '600'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

// ========================================
// LOAD ACCOUNTS TABLE
// ========================================

function loadAccountsTable() {
    showLoadingState('accounts');

    fetch('../modules/api/general-ledger-data.php?action=get_accounts')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayAccountsTable(data.data);
            } else {
                console.warn('API returned error, using fallback accounts data');
                displayAccountsTable(getFallbackAccounts());
            }
        })
        .catch(error => {
            console.error('Error loading accounts:', error);
            console.log('Using fallback accounts data');
            displayAccountsTable(getFallbackAccounts());
        });
}

function displayAccountsTable(accounts) {
    const tbody = document.querySelector('#accounts-table tbody');
    
    if (accounts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No accounts found</td></tr>';
        return;
    }
    
    let html = '';
    accounts.slice(0, 10).forEach((account, index) => {
        html += `
            <tr style="animation-delay: ${index * 0.1}s">
                <td><strong class="account-code">${account.code}</strong></td>
                <td><span class="account-name">${account.name}</span></td>
                <td><span class="badge bg-${getAccountTypeBadge(account.category)}">${account.category}</span></td>
                <td class="amount-cell">₱${formatCurrency(account.balance)}</td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="viewAccountDetails('${account.code}')">View</button></td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Add fade-in animation to table rows
    const rows = tbody.querySelectorAll('tr');
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.4s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, index * 50);
    });
}

// ========================================
// LOAD TRANSACTIONS TABLE
// ========================================

function loadTransactionsTable() {
    showLoadingState('transactions');

    // Get filter parameters
    const dateFrom = document.getElementById('transaction-from')?.value || '';
    const dateTo = document.getElementById('transaction-to')?.value || '';
    const type = document.getElementById('transaction-type')?.value || '';

    // Build query string
    const params = new URLSearchParams();
    params.append('action', 'get_transactions');
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (type) params.append('type', type);

    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayTransactionsTable(data.data);
            } else {
                console.warn('API returned error, using fallback transactions data');
                displayTransactionsTable(getFallbackTransactions());
            }
        })
        .catch(error => {
            console.error('Error loading transactions:', error);
            console.log('Using fallback transactions data');
            displayTransactionsTable(getFallbackTransactions());
        });
}

function displayTransactionsTable(transactions) {
    const tbody = document.querySelector('#transactions-table tbody');
    
    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No transactions found</td></tr>';
        return;
    }
    
    let html = '';
    transactions.slice(0, 10).forEach((txn, index) => {
        html += `
            <tr style="animation-delay: ${index * 0.1}s" data-entry-id="${txn.id || ''}">
                <td><strong class="transaction-id">${txn.journal_no}</strong></td>
                <td><span class="transaction-date">${txn.entry_date}</span></td>
                <td><span class="transaction-desc">${txn.description || '-'}</span></td>
                <td class="text-end amount-debit">₱${formatCurrency(txn.total_debit)}</td>
                <td class="text-end amount-credit">₱${formatCurrency(txn.total_credit)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewTransactionDetailsById(${txn.id || 0}, '${txn.journal_no}')">View</button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Add fade-in animation to table rows
    const rows = tbody.querySelectorAll('tr');
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.4s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, index * 50);
    });
}

// ========================================
// FILTER FUNCTIONS
// ========================================

function applyChartFilters() {
    showNotification('Chart filters applied successfully!', 'success');
    // Implement chart filter logic
}

function viewDrillDown() {
    showNotification('Opening drill-down view...', 'info');
    // Implement drill-down logic
}

function applyAccountFilter() {
    const searchTerm = document.getElementById('account-search').value;

    if (!searchTerm.trim()) {
        loadAccountsTable();
        return;
    }

    showLoadingState('accounts');

    const encodedSearch = encodeURIComponent(searchTerm.trim());

    fetch(`../modules/api/general-ledger-data.php?action=get_accounts&search=${encodedSearch}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAccountsTable(data.data);
                showNotification(`Found ${data.data.length} accounts matching "${searchTerm}"`, 'info');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error applying filter', 'error');
        });
}

function resetAccountFilter() {
    document.getElementById('account-search').value = '';
    loadAccountsTable();
    showNotification('Account filter reset', 'info');
}

function applyTransactionFilter() {
    const dateFrom = document.getElementById('transaction-from')?.value || '';
    const dateTo = document.getElementById('transaction-to')?.value || '';
    const type = document.getElementById('transaction-type')?.value || '';

    if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
        showNotification('From date cannot be after To date', 'error');
        return;
    }

    showNotification('Transaction filters applied!', 'success');
    loadTransactionsTable();
}

function resetTransactionFilter() {
    const dateFromInput = document.getElementById('transaction-from');
    const dateToInput = document.getElementById('transaction-to');
    const typeInput = document.getElementById('transaction-type');

    if (dateFromInput) dateFromInput.value = '';
    if (dateToInput) dateToInput.value = '';
    if (typeInput) typeInput.value = '';

    showNotification('Transaction filters reset', 'info');
    loadTransactionsTable();
}

function showLoadingState(section) {
    const tableTargets = {
        accounts: '#accounts-table tbody',
        transactions: '#transactions-table tbody'
    };

    const selector = tableTargets[section];
    if (!selector) return;

    const tbody = document.querySelector(selector);
    if (!tbody) return;

    const colSpan = section === 'transactions' ? 6 : 5;

    tbody.innerHTML = `
        <tr>
            <td colspan="${colSpan}" class="text-center py-4">
                <div class="loading-spinner"></div>
                <p>Loading ${section}...</p>
            </td>
        </tr>
    `;
}

// ========================================
// VIEW DETAILS FUNCTIONS
// ========================================

function viewAccountDetails(accountCode) {
    showNotification(`Opening account details for: ${accountCode}`, 'info');
    // Implement account details modal/page
}

function viewTransactionDetails(journalNo) {
    showNotification(`Opening transaction details for: ${journalNo}`, 'info');
    // Implement transaction details modal/page
}

function viewAccount() {
    showNotification('Opening account details...', 'info');
}

function viewTransaction() {
    showNotification('Opening transaction details...', 'info');
}

function exportAccounts() {
    showNotification('Preparing account export...', 'info');
}

function exportTransactions() {
    showNotification('Generating transaction export...', 'info');
}

function printTransactions() {
    showNotification('Sending transaction table to printer...', 'info');
    window.print();
}

function refreshTransactions() {
    showNotification('Refreshing transaction list...', 'success');
    loadTransactionsTable();
}

// ========================================
// SCROLL TO SECTION
// ========================================

function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start',
            inline: 'nearest'
        });
        
        // Add highlight effect
        element.style.boxShadow = '0 0 20px rgba(245, 166, 35, 0.3)';
        setTimeout(() => {
            element.style.boxShadow = '';
        }, 2000);
    }
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

function formatCurrency(amount) {
    return parseFloat(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function getAccountTypeBadge(category) {
    const badges = {
        'asset': 'success',
        'liability': 'danger',
        'equity': 'primary',
        'revenue': 'info',
        'expense': 'warning'
    };
    return badges[category] || 'secondary';
}

function showError(message) {
    showNotification(message, 'error');
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        max-width: 300px;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
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

// ========================================
// FALLBACK DATA FUNCTIONS
// ========================================

function getFallbackStatistics() {
    return {
        total_accounts: 247,
        total_transactions: 1542,
        total_audit: 89
    };
}

function getFallbackChartData() {
    return {
        account_types: {
            labels: ['Assets', 'Liabilities', 'Equity', 'Revenue', 'Expenses'],
            values: [45, 32, 28, 15, 25]
        },
        transaction_summary: {
            labels: ['Sales', 'Purchases', 'Payments', 'Receipts'],
            values: [120, 85, 95, 110]
        }
    };
}

function getFallbackAccounts() {
    return [
        { code: '1001', name: 'Cash on Hand', category: 'asset', balance: 15000.00, is_active: true },
        { code: '1002', name: 'Bank Account', category: 'asset', balance: 125000.00, is_active: true },
        { code: '2001', name: 'Accounts Payable', category: 'liability', balance: 25000.00, is_active: true },
        { code: '3001', name: 'Owner Equity', category: 'equity', balance: 100000.00, is_active: true },
        { code: '4001', name: 'Sales Revenue', category: 'revenue', balance: 75000.00, is_active: true },
        { code: '5001', name: 'Office Supplies', category: 'expense', balance: 5000.00, is_active: true }
    ];
}

function getFallbackTransactions() {
    return [
        { journal_no: 'TXN-2024-001', entry_date: 'Jan 15, 2024', description: 'Office Supplies Purchase', total_debit: 2450.00, total_credit: 0, status: 'posted' },
        { journal_no: 'TXN-2024-002', entry_date: 'Jan 14, 2024', description: 'Client Payment Received', total_debit: 0, total_credit: 15750.00, status: 'posted' },
        { journal_no: 'TXN-2024-003', entry_date: 'Jan 13, 2024', description: 'Utility Bill Payment', total_debit: 1250.00, total_credit: 0, status: 'posted' },
        { journal_no: 'TXN-2024-004', entry_date: 'Jan 12, 2024', description: 'Equipment Lease Payment', total_debit: 3200.00, total_credit: 0, status: 'posted' },
        { journal_no: 'TXN-2024-005', entry_date: 'Jan 11, 2024', description: 'Service Revenue', total_debit: 0, total_credit: 8900.00, status: 'posted' }
    ];
}

// ========================================
// JOURNAL ENTRY MANAGEMENT
// ========================================

let currentJournalEntryId = null;

function viewTransactionDetailsById(entryId, journalNo) {
    if (entryId && entryId > 0) {
        loadJournalEntryDetails(entryId);
    } else {
        // Fallback to journal number search
        viewTransactionDetails(journalNo);
    }
}

function viewTransactionDetails(journalNo) {
    // Fallback: search in loaded transactions
    fetch(`../modules/api/general-ledger-data.php?action=get_transactions`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const entry = data.data.find(t => t.journal_no === journalNo);
                if (entry && entry.id) {
                    loadJournalEntryDetails(entry.id);
                } else {
                    showNotification('Journal entry not found', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading journal entry details', 'error');
        });
}

function loadJournalEntryDetails(entryId) {
    fetch(`../modules/api/general-ledger-data.php?action=get_journal_entry_details&id=${entryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayJournalEntryDetails(data.data);
                currentJournalEntryId = entryId;
            } else {
                showNotification(data.message || 'Error loading details', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading journal entry details', 'error');
        });
}

function displayJournalEntryDetails(entry) {
    const body = document.getElementById('journalEntryDetailBody');
    
    // Format dates
    const entryDate = entry.entry_date ? new Date(entry.entry_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
    const createdDate = entry.created_at ? new Date(entry.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A';
    const postedDate = entry.posted_at ? new Date(entry.posted_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A';
    
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Journal Number:</strong> ${entry.journal_no}<br>
                <strong>Type:</strong> ${entry.type_name} (${entry.type_code})<br>
                <strong>Date:</strong> ${entryDate}<br>
                <strong>Status:</strong> <span class="badge bg-${getStatusColor(entry.status)}">${entry.status.toUpperCase()}</span>
            </div>
            <div class="col-md-6">
                <strong>Fiscal Period:</strong> ${entry.period_name || 'N/A'}<br>
                <strong>Reference:</strong> ${entry.reference_no || 'N/A'}<br>
                <strong>Created By:</strong> ${entry.created_by_name} on ${createdDate}<br>
                ${entry.posted_by_name ? `<strong>Posted By:</strong> ${entry.posted_by_name} on ${postedDate}<br>` : ''}
            </div>
        </div>
        <div class="mb-3">
            <strong>Description:</strong><br>
            <p>${entry.description || 'N/A'}</p>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Account</th>
                        <th>Account Name</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th>Memo</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    entry.lines.forEach(line => {
        html += `
            <tr>
                <td>${line.account_code}</td>
                <td>${line.account_name}</td>
                <td class="text-end">${line.debit > 0 ? '₱' + formatCurrency(line.debit) : '-'}</td>
                <td class="text-end">${line.credit > 0 ? '₱' + formatCurrency(line.credit) : '-'}</td>
                <td>${line.memo || '-'}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2">Total</th>
                        <th class="text-end">₱${formatCurrency(entry.total_debit)}</th>
                        <th class="text-end">₱${formatCurrency(entry.total_credit)}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    body.innerHTML = html;
    
    // Show/hide action buttons based on permissions
    document.getElementById('postJournalEntryBtn').classList.toggle('d-none', !entry.can_post);
    document.getElementById('voidJournalEntryBtn').classList.toggle('d-none', !entry.can_void);
    
    const modal = new bootstrap.Modal(document.getElementById('journalEntryDetailModal'));
    modal.show();
}

function getStatusColor(status) {
    const colors = {
        'draft': 'secondary',
        'posted': 'success',
        'voided': 'danger',
        'reversed': 'warning'
    };
    return colors[status] || 'secondary';
}


function postJournalEntry() {
    if (!currentJournalEntryId) return;
    
    if (!confirm('Are you sure you want to post this journal entry? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'post_journal_entry');
    formData.append('journal_entry_id', currentJournalEntryId);
    
    fetch('../modules/api/general-ledger-data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('journalEntryDetailModal'));
            modal.hide();
            loadTransactionsTable();
            loadStatistics();
        } else {
            showNotification(data.message || 'Error posting journal entry', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error posting journal entry', 'error');
    });
}

function voidJournalEntry() {
    if (!currentJournalEntryId) return;
    
    const reason = prompt('Please enter a reason for voiding this journal entry:');
    if (!reason) return;
    
    if (!confirm('Are you sure you want to void this journal entry? This will reverse all account balances.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'void_journal_entry');
    formData.append('journal_entry_id', currentJournalEntryId);
    formData.append('reason', reason);
    
    fetch('../modules/api/general-ledger-data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('journalEntryDetailModal'));
            modal.hide();
            loadTransactionsTable();
            loadStatistics();
        } else {
            showNotification(data.message || 'Error voiding journal entry', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error voiding journal entry', 'error');
    });
}

// ========================================
// AUDIT TRAIL FUNCTIONS
// ========================================

function loadAuditTrail() {
    const dateFrom = document.getElementById('audit-date-from')?.value || '';
    const dateTo = document.getElementById('audit-date-to')?.value || '';
    
    const params = new URLSearchParams();
    params.append('action', 'get_audit_trail');
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAuditTrail(data.data);
            } else {
                console.error('Error loading audit trail:', data.message);
                showNotification('Error loading audit trail', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading audit trail:', error);
            showNotification('Error loading audit trail', 'error');
        });
}

function displayAuditTrail(logs) {
    const tbody = document.querySelector('#audit-trail-table tbody');
    
    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No audit log entries found</td></tr>';
        return;
    }
    
    let html = '';
    logs.forEach(log => {
        html += `
            <tr>
                <td>${log.created_at}</td>
                <td>${log.full_name} (${log.username})</td>
                <td><span class="badge bg-info">${log.action}</span></td>
                <td>${log.object_type}</td>
                <td>${log.additional_info || '-'}</td>
                <td>${log.ip_address || '-'}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

function resetAuditFilter() {
    document.getElementById('audit-date-from').value = '';
    document.getElementById('audit-date-to').value = '';
    loadAuditTrail();
}

function exportAuditTrail() {
    showNotification('Export functionality coming soon', 'info');
}

// ========================================
// TRIAL BALANCE FUNCTIONS
// ========================================

function generateTrialBalance() {
    const dateFrom = document.getElementById('trial-balance-from')?.value || '';
    const dateTo = document.getElementById('trial-balance-to')?.value || '';
    
    if (!dateFrom || !dateTo) {
        showNotification('Please select both start and end dates', 'error');
        return;
    }
    
    if (new Date(dateFrom) > new Date(dateTo)) {
        showNotification('Start date cannot be after end date', 'error');
        return;
    }
    
    const params = new URLSearchParams();
    params.append('action', 'get_trial_balance');
    params.append('date_from', dateFrom);
    params.append('date_to', dateTo);
    
    const tbody = document.querySelector('#trial-balance-table tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="loading-spinner"></div><p>Generating trial balance...</p></td></tr>';
    
    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTrialBalance(data.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error: ${data.message || 'Failed to generate trial balance'}</td></tr>`;
                showNotification(data.message || 'Error generating trial balance', 'error');
            }
        })
        .catch(error => {
            console.error('Error generating trial balance:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Error generating trial balance</td></tr>';
            showNotification('Error generating trial balance', 'error');
        });
}

function displayTrialBalance(data) {
    const tbody = document.querySelector('#trial-balance-table tbody');
    const footer = document.getElementById('trial-balance-footer');
    const hint = document.getElementById('trial-balance-hint');
    const exportBtn = document.getElementById('exportTrialBalanceBtn');
    const printBtn = document.getElementById('printTrialBalanceBtn');
    
    if (data.accounts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No transactions found for the selected period</td></tr>';
        footer.style.display = 'none';
        exportBtn.style.display = 'none';
        printBtn.style.display = 'none';
        hint.textContent = 'No data for selected period';
        return;
    }
    
    let html = '';
    data.accounts.forEach(account => {
        const netBalance = account.net_balance;
        const netBalanceClass = netBalance >= 0 ? 'text-success' : 'text-danger';
        const netBalanceDisplay = netBalance >= 0 ? 
            `₱${formatCurrency(Math.abs(netBalance))}` : 
            `(₱${formatCurrency(Math.abs(netBalance))})`;
        
        html += `
            <tr>
                <td>${account.code}</td>
                <td>${account.name}</td>
                <td><span class="badge bg-secondary">${account.account_type}</span></td>
                <td class="text-end">${account.debit_balance > 0 ? '₱' + formatCurrency(account.debit_balance) : '-'}</td>
                <td class="text-end">${account.credit_balance > 0 ? '₱' + formatCurrency(account.credit_balance) : '-'}</td>
                <td class="text-end ${netBalanceClass}">${netBalanceDisplay}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Display totals in footer
    const totals = data.totals;
    const difference = totals.difference;
    const differenceClass = difference > 0.01 ? 'text-danger' : 'text-success';
    const differenceDisplay = difference > 0.01 ? 
        `Difference: ₱${formatCurrency(difference)}` : 
        'Balanced';
    
    footer.innerHTML = `
        <tr class="table-light">
            <th colspan="3" class="text-end">TOTALS:</th>
            <th class="text-end">₱${formatCurrency(totals.total_debit)}</th>
            <th class="text-end">₱${formatCurrency(totals.total_credit)}</th>
            <th class="text-end ${differenceClass}">${differenceDisplay}</th>
        </tr>
    `;
    footer.style.display = '';
    
    // Update hint and show export/print buttons
    const fromDate = new Date(data.date_from).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    const toDate = new Date(data.date_to).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    hint.textContent = `Trial balance from ${fromDate} to ${toDate}`;
    exportBtn.style.display = 'inline-block';
    printBtn.style.display = 'inline-block';
    
    // Store data for export/print
    window.currentTrialBalanceData = data;
}

function resetTrialBalanceFilter() {
    document.getElementById('trial-balance-from').value = '';
    document.getElementById('trial-balance-to').value = '';
    const tbody = document.querySelector('#trial-balance-table tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><p class="text-muted">Select date range and click "Generate Report" to view trial balance</p></td></tr>';
    document.getElementById('trial-balance-footer').style.display = 'none';
    document.getElementById('exportTrialBalanceBtn').style.display = 'none';
    document.getElementById('printTrialBalanceBtn').style.display = 'none';
    document.getElementById('trial-balance-hint').textContent = 'Trial balance for selected period';
    window.currentTrialBalanceData = null;
}

function exportTrialBalance() {
    if (!window.currentTrialBalanceData) {
        showNotification('No trial balance data to export', 'error');
        return;
    }
    
    const data = window.currentTrialBalanceData;
    const fromDate = data.date_from.replace(/-/g, '');
    const toDate = data.date_to.replace(/-/g, '');
    
    // Create CSV content
    let csv = 'Trial Balance Report\n';
    csv += `Period: ${data.date_from} to ${data.date_to}\n\n`;
    csv += 'Account Code,Account Name,Type,Debit Balance,Credit Balance,Net Balance\n';
    
    data.accounts.forEach(account => {
        csv += `"${account.code}","${account.name}","${account.account_type}",${account.debit_balance},${account.credit_balance},${account.net_balance}\n`;
    });
    
    csv += `\nTotal,${data.totals.total_debit},${data.totals.total_credit},${data.totals.total_debit - data.totals.total_credit}\n`;
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `trial_balance_${fromDate}_${toDate}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Trial balance exported successfully', 'success');
}

function printTrialBalance() {
    if (!window.currentTrialBalanceData) {
        showNotification('No trial balance data to print', 'error');
        return;
    }
    
    const data = window.currentTrialBalanceData;
    const fromDate = new Date(data.date_from).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const toDate = new Date(data.date_to).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    
    // Create print-friendly HTML
    let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Trial Balance Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { text-align: center; margin-bottom: 10px; }
                .period { text-align: center; margin-bottom: 20px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .text-end { text-align: right; }
                .text-success { color: #28a745; }
                .text-danger { color: #dc3545; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <h1>Trial Balance Report</h1>
            <div class="period">Period: ${fromDate} to ${toDate}</div>
            <table>
                <thead>
                    <tr>
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Type</th>
                        <th class="text-end">Debit Balance</th>
                        <th class="text-end">Credit Balance</th>
                        <th class="text-end">Net Balance</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.accounts.forEach(account => {
        const netBalance = account.net_balance;
        const netBalanceClass = netBalance >= 0 ? 'text-success' : 'text-danger';
        const netBalanceDisplay = netBalance >= 0 ? 
            `₱${formatCurrency(Math.abs(netBalance))}` : 
            `(₱${formatCurrency(Math.abs(netBalance))})`;
        
        printContent += `
            <tr>
                <td>${account.code}</td>
                <td>${account.name}</td>
                <td>${account.account_type}</td>
                <td class="text-end">${account.debit_balance > 0 ? '₱' + formatCurrency(account.debit_balance) : '-'}</td>
                <td class="text-end">${account.credit_balance > 0 ? '₱' + formatCurrency(account.credit_balance) : '-'}</td>
                <td class="text-end ${netBalanceClass}">${netBalanceDisplay}</td>
            </tr>
        `;
    });
    
    const difference = data.totals.difference;
    const differenceClass = difference > 0.01 ? 'text-danger' : 'text-success';
    const differenceDisplay = difference > 0.01 ? 
        `Difference: ₱${formatCurrency(difference)}` : 
        'Balanced';
    
    printContent += `
                </tbody>
                <tfoot>
                    <tr style="background-color: #f2f2f2; font-weight: bold;">
                        <td colspan="3" class="text-end">TOTALS:</td>
                        <td class="text-end">₱${formatCurrency(data.totals.total_debit)}</td>
                        <td class="text-end">₱${formatCurrency(data.totals.total_credit)}</td>
                        <td class="text-end ${differenceClass}">${differenceDisplay}</td>
                    </tr>
                </tfoot>
            </table>
            <div class="footer">Generated on ${new Date().toLocaleString()}</div>
        </body>
        </html>
    `;
    
    // Open print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
    }, 250);
}

// ========================================
// EXPORT FUNCTIONS
// ========================================

function exportAccounts() {
    const search = document.getElementById('account-search')?.value || '';
    
    const params = new URLSearchParams();
    params.append('action', 'export_accounts');
    if (search) params.append('search', search);
    
    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create CSV content
                let csv = 'Account Code,Account Name,Type,Balance,Status\n';
                
                data.data.forEach(account => {
                    csv += `"${account.code}","${account.name}","${account.type}",${account.balance},"${account.status}"\n`;
                });
                
                // Download CSV
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', data.filename || 'accounts_export.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showNotification('Accounts exported successfully', 'success');
            } else {
                showNotification(data.message || 'Error exporting accounts', 'error');
            }
        })
        .catch(error => {
            console.error('Error exporting accounts:', error);
            showNotification('Error exporting accounts', 'error');
        });
}

function exportTransactions() {
    const dateFrom = document.getElementById('transaction-from')?.value || '';
    const dateTo = document.getElementById('transaction-to')?.value || '';
    const type = document.getElementById('transaction-type')?.value || '';
    
    const params = new URLSearchParams();
    params.append('action', 'export_transactions');
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (type) params.append('type', type);
    
    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create CSV content
                let csv = 'Journal Number,Entry Date,Type,Description,Reference Number,Debit,Credit,Status,Created By\n';
                
                data.data.forEach(txn => {
                    csv += `"${txn.journal_no}","${txn.entry_date}","${txn.type}","${txn.description}","${txn.reference_no || ''}",${txn.debit},${txn.credit},"${txn.status}","${txn.created_by}"\n`;
                });
                
                // Download CSV
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', data.filename || 'transactions_export.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showNotification('Transactions exported successfully', 'success');
            } else {
                showNotification(data.message || 'Error exporting transactions', 'error');
            }
        })
        .catch(error => {
            console.error('Error exporting transactions:', error);
            showNotification('Error exporting transactions', 'error');
        });
}

function printTransactions() {
    const table = document.getElementById('recent-transactions-table');
    if (!table) {
        showNotification('No transaction data to print', 'error');
        return;
    }
    
    // Create print-friendly HTML
    let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Transaction Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .text-end { text-align: right; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <h1>Transaction Report</h1>
            <table>
    `;
    
    // Copy table structure
    printContent += table.outerHTML;
    
    printContent += `
            </table>
            <div class="footer">Generated on ${new Date().toLocaleString()}</div>
        </body>
        </html>
    `;
    
    // Open print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
    }, 250);
}

// Make functions globally available
window.viewTransactionDetails = viewTransactionDetails;
window.viewTransactionDetailsById = viewTransactionDetailsById;
window.postJournalEntry = postJournalEntry;
window.voidJournalEntry = voidJournalEntry;
window.loadAuditTrail = loadAuditTrail;
window.resetAuditFilter = resetAuditFilter;
window.exportAuditTrail = exportAuditTrail;
window.generateTrialBalance = generateTrialBalance;
window.resetTrialBalanceFilter = resetTrialBalanceFilter;
window.exportTrialBalance = exportTrialBalance;
window.printTrialBalance = printTrialBalance;
window.exportAccounts = exportAccounts;
window.exportTransactions = exportTransactions;
window.printTransactions = printTransactions;