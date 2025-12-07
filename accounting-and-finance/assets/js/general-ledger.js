// ========================================
// GENERAL LEDGER MODULE - BEAUTIFUL & CLEAN JS
// ========================================

// ========================================
// PAGINATION STATE
// ========================================

let paginationState = {
    accounts: { perPage: 25, currentPage: 1 },
    transactions: { perPage: 25, currentPage: 1 },
    audit: { perPage: 25, currentPage: 1 },
    trialBalance: { perPage: 25, currentPage: 1 }
};

document.addEventListener('DOMContentLoaded', function() {
    initializeGeneralLedger();
});

function initializeGeneralLedger() {
    console.log('General Ledger module initialized');
    
    // Add smooth animations
    addSmoothAnimations();
    
    // Add Enter key support for account search
    const accountSearchInput = document.getElementById('account-search');
    if (accountSearchInput) {
        accountSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyAccountFilter();
            }
        });
    }
    
    // Load account types dynamically for filter dropdown
    loadAccountTypes();
    
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
// LOAD ACCOUNT TYPES FOR FILTER
// ========================================

function loadAccountTypes() {
    fetch('../modules/api/general-ledger-data.php?action=get_account_types')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                const typeFilter = document.getElementById('account-type-filter');
                if (typeFilter) {
                    // Clear existing options except "All Account Types"
                    typeFilter.innerHTML = '<option value="">All Account Types</option>';
                    
                    // Add account types from database, excluding USD Account
                    data.data.forEach(type => {
                        // Filter out USD Account
                        if (type.toLowerCase() !== 'usd account') {
                            const option = document.createElement('option');
                            option.value = type;
                            option.textContent = type;
                            typeFilter.appendChild(option);
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading account types:', error);
            // Keep default options if API fails
        });
}

// ========================================
// LOAD ACCOUNTS TABLE
// ========================================

function loadAccountsTable(searchTerm = '', accountType = '') {
    showLoadingState('accounts');

    const params = new URLSearchParams();
    params.append('action', 'get_accounts');
    if (searchTerm) {
        params.append('search', searchTerm);
    }
    if (accountType) {
        params.append('account_type', accountType);
    }
    params.append('limit', paginationState.accounts.perPage);
    params.append('offset', (paginationState.accounts.currentPage - 1) * paginationState.accounts.perPage);

    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Accounts API response:', data);
            if (data.success) {
                if (data.data && data.data.length > 0) {
                    displayAccountsTable(data.data, data);
                } else {
                    console.warn('API returned success but no accounts found');
                    showNotification('No accounts found in database. Please check that bank customer accounts exist.', 'warning');
                    displayAccountsTable([], data);
                }
            } else {
                console.error('API returned error:', data.message || 'Unknown error');
                showNotification('Error loading accounts: ' + (data.message || 'Unknown error'), 'error');
                if (data.debug) {
                    console.error('Debug info:', data.debug);
                }
                displayAccountsTable([], data);
            }
        })
        .catch(error => {
            console.error('Error loading accounts:', error);
            showNotification('Error loading accounts. Please check console for details.', 'error');
            displayAccountsTable([]);
        });
}

