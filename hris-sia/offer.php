<?php
session_start();

require_once 'config/database.php';

$token = $_GET['token'] ?? '';
$message = '';
$messageType = '';
$applicant = null;
$offer_accepted = false;
$offer_declined = false;

if (empty($token)) {
    $message = "Invalid offer link. No token provided.";
    $messageType = "error";
} else {
    // Fetch applicant by offer token
    $applicant = fetchOne($conn, 
        "SELECT a.*, r.job_title, r.description, d.department_name
         FROM applicant a
         LEFT JOIN recruitment r ON a.recruitment_id = r.recruitment_id
         LEFT JOIN department d ON r.department_id = d.department_id
         WHERE a.offer_token = ?",
        [$token]
    );

    if (!$applicant) {
        $message = "Invalid offer link. Offer not found.";
        $messageType = "error";
    } else {
        // Check if already accepted or declined
        if ($applicant['offer_status'] === 'Accepted') {
            $offer_accepted = true;
            $message = "This job offer has already been accepted.";
            $messageType = "success";
        } elseif ($applicant['offer_status'] === 'Declined') {
            $offer_declined = true;
            $message = "This job offer has been declined.";
            $messageType = "warning";
        }

        // Handle offer acceptance/decline
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$offer_accepted && !$offer_declined) {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'accept') {
                try {
                    $sql = "UPDATE applicant 
                            SET offer_status = 'Accepted',
                                offer_acceptance_timestamp = NOW()
                            WHERE offer_token = ? AND offer_status = 'Pending'";
                    
                    $stmt = $conn->prepare($sql);
                    $success = $stmt->execute([$token]);
                    
                    if ($success && $stmt->rowCount() > 0) {
                        $offer_accepted = true;
                        $message = "Job offer accepted successfully! HR will contact you soon to finalize the hiring process.";
                        $messageType = "success";
                        
                        // Refresh applicant data
                        $applicant = fetchOne($conn, 
                            "SELECT a.*, r.job_title, r.description, d.department_name
                             FROM applicant a
                             LEFT JOIN recruitment r ON a.recruitment_id = r.recruitment_id
                             LEFT JOIN department d ON r.department_id = d.department_id
                             WHERE a.offer_token = ?",
                            [$token]
                        );
                    } else {
                        throw new Exception("Failed to accept offer. It may have already been processed.");
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $messageType = "error";
                }
            } elseif ($action === 'decline') {
                try {
                    $sql = "UPDATE applicant 
                            SET offer_status = 'Declined',
                                offer_declined_at = NOW()
                            WHERE offer_token = ? AND offer_status = 'Pending'";
                    
                    $stmt = $conn->prepare($sql);
                    $success = $stmt->execute([$token]);
                    
                    if ($success && $stmt->rowCount() > 0) {
                        $offer_declined = true;
                        $message = "Job offer declined. Thank you for your interest.";
                        $messageType = "warning";
                        
                        // Refresh applicant data
                        $applicant = fetchOne($conn, 
                            "SELECT a.*, r.job_title, r.description, d.department_name
                             FROM applicant a
                             LEFT JOIN recruitment r ON a.recruitment_id = r.recruitment_id
                             LEFT JOIN department d ON r.department_id = d.department_id
                             WHERE a.offer_token = ?",
                            [$token]
                        );
                    } else {
                        throw new Exception("Failed to decline offer. It may have already been processed.");
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $messageType = "error";
                }
            }
        }
    }
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

        .card-enhanced {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
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
            <div class="card-enhanced p-6 lg:p-8">
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg <?php 
                        echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 
                            ($messageType === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                    ?>">
                        <div class="flex items-center gap-2">
                            <?php if ($messageType === 'success'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php elseif ($messageType === 'warning'): ?>
                                <i class="fas fa-exclamation-triangle"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i>
                            <?php endif; ?>
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

                        <?php if ($applicant['description']): ?>
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2">Job Description</h3>
                                <p class="text-gray-700 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars($applicant['description'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <?php if (!$offer_accepted && !$offer_declined && $applicant['offer_status'] === 'Pending'): ?>
                            <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t">
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" 
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-lg hover:shadow-xl">
                                        <i class="fas fa-check-circle mr-2"></i>Accept Offer
                                    </button>
                                </form>
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" 
                                        onclick="return confirm('Are you sure you want to decline this job offer?');"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-lg hover:shadow-xl">
                                        <i class="fas fa-times-circle mr-2"></i>Decline Offer
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($offer_accepted): ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-green-800">
                                    <i class="fas fa-check-circle text-2xl"></i>
                                    <div>
                                        <h3 class="font-semibold">Offer Accepted</h3>
                                        <p class="text-sm">Accepted on <?php echo $applicant['offer_acceptance_timestamp'] ? date('F d, Y \a\t h:i A', strtotime($applicant['offer_acceptance_timestamp'])) : 'N/A'; ?></p>
                                        <p class="text-sm mt-1">Our HR team will contact you soon to finalize the hiring process.</p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($offer_declined): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-yellow-800">
                                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                                    <div>
                                        <h3 class="font-semibold">Offer Declined</h3>
                                        <p class="text-sm">Declined on <?php echo $applicant['offer_declined_at'] ? date('F d, Y \a\t h:i A', strtotime($applicant['offer_declined_at'])) : 'N/A'; ?></p>
                                        <p class="text-sm mt-1">Thank you for your interest. We wish you the best in your career.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">Unable to load offer details. Please contact HR if you believe this is an error.</p>
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

