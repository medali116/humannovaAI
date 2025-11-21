<?php
session_start();
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminIdeaController.php';

// Check if user is admin using AuthController
$authController = new AuthController($pdo);
$authController->requireAdmin();

// Initialize Admin Idea Controller
$adminIdeaController = new AdminIdeaController($pdo);

// Fetch all ideas and users
$ideas = $adminIdeaController->getAllIdeas();
$users = $adminIdeaController->getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innovation Management - Pro Manage AI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-black: #0a0a0a;
            --carbon-dark: #121212;
            --carbon-medium: #1a1a1a;
            --carbon-light: #2a2a2a;
            --metal-dark: #3a3a3a;
            --metal-light: #4a4a4a;
            --accent-red: #ff3333;
            --accent-blue: #00a8ff;
            --accent-green: #00ff88;
            --accent-purple: #9945ff;
            --accent-cyan: #00ffff;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --text-dim: #808080;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--primary-black);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Sidebar - Same as dashboard */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, var(--carbon-dark), var(--carbon-medium));
            border-right: 1px solid var(--metal-dark);
            padding: 20px;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px 0;
            border-bottom: 1px solid var(--metal-dark);
            margin-bottom: 30px;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .logo-icon {
            font-size: 2rem;
        }

        .logo-text .prism {
            color: var(--accent-purple);
        }

        .logo-text .flux {
            color: var(--accent-cyan);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: var(--text-primary);
            transform: translateX(5px);
        }

        .menu-icon {
            font-size: 1.3rem;
            width: 25px;
            text-align: center;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--metal-dark);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: var(--carbon-light);
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .user-details h4 {
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .user-details p {
            font-size: 0.8rem;
            color: var(--text-dim);
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            background: var(--accent-red);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #ff0000;
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            background: var(--carbon-medium);
            border-radius: 15px;
            border: 1px solid var(--metal-dark);
        }

        .page-title h1 {
            font-size: 2rem;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-title p {
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .add-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(153, 69, 255, 0.5);
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--accent-green);
            color: var(--accent-green);
        }

        .message.error {
            background: rgba(255, 51, 51, 0.1);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
        }

        /* Table */
        .content-section {
            background: var(--carbon-medium);
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--carbon-dark);
        }

        th {
            padding: 15px;
            text-align: left;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--metal-dark);
            color: var(--text-primary);
        }

        tbody tr {
            transition: background 0.3s ease;
        }

        tbody tr:hover {
            background: var(--carbon-dark);
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit {
            background: var(--accent-blue);
            color: white;
        }

        .btn-edit:hover {
            background: #0088cc;
        }

        .btn-delete {
            background: var(--accent-red);
            color: white;
        }

        .btn-delete:hover {
            background: #cc0000;
        }

        .btn-view {
            background: var(--accent-cyan);
            color: var(--carbon-dark);
        }

        .btn-view:hover {
            background: #00cccc;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--carbon-medium);
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--metal-dark);
        }

        .modal-header h2 {
            color: var(--text-primary);
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .close-btn:hover {
            color: var(--accent-red);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-group small {
            display: block;
            color: var(--text-dim);
            font-size: 0.8rem;
            margin-top: 8px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-purple);
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(153, 69, 255, 0.5);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <span class="logo-icon">⚙️</span>
                <div class="logo-text">
                    <span class="prism">Pro Manage</span>
                    <span class="flux">AI</span>
                </div>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php">
                    <span class="menu-icon">📊</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="innovationback.php" class="active">
                    <span class="menu-icon">💡</span>
                    <span>Innovation Management</span>
                </a>
            </li>
            <li>
                <a href="users.php">
                    <span class="menu-icon">👥</span>
                    <span>Users Management</span>
                </a>
            </li>
            <li>
                <a href="investments.php">
                    <span class="menu-icon">💰</span>
                    <span>Investments</span>
                </a>
            </li>
            <li>
                <a href="events.php">
                    <span class="menu-icon">📅</span>
                    <span>Events Management</span>
                </a>
            </li>
            <li>
                <a href="reclamations.php">
                    <span class="menu-icon">📝</span>
                    <span>Reclamations</span>
                </a>
            </li>
            <li>
                <a href="news.php">
                    <span class="menu-icon">📰</span>
                    <span>News Management</span>
                </a>
            </li>
            <li>
                <a href="../front_office/index.php">
                    <span class="menu-icon">🌐</span>
                    <span>View Website</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div class="user-details">
                    <h4><?php echo htmlspecialchars($_SESSION['fullname']); ?></h4>
                    <p>Administrator</p>
                </div>
            </div>
            <button class="logout-btn" onclick="location.href='logout.php'">Logout</button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>💡 Innovation Management</h1>
                <p>Manage all innovative ideas and projects</p>
            </div>
            <button class="add-btn" onclick="openAddModal()">+ Add New Idea</button>
        </div>

        <!-- Ideas Table -->
        <div class="content-section">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Author</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ideas)): ?>
                            <?php foreach ($ideas as $idea): ?>
                                <tr>
                                    <td>#<?php echo $idea['id']; ?></td>
                                    <td><?php echo htmlspecialchars($idea['titre']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($idea['description'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo htmlspecialchars($idea['fullname']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($idea['date_creation'])); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-view" onclick='viewIdea(<?php echo json_encode($idea); ?>)'>👁️ View</button>
                                            <button class="btn btn-edit" onclick='editIdea(<?php echo json_encode($idea); ?>)'>✏️ Edit</button>
                                            <button class="btn btn-delete" onclick="deleteIdeaAjax(<?php echo $idea['id']; ?>)">🗑️ Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-secondary);">No ideas found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New Idea</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form id="addIdeaForm">
                <div class="form-group">
                    <label for="utilisateur_id">Select User *</label>
                    <select name="utilisateur_id" id="utilisateur_id">
                        <option value="">Choose a user...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['fullname']); ?> (@<?php echo htmlspecialchars($user['username']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <small>Select the user who will own this idea</small>
                </div>
                <div class="form-group">
                    <label for="titre">Idea Title *</label>
                    <input type="text" name="titre" id="titre" placeholder="Enter idea title (minimum 3 words, no numbers or symbols)">
                    <small>Must be at least 3 words, no numbers or symbols allowed</small>
                </div>
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea name="description" id="description" placeholder="Enter detailed description of the idea (minimum 10 words)"></textarea>
                    <small>Must be at least 10 words</small>
                </div>
                <button type="submit" class="submit-btn">Create Idea</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Idea</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editIdeaForm">
                <input type="hidden" name="idea_id" id="edit_idea_id">
                <div class="form-group">
                    <label for="edit_titre">Idea Title *</label>
                    <input type="text" name="titre" id="edit_titre" placeholder="Enter idea title (minimum 3 words, no numbers or symbols)">
                    <small>Must be at least 3 words, no numbers or symbols allowed</small>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description *</label>
                    <textarea name="description" id="edit_description" placeholder="Enter detailed description of the idea (minimum 10 words)"></textarea>
                    <small>Must be at least 10 words</small>
                </div>
                <button type="submit" class="submit-btn">Update Idea</button>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>View Idea Details</h2>
                <button class="close-btn" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="form-group">
                <label>ID</label>
                <p id="view_id" style="color: var(--text-secondary);"></p>
            </div>
            <div class="form-group">
                <label>Title</label>
                <p id="view_titre" style="color: var(--text-secondary);"></p>
            </div>
            <div class="form-group">
                <label>Description</label>
                <p id="view_description" style="color: var(--text-secondary); line-height: 1.6;"></p>
            </div>
            <div class="form-group">
                <label>Author</label>
                <p id="view_author" style="color: var(--text-secondary);"></p>
            </div>
            <div class="form-group">
                <label>Date Created</label>
                <p id="view_date" style="color: var(--text-secondary);"></p>
            </div>
        </div>
    </div>

    <script src="assets/js/admin-idea-validation.js"></script>
</body>
</html>
