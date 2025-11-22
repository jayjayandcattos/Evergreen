<?php

class CustomerController extends Controller {
    private $customerModel;

   public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Redirect to login if not logged in
        if (!isset($_SESSION['customer_id'])) {
            header('Location: /bank-system/evergreen-marketing/login.php');
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
                'accounts' => $accounts
            ]);

            $this->view('customer/account', $data);

        } else {
            $data = [
                'account_number' => '',
                'account_type'   => '',
                'account_number_error' => '',
                'account_type_error'   => '',
                'success_message'      => '',
            ];

            $this->view('customer/account', $data);
        }
    }

    // -- CREATING ACCOUNT
    public function create_account()
    {
        $data = [
            'title' => 'Open New Account',
            'account_types' => $this->customerModel->getAccountTypes(),
            'account_type_id' => '',
            'initial_deposit' => '',
            'error_message' => ''
        ];

        // Process POST Request
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $data['account_type_id'] = trim($_POST['account_type_id']);

            // Validation
            if (empty($_POST['agree_terms'])) {
                $data['error_message'] = "You must agree to the Terms & Conditions before opening an account.";
                $this->view('customer/open_account', $data);
                return;
            }
            if (empty($data['account_type_id'])) {
                $data['error_message'] = 'Please select an account type.';
            } else {
                // Perform account creation
                $customer_id = $_SESSION['customer_id'] ?? 1; // Use a default if session isn't set (for testing)

                $new_account_number = $this->customerModel->createBankAccount(
                    $customer_id, 
                    $data['account_type_id'], 
                );

                if ($new_account_number) {
                    $_SESSION['success_message'] = "Success! Your new account (No. {$new_account_number}) has been opened.";
                    // header('Location: ' . URLROOT . '/customer/account'); 
                    // exit();
                } else {
                    $data['error_message'] = 'An error occurred while opening the account. Please try again.';
                }
            }
        }

        // Load View
        $this->view('customer/create_account', $data);
    }

    // --- PROFILE ---
    public function profile(){
        $customer_id = $_SESSION['customer_id'];

        $profile_data = $this->customerModel->getCustomerProfileData($customer_id);

        if (!$profile_data) {
             $profile_data = (object)[
                 'first_name' => 'N/A', 'last_name' => 'N/A', 'username' => 'N/A', 
                 'mobile_number' => 'N/A', 'email_address' => 'N/A', 'home_address' => 'N/A',
                 'date_of_birth' => 'N/A', 'gender' => 'N/A', 'civil_status' => 'N/A', 
                 'citizenship' => 'N/A', 'occupation' => 'N/A', 'name_of_employer' => 'N/A'
             ];
        }

        $data = [
            'title' => "My Profile",
            'profile' => $profile_data,
            'full_name' => trim($profile_data->first_name . ' ' . $profile_data->middle_name . ' ' . $profile_data->last_name),
            'source_of_funds' => $profile_data->occupation,
            'employment_status' => $profile_data->occupation ? 'Employed' : 'Unemployed',
            'place_of_birth' => 'Quezon City',
            'employer_address' => '123 Bldg, Metro Manila',
        ];

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

                $this->view('customer/receipt', $data);
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
            header('Location: ' . URLROOT . '/customer/transaction_history');
            exit;
        }
    }
    
    protected function generateCSV($transactions) {
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="transactions_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open a temporary stream for output
        $output = fopen('php://output', 'w');

        // Define CSV Column Headers (adjust these to match your data structure)
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

        $pdf->Output('statement_' . date('Ymd') . '.pdf', 'D');
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

        $data = [
            'title' => "Pay Loan",
            'first_name' => $_SESSION['customer_first_name'] ?? 'Customer',
            'active_loans' => $activeLoans,
            'source_account' => $primaryAccount,
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
        return $this->view('customer/pay_loan', $data);
    }

    // 1. Process the payment using the simplified logic
    $result = $this->customerModel->processApplicationPayment(
        $applicationId,
        $paymentAmount,
        $sourceAccount,
        $customerId
    );

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
}