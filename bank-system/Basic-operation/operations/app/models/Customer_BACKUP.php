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
                c.email,
                c.password_hash,
                a.account_number
            FROM
                bank_customers c
            LEFT JOIN
                customer_accounts a ON c.customer_id = a.customer_id
            WHERE
                c.email = :emailIdentifier OR a.account_number = :accountIdentifier
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
}

  // REST OF THE FILE CONTINUES BELOW...
  // Copy the rest from the original file starting from loginCustomer method
