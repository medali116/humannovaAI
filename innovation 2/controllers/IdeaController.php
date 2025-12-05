<?php
/**
 * Unified Idea Controller
 * Handles CRUD operations for ideas with validation
 * Supports both regular users and admin operations
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Idea.php';

class IdeaController {
    private $pdo;
    private $errors = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if current user is admin
     * @return bool
     */
    private function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    /**
     * Create new idea
     * Supports both user (for themselves) and admin (for any user) creation
     * @param array $postData
     * @param int|null $userId User ID (required for regular users, optional for admin)
     * @return array Response with status and message
     */
    public function createIdea($postData, $userId = null) {
        // If admin and utilisateur_id is provided, use it; otherwise use $userId
        if ($this->isAdmin() && !empty($postData['utilisateur_id'])) {
            $targetUserId = $postData['utilisateur_id'];
            $checkUserId = true; // Admin needs to select a user
        } else {
            $targetUserId = $userId;
            $checkUserId = false; // Regular user uses their own ID
        }
        
        // Validate input
        if (!$this->validateIdeaInput($postData, $checkUserId)) {
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
        
        if (empty($targetUserId)) {
            return [
                'success' => false,
                'errors' => ['User ID is required.']
            ];
        }
        
        $data = [
            'utilisateur_id' => $targetUserId,
            'titre' => trim($postData['titre']),
            'description' => trim($postData['description'])
        ];
        
        $ideaId = $this->createIdeaRecord($data);
        
        if ($ideaId) {
            return [
                'success' => true,
                'message' => 'Idea created successfully!',
                'idea_id' => $ideaId
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Failed to create idea. Please try again.']
            ];
        }
    }
    
    /**
     * Update idea
     * Admin can update any idea, users can only update their own
     * @param array $postData
     * @param int|null $userId User ID (required for regular users, optional for admin)
     * @return array Response with status and message
     */
    public function updateIdea($postData, $userId = null) {
        // Check if idea ID is provided
        if (empty($postData['idea_id'])) {
            return [
                'success' => false,
                'errors' => ['Idea ID is required.']
            ];
        }
        
        $ideaId = $postData['idea_id'];
        
        // Check permissions: Admin can edit any idea, regular user only their own
        if (!$this->isAdmin()) {
            if (empty($userId) || !$this->isIdeaOwner($ideaId, $userId)) {
                return [
                    'success' => false,
                    'errors' => ['You do not have permission to edit this idea.']
                ];
            }
        }
        
        // Validate input
        if (!$this->validateIdeaInput($postData, false)) {
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
        
        $data = [
            'titre' => trim($postData['titre']),
            'description' => trim($postData['description'])
        ];
        
        $result = $this->updateIdeaRecord($ideaId, $data);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Idea updated successfully!'
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Failed to update idea. Please try again.']
            ];
        }
    }
    
    /**
     * Delete idea
     * Admin can delete any idea, users can only delete their own
     * @param int $ideaId
     * @param int|null $userId User ID (required for regular users, optional for admin)
     * @return array Response with status and message
     */
    public function deleteIdea($ideaId, $userId = null) {
        if (empty($ideaId)) {
            return [
                'success' => false,
                'errors' => ['Idea ID is required.']
            ];
        }
        
        // Check permissions: Admin can delete any idea, regular user only their own
        if ($this->isAdmin()) {
            // Admin can delete any idea directly
            try {
                $stmt = $this->pdo->prepare("DELETE FROM idee WHERE id = :id");
                $result = $stmt->execute(['id' => $ideaId]);
                
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Idea deleted successfully!'
                    ];
                } else {
                    return [
                        'success' => false,
                        'errors' => ['Failed to delete idea.']
                    ];
                }
            } catch (PDOException $e) {
                error_log("Error deleting idea: " . $e->getMessage());
                return [
                    'success' => false,
                    'errors' => ['Database error occurred.']
                ];
            }
        } else {
            // Regular user - check ownership
            $result = $this->deleteIdeaWithOwnershipCheck($ideaId, $userId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Idea deleted successfully!'
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => ['Failed to delete idea. You may not have permission.']
                ];
            }
        }
    }
    
    // IDEA CRUD OPERATIONS (moved from Idea model)
    
    /**
     * Get all ideas with user information
     * @return array
     */
    public function getAllIdeas() {
        try {
            $stmt = $this->pdo->query("SELECT idee.*, utilisateurs.fullname, utilisateurs.username 
                                       FROM idee 
                                       JOIN utilisateurs ON idee.utilisateur_id = utilisateurs.id 
                                       ORDER BY idee.date_creation DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching ideas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get ideas by user ID
     * @param int $userId
     * @return array
     */
    public function getIdeasByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT idee.*, utilisateurs.fullname, utilisateurs.username 
                                         FROM idee 
                                         JOIN utilisateurs ON idee.utilisateur_id = utilisateurs.id 
                                         WHERE idee.utilisateur_id = :user_id 
                                         ORDER BY idee.date_creation DESC");
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching user ideas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get idea by ID
     * @param int $id
     * @return array|false
     */
    public function getIdeaById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT idee.*, utilisateurs.fullname, utilisateurs.username 
                                         FROM idee 
                                         JOIN utilisateurs ON idee.utilisateur_id = utilisateurs.id 
                                         WHERE idee.id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching idea: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new idea record
     * @param array $data
     * @return int|false Idea ID or false on failure
     */
    private function createIdeaRecord($data) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO idee (utilisateur_id, titre, description) 
                                         VALUES (:utilisateur_id, :titre, :description)");
            $stmt->execute([
                'utilisateur_id' => $data['utilisateur_id'],
                'titre' => $data['titre'],
                'description' => $data['description']
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating idea: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update idea record
     * @param int $id
     * @param array $data
     * @return bool
     */
    private function updateIdeaRecord($id, $data) {
        try {
            $stmt = $this->pdo->prepare("UPDATE idee 
                                         SET titre = :titre, description = :description 
                                         WHERE id = :id");
            return $stmt->execute([
                'titre' => $data['titre'],
                'description' => $data['description'],
                'id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating idea: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete idea with ownership check
     * @param int $id
     * @param int $userId User ID to verify ownership
     * @return bool
     */
    private function deleteIdeaWithOwnershipCheck($id, $userId) {
        try {
            // First check if the idea belongs to the user
            $stmt = $this->pdo->prepare("SELECT utilisateur_id FROM idee WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $idea = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$idea || $idea['utilisateur_id'] != $userId) {
                return false; // Idea doesn't exist or doesn't belong to user
            }
            
            // Delete the idea
            $stmt = $this->pdo->prepare("DELETE FROM idee WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting idea: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user owns the idea
     * @param int $ideaId
     * @param int $userId
     * @return bool
     */
    public function isIdeaOwner($ideaId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT utilisateur_id FROM idee WHERE id = :id");
            $stmt->execute(['id' => $ideaId]);
            $idea = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $idea && $idea['utilisateur_id'] == $userId;
        } catch (PDOException $e) {
            error_log("Error checking idea ownership: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all users for dropdown (admin only)
     * @return array
     */
    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("SELECT id, fullname, username FROM utilisateurs ORDER BY fullname");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validate idea input
     * @param array $data
     * @param bool $checkUserId Whether to validate user_id field (for admin creating ideas)
     * @return bool
     */
    private function validateIdeaInput($data, $checkUserId = false) {
        $this->errors = [];
        
        // Validate user ID for admin add operation
        if ($checkUserId) {
            if (empty($data['utilisateur_id'])) {
                $this->errors[] = 'User selection is required.';
            }
        }
        
        // Validate title
        if (empty($data['titre'])) {
            $this->errors[] = 'Title is required.';
        } else {
            $titre = trim($data['titre']);
            
            // Check for numbers
            if (preg_match('/\d/', $titre)) {
                $this->errors[] = 'Title must not contain numbers.';
            }
            
            // Check for symbols (allow only letters, spaces, and basic punctuation)
            if (preg_match('/[^a-zA-Z\s\'\-àáâãäåèéêëìíîïòóôõöùúûüçñÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÇÑ]/', $titre)) {
                $this->errors[] = 'Title must not contain symbols or special characters.';
            }
            
            // Check word count (minimum 3 words)
            $wordCount = str_word_count($titre);
            if ($wordCount < 3) {
                $this->errors[] = 'Title must contain at least 3 words.';
            }
        }
        
        // Validate description
        if (empty($data['description'])) {
            $this->errors[] = 'Description is required.';
        } else {
            $description = trim($data['description']);
            
            // Check word count (minimum 10 words)
            $wordCount = str_word_count($description);
            if ($wordCount < 10) {
                $this->errors[] = 'Description must contain at least 10 words.';
            }
        }
        
        return empty($this->errors);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'errors' => ['You must be logged in to perform this action.']]);
        exit();
    }
    
    $ideaController = new IdeaController($pdo);
    $userId = $_SESSION['user_id'];
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    
    switch ($_POST['action']) {
        case 'create':
            // Admin can create for any user, regular user creates for themselves
            $response = $ideaController->createIdea($_POST, $userId);
            echo json_encode($response);
            break;
            
        case 'update':
            // Admin can update any idea, regular user only their own
            $response = $ideaController->updateIdea($_POST, $isAdmin ? null : $userId);
            echo json_encode($response);
            break;
            
        case 'delete':
            $ideaId = $_POST['idea_id'] ?? null;
            // Admin can delete any idea, regular user only their own
            $response = $ideaController->deleteIdea($ideaId, $isAdmin ? null : $userId);
            echo json_encode($response);
            break;
            
        default:
            echo json_encode(['success' => false, 'errors' => ['Invalid action']]);
    }
    exit();
}
?>
