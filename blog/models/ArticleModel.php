<?php
require_once '../config/database.php';

class ArticleModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAllArticles() {
        $stmt = $this->pdo->query("SELECT * FROM articles ORDER BY id");
        return $stmt->fetchAll();
    }

    public function getArticleById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
?>