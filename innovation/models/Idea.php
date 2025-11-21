<?php
/**
 * Idea Model
 * Handles all database operations related to ideas
 */
class Idea {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all ideas with user information
     * @return array
     */
    public function getAllIdeas() {
        try {
            $stmt = $this->pdo->query("SELECT idee.*, utilisateurs.fullname, utilisateurs.username 
                                       FROM idee 
                                       JOIN utilisateurs ON idee.utilisateur_id = utilisateurs.id 
                                       ORDER BY idee.date_creation DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching ideas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get ideas by user ID
     * @param int $userId
     * @return array
     */
    public function getIdeasByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT idee.*, utilisateurs.fullname, utilisateurs.username 
                                         FROM idee 
                                         JOIN utilisateurs ON idee.utilisateur_id = utilisateurs.id 
                                         WHERE idee.utilisateur_id = :user_id 
                                         ORDER BY idee.date_creation DESC");
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching user ideas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get idea by ID
     * @param int $id
     * @return array|false
     */
    public function getIdeaById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT idee.*, utilisateurs.fullname, utilisateurs.username 
                                         FROM idee 
                                         JOIN utilisateurs ON idee.utilisateur_id = utilisateurs.id 
                                         WHERE idee.id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching idea: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new idea
     * @param array $data
     * @return int|false Idea ID or false on failure
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO idee (utilisateur_id, titre, description) 
                                         VALUES (:utilisateur_id, :titre, :description)");
            $stmt->execute([
                'utilisateur_id' => $data['utilisateur_id'],
                'titre' => $data['titre'],
                'description' => $data['description']
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating idea: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update idea
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $stmt = $this->pdo->prepare("UPDATE idee 
                                         SET titre = :titre, description = :description 
                                         WHERE id = :id");
            return $stmt->execute([
                'titre' => $data['titre'],
                'description' => $data['description'],
                'id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating idea: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete idea
     * @param int $id
     * @param int $userId User ID to verify ownership
     * @return bool
     */
    public function delete($id, $userId) {
        try {
            // First check if the idea belongs to the user
            $stmt = $this->pdo->prepare("SELECT utilisateur_id FROM idee WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $idea = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$idea || $idea['utilisateur_id'] != $userId) {
                return false; // Idea doesn't exist or doesn't belong to user
            }
            
            // Delete the idea
            $stmt = $this->pdo->prepare("DELETE FROM idee WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting idea: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user owns the idea
     * @param int $ideaId
     * @param int $userId
     * @return bool
     */
    public function isOwner($ideaId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT utilisateur_id FROM idee WHERE id = :id");
            $stmt->execute(['id' => $ideaId]);
            $idea = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $idea && $idea['utilisateur_id'] == $userId;
        } catch (PDOException $e) {
            error_log("Error checking idea ownership: " . $e->getMessage());
            return false;
        }
    }
}
?>