function displayAccountsTable(accounts, responseData = {}) {
    const tbody = document.querySelector('#accounts-table tbody');
    
    if (!tbody) {
        console.error('Accounts table tbody not found');
        return;
    }
    
    if (accounts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No accounts found</td></tr>';
        const hintElement = document.getElementById('accounts-hint');
        if (hintElement) {
            hintElement.textContent = 'No accounts found';
        }
        return;
    }
    
    let html = '';
    // Show all bank customer accounts
    accounts.forEach((account, index) => {
        html += `
            <tr style="animation-delay: ${index * 0.1}s">
                <td><strong class="account-number">${escapeHtml(account.account_number)}</strong></td>
                <td><span class="account-name">${escapeHtml(account.account_name)}</span></td>
                <td><span class="badge bg-info">${escapeHtml(account.account_type)}</span></td>
                <td class="amount-cell">₱${formatCurrency(account.available_balance)}</td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="viewAccountDetails('${escapeHtml(account.account_number)}', 'bank')">View</button></td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Update table hint to show count
    const hintElement = document.getElementById('accounts-hint');
    if (hintElement) {
        const total = responseData.total || responseData.count || accounts.length;
        const start = (paginationState.accounts.currentPage - 1) * paginationState.accounts.perPage + 1;
        const end = Math.min(start + accounts.length - 1, total);
        hintElement.textContent = `Showing ${start}-${end} of ${total} account${total !== 1 ? 's' : ''}`;
    }
    
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

    // Build query string
    const params = new URLSearchParams();
    params.append('action', 'get_transactions');
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    params.append('limit', paginationState.transactions.perPage);
    params.append('offset', (paginationState.transactions.currentPage - 1) * paginationState.transactions.perPage);

    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayTransactionsTable(data.data, data);
            } else {
                console.warn('API returned error, using fallback transactions data');
                displayTransactionsTable(getFallbackTransactions(), { total: 0 });
            }
        })
        .catch(error => {
            console.error('Error loading transactions:', error);
            console.log('Using fallback transactions data');
            displayTransactionsTable(getFallbackTransactions());
        });
}

function displayTransactionsTable(transactions, responseData = {}) {
    const tbody = document.querySelector('#transactions-table tbody');
    
    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No transactions found</td></tr>';
        const hintElement = document.getElementById('transactions-hint');
        if (hintElement) {
            hintElement.textContent = 'No transactions found';
        }
        return;
    }
    
    let html = '';
    transactions.forEach((txn, index) => {
        const rawId = txn.id || txn.entry_id || '';
        const journalNo = escapeHtml(txn.journal_no || '');
        const source = txn.source || 'journal';
        // Extract numeric ID from string like "JE-123" or "BT-456"
        let entryId = 0;
        if (rawId) {
            const match = rawId.toString().match(/(\d+)$/);
            entryId = match ? parseInt(match[1]) : 0;
        }
        // Store the full ID and source in data attributes
        html += `
            <tr style="animation-delay: ${index * 0.1}s" data-entry-id="${rawId}" data-source="${source}">
                <td><strong class="transaction-id">${escapeHtml(txn.journal_no || '-')}</strong></td>
                <td><span class="transaction-date">${escapeHtml(txn.entry_date || '-')}</span></td>
                <td><span class="transaction-desc">${escapeHtml(txn.description || '-')}</span></td>
                <td class="text-end amount-debit">₱${formatCurrency(txn.total_debit || 0)}</td>
                <td class="text-end amount-credit">₱${formatCurrency(txn.total_credit || 0)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewTransactionDetailsById('${rawId}', '${journalNo}', '${source}')" title="View transaction details">
                        <i class="fas fa-eye me-1"></i>View
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Update table hint to show count
    const hintElement = document.getElementById('transactions-hint');
    if (hintElement) {
        const total = responseData.total || responseData.count || transactions.length;
        const start = (paginationState.transactions.currentPage - 1) * paginationState.transactions.perPage + 1;
        const end = Math.min(start + transactions.length - 1, total);
        hintElement.textContent = `Showing ${start}-${end} of ${total} transaction${total !== 1 ? 's' : ''}`;
    }
    
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
    console.log('Applying chart filters...');
    showNotification('Refreshing charts with current data...', 'info');
    // Reload charts with fresh data
    loadCharts();
}

function viewDrillDown() {
    console.log('Opening drill-down view...');
    showNotification('Drill-down feature: View detailed account breakdown', 'info');
    // Scroll to accounts table for detailed view
    const accountsSection = document.getElementById('accounts');
    if (accountsSection) {
        accountsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => {
            const searchInput = document.getElementById('account-search');
            if (searchInput) {
                searchInput.focus();
            }
        }, 500);
    }
}

function applyAccountFilter() {
    const searchInput = document.getElementById('account-search');
    const typeFilter = document.getElementById('account-type-filter');
    
    if (!searchInput || !typeFilter) {
        console.error('Account filter inputs not found');
        return;
    }
    
    const searchTerm = searchInput.value.trim();
    const accountType = typeFilter.value;
    
    console.log('Applying account filter:', { search: searchTerm, type: accountType });
    
    let filterMsg = '';
    if (searchTerm && accountType) {
        filterMsg = `Filtering by "${searchTerm}" and "${accountType}"...`;
    } else if (searchTerm) {
        filterMsg = `Searching for "${searchTerm}"...`;
    } else if (accountType) {
        filterMsg = `Filtering by account type: "${accountType}"...`;
    }
    
    if (filterMsg) {
        showNotification(filterMsg, 'info');
    }
    
    loadAccountsTable(searchTerm, accountType);
}

function resetAccountFilter() {
    const searchInput = document.getElementById('account-search');
    const typeFilter = document.getElementById('account-type-filter');
    
    if (searchInput) {
        searchInput.value = '';
    }
    if (typeFilter) {
        typeFilter.value = '';
    }
    
    console.log('Resetting account filter...');
    loadAccountsTable('', '');
    showNotification('Account filter reset', 'info');
}

function applyTransactionFilter() {
    const dateFrom = document.getElementById('transaction-from')?.value || '';
    const dateTo = document.getElementById('transaction-to')?.value || '';

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

    if (dateFromInput) dateFromInput.value = '';
    if (dateToInput) dateToInput.value = '';

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

function viewAccountDetails(accountCode, source = '') {
    console.log('Opening account details for:', accountCode, source);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('accountDetailModal'));
    modal.show();
    
    // Set loading state
    document.getElementById('accountDetailBody').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading account details...</p></div>';
    
    // Fetch account transaction history
    const params = new URLSearchParams();
    params.append('action', 'get_account_transactions');
    params.append('account_code', accountCode);
    if (source) {
        params.append('source', source);
    }
    
    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAccountTransactions(data.data);
            } else {
                document.getElementById('accountDetailBody').innerHTML = `<div class="alert alert-warning">${data.message || 'Unable to load account details'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading account details:', error);
            document.getElementById('accountDetailBody').innerHTML = '<div class="alert alert-danger">Error loading account details. Please try again.</div>';
        });
}

function displayAccountTransactions(data) {
    const accountInfo = data.account;
    const transactions = data.transactions || [];
    
    let html = `
        <div class="account-detail-header mb-4">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary"><i class="fas fa-user me-2"></i>${escapeHtml(accountInfo.account_name)}</h5>
                    <p class="mb-1"><strong>Account Number:</strong> <code>${escapeHtml(accountInfo.account_number)}</code></p>
                    <p class="mb-1"><strong>Account Type:</strong> <span class="badge bg-info">${escapeHtml(accountInfo.account_type)}</span></p>
                </div>
                <div class="col-md-6 text-end">
                    <h6 class="text-muted">Available Balance</h6>
                    <h3 class="text-success mb-0">₱${formatCurrency(accountInfo.available_balance)}</h3>
                </div>
            </div>
        </div>
        
        <hr>
        
        <h6 class="mt-4 mb-3"><i class="fas fa-history me-2"></i>Transaction History</h6>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>`;
    
    if (transactions.length === 0) {
        html += '<tr><td colspan="6" class="text-center text-muted py-4">No transactions found for this account</td></tr>';
    } else {
        let runningBalance = 0;
        transactions.forEach(txn => {
            const debit = parseFloat(txn.debit) || 0;
            const credit = parseFloat(txn.credit) || 0;
            runningBalance += debit - credit;
            
            html += `
                <tr>
                    <td>${escapeHtml(txn.date)}</td>
                    <td><strong>${escapeHtml(txn.reference)}</strong></td>
                    <td>${escapeHtml(txn.description)}</td>
                    <td class="text-end">${debit > 0 ? '₱' + formatCurrency(debit) : '-'}</td>
                    <td class="text-end">${credit > 0 ? '₱' + formatCurrency(credit) : '-'}</td>
                    <td class="text-end"><strong>₱${formatCurrency(runningBalance)}</strong></td>
                </tr>
            `;
        });
    }
    
    html += `
                </tbody>
            </table>
        </div>
        
        <div class="mt-3 text-muted text-end">
            <small>Showing ${transactions.length} transaction${transactions.length !== 1 ? 's' : ''}</small>
        </div>
    `;
    
    document.getElementById('accountDetailBody').innerHTML = html;
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
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
    const table = document.getElementById('transactions-table');
    if (!table) {
        showNotification('No transaction data to print', 'error');
        return;
    }
    
    // Get filter values for header
    const dateFrom = document.getElementById('transaction-from')?.value || '';
    const dateTo = document.getElementById('transaction-to')?.value || '';
    const dateRange = dateFrom && dateTo 
        ? `${new Date(dateFrom).toLocaleDateString()} to ${new Date(dateTo).toLocaleDateString()}`
        : 'All Transactions';
    
    // Get all transaction rows (including header)
    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    
    if (!thead || !tbody || tbody.children.length === 0) {
        showNotification('No transaction data to print', 'error');
        return;
    }
    
    // Create print-friendly HTML
    let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Transaction Records Report</title>
            <style>
                @media print {
                    @page {
                        size: landscape;
                        margin: 1cm;
                    }
                }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 20px; 
                    font-size: 12px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .header h1 { 
                    margin: 0 0 5px 0;
                    font-size: 20px;
                    color: #333;
                }
                .header-info {
                    font-size: 12px;
                    color: #666;
                    margin-top: 5px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 10px;
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 8px; 
                    text-align: left;
                }
                th { 
                    background-color: #0a3d3d;
                    color: white;
                    font-weight: bold;
                    text-align: center;
                }
                td {
                    background-color: white;
                }
                .text-end { 
                    text-align: right; 
                }
                tr:nth-child(even) td {
                    background-color: #f9f9f9;
                }
                .footer { 
                    margin-top: 20px; 
                    font-size: 10px; 
                    color: #666;
                    text-align: center;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                .no-data {
                    text-align: center;
                    padding: 20px;
                    color: #999;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Transaction Records Report</h1>
                <div class="header-info">
                    <strong>Date Range:</strong> ${dateRange}<br>
                    <strong>Generated:</strong> ${new Date().toLocaleString()}
                </div>
            </div>
            <table>
    `;
    
    // Add table header
    printContent += '<thead><tr>';
    const headerCells = thead.querySelectorAll('th');
    headerCells.forEach(th => {
        const text = th.textContent.trim();
        // Skip Actions column for print
        if (text.toLowerCase() !== 'actions') {
            printContent += `<th>${escapeHtml(text)}</th>`;
        }
    });
    printContent += '</tr></thead>';
    
    // Add table body
    printContent += '<tbody>';
    const rows = tbody.querySelectorAll('tr');
    
    if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td[colspan]'))) {
        printContent += '<tr><td colspan="5" class="no-data">No transactions found</td></tr>';
    } else {
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0 && !row.querySelector('td[colspan]')) {
                printContent += '<tr>';
                cells.forEach((cell, index) => {
                    // Skip Actions column (last column)
                    if (index < cells.length - 1) {
                        const cellText = cell.textContent.trim();
                        const isAmount = cell.classList.contains('text-end') && (cellText.includes('₱') || cellText === '-');
                        printContent += `<td class="${isAmount ? 'text-end' : ''}">${escapeHtml(cellText)}</td>`;
                    }
                });
                printContent += '</tr>';
            }
        });
    }
    
    printContent += `
            </tbody>
            </table>
            <div class="footer">
                <p>Evergreen Accounting & Finance System | Transaction Records Report</p>
                <p>Page 1 | Generated on ${new Date().toLocaleString()}</p>
            </div>
        </body>
        </html>
    `;
    
    // Open print window
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        showNotification('Please allow pop-ups to print this report', 'error');
        return;
    }
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    
    // Wait for content to load, then trigger print
    setTimeout(() => {
        printWindow.print();
        showNotification('Print dialog opened', 'success');
    }, 500);
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
        { account_number: 'SA-6524-2025', account_name: 'Juan tamad', account_type: 'Savings Account', available_balance: 999999.00, source: 'bank' },
        { account_number: 'CH-1001-2025', account_name: 'Maria Reyes', account_type: 'Checking Account', available_balance: 50000.00, source: 'bank' }
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

