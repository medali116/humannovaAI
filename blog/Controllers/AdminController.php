<?php
require_once "Models/Admin.php";

class AdminController {
    private $model;

    public function __construct() {
        $this->model = new Admin();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];
            if ($this->model->login($username, $password)) {
                session_start();
                $_SESSION['admin'] = $username;
                header("Location: index.php?controller=admin&action=index");
            } else {
                $error = "Invalid credentials!";
            }
        }
        include "Views/admin/login.php";
    }

    public function index() {
        session_start();
        if (!isset($_SESSION['admin'])) {
            header("Location: index.php?controller=admin&action=login");
        }
        include "Views/admin/index.php";
    }

    public function logout() {
        session_start();
        session_destroy();
        header("Location: index.php?controller=admin&action=login");
    }
}
?>
