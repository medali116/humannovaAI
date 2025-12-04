<?php
/**
 * Modèle Question
 * Gestion des questions pour les événements de type quiz
 * Respect des principes OOP avec Getters et Setters
 */

class Question {
    // Connexion à la base de données
    private $conn;
    private $table = "questions";

    // Propriétés privées
    private $id;
    private $evenement_id;
    private $texte_question;
    private $ordre;

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
     * @return int|null
     */
    public function getEvenementId() {
        return $this->evenement_id;
    }

    /**
     * @return string|null
     */
    public function getTexteQuestion() {
        return $this->texte_question;
    }

    /**
     * @return int
     */
    public function getOrdre() {
        return $this->ordre;
    }

    // ==================== SETTERS ====================

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = (int)$id;
    }

    /**
     * @param int $evenement_id
     */
    public function setEvenementId($evenement_id) {
        $this->evenement_id = (int)$evenement_id;
    }

    /**
     * @param string $texte_question
     */
    public function setTexteQuestion($texte_question) {
        $this->texte_question = htmlspecialchars(strip_tags($texte_question));
    }

    /**
     * @param int $ordre
     */
    public function setOrdre($ordre) {
        $this->ordre = (int)$ordre;
    }

    // ==================== MÉTHODES CRUD ====================

    /**
     * Créer une question
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                (evenement_id, texte_question, ordre)
                VALUES (:evenement_id, :texte_question, :ordre)";

        $stmt = $this->conn->prepare($query);

        $evenement_id = $this->getEvenementId();
        $texte_question = $this->getTexteQuestion();
        $ordre = $this->getOrdre();

        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->bindParam(':texte_question', $texte_question);
        $stmt->bindParam(':ordre', $ordre, PDO::PARAM_INT);

        if($stmt->execute()) {
            $this->setId($this->conn->lastInsertId());
            return true;
        }

        return false;
    }

    /**
     * Récupérer une question par ID
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
            $this->setEvenementId($row['evenement_id']);
            $this->setTexteQuestion($row['texte_question']);
            $this->setOrdre($row['ordre']);
            return true;
        }
        
        return false;
    }

    /**
     * Récupérer les questions d'un événement
     * @return PDOStatement
     */
    public function readByEvenement() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE evenement_id = :evenement_id 
                  ORDER BY ordre ASC";
        
        $stmt = $this->conn->prepare($query);
        $evenement_id = $this->getEvenementId();
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Mettre à jour une question
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET texte_question = :texte_question,
                    ordre = :ordre
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $id = $this->getId();
        $texte_question = $this->getTexteQuestion();
        $ordre = $this->getOrdre();

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':texte_question', $texte_question);
        $stmt->bindParam(':ordre', $ordre, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Supprimer une question
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
     * Supprimer les questions d'un événement
     * @return bool
     */
    public function deleteByEvenement() {
        $query = "DELETE FROM " . $this->table . " WHERE evenement_id = :evenement_id";
        $stmt = $this->conn->prepare($query);
        $evenement_id = $this->getEvenementId();
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Compter les questions d'un événement
     * @return int
     */
    public function countByEvenement() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE evenement_id = :evenement_id";
        $stmt = $this->conn->prepare($query);
        $evenement_id = $this->getEvenementId();
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }
}
?>
