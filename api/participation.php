<?php
/**
 * API Endpoint pour les participations
 * Gère l'upload de fichiers et la création de participations
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Participation.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/Evenement.php';

/**
 * Upload file function
 */
function uploadFile($file) {
    $upload_dir = __DIR__ . '/../uploads/';
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            return ['success' => false, 'message' => 'Impossible de créer le dossier uploads'];
        }
    }
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        chmod($upload_dir, 0777);
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip'];
    
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé: ' . $extension];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Fichier trop volumineux (max 5MB)'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Erreur lors du déplacement du fichier'];
}

/**
 * Handle Quiz submission
 */
function handleQuizSubmission($db, $data) {
    try {
        $utilisateur = new Utilisateur($db);
        $utilisateur->setNom($data['nom']);
        $utilisateur->setPrenom($data['prenom']);
        $utilisateur->setEmail($data['email']);
        
        if (!$utilisateur->getOrCreate()) {
            return ['success' => false, 'message' => 'Erreur utilisateur'];
        }
        
        $evenement = new Evenement($db);
        $evenement->setId($data['evenement_id']);
        $stmt = $evenement->readWithQuestions();
        
        $questions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($questions[$row['question_id']]) && $row['question_id']) {
                $questions[$row['question_id']] = [
                    'id' => $row['question_id'],
                    'reponses' => []
                ];
            }
            
            if ($row['reponse_id']) {
                $questions[$row['question_id']]['reponses'][$row['reponse_id']] = [
                    'id' => $row['reponse_id'],
                    'est_correcte' => $row['est_correcte']
                ];
            }
        }
        
        $score = 0;
        $total = count($questions);
        $reponses_details = [];
        
        foreach ($data['reponses'] as $question_id => $reponse_id) {
            $est_correcte = false;
            
            if (isset($questions[$question_id]['reponses'][$reponse_id])) {
                $est_correcte = (bool)$questions[$question_id]['reponses'][$reponse_id]['est_correcte'];
                if ($est_correcte) {
                    $score++;
                }
            }
            
            $reponses_details[] = [
                'question_id' => $question_id,
                'reponse_id' => $reponse_id,
                'est_correcte' => $est_correcte
            ];
        }
        
        $result = $utilisateur->enregistrerResultatQuiz(
            $data['evenement_id'],
            $score,
            $total,
            $reponses_details
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'score' => $score,
                'total' => $total,
                'pourcentage' => $total > 0 ? round(($score / $total) * 100, 2) : 0,
                'resultat_id' => $result['resultat_id']
            ];
        }
        
        return $result;
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Main logic
 */
try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit();
    }
    
    // Handle Quiz submission
    if ($action === 'soumettreQuiz') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
            exit();
        }
        
        echo json_encode(handleQuizSubmission($db, $data));
        exit();
    }
    
    // Handle Participation submission
    if ($action === 'soumettreParticipation' || $action === 'create') {
        // Validate required fields
        $required = ['nom', 'prenom', 'email', 'commentaire', 'evenement_id'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Champs manquants: ' . implode(', ', $missing)
            ]);
            exit();
        }
        
        // Validate data
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $commentaire = trim($_POST['commentaire']);
        $evenement_id = intval($_POST['evenement_id']);
        
        if (strlen($nom) < 2) {
            echo json_encode(['success' => false, 'message' => 'Le nom doit avoir au moins 2 caractères']);
            exit();
        }
        
        if (strlen($prenom) < 2) {
            echo json_encode(['success' => false, 'message' => 'Le prénom doit avoir au moins 2 caractères']);
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email invalide']);
            exit();
        }
        
        if (strlen($commentaire) < 10) {
            echo json_encode(['success' => false, 'message' => 'Le commentaire doit avoir au moins 10 caractères']);
            exit();
        }
        
        // Create or get user
        $utilisateur = new Utilisateur($db);
        $utilisateur->setNom($nom);
        $utilisateur->setPrenom($prenom);
        $utilisateur->setEmail($email);
        
        if (!$utilisateur->getOrCreate()) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de l\'utilisateur']);
            exit();
        }
        
        // Check if event exists
        $evenement = new Evenement($db);
        $evenement->setId($evenement_id);
        
        if (!$evenement->readOne()) {
            echo json_encode(['success' => false, 'message' => 'Événement introuvable']);
            exit();
        }
        
        // Handle file upload
        $fichier_url = null;
        
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['fichier']);
            
            if ($uploadResult['success']) {
                $fichier_url = $uploadResult['filename'];
            } else {
                echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
                exit();
            }
        }
        
        // Create participation
        $participation = new Participation($db);
        $participation->setUtilisateurId($utilisateur->getId());
        $participation->setEvenementId($evenement_id);
        $participation->setCommentaire($commentaire);
        $participation->setFichierUrl($fichier_url);
        $participation->setStatut('en_attente');
        
        if ($participation->create()) {
            echo json_encode([
                'success' => true,
                'message' => 'Participation enregistrée avec succès',
                'participation_id' => $participation->getId(),
                'fichier' => $fichier_url
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de la participation']);
        }
        exit();
    }
    
    // Action: getByEmail - Get participations by user email
    if ($action === 'getByEmail') {
        $email = $_GET['email'] ?? '';
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email requis']);
            exit();
        }
        
        // Get user by email
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => true, 'data' => []]);
            exit();
        }
        
        // Get participations for this user
        $stmt = $db->prepare("
            SELECT p.*, e.titre as evenement_titre, e.type as evenement_type
            FROM participations p
            LEFT JOIN evenements e ON p.evenement_id = e.id
            WHERE p.utilisateur_id = :user_id
            ORDER BY p.date_participation DESC
        ");
        $stmt->execute([':user_id' => $user['id']]);
        $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $participations]);
        exit();
    }
    
    // Unknown action
    echo json_encode(['success' => false, 'message' => 'Action non reconnue: ' . $action]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
