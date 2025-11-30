<?php

class CustomerController extends Controller {
    private $customerModel;

   public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Sync session from evergreen-marketing if logged in there
        // evergreen-marketing uses 'user_id' (which is customer_id) and 'first_name', 'last_name'
        // Basic-operation uses 'customer_id' and 'customer_first_name', 'customer_last_name'
        if (!isset($_SESSION['customer_id']) && isset($_SESSION['user_id'])) {
            // User is logged in via evergreen-marketing, sync session for Basic-operation
            $_SESSION['customer_id'] = $_SESSION['user_id'];
            
            // Sync name fields from evergreen-marketing session
            if (!isset($_SESSION['customer_first_name']) && isset($_SESSION['first_name'])) {
                $_SESSION['customer_first_name'] = $_SESSION['first_name'];
            }
            if (!isset($_SESSION['customer_last_name']) && isset($_SESSION['last_name'])) {
                $_SESSION['customer_last_name'] = $_SESSION['last_name'];
            }
        } elseif (isset($_SESSION['customer_id']) && !isset($_SESSION['customer_first_name'])) {
            // If customer_id exists but names are missing, try to get from evergreen-marketing session
            if (isset($_SESSION['first_name'])) {
                $_SESSION['customer_first_name'] = $_SESSION['first_name'];
            }
            if (isset($_SESSION['last_name'])) {
                $_SESSION['customer_last_name'] = $_SESSION['last_name'];
            }
        }

        // Redirect to login if not logged in
        if (!isset($_SESSION['customer_id'])) {
            header('Location: /Evergreen/bank-system/evergreen-marketing/login.php');
            exit();
        }

