<?php
require_once '../Config.php';
require_once '../Model/Job.php'; 

class JobController {
    // Récupérer tous les jobs
    public function getJobs() {
        $conn = config::getConnexion();
        $sql = "SELECT * FROM jobs";

        try {
            $query = $conn->prepare($sql);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    // Ajouter un job
    public function addJob($job) {
        $conn = config::getConnexion();
        $sql = "INSERT INTO jobs (title, company, salary, description, location, date_posted, category, contract_type, logo)
                VALUES (:title, :company, :salary, :description, :location, :date_posted, :category, :contract_type, :logo)";

        try {
            $query = $conn->prepare($sql);
            $query->execute([
                ':title' => $job['title'] ?? null,
                ':company' => $job['company'] ?? null,
                ':salary' => $job['salary'] ?? null,
                ':description' => $job['description'] ?? null,
                ':location' => $job['location'] ?? null,
                ':date_posted' => $job['date_posted'] ?? null,
                ':category' => $job['category'] ?? null,
                ':contract_type' => $job['contract_type'] ?? null,
                ':logo' => $job['logo'] ?? null,
            ]);
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    // Supprimer un job
    public function deleteJob($id){
        $conn = config::getConnexion();
        $sql = "DELETE FROM jobs WHERE id = :id";
        try{
            $query = $conn->prepare($sql);
            $query->execute([':id' => $id]);
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    // Récupérer un job par ID
    public function getJobById($id){
        $conn = config::getConnexion();
        $sql = "SELECT * FROM jobs WHERE id = :id";
        try {
            $query = $conn->prepare($sql);
            $query->execute([':id' => $id]);
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    // Mettre à jour un job
    public function updateJob($id, $job){
        $conn = config::getConnexion();
        $sql = "UPDATE jobs SET title = :title, company = :company, salary = :salary, description = :description,
                location = :location, date_posted = :date_posted, category = :category, contract_type = :contract_type, logo = :logo
                WHERE id = :id";
        try {
            $query = $conn->prepare($sql);
            $query->execute([
                ':title' => $job['title'] ?? null,
                ':company' => $job['company'] ?? null,
                ':salary' => $job['salary'] ?? null,
                ':description' => $job['description'] ?? null,
                ':location' => $job['location'] ?? null,
                ':date_posted' => $job['date_posted'] ?? null,
                ':category' => $job['category'] ?? null,
                ':contract_type' => $job['contract_type'] ?? null,
                ':logo' => $job['logo'] ?? null,
                ':id' => $id
            ]);
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

}
