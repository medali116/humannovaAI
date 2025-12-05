<?php
session_start();
// Include database connection
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/InvestmentController.php';

// Check if user is logged in
$authController = new AuthController($pdo);
$authController->requireLogin();

$investmentController = new InvestmentController($pdo);

// Fetch current user's investments
$investments = $investmentController->getInvestmentsByUser($_SESSION['user_id']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? $_SESSION['user_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Investments - Pro Manage AI</title>
    <link rel="stylesheet" href="assets/templatemo-prism-flux.css">
    <style>
        /* Investment page specific styles */
        .investment-hero {
            min-height: 40vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 20px 60px;
            position: relative;
            overflow: hidden;
        }

        .investment-hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .investment-hero p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .investments-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .investments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .investment-card {
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            padding: 30px;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .investment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-purple), var(--accent-cyan));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .investment-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-purple);
            box-shadow: 0 10px 40px rgba(153, 69, 255, 0.3);
        }

        .investment-card:hover::before {
            transform: scaleX(1);
        }

        .investment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }

        .investment-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .investment-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .author-name {
            color: var(--accent-cyan);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .investment-date {
            color: var(--text-dim);
            font-size: 0.85rem;
        }

        .investment-amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent-purple);
            margin: 15px 0;
        }

        .investment-amount::after {
            content: ' DT';
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .investment-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .investment-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid var(--metal-dark);
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .no-investments {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .edit-btn {
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: var(--text-primary);
        }

        .edit-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 255, 255, 0.4);
        }

        .delete-btn {
            background: linear-gradient(135deg, #ff3333, #ff6b6b);
            color: var(--text-primary);
        }

        .delete-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255, 51, 51, 0.4);
        }

        .edit-btn:disabled, .delete-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
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
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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

        .modal-content form {
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

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: 'Rajdhani', sans-serif;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 3px rgba(153, 69, 255, 0.1);
        }

        .form-group small {
            display: block;
            color: var(--text-dim);
            font-size: 0.8rem;
            margin-top: 8px;
        }

        .modal-footer {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid var(--metal-dark);
            margin-top: 10px;
        }

        .btn-cancel,
        .btn-submit {
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

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Navigation Dropdown Styles */
        .nav-dropdown {
            position: relative;
        }
        
        .dropdown-trigger {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dropdown-trigger:hover {
            color: var(--accent-cyan) !important;
        }
        
        .nav-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: linear-gradient(135deg, var(--carbon-dark), var(--carbon-medium));
            border: 1px solid var(--accent-purple);
            border-radius: 10px;
            padding: 10px 0;
            min-width: 200px;
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        
        .nav-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .nav-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .nav-dropdown-item:hover {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            color: var(--text-primary);
        }
        
        .nav-dropdown-item.active {
            background: linear-gradient(135deg, rgba(153, 69, 255, 0.2), rgba(0, 255, 255, 0.2));
            color: var(--accent-cyan);
        }
        
        .nav-dropdown-item span {
            font-size: 1rem;
        }

        /* Profile Dropdown */
        .profile-dropdown { position: relative; }
        .dropdown-menu {
            position: absolute; top: 100%; right: 0;
            background: var(--carbon-dark); border: 1px solid var(--metal-dark);
            border-radius: 10px; padding: 10px 0; min-width: 180px;
            margin-top: 10px; opacity: 0; visibility: hidden;
            transform: translateY(-10px); transition: all 0.3s ease;
            z-index: 1000; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .dropdown-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-item {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 20px; color: var(--text-primary);
            text-decoration: none; transition: all 0.3s ease;
        }
        .dropdown-item:hover { background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan)); }
        .dropdown-item span { font-size: 1.2rem; }

        /* Responsive */
        @media (max-width: 768px) {
            .investment-hero h1 {
                font-size: 2.5rem;
            }

            .investments-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
            }
            
            .modal-footer {
                flex-direction: column;
            }
            
            .btn-cancel,
            .btn-submit {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
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
                <li class="nav-dropdown">
                    <a href="#" class="nav-link active dropdown-trigger" id="innovationDropdownBtn">Innovation ⌄</a>
                    <div class="nav-dropdown-menu" id="innovationDropdownMenu">
                        <a href="innovation.php" class="nav-dropdown-item">
                            <span>🔍</span> Browse Ideas
                        </a>
                        <a href="my-ideas.php" class="nav-dropdown-item">
                            <span>💡</span> My Ideas
                        </a>
                        <a href="my-investments.php" class="nav-dropdown-item active">
                            <span>💰</span> My Investments
                        </a>
                    </div>
                </li>
                <li><a href="#evenement" class="nav-link">Event</a></li>
                <li><a href="#reclamation" class="nav-link">Reclamation</a></li>
                <li><a href="#actualite" class="nav-link">News</a></li>
                <?php if (!$isLoggedIn): ?>
                    <li><a href="sign-up.php" class="nav-link btn-signup">Sign Up</a></li>
                    <li><a href="sign-in.php" class="nav-link btn-signin">Sign In</a></li>
                <?php endif; ?>
                <?php if ($isLoggedIn): ?>
                <li class="profile-dropdown">
                    <a href="#" class="nav-link profile-icon" id="profileDropdownBtn" title="Profile">👤</a>
                    <div class="dropdown-menu" id="profileDropdownMenu">
                        <a href="userprofile.php" class="dropdown-item">
                            <span>👤</span> My Profile
                        </a>
                        <a href="logout.php" class="dropdown-item">
                            <span>🚪</span> Deconnexion
                        </a>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="investment-hero">
        <div>
            <h1>💰 My Investments</h1>
            <p>Track your investment portfolio. Manage pending requests and monitor your approved investments.</p>
        </div>
    </section>

    <!-- Investments Grid -->
    <section class="investments-container">
        <div class="investments-grid">
            <?php if (!empty($investments)): ?>
                <?php foreach ($investments as $investment): ?>
                    <div class="investment-card">
                        <div class="investment-header">
                            <div>
                                <h3 class="investment-title"><?php echo htmlspecialchars($investment['idea_title']); ?></h3>
                                <div class="investment-meta">
                                    <span class="author-name">By <?php echo htmlspecialchars($investment['idea_author']); ?></span>
                                    <span class="investment-date">• <?php echo $investment['formatted_date']; ?></span>
                                </div>
                                <div class="investment-amount"><?php echo number_format($investment['montant'], 2); ?></div>
                            </div>
                            <div class="status-badge status-<?php echo $investment['statut']; ?>">
                                <?php 
                                    switch($investment['statut']) {
                                        case 'en_attente': echo '⏳ Pending'; break;
                                        case 'accepte': echo '✅ Accepted'; break;
                                        case 'refuse': echo '❌ Refused'; break;
                                        default: echo $investment['statut'];
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <p class="investment-description">
                            <?php echo htmlspecialchars(substr($investment['idea_description'], 0, 150)) . (strlen($investment['idea_description']) > 150 ? '...' : ''); ?>
                        </p>
                        
                        <div class="investment-footer">
                            <div class="action-buttons">
                                <?php if ($investment['statut'] === 'en_attente'): ?>
                                    <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($investment); ?>)'>
                                        ✏️ Edit
                                    </button>
                                    <button class="delete-btn" onclick="deleteInvestment(<?php echo $investment['id']; ?>)">
                                        🗑️ Cancel
                                    </button>
                                <?php else: ?>
                                    <span style="color: var(--text-dim); font-size: 0.9rem;">
                                        <?php echo $investment['statut'] === 'accepte' ? '✅ Investment Approved' : '❌ Investment Declined'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-investments">
                    <p>You haven't made any investments yet. <a href="innovation.php" style="color: var(--accent-cyan);">Browse innovative ideas</a> and start investing!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Edit Investment Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Investment</h2>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editInvestmentForm">
                <input type="hidden" id="edit_investment_id" name="investment_id">
                <div id="editIdeaDetails" style="margin-bottom: 20px;">
                    <!-- Idea details will be loaded here -->
                </div>
                <div class="form-group">
                    <label for="edit_montant">Investment Amount (DT) *</label>
                    <input type="number" id="edit_montant" name="montant" step="0.01" placeholder="Minimum 500 DT">
                    <small>Minimum investment amount is 500 DT</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Update Investment</button>
                </div>
            </form>
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
                    <a href="my-ideas.php">My Ideas</a>
                    <a href="my-investments.php">My Investments</a>
                    <a href="#evenement">Events</a>
                    <a href="#reclamation">Reclamations</a>
                    <a href="#actualite">Latest News</a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>For Investors</h4>
                <div class="footer-links">
                    <a href="innovation.php">Browse Ideas</a>
                    <a href="my-investments.php">Track Investments</a>
                    <a href="innovation.php">Investment Opportunities</a>
                    <a href="#">Investment Guide</a>
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
    <script src="assets/js/my-investments-validation.js"></script>
    <script>
        // Navigation and Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Innovation dropdown
            const innovationBtn = document.getElementById('innovationDropdownBtn');
            const innovationDropdown = document.getElementById('innovationDropdownMenu');
            
            if (innovationBtn && innovationDropdown) {
                innovationBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    innovationDropdown.classList.toggle('show');
                });
                
                document.addEventListener('click', function(e) {
                    if (!innovationBtn.contains(e.target) && !innovationDropdown.contains(e.target)) {
                        innovationDropdown.classList.remove('show');
                    }
                });
            }
            
            // Profile dropdown
            const profileBtn = document.getElementById('profileDropdownBtn');
            const dropdownMenu = document.getElementById('profileDropdownMenu');
            
            if (profileBtn && dropdownMenu) {
                profileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdownMenu.classList.toggle('show');
                });
                
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