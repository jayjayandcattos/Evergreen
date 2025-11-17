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
?>

<!-- Container for the entire profile page -->
<div class="container py-5">
    <div class="mx-auto max-w-2xl" style="background-color: #fcf9f4; padding: 2rem; border-radius: 1rem;">

        <!-- Account Information Section -->
        <div class="mb-5">
            <h3 class="fw-bold mb-4 d-flex align-items-center" style="color: #003631; border-left: 4px solid #003631; padding-left: 10px;">
                Account Information
            </h3>
            
            <div class="p-4 rounded-3" style="background-color: #ffffff; border: 1px solid #e0e0e0;">

                <!-- Username -->
                <div class="d-flex mb-3 align-items-center py-2 border-bottom">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Username</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['full_name']) ?? 'N/A'; ?></span>
                </div>

                <!-- Mobile Number -->
                <div class="d-flex mb-3 align-items-center py-2 border-bottom">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Mobile Number</span>
                    <span class="fw-bold" style="color: #003631;">(+63) <?= htmlspecialchars($mobile_display ?? 'N/A'); ?></span>
                </div>

                <!-- Email Address -->
                <div class="d-flex mb-3 align-items-center py-2">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Email Address</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->email_address ?? 'N/A'); ?></span>
                </div>

            </div>
        </div>
        
        <!-- Personal Information Section -->
        <div class="mb-5">
            <h3 class="fw-bold mb-4 d-flex align-items-center" style="color: #003631; border-left: 4px solid #003631; padding-left: 10px;">
                Personal Information
            </h3>

            <div class="p-4 rounded-3" style="background-color: #ffffff; border: 1px solid #e0e0e0;">
                
                <!-- Full Name -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Full Name</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['full_name']) ?? 'N/A'; ?></span>
                </div>
                
                <!-- Home Address -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Home Address</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->home_address ?? 'N/A'); ?></span>
                </div>

                <!-- Gender -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Gender</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->gender ?? 'N/A'); ?></span>
                </div>

                <!-- Date of Birth -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Date of Birth</span>
                    <span class="fw-bold" style="color: #003631;"><?= date('F j, Y', strtotime($data['profile']->date_of_birth ?? 'N/A')); ?></span>
                </div>
                
                <!-- Place of Birth (Placeholder) -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Place of Birth</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['place_of_birth']); ?></span>
                </div>

                <!-- Civil Status -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Civil Status</span>
                    <span class="fw-bold text-capitalize" style="color: #003631;"><?= htmlspecialchars($data['profile']->civil_status ?? 'N/A'); ?></span>
                </div>

                <!-- Citizenship -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Citizenship</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->citizenship ?? 'N/A'); ?></span>
                </div>
                
            </div>
        </div>

        <!-- Financial Information Section -->
        <div>
           <h3 class="fw-bold mb-4 d-flex align-items-center" style="color: #003631; border-left: 4px solid #003631; padding-left: 10px;">
                Financial Information
            </h3>

            <div class="p-4 rounded-3" style="background-color: #ffffff; border: 1px solid #e0e0e0;">
                
                <!-- Source of Funds -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Source of Funds</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['source_of_funds'] ?? 'N/A'); ?></span>
                </div>

                <!-- Employment Status -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Employment Status</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['employment_status']); ?></span>
                </div>

                <!-- Name of Employer -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Company</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['profile']->name_of_employer ?? 'N/A'); ?></span>
                </div>

                <!-- Address (Employer Address Placeholder) -->
                <div class="d-flex mb-3">
                    <span class="text-muted fw-normal me-5" style="width: 150px;">Address</span>
                    <span class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['employer_address']); ?></span>
                </div>

            </div>
        </div>

    </div>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>