<?php
require_once 'models/ArticleModel.php';
$articleModel = new ArticleModel();
$articles = $articleModel->getAllArticles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRISM FLUX - Digital Innovation Studio</title>
    <link rel="stylesheet" href="views/css/templatemo-prism-flux.css">
</head>
<body>
    <?php include 'views/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero" id="home">
        <!-- Votre contenu hero -->
    </section>
    
    <?php include 'views/about.php'; ?>
    <?php include 'views/stats.php'; ?>
    <?php include 'views/skills.php'; ?>
    <?php include 'views/contact.php'; ?>
    
    <?php include 'views/footer.php'; ?>
    
    <script src="views/js/templatemo-prism-scripts.js"></script>
</body>
</html>