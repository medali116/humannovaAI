<?php
/**
 * Modèle Evenement
 * Gestion des opérations CRUD pour les événements
 * Respect des principes OOP avec Getters et Setters
 */

class Evenement {
    // Connexion à la base de données
    private $conn;
    private $table = "evenements";

    // Propriétés privées
    private $id;
    private $type;
    private $titre;
    private $description;
    private $date_debut;
    private $date_fin;
    private $image_url;
    private $nombre_questions;

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
    public function getType() {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getTitre() {
        return $this->titre;
    }

    /**
     * @return string|null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getDateDebut() {
        return $this->date_debut;
    }

    /**
     * @return string|null
     */
    public function getDateFin() {
        return $this->date_fin;
    }

    /**
     * @return string|null
     */
    public function getImageUrl() {
        return $this->image_url;
    }

    /**
     * @return int
     */
    public function getNombreQuestions() {
        return $this->nombre_questions;
    }

    // ==================== SETTERS ====================

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = (int)$id;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = htmlspecialchars(strip_tags($type));
    }

    /**
     * @param string $titre
     */
    public function setTitre($titre) {
        $this->titre = htmlspecialchars(strip_tags($titre));
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = htmlspecialchars(strip_tags($description));
    }

    /**
     * @param string $date_debut
     */
    public function setDateDebut($date_debut) {
        $this->date_debut = htmlspecialchars(strip_tags($date_debut));
    }

    /**
     * @param string $date_fin
     */
    public function setDateFin($date_fin) {
        $this->date_fin = htmlspecialchars(strip_tags($date_fin));
    }

    /**
     * @param string $image_url
     */
    public function setImageUrl($image_url) {
        $this->image_url = htmlspecialchars(strip_tags($image_url));
    }

    /**
     * @param int $nombre_questions
     */
    public function setNombreQuestions($nombre_questions) {
        $this->nombre_questions = (int)$nombre_questions;
    }

    // ==================== MÉTHODES CRUD ====================

    /**
     * Récupérer tous les événements
     * @return PDOStatement
     */
    public function readAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY date_debut DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Récupérer un événement par ID
     * @return bool
     */
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->setType($row['type']);
            $this->setTitre($row['titre']);
            $this->setDescription($row['description']);
            $this->setDateDebut($row['date_debut']);
            $this->setDateFin($row['date_fin']);
            $this->setImageUrl($row['image_url']);
            $this->setNombreQuestions($row['nombre_questions']);
            return true;
        }
        
        return false;
    }

    /**
     * Créer un événement
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                (type, titre, description, date_debut, date_fin, image_url, nombre_questions)
                VALUES (:type, :titre, :description, :date_debut, :date_fin, :image_url, :nombre_questions)";

        $stmt = $this->conn->prepare($query);

        // Liaison des paramètres avec les getters
        $type = $this->getType();
        $titre = $this->getTitre();
        $description = $this->getDescription();
        $date_debut = $this->getDateDebut();
        $date_fin = $this->getDateFin();
        $image_url = $this->getImageUrl() ?: 'https://via.placeholder.com/600x400?text=No+Image';
        $nombre_questions = $this->getNombreQuestions();

        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':titre', $titre);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':nombre_questions', $nombre_questions, PDO::PARAM_INT);

        if($stmt->execute()) {
            $this->setId($this->conn->lastInsertId());
            return true;
        }

        return false;
    }

    /**
     * Mettre à jour un événement
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET type = :type,
                    titre = :titre,
                    description = :description,
                    date_debut = :date_debut,
                    date_fin = :date_fin,
                    image_url = :image_url,
                    nombre_questions = :nombre_questions
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Liaison des paramètres avec les getters
        $id = $this->getId();
        $type = $this->getType();
        $titre = $this->getTitre();
        $description = $this->getDescription();
        $date_debut = $this->getDateDebut();
        $date_fin = $this->getDateFin();
        $image_url = $this->getImageUrl();
        $nombre_questions = $this->getNombreQuestions();

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':titre', $titre);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':nombre_questions', $nombre_questions, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Supprimer un événement
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
     * Récupérer les événements avec leurs questions et réponses (pour les quiz)
     * @return PDOStatement
     */
    public function readWithQuestions() {
        $query = "SELECT e.*, 
                         q.id as question_id, q.texte_question, q.ordre as question_ordre,
                         r.id as reponse_id, r.texte_reponse, r.est_correcte, r.ordre as reponse_ordre
                  FROM " . $this->table . " e
                  LEFT JOIN questions q ON e.id = q.evenement_id
                  LEFT JOIN reponses r ON q.id = r.question_id
                  WHERE e.id = :id
                  ORDER BY q.ordre, r.ordre";
        
        $stmt = $this->conn->prepare($query);
        $id = $this->getId();
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Récupérer les événements par type
     * @param string $type
     * @return PDOStatement
     */
    public function readByType($type) {
        $query = "SELECT * FROM " . $this->table . " WHERE type = :type ORDER BY date_debut DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Rechercher des événements
     * @param string $keyword
     * @return PDOStatement
     */
    public function search($keyword) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE titre LIKE :keyword OR description LIKE :keyword
                  ORDER BY date_debut DESC";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . htmlspecialchars(strip_tags($keyword)) . "%";
        $stmt->bindParam(':keyword', $searchTerm);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Compter le total des événements
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
     * Mettre à jour le nombre de questions
     * @return bool
     */
    public function updateNombreQuestions() {
        $query = "UPDATE " . $this->table . " SET nombre_questions = :nombre_questions WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $id = $this->getId();
        $nombre_questions = $this->getNombreQuestions();
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre_questions', $nombre_questions, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}
?>
