<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de sécurité (au cas où quelqu'un accède directement à la vue)
if (!isset($_SESSION['admin'])) {
    header('Location: index.php?controller=admin&action=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PRO MANAGE AI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            min-height: 100vh;
            padding: 20px;
        }

        /* Header */
        .header {
            max-width: 1200px;
            margin: 0 auto 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .welcome {
            font-size: 1.8rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logout-btn {
            padding: 10px 25px;
            background: rgba(220, 53, 69, 0.2);
            color: #ff4757;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid #ff4757;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: #ff4757;
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 40px;
            text-align: center;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 40px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .dashboard-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #ffffff;
        }

        .dashboard-card p {
            color: #a0a0a0;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .card-link {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .card-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
        }

        /* Back to site button */
        .back-to-site {
            display: block;
            text-align: center;
            margin-top: 50px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .back-to-site a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .back-to-site a:hover {
            color: #764ba2;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .welcome {
                font-size: 1.4rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="welcome">Welcome, <?= htmlspecialchars($_SESSION['admin']) ?></div>
        <a href="index.php?controller=admin&action=logout" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <h1>Admin Dashboard</h1>

        <div class="dashboard-grid">
            <!-- Card Articles -->
            <div class="dashboard-card">
                <div class="card-icon">📝</div>
                <h3>Manage Articles</h3>
                <p>Create, edit, and delete blog articles. Manage your content effectively.</p>
                <a href="index.php?controller=article&action=index" class="card-link">Go to Articles</a>
            </div>

            <!-- Card Interactions -->
            <div class="dashboard-card">
                <div class="card-icon">💬</div>
                <h3>Manage Interactions</h3>
                <p>View and moderate comments, likes, and user interactions on your articles.</p>
                <a href="index.php?controller=interaction&action=index" class="card-link">Go to Interactions</a>
            </div>
        </div>

        <div class="back-to-site">
            <a href="index.php">← Back to Main Site</a>
        </div>
    </div>
</body>
</html>