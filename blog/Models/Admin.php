<?php
class Admin {
    private $db;

    public function __construct() {
        try {
            $this->db = new PDO(
                "mysql:host=127.0.0.1;dbname=blog;charset=utf8mb4", 
                'root', 
                '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Erreur connexion DB: " . $e->getMessage());
        }
    }

    public function authenticate($username, $password) {
        try {
            // Récupérer l'admin par username
            $stmt = $this->db->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier si l'admin existe
            if (!$admin) {
                error_log("Admin non trouvé: " . $username);
                return false;
            }
            
            // Vérifier le mot de passe
            if (password_verify($password, $admin['password'])) {
                error_log("Authentification réussie pour: " . $username);
                return true;
            } else {
                error_log("Mot de passe incorrect pour: " . $username);
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Erreur authenticate: " . $e->getMessage());
            return false;
        }
    }
}