<?php
// Use __DIR__ to include Connection.php relative to this file
require_once __DIR__ . "/../Core/Connection.php";

class Article {
    private $id;
    private $titre;
    private $contenu;
    private $date_creation;
    private $conn;

    // Constructor: create PDO connection internally
    public function __construct() {
        // Check if Connection class exists
        if (!class_exists('Connection')) {
            die("Error: Connection class not found. Check Core/Connection.php path.");
        }

        $db = new Connection();       // create Connection object
        $this->conn = $db->connect(); // get PDO connection
    }

    // Getters
    public function getId() { return $this->id; }
    public function getTitre() { return $this->titre; }
    public function getContenu() { return $this->contenu; }
    public function getDateCreation() { return $this->date_creation; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setTitre($titre) { $this->titre = $titre; }
    public function setContenu($contenu) { $this->contenu = $contenu; }
    public function setDateCreation($date_creation) { $this->date_creation = $date_creation; }

    // CRUD
    public function create() {
        $stmt = $this->conn->prepare("INSERT INTO articles(titre, contenu) VALUES(:titre, :contenu)");
        $stmt->execute([
            'titre' => $this->titre,
            'contenu' => $this->contenu
        ]);
        $this->id = $this->conn->lastInsertId();
    }

    public function readAll() {
        $stmt = $this->conn->query("SELECT * FROM articles ORDER BY date_creation DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function read($id) {
        $stmt = $this->conn->prepare("SELECT * FROM articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Alias for read() to match naming convention
    public function readById($id) {
        return $this->read($id);
    }

    public function update() {
        $stmt = $this->conn->prepare("UPDATE articles SET titre = :titre, contenu = :contenu WHERE id = :id");
        $stmt->execute([
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'id' => $this->id
        ]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
?>
