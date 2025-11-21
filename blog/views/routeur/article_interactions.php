<?php
header('Content-Type: application/json');
require_once 'controllers/ArticleController.php';

$controller = new ArticleController();
$action = $_POST['action'] ?? '';
$articleId = (int)($_POST['article_id'] ?? 0);

if (!$articleId && $action !== 'delete_comment') {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

switch ($action) {
    case 'get_status':
        $controller->getStatus($articleId);
        break;
    case 'toggle_like':
        $controller->toggleLike($articleId);
        break;
    case 'add_comment':
        $controller->addComment($articleId);
        break;
    case 'delete_comment':
        $controller->deleteComment();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action non reconnue']);
        break;
}
?>