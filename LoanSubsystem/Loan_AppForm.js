// ================== DOM ELEMENTS ==================
const loanForm = document.getElementById('loanForm');
const applicationContent = document.querySelector('.page-content');
const combinedModal = document.getElementById('combined-modal');
const termsView = document.getElementById('terms-view');
const confirmationView = document.getElementById('confirmation-view');
const progressAccount = document.getElementById('progress-account');
const progressLoan = document.getElementById('progress-loan');
const allInputs = loanForm ? loanForm.querySelectorAll('input, select, textarea') : [];

// ================== PROGRESS TRACKING ==================

function countFilledInputs(section) {
    if (!section) return 0;
    const requiredInputs = section.querySelectorAll('input[required], select[required], textarea[required]');
    let filledCount = 0;
    requiredInputs.forEach(input => {
        const isSelect = input.tagName === 'SELECT';
        const isEmpty = isSelect ? input.value === '' : input.value.trim() === '';
        
        if (input.type === 'file') {
            if (input.files.length > 0) {
                filledCount++;
            }
        } else if (!isEmpty) {
            filledCount++;
        }
    });
    return filledCount;
}

function updateProgress() {
    const accountSection = document.getElementById('step-account-info');
    const loanSection = document.getElementById('step-loan-details');

    const accountInputs = accountSection ? accountSection.querySelectorAll('input[required], select[required], textarea[required]') : [];
    const loanInputs = loanSection ? loanSection.querySelectorAll('input[required], select[required], textarea[required]') : [];

    const totalAccount = accountInputs.length;
    const filledAccount = countFilledInputs(accountSection);
    const isAccountComplete = filledAccount === totalAccount;

    const totalLoan = loanInputs.length;
    const filledLoan = countFilledInputs(loanSection);
    const isLoanComplete = filledLoan === totalLoan;
    
    [progressAccount, progressLoan].forEach(step => {
        step.classList.remove('active', 'complete');
    });

    if (filledAccount > 0) {
        progressAccount.classList.add(isAccountComplete ? 'complete' : 'active');
    }
    
    if (isAccountComplete) {
        if (filledLoan > 0) {
            progressLoan.classList.add(isLoanComplete ? 'complete' : 'active');
        } else {
            progressLoan.classList.add('active');
        }
    }
}

// ================== CUSTOM VALIDATION FUNCTIONS ==================

function showValidationError(input, message, errorId) {
    const errorSpan = document.getElementById(errorId);
    input.classList.add('invalid');
    if (errorSpan) {
        errorSpan.textContent = message;
    }
}

function clearValidationError(input, errorId) {
    const errorSpan = document.getElementById(errorId);
    input.classList.remove('invalid');
    if (errorSpan) {
        errorSpan.textContent = '';
    }
}

function validateField(input, regex, minLength, maxLength, errorMessage, errorId) {
    const value = input.value.trim();
    if (value.length === 0 && input.hasAttribute('required')) {
        showValidationError(input, 'This field is required.', errorId);
        return false;
    }
    if (minLength && value.length < minLength) {
        showValidationError(input, `Must be at least ${minLength} characters.`, errorId);
        return false;
    }
    if (maxLength && value.length > maxLength) {
        showValidationError(input, `Cannot exceed ${maxLength} characters.`, errorId);
        return false;
    }
    if (regex && !new RegExp(regex).test(value)) {
        showValidationError(input, errorMessage, errorId);
        return false;
    }
    clearValidationError(input, errorId);
    return true;
}

function validateAll() {
    let isValid = true;
    const runValidation = (validationFunc) => {
        if (!validationFunc()) {
            isValid = false;
        }
    };

    const fullNameInput = document.getElementById('full_name');
    if (fullNameInput) {
        runValidation(() => validateField(fullNameInput, '^[a-zA-Z\\s]{3,50}$', 3, 50, 'Full Name must be 3-50 letters and spaces only.', 'name-error'));
    }

    // ✅ Validate exactly 10 digits (but skip if readonly)
    const accountInput = document.getElementById('account_number');
    if (accountInput && !accountInput.readOnly) {
        runValidation(() => validateField(accountInput, '^\\d{10}$', 10, 10, 'Account Number must be exactly 10 digits.', 'account-error'));
    }

    const contactInput = document.getElementById('contact_number');
    if (contactInput) {
        runValidation(() => validateField(contactInput, '^\\+?[0-9\\s\\-()]{7,20}$', null, null, 'Enter a valid Contact Number (e.g., +63 912 345 6789).', 'contact-error'));
    }

    const emailInput = document.getElementById('email');
    if (emailInput) {
        runValidation(() => validateField(emailInput, /^\S+@\S+\.\S+$/, null, null, 'Please enter a valid email address.', 'email-error'));
    }

    const amountInput = document.getElementById('loan_amount');
    if (amountInput) {
        const amount = parseInt(amountInput.value);
        if (isNaN(amount) || amount < 5000) {
            showValidationError(amountInput, 'Loan Amount must be a number and at least ₱5,000.', 'amount-error');
            isValid = false;
        } else {
            clearValidationError(amountInput, 'amount-error');
        }
    }

    const loanType = document.getElementById('loan_type');
    const loanTerms = document.getElementById('loan_terms');
    const purpose = document.getElementById('purpose');

    if (loanType && loanType.value === "") {
        showValidationError(loanType, 'Please select a loan type.', 'loan-type-error');
        isValid = false;
    } else {
        clearValidationError(loanType, 'loan-type-error');
    }

    if (loanTerms && loanTerms.value === "") {
        showValidationError(loanTerms, 'Please select loan terms.', 'loan-terms-error');
        isValid = false;
    } else {
        clearValidationError(loanTerms, 'loan-terms-error');
    }

    if (purpose && purpose.value.trim() === "") {
        showValidationError(purpose, 'Please describe the loan purpose.', 'purpose-error');
        isValid = false;
    } else {
        clearValidationError(purpose, 'purpose-error');
    }

    const attachmentInput = document.getElementById('attachment');
    if (attachmentInput) {
        if (attachmentInput.files.length === 0) {
            showValidationError(attachmentInput, 'A document attachment is required.', 'attachment-error');
            isValid = false;
        } else {
            clearValidationError(attachmentInput, 'attachment-error');
        }
    }

    return isValid;
}

