<?php
require_once "Models/Admin.php";

class AdminController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new Admin();
    }

    public function login() {
        // Rediriger si déjà connecté
        if (isset($_SESSION['admin'])) {
            header('Location: index.php?controller=admin&action=index');
            exit;
        }

        $error = null;

        // Traiter le formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            
            // Log pour debug
            error_log("Tentative de connexion - Username: " . $username);
            
            if ($this->model->authenticate($username, $password)) {
                $_SESSION['admin'] = $username;
                error_log("Connexion réussie, redirection vers dashboard");
                header('Location: index.php?controller=admin&action=index');
                exit;
            } else {
                error_log("Échec de connexion");
                $error = "Identifiants invalides";
            }
        }
        
        // Afficher le formulaire
        require_once "Views/admin/login.php";
        exit;
    }

    public function index() {
        if (!isset($_SESSION['admin'])) {
            header('Location: index.php?controller=admin&action=login');
            exit;
        }
        
        require_once "Views/admin/index.php";
        exit;
    }

    public function logout() {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit;
    }
}