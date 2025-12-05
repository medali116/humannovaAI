<?php
// Blog integration: check if viewing article details
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle interaction form submissions (validation done by JavaScript)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['controller']) && $_GET['controller'] === 'interaction') {
  $blogPath = $_SERVER['DOCUMENT_ROOT'] . "/blog";
  require_once $blogPath . "/Models/Interaction.php";
  
  $article_id = isset($_POST['article_id']) ? (int)$_POST['article_id'] : null;
  $type = isset($_POST['type']) ? trim($_POST['type']) : null;
  $auteur = isset($_POST['auteur']) ? trim($_POST['auteur']) : null;
  $email = isset($_POST['email']) ? trim($_POST['email']) : null;
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';

  $interactionModel = new Interaction();
  $interactionModel->setArticleId($article_id);
  $interactionModel->setType($type);
  $interactionModel->setAuteur($auteur);
  $interactionModel->setEmail($email);
  $interactionModel->setMessage($message);
  $interactionModel->create();
  
  $_SESSION['success'] = "Votre interaction a été ajoutée avec succès!";
  header("Location: index.php?action=show&id=" . $article_id);
  exit;
}

// Handle delete interaction
if (isset($_GET['deleteId']) && isset($_GET['id'])) {
  $blogPath = $_SERVER['DOCUMENT_ROOT'] . "/blog";
  require_once $blogPath . "/Models/Interaction.php";
  
  $interactionModel = new Interaction();
  $interactionModel->delete($_GET['deleteId']);
  
  header("Location: index.php?action=show&id=" . $_GET['id']);
  exit;
}

// Handle article create (validation done by JavaScript)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create' && !isset($_GET['id'])) {
  $blogPath = $_SERVER['DOCUMENT_ROOT'] . "/blog";
  require_once $blogPath . "/Models/Article.php";
  
  $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
  $contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';
  
  $articleModel = new Article();
  $articleModel->setTitre($titre);
  $articleModel->setContenu($contenu);
  $articleModel->create();
  header("Location: index.php?controller=article&action=index");
  exit;
}

// Handle article edit (validation done by JavaScript)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $blogPath = $_SERVER['DOCUMENT_ROOT'] . "/blog";
  require_once $blogPath . "/Models/Article.php";
  
  $id = (int)$_GET['id'];
  $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
  $contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';
  
  $articleModel = new Article();
  $articleModel->setId($id);
  $articleModel->setTitre($titre);
  $articleModel->setContenu($contenu);
  $articleModel->update();
  header("Location: index.php?controller=article&action=index");
  exit;
}

// Handle article delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
  $blogPath = $_SERVER['DOCUMENT_ROOT'] . "/blog";
  require_once $blogPath . "/Models/Article.php";
  
  $id = (int)$_GET['id'];
  $articleModel = new Article();
  $articleModel->delete($id);
  header("Location: index.php?controller=article&action=index");
  exit;
}

$blogPath = $_SERVER['DOCUMENT_ROOT'] . "/blog";
require_once $blogPath . "/Models/Article.php";
require_once $blogPath . "/Models/Interaction.php";

