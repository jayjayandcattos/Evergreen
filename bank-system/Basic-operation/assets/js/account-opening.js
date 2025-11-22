// Detect API path dynamically based on current page location
function getApiBaseUrl() {
  // Get the current page path
  const currentPath = window.location.pathname;
  
  // If we're in /public/, go up one level to find /api/
  if (currentPath.includes('/public/')) {
    const basePath = currentPath.substring(0, currentPath.indexOf('/public/'));
    return window.location.origin + basePath + '/api';
  }
  
  // Fallback: construct from known structure
  const pathParts = currentPath.split('/');
  const basicOpIndex = pathParts.indexOf('Basic-operation');
  if (basicOpIndex !== -1) {
    const basePath = pathParts.slice(0, basicOpIndex + 1).join('/');
    return window.location.origin + basePath + '/api';
  }
  
  // Final fallback
  return window.location.origin + '/Evergreen/bank-system/Basic-operation/api';
}

// API Base URL
const API_BASE_URL = getApiBaseUrl();
console.log('API Base URL:', API_BASE_URL);

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  setupAccountTypeSelection();
  setupFormHandlers();
  loadCustomerAccounts();
  updateSubmitButtonState(); // Initialize button state (should be disabled)
});

// Setup account type card selection
function setupAccountTypeSelection() {
  const accountCards = document.querySelectorAll('.account-type-card');
  const hiddenInput = document.getElementById('selected_account_type');
  
  accountCards.forEach(card => {
    card.addEventListener('click', function() {
      // Remove selected class from all cards
      accountCards.forEach(c => c.classList.remove('selected'));
      
      // Add selected class to clicked card
      this.classList.add('selected');
      
      // Set hidden input value
      const accountType = this.getAttribute('data-type');
      hiddenInput.value = accountType;
      
      // Clear error
      clearError('account_type');
      
      // Update submit button state
      updateSubmitButtonState();
    });
  });
}

// Customer accounts cache
let customerAccounts = [];

// Account verification state
let isAccountVerified = false;
let verifiedAccountInfo = null;

// Setup form handlers
function setupFormHandlers() {
  const form = document.getElementById('accountOpeningForm');
  
  form.addEventListener('submit', handleFormSubmit);
  
  // Validate existing account number
  const existingAccountInput = document.getElementById('existing_account_number');
  if (existingAccountInput) {
    // Add debounce for account verification
    let verifyTimeout;
    existingAccountInput.addEventListener('input', function(e) {
      clearTimeout(verifyTimeout);
      const accountNumber = e.target.value.trim();
      
      if (accountNumber.length > 0) {
        verifyTimeout = setTimeout(() => {
          verifyExistingAccount(accountNumber);
        }, 500); // Wait 500ms after user stops typing
      } else {
        isAccountVerified = false;
        verifiedAccountInfo = null;
        updateAccountVerificationStatus('', false);
        clearError('existing_account_number');
      }
    });
  }
  
  // Validate initial deposit on input
  const initialDepositInput = document.getElementById('initial_deposit');
  if (initialDepositInput) {
    initialDepositInput.addEventListener('input', validateInitialDeposit);
    initialDepositInput.addEventListener('input', handleDepositAmountChange);
  }
  
  // Handle deposit source selection
  const depositSourceSelect = document.getElementById('deposit_source');
  if (depositSourceSelect) {
    depositSourceSelect.addEventListener('change', handleDepositSourceChange);
  }
  
  // Handle source account selection
  const sourceAccountSelect = document.getElementById('source_account_number');
  if (sourceAccountSelect) {
    sourceAccountSelect.addEventListener('change', handleSourceAccountChange);
  }
}

// Validate initial deposit
function validateInitialDeposit(e) {
  const value = parseFloat(e.target.value);
  
  if (value < 0) {
    showError('initial_deposit', 'Initial deposit cannot be negative');
    return false;
  }
  
  clearError('initial_deposit');
  return true;
}