function viewTransactionDetailsById(entryId, journalNo, source = 'journal') {
    console.log('View transaction details:', { entryId, journalNo, source });
    
    // Show loading state immediately
    const modalBody = document.getElementById('journalEntryDetailBody');
    if (!modalBody) {
        showNotification('Error: Modal not found', 'error');
        return;
    }
    
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-3">Loading transaction details...</p></div>';
    const modalElement = document.getElementById('journalEntryDetailModal');
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modal.show();
    }
    
    // Extract numeric ID from string like "JE-123" or "BT-456"
    let numericId = 0;
    if (entryId) {
        const match = entryId.toString().match(/(\d+)$/);
        numericId = match ? parseInt(match[1]) : 0;
    }
    
    if (!numericId || numericId <= 0) {
        console.warn('Invalid entry ID, trying journal number:', journalNo);
        if (journalNo) {
            viewTransactionDetails(journalNo);
        } else {
            showNotification('Transaction ID or journal number is required', 'error');
            modalBody.innerHTML = '<div class="alert alert-danger">Invalid transaction ID</div>';
        }
        return;
    }
    
    // Load details based on source
    if (source === 'bank') {
        loadBankTransactionDetails(numericId, journalNo);
    } else {
        loadJournalEntryDetails(numericId);
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
    console.log('Loading journal entry details for ID:', entryId);
    
    if (!entryId || entryId <= 0) {
        showNotification('Invalid journal entry ID', 'error');
        return;
    }
    
    fetch(`../modules/api/general-ledger-data.php?action=get_journal_entry_details&id=${entryId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Journal entry details response:', data);
            if (data.success && data.data) {
                displayJournalEntryDetails(data.data);
                currentJournalEntryId = entryId;
            } else {
                const errorMsg = data.message || 'Error loading journal entry details';
                console.error('API error:', errorMsg);
                showNotification(errorMsg, 'error');
                const modalBody = document.getElementById('journalEntryDetailBody');
                if (modalBody) {
                    modalBody.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>${escapeHtml(errorMsg)}</div>`;
                }
            }
        })
        .catch(error => {
            console.error('Error loading journal entry details:', error);
            showNotification('Error loading journal entry details: ' + error.message, 'error');
            const modalBody = document.getElementById('journalEntryDetailBody');
            if (modalBody) {
                modalBody.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ${escapeHtml(error.message)}</div>`;
            }
        });
}

function loadBankTransactionDetails(transactionId, journalNo) {
    console.log('Loading bank transaction details for ID:', transactionId);
    
    // For bank transactions, show a simplified view
    const modalBody = document.getElementById('journalEntryDetailBody');
    if (!modalBody) {
        showNotification('Error: Modal not found', 'error');
        return;
    }
    
    // Fetch bank transaction details
    fetch(`../modules/api/general-ledger-data.php?action=get_bank_transaction_details&id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                displayBankTransactionDetails(data.data);
            } else {
                // Fallback: show basic info from the transaction list
                displayBankTransactionDetails({
                    transaction_ref: journalNo,
                    description: 'Bank Transaction',
                    amount: 0,
                    created_at: new Date().toISOString()
                });
            }
        })
        .catch(error => {
            console.error('Error loading bank transaction:', error);
            // Show basic info even if API fails
            displayBankTransactionDetails({
                transaction_ref: journalNo,
                description: 'Bank Transaction',
                amount: 0,
                created_at: new Date().toISOString()
            });
        });
}

