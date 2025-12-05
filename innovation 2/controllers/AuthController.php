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
    private $pdo;
    private $errors = [];
    private $success = '';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
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
        $user = $this->findByEmail($email);
        
        if (!$user) {
            $this->errors[] = 'Invalid email or password.';
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
        
        // Verify password
        if (!$this->verifyPassword($password, $user['password'])) {
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
    
    // USER CRUD OPERATIONS (moved from User model)
    
    /**
     * Find user by email
     * @param string $email
     * @return array|false User data or false if not found
     */
    public function findByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find user by ID
     * @param int $id
     * @return array|false User data or false if not found
     */
    public function findById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify user password
     * @param string $plainPassword
     * @param string $hashedPassword
     * @return bool
     */
    public function verifyPassword($plainPassword, $hashedPassword) {
        return password_verify($plainPassword, $hashedPassword);
    }
    
    /**
     * Check if user is admin by ID
     * @param int $userId
     * @return bool
     */
    public function isUserAdmin($userId) {
        $user = $this->findById($userId);
        return $user && $user['is_admin'] == 1;
    }
    
    /**
     * Update user profile
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function updateProfile($userId, $data) {
        try {
            $stmt = $this->pdo->prepare("UPDATE utilisateurs 
                                        SET fullname = :fullname, 
                                            username = :username, 
                                            email = :email 
                                        WHERE id = :id");
            return $stmt->execute([
                'fullname' => $data['fullname'],
                'username' => $data['username'],
                'email' => $data['email'],
                'id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error updating user profile: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user password
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword($userId, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE utilisateurs SET password = :password WHERE id = :id");
            return $stmt->execute([
                'password' => $hashedPassword,
                'id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error updating password: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new user
     * @param array $data
     * @return int|false User ID or false on failure
     */
    public function createUser($data) {
        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO utilisateurs (username, fullname, email, password, is_admin) 
                                        VALUES (:username, :fullname, :email, :password, :is_admin)");
            $stmt->execute([
                'username' => $data['username'],
                'fullname' => $data['fullname'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'is_admin' => $data['is_admin'] ?? 0
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
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