        parent::__construct();
        $this->customerModel = $this->model('Customer');
    }

    // --- ACCOUNT ---

    public function account(){

        $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

        $data = [
            'title' => "Accounts",
            'first_name' => $_SESSION['customer_first_name'],
            'last_name'  => $_SESSION['customer_last_name'],
            'accounts' => $accounts
        ];
        $this->view('customer/account', $data);
    }

    // --- CHANGE PASSWORD ---

    public function change_password(){

        // Initial data load for the view
        $data = [
            'title' => "Change Password",
            'first_name' => $_SESSION['customer_first_name'],
            'last_name' => $_SESSION['customer_last_name'],
            'old_password' => '',
            'new_password' => '',
            'confirm_password' => '',
            'old_password_err' => '',
            'new_password_err' => '',
            'confirm_password_err' => '',
            'success_message' => '',
            'error_message' => '' // Ensure error_message is initialized
        ];

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'title' => "Change Password",
                'user_id' => $_SESSION['customer_id'],
                'old_password' => trim($_POST['old_password']),
                'new_password' => trim($_POST['new_password']),
                'confirm_password' => trim($_POST['confirm_password']),
                'old_password_err' => '',
                'new_password_err' => '',
                'confirm_password_err' => '',
                'success_message' => '',
                'error_message' => ''
            ];

            if(empty($data['old_password'])){
                $data['old_password_err'] = 'Please enter your current password.';
            } else {
                $current_hash = $this->customerModel->getCurrentPasswordHash($data['user_id']);
                
                if(!$current_hash || !password_verify($data['old_password'], $current_hash)){
                    $data['old_password_err'] = 'Incorrect current password.';
                }
            }

            if(empty($data['new_password'])){
                $data['new_password_err'] = 'Please enter a new password.';
            } elseif(strlen($data['new_password']) < 10){
                $data['new_password_err'] = 'Password must be at least 10 characters long.'; 
            }

            if(empty($data['confirm_password'])){
                $data['confirm_password_err'] = 'Please confirm the new password.';
            } elseif($data['new_password'] != $data['confirm_password']){
                $data['confirm_password_err'] = 'New passwords do not match.';
            }
            
            if (empty($data['old_password_err']) && $data['old_password'] === trim($_POST['new_password'])) {
                $data['new_password_err'] = 'New password cannot be the same as the current password.';
            }

            if(empty($data['old_password_err']) && empty($data['new_password_err']) && empty($data['confirm_password_err'])){
                $data['new_password'] = password_hash($data['new_password'], PASSWORD_DEFAULT);

                if($this->customerModel->updatePassword($data['user_id'], $data['new_password'])){
                    $data['old_password'] = $data['new_password'] = $data['confirm_password'] = '';
                    $data['success_message'] = 'Your password has been successfully updated!';
                } else {
                    $data['error_message'] = 'Something went wrong. Please try again.';
                }
            }
        }
        
        // The existing view call assumes $this is a controller object
        $this->view('customer/change_password', $data);
    }

    public function removeAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $account_id = trim($_POST['account_id']);

            $data = [
                'customer_id'      => $_SESSION['customer_id'],
                'account_id'       => $account_id,
                'account_id_error' => '',
                'success_message'  => '',
            ];

            // Validate
            if (empty($account_id)) {
                $data['account_id_error'] = 'Please enter your account ID.';
            } else {
                // Check if account exists and belongs to the logged-in customer
                $account = $this->customerModel->getAccountById($account_id);

                if (!$account) {
                    $data['account_id_error'] = 'Account not found.';
                } elseif ($account->customer_id != $_SESSION['customer_id']) {
                    $data['account_id_error'] = 'You do not have permission to remove this account.';
                }
            }

            // If validation passes
            if (empty($data['account_id_error'])) {
                if ($this->customerModel->deleteAccountById($account_id)) {
                    $_SESSION['flash_success'] = 'Account removed successfully.';
                    header('Location: ' . URLROOT . '/customer/account');
                    exit();
                } else {
                    $data['account_id_error'] = 'Something went wrong while deleting the account.';
                }
            }

            // Get updated account list for view
            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = array_merge($data, [
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts
            ]);

            $this->view('customer/account', $data);

        } else {
            // Default data when page is first loaded
            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'account_id' => '',
                'account_id_error' => '',
                'success_message' => '',
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts
            ];

            $this->view('customer/account', $data);
        }
    }

    public function addAccount(){

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Get the user inputs
            $account_number = trim($_POST['account_number']);
            $account_type   = trim($_POST['account_type']);

            $data = [
                'customer_id'    => $_SESSION['customer_id'],
                'account_number' => $account_number,
                'account_type'   => $account_type,
                'account_number_error' => '',
                'account_type_error'   => '',
                'success_message'      => '',
            ];
            if (empty($account_number)) {
                $data['account_number_error'] = 'Please enter your account number.';
            }

            if (empty($account_type)) {
                $data['account_type_error'] = 'Please select your account type.';
            }

            // If no local errors, call the model
            if (empty($data['account_number_error']) && empty($data['account_type_error'])) {
                $result = $this->customerModel->addAccount($data);

                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                    header('Location: ' . URLROOT . '/customer/account');
                    exit;
                } else {
                    $data['account_number_error'] = $result['error'];
                }
            }

            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = array_merge($data, [
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts,
                'show_add_account_modal' => true  // Flag to auto-show the modal
            ]);

            $this->view('customer/account', $data);

        } else {
            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = [
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'account_number' => '',
                'account_type'   => '',
                'account_number_error' => '',
                'account_type_error'   => '',
                'success_message'      => '',
                'accounts' => $accounts,
                'show_add_account_modal' => false
            ];

            $this->view('customer/account', $data);
        }
    }

    // --- PROFILE ---
    public function profile(){
        $customer_id = $_SESSION['customer_id'];
        
        // Handle POST request for profile update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $update_data = [];
            
            // Only allow updating specific fields (not name or birthday)
            if (isset($_POST['email_address'])) {
                $update_data['email_address'] = trim($_POST['email_address']);
            }
            if (isset($_POST['mobile_number'])) {
                $update_data['mobile_number'] = trim($_POST['mobile_number']);
            }
            if (isset($_POST['home_address'])) {
                $update_data['home_address'] = trim($_POST['home_address']);
            }
            if (isset($_POST['address_line'])) {
                $update_data['address_line'] = trim($_POST['address_line']);
            }
            if (isset($_POST['city'])) {
                $update_data['city'] = trim($_POST['city']);
            }
            if (isset($_POST['province_id'])) {
                $update_data['province_id'] = trim($_POST['province_id']);
            }
            if (isset($_POST['city_id'])) {
                $update_data['city_id'] = trim($_POST['city_id']);
            }
            if (isset($_POST['barangay_id'])) {
                $update_data['barangay_id'] = trim($_POST['barangay_id']);
            }
            if (isset($_POST['gender'])) {
                $update_data['gender'] = trim($_POST['gender']);
            }
            if (isset($_POST['civil_status'])) {
                $update_data['civil_status'] = trim($_POST['civil_status']);
            }
            if (isset($_POST['citizenship'])) {
                $update_data['citizenship'] = trim($_POST['citizenship']);
            }
            if (isset($_POST['occupation'])) {
                $update_data['occupation'] = trim($_POST['occupation']);
            }
            if (isset($_POST['name_of_employer'])) {
                $update_data['name_of_employer'] = trim($_POST['name_of_employer']);
            }
            
            // Validate email if provided
            if (!empty($update_data['email_address']) && !filter_var($update_data['email_address'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['profile_error'] = 'Invalid email address format.';
            } else {
                // Check if there's any data to update
                if (!empty($update_data)) {
                    // Update profile
                    $updated = $this->customerModel->updateCustomerProfile($customer_id, $update_data);
                    
                    if ($updated) {
                        $_SESSION['profile_success'] = 'Profile updated successfully!';
                    } else {
                        $_SESSION['profile_error'] = 'Failed to update profile. Please check your input and try again.';
                        error_log("Profile update failed for customer_id: $customer_id");
                    }
                } else {
                    $_SESSION['profile_error'] = 'No data provided to update.';
                }
            }
            
            // Redirect to refresh the page
            header('Location: ' . URLROOT . '/customer/profile');
            exit();
        }

        $profile_data = $this->customerModel->getCustomerProfileData($customer_id);
        $provinces = $this->customerModel->getProvinces();
        $cities = $this->customerModel->getAllCities();
        
        // Get barangays - load ALL barangays for dynamic filtering on the frontend
        // Also get barangays for current city if available
        $barangays = $this->customerModel->getAllBarangays();
        $current_barangays = [];
        if (!empty($profile_data->city_id)) {
            $current_barangays = $this->customerModel->getBarangaysByCity($profile_data->city_id);
        }

        if (!$profile_data) {
             $profile_data = (object)[
                 'first_name' => 'N/A', 'last_name' => 'N/A', 'username' => 'N/A', 
                 'mobile_number' => 'N/A', 'email_address' => 'N/A', 'home_address' => 'N/A',
                 'address_line' => '', 'city' => '', 'province_id' => null, 'province_name' => '',
                 'city_id' => null, 'barangay_id' => null, 'barangay_name' => '',
                 'date_of_birth' => 'N/A', 'gender' => 'N/A', 'civil_status' => 'N/A', 
                 'citizenship' => 'N/A', 'occupation' => 'N/A', 'name_of_employer' => 'N/A'
             ];
        }

        $data = [
            'title' => "My Profile",
            'profile' => $profile_data,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'full_name' => trim($profile_data->first_name . ' ' . $profile_data->middle_name . ' ' . $profile_data->last_name),
            'source_of_funds' => $profile_data->occupation,
            'employment_status' => $profile_data->occupation ? 'Employed' : 'Unemployed',
            'place_of_birth' => 'Quezon City',
            'employer_address' => '123 Bldg, Metro Manila',
            'success_message' => $_SESSION['profile_success'] ?? '',
            'error_message' => $_SESSION['profile_error'] ?? '',
        ];
        
        // Clear session messages
        unset($_SESSION['profile_success']);
        unset($_SESSION['profile_error']);

        $this->view('customer/profile', $data);
    }


    // --- FUND TRANSFER ---

    public function fund_transfer(){

        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            
            $from_account = trim($_POST['from_account']);
            $recipient_number = trim($_POST['recipient_number']);
            $recipient_name = trim($_POST['recipient_name']);
            $amount = (float) trim($_POST['amount']);
            $message = trim($_POST['message']);

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'from_account' => $from_account,
                'recipient_number' => $recipient_number,
                'recipient_name' => $recipient_name,
                'amount' => $amount,
                'message' => $message,
                'from_account_error' => '',
                'recipient_number_error' => '',
                'recipient_name_error' => '',
                'amount_error' => '',
                'message_error' => '',
                'other_error' => '',
            ];

            if (empty($from_account)){
                $data['from_account_error'] = 'Please select your account number.';
            }

            $sender = $this->customerModel->getAccountByNumber($data['from_account']);

            if(!$sender){
                $data['from_account_error'] = 'Please select your own account number.';
            }

            if(empty($recipient_number)){
                $data['recipient_number_error'] = 'Please enter recipient account number.';
            }

            $recipient_validation = $this->customerModel->validateRecipient($data['recipient_number'], $data['recipient_name']);

            if(!$recipient_validation['status']){
                $data = array_merge($data, [
                    'recipient_number_error' => 'Invalid recipient account number or account name',
                    'recipient_name_error' => 'Invalid recipient account number or account name'
                ]);
            }

            $receiver = $this->customerModel->getAccountByNumber($data['recipient_number']);

            if(empty($recipient_name)){
                $data['recipient_name_error'] = 'Please enter recipient name.';
            }

            if(empty($amount)){
                $data['amount_error'] = 'Please enter an amount.';
            }
            $amount_validation = $this->customerModel->validateAmount($data['from_account']);
            $fee = 15.00;
            $total = $data['amount'] + $fee;

            if((float)$amount_validation->balance < $total){
                $data['amount_error'] = 'Insufficient Funds';
            }

            // Enforce maintaining balance confirmation on final submit as well
            $senderAccount = $this->customerModel->getAccountByNumber($data['from_account']);
            $maintaining_required = isset($senderAccount->maintaining_balance_required) ? (float)$senderAccount->maintaining_balance_required : 500.00;
            $remaining_after = (float)$amount_validation->balance - $total;
            if ($remaining_after < $maintaining_required && $remaining_after >= 0) {
                if (empty($_POST['confirm_low_balance'])) {
                    $data['other_error'] = 'Transfer requires confirmation: resulting balance will be below PHP ' . number_format($maintaining_required,2) . '.';
                }
            }

            // Check maintaining balance rule and require confirmation flag if this transfer will leave balance below minimum
            $senderAccount = $this->customerModel->getAccountByNumber($data['from_account']);
            $maintaining_required = isset($senderAccount->maintaining_balance_required) ? (float)$senderAccount->maintaining_balance_required : 500.00;
            $remaining_after = (float)$amount_validation->balance - $total;
            if ($remaining_after < $maintaining_required && $remaining_after >= 0) {
                // if confirm flag not present, set a flag so view can prompt/require confirmation
                if (empty($_POST['confirm_low_balance'])) {
                    $data['other_error'] = 'This transfer will bring your balance below the required maintaining balance of PHP ' . number_format($maintaining_required,2) . '. Please confirm to proceed.';
                }
            }

            if(strlen($message) >= 100){
                $data['message_error'] = 'Pleaser enter 100 characters only';
            }

            if($data['from_account'] == $data['recipient_number']){
                $data['other_error'] = 'You cannot transfer money to the same account fool.';
            }

            if(empty($data['from_account_error']) && empty($data['recipient_number_error']) && empty($data['recipient_name_error']) && empty($data['amount_error']) && empty($data['message_error']) && empty($data['other_error'])){
                $temp_transaction_ref = 'TXN-PREVIEW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $remaining_balance = (float)$amount_validation->balance - $total;
                $sender_name = $_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name'] ?? 'Sender Name Unknown';

                $data = array_merge($data, [
                    'temp_transaction_ref' => $temp_transaction_ref,
                    'fee' => $fee,
                    'total_payment' => $total,
                    'remaining_balance' => $remaining_balance,
                    'sender_name' => $sender_name,
                ]);

                // If remaining is below maintaining and confirmation not provided, re-render transfer page with warning
                if ($remaining_balance < $maintaining_required && empty($_POST['confirm_low_balance'])) {
                    $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
                    $data = array_merge($data, [
                        'title' => 'Fund Transfer',
                        'accounts' => $accounts,
                        'low_balance_confirm_required' => true,
                        'maintaining_required' => $maintaining_required
                    ]);
                    $this->view('customer/fund_transfer', $data);
                } else {
                    $this->view('customer/receipt', $data);
                }
            } else {
                $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
                $data = array_merge($data, [
                    'title' => 'Fund Transfer',
                    'accounts' => $accounts
                ]);
                $this->view('customer/fund_transfer', $data);
            }
        } else {
             $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
             $data = [
                'title' => 'Fund Transfer',
                'accounts' => $accounts
            ];
            $this->view('customer/fund_transfer', $data);
        }
    }

    public function receipt(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
         $from_account = trim($_POST['from_account']);
            $recipient_number = trim($_POST['recipient_number']);
            $recipient_name = trim($_POST['recipient_name']);
            $amount = (float) trim($_POST['amount']);
            $message = trim($_POST['message']);

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'from_account' => $from_account,
                'recipient_number' => $recipient_number,
                'recipient_name' => $recipient_name,
                'amount' => $amount,
                'message' => $message,
                'from_account_error' => '',
                'recipient_number_error' => '',
                'recipient_name_error' => '',
                'amount_error' => '',
                'message_error' => '',
                'other_error' => '',
            ];

            if (empty($from_account)){
                $data['from_account_error'] = 'Please select your account number.';
            }

            $sender = $this->customerModel->getAccountByNumber($data['from_account']);

            if(!$sender){
                $data['from_account_error'] = 'Please select your own account number.';
            }

            if(empty($recipient_number)){
                $data['recipient_number_error'] = 'Please enter recipient account number.';
            }

            $recipient_validation = $this->customerModel->validateRecipient($data['recipient_number'], $data['recipient_name']);

            if(!$recipient_validation['status']){
                $data = array_merge($data, [
                    'recipient_number_error' => 'Invalid recipient account number or account name',
                    'recipient_name_error' => 'Invalid recipient account number or account name'
                ]);
            }

            $receiver = $this->customerModel->getAccountByNumber($data['recipient_number']);

            if(empty($recipient_name)){
                $data['recipient_name_error'] = 'Please enter recipient name.';
            }

            if(empty($amount)){
                $data['amount_error'] = 'Please enter an amount.';
            }
            $amount_validation = $this->customerModel->validateAmount($data['from_account']);
            $fee = 15.00;
            $total = $data['amount'] + $fee;

            if((float)$amount_validation->balance < $total){
                $data['amount_error'] = 'Insufficient Funds';
            }

            if(strlen($message) >= 100){
                $data['message_error'] = 'Pleaser enter 100 characters only';
            }

            if($data['from_account'] == $data['recipient_number']){
                $data['other_error'] = 'You cannot transfer money to the same account fool.';
            }
            $message = 'Sent to ' . $data['recipient_name'] . ' (' . $data['recipient_number'] . ')';
            if (!empty($data['message'])) {
                $message .= ' - ' . $data['message'];
            }

            if(empty($data['from_account_error']) && empty($data['recipient_number_error']) && empty($data['recipient_name_error']) && empty($data['amount_error']) && empty($data['message_error']) && empty($data['other_error'])){
                $transaction_ref = 'TXN-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));

                $result = $this->customerModel->recordTransaction($transaction_ref, $sender->account_id, $receiver->account_id, $data['amount'], $fee, $message);

                header('Location: ' . URLROOT . '/customer/dashboard');
                exit();
            } else {
                $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
                $data = array_merge($data, [
                    'title' => 'Fund Transfer',
                    'accounts' => $accounts
                ]);
                $this->view('customer/fund_transfer', $data);
            }
        } else {
             $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
             $data = [
                'title' => 'Fund Transfer',
                'accounts' => $accounts
            ];
            $this->view('customer/fund_transfer', $data);
        }
    }

    public function transaction_history() {
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }

        $customer_id = $_SESSION['customer_id'];
        $limit = 20;

        $current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $current_page = max(1, $current_page);
        $offset = ($current_page - 1) * $limit;

        $filters = [
            'account_id' => isset($_GET['account_id']) ? filter_var($_GET['account_id'], FILTER_SANITIZE_STRING) : 'all',
            'type_name' => isset($_GET['type_name']) ? filter_var($_GET['type_name'], FILTER_SANITIZE_STRING) : 'All',
            'start_date' => isset($_GET['start_date']) ? filter_var($_GET['start_date'], FILTER_SANITIZE_STRING) : '',
            'end_date' => isset($_GET['end_date']) ? filter_var($_GET['end_date'], FILTER_SANITIZE_STRING) : '',
        ];

        $accounts = $this->customerModel->getLinkedAccountsForFilter($customer_id);
        $rawTransactionTypes = $this->customerModel->getTransactionTypes();
        $transactionTypes = array_merge(['All'], array_column($rawTransactionTypes, 'type_name'));

        $transactionData = $this->customerModel->getAllTransactionsByCustomerId(
            $customer_id, 
            $filters,
            $limit, 
            $offset
        );

        $total_transactions = $transactionData['total'];
        $total_pages = ceil($total_transactions / $limit);

        $data = [
            'title' => 'Transaction History',
            'accounts' => $accounts,
            'transactions' => $transactionData['bank_transactions'],
            'filters' => $filters,
            'transaction_types' => $transactionTypes,
            'pagination' => [
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'total_transactions' => $total_transactions,
                'limit' => $limit,
                'url_query' => http_build_query(array_filter($_GET, fn($key) => $key !== 'page', ARRAY_FILTER_USE_KEY))
            ]
        ];

        $this->view('customer/transaction_history', $data);
    }

    // -- FOR EXPORT --
    public function export_transactions() {
        // Clear any output buffers to prevent header issues
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }
        $customer_id = $_SESSION['customer_id'];

        // 1. Get the filter data (account_id, type_name, start_date, end_date)
        $filters = [
            'account_id' => $_GET['account_id'] ?? 'all',
            'type_name'  => $_GET['type_name'] ?? 'All',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date'   => $_GET['end_date'] ?? '',
        ];
        $exportType = strtolower($_GET['type'] ?? 'csv');

        try {
            // 2. Call a model method to fetch ALL transactions matching the filters
            // Pass customer_id and filters
            $transactions = $this->customerModel->getAllFilteredTransactions($customer_id, $filters); 

            // 3. Generate and output the file based on $exportType
            if ($exportType === 'csv') {
                $this->generateCSV($transactions);
            } elseif ($exportType === 'pdf') {
                // You would need a PDF library integrated for this (TCPDF seems to be set up)
                $this->generatePDF($transactions);
            } else {
                // Handle invalid type
                $_SESSION['export_error'] = 'Invalid export type. Please select CSV or PDF.';
                header('Location: ' . URLROOT . '/customer/transaction_history');
                exit;
            }
        } catch (Exception $e) {
            // Log error and redirect with error message
            error_log("Export Error: " . $e->getMessage());
            $_SESSION['export_error'] = 'Failed to export transactions. Please try again.';
            header('Location: ' . URLROOT . '/customer/transaction_history');
            exit;
        }
    }
    
    protected function generateCSV($transactions) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for download
        $filename = 'transactions_' . date('Ymd_His') . '.csv';
        
        // Set headers before any output
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Pragma: no-cache');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Define CSV Column Headers
        $headers = ['Date', 'Time', 'Description', 'Reference', 'Account Number', 'Type', 'Amount (PHP)'];
        fputcsv($output, $headers);

        // Write transaction data
        foreach ($transactions as $t) {
            $dateTime = strtotime($t->created_at);
            $amountSign = $t->signed_amount < 0 ? '-' : '+';

            $row = [
                date('Y-m-d', $dateTime),
                date('h:i:s A', $dateTime),
                $t->description,
                $t->transaction_ref,
                $t->account_number,
                $t->transaction_type,
                $amountSign . number_format(abs($t->signed_amount), 2, '.', ''), // Use plain format for export
            ];
            fputcsv($output, $row);
        }

        // Close the stream and terminate script
        fclose($output);
        exit;
    }

    protected function generatePDF($transactions) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        require_once ROOT_PATH . '/vendor/autoload.php';

        // --- Compute Date Range ---
        if (!empty($transactions)) {
            $dates = array_map(fn($t) => strtotime($t->created_at), $transactions);
            $startDate = min($dates);
            $endDate = max($dates);
            $dateRange = date('j M Y', $startDate) . ' - ' . date('j M Y', $endDate);
        } else {
            $dateRange = "No transactions";
        }

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Bank');
        $pdf->SetTitle('Statement of Account');
        $pdf->SetSubject('Customer Statement');

        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        $logo = ROOT_PATH . '/public/img/logo.jpg';
        $customer_name = $_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name'];

        $headerHTML = '
            <table width="100%">
                <tr>
                    <!-- Logo -->
                    <td width="50%">
                        <img src="' . $logo . '" height="40" />
                        <span style="font-size:16px; font-weight:bold;">EVERGREEN</span>
                    </td>

                    <!-- Title + Statement Date -->
                    <td width="50%" align="right" style="text-align:right;">
                        <span style="font-size:16px; font-weight:bold;">Statement of Account</span><br>
                        <span style="font-size:10px;">Statement date: ' . date('j F Y') . '</span>
                    </td>
                </tr>
            </table>
            <br><hr><br>
        ';

        $pdf->writeHTML($headerHTML, true, false, true, false, '');

        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(40, 6, 'Customer Name:', 0, 0, 'L');  
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, htmlspecialchars($customer_name), 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, "Transactions (" . $dateRange . ")", 0, 1, 'L');
        $pdf->Ln(3);
        $html = '<table cellspacing="0" cellpadding="5" border="1" style="border-collapse: collapse;">';

        $html .= '
            <tr style="background-color:#f0f0f0; font-weight:bold;">
                <td width="25%">Date & Time</td>
                <td width="35%">Description</td>
                <td width="20%">Account</td>
                <td width="20%" align="right">Amount(PHP)</td>
            </tr>
        ';

        if (empty($transactions)) {
            $html .= '<tr><td colspan="5" align="center">No transactions found.</td></tr>';
        } else {
            foreach ($transactions as $t) {
                $isDebit = $t->signed_amount < 0;
                $formattedAmount = number_format(abs($t->signed_amount), 2);
                $color = $isDebit ? '#D9534F' : '#5CB85C';

                $html .= '
                    <tr>
                        <td>' . date('d M Y, h:i A', strtotime($t->created_at)) . '</td>
                        <td>' . htmlspecialchars($t->description) . '<br>
                            <span style="font-size:8pt;">Ref: ' . htmlspecialchars($t->transaction_ref) . '</span>
                        </td>
                        <td>' . htmlspecialchars($t->account_number) . '<br>
                            <span style="font-size:8pt;">' . htmlspecialchars($t->transaction_type) . '</span>
                        </td>
                        <td align="right" style="font-weight:bold; color:' . $color . ';">' 
                            . ($isDebit ? '-' : '+') . $formattedAmount . '</td>
                    </tr>
                ';
            }
        }

        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Generate filename with timestamp
        $filename = 'statement_' . date('Ymd_His') . '.pdf';
        
        // Output PDF with 'D' flag to force download
        // 'D' = send to browser and force download with the given name
        $pdf->Output($filename, 'D');
        exit;
    }


    public function referral(){
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }

        $customerId = $_SESSION['customer_id'];
        
        // Get referral code and stats
        $referralData = $this->customerModel->getReferralCode($customerId);
        $referralStats = $this->customerModel->getReferralStats($customerId);
        
        $data = [
            'title' => 'Referral',
            'first_name' => $_SESSION['customer_first_name'],
            'last_name' => $_SESSION['customer_last_name'],
            'referral_code' => $referralData ? $referralData->referral_code : 'Not Available',
            'total_points' => $referralStats['total_points'],
            'referral_count' => $referralStats['referral_count'],
            'friend_code' => '',
            'friend_code_error' => '',
            'success_message' => '',
            'error_message' => ''
        ];

        // Handle POST request (apply referral code)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $friendCode = trim($_POST['friend_code'] ?? '');
            $data['friend_code'] = $friendCode;

            if (empty($friendCode)) {
                $data['friend_code_error'] = 'Please enter a referral code';
            } else {
                $result = $this->customerModel->applyReferralCode($customerId, $friendCode);
                
                if ($result['success']) {
                    $data['success_message'] = $result['message'];
                    $data['friend_code'] = ''; // Clear the input
                    
                    // Refresh stats after successful referral
                    $referralStats = $this->customerModel->getReferralStats($customerId);
                    $data['total_points'] = $referralStats['total_points'];
                    $data['referral_count'] = $referralStats['referral_count'];
                } else {
                    $data['error_message'] = $result['message'];
                }
            }
        }

        $this->view('customer/referral', $data);
    }

    public function signup(){
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }

        $data = [
            'title' => 'Sign Up'
        ];

        $this->view('customer/signup', $data);
    }

    // -- LOANS --
    public function pay_loan()
    {
        $customerId = $_SESSION['customer_id'];
        $activeLoans = $this->customerModel->getActiveLoanApplications($customerId); // Fetching applications now
        $primaryAccount = $this->customerModel->getPrimaryAccountNumber($customerId);
        
        // Get account balance for the primary account
        $accountBalance = 0.00;
        if ($primaryAccount) {
            $balanceData = $this->customerModel->validateAmount($primaryAccount);
            $accountBalance = $balanceData ? (float)$balanceData->balance : 0.00;
        }

        $data = [
            'title' => "Pay Loan",
            'first_name' => $_SESSION['customer_first_name'] ?? 'Customer',
            'active_loans' => $activeLoans,
            'source_account' => $primaryAccount,
            'account_balance' => $accountBalance,
            'message' => ''
        ];

        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->processPayment($data);
        } else {
            $this->view('customer/pay_loan', $data);
        }
    }

    private function processPayment(&$data)
{
    $customerId = $_SESSION['customer_id'];

    // Sanitize and validate POST data
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    $applicationId = trim($_POST['loan_id']); // Renamed to applicationId logically
    $paymentAmount = (float)trim($_POST['payment_amount']);
    $sourceAccount = trim($_POST['source_account']);

    // Basic validation
    if (empty($applicationId) || $paymentAmount <= 0 || empty($sourceAccount)) {
        $data['message'] = '<div class="alert alert-danger">Please select a loan and enter a valid payment amount.</div>';
        // Need to reload data before viewing again
        $data['active_loans'] = $this->customerModel->getActiveLoanApplications($customerId);
        // Reload account balance
        $primaryAccount = $this->customerModel->getPrimaryAccountNumber($customerId);
        if ($primaryAccount) {
            $balanceData = $this->customerModel->validateAmount($primaryAccount);
            $data['account_balance'] = $balanceData ? (float)$balanceData->balance : 0.00;
        }
        return $this->view('customer/pay_loan', $data);
    }

    // 1. Process the payment using the simplified logic
    $result = $this->customerModel->processApplicationPayment(
        $applicationId,
        $paymentAmount,
        $sourceAccount,
        $customerId
    );

    // Reload account balance after payment processing
    $primaryAccount = $this->customerModel->getPrimaryAccountNumber($customerId);
    $accountBalance = 0.00;
    if ($primaryAccount) {
        $balanceData = $this->customerModel->validateAmount($primaryAccount);
        $accountBalance = $balanceData ? (float)$balanceData->balance : 0.00;
    }
    $data['account_balance'] = $accountBalance;

    if ($result['status'] === true) {
        $data['message'] = '<div class="alert alert-success">Loan payment processed successfully! Your balance has been updated and a transaction recorded.</div>';
        $data['active_loans'] = $this->customerModel->getActiveLoanApplications($customerId);
    } else {
        $errorMessage = $result['error'] ?? 'Payment failed. Please check the amount and try again.';
        $data['message'] = '<div class="alert alert-danger">Payment failed: ' . htmlspecialchars($errorMessage) . '</div>';
        $data['active_loans'] = $this->customerModel->getActiveLoanApplications($customerId);
    }

    $this->view('customer/pay_loan', $data);
}

    /**
     * Apply interest to all Savings accounts (Admin/System function)
     * This can be called manually or via cron job
     * Access via: /customer/apply_interest
     */
    public function apply_interest() {
        // Optional: Add admin authentication check here if needed
        // if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'admin') {
        //     header('Content-Type: application/json');
        //     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        //     exit;
        // }
        
        $result = $this->customerModel->calculateAndApplyInterest();
        
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * View Account Applications Status
     * Shows all account applications for the logged-in customer
     * Access via: /customer/account_applications
     */
    public function account_applications() {
        // Get customer email from session or customer data
        $customerEmail = $_SESSION['customer_email'] ?? null;
        
        // If email not in session, try to get it from customer data
        if (!$customerEmail && isset($_SESSION['customer_id'])) {
            // Get email from emails table using the parent controller's database connection
            $this->db->query("
                SELECT email FROM emails 
                WHERE customer_id = :customer_id 
                ORDER BY is_primary DESC, created_at ASC 
                LIMIT 1
            ");
            $this->db->bind(':customer_id', $_SESSION['customer_id']);
            $emailResult = $this->db->single();
            $customerEmail = $emailResult->email ?? null;
        }

        $applications = [];
        if ($customerEmail) {
            $applications = $this->customerModel->getAccountApplicationsByEmail($customerEmail);
        }

        // Format applications data
        $formattedApplications = [];
        foreach ($applications as $app) {
            // Format account type
            $accountTypeDisplay = ucfirst(str_replace(['acct-', '-'], ['', ' '], $app->account_type ?? ''));
            
            // Format dates
            $submittedAt = $app->submitted_at ? date('M d, Y h:i A', strtotime($app->submitted_at)) : 'N/A';
            $reviewedAt = $app->reviewed_at ? date('M d, Y h:i A', strtotime($app->reviewed_at)) : null;
            $dateOfBirth = $app->date_of_birth ? date('M d, Y', strtotime($app->date_of_birth)) : 'N/A';
            
            // Format annual income
            $annualIncome = $app->annual_income ? '₱' . number_format($app->annual_income, 2) : 'N/A';
            
            // Parse selected cards and services
            $selectedCards = $app->selected_cards ? explode(',', $app->selected_cards) : [];
            $additionalServices = $app->additional_services ? explode(',', $app->additional_services) : [];
            
            // Status badge class
            $statusClass = 'warning';
            if ($app->application_status === 'approved') {
                $statusClass = 'success';
            } elseif ($app->application_status === 'rejected') {
                $statusClass = 'danger';
            }
            
            $formattedApplications[] = [
                'application_id' => $app->application_id,
                'application_number' => $app->application_number,
                'application_status' => ucfirst($app->application_status),
                'status_class' => $statusClass,
                'first_name' => $app->first_name,
                'last_name' => $app->last_name,
                'full_name' => $app->first_name . ' ' . $app->last_name,
                'email' => $app->email,
                'phone_number' => $app->phone_number,
                'date_of_birth' => $dateOfBirth,
                'street_address' => $app->street_address,
                'barangay' => $app->barangay,
                'city' => $app->city,
                'state' => $app->state,
                'zip_code' => $app->zip_code,
                'full_address' => trim(implode(', ', array_filter([
                    $app->street_address,
                    $app->barangay,
                    $app->city,
                    $app->state,
                    $app->zip_code
                ]))),
                'ssn' => $app->ssn,
                'id_type' => $app->id_type,
                'id_number' => $app->id_number,
                'employment_status' => $app->employment_status,
                'employer_name' => $app->employer_name ?? 'N/A',
                'job_title' => $app->job_title ?? 'N/A',
                'annual_income' => $annualIncome,
                'account_type' => $accountTypeDisplay,
                'selected_cards' => $selectedCards,
                'additional_services' => $additionalServices,
                'submitted_at' => $submittedAt,
                'reviewed_at' => $reviewedAt
            ];
        }

        $data = [
            'title' => 'Account Applications',
            'first_name' => $_SESSION['customer_first_name'] ?? '',
            'last_name' => $_SESSION['customer_last_name'] ?? '',
            'applications' => $formattedApplications,
            'total_applications' => count($formattedApplications),
            'pending_count' => count(array_filter($formattedApplications, fn($app) => strtolower($app['application_status']) === 'pending')),
            'approved_count' => count(array_filter($formattedApplications, fn($app) => strtolower($app['application_status']) === 'approved')),
            'rejected_count' => count(array_filter($formattedApplications, fn($app) => strtolower($app['application_status']) === 'rejected'))
        ];

        $this->view('customer/account_applications', $data);
    }
}