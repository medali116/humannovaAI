<?php
require_once '../config/database.php';

class CommentModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getCommentsByArticle($articleId) {
        $stmt = $this->pdo->prepare("
            SELECT ac.id, ac.comment_text, ac.user_id, u.username, ac.created_at 
            FROM article_comments ac
            JOIN users u ON ac.user_id = u.id
            WHERE article_id = ?
            ORDER BY ac.created_at DESC
        ");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    public function addComment($userId, $articleId, $commentText) {
        $stmt = $this->pdo->prepare("INSERT INTO article_comments (user_id, article_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $articleId, $commentText]);
        return $this->pdo->lastInsertId();
    }

    public function deleteComment($commentId, $userId) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM article_comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch();
        if ($comment && $comment['user_id'] == $userId) {
            $stmt = $this->pdo->prepare("DELETE FROM article_comments WHERE id = ?");
            $stmt->execute([$commentId]);
            return true;
        }
        return false;
    }
}
?> 