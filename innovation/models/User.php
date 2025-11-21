<?php
/**
 * User Model
 * Handles all database operations related to users
 */
class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
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
     * Check if user is admin
     * @param int $userId
     * @return bool
     */
    public function isAdmin($userId) {
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
    public function create($data) {
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
?>
