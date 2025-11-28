<h1><?= htmlspecialchars($article['titre']) ?></h1>
<p><?= nl2br(htmlspecialchars($article['contenu'])) ?></p>
<p><em>Created: <?= $article['date_creation'] ?></em></p>

<!-- Messages de succès et d'erreur -->
<?php if (isset($_SESSION['success'])): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 15px;">
        <strong>Succès!</strong> <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<!-- Messages de succès et d'erreur (chargés depuis la session en variables locales) -->
<?php
$errors = [];
$form_data = [];
$success = null;
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
}
if (isset($_SESSION['form_data']) && is_array($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
}
// nettoyer la session pour ne pas réafficher
unset($_SESSION['errors'], $_SESSION['form_data'], $_SESSION['success']);
?>
<?php if ($success): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 15px;">
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

<!-- Minimal like form: posts a new like using the existing create() action -->
<form method="post" action="index.php?controller=interaction&action=create" style="margin-bottom:1em;">
    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
    <input type="hidden" name="type" value="like">
    <label>Your name:</label>
        <input type="text" name="auteur" value="<?= isset($form_data['auteur']) ? htmlspecialchars($form_data['auteur']) : '' ?>" required>
        <?php if(isset($errors['auteur'])): ?><div class="error" style="color:red; font-size:0.9em;"><?= htmlspecialchars($errors['auteur']) ?></div><?php endif; ?>
    <label>Your email:</label>
        <input type="email" name="email" value="<?= isset($form_data['email']) ? htmlspecialchars($form_data['email']) : '' ?>" required>
        <?php if(isset($errors['email'])): ?><div class="error" style="color:red; font-size:0.9em;"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
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
<form method="post" action="index.php?controller=interaction&action=create">
    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
    <input type="hidden" name="type" value="comment">
    <label>Your name:</label>
        <input type="text" name="auteur" value="<?= isset($form_data['auteur']) ? htmlspecialchars($form_data['auteur']) : '' ?>" required><br>
        <?php if(isset($errors['auteur'])): ?><div class="error" style="color:red; font-size:0.9em;"><?= htmlspecialchars($errors['auteur']) ?></div><?php endif; ?>
        <label>Your email:</label>
        <input type="email" name="email" value="<?= isset($form_data['email']) ? htmlspecialchars($form_data['email']) : '' ?>" required><br>
        <?php if(isset($errors['email'])): ?><div class="error" style="color:red; font-size:0.9em;"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
        <label>Comment:</label>
        <textarea name="message" required><?= isset($form_data['message']) ? htmlspecialchars($form_data['message']) : '' ?></textarea><br>
        <?php if(isset($errors['message'])): ?><div class="error" style="color:red; font-size:0.9em;"><?= htmlspecialchars($errors['message']) ?></div><?php endif; ?>
    <button type="submit">Add Comment</button>
</form>

<a href="index.php?controller=article&action=index">Back to Articles</a>
