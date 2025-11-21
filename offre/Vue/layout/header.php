<?php
if (!isset($title)) {
    $title = 'HUMANnova ai';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header class="site-header">
        <a class="site-title" href="ListJobs.php"><img src="https://via.placeholder.com/120x36?text=HUMANnova+ai" alt="logo"> HUMANnova ai</a>
        <nav class="main-nav">
            <a href="#">Offres d'emploi</a>
            <a href="#">Formations</a>
            <a href="#">Contact</a>
        </nav>
        <div class="header-actions">
            <a class="btn-primary" href="../Vue/ListJobs.php">Espace Candidat</a>
            <a class="btn-cta" href="../Vue/ListJobs.php?role=recruiter">Espace Recruteur</a>
        </div>
    </header>

    <div class="site-top">
        <h1 style="margin:14px 18px 6px; font-size:32px;">Recherche d'emploi</h1>
    </div>
    <div class="search-panel">
        <div class="search-box">
            <input type="text" placeholder="Rechercher une offre, un mot-clé, une localisation..." style="width:100%; background:transparent; border:0; outline:0; color:#cfe9ef; font-size:15px">
        </div>
    </div>
