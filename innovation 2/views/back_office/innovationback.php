<?php
session_start();
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/IdeaController.php';

// Check if user is admin using AuthController
$authController = new AuthController($pdo);
$authController->requireAdmin();

// Initialize Idea Controller (unified for both user and admin)
$ideaController = new IdeaController($pdo);

// Fetch all ideas and users
$ideas = $ideaController->getAllIdeas();
$users = $ideaController->getAllUsers();
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

        /* Top Controls */
        .top-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .stats-btn, .filter-btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .stats-btn:hover, .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 255, 255, 0.4);
        }

        /* Filter Panel */
        .filter-panel {
            background: var(--carbon-medium);
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
            animation: slideDown 0.3s ease;
        }

        .filter-content {
            padding: 25px;
        }

        .filter-content h3 {
            color: var(--accent-cyan);
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            color: var(--text-primary);
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--accent-cyan);
        }

        .amount-filter, .date-filter {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .filter-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-actions button:first-child {
            background: var(--accent-red);
            color: white;
        }

        .filter-actions button:last-child {
            background: var(--metal-dark);
            color: var(--text-primary);
        }

        /* Stats Panel */
        .stats-panel {
            background: var(--carbon-medium);
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
            animation: slideDown 0.3s ease;
        }

        .stats-content {
            padding: 25px;
        }

        .stats-content h3 {
            color: var(--accent-purple);
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .chart-container {
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .chart-container h4 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .top-controls {
                flex-direction: column;
                gap: 10px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
                <a href="investmentback.php">
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
            <div class="top-controls">
                <button class="stats-btn" onclick="toggleInnovationStats()">📊 Statistics</button>
                <button class="filter-btn" onclick="toggleInnovationFilters()">🔍 Filters</button>
                <button class="add-btn" onclick="openAddModal()">+ Add New Idea</button>
            </div>
        </div>

        <!-- Filter Panel -->
        <div id="innovationFilterPanel" class="filter-panel" style="display: none;">
            <div class="filter-content">
                <h3>🔍 Advanced Filters</h3>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Author Filter:</label>
                        <select id="authorFilter" onchange="applyInnovationFilters()">
                            <option value="">All Authors</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['fullname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Date Range:</label>
                        <div class="date-filter">
                            <input type="date" id="ideaStartDate" onchange="applyInnovationFilters()">
                            <input type="date" id="ideaEndDate" onchange="applyInnovationFilters()">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Title Length:</label>
                        <div class="amount-filter">
                            <input type="number" id="minTitleLength" placeholder="Min chars" min="0" onchange="applyInnovationFilters()">
                            <input type="number" id="maxTitleLength" placeholder="Max chars" min="0" onchange="applyInnovationFilters()">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Search:</label>
                        <input type="text" id="innovationAdvancedSearch" placeholder="Search title, description, author..." oninput="applyInnovationFilters()">
                    </div>
                </div>
                <div class="filter-actions">
                    <button onclick="clearInnovationFilters()">Clear All</button>
                    <button onclick="toggleInnovationFilters()">Close Filters</button>
                </div>
            </div>
        </div>

        <!-- Statistics Panel -->
        <div id="innovationStatsPanel" class="stats-panel" style="display: none;">
            <div class="stats-content">
                <h3>📊 Innovation Statistics</h3>
                <div class="stats-grid">
                    <div class="chart-container">
                        <h4>Ideas by Author</h4>
                        <canvas id="authorChart" width="300" height="200"></canvas>
                    </div>
                    <div class="chart-container">
                        <h4>Ideas Over Time</h4>
                        <canvas id="timelineChart" width="300" height="200"></canvas>
                    </div>
                    <div class="chart-container">
                        <h4>Content Length Distribution</h4>
                        <canvas id="contentChart" width="300" height="200"></canvas>
                    </div>
                    <div class="chart-container">
                        <h4>Creation Patterns</h4>
                        <canvas id="patternChart" width="300" height="200"></canvas>
                    </div>
                </div>
            </div>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/admin-idea-validation.js"></script>
    <script>
        // Filter and Stats functionality for Innovation Management
        function toggleInnovationFilters() {
            const panel = document.getElementById('innovationFilterPanel');
            const isVisible = panel.style.display !== 'none';
            panel.style.display = isVisible ? 'none' : 'block';
        }

        function toggleInnovationStats() {
            const panel = document.getElementById('innovationStatsPanel');
            const isVisible = panel.style.display !== 'none';
            
            if (isVisible) {
                panel.style.display = 'none';
            } else {
                panel.style.display = 'block';
                generateInnovationCharts();
            }
        }

        function applyInnovationFilters() {
            const authorFilter = document.getElementById('authorFilter').value;
            const startDate = document.getElementById('ideaStartDate').value;
            const endDate = document.getElementById('ideaEndDate').value;
            const minLength = document.getElementById('minTitleLength').value;
            const maxLength = document.getElementById('maxTitleLength').value;
            const searchTerm = document.getElementById('innovationAdvancedSearch').value.toLowerCase();
            
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (row.querySelector('td[colspan]')) return; // Skip "no ideas" row
                
                const title = row.cells[1]?.textContent || '';
                const author = row.cells[3]?.textContent || '';
                const dateCell = row.cells[4]?.textContent || '';
                const searchableText = row.textContent.toLowerCase();
                
                let show = true;
                
                // Author filter by name
                if (authorFilter) {
                    const selectedAuthor = document.getElementById('authorFilter').selectedOptions[0]?.textContent || '';
                    if (author !== selectedAuthor) show = false;
                }
                
                // Title length filter
                if (minLength && title.length < parseInt(minLength)) show = false;
                if (maxLength && title.length > parseInt(maxLength)) show = false;
                
                // Date filter
                if (startDate || endDate) {
                    const rowDate = new Date(dateCell);
                    if (startDate && rowDate < new Date(startDate)) show = false;
                    if (endDate && rowDate > new Date(endDate)) show = false;
                }
                
                // Search filter
                if (searchTerm && !searchableText.includes(searchTerm)) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }

        function clearInnovationFilters() {
            document.getElementById('authorFilter').value = '';
            document.getElementById('ideaStartDate').value = '';
            document.getElementById('ideaEndDate').value = '';
            document.getElementById('minTitleLength').value = '';
            document.getElementById('maxTitleLength').value = '';
            document.getElementById('innovationAdvancedSearch').value = '';
            applyInnovationFilters();
        }

        function generateInnovationCharts() {
            const rows = Array.from(document.querySelectorAll('tbody tr')).filter(row => !row.querySelector('td[colspan]'));
            
            if (rows.length === 0) return;
            
            // Author distribution
            const authorData = {};
            const monthlyData = {};
            const lengthData = { 'short': 0, 'medium': 0, 'long': 0 };
            const dayOfWeekData = { 'Mon': 0, 'Tue': 0, 'Wed': 0, 'Thu': 0, 'Fri': 0, 'Sat': 0, 'Sun': 0 };
            
            rows.forEach(row => {
                const author = row.cells[3]?.textContent || 'Unknown';
                const title = row.cells[1]?.textContent || '';
                const description = row.cells[2]?.textContent || '';
                const dateStr = row.cells[4]?.textContent || '';
                
                // Author data
                authorData[author] = (authorData[author] || 0) + 1;
                
                // Monthly data
                if (dateStr) {
                    const date = new Date(dateStr);
                    const month = date.toLocaleDateString('en-US', {month: 'short'});
                    const dayOfWeek = date.toLocaleDateString('en-US', {weekday: 'short'});
                    monthlyData[month] = (monthlyData[month] || 0) + 1;
                    dayOfWeekData[dayOfWeek] = (dayOfWeekData[dayOfWeek] || 0) + 1;
                }
                
                // Content length
                const totalLength = title.length + description.length;
                if (totalLength < 100) lengthData.short++;
                else if (totalLength < 300) lengthData.medium++;
                else lengthData.long++;
            });
            
            // Author Distribution Chart
            const topAuthors = Object.entries(authorData).sort(([,a], [,b]) => b - a).slice(0, 6);
            new Chart(document.getElementById('authorChart'), {
                type: 'doughnut',
                data: {
                    labels: topAuthors.map(([name]) => name.split(' ')[0]),
                    datasets: [{
                        data: topAuthors.map(([,count]) => count),
                        backgroundColor: ['#9945ff', '#00ffff', '#00ff88', '#ff9500', '#ff3333', '#0080ff']
                    }]
                },
                options: { responsive: true, plugins: { legend: { labels: { color: '#ffffff' } } } }
            });
            
            // Timeline Chart
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            new Chart(document.getElementById('timelineChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Ideas Created',
                        data: months.map(month => monthlyData[month] || 0),
                        borderColor: '#00ff88',
                        backgroundColor: 'rgba(0, 255, 136, 0.1)',
                        tension: 0.4
                    }]
                },
                options: { responsive: true, scales: { y: { ticks: { color: '#ffffff' } }, x: { ticks: { color: '#ffffff' } } } }
            });
            
            // Content Length Distribution
            new Chart(document.getElementById('contentChart'), {
                type: 'bar',
                data: {
                    labels: ['Short (<100)', 'Medium (100-300)', 'Long (300+)'],
                    datasets: [{
                        label: 'Ideas',
                        data: [lengthData.short, lengthData.medium, lengthData.long],
                        backgroundColor: ['#ff9500', '#9945ff', '#00ffff']
                    }]
                },
                options: { responsive: true, scales: { y: { ticks: { color: '#ffffff' } }, x: { ticks: { color: '#ffffff' } } } }
            });
            
            // Day of Week Pattern
            new Chart(document.getElementById('patternChart'), {
                type: 'radar',
                data: {
                    labels: Object.keys(dayOfWeekData),
                    datasets: [{
                        label: 'Ideas by Day',
                        data: Object.values(dayOfWeekData),
                        borderColor: '#00ffff',
                        backgroundColor: 'rgba(0, 255, 255, 0.1)',
                        pointBackgroundColor: '#00ffff'
                    }]
                },
                options: { 
                    responsive: true, 
                    scales: { 
                        r: { 
                            ticks: { color: '#ffffff' },
                            pointLabels: { color: '#ffffff' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        } 
                    },
                    plugins: { legend: { labels: { color: '#ffffff' } } }
                }
            });
        }
    </script>
</body>
</html>
