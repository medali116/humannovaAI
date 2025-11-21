<?php
session_start();
// Include database connection
require_once '../../config/config.php';
require_once '../../models/Idea.php';

$ideaModel = new Idea($pdo);

// Fetch all ideas with user information
$ideas = $ideaModel->getAllIdeas();

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
                <li><a href="innovation.php" class="nav-link active">Innovation</a></li>
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
                <button onclick="openAddModal()" class="add-idea-btn">
                    ➕ Share Your Idea
                </button>
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
                        
                        <?php if ($isLoggedIn && $idea['utilisateur_id'] == $currentUserId): ?>
                        <!-- User owns this idea - show edit/delete buttons -->
                        <div class="idea-footer">
                            <div style="display: flex; gap: 10px;">
                                <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($idea); ?>)'>
                                    ✏️ Edit
                                </button>
                                <button class="delete-btn" onclick="deleteIdea(<?php echo $idea['id']; ?>)">
                                    🗑️ Delete
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Regular view - show invest button -->
                        <div class="idea-footer">
                            <button class="invest-btn" onclick="investInIdea(<?php echo $idea['id']; ?>)">
                                💰 Invest Now
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-ideas">
                    <p>No ideas available yet. Be the first to share your innovative concept!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Add Idea Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>💡 Share Your Idea</h2>
                <span class="close-modal" onclick="closeAddModal()">&times;</span>
            </div>
            <form id="addIdeaForm">
                <div class="form-group">
                    <label for="titre">Title *</label>
                    <input type="text" id="titre" name="titre" placeholder="Enter your idea title (minimum 3 words, no numbers or symbols)">
                    <small>Must be at least 3 words, no numbers or symbols allowed</small>
                </div>
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" rows="6" placeholder="Describe your idea in detail (minimum 10 words)"></textarea>
                    <small>Must be at least 10 words</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Create Idea</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Idea Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Your Idea</h2>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editIdeaForm">
                <input type="hidden" id="edit_idea_id" name="idea_id">
                <div class="form-group">
                    <label for="edit_titre">Title *</label>
                    <input type="text" id="edit_titre" name="titre" placeholder="Enter your idea title (minimum 3 words, no numbers or symbols)">
                    <small>Must be at least 3 words, no numbers or symbols allowed</small>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description *</label>
                    <textarea id="edit_description" name="description" rows="6" placeholder="Describe your idea in detail (minimum 10 words)"></textarea>
                    <small>Must be at least 10 words</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Update Idea</button>
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
    <script src="assets/js/idea-validation.js"></script>
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

        /* Edit and Delete Buttons */
        .edit-btn, .delete-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .edit-btn {
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: var(--text-primary);
        }

        .edit-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(0, 255, 255, 0.5);
        }

        .delete-btn {
            background: linear-gradient(135deg, #ff3333, #ff6b6b);
            color: var(--text-primary);
        }

        .delete-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(255, 51, 51, 0.5);
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
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 3px rgba(153, 69, 255, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
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
        }
    </style>
    <script>
        // Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
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
        function investInIdea(ideaId) {
            // Check if user is logged in
            <?php if (isset($_SESSION['user_id'])): ?>
                window.location.href = 'invest.php?idea_id=' + ideaId;
            <?php else: ?>
                alert('Please sign in to invest in this idea.');
                window.location.href = 'sign-in.php';
            <?php endif; ?>
        }

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                // Add filter logic here if needed
                console.log('Filter by:', filter);
            });
        });
    </script>
</body>
</html>
