<h1>Articles</h1>
<a href="index.php?controller=article&action=create">Create New Article</a>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Date</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($articles as $a): ?>
    <tr>
        <td><?= $a['id'] ?></td>
        <td><?= htmlspecialchars($a['titre']) ?></td>
        <td><?= $a['date_creation'] ?></td>
        <td>
            <a href="index.php?controller=article&action=show&id=<?= $a['id'] ?>">View</a> |
            <a href="index.php?controller=article&action=edit&id=<?= $a['id'] ?>">Edit</a> |
            <a href="index.php?controller=article&action=delete&id=<?= $a['id'] ?>" onclick="return confirm('Delete this article?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
