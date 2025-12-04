<?php
/**
 * BackOffice - Gestion des Participations
 */

require_once '../../config/database.php';
require_once '../../models/Participation.php';

$database = new Database();
$db = $database->getConnection();

$participation = new Participation($db);

$message = '';
$messageType = '';

/**
 * Function to send email notification
 */
function sendStatusEmail($email, $userName, $eventTitle, $status) {
    // Validate email first
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email invalide: ' . $email];
    }
    
    $statusText = $status === 'approuve' ? 'APPROUVÉE' : 'REFUSÉE';
    $statusEmoji = $status === 'approuve' ? '✅' : '❌';
    $statusColor = $status === 'approuve' ? '#00ff88' : '#ff3333';
    $statusMessage = $status === 'approuve' 
        ? 'Félicitations ! Votre participation a été approuvée. Nous avons hâte de vous voir !'
        : 'Malheureusement, votre participation n\'a pas été retenue cette fois-ci. N\'hésitez pas à participer à nos prochains événements.';
    
    $subject = "$statusEmoji Participation $statusText - $eventTitle";
    
    $htmlMessage = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #0a0a0a; color: #ffffff; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
            .header { text-align: center; padding: 30px; background: linear-gradient(135deg, #141414, #1a1a1a); border-radius: 20px 20px 0 0; border: 1px solid rgba(255,255,255,0.1); }
            .logo { font-size: 28px; font-weight: 900; }
            .logo-cyan { color: #00ffff; }
            .logo-purple { color: #9945ff; }
            .content { padding: 40px; background: #141414; border-left: 1px solid rgba(255,255,255,0.1); border-right: 1px solid rgba(255,255,255,0.1); }
            .status-badge { display: inline-block; padding: 15px 30px; border-radius: 30px; font-size: 18px; font-weight: 700; background: $statusColor; color: #000; margin: 20px 0; }
            .event-box { background: rgba(0,255,255,0.05); border-left: 4px solid #00ffff; padding: 20px; border-radius: 0 12px 12px 0; margin: 25px 0; }
            .event-title { color: #00ffff; font-size: 18px; font-weight: 700; }
            .message { color: #b0b0b0; font-size: 16px; line-height: 1.8; }
            .footer { text-align: center; padding: 30px; background: #0d0d0d; border-radius: 0 0 20px 20px; border: 1px solid rgba(255,255,255,0.1); color: #606060; font-size: 13px; }
            .cta-btn { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #00ffff, #4361ee); color: #000; text-decoration: none; border-radius: 30px; font-weight: 700; margin-top: 25px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'><span class='logo-cyan'>HUMAN</span><span class='logo-purple'>NOVA AI</span></div>
            </div>
            <div class='content'>
                <h1 style='color: #ffffff; margin: 0 0 20px 0;'>Bonjour $userName,</h1>
                <p class='message'>Nous avons une mise à jour concernant votre participation :</p>
                
                <div style='text-align: center;'>
                    <div class='status-badge'>$statusEmoji $statusText</div>
                </div>
                
                <div class='event-box'>
                    <div class='event-title'>📅 $eventTitle</div>
                </div>
                
                <p class='message'>$statusMessage</p>
                
                <div style='text-align: center;'>
                    <a href='#' class='cta-btn'>Voir nos événements</a>
                </div>
            </div>
            <div class='footer'>
                <p>© 2025 Human Nova AI. Tous droits réservés.</p>
                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers for HTML email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Human Nova AI <noreply@humannova.ai>\r\n";
    $headers .= "Reply-To: noreply@humannova.ai\r\n";
    
    // Try to send email
    $sent = @mail($email, $subject, $htmlMessage, $headers);
    
    if ($sent) {
        return ['success' => true, 'message' => 'Email envoyé à ' . $email];
    } else {
        // If mail() fails, still return success for the status update
        // but note that email wasn't sent (common on localhost)
        return ['success' => true, 'message' => 'Statut mis à jour (email non envoyé - serveur mail non configuré)'];
    }
}

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete' && isset($_POST['id'])) {
        $participation->setId($_POST['id']);
        if ($participation->delete()) {
            $message = 'Participation supprimée avec succès';
            $messageType = 'success';
        } else {
            $message = 'Erreur lors de la suppression';
            $messageType = 'error';
        }
    }
    
    if ($action === 'update' && isset($_POST['id'])) {
        $errors = [];
        if (empty($_POST['commentaire']) || strlen($_POST['commentaire']) < 10) {
            $errors[] = 'Le commentaire doit contenir au moins 10 caractères';
        }
        
        if (empty($errors)) {
            // Get participation details before update
            $oldData = $participation->readOneWithJoinById($_POST['id']);
            $oldStatus = $oldData['statut'] ?? '';
            
            $participation->setId($_POST['id']);
            $participation->setCommentaire($_POST['commentaire']);
            $participation->setStatut($_POST['statut']);
            
            if ($participation->update()) {
                $message = 'Participation modifiée avec succès';
                $messageType = 'success';
                
                // Send email if status changed to approved or rejected
                $newStatus = $_POST['statut'];
                if ($newStatus !== $oldStatus && ($newStatus === 'approuve' || $newStatus === 'rejete')) {
                    $emailResult = sendStatusEmail(
                        $oldData['utilisateur_email'],
                        $oldData['utilisateur_prenom'] . ' ' . $oldData['utilisateur_nom'],
                        $oldData['evenement_titre'],
                        $newStatus
                    );
                }
                // Redirect after successful update
                header('Location: manage-participations.php?success=1');
                exit;
            } else {
                $message = 'Erreur lors de la modification';
                $messageType = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        }
    }
    
    if ($action === 'updateStatut' && isset($_POST['id']) && isset($_POST['statut'])) {
        // Get participation details
        $pData = $participation->readOneWithJoinById($_POST['id']);
        
        $participation->setId($_POST['id']);
        $participation->setStatut($_POST['statut']);
        
        if ($participation->updateStatut()) {
            $message = 'Statut mis à jour';
            $messageType = 'success';
            
            // Send email notification
            $newStatus = $_POST['statut'];
            if ($newStatus === 'approuve' || $newStatus === 'rejete') {
                $emailResult = sendStatusEmail(
                    $pData['utilisateur_email'],
                    $pData['utilisateur_prenom'] . ' ' . $pData['utilisateur_nom'],
                    $pData['evenement_titre'],
                    $newStatus
                );
                $message .= ' - ' . $emailResult['message'];
            }
        } else {
            $message = 'Erreur';
            $messageType = 'error';
        }
    }
}

// Check for success redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = 'Participation modifiée avec succès';
    $messageType = 'success';
}

$filterStatut = $_GET['statut'] ?? 'all';

if ($filterStatut !== 'all') {
    $stmt = $participation->readByStatut($filterStatut);
} else {
    $stmt = $participation->readAll();
}
$participations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = $participation->getStatistics();

$editParticipation = null;
if (isset($_GET['edit'])) {
    $editParticipation = $participation->readOneWithJoinById($_GET['edit']);
}

$viewParticipation = null;
if (isset($_GET['view'])) {
    $viewParticipation = $participation->readOneWithJoinById($_GET['view']);
}

// Function to get file icon based on extension
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝',
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'png' => '🖼️',
        'gif' => '🖼️',
        'zip' => '📦',
        'rar' => '📦',
    ];
    return $icons[$ext] ?? '📎';
}

// Function to check if file is an image
function isImage($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Participations</title>
    <style>
        :root {
            --carbon-dark: #0a0a0a;
            --carbon-medium: #141414;
            --metal-dark: #2a2a2a;
            --metal-light: #3a3a3a;
            --accent-cyan: #00ffff;
            --accent-purple: #9945ff;
            --accent-green: #00ff88;
            --accent-orange: #ff9500;
            --accent-red: #ff3333;
            --accent-blue: #4361ee;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --text-dim: #606060;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--carbon-dark); color: var(--text-primary); font-family: 'Segoe UI', Tahoma, sans-serif; display: flex; min-height: 100vh; }
        
        /* Sidebar - Dashboard Style */
        .sidebar { 
            width: 280px; 
            background: linear-gradient(180deg, #12121a 0%, #0a0a0f 100%); 
            border-right: 1px solid rgba(255,255,255,0.08); 
            position: fixed; 
            top: 0; left: 0; 
            height: 100vh; 
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        .sidebar-logo { 
            padding: 30px 25px; 
            border-bottom: 1px solid rgba(255,255,255,0.08); 
        }
        .logo-link {
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
        .logo-text { font-size: 20px; font-weight: 800; }
        .logo-text .prism { color: var(--accent-cyan); }
        .logo-text .flux { color: var(--accent-purple); }
        .sidebar-section { padding: 25px 15px; flex: 1; }
        .sidebar-section-title { 
            color: var(--text-dim); 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
            padding: 0 15px;
            margin-bottom: 15px; 
        }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 6px; }
        .sidebar-link { 
            display: flex; 
            align-items: center; 
            gap: 14px; 
            padding: 14px 18px; 
            color: var(--text-secondary); 
            text-decoration: none; 
            border-radius: 12px; 
            transition: all 0.3s; 
            font-size: 14px;
            font-weight: 600;
            position: relative;
        }
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%);
            width: 4px; height: 0;
            background: linear-gradient(180deg, var(--accent-cyan), var(--accent-purple));
            border-radius: 0 4px 4px 0;
            transition: height 0.3s;
        }
        .sidebar-link:hover { background: rgba(255,255,255,0.05); color: var(--text-primary); }
        .sidebar-link:hover::before { height: 60%; }
        .sidebar-link.active { 
            background: linear-gradient(135deg, rgba(0,255,255,0.15), rgba(153,69,255,0.1)); 
            color: var(--text-primary); 
        }
        .sidebar-link.active::before { height: 70%; }
        .sidebar-icon {
            width: 42px; height: 42px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s;
        }
        .sidebar-link:hover .sidebar-icon,
        .sidebar-link.active .sidebar-icon {
            background: linear-gradient(135deg, rgba(0,255,255,0.2), rgba(153,69,255,0.2));
        }
        
        /* Main */
        .main-content { margin-left: 280px; flex: 1; padding: 30px; }
        .page-header { margin-bottom: 25px; }
        .page-title { font-size: 28px; font-weight: 900; background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .page-subtitle { color: var(--text-secondary); margin-top: 5px; }
        
        .message { padding: 18px 25px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-weight: 600; animation: slideIn 0.4s ease; display: flex; align-items: center; justify-content: center; gap: 12px; }
        .message.success { background: linear-gradient(135deg, rgba(0,255,136,0.15), rgba(0,255,255,0.1)); border: 2px solid var(--accent-green); color: var(--accent-green); box-shadow: 0 5px 25px rgba(0,255,136,0.2); }
        .message.success::before { content: '✓'; font-size: 22px; background: var(--accent-green); color: #000; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .message.error { background: linear-gradient(135deg, rgba(255,51,51,0.15), rgba(255,100,100,0.1)); border: 2px solid var(--accent-red); color: var(--accent-red); box-shadow: 0 5px 25px rgba(255,51,51,0.2); }
        .message.error::before { content: '✕'; font-size: 22px; background: var(--accent-red); color: #fff; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        @keyframes slideIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Stats */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-box { background: var(--carbon-medium); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s; }
        .stat-box:hover { border-color: var(--accent-cyan); transform: translateY(-3px); }
        .stat-box .value { font-size: 32px; font-weight: 700; color: var(--accent-cyan); }
        .stat-box .label { color: var(--text-secondary); font-size: 11px; margin-top: 5px; text-transform: uppercase; }
        
        /* Filters */
        .filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-btn { padding: 10px 20px; border-radius: 20px; border: 1px solid var(--metal-light); background: transparent; color: var(--text-secondary); text-decoration: none; font-size: 13px; transition: all 0.3s; }
        .filter-btn:hover, .filter-btn.active { background: var(--accent-cyan); color: #000; border-color: var(--accent-cyan); }
        
        /* Table */
        .table-container { background: var(--carbon-medium); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: var(--carbon-dark); color: var(--accent-cyan); padding: 12px; text-align: left; font-size: 11px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: var(--text-secondary); font-size: 13px; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .status-badge { padding: 5px 12px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .status-badge.en_attente { background: rgba(255,149,0,0.2); color: var(--accent-orange); }
        .status-badge.approuve { background: rgba(0,255,136,0.2); color: var(--accent-green); }
        .status-badge.rejete { background: rgba(255,51,51,0.2); color: var(--accent-red); }
        
        .action-btn { padding: 6px 10px; border-radius: 6px; border: none; cursor: pointer; font-size: 12px; transition: all 0.3s; text-decoration: none; display: inline-block; margin: 2px; }
        .btn-view { background: var(--accent-blue); color: #fff; }
        .btn-edit { background: var(--accent-orange); color: #000; }
        .btn-approve { background: var(--accent-green); color: #000; }
        .btn-reject { background: var(--accent-red); color: #fff; }
        .btn-delete { background: linear-gradient(135deg, #ff4444, #cc0000); color: #fff; }
        
        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; overflow-y: auto; padding: 30px; }
        .modal-overlay.active { display: flex; align-items: center; justify-content: center; }
        .modal-box { background: var(--carbon-medium); border: 2px solid var(--accent-cyan); border-radius: 16px; padding: 30px; max-width: 600px; width: 100%; animation: modalIn 0.4s ease; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(-50px) scale(0.9); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .modal-title { color: var(--accent-cyan); font-size: 20px; font-weight: 700; }
        .modal-close { 
            background: rgba(255,51,51,0.15); 
            color: var(--accent-red); 
            border: 2px solid var(--accent-red); 
            width: 44px; 
            height: 44px; 
            border-radius: 50%; 
            cursor: pointer; 
            font-size: 18px; 
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .modal-close svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
        }
        .modal-close:hover { 
            background: var(--accent-red); 
            color: #fff; 
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 0 20px rgba(255,51,51,0.5);
        }
        
        .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .detail-label { color: var(--text-secondary); }
        .detail-value { color: var(--text-primary); font-weight: 600; }
        
        .comment-box { background: var(--carbon-dark); padding: 15px; border-radius: 10px; margin-top: 15px; color: var(--text-secondary); line-height: 1.6; }
        
        /* File Display */
        .file-section { margin-top: 20px; padding: 20px; background: rgba(0,255,255,0.05); border: 1px solid rgba(0,255,255,0.2); border-radius: 12px; }
        .file-section-title { color: var(--accent-cyan); font-size: 14px; font-weight: 700; margin-bottom: 15px; text-transform: uppercase; }
        .file-preview { text-align: center; }
        .file-preview img { max-width: 100%; max-height: 300px; border-radius: 10px; border: 2px solid var(--accent-cyan); }
        .file-preview iframe { width: 100%; height: 400px; border: 2px solid var(--accent-cyan); border-radius: 10px; }
        .file-link { display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue)); color: #000; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 15px; transition: all 0.3s; }
        .file-link:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,255,255,0.4); }
        .file-info { color: var(--text-dim); font-size: 12px; margin-top: 10px; }
        
        /* Form */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: var(--accent-cyan); font-weight: 600; margin-bottom: 8px; font-size: 12px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px; background: var(--carbon-dark); border: 2px solid var(--metal-dark); border-radius: 10px; color: var(--text-primary); font-size: 14px; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--accent-cyan); }
        .form-group textarea.invalid { border-color: var(--accent-red); }
        .validation-msg { font-size: 12px; margin-top: 5px; }
        .validation-msg.valid { color: var(--accent-green); }
        .validation-msg.invalid { color: var(--accent-red); }
        .char-counter { font-size: 11px; color: var(--text-dim); text-align: right; margin-top: 5px; }
        .char-counter.warning { color: var(--accent-orange); }
        .char-counter.valid { color: var(--accent-green); }
        .form-actions { display: flex; gap: 15px; margin-top: 25px; }
        
        .btn { padding: 12px 25px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; transition: all 0.3s; text-decoration: none; font-size: 14px; }
        .btn-primary { background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue)); color: #000; }
        .btn-success { background: linear-gradient(135deg, var(--accent-green), var(--accent-cyan)); color: #000; }
        .btn-secondary { background: var(--metal-dark); color: var(--text-primary); }
        .btn-danger { background: linear-gradient(135deg, #ff4444, #cc0000); color: #fff; }
        
        /* Confirm */
        .confirm-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 3000; align-items: center; justify-content: center; }
        .confirm-overlay.active { display: flex; }
        .confirm-box { background: var(--carbon-medium); border: 2px solid var(--accent-red); border-radius: 16px; padding: 30px; text-align: center; max-width: 400px; }
        .confirm-icon { font-size: 50px; margin-bottom: 20px; }
        .confirm-text { color: var(--text-primary); font-size: 18px; margin-bottom: 25px; }
        .confirm-buttons { display: flex; gap: 15px; justify-content: center; }
        
        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar-logo { padding: 15px; text-align: center; }
            .logo-text .flux { display: none; }
            .sidebar-section-title { display: none; }
            .sidebar-link span:not(:first-child) { display: none; }
            .sidebar-link { justify-content: center; padding: 15px; }
            .main-content { margin-left: 80px; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <a href="../../index.php" class="logo-link">
                <div class="logo-icon">H</div>
                <div class="logo-text"><span class="prism">HUMAN</span> <span class="flux">NOVA AI</span></div>
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Menu</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="sidebar-link"><div class="sidebar-icon">📊</div><span>Dashboard</span></a></li>
                <li><a href="manage-events.php" class="sidebar-link"><div class="sidebar-icon">📅</div><span>Événements</span></a></li>
                <li><a href="manage-events.php?type=quiz" class="sidebar-link"><div class="sidebar-icon">🎯</div><span>Quiz</span></a></li>
                <li><a href="manage-participations.php" class="sidebar-link active"><div class="sidebar-icon">👥</div><span>Participations</span></a></li>
            </ul>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Outils</div>
            <ul class="sidebar-menu">
                <li><a href="../front/events.php" class="sidebar-link"><div class="sidebar-icon">🌐</div><span>Front Office</span></a></li>
            </ul>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">👥 Gestion des Participations</h1>
            <p class="page-subtitle">Gérez les participations aux événements</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>" id="alertMessage"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-box"><div class="value"><?php echo $stats['total'] ?? 0; ?></div><div class="label">Total</div></div>
            <div class="stat-box"><div class="value" style="color: var(--accent-orange);"><?php echo $stats['en_attente'] ?? 0; ?></div><div class="label">En attente</div></div>
            <div class="stat-box"><div class="value" style="color: var(--accent-green);"><?php echo $stats['approuve'] ?? 0; ?></div><div class="label">Approuvées</div></div>
            <div class="stat-box"><div class="value" style="color: var(--accent-red);"><?php echo $stats['rejete'] ?? 0; ?></div><div class="label">Rejetées</div></div>
        </div>

        <div class="filters">
            <a href="?statut=all" class="filter-btn <?php echo $filterStatut === 'all' ? 'active' : ''; ?>">Tous</a>
            <a href="?statut=en_attente" class="filter-btn <?php echo $filterStatut === 'en_attente' ? 'active' : ''; ?>">En attente</a>
            <a href="?statut=approuve" class="filter-btn <?php echo $filterStatut === 'approuve' ? 'active' : ''; ?>">Approuvées</a>
            <a href="?statut=rejete" class="filter-btn <?php echo $filterStatut === 'rejete' ? 'active' : ''; ?>">Rejetées</a>
        </div>

        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Utilisateur</th><th>Email</th><th>Événement</th><th>Fichier</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($participations)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 40px;">Aucune participation</td></tr>
                    <?php else: ?>
                        <?php foreach ($participations as $p): ?>
                            <tr>
                                <td>#<?php echo $p['id']; ?></td>
                                <td><?php echo htmlspecialchars($p['utilisateur_prenom'] . ' ' . $p['utilisateur_nom']); ?></td>
                                <td><?php echo htmlspecialchars($p['utilisateur_email']); ?></td>
                                <td><?php echo htmlspecialchars(substr($p['evenement_titre'] ?? '', 0, 20)); ?>...</td>
                                <td>
                                    <?php if (!empty($p['fichier_url'])): ?>
                                        <?php echo getFileIcon($p['fichier_url']); ?>
                                        <a href="?view=<?php echo $p['id']; ?>" style="color: var(--accent-cyan);">Voir</a>
                                    <?php else: ?>
                                        <span style="color: var(--text-dim);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge <?php echo $p['statut']; ?>"><?php echo strtoupper(str_replace('_', ' ', $p['statut'])); ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($p['date_participation'])); ?></td>
                                <td>
                                    <a href="?view=<?php echo $p['id']; ?>" class="action-btn btn-view">👁️</a>
                                    <a href="?edit=<?php echo $p['id']; ?>" class="action-btn btn-edit">✏️</a>
                                    <?php if ($p['statut'] === 'en_attente'): ?>
                                        <form method="POST" style="display: inline;"><input type="hidden" name="action" value="updateStatut"><input type="hidden" name="id" value="<?php echo $p['id']; ?>"><input type="hidden" name="statut" value="approuve"><button type="submit" class="action-btn btn-approve">✓</button></form>
                                        <form method="POST" style="display: inline;"><input type="hidden" name="action" value="updateStatut"><input type="hidden" name="id" value="<?php echo $p['id']; ?>"><input type="hidden" name="statut" value="rejete"><button type="submit" class="action-btn btn-reject">✗</button></form>
                                    <?php endif; ?>
                                    <button type="button" class="action-btn btn-delete" onclick="showDeleteConfirm(<?php echo $p['id']; ?>)">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- View Modal -->
    <?php if ($viewParticipation): ?>
    <div class="modal-overlay active" id="viewModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">📋 Détails de la participation</h3>
                <a href="manage-participations.php<?php echo $filterStatut !== 'all' ? '?statut=' . $filterStatut : ''; ?>" class="modal-close" aria-label="Fermer">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </a>
            </div>
            <div class="detail-row"><span class="detail-label">ID</span><span class="detail-value">#<?php echo $viewParticipation['id']; ?></span></div>
            <div class="detail-row"><span class="detail-label">Utilisateur</span><span class="detail-value"><?php echo htmlspecialchars($viewParticipation['utilisateur_prenom'] . ' ' . $viewParticipation['utilisateur_nom']); ?></span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value"><?php echo htmlspecialchars($viewParticipation['utilisateur_email']); ?></span></div>
            <div class="detail-row"><span class="detail-label">Événement</span><span class="detail-value"><?php echo htmlspecialchars($viewParticipation['evenement_titre']); ?></span></div>
            <div class="detail-row"><span class="detail-label">Statut</span><span class="detail-value"><span class="status-badge <?php echo $viewParticipation['statut']; ?>"><?php echo strtoupper(str_replace('_', ' ', $viewParticipation['statut'])); ?></span></span></div>
            <div class="detail-row"><span class="detail-label">Date</span><span class="detail-value"><?php echo date('d/m/Y à H:i', strtotime($viewParticipation['date_participation'])); ?></span></div>
            
            <div style="margin-top: 20px;">
                <span class="detail-label">Commentaire:</span>
                <div class="comment-box"><?php echo nl2br(htmlspecialchars($viewParticipation['commentaire'] ?? 'Aucun commentaire')); ?></div>
            </div>
            
            <?php if (!empty($viewParticipation['fichier_url'])): ?>
            <div class="file-section">
                <div class="file-section-title"><?php echo getFileIcon($viewParticipation['fichier_url']); ?> Fichier joint</div>
                <div class="file-preview">
                    <?php 
                    $fichierNom = $viewParticipation['fichier_url'];
                    
                    // Try multiple possible paths
                    $possiblePaths = [
                        ['path' => '../../uploads/' . $fichierNom, 'real' => __DIR__ . '/../../uploads/' . $fichierNom],
                        ['path' => '../../uploads/participations/' . $fichierNom, 'real' => __DIR__ . '/../../uploads/participations/' . $fichierNom],
                        ['path' => '../../../uploads/' . $fichierNom, 'real' => __DIR__ . '/../../../uploads/' . $fichierNom],
                    ];
                    
                    $fileUrl = '';
                    $fileExists = false;
                    
                    foreach ($possiblePaths as $pathInfo) {
                        if (file_exists($pathInfo['real'])) {
                            $fileUrl = $pathInfo['path'];
                            $fileExists = true;
                            break;
                        }
                    }
                    
                    // Default path if not found
                    if (!$fileUrl) {
                        $fileUrl = '../../uploads/' . $fichierNom;
                    }
                    
                    $ext = strtolower(pathinfo($fichierNom, PATHINFO_EXTENSION));
                    
                    if (!$fileExists): ?>
                        <div style="padding: 20px; background: rgba(255,149,0,0.1); border: 1px solid var(--accent-orange); border-radius: 10px; margin-bottom: 15px;">
                            <p style="color: var(--accent-orange);">⚠️ Fichier non trouvé sur le serveur</p>
                            <p style="color: var(--text-dim); font-size: 11px;">Nom du fichier: <?php echo htmlspecialchars($fichierNom); ?></p>
                            <p style="color: var(--text-dim); font-size: 10px; margin-top: 5px;">Le fichier doit être uploadé dans le dossier /uploads/</p>
                        </div>
                    <?php endif;
                    
                    if ($fileExists): 
                        if (isImage($fichierNom)): ?>
                            <img src="<?php echo $fileUrl; ?>" alt="Fichier" style="max-width: 100%; border-radius: 10px;">
                        <?php elseif ($ext === 'pdf'): ?>
                            <embed src="<?php echo $fileUrl; ?>" type="application/pdf" width="100%" height="400" style="border-radius: 10px; border: 1px solid rgba(0,255,255,0.3);">
                        <?php else: ?>
                            <div style="padding: 30px; background: var(--carbon-dark); border-radius: 10px; text-align: center;">
                                <div style="font-size: 48px; margin-bottom: 15px;"><?php echo getFileIcon($fichierNom); ?></div>
                                <div style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars($fichierNom); ?></div>
                            </div>
                        <?php endif;
                    endif; ?>
                </div>
                <div class="file-info">Type: <?php echo strtoupper($ext); ?></div>
                <?php if ($fileExists): ?>
                <a href="<?php echo $fileUrl; ?>" class="file-link" target="_blank" download="<?php echo htmlspecialchars($fichierNom); ?>">📥 Télécharger le fichier</a>
                <?php else: ?>
                <p style="color: var(--text-dim); font-size: 12px; margin-top: 10px;">💡 Les nouveaux fichiers uploadés seront visibles ici.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Modal -->
    <?php if ($editParticipation): ?>
    <div class="modal-overlay active" id="editModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Modifier la participation</h3>
                <a href="manage-participations.php<?php echo $filterStatut !== 'all' ? '?statut=' . $filterStatut : ''; ?>" class="modal-close" aria-label="Fermer">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </a>
            </div>
            <form method="POST" onsubmit="return validateEditForm()">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $editParticipation['id']; ?>">
                
                <div class="form-group">
                    <label>Utilisateur</label>
                    <input type="text" value="<?php echo htmlspecialchars($editParticipation['utilisateur_prenom'] . ' ' . $editParticipation['utilisateur_nom']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Événement</label>
                    <input type="text" value="<?php echo htmlspecialchars($editParticipation['evenement_titre']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Commentaire * (min 10 caractères)</label>
                    <textarea name="commentaire" id="editCommentaire" rows="4" oninput="validateCommentaire(); updateCommentCounter()"><?php echo htmlspecialchars($editParticipation['commentaire']); ?></textarea>
                    <div class="char-counter" id="commentCounter"><?php echo strlen($editParticipation['commentaire']); ?> / 10 caractères minimum</div>
                    <div class="validation-msg" id="commentaire-msg"></div>
                </div>
                
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut">
                        <option value="en_attente" <?php echo $editParticipation['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="approuve" <?php echo $editParticipation['statut'] === 'approuve' ? 'selected' : ''; ?>>Approuvé</option>
                        <option value="rejete" <?php echo $editParticipation['statut'] === 'rejete' ? 'selected' : ''; ?>>Rejeté</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <a href="manage-participations.php<?php echo $filterStatut !== 'all' ? '?statut=' . $filterStatut : ''; ?>" class="btn btn-secondary">✕ Annuler</a>
                    <button type="submit" class="btn btn-success">💾 Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Confirm Delete -->
    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-box">
            <div class="confirm-icon">⚠️</div>
            <div class="confirm-text">Supprimer cette participation ?</div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="confirm-buttons">
                    <button type="button" class="btn btn-secondary" onclick="hideDeleteConfirm()">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-hide message
        document.addEventListener('DOMContentLoaded', function() {
            const alertMsg = document.getElementById('alertMessage');
            if (alertMsg) {
                setTimeout(() => {
                    alertMsg.style.transition = 'opacity 0.5s ease';
                    alertMsg.style.opacity = '0';
                    setTimeout(() => alertMsg.remove(), 500);
                }, 5000);
            }
        });
        
        function showDeleteConfirm(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('confirmOverlay').classList.add('active');
        }
        
        function hideDeleteConfirm() {
            document.getElementById('confirmOverlay').classList.remove('active');
        }
        
        function validateCommentaire() {
            const textarea = document.getElementById('editCommentaire');
            const val = textarea.value.trim();
            const msgEl = document.getElementById('commentaire-msg');
            const valid = val.length >= 10;
            
            textarea.classList.remove('valid', 'invalid');
            textarea.classList.add(valid ? 'valid' : 'invalid');
            msgEl.className = 'validation-msg ' + (valid ? 'valid' : 'invalid');
            msgEl.textContent = valid ? `✓ ${val.length} caractères` : `⚠ Min 10 caractères (${val.length}/10)`;
            return valid;
        }
        
        function updateCommentCounter() {
            const textarea = document.getElementById('editCommentaire');
            const counter = document.getElementById('commentCounter');
            const len = textarea.value.length;
            counter.textContent = `${len} / 10 caractères minimum`;
            counter.className = 'char-counter';
            if (len >= 10) counter.classList.add('valid');
            else if (len > 0) counter.classList.add('warning');
        }
        
        function validateEditForm() { return validateCommentaire(); }
        
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                hideDeleteConfirm();
                <?php if ($viewParticipation || $editParticipation): ?>
                window.location.href = 'manage-participations.php<?php echo $filterStatut !== 'all' ? '?statut=' . $filterStatut : ''; ?>';
                <?php endif; ?>
            }
        });
    </script>
</body>
</html>
