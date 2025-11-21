<?php
/**
 * Authentication Controller
 * Handles user authentication (sign-in, sign-up, logout)
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $userModel;
    private $errors = [];
    private $success = '';
    
    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }
    
    /**
     * Handle sign-in request
     * @param array $postData
     * @return array Response with status and message
     */
    public function signIn($postData) {
        // Validate input
        if (!$this->validateSignInInput($postData)) {
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
        
        $email = trim($postData['email']);
        $password = $postData['password'];
        
        // Find user by email
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            $this->errors[] = 'Invalid email or password.';
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
        
        // Verify password
        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            $this->errors[] = 'Invalid email or password.';
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        // Determine redirect URL based on admin status
        $redirectUrl = ($user['is_admin'] == 1) 
            ? '../back_office/dashboard.php' 
            : 'userprofile.php';
        
        return [
            'success' => true,
            'message' => 'Login successful!',
            'redirect' => $redirectUrl,
            'is_admin' => $user['is_admin']
        ];
    }
    
    /**
     * Validate sign-in input
     * @param array $data
     * @return bool
     */
    private function validateSignInInput($data) {
        $this->errors = [];
        
        // Check if email exists and is valid
        if (empty($data['email'])) {
            $this->errors[] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Invalid email format.';
        }
        
        // Check if password exists
        if (empty($data['password'])) {
            $this->errors[] = 'Password is required.';
        } elseif (strlen($data['password']) < 8) {
            $this->errors[] = 'Password must be at least 8 characters long.';
        }
        
        return empty($this->errors);
    }
    
    /**
     * Handle logout
     */
    public function logout() {
        // Destroy all session data
        $_SESSION = array();
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        return [
            'success' => true,
            'redirect' => 'sign-in.php'
        ];
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Check if user is admin
     * @return bool
     */
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    /**
     * Require login (redirect to sign-in if not logged in)
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: sign-in.php');
            exit();
        }
    }
    
    /**
     * Require admin (redirect to sign-in if not admin)
     */
    public function requireAdmin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../front_office/sign-in.php');
            exit();
        }
        
        if (!$this->isAdmin()) {
            header('Location: ../front_office/userprofile.php');
            exit();
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $authController = new AuthController($pdo);
    
    switch ($_POST['action']) {
        case 'signin':
            $response = $authController->signIn($_POST);
            echo json_encode($response);
            break;
            
        case 'logout':
            $response = $authController->logout();
            echo json_encode($response);
            break;
            
        default:
            echo json_encode(['success' => false, 'errors' => ['Invalid action']]);
    }
    exit();
}
?>
