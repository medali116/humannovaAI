<?php
require_once '../config/database.php';

class LikeModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getLikesCount($articleId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM article_likes WHERE article_id = ?");
        $stmt->execute([$articleId]);
        return (int) $stmt->fetchColumn();
    }

    public function hasUserLiked($articleId, $userId) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM article_likes WHERE article_id = ? AND user_id = ?");
        $stmt->execute([$articleId, $userId]);
        return (bool) $stmt->fetch();
    }

    public function toggleLike($articleId, $userId) {
        if ($this->hasUserLiked($articleId, $userId)) {
            $stmt = $this->pdo->prepare("DELETE FROM article_likes WHERE article_id = ? AND user_id = ?");
            $stmt->execute([$articleId, $userId]);
            return false; // Unliked
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO article_likes (user_id, article_id) VALUES (?, ?)");
            $stmt->execute([$userId, $articleId]);
            return true; // Liked
        }
    }
}
?>