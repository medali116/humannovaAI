<?php
session_start();
// Include database connection
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/InvestmentController.php';

// Check if user is logged in and is admin
$authController = new AuthController($pdo);
$authController->requireLogin();
$authController->requireAdmin();

$investmentController = new InvestmentController($pdo);

// Fetch all investments for admin view
try {
    $stmt = $pdo->prepare("
        SELECT i.*, 
               id.titre as idea_title, 
               id.description as idea_description,
               u_investor.fullname as investor_name,
               u_investor.email as investor_email,
               u_owner.fullname as idea_owner,
               DATE_FORMAT(i.date_demande, '%Y-%m-%d %H:%i') as formatted_date
        FROM investissements i
        JOIN idee id ON i.idee_id = id.id
        JOIN utilisateurs u_investor ON i.utilisateur_id = u_investor.id
        JOIN utilisateurs u_owner ON id.utilisateur_id = u_owner.id
        ORDER BY i.date_demande DESC
    ");
    $stmt->execute();
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $investments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Management - Admin Panel</title>
    <link rel="stylesheet" href="../front_office/assets/templatemo-prism-flux.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--primary-black);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Sidebar - Same as innovationback */
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

        /* Content Section */
        .content-section {
            background: var(--carbon-medium);
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border: 1px solid var(--metal-dark);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-purple), var(--accent-cyan));
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-cyan);
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border-radius: 12px;
            border: 1px solid var(--metal-dark);
        }

        .search-box {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-input {
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            color: var(--text-primary);
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 300px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 2px rgba(153, 69, 255, 0.1);
        }

        .filter-select {
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            color: var(--text-primary);
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .admin-table-container {
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border-radius: 15px;
            border: 1px solid var(--metal-dark);
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            color: var(--text-primary);
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 15px;
            border-bottom: 1px solid var(--metal-dark);
            color: var(--text-secondary);
            font-size: 0.9rem;
            vertical-align: top;
        }

        .admin-table tr:hover {
            background: rgba(153, 69, 255, 0.05);
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-pending {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(255, 193, 7, 0.1));
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-accepted {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(40, 167, 69, 0.1));
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-refused {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(220, 53, 69, 0.1));
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-view, .btn-edit, .btn-delete {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-cyan));
            color: var(--text-primary);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: var(--text-primary);
        }

        .btn-delete {
            background: linear-gradient(135deg, #ff3333, #ff6b6b);
            color: var(--text-primary);
        }

        .btn-view:hover, .btn-edit:hover, .btn-delete:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.3);
        }

        .amount-display {
            font-weight: 700;
            color: var(--accent-purple);
            font-size: 1rem;
        }

        .investor-info {
            color: var(--text-primary);
            font-weight: 600;
        }

        .idea-title {
            color: var(--accent-cyan);
            font-weight: 600;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .no-investments {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            padding: 0;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 1px solid var(--metal-dark);
            background: linear-gradient(135deg, rgba(153, 69, 255, 0.1), rgba(0, 255, 255, 0.1));
        }

        .modal-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .close-modal {
            font-size: 2rem;
            font-weight: 300;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            line-height: 1;
        }

        .close-modal:hover {
            color: var(--accent-red);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
            box-sizing: border-box;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 3px rgba(153, 69, 255, 0.1);
        }

        .modal-footer {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding: 20px 30px;
            border-top: 1px solid var(--metal-dark);
        }

        .btn-cancel, .btn-submit {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-cancel {
            background: var(--carbon-dark);
            color: var(--text-secondary);
            border: 1px solid var(--metal-dark);
        }

        .btn-cancel:hover {
            background: var(--metal-dark);
            color: var(--text-primary);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            color: var(--text-primary);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(153, 69, 255, 0.5);
        }

        /* Top Controls */
        .top-controls {
            display: flex;
            gap: 15px;
            align-items: center;
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

        /* Responsive */
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
            
            .search-input {
                width: 200px;
            }
            
            .admin-table {
                font-size: 0.8rem;
            }
            
            .admin-table th,
            .admin-table td {
                padding: 10px 8px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 4px;
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
                <a href="innovationback.php">
                    <span class="menu-icon">💡</span>
                    <span>Innovation Management</span>
                </a>
            </li>
            <li>
                <a href="investmentback.php" class="active">
                    <span class="menu-icon">💰</span>
                    <span>Investment Management</span>
                </a>
            </li>
            <li>
                <a href="users.php">
                    <span class="menu-icon">👥</span>
                    <span>Users Management</span>
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
                <h1>💰 Investment Management</h1>
                <p>Manage all investment requests and portfolio</p>
            </div>
            <div class="top-controls">
                <button class="stats-btn" onclick="toggleStats()">📊 Statistics</button>
                <button class="filter-btn" onclick="toggleFilters()">🔍 Filters</button>
            </div>
        </div>

        <!-- Filter Panel -->
        <div id="filterPanel" class="filter-panel" style="display: none;">
            <div class="filter-content">
                <h3>🔍 Advanced Filters</h3>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Status Filter:</label>
                        <select id="statusFilterAdvanced" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="en_attente">⏳ Pending</option>
                            <option value="accepte">✅ Accepted</option>
                            <option value="refuse">❌ Refused</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Amount Range:</label>
                        <div class="amount-filter">
                            <input type="number" id="minAmount" placeholder="Min" min="0" onchange="applyFilters()">
                            <input type="number" id="maxAmount" placeholder="Max" min="0" onchange="applyFilters()">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Date Range:</label>
                        <div class="date-filter">
                            <input type="date" id="startDate" onchange="applyFilters()">
                            <input type="date" id="endDate" onchange="applyFilters()">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Search:</label>
                        <input type="text" id="advancedSearch" placeholder="Search investor, idea, owner..." oninput="applyFilters()">
                    </div>
                </div>
                <div class="filter-actions">
                    <button onclick="clearAllFilters()">Clear All</button>
                    <button onclick="toggleFilters()">Close Filters</button>
                </div>
            </div>
        </div>

        <!-- Statistics Panel -->
        <div id="statsPanel" class="stats-panel" style="display: none;">
            <div class="stats-content">
                <h3>📊 Investment Statistics</h3>
                <div class="stats-grid">
                    <div class="chart-container">
                        <h4>Status Distribution</h4>
                        <canvas id="statusChart" width="300" height="200"></canvas>
                    </div>
                    <div class="chart-container">
                        <h4>Investment Amounts</h4>
                        <canvas id="amountChart" width="300" height="200"></canvas>
                    </div>
                    <div class="chart-container">
                        <h4>Monthly Trends</h4>
                        <canvas id="trendChart" width="300" height="200"></canvas>
                    </div>
                    <div class="chart-container">
                        <h4>Top Investors</h4>
                        <canvas id="investorChart" width="300" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-section">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($investments); ?></div>
                <div class="stat-label">Total Investments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($investments, function($inv) { return $inv['statut'] === 'en_attente'; })); ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($investments, function($inv) { return $inv['statut'] === 'accepte'; })); ?></div>
                <div class="stat-label">Accepted</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($investments, function($inv) { return $inv['statut'] === 'refuse'; })); ?></div>
                <div class="stat-label">Refused</div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="actions-bar">
            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="Search investments...">
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="en_attente">Pending</option>
                    <option value="accepte">Accepted</option>
                    <option value="refuse">Refused</option>
                </select>
            </div>
        </div>

        <!-- Investments Table -->
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Investor</th>
                        <th>Idea Title</th>
                        <th>Idea Owner</th>
                        <th>Amount (DT)</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="investmentTableBody">
                    <?php if (!empty($investments)): ?>
                        <?php foreach ($investments as $investment): ?>
                            <tr data-status="<?php echo $investment['statut']; ?>">
                                <td><?php echo $investment['id']; ?></td>
                                <td class="investor-info"><?php echo htmlspecialchars($investment['investor_name']); ?></td>
                                <td class="idea-title" title="<?php echo htmlspecialchars($investment['idea_title']); ?>">
                                    <?php echo htmlspecialchars($investment['idea_title']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($investment['idea_owner']); ?></td>
                                <td class="amount-display"><?php echo number_format($investment['montant'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $investment['statut']; ?>">
                                        <?php 
                                            switch($investment['statut']) {
                                                case 'en_attente': echo '⏳ Pending'; break;
                                                case 'accepte': echo '✅ Accepted'; break;
                                                case 'refuse': echo '❌ Refused'; break;
                                                default: echo $investment['statut'];
                                            }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $investment['formatted_date']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-view" onclick='viewInvestment(<?php echo json_encode($investment); ?>)'>👁️ View</button>
                                        <button class="btn-edit" onclick='editInvestment(<?php echo json_encode($investment); ?>)'>✏️ Edit</button>
                                        <button class="btn-delete" onclick="deleteInvestment(<?php echo $investment['id']; ?>)">🗑️ Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-investments">No investments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Investment Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👁️ Investment Details</h2>
                <span class="close-modal" onclick="closeViewModal()">&times;</span>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Investment details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Edit Investment Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Investment</h2>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editInvestmentForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_investment_id" name="investment_id">
                    
                    <div class="form-group">
                        <label for="edit_montant">Investment Amount (DT)</label>
                        <input type="number" id="edit_montant" name="montant" step="0.01" min="500">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_statut">Status</label>
                        <select id="edit_statut" name="statut">
                            <option value="en_attente">⏳ Pending</option>
                            <option value="accepte">✅ Accepted</option>
                            <option value="refuse">❌ Refused</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Update Investment</button>
                </div>
            </form>
        </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../front_office/assets/js/admin-investment-validation.js"></script>
    <script>
        // Filter and Stats functionality for Investment Management
        function toggleFilters() {
            const panel = document.getElementById('filterPanel');
            const isVisible = panel.style.display !== 'none';
            panel.style.display = isVisible ? 'none' : 'block';
        }

        function toggleStats() {
            const panel = document.getElementById('statsPanel');
            const isVisible = panel.style.display !== 'none';
            
            if (isVisible) {
                panel.style.display = 'none';
            } else {
                panel.style.display = 'block';
                generateInvestmentCharts();
            }
        }

        function applyFilters() {
            const statusFilter = document.getElementById('statusFilterAdvanced').value;
            const minAmount = document.getElementById('minAmount').value;
            const maxAmount = document.getElementById('maxAmount').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const searchTerm = document.getElementById('advancedSearch').value.toLowerCase();
            
            const rows = document.querySelectorAll('#investmentTableBody tr');
            
            rows.forEach(row => {
                if (row.querySelector('.no-investments')) return;
                
                const status = row.dataset.status || '';
                const amount = parseFloat(row.cells[4]?.textContent.replace(/[^0-9.-]+/g, '') || 0);
                const dateCell = row.cells[6]?.textContent || '';
                const searchableText = row.textContent.toLowerCase();
                
                let show = true;
                
                // Status filter
                if (statusFilter && status !== statusFilter) show = false;
                
                // Amount filter
                if (minAmount && amount < parseFloat(minAmount)) show = false;
                if (maxAmount && amount > parseFloat(maxAmount)) show = false;
                
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

        function clearAllFilters() {
            document.getElementById('statusFilterAdvanced').value = '';
            document.getElementById('minAmount').value = '';
            document.getElementById('maxAmount').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('advancedSearch').value = '';
            applyFilters();
        }

        function generateInvestmentCharts() {
            const rows = Array.from(document.querySelectorAll('#investmentTableBody tr')).filter(row => !row.querySelector('.no-investments'));
            
            if (rows.length === 0) return;
            
            // Status Distribution Pie Chart
            const statusData = { 'en_attente': 0, 'accepte': 0, 'refuse': 0 };
            const amounts = [];
            const monthlyData = {};
            const investorData = {};
            
            rows.forEach(row => {
                const status = row.dataset.status || 'en_attente';
                const amount = parseFloat(row.cells[4]?.textContent.replace(/[^0-9.-]+/g, '') || 0);
                const investor = row.cells[1]?.textContent || 'Unknown';
                const dateStr = row.cells[6]?.textContent || '';
                
                statusData[status]++;
                amounts.push(amount);
                
                // Monthly data
                if (dateStr) {
                    const month = new Date(dateStr).toLocaleDateString('en-US', {month: 'short', year: 'numeric'});
                    monthlyData[month] = (monthlyData[month] || 0) + 1;
                }
                
                // Investor data
                investorData[investor] = (investorData[investor] || 0) + amount;
            });
            
            // Status Chart
            new Chart(document.getElementById('statusChart'), {
                type: 'pie',
                data: {
                    labels: ['Pending', 'Accepted', 'Refused'],
                    datasets: [{
                        data: [statusData.en_attente, statusData.accepte, statusData.refuse],
                        backgroundColor: ['#ff9500', '#00ff88', '#ff3333']
                    }]
                },
                options: { responsive: true, plugins: { legend: { labels: { color: '#ffffff' } } } }
            });
            
            // Amount Distribution Chart
            new Chart(document.getElementById('amountChart'), {
                type: 'bar',
                data: {
                    labels: ['0-1K', '1K-5K', '5K-10K', '10K+'],
                    datasets: [{
                        label: 'Investments',
                        data: [
                            amounts.filter(a => a < 1000).length,
                            amounts.filter(a => a >= 1000 && a < 5000).length,
                            amounts.filter(a => a >= 5000 && a < 10000).length,
                            amounts.filter(a => a >= 10000).length
                        ],
                        backgroundColor: '#9945ff'
                    }]
                },
                options: { responsive: true, scales: { y: { ticks: { color: '#ffffff' } }, x: { ticks: { color: '#ffffff' } } } }
            });
            
            // Monthly Trend Chart
            const monthLabels = Object.keys(monthlyData).sort();
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Investments',
                        data: monthLabels.map(month => monthlyData[month]),
                        borderColor: '#00ffff',
                        backgroundColor: 'rgba(0, 255, 255, 0.1)'
                    }]
                },
                options: { responsive: true, scales: { y: { ticks: { color: '#ffffff' } }, x: { ticks: { color: '#ffffff' } } } }
            });
            
            // Top Investors Chart
            const topInvestors = Object.entries(investorData).sort(([,a], [,b]) => b - a).slice(0, 5);
            new Chart(document.getElementById('investorChart'), {
                type: 'doughnut',
                data: {
                    labels: topInvestors.map(([name]) => name.split(' ')[0]),
                    datasets: [{
                        data: topInvestors.map(([,amount]) => amount),
                        backgroundColor: ['#9945ff', '#00ffff', '#00ff88', '#ff9500', '#ff3333']
                    }]
                },
                options: { responsive: true, plugins: { legend: { labels: { color: '#ffffff' } } } }
            });
        }
    </script>
</body>
</html>