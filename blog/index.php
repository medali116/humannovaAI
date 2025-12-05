<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Controller: " . ($_GET['controller'] ?? 'none') . "<br>";
echo "Action: " . ($_GET['action'] ?? 'none') . "<br>";
// Démarrer la session en TOUT PREMIER
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le chemin de base du blog
$blogPath = $_SERVER['DOCUMENT_ROOT'] . "/blog";

// ROUTER principal - Gérer les contrôleurs AVANT toute sortie HTML
$controller = $_GET['controller'] ?? null;
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;

// ===== ROUTING ADMIN =====
if ($controller === 'admin') {
    require_once $blogPath . "/Controllers/AdminController.php";
    $ctrl = new AdminController();
    
    if ($action === 'login') {
        $ctrl->login();
        exit;
    } elseif ($action === 'index') {
        $ctrl->index();
        exit;
    } elseif ($action === 'logout') {
        $ctrl->logout();
        exit;
    }
}

// ===== ROUTING ARTICLES (pour l'admin) =====
if ($controller === 'article' && in_array($action, ['index', 'create', 'edit', 'delete'])) {
    require_once $blogPath . "/Controllers/ArticleController.php";
    $ctrl = new ArticleController();
    
    if ($action === 'index') {
        $ctrl->index();
        exit;
    } elseif ($action === 'create') {
        $ctrl->create();
        exit;
    } elseif ($action === 'edit' && $id) {
        $ctrl->edit($id);
        exit;
    } elseif ($action === 'delete' && $id) {
        $ctrl->delete($id);
        exit;
    }
}

