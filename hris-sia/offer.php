<?php
session_start();
require_once 'config/database.php';

$message = '';
$messageType = '';
$applicant = null;

// Get applicant ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch applicant if ID is provided
if ($id > 0) {
    try {
        $applicant = fetchOne($conn, 
            "SELECT a.*, r.job_title, d.department_name
             FROM applicant a
             LEFT JOIN recruitment r ON a.recruitment_id = r.recruitment_id
             LEFT JOIN department d ON r.department_id = d.department_id
             WHERE a.applicant_id = ?",
            [$id]
        );
        
        // Debug: Log if applicant not found
        if (!$applicant) {
            error_log("Offer.php: Applicant ID $id not found in database");
        }
    } catch (Exception $e) {
        error_log("Offer.php: Database error - " . $e->getMessage());
        $applicant = false;
    }
}

// Handle form submission (accept/decline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $applicant && $applicant['offer_status'] === 'Pending') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'accept') {
        $sql = "UPDATE applicant 
                SET offer_status = 'Accepted',
                    offer_acceptance_timestamp = NOW()
                WHERE applicant_id = ? AND offer_status = 'Pending'";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$id])) {
            $message = "Job offer accepted successfully! HR will contact you soon.";
            $messageType = "success";
            // Refresh applicant data from database
            $applicant = fetchOne($conn, 
                "SELECT a.*, r.job_title, d.department_name
                 FROM applicant a
                 LEFT JOIN recruitment r ON a.recruitment_id = r.recruitment_id
                 LEFT JOIN department d ON r.department_id = d.department_id
                 WHERE a.applicant_id = ?",
                [$id]
            );
        }
    } elseif ($action === 'decline') {
        $sql = "UPDATE applicant 
                SET offer_status = 'Declined',
                    offer_declined_at = NOW()
                WHERE applicant_id = ? AND offer_status = 'Pending'";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$id])) {
            $message = "Job offer declined. Thank you for your interest.";
            $messageType = "warning";
            // Refresh applicant data from database
            $applicant = fetchOne($conn, 
                "SELECT a.*, r.job_title, d.department_name
                 FROM applicant a
                 LEFT JOIN recruitment r ON a.recruitment_id = r.recruitment_id
                 LEFT JOIN department d ON r.department_id = d.department_id
                 WHERE a.applicant_id = ?",
                [$id]
            );
        }
    }
}

// Set message if no applicant found or no offer sent
if ($id <= 0) {
    $message = "Invalid offer link. Missing applicant ID. Please verify the link is correct or contact HR.";
    $messageType = "error";
    $applicant = null;
} elseif (!$applicant) {
    // Check if applicant exists at all (even without offer)
    $checkApplicant = fetchOne($conn, "SELECT applicant_id, full_name FROM applicant WHERE applicant_id = ?", [$id]);
    if ($checkApplicant) {
        $message = "Applicant found but no job offer has been sent yet. Please contact HR to send an offer.";
        $messageType = "warning";
        $applicant = $checkApplicant; // Show basic info
    } else {
        $message = "Invalid offer link. Applicant not found (ID: $id). Please verify the link is correct or contact HR.";
        $messageType = "error";
    }
} elseif (!isset($applicant['offer_status']) || $applicant['offer_status'] === null || $applicant['offer_status'] === '') {
    $message = "No job offer has been sent for this applicant yet. Please contact HR to send an offer.";
    $messageType = "warning";
    // Still show applicant info but disable actions
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="assets/evergreen.svg">
    <title>Job Offer - HRIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2f1 50%, #f8fafc 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        .header-gradient {
            background: linear-gradient(135deg, #003631 0%, #004d45 50%, #002b27 100%);
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <!-- Header -->
            <div class="header-gradient text-white p-6 rounded-t-lg shadow-xl">
                <div class="flex items-center gap-3 mb-2">
                    <img src="assets/evergreen.svg" alt="Logo" class="h-10 w-10">
                    <h1 class="text-2xl font-bold">Job Offer</h1>
                </div>
                <p class="text-gray-200">Review and respond to your job offer</p>
            </div>

            <!-- Main Content -->
            <div class="bg-white p-6 lg:p-8 rounded-b-lg shadow-xl">
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg <?php 
                        echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 
                            ($messageType === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                    ?>">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($applicant): ?>
                    <div class="space-y-6">
                        <!-- Applicant Info -->
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Dear <?php echo htmlspecialchars($applicant['full_name']); ?>,</h2>
                            <p class="text-gray-700 leading-relaxed">
                                We are pleased to offer you the position of <strong><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></strong>
                                <?php if ($applicant['department_name']): ?>
                                    in the <strong><?php echo htmlspecialchars($applicant['department_name']); ?></strong> department.
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Job Details -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-800 mb-3">Job Details</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Position:</span>
                                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></span>
                                </div>
                                <?php if ($applicant['department_name']): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Department:</span>
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($applicant['department_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($applicant['offer_sent_at']): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Offer Sent:</span>
                                        <span class="font-medium text-gray-800"><?php echo date('F d, Y', strtotime($applicant['offer_sent_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>


                        <!-- Action Buttons -->
                        <?php if (isset($applicant['offer_status']) && $applicant['offer_status'] === 'Pending'): ?>
                            <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t">
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" 
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition shadow-lg hover:shadow-xl">
                                        <i class="fas fa-check-circle mr-2"></i>Accept Offer
                                    </button>
                                </form>
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" 
                                        onclick="return confirm('Are you sure you want to decline this job offer?');"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition shadow-lg hover:shadow-xl">
                                        <i class="fas fa-times-circle mr-2"></i>Decline Offer
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($applicant['offer_status'] === 'Accepted'): ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-green-800">
                                    <i class="fas fa-check-circle text-2xl"></i>
                                    <div>
                                        <h3 class="font-semibold">Offer Accepted</h3>
                                        <p class="text-sm">Accepted on <?php echo $applicant['offer_acceptance_timestamp'] ? date('F d, Y \a\t h:i A', strtotime($applicant['offer_acceptance_timestamp'])) : 'N/A'; ?></p>
                                        <p class="text-sm mt-1">Our HR team will contact you soon.</p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($applicant['offer_status'] === 'Declined'): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-yellow-800">
                                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                                    <div>
                                        <h3 class="font-semibold">Offer Declined</h3>
                                        <p class="text-sm">Declined on <?php echo $applicant['offer_declined_at'] ? date('F d, Y \a\t h:i A', strtotime($applicant['offer_declined_at'])) : 'N/A'; ?></p>
                                        <p class="text-sm mt-1">Thank you for your interest.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">Unable to load offer details. Please contact HR.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="text-center mt-4 text-gray-600 text-sm">
                <p>This is a secure job offer portal. Do not share this link with others.</p>
            </div>
        </div>
    </div>
</body>
</html>