// Handle form submission
async function handleFormSubmit(e) {
  e.preventDefault();
  
  // Clear previous errors and success message
  clearAllErrors();
  hideSuccessMessage();
  
  // Validate form
  if (!validateForm()) {
    return;
  }
  
  // Disable submit button
  const submitBtn = document.getElementById('submit-btn');
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
  
  try {
    // Collect form data
    const formData = collectFormData();
    
    // Send to API
    const response = await fetch(`${API_BASE_URL}/customer/open-account.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include', // Include cookies for session
      body: JSON.stringify(formData),
    });
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('Server error:', errorText);
      throw new Error('Server error. Please try again.');
    }
    
    const result = await response.json();
    
    if (result.success) {
      // Show success message
      showSuccessMessage(
        `Account opened successfully! Your new ${result.account_type} account number is: <strong>${result.account_number}</strong>`
      );
      
      // Reset form
      document.getElementById('accountOpeningForm').reset();
      document.querySelectorAll('.account-type-card').forEach(card => {
        card.classList.remove('selected');
      });
      document.getElementById('selected_account_type').value = '';
      
      // Reset verification state
      isAccountVerified = false;
      verifiedAccountInfo = null;
      updateAccountVerificationStatus('', false);
      updateSubmitButtonState();
      
      // Reset source account row visibility
      document.getElementById('source_account_row').style.display = 'none';
      
      // Redirect to accounts page after a delay
      setTimeout(() => {
        // Redirect to customer accounts page
        window.location.href = '/Evergreen/bank-system/Basic-operation/operations/public/customer/account';
      }, 3000);
    } else {
      // Show validation errors
      if (result.errors) {
        displayErrors(result.errors);
      } else {
        alert(result.message || 'An error occurred while opening your account. Please try again.');
      }
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Open Account';
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Could not connect to server. Please ensure you are logged in and try again.');
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Open Account';
  }
}

// Verify existing account number
async function verifyExistingAccount(accountNumber) {
  if (!accountNumber || accountNumber.length === 0) {
    isAccountVerified = false;
    verifiedAccountInfo = null;
    updateSubmitButtonState();
    return false;
  }
  
  const statusDiv = document.getElementById('account_verification_status');
  const input = document.getElementById('existing_account_number');
  
  // Show loading state
  statusDiv.innerHTML = '<span style="color: #666;"><i class="bi bi-hourglass-split"></i> Verifying account...</span>';
  input.classList.remove('error');
  clearError('existing_account_number');
  updateSubmitButtonState();
  
  try {
    const response = await fetch(`${API_BASE_URL}/customer/get-my-accounts.php`, {
      method: 'GET',
      credentials: 'include',
    });
    
    const result = await response.json();
    
    if (result.success && result.data) {
      // Check if the entered account number exists in the customer's accounts
      const account = result.data.find(acc => acc.account_number === accountNumber);
      
      if (account) {
        isAccountVerified = true;
        verifiedAccountInfo = account;
        statusDiv.innerHTML = `<span style="color: #28a745;"><i class="bi bi-check-circle"></i> Account verified: ${account.account_type} (₱${account.balance_formatted})</span>`;
        input.classList.remove('error');
        clearError('existing_account_number');
        updateSubmitButtonState();
        return true;
      } else {
        isAccountVerified = false;
        verifiedAccountInfo = null;
        statusDiv.innerHTML = '<span style="color: #dc3545;"><i class="bi bi-x-circle"></i> Account not found or does not belong to you</span>';
        input.classList.add('error');
        showError('existing_account_number', 'This account number does not belong to you. Please enter a valid account number.');
        updateSubmitButtonState();
        return false;
      }
    } else {
      isAccountVerified = false;
      verifiedAccountInfo = null;
      statusDiv.innerHTML = '<span style="color: #dc3545;"><i class="bi bi-x-circle"></i> Unable to verify account</span>';
      input.classList.add('error');
      showError('existing_account_number', result.message || 'Unable to verify account');
      updateSubmitButtonState();
      return false;
    }
  } catch (error) {
    console.error('Error verifying account:', error);
    isAccountVerified = false;
    verifiedAccountInfo = null;
    statusDiv.innerHTML = '<span style="color: #dc3545;"><i class="bi bi-x-circle"></i> Error verifying account</span>';
    input.classList.add('error');
    showError('existing_account_number', 'Error verifying account. Please try again.');
    updateSubmitButtonState();
    return false;
  }
}

// Update submit button state based on account verification
function updateSubmitButtonState() {
  const submitBtn = document.getElementById('submit-btn');
  const accountType = document.getElementById('selected_account_type').value;
  
  if (submitBtn) {
    // Disable button if account is not verified or account type not selected
    if (!isAccountVerified || !accountType) {
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.6';
      submitBtn.style.cursor = 'not-allowed';
    } else {
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      submitBtn.style.cursor = 'pointer';
    }
  }
}

// Update account verification status display
function updateAccountVerificationStatus(message, isValid) {
  const statusDiv = document.getElementById('account_verification_status');
  if (!statusDiv) return;
  
  if (message) {
    if (isValid) {
      statusDiv.innerHTML = `<span style="color: #28a745;"><i class="bi bi-check-circle"></i> ${message}</span>`;
    } else {
      statusDiv.innerHTML = `<span style="color: #dc3545;"><i class="bi bi-x-circle"></i> ${message}</span>`;
    }
  } else {
    statusDiv.innerHTML = '';
  }
}

// Validate form
function validateForm() {
  let isValid = true;
  
  // Check if existing account number is provided and verified
  const existingAccountNumber = document.getElementById('existing_account_number').value.trim();
  if (!existingAccountNumber) {
    showError('existing_account_number', 'Please enter your existing account number');
    isValid = false;
  } else if (!isAccountVerified) {
    showError('existing_account_number', 'Please verify your account number. Make sure it exists and belongs to you.');
    isValid = false;
  }
  
  // Check if account type is selected
  const accountType = document.getElementById('selected_account_type').value;
  if (!accountType) {
    showError('account_type', 'Please select an account type');
    isValid = false;
  }
  
  // Validate initial deposit if provided
  const initialDeposit = parseFloat(document.getElementById('initial_deposit').value) || 0;
  const depositSource = document.getElementById('deposit_source').value;
  const sourceAccount = document.getElementById('source_account_number').value;
  
  if (initialDeposit > 0) {
    if (!depositSource) {
      showError('deposit_source', 'Please select a deposit source (Cash or Transfer)');
      isValid = false;
    }
    
    if (depositSource === 'transfer') {
      if (!sourceAccount) {
        showError('source_account_number', 'Please select a source account');
        isValid = false;
      } else {
        // Check balance
        const selectedOption = document.getElementById('source_account_number').options[
          document.getElementById('source_account_number').selectedIndex
        ];
        const balance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
        
        if (initialDeposit > balance) {
          showError('initial_deposit', `Insufficient balance. Available: ₱${balance.toFixed(2)}`);
          isValid = false;
        }
      }
    }
    
    if (!validateInitialDeposit({ target: document.getElementById('initial_deposit') })) {
      isValid = false;
    }
  }
  
  return isValid;
}

// Load customer accounts
async function loadCustomerAccounts() {
  try {
    const response = await fetch(`${API_BASE_URL}/customer/get-my-accounts.php`, {
      method: 'GET',
      credentials: 'include',
    });
    
    const result = await response.json();
    
    if (result.success && result.data) {
      customerAccounts = result.data;
      populateSourceAccountDropdown();
    }
  } catch (error) {
    console.error('Error loading customer accounts:', error);
  }
}

// Populate source account dropdown
function populateSourceAccountDropdown() {
  const select = document.getElementById('source_account_number');
  if (!select) return;
  
  if (customerAccounts.length === 0) {
    select.innerHTML = '<option value="">No accounts available</option>';
    return;
  }
  
  const options = customerAccounts.map(account => {
    const balance = parseFloat(account.balance || 0);
    return `<option value="${account.account_number}" data-balance="${balance}" data-account-id="${account.account_id}">
      ${account.account_number} - ${account.account_type} (₱${account.balance_formatted || '0.00'})
    </option>`;
  }).join('');
  
  select.innerHTML = '<option value="">Select source account</option>' + options;
}

// Handle deposit source change
function handleDepositSourceChange(e) {
  const source = e.target.value;
  const sourceAccountRow = document.getElementById('source_account_row');
  const sourceAccountSelect = document.getElementById('source_account_number');
  
  if (source === 'transfer') {
    sourceAccountRow.style.display = 'flex';
    sourceAccountSelect.required = true;
    if (customerAccounts.length > 0) {
      populateSourceAccountDropdown();
    }
  } else {
    sourceAccountRow.style.display = 'none';
    sourceAccountSelect.required = false;
    sourceAccountSelect.value = '';
    clearError('source_account_number');
  }
  
  // Validate if deposit amount is provided
  const depositAmount = document.getElementById('initial_deposit').value;
  if (depositAmount && parseFloat(depositAmount) > 0) {
    if (source === 'transfer' && !sourceAccountSelect.value) {
      showError('deposit_source', 'Please select a source account when transferring funds');
    } else {
      clearError('deposit_source');
    }
  }
}

// Handle deposit amount change
function handleDepositAmountChange(e) {
  const amount = parseFloat(e.target.value) || 0;
  const depositSource = document.getElementById('deposit_source').value;
  
  if (amount > 0 && !depositSource) {
    // Require deposit source if amount is provided
    document.getElementById('deposit_source').required = true;
  } else if (amount === 0) {
    document.getElementById('deposit_source').required = false;
    document.getElementById('deposit_source').value = '';
    document.getElementById('source_account_row').style.display = 'none';
    document.getElementById('source_account_number').value = '';
  }
}

// Handle source account selection change
function handleSourceAccountChange(e) {
  const accountNumber = e.target.value;
  const selectedOption = e.target.options[e.target.selectedIndex];
  const balance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
  
  const balanceText = document.getElementById('source_account_balance');
  if (balanceText) {
    balanceText.textContent = `Available balance: ₱${balance.toFixed(2)}`;
  }
  
  // Validate if deposit amount exceeds balance
  const depositAmount = parseFloat(document.getElementById('initial_deposit').value) || 0;
  if (depositAmount > balance) {
    showError('source_account_number', `Insufficient balance. Available: ₱${balance.toFixed(2)}`);
  } else {
    clearError('source_account_number');
  }
}

// Collect form data
function collectFormData() {
  const form = document.getElementById('accountOpeningForm');
  const formData = new FormData(form);
  
  const depositAmount = formData.get('initial_deposit');
  const depositSource = formData.get('deposit_source');
  const sourceAccount = formData.get('source_account_number');
  
  const data = {
    existing_account_number: formData.get('existing_account_number'),
    account_type: formData.get('account_type'),
    initial_deposit: depositAmount && parseFloat(depositAmount) > 0 ? parseFloat(depositAmount) : null,
    deposit_source: depositSource || null,
    source_account_number: (depositSource === 'transfer' && sourceAccount) ? sourceAccount : null,
    account_purpose: formData.get('account_purpose') || null,
  };
  
  return data;
}

// Display validation errors
function displayErrors(errors) {
  for (let [field, message] of Object.entries(errors)) {
    showError(field, message);
  }
}

// Show error message
function showError(field, message) {
  const errorElement = document.getElementById(`error-${field}`);
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.classList.add('show');
  }
  
  // Also add error class to input
  const input = document.querySelector(`[name="${field}"]`) || 
                document.getElementById(field);
  if (input) {
    input.classList.add('error');
  }
}

// Clear error message
function clearError(field) {
  const errorElement = document.getElementById(`error-${field}`);
  if (errorElement) {
    errorElement.textContent = '';
    errorElement.classList.remove('show');
  }
  
  // Remove error class from input
  const input = document.querySelector(`[name="${field}"]`) || 
                document.getElementById(field);
  if (input) {
    input.classList.remove('error');
  }
}

// Clear all errors
function clearAllErrors() {
  document.querySelectorAll('.error-message').forEach(el => {
    el.textContent = '';
    el.classList.remove('show');
  });
  document.querySelectorAll('.form-control').forEach(el => {
    el.classList.remove('error');
  });
}

// Show success message
function showSuccessMessage(message) {
  const successElement = document.getElementById('success-message');
  if (successElement) {
    successElement.innerHTML = message;
    successElement.classList.add('show');
  }
}

// Hide success message
function hideSuccessMessage() {
  const successElement = document.getElementById('success-message');
  if (successElement) {
    successElement.classList.remove('show');
    successElement.innerHTML = '';
  }
}

