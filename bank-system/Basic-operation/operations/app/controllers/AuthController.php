<?php

class AuthController extends Controller{
  private $customerModel;

  public function __construct(){
    parent::__construct();
    $this->customerModel = $this->model('Customer');
  }
  
  public function login(){
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if(isset($_SESSION['customer_id'])){
      header('Location: ' .URLROOT. '/customer/account');
      exit;
    }

    // Get remembered identifier from cookie
    $remembered_identifier = isset($_COOKIE['remember_identifier']) ? $_COOKIE['remember_identifier'] : '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      $identifier = trim($_POST['identifier']);
      $password = trim($_POST['password']);
      $remember_me = isset($_POST['remember_me']) ? true : false;

      $data = [
        'identifier' => $identifier,
        'password' => $password,
        'remember_me' => $remember_me,
        'identifier_error' => '',
        'password_error' => '',
        'login_error' => ''
      ];

      if(empty($data['identifier'])){
        $data['identifier_error'] = 'Please enter your email or account number.';
      }

      if(empty($data['password'])){
        $data['password_error'] = 'Please enter your password.';
      }

      if(empty($data['identifier_error']) && empty($data['password_error'])){
        $loggedInCustomer = $this->customerModel->loginCustomer($data['identifier'], $data['password']);
        if($loggedInCustomer){
          $_SESSION['customer_id'] = $loggedInCustomer->customer_id;
          $_SESSION['customer_first_name'] = $loggedInCustomer->first_name;
          $_SESSION['customer_last_name'] = $loggedInCustomer->last_name;

          // Handle remember me checkbox
          if ($remember_me) {
            // Set cookie to remember identifier for 30 days
            setcookie('remember_identifier', $identifier, time() + (30 * 24 * 60 * 60), '/');
          } else {
            // Clear remember me cookie if unchecked
            if (isset($_COOKIE['remember_identifier'])) {
              setcookie('remember_identifier', '', time() - 3600, '/');
            }
          }

          header('Location: ' .URLROOT. '/customer/account');
          exit;
        } else {
            $data['login_error'] = 'Invalid credentials or account not found.';
            $this->view('auth/login', $data);
            return;
        }
      } else {
            $this->view('auth/login', $data);
            return;
        }
    }
     $data = [
            'identifier' => $remembered_identifier,
            'password' => '',
            'remember_me' => !empty($remembered_identifier),
            'identifier_error' => '',
            'password_error' => '',
            'login_error' => ''
        ];
        $this->view('auth/login', $data);
  }

  public function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_identifier'])) {
        setcookie('remember_identifier', '', time() - 3600, '/');
    }
    
    // Redirect to login page - dynamically construct URL to work with any host
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = '/Evergreen/bank-system/evergreen-marketing/login.php';
    header('Location: ' . $protocol . '://' . $host . $basePath);
    exit();
  }
}