<?php
session_start();
require_once '../models/ArticleModel.php';
require_once '../models/LikeModel.php';
require_once '../models/CommentModel.php';

class ArticleController {
    private $articleModel;
    private $likeModel;
    private $commentModel;

    public function __construct() {
        $this->articleModel = new ArticleModel();
        $this->likeModel = new LikeModel();
        $this->commentModel = new CommentModel();
    }

    public function getStatus($articleId) {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Connexion utilisateur requise']);
            return;
        }
        $userId = (int)$_SESSION['user_id'];

        $likesCount = $this->likeModel->getLikesCount($articleId);
        $userLiked = $this->likeModel->hasUserLiked($articleId, $userId);
        $comments = $this->commentModel->getCommentsByArticle($articleId);

        echo json_encode([
            'likesCount' => $likesCount,
            'userLiked' => $userLiked,
            'comments' => $comments
        ]);
    }

    public function toggleLike($articleId) {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Connexion utilisateur requise']);
            return;
        }
        $userId = (int)$_SESSION['user_id'];

        $liked = $this->likeModel->toggleLike($articleId, $userId);
        $likesCount = $this->likeModel->getLikesCount($articleId);

        echo json_encode(['liked' => $liked, 'likesCount' => $likesCount]);
    }

    public function addComment($articleId) {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Connexion utilisateur requise']);
            return;
        }
        $userId = (int)$_SESSION['user_id'];
        $commentText = trim($_POST['comment_text'] ?? '');

        if ($commentText === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Commentaire vide']);
            return;
        }

        $commentId = $this->commentModel->addComment($userId, $articleId, $commentText);

        echo json_encode([
            'id' => $commentId,
            'comment_text' => htmlspecialchars($commentText),
            'user_id' => $userId,
            'username' => 'Vous', // À adapter si username en session
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function deleteComment() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Connexion utilisateur requise']);
            return;
        }
        $userId = (int)$_SESSION['user_id'];
        $commentId = (int)($_POST['comment_id'] ?? 0);

        if (!$commentId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID commentaire invalide']);
            return;
        }

        $success = $this->commentModel->deleteComment($commentId, $userId);
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Autorisation refusée']);
        }
    }
}
?>