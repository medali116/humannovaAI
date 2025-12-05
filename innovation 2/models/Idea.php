<?php
/**
 * Idea Entity Model
 * Pure entity class with properties, constructor, getters and setters only
 */
class Idea {
    private $id;
    private $utilisateurId;
    private $titre;
    private $description;
    private $dateCreation;
    
    /**
     * Constructor
     * @param int|null $id
     * @param int|null $utilisateurId
     * @param string|null $titre
     * @param string|null $description
     * @param string|null $dateCreation
     */
    public function __construct($id = null, $utilisateurId = null, $titre = null, $description = null, $dateCreation = null) {
        $this->id = $id;
        $this->utilisateurId = $utilisateurId;
        $this->titre = $titre;
        $this->description = $description;
        $this->dateCreation = $dateCreation;
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getUtilisateurId() {
        return $this->utilisateurId;
    }
    
    public function getTitre() {
        return $this->titre;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getDateCreation() {
        return $this->dateCreation;
    }
    
    // Setters
    public function setId($id) {
        $this->id = $id;
    }
    
    public function setUtilisateurId($utilisateurId) {
        $this->utilisateurId = $utilisateurId;
    }
    
    public function setTitre($titre) {
        $this->titre = $titre;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }
    
    public function setDateCreation($dateCreation) {
        $this->dateCreation = $dateCreation;
    }
}
?>
