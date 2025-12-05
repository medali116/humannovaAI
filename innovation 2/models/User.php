<?php
/**
 * User Entity Model
 * Pure entity class with properties, constructor, getters and setters only
 */
class User {
    private $id;
    private $username;
    private $fullname;
    private $email;
    private $password;
    private $isAdmin;
    
    /**
     * Constructor
     * @param int|null $id
     * @param string|null $username
     * @param string|null $fullname
     * @param string|null $email
     * @param string|null $password
     * @param bool|int|null $isAdmin
     */
    public function __construct($id = null, $username = null, $fullname = null, $email = null, $password = null, $isAdmin = null) {
        $this->id = $id;
        $this->username = $username;
        $this->fullname = $fullname;
        $this->email = $email;
        $this->password = $password;
        $this->isAdmin = $isAdmin;
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getFullname() {
        return $this->fullname;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getPassword() {
        return $this->password;
    }
    
    public function getIsAdmin() {
        return $this->isAdmin;
    }
    
    public function isAdmin() {
        return $this->isAdmin == 1;
    }
    
    // Setters
    public function setId($id) {
        $this->id = $id;
    }
    
    public function setUsername($username) {
        $this->username = $username;
    }
    
    public function setFullname($fullname) {
        $this->fullname = $fullname;
    }
    
    public function setEmail($email) {
        $this->email = $email;
    }
    
    public function setPassword($password) {
        $this->password = $password;
    }
    
    public function setIsAdmin($isAdmin) {
        $this->isAdmin = $isAdmin;
    }
}
?>