// ================== MODAL CONTROL FUNCTIONS ==================

function closeModal() {
    combinedModal.classList.add("hidden");
    applicationContent.classList.remove('blur-background');
    document.body.style.overflow = 'auto';
    updateProgress(); 
}

function openTermsModal() {
    combinedModal.classList.remove("hidden");
    termsView.classList.remove('hidden');
    confirmationView.classList.add('hidden');
    applicationContent.classList.add('blur-background');
    document.body.style.overflow = 'hidden';

    progressAccount.classList.remove('active');
    progressAccount.classList.add('complete');
    progressLoan.classList.remove('active');
    progressLoan.classList.add('complete');
}

window.acceptTerms = function() {
    termsView.classList.add('hidden');
    confirmationView.classList.remove('hidden');

    const now = new Date();
    document.getElementById("ref-date").innerText = now.toLocaleString();
    document.getElementById("ref-number").innerText = "EVG-" + Math.floor(Math.random() * 900000 + 100000);

    const formData = new FormData(loanForm);
    fetch("submit_loan.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        console.log("Server Response:", result);
    })
    .catch(error => {
        console.error("Error submitting form:", error);
    });
}

// ================== EVENT LISTENERS ==================

document.addEventListener("DOMContentLoaded", function () {
    if (!loanForm) return;

    updateProgress();

    allInputs.forEach(input => {
        input.addEventListener('input', function() {
            switch(input.id) {
                case 'full_name':
                    validateField(input, '^[a-zA-Z\\s]{3,50}$', 3, 50, 'Full Name must be 3-50 letters and spaces only.', 'name-error');
                    break;
                case 'account_number':
                    if (!input.readOnly) {
                        validateField(input, '^\\d{10}$', 10, 10, 'Account Number must be exactly 10 digits.', 'account-error');
                    }
                    break;
                case 'contact_number':
                    validateField(input, '^\\+?[0-9\\s\\-()]{7,20}$', null, null, 'Enter a valid Contact Number (e.g., +63 912 345 6789).', 'contact-error');
                    break;
                case 'email':
                    validateField(input, /^\S+@\S+\.\S+$/, null, null, 'Please enter a valid email address.', 'email-error');
                    break;
                case 'loan_amount':
                    const amount = parseInt(input.value);
                    if (isNaN(amount) || amount < 5000) {
                        showValidationError(input, 'Loan Amount must be a number and at least ₱5,000.', 'amount-error');
                    } else {
                        clearValidationError(input, 'amount-error');
                    }
                    break;
                case 'loan_type':
                    if (input.value === "") {
                        showValidationError(input, 'Please select a loan type.', 'loan-type-error');
                    } else {
                        clearValidationError(input, 'loan-type-error');
                    }
                    break;
                case 'loan_terms':
                    if (input.value === "") {
                        showValidationError(input, 'Please select loan terms.', 'loan-terms-error');
                    } else {
                        clearValidationError(input, 'loan-terms-error');
                    }
                    break;
                case 'purpose':
                    if (input.value.trim() === "") {
                        showValidationError(input, 'Please describe the loan purpose.', 'purpose-error');
                    } else {
                        clearValidationError(input, 'purpose-error');
                    }
                    break;
                case 'attachment':
                    if (input.files.length === 0) {
                        showValidationError(input, 'A document attachment is required.', 'attachment-error');
                    } else {
                        clearValidationError(input, 'attachment-error');
                    }
                    break;
            }
            updateProgress();
        });
    });

    loanForm.addEventListener("submit", function (event) {
        event.preventDefault();
        
        if (validateAll()) {
            openTermsModal();
        } else {
            const firstInvalid = loanForm.querySelector('.invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});