function displayBankTransactionDetails(txn) {
    const body = document.getElementById('journalEntryDetailBody');
    if (!body) return;
    
    const date = txn.created_at ? new Date(txn.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A';
    const amount = parseFloat(txn.amount || 0);
    
    let html = `
        <div class="bank-transaction-header mb-4">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary mb-3"><i class="fas fa-university me-2"></i>Bank Transaction Information</h5>
                    <div class="info-group">
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-hashtag me-2 text-muted"></i>Reference:</strong> 
                            <span class="ms-2">${escapeHtml(txn.transaction_ref || 'N/A')}</span>
                        </div>
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-calendar me-2 text-muted"></i>Date:</strong> 
                            <span class="ms-2">${date}</span>
                        </div>
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-dollar-sign me-2 text-muted"></i>Amount:</strong> 
                            <span class="ms-2 ${amount >= 0 ? 'text-success' : 'text-danger'}">₱${formatCurrency(Math.abs(amount))}</span>
                        </div>
                        ${txn.account_number ? `
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-credit-card me-2 text-muted"></i>Account Number:</strong> 
                            <span class="ms-2">${escapeHtml(txn.account_number)}</span>
                        </div>
                        ` : ''}
                        ${txn.transaction_type ? `
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-tag me-2 text-muted"></i>Transaction Type:</strong> 
                            <span class="badge bg-info ms-2">${escapeHtml(txn.transaction_type)}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <strong><i class="fas fa-align-left me-2"></i>Description:</strong>
            <p class="mt-2 p-3 bg-light rounded">${escapeHtml(txn.description || 'N/A')}</p>
        </div>
    `;
    
    body.innerHTML = html;
    
    // Hide action buttons for bank transactions
    const postBtn = document.getElementById('postJournalEntryBtn');
    const voidBtn = document.getElementById('voidJournalEntryBtn');
    if (postBtn) postBtn.classList.add('d-none');
    if (voidBtn) voidBtn.classList.add('d-none');
}

function displayJournalEntryDetails(entry) {
    const body = document.getElementById('journalEntryDetailBody');
    
    if (!body) {
        console.error('Journal entry detail body not found');
        showNotification('Error: Modal element not found', 'error');
        return;
    }
    
    if (!entry) {
        body.innerHTML = '<div class="alert alert-danger">No data available</div>';
        return;
    }
    
    // Format dates
    const entryDate = entry.entry_date ? new Date(entry.entry_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
    const createdDate = entry.created_at ? new Date(entry.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A';
    const postedDate = entry.posted_at ? new Date(entry.posted_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A';
    
    let html = `
        <div class="journal-entry-header mb-4">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary mb-3"><i class="fas fa-file-invoice me-2"></i>Journal Entry Information</h5>
                    <div class="info-group">
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-hashtag me-2 text-muted"></i>Journal Number:</strong> 
                            <span class="ms-2">${escapeHtml(entry.journal_no || 'N/A')}</span>
                        </div>
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-tag me-2 text-muted"></i>Type:</strong> 
                            <span class="badge bg-info ms-2">${escapeHtml(entry.type_name || 'N/A')} (${escapeHtml(entry.type_code || 'N/A')})</span>
                        </div>
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-calendar me-2 text-muted"></i>Date:</strong> 
                            <span class="ms-2">${entryDate}</span>
                        </div>
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-info-circle me-2 text-muted"></i>Status:</strong> 
                            <span class="badge bg-${getStatusColor(entry.status)} ms-2">${escapeHtml((entry.status || 'unknown').toUpperCase())}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="text-secondary mb-3"><i class="fas fa-info-circle me-2"></i>Additional Information</h5>
                    <div class="info-group">
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-calendar-alt me-2 text-muted"></i>Fiscal Period:</strong> 
                            <span class="ms-2">${escapeHtml(entry.period_name || 'N/A')}</span>
                        </div>
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-file-alt me-2 text-muted"></i>Reference:</strong> 
                            <span class="ms-2">${escapeHtml(entry.reference_no || 'N/A')}</span>
                        </div>
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-user me-2 text-muted"></i>Created By:</strong> 
                            <span class="ms-2">${escapeHtml(entry.created_by_name || entry.created_by_username || 'N/A')} on ${createdDate}</span>
                        </div>
                        ${entry.posted_by_name || entry.posted_by_username ? `
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-check-circle me-2 text-muted"></i>Posted By:</strong> 
                            <span class="ms-2">${escapeHtml(entry.posted_by_name || entry.posted_by_username || 'N/A')} on ${postedDate}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <strong><i class="fas fa-align-left me-2"></i>Description:</strong>
            <p class="mt-2 p-3 bg-light rounded">${escapeHtml(entry.description || 'N/A')}</p>
        </div>
        
        <div class="table-responsive">
            <h6 class="mb-3"><i class="fas fa-list me-2"></i>Account Lines</h6>
            <table class="table table-sm table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th><i class="fas fa-hashtag me-1"></i>Account Code</th>
                        <th><i class="fas fa-book me-1"></i>Account Name</th>
                        <th class="text-end"><i class="fas fa-arrow-up me-1"></i>Debit</th>
                        <th class="text-end"><i class="fas fa-arrow-down me-1"></i>Credit</th>
                        <th><i class="fas fa-comment me-1"></i>Memo</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    if (entry.lines && entry.lines.length > 0) {
        entry.lines.forEach((line, index) => {
            html += `
                <tr>
                    <td><strong>${escapeHtml(line.account_code || 'N/A')}</strong></td>
                    <td>${escapeHtml(line.account_name || 'N/A')}</td>
                    <td class="text-end text-success">${line.debit > 0 ? '₱' + formatCurrency(line.debit) : '-'}</td>
                    <td class="text-end text-danger">${line.credit > 0 ? '₱' + formatCurrency(line.credit) : '-'}</td>
                    <td>${escapeHtml(line.memo || '-')}</td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="5" class="text-center text-muted py-3">No account lines found</td></tr>';
    }
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2"><strong>Total</strong></th>
                        <th class="text-end text-success"><strong>₱${formatCurrency(entry.total_debit || 0)}</strong></th>
                        <th class="text-end text-danger"><strong>₱${formatCurrency(entry.total_credit || 0)}</strong></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    body.innerHTML = html;
    
    // Show/hide action buttons based on permissions
    const postBtn = document.getElementById('postJournalEntryBtn');
    const voidBtn = document.getElementById('voidJournalEntryBtn');
    if (postBtn) {
        postBtn.classList.toggle('d-none', !entry.can_post);
    }
    if (voidBtn) {
        voidBtn.classList.toggle('d-none', !entry.can_void);
    }
    
    // Show modal if not already shown
    const modalElement = document.getElementById('journalEntryDetailModal');
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modal.show();
    }
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
    params.append('limit', paginationState.audit.perPage);
    params.append('offset', (paginationState.audit.currentPage - 1) * paginationState.audit.perPage);
    
    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAuditTrail(data.data, data);
            } else {
                console.error('Error loading audit trail:', data.message);
                showNotification('Error loading audit trail', 'error');
                displayAuditTrail([], data);
            }
        })
        .catch(error => {
            console.error('Error loading audit trail:', error);
            showNotification('Error loading audit trail', 'error');
        });
}

