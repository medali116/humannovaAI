<?php
/**
 * Modèle Utilisateur
 * Gestion des utilisateurs et leurs participations
 * Respect des principes OOP avec Getters et Setters
 */

class Utilisateur {
    // Connexion à la base de données
    private $conn;
    private $table = "utilisateurs";

    // Propriétés privées
    private $id;
    private $nom;
    private $prenom;
    private $email;

    /**
     * Constructeur
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    // ==================== GETTERS ====================

    /**
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getNom() {
        return $this->nom;
    }

    /**
     * @return string|null
     */
    public function getPrenom() {
        return $this->prenom;
    }

    /**
     * @return string|null
     */
    public function getEmail() {
        return $this->email;
    }

    // ==================== SETTERS ====================

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = (int)$id;
    }

    /**
     * @param string $nom
     */
    public function setNom($nom) {
        $this->nom = htmlspecialchars(strip_tags($nom));
    }

    /**
     * @param string $prenom
     */
    public function setPrenom($prenom) {
        $this->prenom = htmlspecialchars(strip_tags($prenom));
    }

    /**
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = htmlspecialchars(strip_tags($email));
    }

    // ==================== MÉTHODES CRUD ====================

    /**
     * Créer ou récupérer un utilisateur par email
     * @return bool
     */
    public function getOrCreate() {
        // Vérifier si l'utilisateur existe
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $email = $this->getEmail();
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->setId($row['id']);
            $this->setNom($row['nom']);
            $this->setPrenom($row['prenom']);
            return true;
        }
        
        // Créer un nouveau utilisateur
        $query = "INSERT INTO " . $this->table . " (nom, prenom, email) VALUES (:nom, :prenom, :email)";
        $stmt = $this->conn->prepare($query);
        
        $nom = $this->getNom();
        $prenom = $this->getPrenom();
        
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':email', $email);
        
        if($stmt->execute()) {
            $this->setId($this->conn->lastInsertId());
            return true;
        }
        
        return false;
    }

    /**
     * Récupérer un utilisateur par ID
     * @return bool
     */
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $id = $this->getId();
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->setNom($row['nom']);
            $this->setPrenom($row['prenom']);
            $this->setEmail($row['email']);
            return true;
        }
        
        return false;
    }

    /**
     * Récupérer tous les utilisateurs
     * @return PDOStatement
     */
    public function readAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Mettre à jour un utilisateur
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET nom = :nom,
                    prenom = :prenom,
                    email = :email
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $id = $this->getId();
        $nom = $this->getNom();
        $prenom = $this->getPrenom();
        $email = $this->getEmail();

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':email', $email);

        return $stmt->execute();
    }

    /**
     * Supprimer un utilisateur
     * @return bool
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $id = $this->getId();
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Enregistrer un résultat de quiz
     * @param int $evenement_id
     * @param int $score
     * @param int $total
     * @param array $reponses
     * @return array
     */
    public function enregistrerResultatQuiz($evenement_id, $score, $total, $reponses) {
        try {
            $this->conn->beginTransaction();
            
            $pourcentage = $total > 0 ? ($score / $total) * 100 : 0;
            
            // Insérer le résultat global
            $query = "INSERT INTO resultats_quiz (utilisateur_id, evenement_id, score, total_questions, pourcentage) 
                      VALUES (:utilisateur_id, :evenement_id, :score, :total, :pourcentage)";
            
            $stmt = $this->conn->prepare($query);
            $utilisateur_id = $this->getId();
            $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
            $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
            $stmt->bindParam(':score', $score, PDO::PARAM_INT);
            $stmt->bindParam(':total', $total, PDO::PARAM_INT);
            $stmt->bindParam(':pourcentage', $pourcentage);
            $stmt->execute();
            
            $resultat_id = $this->conn->lastInsertId();
            
            // Insérer les détails des réponses
            $query = "INSERT INTO reponses_utilisateur (resultat_id, question_id, reponse_id, est_correcte) 
                      VALUES (:resultat_id, :question_id, :reponse_id, :est_correcte)";
            $stmt = $this->conn->prepare($query);
            
            foreach($reponses as $reponse) {
                $est_correcte = $reponse['est_correcte'] ? 1 : 0;
                $stmt->bindParam(':resultat_id', $resultat_id, PDO::PARAM_INT);
                $stmt->bindParam(':question_id', $reponse['question_id'], PDO::PARAM_INT);
                $stmt->bindParam(':reponse_id', $reponse['reponse_id'], PDO::PARAM_INT);
                $stmt->bindParam(':est_correcte', $est_correcte, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            $this->conn->commit();
            return array('success' => true, 'resultat_id' => $resultat_id);
            
        } catch(Exception $e) {
            $this->conn->rollBack();
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Enregistrer une participation à un événement normal
     * @param int $evenement_id
     * @param string $commentaire
     * @param string|null $fichier
     * @return array
     */
    public function enregistrerParticipation($evenement_id, $commentaire, $fichier = null) {
        try {
            $query = "INSERT INTO participations (utilisateur_id, evenement_id, commentaire, fichier_url, statut) 
                      VALUES (:utilisateur_id, :evenement_id, :commentaire, :fichier_url, 'en_attente')";
            
            $stmt = $this->conn->prepare($query);
            
            $utilisateur_id = $this->getId();
            $commentaire = htmlspecialchars(strip_tags($commentaire));
            
            $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
            $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
            $stmt->bindParam(':commentaire', $commentaire);
            $stmt->bindParam(':fichier_url', $fichier);
            
            if($stmt->execute()) {
                $participation_id = $this->conn->lastInsertId();
                return array('success' => true, 'participation_id' => $participation_id);
            }
            
            $errorInfo = $stmt->errorInfo();
            return array('success' => false, 'message' => 'Erreur SQL: ' . $errorInfo[2]);
            
        } catch(PDOException $e) {
            return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les résultats d'un utilisateur pour un événement
     * @param int $evenement_id
     * @return PDOStatement
     */
    public function getResultatsQuiz($evenement_id) {
        $query = "SELECT * FROM resultats_quiz 
                  WHERE utilisateur_id = :utilisateur_id AND evenement_id = :evenement_id 
                  ORDER BY date_passage DESC";
        
        $stmt = $this->conn->prepare($query);
        $utilisateur_id = $this->getId();
        $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Vérifier si l'utilisateur a déjà participé à un événement
     * @param int $evenement_id
     * @param string $type
     * @return bool
     */
    public function aDejaParticipe($evenement_id, $type) {
        if($type === 'quiz') {
            $query = "SELECT COUNT(*) as count FROM resultats_quiz 
                      WHERE utilisateur_id = :utilisateur_id AND evenement_id = :evenement_id";
        } else {
            $query = "SELECT COUNT(*) as count FROM participations 
                      WHERE utilisateur_id = :utilisateur_id AND evenement_id = :evenement_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $utilisateur_id = $this->getId();
        $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }

    /**
     * Compter le total des utilisateurs
     * @return int
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    /**
     * Rechercher des utilisateurs
     * @param string $keyword
     * @return PDOStatement
     */
    public function search($keyword) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE nom LIKE :keyword OR prenom LIKE :keyword OR email LIKE :keyword
                  ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . htmlspecialchars(strip_tags($keyword)) . "%";
        $stmt->bindParam(':keyword', $searchTerm);
        $stmt->execute();
        return $stmt;
    }
}
?>
