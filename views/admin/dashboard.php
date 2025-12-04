<?php
/**
 * Dashboard Admin - Ultra Professional Version
 */

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];
$stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs");
$stats['total_users'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM evenements");
$stats['total_events'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM evenements WHERE type = 'quiz'");
$stats['total_quiz'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM participations");
$stats['total_participations'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM resultats_quiz");
$stats['total_quiz_results'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT AVG(pourcentage) as avg FROM resultats_quiz");
$avg = $stmt->fetch()['avg'];
$stats['avg_quiz_score'] = $avg ? round($avg, 1) : 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM participations WHERE statut = 'approuve'");
$stats['approved'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM participations WHERE statut = 'rejete'");
$stats['rejected'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM participations WHERE statut = 'en_attente'");
$stats['pending'] = $stmt->fetch()['total'];

$stats['conversion_rate'] = $stats['total_participations'] > 0 ? round(($stats['approved'] / $stats['total_participations']) * 100, 1) : 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM participations WHERE date_participation >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['weekly_participations'] = $stmt->fetch()['total'];

// Get events by type for chart
$stmt = $db->query("SELECT type, COUNT(*) as count FROM evenements GROUP BY type");
$eventsByType = $stmt->fetchAll();

// Get participations by status for chart
$stmt = $db->query("SELECT statut, COUNT(*) as count FROM participations GROUP BY statut");
$participationsByStatus = $stmt->fetchAll();

// Recent participations
$stmt = $db->query("SELECT u.nom, u.prenom, u.email, e.titre, p.date_participation, p.statut, p.id FROM participations p INNER JOIN utilisateurs u ON p.utilisateur_id = u.id INNER JOIN evenements e ON p.evenement_id = e.id ORDER BY p.date_participation DESC LIMIT 8");
$recentParticipations = $stmt->fetchAll();

// Recent quiz results
$stmt = $db->query("SELECT u.nom, u.prenom, e.titre, rq.score, rq.total_questions, rq.pourcentage, rq.date_passage FROM resultats_quiz rq INNER JOIN utilisateurs u ON rq.utilisateur_id = u.id INNER JOIN evenements e ON rq.evenement_id = e.id ORDER BY rq.date_passage DESC LIMIT 5");
$recentQuizResults = $stmt->fetchAll();

// Upcoming events
$stmt = $db->query("SELECT * FROM evenements WHERE date_debut > NOW() ORDER BY date_debut ASC LIMIT 5");
$upcomingEvents = $stmt->fetchAll();

// Active events count
$stmt = $db->query("SELECT COUNT(*) as total FROM evenements WHERE date_debut <= NOW() AND date_fin >= NOW()");
$stats['active_events'] = $stmt->fetch()['total'];

// Top participants
$stmt = $db->query("SELECT u.nom, u.prenom, u.email, COUNT(p.id) as total_participations FROM utilisateurs u INNER JOIN participations p ON u.id = p.utilisateur_id GROUP BY u.id ORDER BY total_participations DESC LIMIT 5");
$topParticipants = $stmt->fetchAll();

// Prepare chart data
$eventTypes = [];
$eventCounts = [];
foreach($eventsByType as $row) {
    $eventTypes[] = $row['type'] === 'quiz' ? 'Quiz' : 'Événements';
    $eventCounts[] = $row['count'];
}

$statusLabels = [];
$statusCounts = [];
$statusColors = [];
foreach($participationsByStatus as $row) {
    $statusLabels[] = ucfirst(str_replace('_', ' ', $row['statut']));
    $statusCounts[] = $row['count'];
    if ($row['statut'] === 'approuve') $statusColors[] = '#00ff88';
    elseif ($row['statut'] === 'rejete') $statusColors[] = '#ff3333';
    else $statusColors[] = '#ff9500';
}

// Get participations trend for last 7 days
$participationsTrend = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM participations WHERE DATE(date_participation) = ?");
    $stmt->execute([$date]);
    $participationsTrend[] = [
        'date' => date('d/m', strtotime($date)),
        'count' => $stmt->fetch()['count']
    ];
}

// Monthly comparison
$thisMonth = $db->query("SELECT COUNT(*) as count FROM participations WHERE MONTH(date_participation) = MONTH(NOW()) AND YEAR(date_participation) = YEAR(NOW())")->fetch()['count'];
$lastMonth = $db->query("SELECT COUNT(*) as count FROM participations WHERE MONTH(date_participation) = MONTH(NOW() - INTERVAL 1 MONTH)")->fetch()['count'];
$monthlyGrowth = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Human Nova AI</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-tertiary: #1a1a25;
            --bg-card: #15151f;
            --bg-hover: #1f1f2e;
            --accent-cyan: #00d4ff;
            --accent-purple: #a855f7;
            --accent-green: #22c55e;
            --accent-orange: #f59e0b;
            --accent-red: #ef4444;
            --accent-blue: #3b82f6;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --text-dim: #64748b;
            --border-color: rgba(255,255,255,0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: orbFloat 20s ease-in-out infinite;
        }

        .orb-1 {
            width: 500px; height: 500px;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            top: -150px; left: -100px;
        }

        .orb-2 {
            width: 400px; height: 400px;
            background: linear-gradient(135deg, var(--accent-purple), #ec4899);
            bottom: -100px; right: -100px;
            animation-delay: -5s;
        }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(50px, 50px) scale(1.1); }
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100vh;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid var(--border-color);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 900;
            color: #000;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 800;
        }

        .logo-text span:first-child { color: var(--accent-cyan); }
        .logo-text span:last-child { color: var(--accent-purple); }

        .sidebar-nav {
            flex: 1;
            padding: 25px 15px;
        }

        .nav-section { margin-bottom: 25px; }

        .nav-section-title {
            color: var(--text-dim);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 0 15px;
            margin-bottom: 15px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
            margin-bottom: 6px;
            position: relative;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%);
            width: 4px; height: 0;
            background: linear-gradient(180deg, var(--accent-cyan), var(--accent-purple));
            border-radius: 0 4px 4px 0;
            transition: height 0.3s;
        }

        .nav-link:hover { background: var(--bg-hover); color: var(--text-primary); }
        .nav-link:hover::before { height: 60%; }
        .nav-link.active { background: linear-gradient(135deg, rgba(0,212,255,0.15), rgba(168,85,247,0.1)); color: var(--text-primary); }
        .nav-link.active::before { height: 70%; }

        .nav-icon {
            width: 42px; height: 42px;
            background: var(--bg-tertiary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .nav-link:hover .nav-icon, .nav-link.active .nav-icon {
            background: linear-gradient(135deg, rgba(0,212,255,0.2), rgba(168,85,247,0.2));
        }

        .nav-label { font-size: 14px; font-weight: 600; }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            position: relative;
            z-index: 10;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 35px;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header-content h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .header-date {
            color: var(--text-dim);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-date::before {
            content: '';
            width: 8px; height: 8px;
            background: var(--accent-green);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: #000;
            box-shadow: 0 8px 25px rgba(0,212,255,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0,212,255,0.4);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.4s;
            animation: fadeUp 0.6s ease backwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card:hover {
            transform: translateY(-8px);
            border-color: rgba(0,212,255,0.3);
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .stat-icon.users { background: linear-gradient(135deg, rgba(168,85,247,0.2), rgba(168,85,247,0.1)); }
        .stat-icon.events { background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(59,130,246,0.1)); }
        .stat-icon.participations { background: linear-gradient(135deg, rgba(34,197,94,0.2), rgba(34,197,94,0.1)); }
        .stat-icon.quiz { background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(245,158,11,0.1)); }

        .stat-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-up { background: rgba(34,197,94,0.15); color: var(--accent-green); }
        .badge-down { background: rgba(239,68,68,0.15); color: var(--accent-red); }
        .badge-neutral { background: rgba(255,255,255,0.1); color: var(--text-secondary); }
        .badge-active { background: rgba(59,130,246,0.2); color: var(--accent-blue); }

        .stat-value {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--text-dim);
            font-size: 14px;
        }

        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .quick-stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .quick-stat-card:hover {
            transform: translateX(8px);
            border-color: rgba(0,212,255,0.3);
        }

        .quick-stat-icon {
            width: 64px; height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .quick-stat-icon.green { background: linear-gradient(135deg, rgba(34,197,94,0.2), rgba(34,197,94,0.1)); }
        .quick-stat-icon.blue { background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(59,130,246,0.1)); }
        .quick-stat-icon.purple { background: linear-gradient(135deg, rgba(168,85,247,0.2), rgba(168,85,247,0.1)); }

        .quick-stat-value {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .quick-stat-value.green { color: var(--accent-green); }
        .quick-stat-value.blue { color: var(--accent-blue); }
        .quick-stat-value.purple { color: var(--accent-purple); }

        .quick-stat-label { color: var(--text-dim); font-size: 14px; }

        /* Charts */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 25px;
        }

        .chart-header {
            margin-bottom: 25px;
        }

        .chart-title {
            font-size: 16px;
            font-weight: 700;
        }

        .chart-container { height: 220px; }

        /* Tables */
        .tables-row {
            display: grid;
            grid-template-columns: 1.8fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 25px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .table-title {
            font-size: 18px;
            font-weight: 700;
        }

        .view-all-btn {
            color: var(--accent-cyan);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .view-all-btn:hover { transform: translateX(5px); }

        table { width: 100%; border-collapse: collapse; }

        th {
            text-align: left;
            padding: 14px 16px;
            color: var(--text-dim);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--bg-hover); }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-avatar {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
        }

        .user-name { font-weight: 600; }
        .user-email { font-size: 12px; color: var(--text-dim); }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-approved { background: rgba(34,197,94,0.15); color: var(--accent-green); }
        .status-pending { background: rgba(245,158,11,0.15); color: var(--accent-orange); }
        .status-rejected { background: rgba(239,68,68,0.15); color: var(--accent-red); }

        /* Upcoming Events */
        .upcoming-events { display: flex; flex-direction: column; gap: 15px; }

        .event-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--bg-tertiary);
            border-radius: 14px;
            transition: all 0.3s;
        }

        .event-item:hover { background: var(--bg-hover); transform: translateX(8px); }

        .event-date-box {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #000;
            font-weight: 700;
        }

        .event-day { font-size: 22px; line-height: 1; }
        .event-month { font-size: 11px; text-transform: uppercase; }
        .event-info { flex: 1; }
        .event-name { font-weight: 600; margin-bottom: 4px; }
        .event-type { font-size: 12px; color: var(--text-dim); }

        .empty-state { text-align: center; padding: 40px 20px; }
        .empty-icon { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        .empty-state p { color: var(--text-dim); }

        /* Bottom Row */
        .bottom-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px;
            background: var(--bg-tertiary);
            border-radius: 14px;
            margin-bottom: 12px;
            transition: all 0.3s;
        }

        .leaderboard-item:hover { background: var(--bg-hover); transform: scale(1.02); }

        .rank {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
        }

        .rank-1 { background: linear-gradient(135deg, #ffd700, #ffb800); color: #000; }
        .rank-2 { background: linear-gradient(135deg, #c0c0c0, #a0a0a0); color: #000; }
        .rank-3 { background: linear-gradient(135deg, #cd7f32, #a0522d); color: #fff; }
        .rank-other { background: var(--bg-hover); color: var(--text-dim); }

        .leaderboard-info { flex: 1; }
        .leaderboard-name { font-weight: 600; font-size: 14px; }
        .leaderboard-email { font-size: 12px; color: var(--text-dim); }
        .leaderboard-count { font-weight: 700; color: var(--accent-cyan); font-size: 18px; }

        /* Responsive */
        @media (max-width: 1400px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-row { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 1200px) {
            .tables-row, .bottom-row { grid-template-columns: 1fr; }
            .quick-stats, .charts-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr; }
            .dashboard-header { flex-direction: column; gap: 20px; }
        }
    </style>
</head>
<body>
    <div class="animated-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="../../index.php" class="logo">
                <div class="logo-icon">H</div>
                <div class="logo-text"><span>HUMAN</span> <span>NOVA AI</span></div>
            </a>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Menu</div>
                <a href="dashboard.php" class="nav-link active">
                    <div class="nav-icon">📊</div>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="manage-events.php" class="nav-link">
                    <div class="nav-icon">📅</div>
                    <span class="nav-label">Événements</span>
                </a>
                <a href="manage-events.php?type=quiz" class="nav-link">
                    <div class="nav-icon">🎯</div>
                    <span class="nav-label">Quiz</span>
                </a>
                <a href="manage-participations.php" class="nav-link">
                    <div class="nav-icon">👥</div>
                    <span class="nav-label">Participations</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Outils</div>
                <a href="../front/events.php" class="nav-link">
                    <div class="nav-icon">🌐</div>
                    <span class="nav-label">Front Office</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <div class="dashboard-header">
            <div class="header-content">
                <h1>Dashboard</h1>
                <div class="header-date"><?php echo date('l, d F Y'); ?></div>
            </div>
            <div class="header-actions">
                <a href="manage-events.php" class="btn btn-primary">
                    <span>➕</span> Nouvel Événement
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon users">👥</div>
                    <span class="stat-badge badge-neutral">Total</span>
                </div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Utilisateurs inscrits</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon events">📅</div>
                    <span class="stat-badge badge-active">Actif</span>
                </div>
                <div class="stat-value"><?php echo $stats['total_events']; ?></div>
                <div class="stat-label">Événements créés</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon participations">🚀</div>
                    <span class="stat-badge <?php echo $monthlyGrowth >= 0 ? 'badge-up' : 'badge-down'; ?>">
                        +<?php echo $stats['weekly_participations']; ?> cette semaine
                    </span>
                </div>
                <div class="stat-value"><?php echo $stats['total_participations']; ?></div>
                <div class="stat-label">Participations totales</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon quiz">🎯</div>
                    <span class="stat-badge badge-neutral"><?php echo $stats['total_quiz_results']; ?> complétés</span>
                </div>
                <div class="stat-value"><?php echo $stats['total_quiz']; ?></div>
                <div class="stat-label">Quiz disponibles</div>
            </div>
        </div>

        <div class="quick-stats">
            <div class="quick-stat-card">
                <div class="quick-stat-icon green">✅</div>
                <div>
                    <div class="quick-stat-value green"><?php echo $stats['conversion_rate']; ?>%</div>
                    <div class="quick-stat-label">Taux d'approbation</div>
                </div>
            </div>

            <div class="quick-stat-card">
                <div class="quick-stat-icon blue">📈</div>
                <div>
                    <div class="quick-stat-value blue"><?php echo $stats['avg_quiz_score']; ?>%</div>
                    <div class="quick-stat-label">Score moyen Quiz</div>
                </div>
            </div>

            <div class="quick-stat-card">
                <div class="quick-stat-icon purple">⏳</div>
                <div>
                    <div class="quick-stat-value purple"><?php echo $stats['pending']; ?></div>
                    <div class="quick-stat-label">En attente de validation</div>
                </div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📊 Types d'événements</h3>
                </div>
                <div class="chart-container">
                    <canvas id="eventsChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📈 Statuts des participations</h3>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📉 Tendance (7 jours)</h3>
                </div>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <div class="tables-row">
            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">👥 Participations récentes</h3>
                    <a href="manage-participations.php" class="view-all-btn">Voir tout →</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Événement</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentParticipations)): ?>
                            <tr><td colspan="4"><div class="empty-state"><div class="empty-icon">📭</div><p>Aucune participation</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach($recentParticipations as $p): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar"><?php echo strtoupper(substr($p['prenom'], 0, 1) . substr($p['nom'], 0, 1)); ?></div>
                                        <div>
                                            <div class="user-name"><?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($p['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars(mb_substr($p['titre'], 0, 20)); ?>...</td>
                                <td>
                                    <?php
                                    $statusClass = 'status-pending';
                                    $statusIcon = '⏳';
                                    if ($p['statut'] === 'approuve') { $statusClass = 'status-approved'; $statusIcon = '✓'; }
                                    elseif ($p['statut'] === 'rejete') { $statusClass = 'status-rejected'; $statusIcon = '✗'; }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusIcon; ?> <?php echo ucfirst(str_replace('_', ' ', $p['statut'])); ?></span>
                                </td>
                                <td style="color: var(--text-dim);"><?php echo date('d/m H:i', strtotime($p['date_participation'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">📅 Événements à venir</h3>
                </div>
                <div class="upcoming-events">
                    <?php if (empty($upcomingEvents)): ?>
                        <div class="empty-state"><div class="empty-icon">📭</div><p>Aucun événement à venir</p></div>
                    <?php else: ?>
                        <?php foreach($upcomingEvents as $event): ?>
                        <div class="event-item">
                            <div class="event-date-box">
                                <span class="event-day"><?php echo date('d', strtotime($event['date_debut'])); ?></span>
                                <span class="event-month"><?php echo date('M', strtotime($event['date_debut'])); ?></span>
                            </div>
                            <div class="event-info">
                                <div class="event-name"><?php echo htmlspecialchars($event['titre']); ?></div>
                                <div class="event-type"><?php echo $event['type'] === 'quiz' ? '🎯 Quiz' : '📅 Événement'; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bottom-row">
            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">🏆 Top Participants</h3>
                </div>
                <?php if (empty($topParticipants)): ?>
                    <div class="empty-state"><div class="empty-icon">🏆</div><p>Aucun participant</p></div>
                <?php else: ?>
                    <?php foreach($topParticipants as $index => $participant): ?>
                    <div class="leaderboard-item">
                        <div class="rank <?php echo $index < 3 ? 'rank-' . ($index + 1) : 'rank-other'; ?>">
                            <?php echo $index + 1; ?>
                        </div>
                        <div class="leaderboard-info">
                            <div class="leaderboard-name"><?php echo htmlspecialchars($participant['prenom'] . ' ' . $participant['nom']); ?></div>
                            <div class="leaderboard-email"><?php echo htmlspecialchars($participant['email']); ?></div>
                        </div>
                        <div class="leaderboard-count"><?php echo $participant['total_participations']; ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">🎯 Derniers Quiz</h3>
                </div>
                <?php if (empty($recentQuizResults)): ?>
                    <div class="empty-state"><div class="empty-icon">🎯</div><p>Aucun quiz complété</p></div>
                <?php else: ?>
                    <?php foreach($recentQuizResults as $result): ?>
                    <div class="leaderboard-item">
                        <div class="rank" style="background: linear-gradient(135deg, <?php echo $result['pourcentage'] >= 70 ? 'var(--accent-green)' : ($result['pourcentage'] >= 50 ? 'var(--accent-orange)' : 'var(--accent-red)'); ?>, <?php echo $result['pourcentage'] >= 70 ? '#00a855' : ($result['pourcentage'] >= 50 ? '#c77a00' : '#c00'); ?>); color: <?php echo $result['pourcentage'] >= 50 ? '#000' : '#fff'; ?>;">
                            <?php echo $result['pourcentage']; ?>%
                        </div>
                        <div class="leaderboard-info">
                            <div class="leaderboard-name"><?php echo htmlspecialchars($result['prenom'] . ' ' . $result['nom']); ?></div>
                            <div class="leaderboard-email"><?php echo htmlspecialchars(mb_substr($result['titre'], 0, 25)); ?>...</div>
                        </div>
                        <div class="leaderboard-count" style="font-size: 14px; color: var(--text-dim);">
                            <?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Events Doughnut Chart
        const eventsCtx = document.getElementById('eventsChart').getContext('2d');
        new Chart(eventsCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($eventTypes); ?>,
                datasets: [{
                    data: <?php echo json_encode($eventCounts); ?>,
                    backgroundColor: ['#a855f7', '#3b82f6'],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { color: '#94a3b8', padding: 20, font: { size: 13 }, usePointStyle: true } 
                    }
                }
            }
        });

        // Status Bar Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($statusLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($statusCounts); ?>,
                    backgroundColor: <?php echo json_encode($statusColors); ?>,
                    borderRadius: 10,
                    barThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' }, beginAtZero: true }
                }
            }
        });

        // Trend Line Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const gradient = trendCtx.createLinearGradient(0, 0, 0, 220);
        gradient.addColorStop(0, 'rgba(0, 212, 255, 0.3)');
        gradient.addColorStop(1, 'rgba(0, 212, 255, 0)');

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($participationsTrend, 'date')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($participationsTrend, 'count')); ?>,
                    borderColor: '#00d4ff',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointBackgroundColor: '#00d4ff',
                    pointBorderColor: '#0a0a0f',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' }, beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
