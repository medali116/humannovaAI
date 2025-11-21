<?php
/**
 * Idea Controller
 * Handles CRUD operations for ideas with validation
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Idea.php';

class IdeaController {
    private $ideaModel;
    private $errors = [];
    
    public function __construct($pdo) {
        $this->ideaModel = new Idea($pdo);
    }
    
    /**
     * Create new idea
     * @param array $postData
     * @param int $userId
     * @return array Response with status and message
     */
    public function createIdea($postData, $userId) {
        // Validate input
        if (!$this->validateIdeaInput($postData)) {
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
        
        $data = [
            'utilisateur_id' => $userId,
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
     * Update idea
     * @param array $postData
     * @param int $userId
     * @return array Response with status and message
     */
    public function updateIdea($postData, $userId) {
        // Check if idea ID is provided
        if (empty($postData['idea_id'])) {
            return [
                'success' => false,
                'errors' => ['Idea ID is required.']
            ];
        }
        
        $ideaId = $postData['idea_id'];
        
        // Check if user owns the idea
        if (!$this->ideaModel->isOwner($ideaId, $userId)) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to edit this idea.']
            ];
        }
        
        // Validate input
        if (!$this->validateIdeaInput($postData)) {
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
        
        $data = [
            'titre' => trim($postData['titre']),
            'description' => trim($postData['description'])
        ];
        
        $result = $this->ideaModel->update($ideaId, $data);
        
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
     * @param int $ideaId
     * @param int $userId
     * @return array Response with status and message
     */
    public function deleteIdea($ideaId, $userId) {
        if (empty($ideaId)) {
            return [
                'success' => false,
                'errors' => ['Idea ID is required.']
            ];
        }
        
        $result = $this->ideaModel->delete($ideaId, $userId);
        
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
    
    /**
     * Validate idea input
     * @param array $data
     * @return bool
     */
    private function validateIdeaInput($data) {
        $this->errors = [];
        
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
    
    switch ($_POST['action']) {
        case 'create':
            $response = $ideaController->createIdea($_POST, $userId);
            echo json_encode($response);
            break;
            
        case 'update':
            $response = $ideaController->updateIdea($_POST, $userId);
            echo json_encode($response);
            break;
            
        case 'delete':
            $ideaId = $_POST['idea_id'] ?? null;
            $response = $ideaController->deleteIdea($ideaId, $userId);
            echo json_encode($response);
            break;
            
        default:
            echo json_encode(['success' => false, 'errors' => ['Invalid action']]);
    }
    exit();
}
?>
