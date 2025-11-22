<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title'] ?? 'Evergreen'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #003631;">
    <div class="container-fluid px-4">

        <!--------------------------- LOGO --------------------------------------------------------------------------------------------->
        <a class="navbar-brand d-flex align-items-center" href=<?= URLROOT .'/customer/account'; ?>>
        <img src= <?= URLROOT . "/img/logo.png";?> alt="Evergreen Logo" width="40" height="40" class="me-2">
        <span class="fw-bold">EVERGREEN</span>
        </a>

        <!--------------------------- TOGGLER FOR PHONES ------------------------------------------------------------------------------->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
        </button>

        <!--------------------------- BUTTONS FOR OTHER PAGES -------------------------------------------------------------------------->
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if((isset($_SESSION['customer_id']))): ?>
            <ul class="navbar-nav mx-auto">
                <li class="nav-item me-2">
                    <a class="nav-link" href="/Evergreen/bank-system/evergreen-marketing/viewingpage.php">Home</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link <?= (!empty($data['title']) && $data['title'] == 'Accounts') ? 'active text-warning' : ''; ?>" 
                    href="<?= URLROOT ?>/customer/account">Accounts</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link <?= (!empty($data['title']) && $data['title'] == 'Fund Transfer') ? 'active text-warning' : ''; ?>" 
                    href="<?= URLROOT ?>/customer/fund_transfer">Fund Transfer</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link <?= (!empty($data['title']) && $data['title'] == 'Transaction History') ? 'active text-warning' : ''; ?>" 
                    href="<?= URLROOT ?>/customer/transaction_history">Transaction History</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link <?= (!empty($data['title']) && $data['title'] == 'Referral') ? 'active text-warning' : ''; ?>" 
                    href="<?= URLROOT ?>/customer/referral">Referral</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link <?= (!empty($data['title']) && $data['title'] == 'Open New Account') ? 'active text-warning' : ''; ?>" 
                    href="<?= URLROOT ?>/customer/create_account">Create Account</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link <?= (!empty($data['title']) && $data['title'] == 'Pay Loan') ? 'active text-warning' : ''; ?>" 
                    href="<?= URLROOT ?>/customer/pay_loan">Pay Loan</a>
                </li>
            </ul>

        <!------------------------- USERNAME AND PROFILE ----------------------------------------------------------------------------->
        <div class="d-flex align-items-center dropdown">
            <span class="text-white me-1">Logged in as:</span>
            <button class="btn btn-sm text-white dropdown-toggle focus-ring-0" 
                    type="button" 
                    id="userDropdownMenu" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false"
                    style="background: none; border: none; box-shadow: none; text-decoration: underline;">
                <?= strtoupper($_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name'])?>
            </button>
            
            <!-- The Dropdown Menu -->
            <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="userDropdownMenu">
                <h6 class="dropdown-header">Account Overview</h6>
                <li><a class="dropdown-item" href="<?= URLROOT . '/customer/profile' ;?>">
                    <i class="bi bi-person-circle me-2"></i> View Profile
                </a></li>
                <li><a class="dropdown-item" href="<?= URLROOT . '/customer/change_password' ;?>">
                    <i class="bi bi-gear-fill me-2"></i> Settings
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= URLROOT . '/auth/logout'; ?>">
                    <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                </a></li>
            </ul>
        </div>
        </div>

        <?php else: ?>
        <ul class="navbar-nav mx-auto">
            <li class="nav-item me-2"><a class="nav-link" href="#">Home</a></li>
            <li class="nav-item mx-2"><a class="nav-link" href="#">Accounts</a></li>
            <li class="nav-item mx-2"><a class="nav-link" href="#">Fund Transfer</a></li>
            <li class="nav-item mx-2"><a class="nav-link" href="#">Transaction History</a></li>
            <li class="nav-item mx-2"><a class="nav-link" href="#">Referral</a></li>
        </ul>
    <?php endif;?>
    </div>
    </nav>