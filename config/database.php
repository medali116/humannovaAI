<?php
/**
 * Configuration de la connexion à la base de données
 * Utilise PDO pour une meilleure sécurité
 */

class Database {
    // Propriétés privées
    private $host = "localhost";
    private $db_name = "events_management";
    private $username = "root";
    private $password = "";
    private $conn = null;

    /**
     * Getter pour le host
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * Setter pour le host
     * @param string $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * Getter pour le nom de la base de données
     * @return string
     */
    public function getDbName() {
        return $this->db_name;
    }

    /**
     * Setter pour le nom de la base de données
     * @param string $db_name
     */
    public function setDbName($db_name) {
        $this->db_name = $db_name;
    }

    /**
     * Getter pour le username
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Setter pour le username
     * @param string $username
     */
    public function setUsername($username) {
        $this->username = $username;
    }

    /**
     * Setter pour le password
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * Obtenir la connexion à la base de données
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            
            // Configuration PDO
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch(PDOException $e) {
            echo "Erreur de connexion : " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
