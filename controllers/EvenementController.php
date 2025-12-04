<?php
/**
 * Contrôleur Evenement
 * Gestion de la logique métier pour les événements
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Evenement.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Reponse.php';

class EvenementController {
    private $db;
    private $evenement;
    private $question;
    private $reponse;

    /**
     * Constructeur
     */
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->evenement = new Evenement($this->db);
        $this->question = new Question($this->db);
        $this->reponse = new Reponse($this->db);
    }

    /**
     * Récupérer tous les événements
     * @return array
     */
    public function getAllEvenements() {
        $stmt = $this->evenement->readAll();
        $evenements = array();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $evenements[] = $row;
        }
        
        return $evenements;
    }

    /**
     * Récupérer un événement avec ses questions et réponses
     * @param int $id
     * @return array|null
     */
    public function getEvenementComplet($id) {
        $this->evenement->setId($id);
        
        if(!$this->evenement->readOne()) {
            return null;
        }
        
        $data = array(
            'id' => $this->evenement->getId(),
            'type' => $this->evenement->getType(),
            'titre' => $this->evenement->getTitre(),
            'description' => $this->evenement->getDescription(),
            'date_debut' => $this->evenement->getDateDebut(),
            'date_fin' => $this->evenement->getDateFin(),
            'image_url' => $this->evenement->getImageUrl(),
            'nombre_questions' => $this->evenement->getNombreQuestions(),
            'questions' => array()
        );
        
        // Si c'est un quiz, récupérer les questions et réponses
        if($this->evenement->getType() === 'quiz') {
            $this->question->setEvenementId($id);
            $questions_stmt = $this->question->readByEvenement();
            
            while($question_row = $questions_stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->reponse->setQuestionId($question_row['id']);
                $reponses_stmt = $this->reponse->readByQuestion();
                
                $reponses = array();
                while($reponse_row = $reponses_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $reponses[] = $reponse_row;
                }
                
                $question_row['reponses'] = $reponses;
                $data['questions'][] = $question_row;
            }
        }
        
        return $data;
    }

    /**
     * Créer un événement
     * @param array $data
     * @return array
     */
    public function createEvenement($data) {
        // Validation côté serveur
        $errors = $this->validateEvenement($data);
        if(!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }

        try {
            $this->db->beginTransaction();
            
            // Créer l'événement avec les setters
            $this->evenement->setType($data['type']);
            $this->evenement->setTitre($data['titre']);
            $this->evenement->setDescription($data['description']);
            $this->evenement->setDateDebut($data['date_debut']);
            $this->evenement->setDateFin($data['date_fin']);
            
            // Traiter l'image (base64 ou URL)
            $imageUrl = $data['image_url'] ?? '';
            if (!empty($imageUrl) && strpos($imageUrl, 'data:image') === 0) {
                $savedImage = $this->saveBase64Image($imageUrl);
                if ($savedImage) {
                    $imageUrl = $savedImage;
                }
            }
            $this->evenement->setImageUrl($imageUrl ?: 'https://via.placeholder.com/600x400?text=No+Image');
            $this->evenement->setNombreQuestions($data['nombre_questions'] ?? 0);
            
            if(!$this->evenement->create()) {
                throw new Exception("Erreur lors de la création de l'événement");
            }
            
            $evenement_id = $this->evenement->getId();
            
            // Si c'est un quiz, créer les questions et réponses
            if($data['type'] === 'quiz' && isset($data['questions'])) {
                foreach($data['questions'] as $index => $question_data) {
                    $this->question->setEvenementId($evenement_id);
                    $this->question->setTexteQuestion($question_data['texte']);
                    $this->question->setOrdre($index + 1);
                    
                    if(!$this->question->create()) {
                        throw new Exception("Erreur lors de la création de la question " . ($index + 1));
                    }
                    
                    $question_id = $this->question->getId();
                    
                    // Créer les réponses
                    if(isset($question_data['reponses'])) {
                        foreach($question_data['reponses'] as $rep_index => $reponse_data) {
                            $this->reponse->setQuestionId($question_id);
                            $this->reponse->setTexteReponse($reponse_data['texte']);
                            
                            // Support both formats: est_correcte in reponse_data or reponse_correcte index
                            $isCorrect = false;
                            if (isset($reponse_data['est_correcte'])) {
                                $isCorrect = $reponse_data['est_correcte'] === true || $reponse_data['est_correcte'] === 1 || $reponse_data['est_correcte'] === '1';
                            } elseif (isset($question_data['reponse_correcte'])) {
                                $isCorrect = $question_data['reponse_correcte'] == $rep_index;
                            }
                            
                            $this->reponse->setEstCorrecte($isCorrect);
                            $this->reponse->setOrdre($rep_index + 1);
                            
                            if(!$this->reponse->create()) {
                                throw new Exception("Erreur lors de la création de la réponse");
                            }
                        }
                    }
                }
            }
            
            $this->db->commit();
            return array('success' => true, 'id' => $evenement_id, 'message' => 'Événement créé avec succès');
            
        } catch(Exception $e) {
            $this->db->rollBack();
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Mettre à jour un événement
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateEvenement($id, $data) {
        // Validation côté serveur
        $errors = $this->validateEvenement($data);
        if(!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }

        try {
            $this->db->beginTransaction();
            
            // Mettre à jour l'événement avec les setters
            $this->evenement->setId($id);
            $this->evenement->setType($data['type']);
            $this->evenement->setTitre($data['titre']);
            $this->evenement->setDescription($data['description']);
            $this->evenement->setDateDebut($data['date_debut']);
            $this->evenement->setDateFin($data['date_fin']);
            
            // Traiter l'image (base64 ou URL)
            $imageUrl = $data['image_url'] ?? '';
            if (!empty($imageUrl) && strpos($imageUrl, 'data:image') === 0) {
                $savedImage = $this->saveBase64Image($imageUrl);
                if ($savedImage) {
                    $imageUrl = $savedImage;
                }
            }
            $this->evenement->setImageUrl($imageUrl ?: 'https://via.placeholder.com/600x400?text=No+Image');
            $this->evenement->setNombreQuestions($data['nombre_questions'] ?? 0);
            
            if(!$this->evenement->update()) {
                throw new Exception("Erreur lors de la mise à jour de l'événement");
            }
            
            // Si c'est un quiz, supprimer les anciennes questions et créer les nouvelles
            if($data['type'] === 'quiz') {
                // Supprimer les anciennes questions (les réponses seront supprimées par CASCADE)
                $this->question->setEvenementId($id);
                $this->question->deleteByEvenement();
                
                // Créer les nouvelles questions
                if(isset($data['questions'])) {
                    foreach($data['questions'] as $index => $question_data) {
                        $this->question->setEvenementId($id);
                        $this->question->setTexteQuestion($question_data['texte']);
                        $this->question->setOrdre($index + 1);
                        
                        if(!$this->question->create()) {
                            throw new Exception("Erreur lors de la création de la question " . ($index + 1));
                        }
                        
                        $question_id = $this->question->getId();
                        
                        // Créer les réponses
                        if(isset($question_data['reponses'])) {
                            foreach($question_data['reponses'] as $rep_index => $reponse_data) {
                                $this->reponse->setQuestionId($question_id);
                                $this->reponse->setTexteReponse($reponse_data['texte']);
                                
                                // Support both formats: est_correcte in reponse_data or reponse_correcte index
                                $isCorrect = false;
                                if (isset($reponse_data['est_correcte'])) {
                                    $isCorrect = $reponse_data['est_correcte'] === true || $reponse_data['est_correcte'] === 1 || $reponse_data['est_correcte'] === '1';
                                } elseif (isset($question_data['reponse_correcte'])) {
                                    $isCorrect = $question_data['reponse_correcte'] == $rep_index;
                                }
                                
                                $this->reponse->setEstCorrecte($isCorrect);
                                $this->reponse->setOrdre($rep_index + 1);
                                
                                if(!$this->reponse->create()) {
                                    throw new Exception("Erreur lors de la création de la réponse");
                                }
                            }
                        }
                    }
                }
            }
            
            $this->db->commit();
            return array('success' => true, 'message' => 'Événement mis à jour avec succès');
            
        } catch(Exception $e) {
            $this->db->rollBack();
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Supprimer un événement
     * @param int $id
     * @return array
     */
    public function deleteEvenement($id) {
        $this->evenement->setId($id);
        
        if($this->evenement->delete()) {
            return array('success' => true, 'message' => 'Événement supprimé avec succès');
        }
        
        return array('success' => false, 'message' => 'Erreur lors de la suppression');
    }

    /**
     * Validation des données de l'événement (côté serveur)
     * @param array $data
     * @return array Tableau des erreurs
     */
    private function validateEvenement($data) {
        $errors = array();

        // Validation du type
        if(empty($data['type'])) {
            $errors['type'] = 'Le type est obligatoire';
        } elseif(!in_array($data['type'], ['normal', 'quiz'])) {
            $errors['type'] = 'Type invalide';
        }

        // Validation du titre
        if(empty($data['titre'])) {
            $errors['titre'] = 'Le titre est obligatoire';
        } elseif(strlen($data['titre']) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caractères';
        } elseif(strlen($data['titre']) > 150) {
            $errors['titre'] = 'Le titre ne peut pas dépasser 150 caractères';
        }

        // Validation de la description
        if(empty($data['description'])) {
            $errors['description'] = 'La description est obligatoire';
        } elseif(strlen($data['description']) < 10) {
            $errors['description'] = 'La description doit contenir au moins 10 caractères';
        } elseif(strlen($data['description']) > 500) {
            $errors['description'] = 'La description ne peut pas dépasser 500 caractères';
        }

        // Validation des dates
        if(empty($data['date_debut'])) {
            $errors['date_debut'] = 'La date de début est obligatoire';
        }

        if(empty($data['date_fin'])) {
            $errors['date_fin'] = 'La date de fin est obligatoire';
        }

        if(!empty($data['date_debut']) && !empty($data['date_fin'])) {
            $debut = strtotime($data['date_debut']);
            $fin = strtotime($data['date_fin']);
            if($fin <= $debut) {
                $errors['date_fin'] = 'La date de fin doit être postérieure à la date de début';
            }
        }

        // Validation de l'URL image (optionnel) - supporte URL et base64
        if(!empty($data['image_url'])) {
            // Ignorer la validation si c'est une image base64
            if (strpos($data['image_url'], 'data:image') !== 0 && 
                !filter_var($data['image_url'], FILTER_VALIDATE_URL) &&
                strpos($data['image_url'], 'uploads/') !== 0) {
                $errors['image_url'] = 'URL d\'image invalide';
            }
        }

        // Validation des questions pour les quiz
        if(isset($data['type']) && $data['type'] === 'quiz') {
            if(empty($data['questions']) || !is_array($data['questions'])) {
                $errors['questions'] = 'Un quiz doit avoir au moins une question';
            } else {
                foreach($data['questions'] as $index => $question) {
                    if(empty($question['texte'])) {
                        $errors["question_$index"] = "La question " . ($index + 1) . " est vide";
                    }
                    if(empty($question['reponses']) || count($question['reponses']) < 2) {
                        $errors["reponses_$index"] = "La question " . ($index + 1) . " doit avoir au moins 2 réponses";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Rechercher des événements
     * @param string $keyword
     * @return array
     */
    public function searchEvenements($keyword) {
        $stmt = $this->evenement->search($keyword);
        $evenements = array();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $evenements[] = $row;
        }
        
        return $evenements;
    }

    /**
     * Obtenir les statistiques
     * @return array
     */
    public function getStatistics() {
        $stats = array();
        $stats['total'] = $this->evenement->count();
        
        // Par type
        $stmt = $this->evenement->readByType('quiz');
        $stats['quiz'] = $stmt->rowCount();
        
        $stmt = $this->evenement->readByType('normal');
        $stats['normal'] = $stmt->rowCount();
        
        return $stats;
    }

    /**
     * Sauvegarder une image base64
     * @param string $base64Image
     * @return string|false
     */
    private function saveBase64Image($base64Image) {
        // Extraire le type et les données
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $imageType = $matches[1];
            $base64Data = substr($base64Image, strpos($base64Image, ',') + 1);
            $imageData = base64_decode($base64Data);
            
            if ($imageData === false) {
                return false;
            }
            
            // Générer un nom de fichier unique
            $fileName = 'event_' . uniqid() . '.' . $imageType;
            $uploadDir = __DIR__ . '/../uploads/images/';
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filePath = $uploadDir . $fileName;
            
            if (file_put_contents($filePath, $imageData)) {
                return 'uploads/images/' . $fileName;
            }
        }
        
        return false;
    }
}

// Gestion des requêtes AJAX
if($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller = new EvenementController();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    header('Content-Type: application/json; charset=UTF-8');
    
    switch($action) {
        case 'getAll':
            echo json_encode($controller->getAllEvenements());
            break;
            
        case 'getOne':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            echo json_encode($controller->getEvenementComplet($id));
            break;
            
        case 'create':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($controller->createEvenement($data));
            break;
            
        case 'update':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($controller->updateEvenement($id, $data));
            break;
            
        case 'delete':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
            echo json_encode($controller->deleteEvenement($id));
            break;

        case 'search':
            $keyword = $_GET['keyword'] ?? $_POST['keyword'] ?? '';
            echo json_encode($controller->searchEvenements($keyword));
            break;
            
        default:
            // Ne rien faire si pas d'action AJAX
            break;
    }
}
?>