function displayAuditTrail(logs, responseData = {}) {
    const tbody = document.querySelector('#audit-trail-table tbody');
    
    if (!tbody) {
        console.error('Audit trail table not found');
        return;
    }
    
    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No audit log entries found</td></tr>';
        const hintElement = document.getElementById('audit-hint');
        if (hintElement) {
            hintElement.textContent = 'No audit logs found';
        }
        return;
    }
    
    let html = '';
    logs.forEach((log, index) => {
        html += `
            <tr style="animation-delay: ${index * 0.05}s">
                <td><small>${escapeHtml(log.created_at)}</small></td>
                <td>${escapeHtml(log.full_name)} <small class="text-muted">(${escapeHtml(log.username)})</small></td>
                <td><span class="badge bg-info">${escapeHtml(log.action)}</span></td>
                <td><span class="badge bg-secondary">${escapeHtml(log.object_type)}</span></td>
                <td>${escapeHtml(log.description || log.additional_info || '-')}</td>
                <td><small class="text-muted">${escapeHtml(log.ip_address || '-')}</small></td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Update count hint
    const hintElement = document.getElementById('audit-hint');
    if (hintElement) {
        const total = responseData.total || responseData.count || logs.length;
        const start = (paginationState.audit.currentPage - 1) * paginationState.audit.perPage + 1;
        const end = Math.min(start + logs.length - 1, total);
        hintElement.textContent = `Showing ${start}-${end} of ${total} audit log${total !== 1 ? 's' : ''}`;
    }
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
    params.append('limit', paginationState.trialBalance.perPage);
    params.append('offset', (paginationState.trialBalance.currentPage - 1) * paginationState.trialBalance.perPage);
    
    const tbody = document.querySelector('#trial-balance-table tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="loading-spinner"></div><p>Generating trial balance...</p></td></tr>';
    
    fetch(`../modules/api/general-ledger-data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTrialBalance(data.data, data);
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

function displayTrialBalance(data, responseData = {}) {
    const tbody = document.querySelector('#trial-balance-table tbody');
    const footer = document.getElementById('trial-balance-footer');
    const hint = document.getElementById('trial-balance-hint');
    const exportBtn = document.getElementById('exportTrialBalanceBtn');
    const printBtn = document.getElementById('printTrialBalanceBtn');
    const paginationControls = document.getElementById('trial-balance-pagination');
    
    if (data.accounts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No transactions found for the selected period</td></tr>';
        footer.style.display = 'none';
        exportBtn.style.display = 'none';
        printBtn.style.display = 'none';
        if (paginationControls) paginationControls.style.display = 'none';
        hint.textContent = 'No data for selected period';
        return;
    }
    
    // Show pagination controls and export/print buttons
    if (paginationControls) paginationControls.style.display = 'inline-block';
    exportBtn.style.display = 'inline-block';
    printBtn.style.display = 'inline-block';
    
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
    const total = responseData.total || responseData.count || data.accounts.length;
    const start = (paginationState.trialBalance.currentPage - 1) * paginationState.trialBalance.perPage + 1;
    const end = Math.min(start + data.accounts.length - 1, total);
    hint.textContent = `Showing ${start}-${end} of ${total} account${total !== 1 ? 's' : ''} - ${fromDate} to ${toDate}`;
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


function exportAccountTransactions() {
    showNotification('Exporting account transactions...', 'info');
    // Implementation would extract table data from modal and export as CSV
    const table = document.querySelector('#accountDetailBody table');
    if (!table) {
        showNotification('No transaction data to export', 'error');
        return;
    }
    
    // Create CSV from table
    let csv = 'Date,Reference,Description,Debit,Credit,Balance\n';
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) {
            const rowData = Array.from(cells).map(cell => {
                let text = cell.textContent.trim();
                text = text.replace(/₱/g, '').replace(/,/g, '');
                return `"${text}"`;
            }).join(',');
            csv += rowData + '\n';
        }
    });
    
    // Download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `account_transactions_${Date.now()}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Account transactions exported successfully', 'success');
}