$showArticleDetail = isset($_GET['action']) && $_GET['action'] === 'show' && isset($_GET['id']);
$showArticlesList = isset($_GET['action']) && $_GET['action'] === 'index';
$showArticleCreate = isset($_GET['action']) && $_GET['action'] === 'create' && !isset($_GET['id']);
$showArticleEdit = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$isBlog = $showArticleDetail || $showArticlesList || $showArticleCreate || $showArticleEdit;
?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PRISM FLUX - Digital Innovation Studio</title>
    <link rel="stylesheet" href="templatemo-prism-flux.css" />
  </head>
  <body>
    <!-- Loading Screen -->
    <div class="loader" id="loader">
      <div class="loader-content">
        <div class="loader-prism">
          <div class="prism-face"></div>
          <div class="prism-face"></div>
          <div class="prism-face"></div>
        </div>
        <div style="color: var(--accent-purple); font-size: 18px; text-transform: uppercase; letter-spacing: 3px;">Refracting Reality...</div>
      </div>
    </div>

    <script>
      setTimeout(function() {
        var loader = document.getElementById('loader');
        if (loader && loader.style.display !== 'none') {
          loader.style.display = 'none';
        }
      }, 3000);
    </script>

    <!-- Navigation Header -->
    <header class="header" id="header">
      <nav class="nav-container">
        <a href="index.php" class="logo">
          <div class="logo-icon">
            <div class="logo-prism">
              <div class="prism-shape"></div>
            </div>
          </div>
          <span class="logo-text">
            <span class="prism">PRISM</span>
            <span class="flux">FLUX</span>
          </span>
        </a>

        <ul class="nav-menu" id="navMenu">
          <li><a href="index.php" class="nav-link active">Home</a></li>
          <li><a href="#about" class="nav-link">About</a></li>
          <li><a href="#stats" class="nav-link">Metrics</a></li>
          <li><a href="#skills" class="nav-link">Arsenal</a></li>
          <li><a href="#blog" class="nav-link">Blog</a></li>
          <li><a href="#contact" class="nav-link">Contact</a></li>
        </ul>

        <div class="menu-toggle" id="menuToggle">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </nav>
    </header>

    <?php if (!$isBlog): ?>
    <!-- Hero Section with 3D Carousel -->
    <section class="hero" id="home">
      <div class="carousel-container">
        <div class="carousel" id="carousel"></div>
        <div class="carousel-controls">
          <button class="carousel-btn" id="prevBtn">‹</button>
          <button class="carousel-btn" id="nextBtn">›</button>
        </div>
        <div class="carousel-indicators" id="indicators"></div>
      </div>
    </section>

    <!-- About Section -->
    <section class="philosophy-section" id="about">
      <div class="philosophy-container">
        <div class="prism-line"></div>
        <h2 class="philosophy-headline">Refracting Ideas<br />Into Reality</h2>
        <p class="philosophy-subheading">
          At PRISM FLUX, we transform complex challenges into elegant solutions
          through the convergence of cutting-edge technology and visionary
          design. Every project is a spectrum of possibilities waiting to be
          discovered.
        </p>
        <div class="philosophy-pillars">
          <div class="pillar">
            <div class="pillar-icon">💎</div>
            <h3 class="pillar-title">Innovation</h3>
            <p class="pillar-description">Breaking boundaries with revolutionary approaches that redefine industry standards and push the limits of what's possible.</p>
          </div>
          <div class="pillar">
            <div class="pillar-icon">🔬</div>
            <h3 class="pillar-title">Precision</h3>
            <p class="pillar-description">Meticulous attention to detail ensures every pixel, every line of code, and every interaction is perfectly crafted.</p>
          </div>
          <div class="pillar">
            <div class="pillar-icon">∞</div>
            <h3 class="pillar-title">Evolution</h3>
            <p class="pillar-description">Continuous adaptation and growth, staying ahead of trends while building timeless solutions for tomorrow.</p>
          </div>
        </div>
        <div class="philosophy-particles" id="particles"></div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section" id="stats">
      <div class="section-header">
        <h2 class="section-title">Performance Metrics</h2>
        <p class="section-subtitle">Real-time analytics and achievements powered by cutting-edge technology</p>
      </div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">🚀</div>
          <div class="stat-number" data-target="150">0</div>
          <div class="stat-label">Projects Completed</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⚡</div>
          <div class="stat-number" data-target="99">0</div>
          <div class="stat-label">Client Satisfaction %</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🏆</div>
          <div class="stat-number" data-target="25">0</div>
          <div class="stat-label">Industry Awards</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">💎</div>
          <div class="stat-number" data-target="500">0</div>
          <div class="stat-label">Code Commits Daily</div>
        </div>
      </div>
    </section>

    <!-- Skills Section -->
    <section class="skills-section" id="skills">
      <div class="skills-container">
        <div class="section-header">
          <h2 class="section-title">Technical Arsenal</h2>
          <p class="section-subtitle">Mastery of cutting-edge technologies and frameworks</p>
        </div>
        <div class="skill-categories">
          <div class="category-tab active" data-category="all">All Skills</div>
          <div class="category-tab" data-category="frontend">Frontend</div>
          <div class="category-tab" data-category="backend">Backend</div>
          <div class="category-tab" data-category="cloud">Cloud & DevOps</div>
          <div class="category-tab" data-category="emerging">Emerging Tech</div>
        </div>
        <div class="skills-hexagon-grid" id="skillsGrid"></div>
      </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
      <div class="section-header">
        <h2 class="section-title">Initialize Connection</h2>
        <p class="section-subtitle">Ready to transform your vision into reality? Let's connect.</p>
      </div>
      <div class="contact-container">
        <div class="contact-info">
          <a href="https://maps.google.com/?q=Silicon+Valley+CA+94025" target="_blank" class="info-item">
            <div class="info-icon">📍</div>
            <div class="info-text"><h4>Location</h4><p>Silicon Valley, CA 94025</p></div>
          </a>
          <a href="mailto:hello@prismflux.io" class="info-item">
            <div class="info-icon">📧</div>
            <div class="info-text"><h4>Email</h4><p>hello@prismflux.io</p></div>
          </a>
          <a href="tel:+15551234567" class="info-item">
            <div class="info-icon">📱</div>
            <div class="info-text"><h4>Phone</h4><p>+1 (555) 123-4567</p></div>
          </a>
        </div>
        <form class="contact-form" id="contactForm">
          <div class="form-group"><label for="name">Name</label><input type="text" id="name" name="name" required /></div>
          <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" required /></div>
          <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" name="subject" required /></div>
          <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" required></textarea></div>
          <button type="submit" class="submit-btn">Transmit Message</button>
        </form>
      </div>
    </section>
    <?php endif; ?>

    <!-- Blog Section -->
    <?php if ($isBlog): ?>
    <section class="blog-section" id="blog" style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%); padding: 60px 20px; min-height: 500px;">
      <div class="section-header">
        <h2 class="section-title" style="color: var(--accent-cyan);">Latest Articles</h2>
        <p class="section-subtitle">Discover our latest insights and technical content</p>
      </div>

      <div class="blog-container" style="max-width: 1200px; margin: 40px auto;">
        <?php
          if ($showArticleCreate) {
            echo '<div style="color: #fff; max-width: 600px; margin: 0 auto;">';
            echo '<h2 style="color: var(--accent-cyan); margin-bottom: 20px;">Create New Article</h2>';
            echo '<form method="post" action="index.php?action=create" style="background: rgba(0, 255, 255, 0.05); padding: 20px; border-radius: 4px; border: 1px solid var(--accent-cyan);">';
            echo '<div style="margin-bottom: 15px;">';
            echo '<label style="color: #ddd; display: block; margin-bottom: 5px;">Title:</label>';
            echo '<input type="text" name="titre" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box;">';
            echo '</div>';
            echo '<div style="margin-bottom: 15px;">';
            echo '<label style="color: #ddd; display: block; margin-bottom: 5px;">Content:</label>';
            echo '<textarea name="contenu" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box; font-family: inherit; min-height: 200px;"></textarea>';
            echo '</div>';
            echo '<button type="submit" style="padding: 8px 16px; background: var(--accent-cyan); color: #000; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Create</button>';
            echo ' <a href="index.php?controller=article&action=index" style="color: var(--accent-cyan); text-decoration: none; margin-left: 10px;">Back to Articles</a>';
            echo '</form>';
            echo '</div>';
          } elseif ($showArticleEdit) {
            $articleId = (int)$_GET['id'];
            $articleModel = new Article();
            $article = $articleModel->readById($articleId);
            
            if (!$article) {
              echo '<p style="text-align: center; color: #aaa;">Article not found.</p>';
            } else {
              echo '<div style="color: #fff; max-width: 600px; margin: 0 auto;">';
              echo '<h2 style="color: var(--accent-cyan); margin-bottom: 20px;">Edit Article</h2>';
              echo '<form method="post" action="index.php?action=edit&id=' . $article['id'] . '" style="background: rgba(0, 255, 255, 0.05); padding: 20px; border-radius: 4px; border: 1px solid var(--accent-cyan);">';
              echo '<div style="margin-bottom: 15px;">';
              echo '<label style="color: #ddd; display: block; margin-bottom: 5px;">Title:</label>';
              echo '<input type="text" name="titre" value="' . htmlspecialchars($article['titre']) . '" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box;">';
              echo '</div>';
              echo '<div style="margin-bottom: 15px;">';
              echo '<label style="color: #ddd; display: block; margin-bottom: 5px;">Content:</label>';
              echo '<textarea name="contenu" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box; font-family: inherit; min-height: 200px;">' . htmlspecialchars($article['contenu']) . '</textarea>';
              echo '</div>';
              echo '<button type="submit" style="padding: 8px 16px; background: var(--accent-cyan); color: #000; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Update</button>';
              echo ' <a href="index.php?controller=article&action=index" style="color: var(--accent-cyan); text-decoration: none; margin-left: 10px;">Back</a>';
              echo '</form>';
              echo '</div>';
            }
          } elseif ($showArticleDetail) {
            $articleId = (int)$_GET['id'];
            $articleModel = new Article();
            $article = $articleModel->readById($articleId);
            
            if (!$article) {
              echo '<p style="text-align: center; color: #aaa;">Article not found.</p>';
            } else {
              $success = $_SESSION['success'] ?? null;
              unset($_SESSION['success']);
              
              echo '<div style="color: #fff; margin-bottom: 40px;">';
              echo '<h2 style="color: var(--accent-cyan); margin-bottom: 10px;">' . htmlspecialchars($article['titre']) . '</h2>';
              echo '<p style="color: #aaa; font-size: 0.9em; margin-bottom: 20px;"><em>Created: ' . $article['date_creation'] . '</em></p>';
              echo '<div style="color: #ddd; line-height: 1.8; margin-bottom: 30px;">' . nl2br(htmlspecialchars($article['contenu'])) . '</div>';
              
              if ($success) {
                echo '<div style="background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;"><strong>Succès!</strong> ' . htmlspecialchars($success) . '</div>';
              }
              
              echo '<h3 style="color: var(--accent-cyan); margin-top: 30px;">Interactions</h3>';
              
              $interactionModel = new Interaction();
              $interactions = [];
              $likeCount = 0;
              try {
                $interactions = $interactionModel->readAllByArticle($article['id']);
                $likeCount = $interactionModel->countLikes($article['id']);
              } catch (Exception $e) {
                echo "<p style='color:#aaa;'>Error loading interactions</p>";
              }
              
              echo '<p style="color: #ddd; margin: 15px 0;"><strong>Likes: ' . $likeCount . '</strong></p>';
              
              echo '<form method="post" action="index.php?action=show&id=' . $article['id'] . '&controller=interaction&formAction=create" style="margin-bottom:20px; background: rgba(0, 255, 255, 0.05); padding: 15px; border-radius: 4px; border: 1px solid var(--accent-cyan);">';
              echo '<input type="hidden" name="article_id" value="' . $article['id'] . '">';
              echo '<input type="hidden" name="type" value="like">';
              echo '<div style="display: flex; gap: 10px; align-items: flex-end;">';
              echo '<div><label style="color: #ddd; display: block; margin-bottom: 5px;">Your name:</label>';
              echo '<input type="text" name="auteur" required style="padding: 8px; border-radius: 4px; border: 1px solid #666; background: #1a1f3a; color: #fff;">';
              echo '</div>';
              echo '<div><label style="color: #ddd; display: block; margin-bottom: 5px;">Your email:</label>';
              echo '<input type="email" name="email" required style="padding: 8px; border-radius: 4px; border: 1px solid #666; background: #1a1f3a; color: #fff;">';
              echo '</div>';
              echo '<button type="submit" style="padding: 8px 16px; background: var(--accent-cyan); color: #000; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Like</button>';
              echo '</div>';
              echo '</form>';
              
              if (!empty($interactions)) {
                echo '<div style="background: rgba(0, 255, 255, 0.05); padding: 15px; border-radius: 4px; border: 1px solid rgba(0, 255, 255, 0.2); margin-bottom: 20px;">';
                echo '<h4 style="color: var(--accent-cyan); margin-top: 0;">Recent Activity</h4>';
                echo '<ul style="list-style: none; padding: 0; margin: 0;">';
                foreach ($interactions as $i) {
                  echo '<li style="padding: 8px 0; border-bottom: 1px solid rgba(0, 255, 255, 0.1); color: #ddd;">';
                  if ($i['type'] === 'like') {
                    echo '👍 ' . htmlspecialchars($i['auteur']) . ' (' . htmlspecialchars($i['email']) . ')';
                  } else {
                    echo '💬 ' . htmlspecialchars($i['auteur']) . ': ' . htmlspecialchars($i['message']);
                  }
                  echo ' <a href="index.php?action=show&id=' . $article['id'] . '&deleteId=' . $i['id'] . '" style="color: #ff6b6b; text-decoration: none; margin-left: 10px; font-size: 0.85em;">Delete</a>';
                  echo '</li>';
                }
                echo '</ul>';
                echo '</div>';
              }
              
              echo '<div style="background: rgba(0, 255, 255, 0.05); padding: 15px; border-radius: 4px; border: 1px solid var(--accent-cyan);">';
              echo '<h4 style="color: var(--accent-cyan); margin-top: 0;">Add Comment</h4>';
              echo '<form method="post" action="index.php?action=show&id=' . $article['id'] . '&controller=interaction&formAction=create">';
              echo '<input type="hidden" name="article_id" value="' . $article['id'] . '">';
              echo '<input type="hidden" name="type" value="comment">';
              echo '<div style="margin-bottom: 10px;"><label style="color: #ddd; display: block; margin-bottom: 5px;">Your name:</label>';
              echo '<input type="text" name="auteur" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box;">';
              echo '</div>';
              echo '<div style="margin-bottom: 10px;"><label style="color: #ddd; display: block; margin-bottom: 5px;">Your email:</label>';
              echo '<input type="email" name="email" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box;">';
              echo '</div>';
              echo '<div style="margin-bottom: 10px;"><label style="color: #ddd; display: block; margin-bottom: 5px;">Comment:</label>';
              echo '<textarea name="message" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box; font-family: inherit; min-height: 100px;"></textarea>';
              echo '</div>';
              echo '<button type="submit" style="padding: 8px 16px; background: var(--accent-cyan); color: #000; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Add Comment</button>';
              echo '</form>';
              echo '</div>';
              
              echo '<div style="margin-top: 20px;"><a href="index.php#blog" style="color: var(--accent-cyan); text-decoration: none; font-weight: bold;">← Back to Blog</a></div>';
              echo '</div>';
            }
          } else {
            $articleModel = new Article();
            $articles = $articleModel->readAll();

            if (isset($_GET['controller']) && $_GET['controller'] === 'article') {
              echo '<div style="color: #fff;">';
              echo '<h2 style="color: var(--accent-cyan);">Articles</h2>';
              echo '<p><a href="index.php?action=create" style="color: var(--accent-cyan);">Create New Article</a></p>';
              echo '<div style="overflow:auto;">';
              echo '<table style="border-collapse: collapse; width:100%; color:#000; background:#fff;">';
              echo '<thead><tr style="background: #eee;"><th style="border:1px solid #ccc; padding:8px;">ID</th><th style="border:1px solid #ccc; padding:8px;">Title</th><th style="border:1px solid #ccc; padding:8px;">Date</th><th style="border:1px solid #ccc; padding:8px;">Actions</th></tr></thead>';
              echo '<tbody>';
              foreach ($articles as $a) {
                echo '<tr>';
                echo '<td style="border:1px solid #ccc; padding:8px; text-align:center;">' . $a['id'] . '</td>';
                echo '<td style="border:1px solid #ccc; padding:8px;">' . htmlspecialchars($a['titre']) . '</td>';
                echo '<td style="border:1px solid #ccc; padding:8px;">' . $a['date_creation'] . '</td>';
                echo '<td style="border:1px solid #ccc; padding:8px; text-align:center;">';
                echo '<a href="index.php?action=show&id=' . $a['id'] . '" style="color: var(--accent-cyan);">View</a> | ';
                echo '<a href="index.php?action=edit&id=' . $a['id'] . '" style="color: var(--accent-cyan);">Edit</a> | ';
                echo '<a href="index.php?action=delete&id=' . $a['id'] . '" onclick="return confirm(\'Delete this article?\')" style="color: #ff6b6b;">Delete</a>';
                echo '</td>';
                echo '</tr>';
              }
              echo '</tbody>';
              echo '</table>';
              echo '</div>';
              echo '</div>';
            } else {
              if (!empty($articles)) {
                echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">';
                foreach ($articles as $article) {
                    echo '<div style="background: rgba(0, 255, 255, 0.05); border: 1px solid var(--accent-cyan); border-radius: 8px; padding: 20px; color: #fff; transition: all 0.3s ease;">';
                    echo '<h3 style="color: var(--accent-cyan); margin-top: 0;">Blog</h3>';
                    echo '<p style="color: #aaa; font-size: 0.95em; margin: 10px 0;">Votre avis compte énormément ! Dites-nous ce que vous en pensez en laissant un commentaire.</p>';
                    echo '<a href="index.php?action=show&id=' . $article['id'] . '#blog" style="color: var(--accent-cyan); text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px;">Read More →</a>';
                    echo '</div>';
                }
                echo '</div>';
              } else {
                echo '<p style="text-align: center; color: #aaa;">No articles yet. Check back soon!</p>';
              }
            }
          }
        ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
      <div class="footer-content">
        <div class="footer-brand">
          <div class="footer-logo">
            <div class="logo-icon">
              <div class="logo-prism">
                <div class="prism-shape"></div>
              </div>
            </div>
            <span class="logo-text">
              <span class="prism">PRISM</span>
              <span class="flux">FLUX</span>
            </span>
          </div>
          <p class="footer-description">
            Refracting complex challenges into brilliant solutions through the
            convergence of art, science, and technology.
          </p>
          <div class="footer-social">
            <a href="#" class="social-icon">f</a>
            <a href="#" class="social-icon">t</a>
            <a href="#" class="social-icon">in</a>
            <a href="#" class="social-icon">ig</a>
          </div>
        </div>

        <div class="footer-section">
          <h4>Services</h4>
          <div class="footer-links">
            <a href="#">Web Development</a>
            <a href="#">App Development</a>
            <a href="#">Cloud Solutions</a>
            <a href="#">AI Integration</a>
          </div>
        </div>

        <div class="footer-section">
          <h4>Company</h4>
          <div class="footer-links">
            <a href="#">About Us</a>
            <a href="#">Our Team</a>
            <a href="#">Careers</a>
            <a href="#">Press Kit</a>
          </div>
        </div>

        <div class="footer-section">
          <h4>Resources</h4>
          <div class="footer-links">
            <a href="#">Documentation</a>
            <a href="#">API Reference</a>
            <a href="#">Blog</a>
            <a href="#">Support</a>
          </div>
        </div>

        <div class="footer-section">
          <h4>Admin</h4>
          <div class="footer-links">
            <a href="index.php?controller=article&action=index">Manage Articles</a>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div class="copyright">© 2026 PRISM FLUX. All rights reserved.</div>
        <div class="footer-credits">
          Designed by
          <a href="https://templatemo.com" rel="nofollow" target="_blank">TemplateMo</a>
        </div>
      </div>
    </footer>
    <script src="templatemo-prism-scripts.js"></script>
    
    <!-- Validation JavaScript -->
    <script>
      // Validation pour formulaires d'articles (Create/Edit)
      function validateArticleForm(form) {
        const titre = form.querySelector('input[name="titre"]');
        const contenu = form.querySelector('textarea[name="contenu"]');
        let hasError = false;
        
        // Effacer les erreurs précédentes
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        
        // Valider le titre
        if (!titre || !titre.value.trim()) {
          showError(titre, 'Le titre est requis.');
          hasError = true;
        } else if (titre.value.trim().length < 3) {
          showError(titre, 'Le titre doit contenir au moins 3 caractères.');
          hasError = true;
        } else if (titre.value.trim().length > 255) {
          showError(titre, 'Le titre ne peut pas dépasser 255 caractères.');
          hasError = true;
        }
        
        // Valider le contenu
        if (!contenu || !contenu.value.trim()) {
          showError(contenu, 'Le contenu est requis.');
          hasError = true;
        } else if (contenu.value.trim().length < 10) {
          showError(contenu, 'Le contenu doit contenir au moins 10 caractères.');
          hasError = true;
        }
        
        return !hasError;
      }
      
      // Validation pour formulaires de like
      function validateLikeForm(form) {
        const auteur = form.querySelector('input[name="auteur"]');
        const email = form.querySelector('input[name="email"]');
        let hasError = false;
        
        // Effacer les erreurs précédentes
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        
        // Valider l'auteur
        if (!auteur || !auteur.value.trim()) {
          showError(auteur, 'Le nom est requis.');
          hasError = true;
        } else if (auteur.value.trim().length < 2) {
          showError(auteur, 'Le nom doit contenir au moins 2 caractères.');
          hasError = true;
        } else if (auteur.value.trim().length > 255) {
          showError(auteur, 'Le nom ne peut pas dépasser 255 caractères.');
          hasError = true;
        }
        
        // Valider l'email
        if (!email || !email.value.trim()) {
          showError(email, 'L\'email est requis.');
          hasError = true;
        } else if (!isValidEmail(email.value.trim())) {
          showError(email, 'Veuillez entrer un email valide.');
          hasError = true;
        }
        
        return !hasError;
      }
      
      // Validation pour formulaires de commentaire
      function validateCommentForm(form) {
        const auteur = form.querySelector('input[name="auteur"]');
        const email = form.querySelector('input[name="email"]');
        const message = form.querySelector('textarea[name="message"]');
        let hasError = false;
        
        // Effacer les erreurs précédentes
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        
        // Valider l'auteur
        if (!auteur || !auteur.value.trim()) {
          showError(auteur, 'Le nom est requis.');
          hasError = true;
        } else if (auteur.value.trim().length < 2) {
          showError(auteur, 'Le nom doit contenir au moins 2 caractères.');
          hasError = true;
        } else if (auteur.value.trim().length > 255) {
          showError(auteur, 'Le nom ne peut pas dépasser 255 caractères.');
          hasError = true;
        }
        
        // Valider l'email
        if (!email || !email.value.trim()) {
          showError(email, 'L\'email est requis.');
          hasError = true;
        } else if (!isValidEmail(email.value.trim())) {
          showError(email, 'Veuillez entrer un email valide.');
          hasError = true;
        }
        
        // Valider le message
        if (!message || !message.value.trim()) {
          showError(message, 'Le commentaire est requis.');
          hasError = true;
        } else if (message.value.trim().length < 5) {
          showError(message, 'Le commentaire doit contenir au moins 5 caractères.');
          hasError = true;
        }
        
        return !hasError;
      }
      
      // Validation pour formulaire de contact
      function validateContactForm(form) {
        const name = form.querySelector('#name');
        const email = form.querySelector('#email');
        const subject = form.querySelector('#subject');
        const message = form.querySelector('#message');
        let hasError = false;
        
        // Effacer les erreurs précédentes
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        
        if (!name || !name.value.trim()) {
          showError(name, 'Le nom est requis.');
          hasError = true;
        }
        
        if (!email || !email.value.trim()) {
          showError(email, 'L\'email est requis.');
          hasError = true;
        } else if (!isValidEmail(email.value.trim())) {
          showError(email, 'Veuillez entrer un email valide.');
          hasError = true;
        }
        
        if (!subject || !subject.value.trim()) {
          showError(subject, 'Le sujet est requis.');
          hasError = true;
        }
        
        if (!message || !message.value.trim()) {
          showError(message, 'Le message est requis.');
          hasError = true;
        }
        
        return !hasError;
      }
      
      // Fonction utilitaire: vérifier email valide
      function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
      }
      
      // Fonction utilitaire: afficher erreur
      function showError(element, message) {
        if (!element) return;
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.cssText = 'color: #ff6b6b; font-size: 0.85em; margin-top: 3px; display: block;';
        
        element.parentNode.appendChild(errorDiv);
        element.style.borderColor = '#ff6b6b';
      }
      
      // Attacher les validations aux formulaires
      document.addEventListener('DOMContentLoaded', function() {
        // Formulaires d'articles
        const articleForms = document.querySelectorAll('form[action*="action=create"], form[action*="action=edit"]');
        articleForms.forEach(form => {
          form.addEventListener('submit', function(e) {
            if (!validateArticleForm(this)) {
              e.preventDefault();
            }
          });
        });
        
        // Formulaire de contact
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
          contactForm.addEventListener('submit', function(e) {
            if (!validateContactForm(this)) {
              e.preventDefault();
            }
          });
        }
        
        // Formulaires de like et commentaire
        const interactionForms = document.querySelectorAll('form[action*="controller=interaction"]');
        interactionForms.forEach(form => {
          form.addEventListener('submit', function(e) {
            // Vérifier le type d'interaction
            const typeInput = this.querySelector('input[name="type"]');
            if (typeInput && typeInput.value === 'like') {
              if (!validateLikeForm(this)) {
                e.preventDefault();
              }
            } else {
              if (!validateCommentForm(this)) {
                e.preventDefault();
              }
            }
          });
        });
        
        // Ajouter des styles pour les champs avec erreur
        const style = document.createElement('style');
        style.textContent = `
          input:invalid,
          textarea:invalid {
            border-color: #ff6b6b !important;
          }
          
          .error-message {
            display: block;
            margin-top: 3px;
            font-size: 0.85em;
            color: #ff6b6b;
          }
        `;
        document.head.appendChild(style);
      });
    </script>
  </body>
</html>
