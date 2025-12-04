<?php
/**
 * Modèle Participation
 * Gestion des participations aux événements
 * Entité avec Jointure (utilisateurs et evenements)
 * Respect des principes OOP avec Getters et Setters
 */

class Participation {
    // Connexion à la base de données
    private $conn;
    private $table = "participations";

    // Propriétés privées
    private $id;
    private $utilisateur_id;
    private $evenement_id;
    private $commentaire;
    private $fichier_url;
    private $statut;
    private $date_participation;

    /**
     * Constructeur
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    // ==================== GETTERS ====================

    /**
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getUtilisateurId() {
        return $this->utilisateur_id;
    }

    /**
     * @return int|null
     */
    public function getEvenementId() {
        return $this->evenement_id;
    }

    /**
     * @return string|null
     */
    public function getCommentaire() {
        return $this->commentaire;
    }

    /**
     * @return string|null
     */
    public function getFichierUrl() {
        return $this->fichier_url;
    }

    /**
     * @return string|null
     */
    public function getStatut() {
        return $this->statut;
    }

    /**
     * @return string|null
     */
    public function getDateParticipation() {
        return $this->date_participation;
    }

    // ==================== SETTERS ====================

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = (int)$id;
    }

    /**
     * @param int $utilisateur_id
     */
    public function setUtilisateurId($utilisateur_id) {
        $this->utilisateur_id = (int)$utilisateur_id;
    }

    /**
     * @param int $evenement_id
     */
    public function setEvenementId($evenement_id) {
        $this->evenement_id = (int)$evenement_id;
    }

    /**
     * @param string $commentaire
     */
    public function setCommentaire($commentaire) {
        $this->commentaire = htmlspecialchars(strip_tags($commentaire));
    }

    /**
     * @param string|null $fichier_url
     */
    public function setFichierUrl($fichier_url) {
        $this->fichier_url = $fichier_url ? htmlspecialchars(strip_tags($fichier_url)) : null;
    }

    /**
     * @param string $statut
     */
    public function setStatut($statut) {
        $allowed = ['en_attente', 'approuve', 'rejete'];
        $this->statut = in_array($statut, $allowed) ? $statut : 'en_attente';
    }

    /**
     * @param string $date_participation
     */
    public function setDateParticipation($date_participation) {
        $this->date_participation = $date_participation;
    }

    // ==================== MÉTHODES CRUD ====================

    /**
     * Créer une participation
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                (utilisateur_id, evenement_id, commentaire, fichier_url, statut)
                VALUES (:utilisateur_id, :evenement_id, :commentaire, :fichier_url, :statut)";

        $stmt = $this->conn->prepare($query);

        $utilisateur_id = $this->getUtilisateurId();
        $evenement_id = $this->getEvenementId();
        $commentaire = $this->getCommentaire();
        $fichier_url = $this->getFichierUrl();
        $statut = $this->getStatut() ?: 'en_attente';

        $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->bindParam(':commentaire', $commentaire);
        $stmt->bindParam(':fichier_url', $fichier_url);
        $stmt->bindParam(':statut', $statut);

        if($stmt->execute()) {
            $this->setId($this->conn->lastInsertId());
            return true;
        }

        return false;
    }

    /**
     * Récupérer une participation par ID
     * @return bool
     */
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $id = $this->getId();
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->setUtilisateurId($row['utilisateur_id']);
            $this->setEvenementId($row['evenement_id']);
            $this->setCommentaire($row['commentaire']);
            $this->setFichierUrl($row['fichier_url']);
            $this->setStatut($row['statut']);
            $this->setDateParticipation($row['date_participation']);
            return true;
        }
        
        return false;
    }

    /**
     * Récupérer toutes les participations avec JOINTURE
     * @return PDOStatement
     */
    public function readAll() {
        $query = "SELECT p.*, 
                         u.nom as utilisateur_nom, u.prenom as utilisateur_prenom, u.email as utilisateur_email,
                         e.titre as evenement_titre, e.type as evenement_type
                  FROM " . $this->table . " p
                  INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
                  INNER JOIN evenements e ON p.evenement_id = e.id
                  ORDER BY p.date_participation DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Récupérer une participation avec JOINTURE par ID
     * @return array|null
     */
    public function readOneWithJoin() {
        $query = "SELECT p.*, 
                         u.nom as utilisateur_nom, u.prenom as utilisateur_prenom, u.email as utilisateur_email,
                         e.titre as evenement_titre, e.type as evenement_type, e.description as evenement_description
                  FROM " . $this->table . " p
                  INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
                  INNER JOIN evenements e ON p.evenement_id = e.id
                  WHERE p.id = :id 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $id = $this->getId();
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les participations par utilisateur
     * @return PDOStatement
     */
    public function readByUtilisateur() {
        $query = "SELECT p.*, e.titre as evenement_titre, e.type as evenement_type
                  FROM " . $this->table . " p
                  INNER JOIN evenements e ON p.evenement_id = e.id
                  WHERE p.utilisateur_id = :utilisateur_id
                  ORDER BY p.date_participation DESC";
        $stmt = $this->conn->prepare($query);
        $utilisateur_id = $this->getUtilisateurId();
        $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Récupérer les participations par événement
     * @return PDOStatement
     */
    public function readByEvenement() {
        $query = "SELECT p.*, u.nom as utilisateur_nom, u.prenom as utilisateur_prenom, u.email as utilisateur_email
                  FROM " . $this->table . " p
                  INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
                  WHERE p.evenement_id = :evenement_id
                  ORDER BY p.date_participation DESC";
        $stmt = $this->conn->prepare($query);
        $evenement_id = $this->getEvenementId();
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Récupérer les participations par statut
     * @param string $statut
     * @return PDOStatement
     */
    public function readByStatut($statut) {
        $query = "SELECT p.*, 
                         u.nom as utilisateur_nom, u.prenom as utilisateur_prenom, u.email as utilisateur_email,
                         e.titre as evenement_titre
                  FROM " . $this->table . " p
                  INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
                  INNER JOIN evenements e ON p.evenement_id = e.id
                  WHERE p.statut = :statut
                  ORDER BY p.date_participation DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':statut', $statut);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Mettre à jour une participation
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET commentaire = :commentaire,
                    fichier_url = :fichier_url,
                    statut = :statut
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $id = $this->getId();
        $commentaire = $this->getCommentaire();
        $fichier_url = $this->getFichierUrl();
        $statut = $this->getStatut();

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':commentaire', $commentaire);
        $stmt->bindParam(':fichier_url', $fichier_url);
        $stmt->bindParam(':statut', $statut);

        return $stmt->execute();
    }

    /**
     * Mettre à jour le statut d'une participation
     * @return bool
     */
    public function updateStatut() {
        $query = "UPDATE " . $this->table . " SET statut = :statut WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $id = $this->getId();
        $statut = $this->getStatut();
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':statut', $statut);
        return $stmt->execute();
    }

    /**
     * Supprimer une participation
     * @return bool
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $id = $this->getId();
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Compter le total des participations
     * @return int
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    /**
     * Compter les participations par statut
     * @param string $statut
     * @return int
     */
    public function countByStatut($statut) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE statut = :statut";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':statut', $statut);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    /**
     * Vérifier si une participation existe déjà
     * @return bool
     */
    public function exists() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE utilisateur_id = :utilisateur_id AND evenement_id = :evenement_id";
        $stmt = $this->conn->prepare($query);
        $utilisateur_id = $this->getUtilisateurId();
        $evenement_id = $this->getEvenementId();
        $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt->bindParam(':evenement_id', $evenement_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }

    /**
     * Rechercher des participations
     * @param string $keyword
     * @return PDOStatement
     */
    public function search($keyword) {
        $query = "SELECT p.*, 
                         u.nom as utilisateur_nom, u.prenom as utilisateur_prenom, u.email as utilisateur_email,
                         e.titre as evenement_titre
                  FROM " . $this->table . " p
                  INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
                  INNER JOIN evenements e ON p.evenement_id = e.id
                  WHERE u.nom LIKE :keyword 
                     OR u.prenom LIKE :keyword 
                     OR u.email LIKE :keyword
                     OR e.titre LIKE :keyword
                     OR p.commentaire LIKE :keyword
                  ORDER BY p.date_participation DESC";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . htmlspecialchars(strip_tags($keyword)) . "%";
        $stmt->bindParam(':keyword', $searchTerm);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Statistiques des participations
     * @return array
     */
    public function getStatistics() {
        $stats = [];
        
        // Total
        $stats['total'] = $this->count();
        
        // Par statut
        $stats['en_attente'] = $this->countByStatut('en_attente');
        $stats['approuve'] = $this->countByStatut('approuve');
        $stats['rejete'] = $this->countByStatut('rejete');
        
        // Récentes (7 derniers jours)
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE date_participation >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['recent'] = (int)$row['total'];
        
        return $stats;
    }

    /**
     * Récupérer une participation avec JOINTURE par ID
     * @param int $id
     * @return array|null
     */
    public function readOneWithJoinById($id) {
        $query = "SELECT p.*, 
                         u.nom as utilisateur_nom, u.prenom as utilisateur_prenom, u.email as utilisateur_email,
                         e.titre as evenement_titre, e.type as evenement_type, e.description as evenement_description
                  FROM " . $this->table . " p
                  INNER JOIN utilisateurs u ON p.utilisateur_id = u.id
                  INNER JOIN evenements e ON p.evenement_id = e.id
                  WHERE p.id = :id 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
