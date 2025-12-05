<?php
require_once "Models/Interaction.php";

class InteractionController {
    private $model;

    public function __construct() {
        // DÉMARRER LA SESSION DANS LE CONSTRUCTEUR
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new Interaction();
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validation et sanitization des données
            $article_id = isset($_POST['article_id']) ? (int)$_POST['article_id'] : null;
            $type = isset($_POST['type']) ? trim($_POST['type']) : null;
            $auteur = isset($_POST['auteur']) ? trim($_POST['auteur']) : null;
            $email = isset($_POST['email']) ? trim($_POST['email']) : null;
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';

            // Validation
            $errors = [];

            if (!$article_id || $article_id <= 0) {
                $errors['article_id'] = "Article ID invalide.";
            }

            if (!$type || !in_array($type, ['like', 'comment'])) {
                $errors['type'] = "Type invalide. Doit être 'like' ou 'comment'.";
            }

            if (!$auteur || strlen($auteur) < 2 || strlen($auteur) > 255) {
                $errors['auteur'] = "L'auteur doit contenir entre 2 et 255 caractères.";
            }

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Email invalide.";
            }

            if ($type === 'comment' && (empty($message) || strlen($message) < 5)) {
                $errors['message'] = "Le commentaire doit contenir au moins 5 caractères.";
            }

            if (!empty($errors)) {
                // Stocker les erreurs en session pour les afficher dans la vue
                $_SESSION['errors'] = $errors;
                $_SESSION['form_data'] = ['article_id' => $article_id, 'auteur' => $auteur, 'email' => $email, 'message' => $message, 'type' => $type];
                header("Location: index.php?controller=article&action=show&id=" . $article_id);
                exit;
            }

            // Créer l'interaction si valide
            $this->model->setArticleId($article_id);
            $this->model->setType($type);
            $this->model->setAuteur($auteur);
            $this->model->setEmail($email);
            $this->model->setMessage($message);
            $this->model->create();
            
            $_SESSION['success'] = "Votre interaction a été ajoutée avec succès!";
            header("Location: index.php?controller=article&action=show&id=" . $article_id);
            exit;
        }
    }

    public function delete($id = null) {
        // Récupérer les paramètres depuis GET si non passés en argument
        $id = $id ?? ($_GET['id'] ?? null);
        $article_id = $_GET['article_id'] ?? null;

        if (!$id || !$article_id) {
            header("Location: index.php?controller=article&action=index");
            exit;
        }

        $this->model->delete($id);
        header("Location: index.php?controller=article&action=show&id=" . $article_id);
        exit;
    }
}
?>