// ===== GESTION DES INTERACTIONS =====
if (isset($_POST['article_id']) && isset($_POST['type'])) {
    require_once $blogPath . "/Models/Interaction.php";
    
    $article_id = (int)$_POST['article_id'];
    $type = trim($_POST['type']);
    $auteur = trim($_POST['auteur']);
    $email = trim($_POST['email']);
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

// ===== SUPPRESSION D'INTERACTIONS =====
if (isset($_GET['deleteId']) && $id) {
    require_once $blogPath . "/Models/Interaction.php";
    
    $interactionModel = new Interaction();
    $interactionModel->delete($_GET['deleteId']);
    
    header("Location: index.php?action=show&id=" . $id);
    exit;
}

// ===== CHARGER LES MODÈLES POUR L'AFFICHAGE PUBLIC =====
require_once $blogPath . "/Models/Article.php";
require_once $blogPath . "/Models/Interaction.php";

// Déterminer quelle page afficher
$showArticleDetail = ($action === 'show' && $id);
$isBlog = $showArticleDetail;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PRISM FLUX - Digital Innovation Studio</title>
    <link rel="stylesheet" href="templatemo-prism-flux.css" />
</head>
<body>
<!DOCTYPE html>
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
            <span class="prism">PRO MANAGE</span>
            <span class="flux">AI</span>
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

    <!-- Blog Section -->
    <section class="blog-section" id="blog">
      <div class="section-header">
        <h2 class="section-title">Blog</h2>
        <p class="section-subtitle">Donnez votre avis sur nos articles</p>
      </div>
      <div class="blog-container" style="max-width: 1200px; margin: 40px auto;">
        <?php
          $articleModel = new Article();
          $articles = $articleModel->readAll();

          if (!empty($articles)) {
            echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">';
            foreach ($articles as $article) {
                echo '<div style="background: rgba(0, 255, 255, 0.05); border: 1px solid var(--accent-cyan); border-radius: 8px; padding: 20px; color: #fff; transition: all 0.3s ease;">';
                echo '<h3 style="color: var(--accent-cyan); margin-top: 0;">' . htmlspecialchars($article['titre']) . '</h3>';
                echo '<p style="color: #aaa; font-size: 0.95em; margin: 10px 0;">Découvrez cet article et partagez votre avis en laissant un commentaire.</p>';
                echo '<a href="index.php?action=show&id=' . $article['id'] . '" style="color: var(--accent-cyan); text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px;">Lire la suite →</a>';
                echo '</div>';
            }
            echo '</div>';
          } else {
            echo '<p style="text-align: center; color: #aaa;">Aucun article disponible pour le moment.</p>';
          }
        ?>
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

    <!-- Article Detail Page -->
    <?php if ($showArticleDetail): ?>
    <section class="blog-section" id="blog" style="padding: 100px 20px; min-height: calc(100vh - 200px);">
      <div class="blog-container" style="max-width: 900px; margin: 0 auto; color: #fff;">
        <?php
          $articleId = (int)$id;
          $articleModel = new Article();
          $article = $articleModel->readById($articleId);
          
          if (!$article) {
            echo '<p style="text-align: center; color: #aaa;">Article non trouvé.</p>';
          } else {
            $success = $_SESSION['success'] ?? null;
            unset($_SESSION['success']);
            
            echo '<div style="margin-bottom: 40px;">';
            echo '<h1 style="color: var(--accent-cyan); margin-bottom: 10px; font-size: 2.5rem;">' . htmlspecialchars($article['titre']) . '</h1>';
            echo '<p style="color: #aaa; font-size: 0.9em; margin-bottom: 30px;"><em>Publié le: ' . $article['date_creation'] . '</em></p>';
            echo '<div style="color: #ddd; line-height: 1.8; font-size: 1.1rem; margin-bottom: 50px;">' . nl2br(htmlspecialchars($article['contenu'])) . '</div>';
            
            if ($success) {
              echo '<div style="background-color: rgba(76, 175, 80, 0.1); color: #4caf50; padding: 15px; border: 1px solid #4caf50; border-radius: 8px; margin-bottom: 30px;"><strong>Succès!</strong> ' . htmlspecialchars($success) . '</div>';
            }
            
            echo '<div style="border-top: 2px solid rgba(0, 255, 255, 0.2); padding-top: 40px;">';
            echo '<h2 style="color: var(--accent-cyan); margin-bottom: 20px;">Interactions</h2>';
            
            $interactionModel = new Interaction();
            $interactions = [];
            $likeCount = 0;
            try {
              $interactions = $interactionModel->readAllByArticle($article['id']);
              $likeCount = $interactionModel->countLikes($article['id']);
            } catch (Exception $e) {
              echo "<p style='color:#aaa;'>Erreur lors du chargement des interactions</p>";
            }
            
            echo '<p style="color: #ddd; margin: 20px 0; font-size: 1.1rem;"><strong>👍 ' . $likeCount . ' J\'aime</strong></p>';
            
            // Formulaire Like
            echo '<form method="post" style="margin-bottom:30px; background: rgba(0, 255, 255, 0.05); padding: 20px; border-radius: 8px; border: 1px solid var(--accent-cyan);">';
            echo '<h3 style="color: var(--accent-cyan); margin-top: 0; margin-bottom: 15px;">Aimer cet article</h3>';
            echo '<input type="hidden" name="article_id" value="' . $article['id'] . '">';
            echo '<input type="hidden" name="type" value="like">';
            echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">';
            echo '<div><label style="color: #ddd; display: block; margin-bottom: 5px;">Votre nom:</label>';
            echo '<input type="text" name="auteur" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box;">';
            echo '</div>';
            echo '<div><label style="color: #ddd; display: block; margin-bottom: 5px;">Votre email:</label>';
            echo '<input type="email" name="email" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box;">';
            echo '</div>';
            echo '</div>';
            echo '<button type="submit" style="padding: 10px 25px; background: var(--accent-cyan); color: #000; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1rem;">👍 J\'aime</button>';
            echo '</form>';
            
            // Liste des interactions
            if (!empty($interactions)) {
              echo '<div style="background: rgba(0, 255, 255, 0.05); padding: 20px; border-radius: 8px; border: 1px solid rgba(0, 255, 255, 0.2); margin-bottom: 30px;">';
              echo '<h3 style="color: var(--accent-cyan); margin-top: 0;">Activités récentes</h3>';
              echo '<ul style="list-style: none; padding: 0; margin: 0;">';
              foreach ($interactions as $i) {
                echo '<li style="padding: 12px 0; border-bottom: 1px solid rgba(0, 255, 255, 0.1); color: #ddd;">';
                if ($i['type'] === 'like') {
                  echo '👍 <strong>' . htmlspecialchars($i['auteur']) . '</strong> a aimé cet article';
                } else {
                  echo '💬 <strong>' . htmlspecialchars($i['auteur']) . '</strong>: ' . htmlspecialchars($i['message']);
                }
                echo ' <a href="index.php?action=show&id=' . $article['id'] . '&deleteId=' . $i['id'] . '" style="color: #ff6b6b; text-decoration: none; margin-left: 10px; font-size: 0.9em;" onclick="return confirm(\'Supprimer cette interaction ?\')">Supprimer</a>';
                echo '</li>';
              }
              echo '</ul>';
              echo '</div>';
            }
            
            // Formulaire de commentaire
            echo '<div style="background: rgba(0, 255, 255, 0.05); padding: 25px; border-radius: 8px; border: 1px solid var(--accent-cyan);">';
            echo '<h3 style="color: var(--accent-cyan); margin-top: 0;">Ajouter un commentaire</h3>';
            echo '<form method="post">';
            echo '<input type="hidden" name="article_id" value="' . $article['id'] . '">';
            echo '<input type="hidden" name="type" value="comment">';
            echo '<div style="margin-bottom: 15px;"><label style="color: #ddd; display: block; margin-bottom: 5px;">Votre nom:</label>';
            echo '<input type="text" name="auteur" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box;">';
            echo '</div>';
            echo '<div style="margin-bottom: 15px;"><label style="color: #ddd; display: block; margin-bottom: 5px;">Votre email:</label>';
            echo '<input type="email" name="email" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box;">';
            echo '</div>';
            echo '<div style="margin-bottom: 15px;"><label style="color: #ddd; display: block; margin-bottom: 5px;">Commentaire:</label>';
            echo '<textarea name="message" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #666; background: #1a1f3a; color: #fff; box-sizing: border-box; font-family: inherit; min-height: 120px; resize: vertical;"></textarea>';
            echo '</div>';
            echo '<button type="submit" style="padding: 10px 25px; background: var(--accent-cyan); color: #000; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1rem;">💬 Publier le commentaire</button>';
            echo '</form>';
            echo '</div>';
            
            echo '</div>'; // Fin interactions
            
            echo '<div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid rgba(0, 255, 255, 0.2);"><a href="index.php#blog" style="color: var(--accent-cyan); text-decoration: none; font-weight: bold; font-size: 1.1rem;">← Retour au blog</a></div>';
            echo '</div>';
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
            <a href="index.php?controller=admin&action=login">Admin Login</a>
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
      function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
      }
      
      function showError(element, message) {
        if (!element) return;
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.cssText = 'color: #ff6b6b; font-size: 0.85em; margin-top: 5px; display: block;';
        
        element.parentNode.appendChild(errorDiv);
        element.style.borderColor = '#ff6b6b';
      }
      
      function validateInteractionForm(form) {
        const auteur = form.querySelector('input[name="auteur"]');
        const email = form.querySelector('input[name="email"]');
        const message = form.querySelector('textarea[name="message"]');
        const type = form.querySelector('input[name="type"]');
        let hasError = false;
        
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        form.querySelectorAll('input, textarea').forEach(el => el.style.borderColor = '#666');
        
        if (!auteur || !auteur.value.trim()) {
          showError(auteur, 'Le nom est requis.');
          hasError = true;
        } else if (auteur.value.trim().length < 2) {
          showError(auteur, 'Le nom doit contenir au moins 2 caractères.');
          hasError = true;
        }
        
        if (!email || !email.value.trim()) {
          showError(email, 'L\'email est requis.');
          hasError = true;
        } else if (!isValidEmail(email.value.trim())) {
          showError(email, 'Veuillez entrer un email valide.');
          hasError = true;
        }
        
        if (type && type.value === 'comment') {
          if (!message || !message.value.trim()) {
            showError(message, 'Le commentaire est requis.');
            hasError = true;
          } else if (message.value.trim().length < 5) {
            showError(message, 'Le commentaire doit contenir au moins 5 caractères.');
            hasError = true;
          }
        }
        
        return !hasError;
      }
      
      function validateContactForm(form) {
        const name = form.querySelector('#name');
        const email = form.querySelector('#email');
        const subject = form.querySelector('#subject');
        const message = form.querySelector('#message');
        let hasError = false;
        
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
      
      document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
          contactForm.addEventListener('submit', function(e) {
            if (!validateContactForm(this)) {
              e.preventDefault();
            }
          });
        }
        
        const interactionForms = document.querySelectorAll('form[method="post"]');
        interactionForms.forEach(form => {
          if (form.querySelector('input[name="article_id"]')) {
            form.addEventListener('submit', function(e) {
              if (!validateInteractionForm(this)) {
                e.preventDefault();
              }
            });
          }
        });
      });
    </script>
  </body>
</html>