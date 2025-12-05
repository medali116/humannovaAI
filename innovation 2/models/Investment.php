<?php

class Investment {
    private $id;
    private $ideeId;
    private $utilisateurId;
    private $montant;
    private $statut;
    private $dateDemande;
    
    // Constructor
    public function __construct($id = null, $ideeId = null, $utilisateurId = null, $montant = null, $statut = 'en_attente', $dateDemande = null) {
        $this->id = $id;
        $this->ideeId = $ideeId;
        $this->utilisateurId = $utilisateurId;
        $this->montant = $montant;
        $this->statut = $statut;
        $this->dateDemande = $dateDemande;
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getIdeeId() {
        return $this->ideeId;
    }
    
    public function getUtilisateurId() {
        return $this->utilisateurId;
    }
    
    public function getMontant() {
        return $this->montant;
    }
    
    public function getStatut() {
        return $this->statut;
    }
    
    public function getDateDemande() {
        return $this->dateDemande;
    }
    
    // Setters
    public function setId($id) {
        $this->id = $id;
    }
    
    public function setIdeeId($ideeId) {
        $this->ideeId = $ideeId;
    }
    
    public function setUtilisateurId($utilisateurId) {
        $this->utilisateurId = $utilisateurId;
    }
    
    public function setMontant($montant) {
        $this->montant = $montant;
    }
    
    public function setStatut($statut) {
        $this->statut = $statut;
    }
    
    public function setDateDemande($dateDemande) {
        $this->dateDemande = $dateDemande;
    }
    
    // Convenience method to check if investment is pending
    public function isPending() {
        return $this->statut === 'en_attente';
    }
    
    // Convenience method to check if investment is accepted
    public function isAccepted() {
        return $this->statut === 'accepte';
    }
    
    // Convenience method to check if investment is refused
    public function isRefused() {
        return $this->statut === 'refuse';
    }
}
?>