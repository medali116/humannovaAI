<?php
session_start();
// Include database connection
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/IdeaController.php';

// Check if user is logged in
$authController = new AuthController($pdo);
$authController->requireLogin();

$ideaController = new IdeaController($pdo);

// Fetch only current user's ideas
$ideas = $ideaController->getIdeasByUser($_SESSION['user_id']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Fetch investments for each idea
$ideaInvestments = [];
try {
    $stmt = $pdo->prepare("
        SELECT i.*, 
               u.fullname as investor_name,
               u.email as investor_email,
               id.titre as idea_title
        FROM investissements i
        JOIN utilisateurs u ON i.utilisateur_id = u.id  
        JOIN idee id ON i.idee_id = id.id
        WHERE id.utilisateur_id = ?
        ORDER BY i.date_demande DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group investments by idea ID
    foreach ($investments as $investment) {
        $ideaInvestments[$investment['idee_id']][] = $investment;
    }
} catch (PDOException $e) {
    $ideaInvestments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ideas - Pro Manage AI</title>
    <link rel="stylesheet" href="assets/templatemo-prism-flux.css">
    <style>
        /* Additional styles for my ideas cards */
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
            background-clip: text;
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

        .idea-icon {
            font-size: 2rem;
            opacity: 0.3;
        }

        .no-ideas {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

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

        /* Investment Summary Styles */
        .idea-card.has-investments {
            border-color: var(--accent-green);
            background: linear-gradient(135deg, var(--carbon-medium), rgba(0, 255, 136, 0.05));
        }

        .investment-badge {
            background: linear-gradient(135deg, var(--accent-green), #00d084);
            color: var(--text-primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
        }

        .investment-summary {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--accent-green);
        }

        .investment-summary h4 {
            color: var(--accent-green);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .investment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 10px;
            background: var(--carbon-dark);
            border-radius: 8px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .stat-value {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .stat-value.accepted {
            color: var(--accent-green);
        }

        .stat-value.pending {
            color: var(--accent-orange);
        }

        .stat-value.rejected {
            color: var(--accent-red);
        }

        .view-investors-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: var(--text-primary);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .view-investors-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(153, 69, 255, 0.4);
        }

        .funding-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1), rgba(0, 212, 132, 0.1));
            border: 1px solid var(--accent-green);
            border-radius: 8px;
            color: var(--accent-green);
            font-weight: 600;
            text-align: center;
            justify-content: center;
        }

        .funding-icon {
            font-size: 1.2rem;
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
            .innovation-hero h1 {
                font-size: 2.5rem;
            }

            .ideas-grid {
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
                        <a href="my-ideas.php" class="nav-dropdown-item active">
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
    <section class="innovation-hero">
        <div>
            <h1>💡 My Ideas</h1>
            <p>Manage your innovative ideas. Edit, update, or delete your projects at any time.</p>
            
            <div style="margin-top: 30px;">
                <button onclick="openAddModal()" class="add-idea-btn">
                    ➕ Add New Idea
                </button>
            </div>
        </div>
    </section>

    <!-- Ideas Grid -->
    <section class="ideas-container">
        <div class="ideas-grid">
            <?php if (!empty($ideas)): ?>
                <?php foreach ($ideas as $idea): ?>
                    <?php 
                        // Get investments for this idea
                        $currentInvestments = $ideaInvestments[$idea['id']] ?? [];
                        $acceptedInvestments = array_filter($currentInvestments, function($inv) { 
                            return $inv['statut'] === 'accepte'; 
                        });
                        $hasAcceptedInvestments = !empty($acceptedInvestments);
                        $totalInvestmentAmount = array_sum(array_column($acceptedInvestments, 'montant'));
                    ?>
                    <div class="idea-card <?php echo $hasAcceptedInvestments ? 'has-investments' : ''; ?>">
                        <div class="idea-header">
                            <div>
                                <h3 class="idea-title"><?php echo htmlspecialchars($idea['titre']); ?></h3>
                                <div class="idea-meta">
                                    <span class="author-name">By You</span>
                                    <span class="idea-date">• <?php echo date('M d, Y', strtotime($idea['date_creation'])); ?></span>
                                    <?php if ($hasAcceptedInvestments): ?>
                                        <span class="investment-badge">🏆 Funded</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="idea-icon">💡</div>
                        </div>
                        
                        <p class="idea-description">
                            <?php echo htmlspecialchars(substr($idea['description'], 0, 200)) . (strlen($idea['description']) > 200 ? '...' : ''); ?>
                        </p>
                        
                        <?php if (!empty($currentInvestments)): ?>
                            <div class="investment-summary">
                                <h4>💰 Investment Status</h4>
                                <div class="investment-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Total Investors:</span>
                                        <span class="stat-value"><?php echo count($currentInvestments); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Total Funded:</span>
                                        <span class="stat-value"><?php echo number_format($totalInvestmentAmount, 2); ?> DT</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Accepted:</span>
                                        <span class="stat-value accepted"><?php echo count($acceptedInvestments); ?></span>
                                    </div>
                                    <?php 
                                        $pendingCount = count(array_filter($currentInvestments, function($inv) { return $inv['statut'] === 'en_attente'; }));
                                        $rejectedCount = count(array_filter($currentInvestments, function($inv) { return $inv['statut'] === 'refuse'; }));
                                    ?>
                                    <?php if ($pendingCount > 0): ?>
                                        <div class="stat-item">
                                            <span class="stat-label">Pending:</span>
                                            <span class="stat-value pending"><?php echo $pendingCount; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($rejectedCount > 0): ?>
                                        <div class="stat-item">
                                            <span class="stat-label">Rejected:</span>
                                            <span class="stat-value rejected"><?php echo $rejectedCount; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button class="view-investors-btn" onclick='viewInvestors(<?php echo json_encode($currentInvestments); ?>, "<?php echo htmlspecialchars($idea['titre']); ?>")'>
                                    👥 View All Investors
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="idea-footer">
                            <?php if ($hasAcceptedInvestments): ?>
                                <div class="funding-notice">
                                    <span class="funding-icon">🔒</span>
                                    <span>Idea is funded and cannot be modified</span>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; gap: 10px;">
                                    <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($idea); ?>)'>
                                        ✏️ Edit
                                    </button>
                                    <button class="delete-btn" onclick="deleteIdea(<?php echo $idea['id']; ?>)">
                                        🗑️ Delete
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-ideas">
                    <p>You haven't shared any ideas yet. Be the first to share your innovative concept!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Add Idea Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>💡 Share New Idea</h2>
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
                    <a href="my-ideas.php">Share Your Idea</a>
                    <a href="innovation.php">Find Investors</a>
                    <a href="innovation.php">Browse Projects</a>
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
    <script src="assets/js/my-ideas-validation.js"></script>
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

    <!-- View Investors Modal -->
    <div id="investorsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="investorsModalTitle">👥 Investors for: [Idea Title]</h2>
                <span class="close-modal" onclick="closeInvestorsModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <div id="investorsContainer">
                    <!-- Investors will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeInvestorsModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // View investors functionality
        function viewInvestors(investments, ideaTitle) {
            const modal = document.getElementById('investorsModal');
            const title = document.getElementById('investorsModalTitle');
            const container = document.getElementById('investorsContainer');
            
            title.textContent = `👥 Investors for: ${ideaTitle}`;
            
            if (!investments || investments.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No investments yet.</p>';
                modal.classList.add('active');
                return;
            }
            
            // Sort investments by status priority (accepted first, then pending, then rejected)
            const sortedInvestments = investments.sort((a, b) => {
                const statusOrder = { 'accepte': 1, 'en_attente': 2, 'refuse': 3 };
                return statusOrder[a.statut] - statusOrder[b.statut];
            });
            
            let html = '<div style=\"display: grid; gap: 15px;\">';
            
            sortedInvestments.forEach(investment => {
                let statusClass = '';
                let statusText = '';
                let statusIcon = '';
                
                switch(investment.statut) {
                    case 'accepte':
                        statusClass = 'accepted';
                        statusText = 'Accepted';
                        statusIcon = '✅';
                        break;
                    case 'en_attente':
                        statusClass = 'pending';
                        statusText = 'Pending';
                        statusIcon = '⏳';
                        break;
                    case 'refuse':
                        statusClass = 'rejected';
                        statusText = 'Rejected';
                        statusIcon = '❌';
                        break;
                }
                
                const investmentDate = new Date(investment.date_demande).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
                
                html += `
                    <div class="investor-card" style="
                        background: var(--carbon-dark);
                        border: 1px solid var(--metal-dark);
                        border-radius: 10px;
                        padding: 20px;
                        display: grid;
                        grid-template-columns: 1fr auto;
                        gap: 20px;
                        align-items: center;
                    ">
                        <div>
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                <div style="
                                    width: 50px;
                                    height: 50px;
                                    border-radius: 50%;
                                    background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 1.5rem;
                                ">👤</div>
                                <div>
                                    <h4 style="color: var(--text-primary); margin: 0; font-size: 1.1rem;">
                                        ${investment.investor_name}
                                    </h4>
                                    <p style="color: var(--text-secondary); margin: 5px 0 0 0; font-size: 0.9rem;">
                                        ${investment.investor_email}
                                    </p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 20px; align-items: center;">
                                <div>
                                    <span style="color: var(--text-secondary); font-size: 0.8rem;">Investment Amount</span>
                                    <div style="color: var(--accent-purple); font-weight: 700; font-size: 1.3rem;">
                                        ${parseFloat(investment.montant).toLocaleString()} DT
                                    </div>
                                </div>
                                <div>
                                    <span style="color: var(--text-secondary); font-size: 0.8rem;">Date</span>
                                    <div style="color: var(--text-primary);">${investmentDate}</div>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <div class="status-badge-large ${statusClass}" style="
                                display: inline-flex;
                                align-items: center;
                                gap: 8px;
                                padding: 12px 20px;
                                border-radius: 25px;
                                font-weight: 600;
                                font-size: 0.9rem;
                                ${statusClass === 'accepted' ? 'background: linear-gradient(135deg, #00ff88, #00d084); color: #000;' :
                                  statusClass === 'pending' ? 'background: linear-gradient(135deg, #ff9500, #ffb84d); color: #000;' :
                                  'background: linear-gradient(135deg, #ff3333, #ff6b6b); color: #fff;'}
                            ">
                                <span style="font-size: 1.1rem;">${statusIcon}</span>
                                ${statusText}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeInvestorsModal() {
            const modal = document.getElementById('investorsModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal.active');
                if (activeModal && activeModal.id === 'investorsModal') {
                    closeInvestorsModal();
                }
            }
        });
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.id === 'investorsModal') {
                closeInvestorsModal();
            }
        });
    </script>
</body>
</html>