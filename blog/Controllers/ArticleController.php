<?php
require_once "Models/Article.php";
require_once "Models/Interaction.php";

class ArticleController {
    private $model;

    public function __construct() {
        $this->model = new Article(); // Article internally creates PDO connection
    }

    // List all articles
    public function index() {
        $articles = $this->model->readAll();
        include "Views/articles/index.php";
    }

    // Show a single article with interactions
    public function show($id) {
        $article = $this->model->read($id);
        include "Views/articles/show.php";
    }

    // Create a new article
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->setTitre($_POST['titre']);
            $this->model->setContenu($_POST['contenu']);
            $this->model->create();
            header("Location: index.php?controller=article&action=index");
            exit;
        }
        include "Views/articles/create.php";
    }

    // Edit an existing article
    public function edit($id) {
        $article = $this->model->read($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->setId($id);
            $this->model->setTitre($_POST['titre']);
            $this->model->setContenu($_POST['contenu']);
            $this->model->update();
            header("Location: index.php?controller=article&action=index");
            exit;
        }

        include "Views/articles/edit.php";
    }

    // Delete an article
    public function delete($id) {
        $this->model->delete($id);
        header("Location: index.php?controller=article&action=index");
        exit;
    }
}
?>
