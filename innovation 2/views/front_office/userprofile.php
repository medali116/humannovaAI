<?php
session_start();
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';

// Check if user is logged in using AuthController
$authController = new AuthController($pdo);
$authController->requireLogin();

// Redirect admin to dashboard
if ($authController->isAdmin()) {
    header('Location: ../back_office/dashboard.php');
    exit();
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Fetch user's ideas
    $stmt = $pdo->prepare("SELECT * FROM idee WHERE utilisateur_id = :id ORDER BY date_creation DESC");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $userIdeas = $stmt->fetchAll();
    
    // Fetch user's investments
    $stmt = $pdo->prepare("SELECT investissements.*, idee.titre 
                           FROM investissements 
                           JOIN idee ON investissements.idee_id = idee.id 
                           WHERE investissements.utilisateur_id = :id 
                           ORDER BY investissements.date_demande DESC");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $userInvestments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Pro Manage AI</title>
    <link rel="stylesheet" href="assets/templatemo-prism-flux.css">
    <style>
        /* Additional styles for profile page */
        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 120px 20px 60px;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border: 1px solid var(--metal-dark);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--accent-purple), var(--accent-cyan));
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            flex-shrink: 0;
        }

        .profile-details h1 {
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .profile-details p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .profile-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-cyan);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .section-card {
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            padding: 30px;
        }

        .section-card h2 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--metal-dark);
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

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: 'Orbitron', 'Rajdhani', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-purple);
            box-shadow: 0 0 20px rgba(153, 69, 255, 0.3);
        }

        .update-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: var(--text-primary);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(153, 69, 255, 0.5);
        }

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

        .full-width {
            grid-column: 1 / -1;
        }

        .ideas-list, .investments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .item-card {
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .item-card:hover {
            border-color: var(--accent-purple);
            transform: translateX(5px);
        }

        .item-card h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .item-card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .item-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--metal-dark);
        }

        .date-badge {
            color: var(--text-dim);
            font-size: 0.85rem;
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

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        .info-display {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-item {
            padding-bottom: 15px;
            border-bottom: 1px solid var(--metal-dark);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item label {
            display: block;
            color: var(--text-dim);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .info-item p {
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            border-color: var(--accent-cyan);
            transform: translateX(5px);
        }

        .action-btn.logout-action:hover {
            border-color: var(--accent-red);
        }

        .action-icon {
            font-size: 2rem;
        }

        .action-btn h4 {
            color: var(--text-primary);
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .action-btn p {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Profile Dropdown */
        .profile-dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 10px;
            padding: 10px 0;
            min-width: 180px;
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--accent-red), #cc0000);
            color: white;
        }

        .dropdown-item span {
            font-size: 1.2rem;
        }

        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .profile-info {
                flex-direction: column;
                text-align: center;
            }

            .profile-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header class="header" id="header">
        <nav class="nav-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <div class="logo-prism">
                        <div class="prism-shape"></div>
                    </div>
                </div>
                <span class="logo-text">
                    <span class="prism">Pro Manage</span>
                    <span class="flux">AI</span>
                </span>
            </a>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="innovation.php" class="nav-link">Innovation</a></li>
                <li><a href="#evenement" class="nav-link">Event</a></li>
                <li><a href="#reclamation" class="nav-link">Reclamation</a></li>
                <li><a href="#actualite" class="nav-link">News</a></li>
                <li><a href="sign-up.php" class="nav-link btn-signup">Sign Up</a></li>
                <li><a href="sign-in.php" class="nav-link btn-signin">Sign In</a></li>
                <li class="profile-dropdown">
                    <a href="#" class="nav-link profile-icon active" id="profileDropdownBtn" title="Profile">👤</a>
                    <div class="dropdown-menu" id="profileDropdownMenu">
                        <a href="logout.php" class="dropdown-item">
                            <span>🚪</span> Deconnexion
                        </a>
                    </div>
                </li>
            </ul>
            
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Profile Container -->
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-avatar">👤</div>
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($user['fullname']); ?></h1>
                    <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p>📧 <?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($userIdeas); ?></div>
                            <div class="stat-label">Ideas Posted</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($userInvestments); ?></div>
                            <div class="stat-label">Investments</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user['is_admin'] ? 'Admin' : 'User'; ?></div>
                            <div class="stat-label">Account Type</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- User Information Display -->
            <div class="section-card full-width">
                <h2>👤 Personal Information</h2>
                <div class="info-display">
                    <div class="info-item">
                        <label>Full Name</label>
                        <p><?php echo htmlspecialchars($user['fullname']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Username</label>
                        <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email Address</label>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Account Type</label>
                        <p><?php echo $user['is_admin'] ? '👑 Administrator' : '👤 Regular User'; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Member Since</label>
                        <p><?php echo isset($user['created_at']) ? date('F d, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>

            <!-- My Ideas -->
            <div class="section-card full-width">
                <h2>💡 My Ideas</h2>
                <div class="ideas-list">
                    <?php if (!empty($userIdeas)): ?>
                        <?php foreach ($userIdeas as $idea): ?>
                            <div class="item-card">
                                <h3><?php echo htmlspecialchars($idea['titre']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($idea['description'], 0, 150)) . '...'; ?></p>
                                <div class="item-meta">
                                    <span class="date-badge">Posted: <?php echo date('M d, Y', strtotime($idea['date_creation'])); ?></span>
                                    <a href="innovation.php" style="color: var(--accent-cyan); text-decoration: none;">View Details →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">💡</div>
                            <p>You haven't posted any ideas yet. Share your innovative concepts!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Investments -->
            <div class="section-card full-width">
                <h2>💰 My Investments</h2>
                <div class="investments-list">
                    <?php if (!empty($userInvestments)): ?>
                        <?php foreach ($userInvestments as $inv): ?>
                            <div class="item-card">
                                <h3><?php echo htmlspecialchars($inv['titre']); ?></h3>
                                <p>Investment Amount: <?php echo $inv['montant'] ? '$' . number_format($inv['montant'], 2) : 'Pending'; ?></p>
                                <div class="item-meta">
                                    <span class="date-badge">Requested: <?php echo date('M d, Y', strtotime($inv['date_demande'])); ?></span>
                                    <span class="status-badge status-<?php echo $inv['statut'] === 'en_attente' ? 'pending' : ($inv['statut'] === 'accepte' ? 'accepted' : 'refused'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $inv['statut'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">💰</div>
                            <p>You haven't made any investment requests yet. Browse innovative ideas to invest!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="footer-logo">
                    <div class="logo-icon">
                        <div class="logo-prism">
                            <div class="prism-shape"></div>
                        </div>
                    </div>
                    <span class="logo-text">
                        <span class="prism">Pro Manage</span>
                        <span class="flux">AI</span>
                    </span>
                </div>
                <p class="footer-description">
                    Connecting innovative ideas with passionate investors. Building the future, one project at a time.
                </p>
                <div class="footer-social">
                    <a href="#" class="social-icon">f</a>
                    <a href="#" class="social-icon">t</a>
                    <a href="#" class="social-icon">in</a>
                    <a href="#" class="social-icon">ig</a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Platform</h4>
                <div class="footer-links">
                    <a href="innovation.php">Innovation Management</a>
                    <a href="#evenement">Events</a>
                    <a href="#reclamation">Reclamations</a>
                    <a href="#actualite">Latest News</a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>For Users</h4>
                <div class="footer-links">
                    <a href="#">Share Your Idea</a>
                    <a href="#">Find Investors</a>
                    <a href="#">Browse Projects</a>
                    <a href="#">Internships</a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Resources</h4>
                <div class="footer-links">
                    <a href="#">How It Works</a>
                    <a href="#">Success Stories</a>
                    <a href="#">FAQ</a>
                    <a href="#">Contact Support</a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="copyright">
                © 2025 Pro Manage AI. All rights reserved.
            </div>
            <div class="footer-credits">
                Empowering Innovation & Investment
            </div>
        </div>
    </footer>

    <script src="assets/js/templatemo-prism-scripts.js"></script>
    <script>
        // Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('profileDropdownBtn');
            const dropdownMenu = document.getElementById('profileDropdownMenu');
            
            if (profileBtn && dropdownMenu) {
                // Toggle dropdown on click
                profileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdownMenu.classList.toggle('show');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>
