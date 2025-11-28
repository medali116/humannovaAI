<?php
require_once __DIR__ . "/../Core/Connection.php";

class Interaction {
    private $id, $article_id, $type, $auteur, $email, $message, $owner_token, $date_creation;
    private $conn;

    public function __construct() {
        $db = new Connection();
        $this->conn = $db->connect();
    }

    // Getters and Setters
    public function getId() { return $this->id; }
    public function getArticleId() { return $this->article_id; }
    public function getType() { return $this->type; }
    public function getAuteur() { return $this->auteur; }
    public function getEmail() { return $this->email; }
    public function getMessage() { return $this->message; }
    public function getOwnerToken() { return $this->owner_token; }
    public function getDateCreation() { return $this->date_creation; }

    public function setId($id) { $this->id = $id; }
    public function setArticleId($article_id) { $this->article_id = $article_id; }
    public function setType($type) { $this->type = $type; }
    public function setAuteur($auteur) { $this->auteur = $auteur; }
    public function setEmail($email) { $this->email = $email; }
    public function setMessage($message) { $this->message = $message; }
    public function setOwnerToken($owner_token) { $this->owner_token = $owner_token; }
    public function setDateCreation($date_creation) { $this->date_creation = $date_creation; }

    // CRUD functions
    public function create() {
        $stmt = $this->conn->prepare("INSERT INTO interactions(article_id, type, auteur, email, message) VALUES(:article_id, :type, :auteur, :email, :message)");
        $stmt->execute([
            'article_id' => $this->article_id,
            'type' => $this->type,
            'auteur' => $this->auteur,
            'email' => $this->email,
            'message' => $this->message
        ]);
        $this->id = $this->conn->lastInsertId();
    }

    public function readAllByArticle($article_id) {
        $stmt = $this->conn->prepare("SELECT * FROM interactions WHERE article_id = :article_id ORDER BY date_creation ASC");
        $stmt->execute(['article_id' => $article_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countLikes($article_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM interactions WHERE article_id = :article_id AND type = 'like'");
        $stmt->execute(['article_id' => $article_id]);
        return (int) $stmt->fetchColumn();
    }

    public function userLiked($article_id, $email) {
        $stmt = $this->conn->prepare("SELECT * FROM interactions WHERE article_id = :article_id AND type = 'like' AND email = :email LIMIT 1");
        $stmt->execute(['article_id' => $article_id, 'email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : false;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM interactions WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
?>
