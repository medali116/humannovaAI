<?php
/**
 * Admin Idea Controller
 * Handles CRUD operations for ideas in the admin panel with validation
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Idea.php';

class AdminIdeaController {
    private $ideaModel;
    private $errors = [];
    
    public function __construct($pdo) {
        $this->ideaModel = new Idea($pdo);
    }
    
    /**
     * Create new idea (Admin can create for any user)
     * @param array $postData
     * @return array Response with status and message
     */
    public function createIdea($postData) {
        // Validate input
        if (!$this->validateIdeaInput($postData, true)) {
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
        
        // Check if user exists
        if (empty($postData['utilisateur_id'])) {
            return [
                'success' => false,
                'errors' => ['User selection is required.']
            ];
        }
        
        $data = [
            'utilisateur_id' => $postData['utilisateur_id'],
            'titre' => trim($postData['titre']),
            'description' => trim($postData['description'])
        ];
        
        $ideaId = $this->ideaModel->create($data);
        
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
     * Update idea (Admin can update any idea)
     * @param array $postData
     * @return array Response with status and message
     */
    public function updateIdea($postData) {
        // Check if idea ID is provided
        if (empty($postData['idea_id'])) {
            return [
                'success' => false,
                'errors' => ['Idea ID is required.']
            ];
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
        
        $result = $this->ideaModel->update($postData['idea_id'], $data);
        
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
     * Delete idea (Admin can delete any idea)
     * @param int $ideaId
     * @return array Response with status and message
     */
    public function deleteIdea($ideaId) {
        if (empty($ideaId)) {
            return [
                'success' => false,
                'errors' => ['Idea ID is required.']
            ];
        }
        
        try {
            global $pdo;
            $stmt = $pdo->prepare("DELETE FROM idee WHERE id = :id");
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
    }
    
    /**
     * Get all ideas with user information
     * @return array
     */
    public function getAllIdeas() {
        return $this->ideaModel->getAllIdeas();
    }
    
    /**
     * Get all users for dropdown
     * @return array
     */
    public function getAllUsers() {
        try {
            global $pdo;
            $stmt = $pdo->query("SELECT id, fullname, username FROM utilisateurs ORDER BY fullname");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validate idea input
     * @param array $data
     * @param bool $checkUserId Whether to validate user_id field
     * @return bool
     */
    private function validateIdeaInput($data, $checkUserId = false) {
        $this->errors = [];
        
        // Validate user ID for add operation
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
    
    // Check if user is admin
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        echo json_encode(['success' => false, 'errors' => ['Unauthorized access. Admin privileges required.']]);
        exit();
    }
    
    $adminController = new AdminIdeaController($pdo);
    
    switch ($_POST['action']) {
        case 'create':
            $response = $adminController->createIdea($_POST);
            echo json_encode($response);
            break;
            
        case 'update':
            $response = $adminController->updateIdea($_POST);
            echo json_encode($response);
            break;
            
        case 'delete':
            $ideaId = $_POST['idea_id'] ?? null;
            $response = $adminController->deleteIdea($ideaId);
            echo json_encode($response);
            break;
            
        default:
            echo json_encode(['success' => false, 'errors' => ['Invalid action']]);
    }
    exit();
}
?>
