<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; 

function formatMobileNumber($number) {
    $digits = preg_replace('/\D/', '', $number);
    
    if (strlen($digits) >= 10) {
        $clean_number = substr($digits, -10);
        return '0' . substr($clean_number, 0, 3) . ' ' . substr($clean_number, 3, 3) . ' ' . substr($clean_number, 6, 4);
    }

    return htmlspecialchars($number ?? 'N/A');
}

$mobile_display = formatMobileNumber($data['profile']->mobile_number);
$mobile_raw = $data['profile']->mobile_number ?? '';
// Clean mobile number for input (remove +63 if present)
if (!empty($mobile_raw) && $mobile_raw !== 'N/A') {
    $mobile_raw = preg_replace('/[^0-9]/', '', $mobile_raw);
    if (strpos($mobile_raw, '63') === 0 && strlen($mobile_raw) > 10) {
        $mobile_raw = '0' . substr($mobile_raw, 2);
    }
}
?>

<style>
.edit-field-group {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.edit-field-group.view-mode .edit-input {
    display: none;
}

.edit-field-group.edit-mode .field-value {
    display: none;
}

.edit-input {
    flex: 1;
    max-width: 400px;
}

.edit-btn {
    background: none;
    border: none;
    color: #003631;
    cursor: pointer;
    font-size: 0.875rem;
    padding: 0.25rem 0.75rem;
    margin-left: 1rem;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.edit-btn:hover {
    background-color: #f0f0f0;
}

.edit-btn i {
    margin-right: 0.25rem;
}

.btn-save, .btn-cancel {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    margin-left: 0.5rem;
}

.btn-save {
    background-color: #003631;
    color: white;
}

.btn-save:hover {
    background-color: #004d47;
}

.btn-cancel {
    background-color: #6c757d;
    color: white;
}

.btn-cancel:hover {
    background-color: #5a6268;
}

.alert {
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
}

.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

select.edit-input {
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background-color: white;
}
</style>

<!-- Container for the entire profile page -->
<div class="container py-5">
    <div class="mx-auto max-w-2xl" style="background-color: #fcf9f4; padding: 2rem; border-radius: 1rem;">

        <!-- Success/Error Messages -->
        <?php if (!empty($data['success_message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($data['success_message']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($data['error_message'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($data['error_message']) ?>
            </div>
        <?php endif; ?>

        <!-- Account Information Section -->
        <div class="mb-5">
            <h3 class="fw-bold mb-4 d-flex align-items-center" style="color: #003631; border-left: 4px solid #003631; padding-left: 10px;">
                Account Information
            </h3>
            
            <form id="profileForm" method="POST" action="<?= URLROOT ?>/customer/profile">
                <div class="p-4 rounded-3" style="background-color: #ffffff; border: 1px solid #e0e0e0;">

                    <!-- Username - NOT EDITABLE -->
                    <div class="d-flex mb-3 align-items-center py-2 border-bottom">
                        <span class="text-muted fw-normal me-5" style="width: 150px;">Username</span>
                        <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['full_name']) ?? 'N/A'; ?></span>
                    </div>

                    <!-- Mobile Number - EDITABLE -->
                    <div class="edit-field-group view-mode mb-3 align-items-center py-2 border-bottom" data-field="mobile_number">
                        <div class="d-flex align-items-center" style="flex: 1;">
                            <span class="text-muted fw-normal me-5" style="width: 150px;">Mobile Number</span>
                            <span class="field-value fw-bold" style="color: #003631;">(+63) <?= htmlspecialchars($mobile_display ?? 'N/A'); ?></span>
                        </div>
                        <input type="text" name="mobile_number" class="edit-input form-control" value="<?= htmlspecialchars($mobile_raw); ?>" placeholder="09123456789">
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>

                    <!-- Email Address - EDITABLE -->
                    <div class="edit-field-group view-mode mb-3 align-items-center py-2" data-field="email_address">
                        <div class="d-flex align-items-center" style="flex: 1;">
                            <span class="text-muted fw-normal me-5" style="width: 150px;">Email Address</span>
                            <span class="field-value fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->email_address ?? 'N/A'); ?></span>
                        </div>
                        <input type="email" name="email_address" class="edit-input form-control" value="<?= htmlspecialchars($data['profile']->email_address ?? ''); ?>" placeholder="email@example.com">
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>

                </div>
            </form>
        </div>
        
        <!-- Personal Information Section -->
        <div class="mb-5">
            <h3 class="fw-bold mb-4 d-flex align-items-center" style="color: #003631; border-left: 4px solid #003631; padding-left: 10px;">
                Personal Information
            </h3>

            <form id="personalForm" method="POST" action="<?= URLROOT ?>/customer/profile">
                <div class="p-4 rounded-3" style="background-color: #ffffff; border: 1px solid #e0e0e0;">
                    
                    <!-- Full Name - NOT EDITABLE -->
                    <div class="d-flex mb-3">
                        <span class="text-muted fw-normal me-5" style="width: 150px;">Full Name</span>
                        <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['full_name']) ?? 'N/A'; ?></span>
                    </div>
                    
                    <!-- Home Address - EDITABLE -->
                    <div class="edit-field-group view-mode mb-3" data-field="home_address">
                        <div class="d-flex" style="flex: 1;">
                            <span class="text-muted fw-normal me-5" style="width: 150px;">Home Address</span>
                            <span class="field-value fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->home_address ?? 'N/A'); ?></span>
                        </div>
                        <input type="text" name="home_address" class="edit-input form-control" value="<?= htmlspecialchars($data['profile']->home_address ?? ''); ?>" placeholder="Street, City, Province">
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>

                    <!-- Gender - EDITABLE -->
                    <div class="edit-field-group view-mode mb-3" data-field="gender">
                        <div class="d-flex" style="flex: 1;">
                            <span class="text-muted fw-normal me-5" style="width: 150px;">Gender</span>
                            <span class="field-value fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->gender ?? 'N/A'); ?></span>
                        </div>
                        <select name="gender" class="edit-input form-control">
                            <option value="">Select Gender</option>
                            <option value="Male" <?= (strtolower($data['profile']->gender ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?= (strtolower($data['profile']->gender ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?= (strtolower($data['profile']->gender ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>

                    <!-- Date of Birth - NOT EDITABLE -->
                    <div class="d-flex mb-3">
                        <span class="text-muted fw-normal me-5" style="width: 150px;">Date of Birth</span>
                        <span class="fw-bold" style="color: #003631;">
                            <?php 
                            if (!empty($data['profile']->date_of_birth) && $data['profile']->date_of_birth !== 'N/A') {
                                echo date('F j, Y', strtotime($data['profile']->date_of_birth));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </span>
                    </div>
                    
                    <!-- Place of Birth (Placeholder) -->
                    <div class="d-flex mb-3">
                        <span class="text-muted fw-normal me-5" style="width: 150px;">Place of Birth</span>
                        <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['place_of_birth']); ?></span>
                    </div>

                    <!-- Civil Status - EDITABLE -->
                    <div class="edit-field-group view-mode mb-3" data-field="civil_status">
                        <div class="d-flex" style="flex: 1;">
                            <span class="text-muted fw-normal me-5" style="width: 150px;">Civil Status</span>
                            <span class="field-value fw-bold text-capitalize" style="color: #003631;"><?= htmlspecialchars($data['profile']->civil_status ?? 'N/A'); ?></span>
                        </div>
                        <select name="civil_status" class="edit-input form-control">
                            <option value="">Select Status</option>
                            <option value="single" <?= (strtolower($data['profile']->civil_status ?? '') === 'single') ? 'selected' : ''; ?>>Single</option>
                            <option value="married" <?= (strtolower($data['profile']->civil_status ?? '') === 'married') ? 'selected' : ''; ?>>Married</option>
                            <option value="divorced" <?= (strtolower($data['profile']->civil_status ?? '') === 'divorced') ? 'selected' : ''; ?>>Divorced</option>
                            <option value="widowed" <?= (strtolower($data['profile']->civil_status ?? '') === 'widowed') ? 'selected' : ''; ?>>Widowed</option>
                            <option value="other" <?= (strtolower($data['profile']->civil_status ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>

                    <!-- Citizenship - EDITABLE -->
                    <div class="edit-field-group view-mode mb-3" data-field="citizenship">
                        <div class="d-flex" style="flex: 1;">
                            <span class="text-muted fw-normal me-5" style="width: 150px;">Citizenship</span>
                            <span class="field-value fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->citizenship ?? 'N/A'); ?></span>
                        </div>
                        <input type="text" name="citizenship" class="edit-input form-control" value="<?= htmlspecialchars($data['profile']->citizenship ?? ''); ?>" placeholder="e.g., Filipino, American">
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>
                    
                </div>
            </form>
        </div>

        <!-- Financial Information Section -->
        <div>
           <h3 class="fw-bold mb-4 d-flex align-items-center" style="color: #003631; border-left: 4px solid #003631; padding-left: 10px;">
                Financial Information
            </h3>

            <form id="financialForm" method="POST" action="<?= URLROOT ?>/customer/profile">
                <div class="p-4 rounded-3" style="background-color: #ffffff; border: 1px solid #e0e0e0;">
                    
                    <!-- Source of Funds (Occupation) - EDITABLE -->
                    <div class="edit-field-group view-mode mb-3" data-field="occupation">
                        <div class="d-flex" style="flex: 1;">
                            <span class="text-muted fw-normal me-5" style="width: 150px;">Source of Funds</span>
                            <span class="field-value fw-bold" style="color: #003631;"><?= htmlspecialchars($data['source_of_funds'] ?? 'N/A'); ?></span>
                        </div>
                        <input type="text" name="occupation" class="edit-input form-control" value="<?= htmlspecialchars($data['profile']->occupation ?? ''); ?>" placeholder="e.g., Employment, Business">
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>

                    <!-- Employment Status -->
                    <div class="d-flex mb-3">
                        <span class="text-muted fw-normal me-5" style="width: 150px;">Employment Status</span>
                        <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['employment_status']); ?></span>
                    </div>

                    <!-- Name of Employer (Company) - EDITABLE -->
                    <div class="edit-field-group view-mode mb-3" data-field="name_of_employer">
                        <div class="d-flex" style="flex: 1;">
                            <span class="text-muted fw-normal me-5" style="width: 150px;">Company</span>
                            <span class="field-value fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->name_of_employer ?? 'N/A'); ?></span>
                        </div>
                        <input type="text" name="name_of_employer" class="edit-input form-control" value="<?= htmlspecialchars($data['profile']->name_of_employer ?? ''); ?>" placeholder="Company Name">
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>

                    <!-- Address (Employer Address Placeholder) -->
                    <div class="d-flex mb-3">
                        <span class="text-muted fw-normal me-5" style="width: 150px;">Address</span>
                        <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['employer_address']); ?></span>
                    </div>

                </div>
            </form>
        </div>

    </div>
</div>

<script>
function toggleEdit(button) {
    const fieldGroup = button.closest('.edit-field-group');
    const form = fieldGroup.closest('form');
    
    if (fieldGroup.classList.contains('view-mode')) {
        // Switch to edit mode
        fieldGroup.classList.remove('view-mode');
        fieldGroup.classList.add('edit-mode');
        
        // Replace Edit button with Save/Cancel buttons
        const fieldName = fieldGroup.getAttribute('data-field');
        button.outerHTML = `
            <button type="button" class="btn-save" onclick="saveField(this, '${fieldName}')">
                <i class="bi bi-check"></i> Save
            </button>
            <button type="button" class="btn-cancel" onclick="cancelEdit(this)">
                <i class="bi bi-x"></i> Cancel
            </button>
        `;
        
        // Focus on the input
        const input = fieldGroup.querySelector('.edit-input');
        if (input) {
            input.focus();
            if (input.tagName === 'INPUT') {
                input.select();
            }
        }
    }
}

function cancelEdit(button) {
    const fieldGroup = button.closest('.edit-field-group');
    fieldGroup.classList.remove('edit-mode');
    fieldGroup.classList.add('view-mode');
    
    // Restore original value (reload page or restore from data attribute)
    location.reload();
}

function saveField(button, fieldName) {
    const fieldGroup = button.closest('.edit-field-group');
    const form = fieldGroup.closest('form');
    const input = fieldGroup.querySelector('.edit-input');
    
    if (!input) {
        alert('Error: Could not find input field.');
        return false;
    }
    
    // Get value - handle both input and select elements
    let value = '';
    if (input.tagName === 'SELECT') {
        value = input.options[input.selectedIndex] ? input.options[input.selectedIndex].value.trim() : '';
    } else {
        value = input.value.trim();
    }
    
    // Validate email
    if (fieldName === 'email_address' && value && !validateEmail(value)) {
        alert('Please enter a valid email address.');
        input.focus();
        return false;
    }
    
    // Validate mobile number - clean it first
    if (fieldName === 'mobile_number' && value) {
        const cleaned = value.replace(/[^0-9]/g, '');
        if (cleaned.length < 10) {
            alert('Please enter a valid mobile number (at least 10 digits).');
            input.focus();
            return false;
        }
        // Format: ensure it starts with 0 and has 11 digits, or starts with 63
        if (cleaned.length === 10) {
            value = '0' + cleaned;
        } else if (cleaned.length > 10 && cleaned.startsWith('63')) {
            value = '0' + cleaned.substring(2);
        } else if (cleaned.length === 11 && cleaned.startsWith('0')) {
            value = cleaned;
        } else {
            value = cleaned;
        }
    }
    
    // Validate required fields
    if (!value && (fieldName === 'email_address' || fieldName === 'mobile_number')) {
        alert('This field cannot be empty.');
        input.focus();
        return false;
    }
    
    // Get form action URL
    const actionUrl = form && form.action ? form.action : '<?= URLROOT ?>/customer/profile';
    
    // Create a hidden form with just this field
    const hiddenForm = document.createElement('form');
    hiddenForm.method = 'POST';
    hiddenForm.action = actionUrl;
    hiddenForm.style.display = 'none';
    hiddenForm.setAttribute('id', 'hiddenForm_' + fieldName);
    
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = fieldName;
    hiddenInput.value = value;
    
    hiddenForm.appendChild(hiddenInput);
    document.body.appendChild(hiddenForm);
    
    // Show loading state
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
    
    // Submit the form
    try {
        hiddenForm.submit();
    } catch (e) {
        alert('Error submitting form. Please try again.');
        button.disabled = false;
        button.innerHTML = originalHTML;
        document.body.removeChild(hiddenForm);
        console.error('Form submission error:', e);
        return false;
    }
    
    return true;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
</script>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