// ========================================
// PAGINATION CONTROL FUNCTIONS
// ========================================

function changeAccountsPerPage() {
    const select = document.getElementById('accounts-per-page');
    if (select) {
        paginationState.accounts.perPage = parseInt(select.value);
        paginationState.accounts.currentPage = 1;
        const searchInput = document.getElementById('account-search');
        const typeFilter = document.getElementById('account-type-filter');
        loadAccountsTable(searchInput?.value || '', typeFilter?.value || '');
    }
}

function changeTransactionsPerPage() {
    const select = document.getElementById('transactions-per-page');
    if (select) {
        paginationState.transactions.perPage = parseInt(select.value);
        paginationState.transactions.currentPage = 1;
        loadTransactionsTable();
    }
}

function changeAuditPerPage() {
    const select = document.getElementById('audit-per-page');
    if (select) {
        paginationState.audit.perPage = parseInt(select.value);
        paginationState.audit.currentPage = 1;
        loadAuditTrail();
    }
}

function changeTrialBalancePerPage() {
    const select = document.getElementById('trial-balance-per-page');
    if (select) {
        paginationState.trialBalance.perPage = parseInt(select.value);
        paginationState.trialBalance.currentPage = 1;
        generateTrialBalance();
    }
}

// Make functions globally available
window.viewAccountDetails = viewAccountDetails;
window.viewTransactionDetails = viewTransactionDetails;
window.viewTransactionDetailsById = viewTransactionDetailsById;
window.loadJournalEntryDetails = loadJournalEntryDetails;
window.loadBankTransactionDetails = loadBankTransactionDetails;
window.postJournalEntry = postJournalEntry;
window.voidJournalEntry = voidJournalEntry;
window.loadAuditTrail = loadAuditTrail;
window.resetAuditFilter = resetAuditFilter;
window.exportAuditTrail = exportAuditTrail;
window.changeAccountsPerPage = changeAccountsPerPage;
window.changeTransactionsPerPage = changeTransactionsPerPage;
window.changeAuditPerPage = changeAuditPerPage;
window.changeTrialBalancePerPage = changeTrialBalancePerPage;
window.generateTrialBalance = generateTrialBalance;
window.resetTrialBalanceFilter = resetTrialBalanceFilter;
window.exportTrialBalance = exportTrialBalance;
window.printTrialBalance = printTrialBalance;
window.exportAccounts = exportAccounts;
window.exportTransactions = exportTransactions;
window.printTransactions = printTransactions;
window.exportAccountTransactions = exportAccountTransactions;