<?php
class Job {
    private $id;
    private $title;
    private $company;
    private $salary;
    private $description;
    private $location;
    private $date_posted;
    private $category;
    private $contract_type;
    private $logo;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->company = $data['company'] ?? null;
        $this->salary = $data['salary'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->date_posted = $data['date_posted'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->contract_type = $data['contract_type'] ?? null;
        $this->logo = $data['logo'] ?? null;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getCompany() {
        return $this->company;
    }

    public function setCompany($company) {
        $this->company = $company;
    }

    public function getSalary() {
        return $this->salary;
    }

    public function setSalary($salary) {
        $this->salary = $salary;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getLocation() {
        return $this->location;
    }

    public function setLocation($location) {
        $this->location = $location;
    }

    public function getDatePosted() {
        return $this->date_posted;
    }

    public function setDatePosted($date_posted) {
        $this->date_posted = $date_posted;
    }

    public function getCategory() {
        return $this->category;
    }

    public function setCategory($category) {
        $this->category = $category;
    }

    public function getContractType() {
        return $this->contract_type;
    }

    public function setContractType($contract_type) {
        $this->contract_type = $contract_type;
    }

    public function getLogo() {
        return $this->logo;
    }

    public function setLogo($logo) {
        $this->logo = $logo;
    }
}
?>
