<h1>Edit Article</h1>
<form method="post">
    <label>Title:</label><br>
    <input type="text" name="titre" value="<?= htmlspecialchars($article['titre']) ?>" required><br><br>
    <label>Content:</label><br>
    <textarea name="contenu" rows="5" cols="50" required><?= htmlspecialchars($article['contenu']) ?></textarea><br><br>
    <button type="submit">Update</button>
</form>
<a href="index.php?controller=article&action=index">Back to Articles</a>
