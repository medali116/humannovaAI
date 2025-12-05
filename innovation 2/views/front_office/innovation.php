<?php
session_start();
// Include database connection
require_once '../../config/config.php';
require_once '../../controllers/IdeaController.php';

$ideaController = new IdeaController($pdo);

// Fetch all ideas with user information
$ideas = $ideaController->getAllIdeas();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? $_SESSION['user_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innovation - Pro Manage AI</title>
    <link rel="stylesheet" href="assets/templatemo-prism-flux.css">
    <style>
        /* Additional styles for innovation cards */
        .innovation-hero {
            min-height: 40vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 20px 60px;
            position: relative;
            overflow: hidden;
        }

        .innovation-hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .innovation-hero p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .ideas-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .ideas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .idea-card {
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border: 1px solid var(--metal-dark);
            border-radius: 15px;
            padding: 30px;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .idea-card::before {
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

        .idea-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-purple);
            box-shadow: 0 10px 40px rgba(153, 69, 255, 0.3);
        }

        .idea-card:hover::before {
            transform: scaleX(1);
        }

        .idea-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }

        .idea-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .idea-meta {
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

        .idea-date {
            color: var(--text-dim);
            font-size: 0.85rem;
        }

        .idea-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .idea-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid var(--metal-dark);
        }

        .invest-btn {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: var(--text-primary);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .invest-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(153, 69, 255, 0.5);
        }

        .idea-icon {
            font-size: 2rem;
            opacity: 0.3;
        }

        .filter-section {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .filter-btn {
            background: var(--carbon-medium);
            color: var(--text-secondary);
            border: 1px solid var(--metal-dark);
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            color: var(--text-primary);
            border-color: transparent;
        }

        .no-ideas {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .innovation-hero h1 {
                font-size: 2.5rem;
            }

            .ideas-grid {
                grid-template-columns: 1fr;
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
                        <a href="my-investments.php" class="nav-dropdown-item">
                            <span>💰</span> My Investments
                        </a>
                    </div>
                </li>
                <li><a href="#evenement" class="nav-link">Event</a></li>
                <li><a href="#reclamation" class="nav-link">Reclamation</a></li>
                <li><a href="#actualite" class="nav-link">News</a></li>
                <li><a href="sign-up.php" class="nav-link btn-signup">Sign Up</a></li>
                <li><a href="sign-in.php" class="nav-link btn-signin">Sign In</a></li>
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
            </ul>
            
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="innovation-hero">
        <div>
            <h1>💡 Innovation Hub</h1>
            <p>Discover groundbreaking ideas seeking investment. Connect with visionary entrepreneurs and fund the next big thing.</p>
            
            <?php if ($isLoggedIn): ?>
            <div style="margin-top: 30px;">
                <a href="my-ideas.php" class="add-idea-btn">
                    ➕ Share Your Idea
                </a>
            </div>
            <?php else: ?>
            <div style="margin-top: 30px;">
                <a href="sign-in.php" class="add-idea-btn">
                    🚀 Join to Share Ideas
                </a>
            </div>
            <?php endif; ?>
            
            <div class="filter-section">
                <button class="filter-btn active" data-filter="all">All Ideas</button>
                <button class="filter-btn" data-filter="technology">Technology</button>
                <button class="filter-btn" data-filter="business">Business</button>
                <button class="filter-btn" data-filter="healthcare">Healthcare</button>
                <button class="filter-btn" data-filter="sustainability">Sustainability</button>
            </div>
        </div>
    </section>

    <!-- Ideas Grid -->
    <section class="ideas-container">
        <div class="ideas-grid">
            <?php if (!empty($ideas)): ?>
                <?php foreach ($ideas as $idea): ?>
                    <div class="idea-card">
                        <div class="idea-header">
                            <div>
                                <h3 class="idea-title"><?php echo htmlspecialchars($idea['titre']); ?></h3>
                                <div class="idea-meta">
                                    <span class="author-name">By <?php echo htmlspecialchars($idea['fullname']); ?></span>
                                    <span class="idea-date">• <?php echo date('M d, Y', strtotime($idea['date_creation'])); ?></span>
                                </div>
                            </div>
                            <div class="idea-icon">💡</div>
                        </div>
                        
                        <p class="idea-description">
                            <?php echo htmlspecialchars(substr($idea['description'], 0, 200)) . (strlen($idea['description']) > 200 ? '...' : ''); ?>
                        </p>
                        
                        <!-- Universal invest button for all ideas -->
                        <div class="idea-footer">
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <?php if ($isLoggedIn && $idea['utilisateur_id'] == $currentUserId): ?>
                                    <span style="color: var(--text-dim); font-size: 0.9rem;">Your idea</span>
                                <?php else: ?>
                                    <span style="color: var(--text-dim); font-size: 0.9rem;">By <?php echo htmlspecialchars($idea['fullname']); ?></span>
                                <?php endif; ?>
                                <button class="invest-btn" onclick="investInIdea(<?php echo $idea['id']; ?>, <?php echo $idea['utilisateur_id']; ?>)">
                                    💰 Invest Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-ideas">
                    <p>No ideas available yet. Be the first to share your innovative concept!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Investment Modal -->
    <div id="investModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>💰 Invest in This Idea</h2>
                <span class="close-modal" onclick="closeInvestModal()">&times;</span>
            </div>
            <div style="padding: 30px;">
                <div id="ideaDetails" class="idea-details-section">
                    <!-- Idea details will be loaded here -->
                </div>
                
                <form id="investmentForm" style="margin-top: 30px;">
                    <input type="hidden" id="invest_idea_id" name="idee_id">
                    <div class="form-group">
                        <label for="montant">Investment Amount (DT) *</label>
                        <input type="number" id="montant" name="montant" step="0.01" placeholder="Minimum 500 DT">
                        <small>Minimum investment amount is 500 DT</small>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(153, 69, 255, 0.1), rgba(0, 255, 255, 0.1)); 
                                border: 1px solid var(--accent-purple); border-radius: 10px; padding: 20px; margin: 20px 0;">
                        <h4 style="color: var(--accent-cyan); margin-bottom: 10px;">📋 Investment Terms:</h4>
                        <ul style="color: var(--text-secondary); line-height: 1.6; margin: 0; padding-left: 20px;">
                            <li>Minimum investment: 500 DT</li>
                            <li>Your request will be reviewed by the idea owner</li>
                            <li>You can modify or cancel before approval/rejection</li>
                            <li>Track your investments in "My Investments" section</li>
                        </ul>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                        <button type="button" class="btn-cancel" onclick="closeInvestModal()">Cancel</button>
                        <button type="submit" class="btn-submit">Submit Investment</button>
                    </div>
                </form>
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
                    <a href="my-ideas.php">My Ideas</a>
                    <a href="my-investments.php">My Investments</a>
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

    <style>
        /* Add Idea Button */
        .add-idea-btn {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            color: var(--text-primary);
            border: none;
            padding: 15px 35px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 1rem;
            letter-spacing: 1px;
            box-shadow: 0 5px 20px rgba(153, 69, 255, 0.4);
        }

        .add-idea-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(153, 69, 255, 0.6);
        }



        /* Investment Modal Styles */
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

        .form-group input,
        .form-group textarea {
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

        .form-group input:focus,
        .form-group textarea:focus {
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

        .idea-details-section {
            background: linear-gradient(135deg, rgba(0, 255, 255, 0.05), rgba(153, 69, 255, 0.05));
            border: 1px solid var(--metal-dark);
            border-radius: 10px;
            padding: 20px;
        }

        .idea-details-section h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .idea-details-section p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .idea-author {
            color: var(--accent-cyan);
            font-weight: 600;
            font-size: 0.9rem;
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
            .innovation-hero h1 {
                font-size: 2.5rem;
            }

            .ideas-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
    <script>
        // Investment functionality
        function investInIdea(ideaId, ideaAuthorId) {
            <?php if (isset($_SESSION['user_id'])): ?>
                // Check if trying to invest in own idea
                const currentUserId = <?php echo $_SESSION['user_id']; ?>;
                if (ideaAuthorId == currentUserId) {
                    showMessage('You cannot invest in your own idea!', 'error');
                    return;
                }
                
                // Open investment modal
                openInvestModal(ideaId);
            <?php else: ?>
                alert('Please sign in to invest in this idea.');
                window.location.href = 'sign-in.php';
            <?php endif; ?>
        }

        // Investment modal functions
        function openInvestModal(ideaId) {
            // Fetch idea details
            const formData = new FormData();
            formData.append('action', 'get_idea');
            formData.append('idea_id', ideaId);
            
            fetch('../../controllers/InvestmentController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const idea = data.idea;
                    document.getElementById('invest_idea_id').value = ideaId;
                    
                    // Populate idea details
                    document.getElementById('ideaDetails').innerHTML = `
                        <h3>${idea.titre}</h3>
                        <p class="idea-author">By ${idea.author_name}</p>
                        <p>${idea.description}</p>
                    `;
                    
                    // Show modal
                    document.getElementById('investModal').classList.add('active');
                    document.body.style.overflow = 'hidden';
                    
                    // Focus on amount input
                    setTimeout(() => {
                        document.getElementById('montant').focus();
                    }, 100);
                } else {
                    showMessage(data.message || 'Failed to load idea details.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Network error. Please try again.', 'error');
            });
        }

        function closeInvestModal() {
            document.getElementById('investModal').classList.remove('active');
            document.body.style.overflow = '';
            
            // Reset form
            document.getElementById('investmentForm').reset();
        }

        // Investment form submission
        document.addEventListener('DOMContentLoaded', function() {
            const investForm = document.getElementById('investmentForm');
            if (investForm) {
                investForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (validateInvestmentForm()) {
                        const formData = new FormData(this);
                        formData.append('action', 'create');
                        
                        const submitBtn = this.querySelector('.btn-submit');
                        const originalText = submitBtn.textContent;
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Processing...';
                        
                        fetch('../../controllers/InvestmentController.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showMessage(data.message || 'Investment submitted successfully!', 'success');
                                closeInvestModal();
                            } else {
                                showMessage(data.message || 'Failed to submit investment.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showMessage('Network error. Please try again.', 'error');
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        });
                    }
                });
            }
        });

        // Investment form validation with detailed feedback
        function validateInvestmentForm() {
            const montant = document.getElementById('montant');
            const amount = parseFloat(montant.value);
            
            // Clear previous validation
            clearFieldValidation(montant);
            
            // Check if empty
            if (!montant.value || montant.value.trim() === '') {
                showFieldError(montant, 'Investment amount is required.');
                return false;
            }
            
            // Check if valid number
            if (isNaN(amount) || amount <= 0) {
                showFieldError(montant, 'Please enter a valid amount.');
                return false;
            }
            
            // Check minimum amount with JavaScript (not HTML5)
            if (amount < 500) {
                showFieldError(montant, 'Minimum investment amount is 500 DT. You entered: ' + amount + ' DT');
                return false;
            }
            
            // Show success
            showFieldSuccess(montant);
            return true;
        }
        
        // Validation helper functions for investment form
        function showFieldError(field, message) {
            field.style.borderColor = '#ff6b6b';
            field.style.boxShadow = '0 0 0 3px rgba(255, 107, 107, 0.1)';
            
            const existingError = field.parentNode.querySelector('.validation-error');
            if (existingError) existingError.remove();
            
            const errorElement = document.createElement('div');
            errorElement.className = 'validation-error';
            errorElement.style.cssText = `
                color: #ff6b6b;
                font-size: 0.8rem;
                margin-top: 5px;
                animation: fadeIn 0.3s ease;
            `;
            errorElement.innerHTML = `⚠️ ${message}`;
            field.parentNode.appendChild(errorElement);
        }
        
        function showFieldSuccess(field) {
            field.style.borderColor = '#4ade80';
            field.style.boxShadow = '0 0 0 3px rgba(74, 222, 128, 0.1)';
        }
        
        function clearFieldValidation(field) {
            field.style.borderColor = '';
            field.style.boxShadow = '';
            const existingError = field.parentNode.querySelector('.validation-error');
            if (existingError) existingError.remove();
        }

        // Message display function
        function showMessage(message, type) {
            const existingMessages = document.querySelectorAll('.toast-message');
            existingMessages.forEach(msg => msg.remove());
            
            const toast = document.createElement('div');
            toast.className = 'toast-message';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10001;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                max-width: 400px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                ${type === 'success' 
                    ? 'background: linear-gradient(135deg, #4ade80, #22c55e);' 
                    : 'background: linear-gradient(135deg, #ff6b6b, #ff3333);'
                }
            `;
            
            toast.innerHTML = `${type === 'success' ? '✅' : '❌'} ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => {
                if (toast.parentNode) toast.remove();
            }, 3000);
        }

        // Modal close on escape and outside click
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal.active');
                if (activeModal && activeModal.id === 'investModal') {
                    closeInvestModal();
                }
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target.id === 'investModal') {
                closeInvestModal();
            }
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                console.log('Filter by:', filter);
            });
        });
    </script>
</body>
</html>
