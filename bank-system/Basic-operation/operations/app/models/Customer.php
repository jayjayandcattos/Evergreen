<?php

class Customer extends Database{

  private $db;

  public function __construct()
  {
    $this->db = new Database();
  }

  public function getCustomerByEmailOrAccountNumber($identifier) {
    $this->db->query("
            SELECT
                c.customer_id,
                c.first_name,
                c.last_name,
                c.password_hash,
                a.account_number
            FROM
                bank_customers c
            LEFT JOIN
                emails e ON c.customer_id = e.customer_id
            LEFT JOIN
                customer_accounts a ON c.customer_id = a.customer_id
            WHERE
                e.email = :emailIdentifier OR a.account_number = :accountIdentifier
            LIMIT 1;
        ");

    if(filter_var($identifier, FILTER_VALIDATE_EMAIL)){
        $email = $identifier;
        $account_number = null;
    } else {
        $email = null;
        $account_number = $identifier;
    }

    $this->db->bind(':emailIdentifier', $email);
    $this->db->bind(':accountIdentifier', $account_number);
    return $this->db->single();

    }

    public function loginCustomer($identifier, $password) {
        $customer = $this->getCustomerByEmailOrAccountNumber($identifier);
        
        // Debug: Check if customer is found
        if (!$customer) {
            error_log("Customer not found for identifier: $identifier"); // Or echo for testing
            return false;
        }
        
        // Debug: Check password verification
        if (password_verify($password, $customer->password_hash)) {
            return $customer;
        } else {
            error_log("Password mismatch for: $identifier"); // Or echo
            return false;
        }
    }

    public function getAccountsByCustomerId($customer_id) {
        // --- 1. FIRST QUERY (GET ACCOUNTS AND BALANCES) ---
        $this->db->query("
            SELECT
                a.account_id,
                a.account_number,
                act.type_name AS account_type,
                c.first_name,
                c.last_name,
                
                COALESCE(SUM(
                    CASE tt.type_name
                       WHEN 'Deposit' THEN t.amount
                        WHEN 'Transfer In' THEN t.amount
                        WHEN 'Interest Payment' THEN t.amount
                        WHEN 'Loan Disbursement' THEN t.amount
                        -- Debits (will show as negative in the SQL result)
                        WHEN 'Withdrawal' THEN -t.amount
                        WHEN 'Transfer Out' THEN -t.amount
                        WHEN 'Service Charge' THEN -t.amount
                        WHEN 'Loan Payment' THEN -t.amount
                        ELSE 0
                    END
                ), 0) AS current_balance
                
            FROM 
                customer_linked_accounts cla
            INNER JOIN customer_accounts a ON cla.account_id = a.account_id
            INNER JOIN bank_customers c ON cla.customer_id = c.customer_id
            LEFT JOIN bank_account_types act ON a.account_type_id = act.account_type_id
            LEFT JOIN bank_transactions t ON a.account_id = t.account_id
            LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
            WHERE 
                cla.customer_id = :customer_id AND cla.is_active = 1 AND a.is_locked = 0
            GROUP BY 
                a.account_id, a.account_number, act.type_name, c.first_name, c.last_name
            ORDER BY a.created_at DESC;
        ");

        $this->db->bind(':customer_id', $customer_id);
        $customer_accounts = $this->db->resultSet();

        foreach ($customer_accounts as $account) {
            $account->account_name = $account->first_name . ' ' . $account->last_name;
            $account->branch = 'SM Fairview';
            
            // Use the calculated balance from the SQL query
            $account->beginning_balance = 0.00; // This is arbitrary, 'current_balance' is the useful value
            $account->ending_balance = (float) $account->current_balance;

            // Credit Card Logic
            if (str_contains(strtolower($account->account_type), 'credit card')) {
                $account->available_credit = 5245.00;
                $account->credit_limit = 50000.00;
            } else {
                $account->available_credit = null;
                $account->credit_limit = null;
            }

            $this->db->query("
                SELECT
                    t.transaction_id,
                    t.transaction_ref,
                    t.amount,
                    t.description,
                    tt.type_name AS transaction_type_name,
                    t.created_at
                FROM bank_transactions t
                JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
                WHERE t.account_id = :account_id
                ORDER BY t.created_at DESC
                LIMIT 3
            ");
            $this->db->bind(':account_id', $account->account_id);
            $account->transactions = $this->db->resultSet();
        }

        return $customer_accounts;
    }

    public function getAccountById($id) {
        $this->db->query('SELECT * FROM customer_accounts WHERE account_id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function deleteAccountById($id) {
        $this->db->query('UPDATE `customer_linked_accounts` SET `is_active`= 0 WHERE account_id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function addAccount($data) {
        // Step 1: Get account_id and account_type by account_number
        $this->db->query("
            SELECT account_id, account_type_id 
            FROM customer_accounts 
            WHERE account_number = :account_number
        ");
        $this->db->bind(':account_number', $data['account_number']);
        $account = $this->db->single();

        if (!$account) {
            // No account found with that number
            return ['success' => false, 'error' => 'Account number not found.'];
        }

        // Step 2: Verify account type matches user input
        $this->db->query("
            SELECT account_type_id, type_name 
            FROM bank_account_types 
            WHERE type_name = :account_type
        ");
        $this->db->bind(':account_type', $data['account_type']);
        $type = $this->db->single();

        if (!$type) {
            return ['success' => false, 'error' => 'Invalid account type provided.'];
        }

        if ($account->account_type_id !== $type->account_type_id) {
            return ['success' => false, 'error' => 'Account type does not match the account number.'];
        }

        $account_id = $account->account_id;

        // Step 3: Check if link already exists
        $this->db->query("
            SELECT * 
            FROM customer_linked_accounts
            WHERE customer_id = :customer_id AND account_id = :account_id
        ");
        $this->db->bind(':customer_id', $data['customer_id']);
        $this->db->bind(':account_id', $account_id);
        $existing = $this->db->single();

        if ($existing) {
            if ($existing->is_active == 0) {
                // Step 4: Reactivate if inactive
                $this->db->query("
                    UPDATE customer_linked_accounts
                    SET is_active = 1
                    WHERE customer_id = :customer_id AND account_id = :account_id
                ");
                $this->db->bind(':customer_id', $data['customer_id']);
                $this->db->bind(':account_id', $account_id);
                $this->db->execute();
                return ['success' => true, 'message' => 'Account reactivated successfully.'];
            } else {
                return ['success' => false, 'error' => 'This account is already linked and active.'];
            }
        }

        // Step 5: Insert new link
        $this->db->query("
            INSERT INTO customer_linked_accounts (customer_id, account_id, is_active)
            VALUES (:customer_id, :account_id, 1)
        ");
        $this->db->bind(':customer_id', $data['customer_id']);
        $this->db->bind(':account_id', $account_id);

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Account linked successfully.'];
        }

        return ['success' => false, 'error' => 'Failed to add account.'];
    }

    // -- CREATING ACCOUNT
    public function getAccountTypes(){
        // Limiting to IDs 1 and 2 based on the user's explicit requirement for Checking and Savings only.
        $this->db->query("SELECT account_type_id, type_name FROM bank_account_types WHERE account_type_id IN (1, 2) ORDER BY account_type_id ASC");
        return $this->db->resultSet(); 
    }

    public function createBankAccount($customer_id, $account_type_id){
        $account_number = $this->generateUniqueAccountNumber($account_type_id);
        
        // Set interest rate for Savings accounts (account_type_id = 1)
        // 0.5% annual interest rate (0.50 in DECIMAL format)
        // Checking accounts (account_type_id = 2) have NULL interest_rate
        $interest_rate = ($account_type_id == 1) ? 0.50 : NULL;

        $this->db->query("
            INSERT INTO customer_accounts 
                (customer_id, account_number, account_type_id, interest_rate, is_locked, created_at)
            VALUES 
                (:customer_id, :account_number, :account_type_id, :interest_rate, 0, NOW())
        ");
        
        $this->db->bind(':customer_id', $customer_id);
        $this->db->bind(':account_number', $account_number);
        $this->db->bind(':account_type_id', $account_type_id);
        $this->db->bind(':interest_rate', $interest_rate);

        if ($this->db->execute()) {
             $account_id = $this->db->lastInsertId();
             $this->autoInsertCustomerLinkedAccount($customer_id, $account_id);
            return $account_number;
        }
        return false;
    }

    // AUTO INSERT
    public function autoInsertCustomerLinkedAccount($customer_id, $account_id){
        $this->db->query("
            INSERT INTO customer_linked_accounts 
                (customer_id, account_id, linked_at, is_active) 
            VALUES 
                (:customer_id, :account_id, NOW(), 1)
        ");
        $this->db->bind(':customer_id', $customer_id);
        $this->db->bind(':account_id', $account_id);

        return $this->db->execute();
    }

    private function generateUniqueAccountNumber($account_type_id) {
        $prefix = '';
        if ($account_type_id == 1) { 
            $prefix = 'SA'; // Savings Account (ID 1)
        } elseif ($account_type_id == 2) {
            $prefix = 'CHA'; // Checking Account (ID 2)
        } else {
            $prefix = 'GEN'; // Generic Account
        }

        // Generate a unique 4-digit random number
        // Format: PREFIX-XXXX-YEAR (e.g., CHA-1234-2024 or SA-5678-2024)
        $current_year = date('Y');
        $max_attempts = 100;
        $attempt = 0;
        
        do {
            $unique_digits = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $account_number = "{$prefix}-{$unique_digits}-{$current_year}";
            
            // Check if account number already exists
            $this->db->query("SELECT COUNT(*) as count FROM customer_accounts WHERE account_number = :account_number");
            $this->db->bind(':account_number', $account_number);
            $result = $this->db->single();
            
            $attempt++;
        } while ($result && $result->count > 0 && $attempt < $max_attempts);
        
        if ($attempt >= $max_attempts) {
            // Fallback: add timestamp to ensure uniqueness
            $account_number = "{$prefix}-{$unique_digits}-{$current_year}-" . time();
        }

        return $account_number;
    }

    public function getCustomerProfileData($customer_id){
        $this->db->query("
            SELECT 
                c.first_name, c.middle_name,
                c.last_name,
                c.middle_name,
                -- Account Info
                (SELECT phone_number FROM phones WHERE customer_id = c.customer_id AND is_primary = 1 LIMIT 1) AS mobile_number,
                (SELECT email FROM emails WHERE customer_id = c.customer_id AND is_primary = 1 LIMIT 1) AS email_address,
                -- Personal Info
                cp.date_of_birth,
                cp.marital_status AS civil_status,
                cp.nationality AS citizenship,
                cp.occupation,
                cp.company AS name_of_employer,
                g.gender_name AS gender,
                -- Address (Home Address - assuming primary home address)
                (SELECT CONCAT(a.address_line, ', ', a.city, ', ', p.province_name, ', Philippines') 
                 FROM addresses a
                 JOIN provinces p ON a.province_id = p.province_id
                 WHERE a.customer_id = c.customer_id AND a.is_primary = 1 AND a.address_type = 'home' LIMIT 1) AS home_address
            FROM bank_customers c
            LEFT JOIN customer_profiles cp ON c.customer_id = cp.customer_id
            LEFT JOIN genders g ON cp.gender_id = g.gender_id
            WHERE c.customer_id = :customer_id;
        ");
        
        $this->db->bind(':customer_id', $customer_id);
        
        return $this->db->single();
    }

    // --- FOR THE CHANGE PASSWORD ---
    public function getCurrentPasswordHash($user_id){
        $this->db->query("
            SELECT password_hash
            FROM bank_customers
            WHERE customer_id = :id;
        ");
        $this->db->bind(':id', $user_id);
        $row = $this->db->single();
        return $row ? $row->password_hash : false;
    }

    public function updatePassword($user_id, $new_password_hash){
        $this->db->query("
            UPDATE bank_customers
            SET password_hash = :new_password_hash
            WHERE customer_id = :id;
        ");
        $this->db->bind(':new_password_hash', $new_password_hash);
        $this->db->bind(':id', $user_id);

        return $this->db->execute(); 
    }
    
    public function getAccountByNumber($account_number){
        $this->db->query("
            SELECT *
            FROM customer_accounts
            WHERE account_number = :account_number;
        ");

        $this->db->bind(':account_number', $account_number);
        return $this->db->single();
    }

    public function validateRecipient($recipient_number, $recipient_name){
        $this->db->query("
            SELECT 
                a.account_number, 
                CONCAT_WS(' ', c.first_name, c.last_name) AS customer_name,
                c.customer_id
            FROM 
                customer_accounts a
            INNER JOIN 
                bank_customers c ON a.customer_id = c.customer_id
            WHERE 
                a.account_number = :recipient_number;
        ");
        $this->db->bind(':recipient_number', $recipient_number);
        $result = $this->db->single();

        if(empty($result)){
            return ['status' => false , 'error' => 'Invalid Account Number'];
        }

        if (strtolower(trim($result->customer_name)) !== strtolower(trim($recipient_name))) {
            return ['status' => false, 'error' => 'Recipient name does not match the account number.'];
        }

        return [
            'status' => true,
            'customer_id' => $result->customer_id,
            'account_number' => $result->account_number
        ];

    }

    public function validateAmount($account_number){
        $this->db->query("
            SELECT 
                a.account_number,
                COALESCE(SUM(
                    CASE tt.type_name 
                        WHEN 'Deposit' THEN t.amount
                        WHEN 'Transfer In' THEN t.amount
                        WHEN 'Interest Payment' THEN t.amount
                        WHEN 'Loan Disbursement' THEN t.amount
                        -- Debits (will show as negative in the SQL result)
                        WHEN 'Withdrawal' THEN -t.amount
                        WHEN 'Transfer Out' THEN -t.amount
                        WHEN 'Service Charge' THEN -t.amount
                        WHEN 'Loan Payment' THEN -t.amount
                        
                        -- If a transaction type isn't listed (e.g., system error), treat as 0
                        ELSE 0 
                    END
                ), 0) AS balance
            FROM 
                customer_accounts a
            LEFT JOIN 
                bank_transactions t ON a.account_id = t.account_id
            LEFT JOIN 
                transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
            WHERE 
                a.account_number = :account_number
            GROUP BY 
                a.account_id, a.account_number;
        ");
        $this->db->bind('account_number', $account_number);

        return $this->db->single();
    }

    public function recordTransaction($transaction_ref, $sender, $receiver, $amount, $fee, $message){
        // for sender
        $this->db->query("
        INSERT INTO bank_transactions (
            transaction_ref,
            account_id,
            transaction_type_id,
            amount,
            related_account_id,
            description
        )
        VALUES (
            :transaction_ref,
            :sender,
            :transaction_type,
            :amount,
            :receiver,
            :message
        );
        ");
        $this->db->bind(':transaction_ref', $transaction_ref);
        $this->db->bind(':sender', $sender);
        $this->db->bind(':transaction_type', 8);
        $this->db->bind(':amount', $amount);
        $this->db->bind(':receiver', $receiver);
        $this->db->bind(':message', $message);
        $this->db->execute();

        // For the fee
        $this->db->query("
        INSERT INTO bank_transactions (
            account_id,
            transaction_type_id,
            amount,
            description
        )
        VALUES (
            :sender,
            :transaction_type,
            :amount,
            :message
        );
        ");
        $this->db->bind(':sender', $sender);
        $this->db->bind(':transaction_type', 5);
        $this->db->bind(':amount', $fee);
        $this->db->bind(':message', 'Transaction Service Charge - ' . $transaction_ref);
        $this->db->execute();

        // for the receiver
        $this->db->query("
        INSERT INTO bank_transactions (
            transaction_ref,
            account_id,
            transaction_type_id,
            amount,
            related_account_id,
            description
        )
        VALUES (
            :transaction_ref,
            :sender,
            :transaction_type,
            :amount,
            :receiver,
            :message
        );
        ");
        $this->db->bind(':transaction_ref', $transaction_ref);
        $this->db->bind(':sender', $receiver);
        $this->db->bind(':transaction_type', 3);
        $this->db->bind(':amount', $amount);
        $this->db->bind(':receiver', $sender);
        $this->db->bind(':message', $message);
        $this->db->execute();
    }

    public function getDropDownByCustomerId($customer_id) {
        $this->db->query("
            SELECT 
                a.account_id, 
                a.account_number
            FROM 
                customer_accounts a
            WHERE 
                a.customer_id = :id
            AND
                a.is_locked = 0
        ");

        $this->db->bind(':id', $customer_id);
        return $this->db->resultSet();
    }

    // transaction 
    public function getTransactionTypes() {
        $this->db->query("SELECT type_name FROM transaction_types ORDER BY type_name");
        return $this->db->resultSet();
    }

    public function getLinkedAccountsForFilter($customer_id) {
        $this->db->query("
            SELECT
                a.account_id,
                a.account_number,
                act.type_name AS account_type
            FROM customer_linked_accounts cla
            INNER JOIN customer_accounts a ON cla.account_id = a.account_id
            LEFT JOIN bank_account_types act ON a.account_type_id = act.account_type_id
            WHERE
                cla.customer_id = :customer_id AND cla.is_active = 1
            ORDER BY a.account_number
        ");
        $this->db->bind(':customer_id', $customer_id);
        return $this->db->resultSet();
    }

    public function getAllTransactionsByCustomerId($customer_id, $filters = [], $limit = 20, $offset = 0) {
        // SQL logic to determine if the transaction is a credit (in) or debit (out)
        $sql_signed_amount = "
            CASE tt.type_name
                WHEN 'Deposit' THEN t.amount
                WHEN 'Transfer In' THEN t.amount
                WHEN 'Interest Payment' THEN t.amount
                WHEN 'Loan Disbursement' THEN t.amount
                -- Debits (will show as negative in the SQL result)
                WHEN 'Withdrawal' THEN -t.amount
                WHEN 'Transfer Out' THEN -t.amount
                WHEN 'Service Charge' THEN -t.amount
                WHEN 'Loan Payment' THEN -t.amount
                ELSE 0
            END
        ";
        $sql_select = "
            SELECT
                t.transaction_id,
                t.transaction_ref,
                t.description,
                t.created_at,
                tt.type_name AS transaction_type,
                a.account_number,
                a.account_id,
                ({$sql_signed_amount}) AS signed_amount,
                t.amount AS raw_amount
            FROM customer_linked_accounts cla
            INNER JOIN customer_accounts a ON cla.account_id = a.account_id
            INNER JOIN bank_transactions t ON a.account_id = t.account_id
            LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
            WHERE
                cla.customer_id = :customer_id
        ";

        $params = [':customer_id' => $customer_id];
        $conditions = [];

        // Filter by Account ID
        if (!empty($filters['account_id']) && $filters['account_id'] !== 'all') {
            $conditions[] = "a.account_id = :account_id";
            $params[':account_id'] = $filters['account_id'];
        }
        
        // Filter by Transaction Type
        if (!empty($filters['type_name']) && $filters['type_name'] !== 'All') {
            $conditions[] = "tt.type_name = :type_name";
            $params[':type_name'] = $filters['type_name'];
        }

        // Filter by Date Range
        if (!empty($filters['start_date'])) {
            $conditions[] = "DATE(t.created_at) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = "DATE(t.created_at) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Append all WHERE conditions
        if (!empty($conditions)) {
            $sql_select .= " AND " . implode(" AND ", $conditions);
        }

        // --- PAGINATION COUNT ---
        $sql_count = "SELECT COUNT(*) AS total FROM ({$sql_select}) AS subquery";
        $this->db->query($sql_count);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $total_bank_transactions = $this->db->single()->total;

        // --- FETCH PAGINATED RESULTS ---
        $sql_order_limit = " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql_select . $sql_order_limit);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        // Re-bind all filter parameters
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $bank_transactions = $this->db->resultSet();

        return [
            'bank_transactions' => $bank_transactions,
            'total' => $total_bank_transactions,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    public function getAllFilteredTransactions($customer_id, $filters) {
        // SQL logic to determine if the transaction is a credit (in) or debit (out)
        // This logic should match the one in getAllTransactionsByCustomerId
        $sql_signed_amount = "
            CASE tt.type_name
                WHEN 'Deposit' THEN t.amount
                WHEN 'Transfer In' THEN t.amount
                WHEN 'Interest Payment' THEN t.amount
                WHEN 'Loan Disbursement' THEN t.amount
                -- Debits (will show as negative in the SQL result)
                WHEN 'Withdrawal' THEN -t.amount
                WHEN 'Transfer Out' THEN -t.amount
                WHEN 'Service Charge' THEN -t.amount
                WHEN 'Loan Payment' THEN -t.amount
                ELSE 0
            END
        ";

        $sql = "SELECT 
                    t.*, 
                    tt.type_name AS transaction_type, 
                    a.account_number, 
                    ({$sql_signed_amount}) AS signed_amount,
                    t.amount AS raw_amount
                FROM bank_transactions t
                JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
                JOIN customer_accounts a ON t.account_id = a.account_id
                INNER JOIN customer_linked_accounts cla ON a.account_id = cla.account_id
                WHERE cla.customer_id = :customer_id AND cla.is_active = 1";

        $params = [':customer_id' => $customer_id];

        // Apply Filters
        if (!empty($filters['account_id']) && $filters['account_id'] !== 'all') {
            $sql .= ' AND a.account_id = :account_id';
            $params[':account_id'] = $filters['account_id'];
        }

        if (!empty($filters['type_name']) && $filters['type_name'] !== 'All') {
            $sql .= ' AND tt.type_name = :type_name';
            $params[':type_name'] = $filters['type_name'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= ' AND DATE(t.created_at) >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= ' AND DATE(t.created_at) <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        $sql .= ' ORDER BY t.created_at DESC';

        $this->db->query($sql);
        
        // Bind parameters
        foreach ($params as $param_name => $value) {
            $this->db->bind($param_name, $value);
        }

        return $this->db->resultSet();
    }

    // -- LOANS --
    public function getActiveLoanApplications($customerId)
    {
        $this->db->query("
            SELECT
                ca.account_number,
                la.id AS application_id,
                la.loan_type,
                la.loan_amount AS remaining_balance, -- NULL if no active loan application
                DATE_FORMAT(la.created_at, '%M %d, %Y') AS application_date
            FROM
                customer_accounts ca
            LEFT JOIN
                loan_applications la 
                ON ca.account_number = la.account_number
                AND la.status = 'Approved'       -- Only join to approved applications
            WHERE
                ca.customer_id = :customer_id
            ORDER BY
                ca.account_number, la.loan_amount DESC;
        ");
        $this->db->bind(':customer_id', $customerId);
        return $this->db->resultSet();
    }

    public function processApplicationPayment($applicationId, $paymentAmount, $sourceAccountNumber, $customerId){
        // Input validation
        if (!is_numeric($paymentAmount) || $paymentAmount <= 0) {
            return ['status' => false, 'error' => 'Invalid payment amount. Must be a positive number.'];
        }
        if (empty($applicationId) || empty($sourceAccountNumber) || empty($customerId)) {
            return ['status' => false, 'error' => 'Missing required parameters.'];
        }

        // 1. Validate that the loan application belongs to the customer and is active/approved
        $this->db->query("
            SELECT la.id, la.loan_amount, la.status
            FROM loan_applications la
            INNER JOIN customer_accounts ca ON la.account_number = ca.account_number
            WHERE la.id = :application_id AND ca.customer_id = :customer_id AND la.status = 'Approved'
        ");
        $this->db->bind(':application_id', $applicationId);
        $this->db->bind(':customer_id', $customerId);
        $loanApp = $this->db->single();

        if (!$loanApp) {
            return ['status' => false, 'error' => 'Loan application not found or does not belong to the customer.'];
        }

        $remainingBalance = (float)$loanApp->loan_amount;
        if ($remainingBalance <= 0) {
            return ['status' => false, 'error' => 'Loan is already fully paid or closed.'];
        }
        if ($paymentAmount > $remainingBalance) {
            return ['status' => false, 'error' => 'Payment amount exceeds remaining loan balance.'];
        }

        // 2. Validate that the source account belongs to the customer and is not locked
        $this->db->query("
            SELECT account_id
            FROM customer_accounts
            WHERE account_number = :account_number AND customer_id = :customer_id AND is_locked = 0
        ");
        $this->db->bind(':account_number', $sourceAccountNumber);
        $this->db->bind(':customer_id', $customerId);
        $sourceAccount = $this->db->single();

        if (!$sourceAccount) {
            return ['status' => false, 'error' => 'Source account not found, not owned by the customer, or is locked.'];
        }
        $sourceAccountId = $sourceAccount->account_id;

        // 3. Check source account balance
        $balanceCheck = $this->validateAmount($sourceAccountNumber);
        $currentBalance = $balanceCheck ? (float)$balanceCheck->balance : 0.00;
        if ($currentBalance < $paymentAmount) {
            return ['status' => false, 'error' => 'Insufficient funds in the source account.'];
        }

        // 4. Begin transaction
        $this->db->beginTransaction();

        try {
            // 5. Update loan application balance (subtract payment)
            $this->db->query("
                UPDATE loan_applications
                SET loan_amount = loan_amount - :payment_amount
                WHERE id = :application_id
            ");
            $this->db->bind(':payment_amount', $paymentAmount);
            $this->db->bind(':application_id', $applicationId);

            if (!$this->db->execute()) {
                throw new Exception("Failed to update loan application balance.");
            }

            // 6. Insert bank transaction for the payment (debit from source account)
            $transactionTypeId = 7; // Assuming 7 is 'Loan Payment'
            $transactionRef = uniqid('LP-'); // Prefix for clarity
            $description = "Loan Payment - Ref: {$transactionRef}, Application ID: {$applicationId}, From: {$sourceAccountNumber}";

            $this->db->query("
                INSERT INTO bank_transactions (
                    transaction_ref,
                    account_id,
                    transaction_type_id,
                    amount,
                    description,
                    created_at
                )
                VALUES (
                    :transaction_ref,
                    :account_id,
                    :type_id,
                    :amount,
                    :description,
                    NOW()
                )
            ");
            $this->db->bind(':transaction_ref', $transactionRef);
            $this->db->bind(':account_id', $sourceAccountId);
            $this->db->bind(':type_id', $transactionTypeId);
            $this->db->bind(':amount', $paymentAmount); // Positive for raw amount; signed logic handles debit
            $this->db->bind(':description', $description);

            if (!$this->db->execute()) {
                throw new Exception("Failed to record bank transaction.");
            }

            // 7. Check if loan is fully paid and close it
            $this->db->query("SELECT loan_amount FROM loan_applications WHERE id = :application_id");
            $this->db->bind(':application_id', $applicationId);
            $updatedLoan = $this->db->single();

            if ($updatedLoan && (float)$updatedLoan->loan_amount <= 0) {
                $this->db->query("
                    UPDATE loan_applications
                    SET status = 'Closed'
                    WHERE id = :application_id
                ");
                $this->db->bind(':application_id', $applicationId);
                if (!$this->db->execute()) {
                    throw new Exception("Failed to close the loan application.");
                }
            }

            // 8. Commit transaction
            $this->db->commit();
            return ['status' => true, 'message' => 'Loan payment processed successfully.', 'transaction_ref' => $transactionRef];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Loan Payment Error: " . $e->getMessage());
            return ['status' => false, 'error' => 'Payment processing failed: ' . $e->getMessage()];
        }
    }


    public function getPrimaryAccountNumber($customerId)
    {
        $this->db->query("SELECT account_number FROM customer_accounts WHERE customer_id = :customer_id LIMIT 1");
        $this->db->bind(':customer_id', $customerId);
        $result = $this->db->single();
        return $result ? $result->account_number : null;
    }

    // -- REFERRAL SYSTEM --
    
    /**
     * Get customer's referral code
     */
    public function getReferralCode($customerId)
    {
        $this->db->query("
            SELECT referral_code, total_points 
            FROM bank_customers 
            WHERE customer_id = :customer_id
        ");
        $this->db->bind(':customer_id', $customerId);
        $result = $this->db->single();
        return $result;
    }

    /**
     * Get referral statistics for a customer
     */
    public function getReferralStats($customerId)
    {
        // Get total points
        $this->db->query("
            SELECT total_points 
            FROM bank_customers 
            WHERE customer_id = :customer_id
        ");
        $this->db->bind(':customer_id', $customerId);
        $customer = $this->db->single();
        $totalPoints = $customer ? $customer->total_points : 0;

        // Count number of referrals (people who used this customer's code)
        $this->db->query("
            SELECT COUNT(*) as referral_count
            FROM bank_customers
            WHERE referred_by_customer_id = :customer_id
        ");
        $this->db->bind(':customer_id', $customerId);
        $referralCount = $this->db->single();
        $count = $referralCount ? $referralCount->referral_count : 0;

        return [
            'total_points' => $totalPoints,
            'referral_count' => $count
        ];
    }

    /**
     * Apply a friend's referral code
     */
    public function applyReferralCode($customerId, $friendCode)
    {
        $friendCode = strtoupper(trim($friendCode));
        
        if (empty($friendCode)) {
            return ['success' => false, 'message' => 'Please enter a referral code'];
        }

        try {
            $this->db->beginTransaction();

            // Check if user already used a referral code
            $this->db->query("
                SELECT referred_by_customer_id 
                FROM bank_customers 
                WHERE customer_id = :customer_id AND referred_by_customer_id IS NOT NULL
            ");
            $this->db->bind(':customer_id', $customerId);
            $existing = $this->db->single();
            
            if ($existing && $existing->referred_by_customer_id) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'You have already used a referral code'];
            }

            // Get user's own referral code to prevent self-referral
            $this->db->query("
                SELECT referral_code 
                FROM bank_customers 
                WHERE customer_id = :customer_id
            ");
            $this->db->bind(':customer_id', $customerId);
            $user = $this->db->single();
            
            if ($user && $user->referral_code === $friendCode) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'You cannot use your own referral code'];
            }

            // Find the referrer
            $this->db->query("
                SELECT customer_id, first_name, last_name 
                FROM bank_customers 
                WHERE referral_code = :referral_code
            ");
            $this->db->bind(':referral_code', $friendCode);
            $referrer = $this->db->single();
            
            if (!$referrer) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Invalid referral code'];
            }

            $referrerId = $referrer->customer_id;
            
            // Check if trying to use own code (double check)
            if ($referrerId == $customerId) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'You cannot use your own referral code'];
            }

            // Points to award
            $referrerPoints = 50.00;
            $referredPoints = 25.00;

            // Update new customer with referrer info
            $this->db->query("
                UPDATE bank_customers 
                SET referred_by_customer_id = :referrer_id 
                WHERE customer_id = :customer_id
            ");
            $this->db->bind(':referrer_id', $referrerId);
            $this->db->bind(':customer_id', $customerId);
            $this->db->execute();

            // Award points to referrer
            $this->db->query("
                UPDATE bank_customers 
                SET total_points = total_points + :points 
                WHERE customer_id = :referrer_id
            ");
            $this->db->bind(':points', $referrerPoints);
            $this->db->bind(':referrer_id', $referrerId);
            $this->db->execute();

            // Award points to new customer
            $this->db->query("
                UPDATE bank_customers 
                SET total_points = total_points + :points 
                WHERE customer_id = :customer_id
            ");
            $this->db->bind(':points', $referredPoints);
            $this->db->bind(':customer_id', $customerId);
            $this->db->execute();

            $this->db->commit();

            return [
                'success' => true, 
                'message' => 'Referral code applied successfully! You and your friend earned bonus points!',
                'referrer_points' => $referrerPoints,
                'your_points' => $referredPoints
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Referral Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    /**
     * Calculate and apply interest to all Savings accounts
     * Interest is calculated monthly based on the account balance
     * @return array Results of interest application
     */
    public function calculateAndApplyInterest() {
        // Get transaction type ID for Interest Payment
        $this->db->query("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Interest Payment' LIMIT 1");
        $interestType = $this->db->single();
        
        if (!$interestType) {
            return ['success' => false, 'error' => 'Interest Payment transaction type not found'];
        }
        
        $interest_type_id = $interestType->transaction_type_id;
        $results = [];
        $total_interest_applied = 0;
        $accounts_processed = 0;
        
        // Get all active Savings accounts (account_type_id = 1) with interest rate
        $this->db->query("
            SELECT 
                ca.account_id,
                ca.account_number,
                ca.interest_rate,
                ca.last_interest_date,
                ca.customer_id
            FROM customer_accounts ca
            INNER JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
            WHERE ca.account_type_id = 1 
            AND ca.interest_rate IS NOT NULL 
            AND ca.interest_rate > 0
            AND ca.is_locked = 0
        ");
        
        $savings_accounts = $this->db->resultSet();
        
        foreach ($savings_accounts as $account) {
            // Calculate current balance
            $balance = $this->getAccountBalance($account->account_id);
            
            if ($balance <= 0) {
                continue; // Skip accounts with zero or negative balance
            }
            
            // Calculate monthly interest (annual rate / 12)
            // interest_rate is stored as percentage (0.50 = 0.5%)
            $monthly_rate = ($account->interest_rate / 100) / 12;
            $interest_amount = $balance * $monthly_rate;
            
            // Round to 2 decimal places
            $interest_amount = round($interest_amount, 2);
            
            if ($interest_amount > 0) {
                // Record interest payment transaction
                $transaction_ref = 'INT-' . date('YmdHis') . '-' . $account->account_id;
                
                $this->db->query("
                    INSERT INTO bank_transactions 
                        (transaction_ref, account_id, transaction_type_id, amount, description, created_at)
                    VALUES 
                        (:transaction_ref, :account_id, :transaction_type_id, :amount, :description, NOW())
                ");
                
                $this->db->bind(':transaction_ref', $transaction_ref);
                $this->db->bind(':account_id', $account->account_id);
                $this->db->bind(':transaction_type_id', $interest_type_id);
                $this->db->bind(':amount', $interest_amount);
                $this->db->bind(':description', 'Monthly interest payment - ' . date('F Y'));
                
                if ($this->db->execute()) {
                    // Update last_interest_date
                    $this->db->query("
                        UPDATE customer_accounts 
                        SET last_interest_date = CURDATE()
                        WHERE account_id = :account_id
                    ");
                    $this->db->bind(':account_id', $account->account_id);
                    $this->db->execute();
                    
                    $total_interest_applied += $interest_amount;
                    $accounts_processed++;
                    
                    $results[] = [
                        'account_number' => $account->account_number,
                        'balance' => $balance,
                        'interest_applied' => $interest_amount
                    ];
                }
            }
        }
        
        return [
            'success' => true,
            'accounts_processed' => $accounts_processed,
            'total_interest_applied' => $total_interest_applied,
            'details' => $results
        ];
    }

    /**
     * Get current account balance from transactions
     * @param int $account_id
     * @return float Current balance
     */
    private function getAccountBalance($account_id) {
        $this->db->query("
            SELECT
                COALESCE(SUM(
                    CASE tt.type_name
                        WHEN 'Deposit' THEN t.amount
                        WHEN 'Transfer In' THEN t.amount
                        WHEN 'Interest Payment' THEN t.amount
                        WHEN 'Loan Disbursement' THEN t.amount
                        WHEN 'Withdrawal' THEN -t.amount
                        WHEN 'Transfer Out' THEN -t.amount
                        WHEN 'Service Charge' THEN -t.amount
                        WHEN 'Loan Payment' THEN -t.amount
                        ELSE 0
                    END
                ), 0) AS current_balance
            FROM bank_transactions t
            INNER JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
            WHERE t.account_id = :account_id
        ");
        
        $this->db->bind(':account_id', $account_id);
        $result = $this->db->single();
        
        return $result ? (float)$result->current_balance : 0.00;
    }
}