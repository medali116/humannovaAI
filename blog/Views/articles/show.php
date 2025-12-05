<?php
// DÉMARRER LA SESSION AU TRÈS DÉBUT DE LA PAGE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer les données de session
$errors = $_SESSION['errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
$success = $_SESSION['success'] ?? null;

// Nettoyer la session IMMÉDIATEMENT
unset($_SESSION['errors'], $_SESSION['form_data'], $_SESSION['success']);
?>

<style>
.error-message {
    color: #dc3545 !important;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
    min-height: 20px;
}

.input-error {
    border: 2px solid #dc3545 !important;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    margin-bottom: 15px;
}

input[type="text"],
textarea {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-group {
    margin-bottom: 15px;
}
</style>

<h1><?= htmlspecialchars($article['titre']) ?></h1>
<p><?= nl2br(htmlspecialchars($article['contenu'])) ?></p>
<p><em>Created: <?= $article['date_creation'] ?></em></p>

<!-- Message de succès -->
<?php if ($success): ?>
    <div class="success-message">
        <strong>Succès!</strong> <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<h2>Interactions</h2>
<?php
require_once "Models/Interaction.php";
$interactionModel = new Interaction();
$interactions = [];
$likeCount = 0;
try {
    $interactions = $interactionModel->readAllByArticle($article['id']);
    $likeCount = $interactionModel->countLikes($article['id']);
} catch (Exception $e) {
    echo "<p style='color:red;'>Error loading interactions: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<p>Likes: <?= $likeCount ?></p>

<!-- Formulaire Like -->
<form method="post" action="index.php?controller=interaction&action=create" style="margin-bottom:1em;" id="likeForm">
    <input type="hidden"  name="article_id" value="<?= $article['id'] ?>">
    <input type="hidden" name="type" value="like">
    
    <div class="form-group">
        <label>Your name:</label><br>
        <input type="text" name="auteur" id="like_auteur" value="<?= isset($form_data['auteur']) ? htmlspecialchars($form_data['auteur']) : '' ?>">
        <span class="error-message" id="like_auteur_error"></span>
    </div>
    
    <div class="form-group">
        <label>Your email:</label><br>
        <input type="text" name="email" id="like_email" value="<?= isset($form_data['email']) ? htmlspecialchars($form_data['email']) : '' ?>">
        <span class="error-message" id="like_email_error"></span>
    </div>
    
    <button type="submit">Like</button>
</form>

<ul>
<?php foreach ($interactions as $i): ?>
    <li>
        <?= htmlspecialchars($i['type']) ?> by <?= htmlspecialchars($i['auteur']) ?> (<?= htmlspecialchars($i['email']) ?>)
        <?php if ($i['type'] === 'comment'): ?>
            : <?= htmlspecialchars($i['message']) ?>
        <?php endif; ?>
        <a href="index.php?controller=interaction&action=delete&id=<?= $i['id'] ?>&article_id=<?= $article['id'] ?>">Delete</a>
    </li>
<?php endforeach; ?>
</ul>

<h3>Add Comment</h3>
<form method="post" action="index.php?controller=interaction&action=create" id="commentForm">
    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
    <input type="hidden" name="type" value="comment">
    
    <div class="form-group">
        <label>Your name:</label><br>
        <input type="text" name="auteur" id="comment_auteur" value="<?= isset($form_data['auteur']) ? htmlspecialchars($form_data['auteur']) : '' ?>">
        <span class="error-message" id="comment_auteur_error"></span>
    </div>
    
    <div class="form-group">
        <label>Your email:</label><br>
        <input type="text" name="email" id="comment_email" value="<?= isset($form_data['email']) ? htmlspecialchars($form_data['email']) : '' ?>">
        <span class="error-message" id="comment_email_error"></span>
    </div>
    
    <div class="form-group">
        <label>Comment:</label><br>
        <textarea name="message" id="comment_message" rows="5"><?= isset($form_data['message']) ? htmlspecialchars($form_data['message']) : '' ?></textarea>
        <span class="error-message" id="comment_message_error"></span>
    </div>
    
    <button type="submit">Add Comment</button>
</form>

<a href="index.php?controller=article&action=index">Back to Articles</a>
<script>
(function() {
  'use strict';

  // Helper: email validator
  function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
  }

  // Helpers to show/clear errors
  function clearError(input, errorEl) {
    if (input) input.classList.remove('input-error');
    if (errorEl) errorEl.textContent = '';
  }
  function showError(input, errorEl, message) {
    if (input) input.classList.add('input-error');
    if (errorEl) errorEl.textContent = message;
  }

  // Main logic inside DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function() {
    try {
      console.log('Validation script running — DOM ready');

      // ========= LIKE FORM =========
      const likeForm = document.getElementById('likeForm');
      if (likeForm) {
        const likeAuteur = document.getElementById('like_auteur');
        const likeEmail = document.getElementById('like_email');
        const likeAuteurErr = document.getElementById('like_auteur_error');
        const likeEmailErr = document.getElementById('like_email_error');

        likeForm.addEventListener('submit', function(evt) {
          evt.preventDefault();
          let isValid = true;

          const auteurVal = (likeAuteur?.value || '').trim();
          const emailVal = (likeEmail?.value || '').trim();

          clearError(likeAuteur, likeAuteurErr);
          clearError(likeEmail, likeEmailErr);

          if (!auteurVal) {
            showError(likeAuteur, likeAuteurErr, 'test erreur');
            isValid = false;
          } else if (auteurVal.length < 2) {
            showError(likeAuteur, likeAuteurErr, 'Le nom doit contenir au moins 2 caractères.');
            isValid = false;
          }

          if (!emailVal) {
            showError(likeEmail, likeEmailErr, 'erreur.');
            isValid = false;
          } else if (!validateEmail(emailVal)) {
            showError(likeEmail, likeEmailErr, 'Veuillez entrer une adresse email valide.');
            isValid = false;
          }

          if (isValid) likeForm.submit();
        });

        // live-clear on input (only if element exists)
        if (likeAuteur) likeAuteur.addEventListener('input', () => clearError(likeAuteur, likeAuteurErr));
        if (likeEmail) likeEmail.addEventListener('input', () => clearError(likeEmail, likeEmailErr));
      }

      // ========= COMMENT FORM =========
      const commentForm = document.getElementById('commentForm');
      if (commentForm) {
        const cAuteur = document.getElementById('comment_auteur');
        const cEmail = document.getElementById('comment_email');
        const cMessage = document.getElementById('comment_message');
        const cAuteurErr = document.getElementById('comment_auteur_error');
        const cEmailErr = document.getElementById('comment_email_error');
        const cMessageErr = document.getElementById('comment_message_error');

        commentForm.addEventListener('submit', function(evt) {
          evt.preventDefault();
          let isValid = true;

          const auteurVal = (cAuteur?.value || '').trim();
          const emailVal = (cEmail?.value || '').trim();
          const msgVal = (cMessage?.value || '').trim();

          clearError(cAuteur, cAuteurErr);
          clearError(cEmail, cEmailErr);
          clearError(cMessage, cMessageErr);

          if (!auteurVal) {
            showError(cAuteur, cAuteurErr, 'Veuillez renseigner ce champ.');
            isValid = false;
          } else if (auteurVal.length < 2) {
            showError(cAuteur, cAuteurErr, 'Le nom doit contenir au moins 2 caractères.');
            isValid = false;
          }

          if (!emailVal) {
            showError(cEmail, cEmailErr, 'Veuillez renseigner ce champ.');
            isValid = false;
          } else if (!validateEmail(emailVal)) {
            showError(cEmail, cEmailErr, 'Veuillez entrer une adresse email valide.');
            isValid = false;
          }

          if (!msgVal) {
            showError(cMessage, cMessageErr, 'Veuillez renseigner ce champ.');
            isValid = false;
          } else if (msgVal.length < 10) {
            showError(cMessage, cMessageErr, 'Le commentaire doit contenir au moins 10 caractères.');
            isValid = false;
          }

          if (isValid) commentForm.submit();
        });

        if (cAuteur) cAuteur.addEventListener('input', () => clearError(cAuteur, cAuteurErr));
        if (cEmail) cEmail.addEventListener('input', () => clearError(cEmail, cEmailErr));
        if (cMessage) cMessage.addEventListener('input', () => clearError(cMessage, cMessageErr));
      }

    } catch (err) {
      // show console error so you can copy/paste it
      console.error('Validation script error:', err);
    }
  });
})();
</script>