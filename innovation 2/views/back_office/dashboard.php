<?php
session_start();
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';

// Check if user is admin using AuthController
$authController = new AuthController($pdo);
$authController->requireAdmin();

// Get statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
    $totalUsers = $stmt->fetch()['total'];
    
    // Total ideas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM idee");
    $totalIdeas = $stmt->fetch()['total'];
    
    // Total investments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM investissements");
    $totalInvestments = $stmt->fetch()['total'];
    
    // Pending investments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM investissements WHERE statut = 'en_attente'");
    $pendingInvestments = $stmt->fetch()['total'];
    
    // Recent ideas
    $stmt = $pdo->query("SELECT idee.*, utilisateurs.fullname 
                         FROM idee 
                         JOIN utilisateurs ON idee.utilisateur_id = utilisateurs.id 
                         ORDER BY idee.date_creation DESC LIMIT 5");
    $recentIdeas = $stmt->fetchAll();
    
    // Recent investments
    $stmt = $pdo->query("SELECT investissements.*, idee.titre, utilisateurs.fullname 
                         FROM investissements 
                         JOIN idee ON investissements.idee_id = idee.id 
                         JOIN utilisateurs ON investissements.utilisateur_id = utilisateurs.id 
                         ORDER BY investissements.date_demande DESC LIMIT 5");
    $recentInvestments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pro Manage AI</title>
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

        /* Sidebar */
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

        .top-actions {
            display: flex;
            gap: 15px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-purple), var(--accent-cyan));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(153, 69, 255, 0.3);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* Tables */
        .content-section {
            background: var(--carbon-medium);
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--metal-dark);
        }

        .section-header h2 {
            font-size: 1.5rem;
            color: var(--text-primary);
        }

        .view-all-btn {
            color: var(--accent-cyan);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .view-all-btn:hover {
            color: var(--accent-purple);
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

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(255, 168, 0, 0.2);
            color: #ffaa00;
        }

        .status-accepted {
            background: rgba(0, 255, 136, 0.2);
            color: var(--accent-green);
        }

        .status-refused {
            background: rgba(255, 51, 51, 0.2);
            color: var(--accent-red);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
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
                <a href="dashboard.php" class="active">
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
                <h1>📊 Dashboard Overview</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</p>
            </div>
            <div class="top-actions">
                <span style="color: var(--text-secondary);">Last login: Today at <?php echo date('H:i'); ?></span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $totalUsers ?? 0; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-icon">👥</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $totalIdeas ?? 0; ?></div>
                        <div class="stat-label">Total Ideas</div>
                    </div>
                    <div class="stat-icon">💡</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $totalInvestments ?? 0; ?></div>
                        <div class="stat-label">Total Investments</div>
                    </div>
                    <div class="stat-icon">💰</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $pendingInvestments ?? 0; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                    <div class="stat-icon">⏳</div>
                </div>
            </div>
        </div>

        <!-- Recent Ideas -->
        <div class="content-section">
            <div class="section-header">
                <h2>Recent Ideas</h2>
                <a href="innovationback.php" class="view-all-btn">View All →</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentIdeas)): ?>
                            <?php foreach ($recentIdeas as $idea): ?>
                                <tr>
                                    <td>#<?php echo $idea['id']; ?></td>
                                    <td><?php echo htmlspecialchars($idea['titre']); ?></td>
                                    <td><?php echo htmlspecialchars($idea['fullname']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($idea['date_creation'])); ?></td>
                                    <td>
                                        <a href="innovationback.php?view=<?php echo $idea['id']; ?>" style="color: var(--accent-cyan); text-decoration: none;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-secondary);">No ideas yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Investments -->
        <div class="content-section">
            <div class="section-header">
                <h2>Recent Investment Requests</h2>
                <a href="investments.php" class="view-all-btn">View All →</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Idea</th>
                            <th>Investor</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentInvestments)): ?>
                            <?php foreach ($recentInvestments as $inv): ?>
                                <tr>
                                    <td>#<?php echo $inv['id']; ?></td>
                                    <td><?php echo htmlspecialchars($inv['titre']); ?></td>
                                    <td><?php echo htmlspecialchars($inv['fullname']); ?></td>
                                    <td><?php echo $inv['montant'] ? '$' . number_format($inv['montant'], 2) : 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $inv['statut'] === 'en_attente' ? 'pending' : ($inv['statut'] === 'accepte' ? 'accepted' : 'refused'); ?>">
                                            <?php echo ucfirst($inv['statut']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($inv['date_demande'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-secondary);">No investment requests yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
