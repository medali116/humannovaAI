<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Investment.php';

class InvestmentController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new investment
    public function createInvestment($ideeId, $utilisateurId, $montant) {
        try {
            // Validate minimum amount
            if ($montant < 500) {
                return ['success' => false, 'message' => 'Minimum investment amount is 500 DT.'];
            }
            
            // Check if user is trying to invest in their own idea
            if ($this->isOwnIdea($ideeId, $utilisateurId)) {
                return ['success' => false, 'message' => 'You cannot invest in your own idea.'];
            }
            
            // Check if user already has a pending investment for this idea
            if ($this->hasExistingInvestment($ideeId, $utilisateurId)) {
                return ['success' => false, 'message' => 'You already have an investment request for this idea.'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO investissements (idee_id, utilisateur_id, montant, statut, date_demande) 
                VALUES (?, ?, ?, 'en_attente', NOW())
            ");
            
            $result = $stmt->execute([$ideeId, $utilisateurId, $montant]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Investment request submitted successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to submit investment request.'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get all investments by user
    public function getInvestmentsByUser($utilisateurId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.*, 
                       id.titre as idea_title, 
                       id.description as idea_description,
                       u.fullname as idea_author,
                       DATE_FORMAT(i.date_demande, '%M %d, %Y at %H:%i') as formatted_date
                FROM investissements i
                JOIN idee id ON i.idee_id = id.id
                JOIN utilisateurs u ON id.utilisateur_id = u.id
                WHERE i.utilisateur_id = ?
                ORDER BY i.date_demande DESC
            ");
            
            $stmt->execute([$utilisateurId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get investment by ID
    public function getInvestmentById($investmentId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.*, 
                       id.titre as idea_title, 
                       id.description as idea_description,
                       u.fullname as idea_author
                FROM investissements i
                JOIN idee id ON i.idee_id = id.id
                JOIN utilisateurs u ON id.utilisateur_id = u.id
                WHERE i.id = ?
            ");
            
            $stmt->execute([$investmentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Update investment (only if status is still 'en_attente')
    public function updateInvestment($investmentId, $montant, $utilisateurId) {
        try {
            // Validate minimum amount
            if ($montant < 500) {
                return ['success' => false, 'message' => 'Minimum investment amount is 500 DT.'];
            }
            
            // Check if investment belongs to user and is still pending
            $stmt = $this->pdo->prepare("
                SELECT statut FROM investissements 
                WHERE id = ? AND utilisateur_id = ?
            ");
            $stmt->execute([$investmentId, $utilisateurId]);
            $investment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$investment) {
                return ['success' => false, 'message' => 'Investment not found.'];
            }
            
            if ($investment['statut'] !== 'en_attente') {
                return ['success' => false, 'message' => 'Cannot modify investment. Status: ' . $investment['statut']];
            }
            
            $updateStmt = $this->pdo->prepare("
                UPDATE investissements 
                SET montant = ? 
                WHERE id = ? AND utilisateur_id = ?
            ");
            
            $result = $updateStmt->execute([$montant, $investmentId, $utilisateurId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Investment updated successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to update investment.'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Delete investment (only if status is still 'en_attente')
    public function deleteInvestment($investmentId, $utilisateurId) {
        try {
            // Check if investment belongs to user and is still pending
            $stmt = $this->pdo->prepare("
                SELECT statut FROM investissements 
                WHERE id = ? AND utilisateur_id = ?
            ");
            $stmt->execute([$investmentId, $utilisateurId]);
            $investment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$investment) {
                return ['success' => false, 'message' => 'Investment not found.'];
            }
            
            if ($investment['statut'] !== 'en_attente') {
                return ['success' => false, 'message' => 'Cannot delete investment. Status: ' . $investment['statut']];
            }
            
            $deleteStmt = $this->pdo->prepare("
                DELETE FROM investissements 
                WHERE id = ? AND utilisateur_id = ?
            ");
            
            $result = $deleteStmt->execute([$investmentId, $utilisateurId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Investment deleted successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete investment.'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get idea details for investment form
    public function getIdeaForInvestment($ideaId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id.*, u.fullname as author_name
                FROM idee id
                JOIN utilisateurs u ON id.utilisateur_id = u.id
                WHERE id.id = ?
            ");
            
            $stmt->execute([$ideaId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Check if user owns the idea
    private function isOwnIdea($ideaId, $utilisateurId) {
        try {
            $stmt = $this->pdo->prepare("SELECT utilisateur_id FROM idee WHERE id = ?");
            $stmt->execute([$ideaId]);
            $idea = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $idea && $idea['utilisateur_id'] == $utilisateurId;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Check if user already has an investment for this idea
    private function hasExistingInvestment($ideaId, $utilisateurId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM investissements 
                WHERE idee_id = ? AND utilisateur_id = ?
            ");
            $stmt->execute([$ideaId, $utilisateurId]);
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Admin method to update any investment (unrestricted)
    public function adminUpdateInvestment($investmentId, $montant, $statut) {
        try {
            // Validate minimum amount
            if ($montant < 500) {
                return ['success' => false, 'message' => 'Minimum investment amount is 500 DT.'];
            }
            
            // Validate status
            $validStatuses = ['en_attente', 'accepte', 'refuse'];
            if (!in_array($statut, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid status value.'];
            }
            
            // Update investment without ownership or status restrictions
            $stmt = $this->pdo->prepare("
                UPDATE investissements 
                SET montant = ?, statut = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$montant, $statut, $investmentId]);
            
            if ($result) {
                $rowsAffected = $stmt->rowCount();
                if ($rowsAffected > 0) {
                    return ['success' => true, 'message' => 'Investment updated successfully by admin!'];
                } else {
                    return ['success' => false, 'message' => 'Investment not found.'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to update investment.'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Admin method to delete any investment (unrestricted)
    public function adminDeleteInvestment($investmentId) {
        try {
            // Delete investment without ownership or status restrictions
            $stmt = $this->pdo->prepare("DELETE FROM investissements WHERE id = ?");
            $result = $stmt->execute([$investmentId]);
            
            if ($result) {
                $rowsAffected = $stmt->rowCount();
                if ($rowsAffected > 0) {
                    return ['success' => true, 'message' => 'Investment deleted successfully by admin!'];
                } else {
                    return ['success' => false, 'message' => 'Investment not found.'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to delete investment.'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Check if user is admin
    private function isAdmin($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT is_admin FROM utilisateurs WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user && $user['is_admin'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Handle AJAX requests
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'create':
                    session_start();
                    if (!isset($_SESSION['user_id'])) {
                        echo json_encode(['success' => false, 'message' => 'Please log in to invest.']);
                        return;
                    }
                    
                    $ideeId = $_POST['idee_id'] ?? '';
                    $montant = $_POST['montant'] ?? '';
                    
                    if (empty($ideeId) || empty($montant)) {
                        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
                        return;
                    }
                    
                    $result = $this->createInvestment($ideeId, $_SESSION['user_id'], $montant);
                    echo json_encode($result);
                    break;
                    
                case 'update':
                    session_start();
                    if (!isset($_SESSION['user_id'])) {
                        echo json_encode(['success' => false, 'message' => 'Please log in.']);
                        return;
                    }
                    
                    $investmentId = $_POST['investment_id'] ?? '';
                    $montant = $_POST['montant'] ?? '';
                    
                    if (empty($investmentId) || empty($montant)) {
                        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
                        return;
                    }
                    
                    $result = $this->updateInvestment($investmentId, $montant, $_SESSION['user_id']);
                    echo json_encode($result);
                    break;
                    
                case 'delete':
                    session_start();
                    if (!isset($_SESSION['user_id'])) {
                        echo json_encode(['success' => false, 'message' => 'Please log in.']);
                        return;
                    }
                    
                    $investmentId = $_POST['investment_id'] ?? '';
                    
                    if (empty($investmentId)) {
                        echo json_encode(['success' => false, 'message' => 'Investment ID is required.']);
                        return;
                    }
                    
                    $result = $this->deleteInvestment($investmentId, $_SESSION['user_id']);
                    echo json_encode($result);
                    break;
                    
                case 'get_idea':
                    $ideaId = $_POST['idea_id'] ?? '';
                    if (empty($ideaId)) {
                        echo json_encode(['success' => false, 'message' => 'Idea ID is required.']);
                        return;
                    }
                    
                    $idea = $this->getIdeaForInvestment($ideaId);
                    if ($idea) {
                        echo json_encode(['success' => true, 'idea' => $idea]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Idea not found.']);
                    }
                    break;
                    
                case 'admin_update':
                    session_start();
                    if (!isset($_SESSION['user_id'])) {
                        echo json_encode(['success' => false, 'message' => 'Please log in.']);
                        return;
                    }
                    
                    // Debug admin check
                    $isAdminCheck = $this->isAdmin($_SESSION['user_id']);
                    $sessionAdmin = $_SESSION['is_admin'] ?? 'not_set';
                    
                    // Check if user is admin
                    if (!$isAdminCheck) {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Admin privileges required.', 
                            'debug' => [
                                'user_id' => $_SESSION['user_id'],
                                'session_is_admin' => $sessionAdmin,
                                'db_admin_check' => $isAdminCheck
                            ]
                        ]);
                        return;
                    }
                    
                    $investmentId = $_POST['investment_id'] ?? '';
                    $montant = $_POST['montant'] ?? '';
                    $statut = $_POST['statut'] ?? '';
                    
                    if (empty($investmentId) || empty($montant) || empty($statut)) {
                        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
                        return;
                    }
                    
                    $result = $this->adminUpdateInvestment($investmentId, $montant, $statut);
                    echo json_encode($result);
                    break;
                    
                case 'debug_session':
                    session_start();
                    echo json_encode([
                        'success' => true,
                        'session_data' => [
                            'user_id' => $_SESSION['user_id'] ?? 'not_set',
                            'is_admin' => $_SESSION['is_admin'] ?? 'not_set',
                            'fullname' => $_SESSION['fullname'] ?? 'not_set'
                        ],
                        'db_check' => isset($_SESSION['user_id']) ? $this->isAdmin($_SESSION['user_id']) : 'no_user_id'
                    ]);
                    break;
                    
                case 'admin_delete':
                    session_start();
                    if (!isset($_SESSION['user_id'])) {
                        echo json_encode(['success' => false, 'message' => 'Please log in.']);
                        return;
                    }
                    
                    // Check if user is admin
                    if (!$this->isAdmin($_SESSION['user_id'])) {
                        echo json_encode(['success' => false, 'message' => 'Admin privileges required.']);
                        return;
                    }
                    
                    $investmentId = $_POST['investment_id'] ?? '';
                    
                    if (empty($investmentId)) {
                        echo json_encode(['success' => false, 'message' => 'Investment ID is required.']);
                        return;
                    }
                    
                    $result = $this->adminDeleteInvestment($investmentId);
                    echo json_encode($result);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
                    break;
            }
        }
    }
}

// Handle direct requests to this file
if (basename($_SERVER['PHP_SELF']) === 'InvestmentController.php') {
    $investmentController = new InvestmentController($pdo);
    $investmentController->handleRequest();
}
?>