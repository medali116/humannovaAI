<?php
/**
 * Modèle Reponse
 * Gestion des réponses pour les questions
 * Respect des principes OOP avec Getters et Setters
 */

class Reponse {
    // Connexion à la base de données
    private $conn;
    private $table = "reponses";

    // Propriétés privées
    private $id;
    private $question_id;
    private $texte_reponse;
    private $est_correcte;
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
    public function getQuestionId() {
        return $this->question_id;
    }

    /**
     * @return string|null
     */
    public function getTexteReponse() {
        return $this->texte_reponse;
    }

    /**
     * @return bool
     */
    public function getEstCorrecte() {
        return $this->est_correcte;
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
     * @param int $question_id
     */
    public function setQuestionId($question_id) {
        $this->question_id = (int)$question_id;
    }

    /**
     * @param string $texte_reponse
     */
    public function setTexteReponse($texte_reponse) {
        $this->texte_reponse = htmlspecialchars(strip_tags($texte_reponse));
    }

    /**
     * @param bool $est_correcte
     */
    public function setEstCorrecte($est_correcte) {
        $this->est_correcte = (bool)$est_correcte;
    }

    /**
     * @param int $ordre
     */
    public function setOrdre($ordre) {
        $this->ordre = (int)$ordre;
    }

    // ==================== MÉTHODES CRUD ====================

    /**
     * Créer une réponse
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                (question_id, texte_reponse, est_correcte, ordre)
                VALUES (:question_id, :texte_reponse, :est_correcte, :ordre)";

        $stmt = $this->conn->prepare($query);

        $question_id = $this->getQuestionId();
        $texte_reponse = $this->getTexteReponse();
        $est_correcte = $this->getEstCorrecte() ? 1 : 0;
        $ordre = $this->getOrdre();

        $stmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
        $stmt->bindParam(':texte_reponse', $texte_reponse);
        $stmt->bindParam(':est_correcte', $est_correcte, PDO::PARAM_INT);
        $stmt->bindParam(':ordre', $ordre, PDO::PARAM_INT);

        if($stmt->execute()) {
            $this->setId($this->conn->lastInsertId());
            return true;
        }

        return false;
    }

    /**
     * Récupérer une réponse par ID
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
            $this->setQuestionId($row['question_id']);
            $this->setTexteReponse($row['texte_reponse']);
            $this->setEstCorrecte($row['est_correcte']);
            $this->setOrdre($row['ordre']);
            return true;
        }
        
        return false;
    }

    /**
     * Récupérer les réponses d'une question
     * @return PDOStatement
     */
    public function readByQuestion() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE question_id = :question_id 
                  ORDER BY ordre ASC";
        
        $stmt = $this->conn->prepare($query);
        $question_id = $this->getQuestionId();
        $stmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Mettre à jour une réponse
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET texte_reponse = :texte_reponse,
                    est_correcte = :est_correcte,
                    ordre = :ordre
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $id = $this->getId();
        $texte_reponse = $this->getTexteReponse();
        $est_correcte = $this->getEstCorrecte() ? 1 : 0;
        $ordre = $this->getOrdre();

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':texte_reponse', $texte_reponse);
        $stmt->bindParam(':est_correcte', $est_correcte, PDO::PARAM_INT);
        $stmt->bindParam(':ordre', $ordre, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Supprimer une réponse
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
     * Supprimer les réponses d'une question
     * @return bool
     */
    public function deleteByQuestion() {
        $query = "DELETE FROM " . $this->table . " WHERE question_id = :question_id";
        $stmt = $this->conn->prepare($query);
        $question_id = $this->getQuestionId();
        $stmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Vérifier si une réponse est correcte
     * @param int $question_id
     * @param int $reponse_id
     * @return bool
     */
    public function isCorrect($question_id, $reponse_id) {
        $query = "SELECT est_correcte FROM " . $this->table . " 
                  WHERE id = :reponse_id AND question_id = :question_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':reponse_id', $reponse_id, PDO::PARAM_INT);
        $stmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (bool)$row['est_correcte'] : false;
    }
}
?>
