<?php
/**
 * Contrôleur Participation
 * Gestion de la logique métier pour les participations
 * CRUD complet avec jointure (utilisateurs et evenements)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Participation.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/Evenement.php';

class ParticipationController {
    private $db;
    private $participation;
    private $utilisateur;
    private $evenement;

    /**
     * Constructeur
     */
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->participation = new Participation($this->db);
        $this->utilisateur = new Utilisateur($this->db);
        $this->evenement = new Evenement($this->db);
    }

    /**
     * Récupérer toutes les participations avec jointure
     * @return array
     */
    public function getAllParticipations() {
        $stmt = $this->participation->readAll();
        $participations = array();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $participations[] = $row;
        }
        
        return $participations;
    }

    /**
     * Récupérer une participation par ID avec jointure
     * @param int $id
     * @return array|null
     */
    public function getParticipationById($id) {
        $this->participation->setId($id);
        return $this->participation->readOneWithJoin();
    }

    /**
     * Créer une participation
     * @param array $data
     * @param array|null $file
     * @return array
     */
    public function createParticipation($data, $file = null) {
        // Validation côté serveur
        $errors = $this->validateParticipation($data);
        if(!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }

        try {
            // Créer ou récupérer l'utilisateur
            $this->utilisateur->setNom($data['nom']);
            $this->utilisateur->setPrenom($data['prenom']);
            $this->utilisateur->setEmail($data['email']);
            
            if(!$this->utilisateur->getOrCreate()) {
                return array('success' => false, 'message' => 'Erreur lors de la création de l\'utilisateur');
            }

            // Vérifier que l'événement existe
            $this->evenement->setId($data['evenement_id']);
            if(!$this->evenement->readOne()) {
                return array('success' => false, 'message' => 'Événement introuvable');
            }

            // Gérer l'upload du fichier si présent
            $fichier_url = null;
            if($file && $file['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->uploadFichier($file);
                if($uploadResult['success']) {
                    $fichier_url = $uploadResult['filename'];
                } else {
                    return array('success' => false, 'message' => $uploadResult['message']);
                }
            }

            // Créer la participation avec les setters
            $this->participation->setUtilisateurId($this->utilisateur->getId());
            $this->participation->setEvenementId($data['evenement_id']);
            $this->participation->setCommentaire($data['commentaire']);
            $this->participation->setFichierUrl($fichier_url);
            $this->participation->setStatut('en_attente');

            if($this->participation->create()) {
                return array(
                    'success' => true, 
                    'message' => 'Participation enregistrée avec succès',
                    'participation_id' => $this->participation->getId()
                );
            }

            return array('success' => false, 'message' => 'Erreur lors de l\'enregistrement');

        } catch(Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Mettre à jour une participation
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateParticipation($id, $data) {
        // Validation côté serveur
        $errors = $this->validateParticipationUpdate($data);
        if(!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }

        try {
            $this->participation->setId($id);
            
            // Vérifier que la participation existe
            if(!$this->participation->readOne()) {
                return array('success' => false, 'message' => 'Participation introuvable');
            }

            // Mettre à jour avec les setters
            if(isset($data['commentaire'])) {
                $this->participation->setCommentaire($data['commentaire']);
            }
            if(isset($data['statut'])) {
                $this->participation->setStatut($data['statut']);
            }

            if($this->participation->update()) {
                return array('success' => true, 'message' => 'Participation mise à jour avec succès');
            }

            return array('success' => false, 'message' => 'Erreur lors de la mise à jour');

        } catch(Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Mettre à jour le statut d'une participation
     * @param int $id
     * @param string $statut
     * @return array
     */
    public function updateStatut($id, $statut) {
        $allowed = ['en_attente', 'approuve', 'rejete'];
        if(!in_array($statut, $allowed)) {
            return array('success' => false, 'message' => 'Statut invalide');
        }

        $this->participation->setId($id);
        $this->participation->setStatut($statut);

        if($this->participation->updateStatut()) {
            return array('success' => true, 'message' => 'Statut mis à jour avec succès');
        }

        return array('success' => false, 'message' => 'Erreur lors de la mise à jour du statut');
    }

    /**
     * Supprimer une participation
     * @param int $id
     * @return array
     */
    public function deleteParticipation($id) {
        $this->participation->setId($id);
        
        // Récupérer les infos pour supprimer le fichier associé
        if($this->participation->readOne()) {
            $fichier = $this->participation->getFichierUrl();
            if($fichier) {
                $filepath = __DIR__ . '/../uploads/' . $fichier;
                if(file_exists($filepath)) {
                    unlink($filepath);
                }
            }
        }

        if($this->participation->delete()) {
            return array('success' => true, 'message' => 'Participation supprimée avec succès');
        }

        return array('success' => false, 'message' => 'Erreur lors de la suppression');
    }

    /**
     * Rechercher des participations
     * @param string $keyword
     * @return array
     */
    public function searchParticipations($keyword) {
        $stmt = $this->participation->search($keyword);
        $participations = array();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $participations[] = $row;
        }
        
        return $participations;
    }

    /**
     * Récupérer les participations par statut
     * @param string $statut
     * @return array
     */
    public function getParticipationsByStatut($statut) {
        $stmt = $this->participation->readByStatut($statut);
        $participations = array();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $participations[] = $row;
        }
        
        return $participations;
    }

    /**
     * Récupérer les participations par événement
     * @param int $evenement_id
     * @return array
     */
    public function getParticipationsByEvenement($evenement_id) {
        $this->participation->setEvenementId($evenement_id);
        $stmt = $this->participation->readByEvenement();
        $participations = array();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $participations[] = $row;
        }
        
        return $participations;
    }

    /**
     * Obtenir les statistiques
     * @return array
     */
    public function getStatistics() {
        return $this->participation->getStatistics();
    }

    /**
     * Validation des données de participation (création)
     * @param array $data
     * @return array
     */
    private function validateParticipation($data) {
        $errors = array();

        // Validation du nom
        if(empty($data['nom'])) {
            $errors['nom'] = 'Le nom est obligatoire';
        } elseif(strlen($data['nom']) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caractères';
        } elseif(strlen($data['nom']) > 100) {
            $errors['nom'] = 'Le nom ne peut pas dépasser 100 caractères';
        }

        // Validation du prénom
        if(empty($data['prenom'])) {
            $errors['prenom'] = 'Le prénom est obligatoire';
        } elseif(strlen($data['prenom']) < 2) {
            $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères';
        } elseif(strlen($data['prenom']) > 100) {
            $errors['prenom'] = 'Le prénom ne peut pas dépasser 100 caractères';
        }

        // Validation de l'email
        if(empty($data['email'])) {
            $errors['email'] = 'L\'email est obligatoire';
        } elseif(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide';
        } elseif(strlen($data['email']) > 255) {
            $errors['email'] = 'L\'email ne peut pas dépasser 255 caractères';
        }

        // Validation de l'événement
        if(empty($data['evenement_id'])) {
            $errors['evenement_id'] = 'L\'événement est obligatoire';
        } elseif(!is_numeric($data['evenement_id']) || $data['evenement_id'] <= 0) {
            $errors['evenement_id'] = 'ID d\'événement invalide';
        }

        // Validation du commentaire
        if(empty($data['commentaire'])) {
            $errors['commentaire'] = 'Le commentaire est obligatoire';
        } elseif(strlen($data['commentaire']) < 10) {
            $errors['commentaire'] = 'Le commentaire doit contenir au moins 10 caractères';
        } elseif(strlen($data['commentaire']) > 1000) {
            $errors['commentaire'] = 'Le commentaire ne peut pas dépasser 1000 caractères';
        }

        return $errors;
    }

    /**
     * Validation des données de participation (mise à jour)
     * @param array $data
     * @return array
     */
    private function validateParticipationUpdate($data) {
        $errors = array();

        // Validation du commentaire si fourni
        if(isset($data['commentaire'])) {
            if(strlen($data['commentaire']) < 10) {
                $errors['commentaire'] = 'Le commentaire doit contenir au moins 10 caractères';
            } elseif(strlen($data['commentaire']) > 1000) {
                $errors['commentaire'] = 'Le commentaire ne peut pas dépasser 1000 caractères';
            }
        }

        // Validation du statut si fourni
        if(isset($data['statut'])) {
            $allowed = ['en_attente', 'approuve', 'rejete'];
            if(!in_array($data['statut'], $allowed)) {
                $errors['statut'] = 'Statut invalide';
            }
        }

        return $errors;
    }

    /**
     * Upload d'un fichier
     * @param array $file
     * @return array
     */
    private function uploadFichier($file) {
        $upload_dir = __DIR__ . '/../uploads/';
        
        // Créer le dossier s'il n'existe pas
        if(!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Vérifier le type de fichier
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $types_autorises = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip');
        
        if(!in_array($extension, $types_autorises)) {
            return array('success' => false, 'message' => 'Type de fichier non autorisé. Types acceptés: ' . implode(', ', $types_autorises));
        }
        
        // Vérifier la taille (max 5MB)
        if($file['size'] > 5 * 1024 * 1024) {
            return array('success' => false, 'message' => 'Le fichier est trop volumineux (max 5MB)');
        }
        
        // Générer un nom unique
        $nom_fichier = uniqid() . '_' . time() . '.' . $extension;
        $chemin_complet = $upload_dir . $nom_fichier;
        
        // Déplacer le fichier
        if(move_uploaded_file($file['tmp_name'], $chemin_complet)) {
            return array('success' => true, 'filename' => $nom_fichier);
        }
        
        return array('success' => false, 'message' => 'Erreur lors de l\'upload du fichier');
    }

    /**
     * Soumettre un quiz (pour compatibilité)
     * @param array $data
     * @return array
     */
    public function soumettreQuiz($data) {
        try {
            // Créer ou récupérer l'utilisateur
            $this->utilisateur->setNom($data['nom']);
            $this->utilisateur->setPrenom($data['prenom']);
            $this->utilisateur->setEmail($data['email']);
            
            if(!$this->utilisateur->getOrCreate()) {
                return array('success' => false, 'message' => 'Erreur utilisateur');
            }
            
            // Récupérer l'événement et ses questions
            $this->evenement->setId($data['evenement_id']);
            $stmt = $this->evenement->readWithQuestions();
            
            $questions = array();
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if(!isset($questions[$row['question_id']]) && $row['question_id']) {
                    $questions[$row['question_id']] = array(
                        'id' => $row['question_id'],
                        'reponses' => array()
                    );
                }
                
                if($row['reponse_id']) {
                    $questions[$row['question_id']]['reponses'][$row['reponse_id']] = array(
                        'id' => $row['reponse_id'],
                        'est_correcte' => $row['est_correcte']
                    );
                }
            }
            
            // Calculer le score
            $score = 0;
            $total = count($questions);
            $reponses_details = array();
            
            foreach($data['reponses'] as $question_id => $reponse_id) {
                $est_correcte = false;
                
                if(isset($questions[$question_id]['reponses'][$reponse_id])) {
                    $est_correcte = (bool)$questions[$question_id]['reponses'][$reponse_id]['est_correcte'];
                    if($est_correcte) {
                        $score++;
                    }
                }
                
                $reponses_details[] = array(
                    'question_id' => $question_id,
                    'reponse_id' => $reponse_id,
                    'est_correcte' => $est_correcte
                );
            }
            
            // Enregistrer le résultat
            $result = $this->utilisateur->enregistrerResultatQuiz(
                $data['evenement_id'],
                $score,
                $total,
                $reponses_details
            );
            
            if($result['success']) {
                return array(
                    'success' => true,
                    'score' => $score,
                    'total' => $total,
                    'pourcentage' => $total > 0 ? round(($score / $total) * 100, 2) : 0,
                    'resultat_id' => $result['resultat_id']
                );
            }
            
            return $result;
            
        } catch(Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}

// Gestion des requêtes AJAX
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if(!empty($action)) {
    // Only set headers and output JSON if there's an action
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    try {
        $controller = new ParticipationController();
        
        switch($action) {
            case 'getAll':
                echo json_encode($controller->getAllParticipations());
                break;
                
            case 'getOne':
                $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                echo json_encode($controller->getParticipationById($id));
                break;
                
            case 'soumettreParticipation':
                $file = isset($_FILES['fichier']) && $_FILES['fichier']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['fichier'] : null;
                $result = $controller->createParticipation($_POST, $file);
                echo json_encode($result);
                break;
                
            case 'update':
                $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
                $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                echo json_encode($controller->updateParticipation($id, $data));
                break;

            case 'updateStatut':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $statut = $_POST['statut'] ?? '';
                echo json_encode($controller->updateStatut($id, $statut));
                break;
                
            case 'delete':
                $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
                echo json_encode($controller->deleteParticipation($id));
                break;

            case 'search':
                $keyword = $_GET['keyword'] ?? $_POST['keyword'] ?? '';
                echo json_encode($controller->searchParticipations($keyword));
                break;

            case 'getByStatut':
                $statut = $_GET['statut'] ?? 'en_attente';
                echo json_encode($controller->getParticipationsByStatut($statut));
                break;

            case 'getByEvenement':
                $evenement_id = isset($_GET['evenement_id']) ? (int)$_GET['evenement_id'] : 0;
                echo json_encode($controller->getParticipationsByEvenement($evenement_id));
                break;

            case 'soumettreQuiz':
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($controller->soumettreQuiz($data));
                break;

            case 'statistics':
                echo json_encode($controller->getStatistics());
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Action inconnue']);
                break;
        }
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
    }
    exit();
}
?>
