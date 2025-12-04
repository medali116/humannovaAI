<?php
/**
 * BackOffice - Gestion des Événements
 * CRUD complet avec PHP + Validation + Configuration Quiz originale
 */

require_once '../../config/database.php';
require_once '../../models/Evenement.php';
require_once '../../models/Question.php';
require_once '../../models/Reponse.php';

$database = new Database();
$db = $database->getConnection();

$evenement = new Evenement($db);
$question = new Question($db);
$reponse = new Reponse($db);

$message = '';
$messageType = '';

// Filter by type
$filterType = isset($_GET['type']) ? $_GET['type'] : '';

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // SUPPRIMER
    if ($action === 'delete' && isset($_POST['id'])) {
        $evenement->setId($_POST['id']);
        if ($evenement->delete()) {
            $message = 'Événement supprimé avec succès';
            $messageType = 'success';
        } else {
            $message = 'Erreur lors de la suppression';
            $messageType = 'error';
        }
    }
    
    // CRÉER
    if ($action === 'create') {
        $errors = [];
        if (empty($_POST['type'])) $errors[] = 'Le type est obligatoire';
        if (empty($_POST['titre']) || strlen($_POST['titre']) < 3) $errors[] = 'Le titre doit contenir au moins 3 caractères';
        if (empty($_POST['description']) || strlen($_POST['description']) < 10) $errors[] = 'La description doit contenir au moins 10 caractères';
        if (empty($_POST['date_debut'])) $errors[] = 'La date de début est obligatoire';
        if (empty($_POST['date_fin'])) $errors[] = 'La date de fin est obligatoire';
        if (!empty($_POST['date_debut']) && !empty($_POST['date_fin']) && strtotime($_POST['date_fin']) <= strtotime($_POST['date_debut'])) {
            $errors[] = 'La date de fin doit être postérieure à la date de début';
        }
        
        if (empty($errors)) {
            $imageUrl = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../uploads/images/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = 'event_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                    $imageUrl = 'uploads/images/' . $fileName;
                }
            }
            
            $evenement->setType($_POST['type']);
            $evenement->setTitre($_POST['titre']);
            $evenement->setDescription($_POST['description']);
            $evenement->setDateDebut($_POST['date_debut']);
            $evenement->setDateFin($_POST['date_fin']);
            $evenement->setImageUrl($imageUrl ?: 'https://via.placeholder.com/600x400/1a1a1a/00ffff?text=Event');
            $evenement->setNombreQuestions(0);
            
            if ($evenement->create()) {
                $eventId = $evenement->getId();
                
                if ($_POST['type'] === 'quiz' && isset($_POST['questions'])) {
                    $nombreQuestions = 0;
                    foreach ($_POST['questions'] as $index => $q) {
                        if (!empty($q['texte'])) {
                            $question->setEvenementId($eventId);
                            $question->setTexteQuestion($q['texte']);
                            $question->setOrdre($index + 1);
                            
                            if ($question->create()) {
                                $questionId = $question->getId();
                                $nombreQuestions++;
                                
                                if (isset($q['reponses'])) {
                                    $correctAnswer = $_POST['reponse_correcte_' . $index] ?? 0;
                                    foreach ($q['reponses'] as $rIndex => $r) {
                                        if (!empty($r['texte'])) {
                                            $reponse->setQuestionId($questionId);
                                            $reponse->setTexteReponse($r['texte']);
                                            $reponse->setEstCorrecte($rIndex == $correctAnswer);
                                            $reponse->setOrdre($rIndex + 1);
                                            $reponse->create();
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $evenement->setId($eventId);
                    $evenement->setNombreQuestions($nombreQuestions);
                    $evenement->updateNombreQuestions();
                }
                
                $message = 'Événement créé avec succès';
                $messageType = 'success';
            } else {
                $message = 'Erreur lors de la création';
                $messageType = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        }
    }
    
    // MODIFIER
    if ($action === 'update' && isset($_POST['id'])) {
        $errors = [];
        if (empty($_POST['type'])) $errors[] = 'Le type est obligatoire';
        if (empty($_POST['titre']) || strlen($_POST['titre']) < 3) $errors[] = 'Le titre doit contenir au moins 3 caractères';
        if (empty($_POST['description']) || strlen($_POST['description']) < 10) $errors[] = 'La description doit contenir au moins 10 caractères';
        if (empty($_POST['date_debut'])) $errors[] = 'La date de début est obligatoire';
        if (empty($_POST['date_fin'])) $errors[] = 'La date de fin est obligatoire';
        
        if (empty($errors)) {
            $evenement->setId($_POST['id']);
            $evenement->readOne();
            
            $imageUrl = $_POST['current_image'] ?? '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../uploads/images/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = 'event_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                    $imageUrl = 'uploads/images/' . $fileName;
                }
            }
            
            $evenement->setType($_POST['type']);
            $evenement->setTitre($_POST['titre']);
            $evenement->setDescription($_POST['description']);
            $evenement->setDateDebut($_POST['date_debut']);
            $evenement->setDateFin($_POST['date_fin']);
            $evenement->setImageUrl($imageUrl ?: 'https://via.placeholder.com/600x400/1a1a1a/00ffff?text=Event');
            
            if ($evenement->update()) {
                if ($_POST['type'] === 'quiz') {
                    $question->setEvenementId($_POST['id']);
                    $question->deleteByEvenement();
                    
                    $nombreQuestions = 0;
                    if (isset($_POST['questions'])) {
                        foreach ($_POST['questions'] as $index => $q) {
                            if (!empty($q['texte'])) {
                                $question->setEvenementId($_POST['id']);
                                $question->setTexteQuestion($q['texte']);
                                $question->setOrdre($index + 1);
                                
                                if ($question->create()) {
                                    $questionId = $question->getId();
                                    $nombreQuestions++;
                                    
                                    if (isset($q['reponses'])) {
                                        $correctAnswer = $_POST['reponse_correcte_' . $index] ?? 0;
                                        foreach ($q['reponses'] as $rIndex => $r) {
                                            if (!empty($r['texte'])) {
                                                $reponse->setQuestionId($questionId);
                                                $reponse->setTexteReponse($r['texte']);
                                                $reponse->setEstCorrecte($rIndex == $correctAnswer);
                                                $reponse->setOrdre($rIndex + 1);
                                                $reponse->create();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $evenement->setNombreQuestions($nombreQuestions);
                    $evenement->updateNombreQuestions();
                }
                
                $message = 'Événement modifié avec succès';
                $messageType = 'success';
            } else {
                $message = 'Erreur lors de la modification';
                $messageType = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        }
    }
}

if ($filterType === 'quiz') {
    $stmt = $evenement->readByType('quiz');
} else {
    $stmt = $evenement->readAll();
}
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventsJson = [];
foreach ($events as $e) {
    $eventData = $e;
    $eventData['questions'] = [];
    if ($e['type'] === 'quiz') {
        $question->setEvenementId($e['id']);
        $questionsStmt = $question->readByEvenement();
        while ($q = $questionsStmt->fetch(PDO::FETCH_ASSOC)) {
            $reponse->setQuestionId($q['id']);
            $reponsesStmt = $reponse->readByQuestion();
            $q['reponses'] = $reponsesStmt->fetchAll(PDO::FETCH_ASSOC);
            $eventData['questions'][] = $q;
        }
    }
    $eventsJson[] = $eventData;
}

function getImagePath($url) {
    if (empty($url)) return 'https://via.placeholder.com/600x400/1a1a1a/00ffff?text=Event';
    if (strpos($url, 'uploads/') === 0) return '../../' . $url;
    return $url;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Événements - Back Office</title>
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
        
        .btn { padding: 12px 25px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; }
        .btn-add { background: linear-gradient(135deg, var(--accent-green), var(--accent-cyan)); color: #000; margin-bottom: 25px; }
        .btn-add:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,255,255,0.3); }
        .btn-primary { background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue)); color: #000; }
        .btn-danger { background: linear-gradient(135deg, #ff4444, #cc0000); color: #fff; }
        .btn-secondary { background: var(--metal-dark); color: var(--text-primary); }
        .btn-purple { background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue)); color: #fff; }
        
        /* Grid */
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        .event-card { background: var(--carbon-medium); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; overflow: hidden; transition: all 0.4s; }
        .event-card:hover { transform: translateY(-8px); border-color: var(--accent-cyan); box-shadow: 0 20px 50px rgba(0,255,255,0.15); }
        .card-image { position: relative; height: 160px; overflow: hidden; }
        .card-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .event-card:hover .card-image img { transform: scale(1.1); }
        .card-badge { position: absolute; top: 12px; right: 12px; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-quiz { background: var(--accent-purple); color: #fff; }
        .badge-normal { background: var(--accent-cyan); color: #000; }
        .card-content { padding: 20px; }
        .card-title { color: var(--text-primary); font-size: 17px; font-weight: 700; margin-bottom: 10px; }
        .card-description { color: var(--text-secondary); font-size: 13px; line-height: 1.5; margin-bottom: 12px; }
        .card-date { color: var(--accent-cyan); font-size: 12px; font-weight: 600; }
        .card-actions { display: flex; gap: 10px; margin-top: 15px; }
        .card-actions .btn { flex: 1; justify-content: center; padding: 10px; font-size: 13px; }
        
        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; overflow-y: auto; padding: 30px; }
        .modal-overlay.active { display: block; }
        .modal-box { background: var(--carbon-medium); border: 2px solid var(--accent-cyan); border-radius: 16px; padding: 30px; max-width: 800px; margin: 0 auto; animation: modalIn 0.4s ease; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(-50px); } to { opacity: 1; transform: translateY(0); } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .modal-title { color: var(--accent-cyan); font-size: 22px; font-weight: 700; }
        .modal-close { 
            background: rgba(255,51,51,0.15); 
            color: var(--accent-red); 
            border: 2px solid var(--accent-red); 
            width: 44px; 
            height: 44px; 
            border-radius: 50%; 
            cursor: pointer; 
            font-size: 20px; 
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            padding: 0;
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
        
        /* Form */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: var(--accent-cyan); font-weight: 600; margin-bottom: 8px; font-size: 12px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px; background: var(--carbon-dark); border: 2px solid var(--metal-dark); border-radius: 10px; color: var(--text-primary); font-size: 15px; transition: all 0.3s; }
        /* Date input styling - simple white icon */
        .form-group input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
            padding: 5px;
        }
        /* Character counter */
        .char-counter { font-size: 11px; color: var(--text-dim); text-align: right; margin-top: 5px; }
        .char-counter.warning { color: var(--accent-orange); }
        .char-counter.valid { color: var(--accent-green); }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--accent-cyan); box-shadow: 0 0 15px rgba(0,255,255,0.2); }
        .form-group input.valid { border-color: var(--accent-green); }
        .form-group input.invalid, .form-group textarea.invalid { border-color: var(--accent-red); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px; }
        .validation-msg { font-size: 12px; margin-top: 5px; }
        .validation-msg.valid { color: var(--accent-green); }
        .validation-msg.invalid { color: var(--accent-red); }
        
        /* Image Upload */
        .image-upload { border: 2px dashed var(--metal-light); border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .image-upload:hover { border-color: var(--accent-cyan); background: rgba(0,255,255,0.05); }
        .image-upload.valid { border-color: var(--accent-green); background: rgba(0,255,136,0.05); }
        .image-upload.invalid { border-color: var(--accent-red); background: rgba(255,51,51,0.05); }
        .image-preview { max-width: 150px; max-height: 100px; border-radius: 8px; margin-top: 10px; }
        
        /* Quiz Section - Original Design */
        .quiz-section { background: rgba(153,69,255,0.08); border: 1px solid rgba(153,69,255,0.3); border-radius: 12px; padding: 25px; margin-top: 20px; }
        .quiz-section-title { color: var(--accent-purple); font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .quiz-config { display: flex; gap: 20px; align-items: flex-end; margin-bottom: 20px; }
        .quiz-config .form-group { flex: 1; margin-bottom: 0; }
        
        .question-block { background: linear-gradient(135deg, var(--carbon-dark), rgba(153,69,255,0.05)); border: 2px solid rgba(153,69,255,0.3); border-radius: 12px; padding: 25px; margin: 20px 0; }
        .question-block:hover { border-color: var(--accent-purple); }
        .question-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .question-title { color: var(--accent-purple); font-size: 16px; font-weight: 700; text-transform: uppercase; }
        
        .reponse-block { background: var(--carbon-medium); border-left: 4px solid var(--accent-cyan); padding: 15px 20px; margin: 12px 0; border-radius: 0 10px 10px 0; }
        
        /* Radio buttons for correct answer - Original style */
        .radio-wrapper { display: flex; align-items: center; gap: 12px; margin-top: 12px; cursor: pointer; }
        .radio-wrapper input[type="radio"] { display: none; }
        .custom-radio { width: 24px; height: 24px; border: 2px solid var(--accent-cyan); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
        .radio-wrapper input[type="radio"]:checked + .custom-radio { background: linear-gradient(135deg, var(--accent-green), var(--accent-cyan)); border-color: var(--accent-green); }
        .custom-radio::after { content: '✓'; color: #000; font-weight: 900; font-size: 12px; opacity: 0; transform: scale(0); transition: all 0.2s; }
        .radio-wrapper input[type="radio"]:checked + .custom-radio::after { opacity: 1; transform: scale(1); }
        .radio-text { color: var(--text-secondary); font-size: 14px; }
        
        .btn-remove { background: rgba(255,51,51,0.2); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 5px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; transition: all 0.3s; }
        .btn-remove:hover { background: var(--accent-red); color: #fff; }
        
        /* Confirm Modal */
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
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } .events-grid { grid-template-columns: 1fr; } }
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
                <li><a href="manage-events.php" class="sidebar-link <?php echo empty($filterType) ? 'active' : ''; ?>"><div class="sidebar-icon">📅</div><span>Événements</span></a></li>
                <li><a href="manage-events.php?type=quiz" class="sidebar-link <?php echo $filterType === 'quiz' ? 'active' : ''; ?>"><div class="sidebar-icon">🎯</div><span>Quiz</span></a></li>
                <li><a href="manage-participations.php" class="sidebar-link"><div class="sidebar-icon">👥</div><span>Participations</span></a></li>
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
            <h1 class="page-title">📅 Gestion des <?php echo $filterType === 'quiz' ? 'Quiz' : 'Événements'; ?></h1>
            <p class="page-subtitle">Créez, modifiez ou supprimez vos <?php echo $filterType === 'quiz' ? 'quiz' : 'événements'; ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>" id="alertMessage"><?php echo $message; ?></div>
        <?php endif; ?>

        <button class="btn btn-add" onclick="openAddModal()">➕ Ajouter un <?php echo $filterType === 'quiz' ? 'quiz' : 'événement'; ?></button>

        <div class="events-grid">
            <?php if (empty($events)): ?>
                <p style="color: var(--text-secondary); grid-column: 1/-1; text-align: center;">Aucun <?php echo $filterType === 'quiz' ? 'quiz' : 'événement'; ?>.</p>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <div class="card-image">
                            <img src="<?php echo getImagePath($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['titre']); ?>" onerror="this.src='https://via.placeholder.com/600x400/1a1a1a/00ffff?text=Event'">
                            <span class="card-badge <?php echo $event['type'] === 'quiz' ? 'badge-quiz' : 'badge-normal'; ?>"><?php echo $event['type'] === 'quiz' ? '🎯 Quiz' : '📅 Normal'; ?></span>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($event['titre']); ?></h3>
                            <p class="card-description"><?php echo htmlspecialchars(substr($event['description'], 0, 70)); ?>...</p>
                            <div class="card-date">📅 <?php echo date('d M Y', strtotime($event['date_debut'])); ?> - <?php echo date('d M Y', strtotime($event['date_fin'])); ?></div>
                            <?php if ($event['type'] === 'quiz'): ?>
                                <p style="color: var(--accent-purple); font-size: 12px; margin-top: 8px;">❓ <?php echo $event['nombre_questions'] ?? 0; ?> question(s)</p>
                            <?php endif; ?>
                            <div class="card-actions">
                                <button class="btn btn-primary" onclick="openEditModal(<?php echo $event['id']; ?>)">✏️ Modifier</button>
                                <button class="btn btn-danger" onclick="showDeleteConfirm(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars(addslashes($event['titre'])); ?>')">🗑️ Supprimer</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Add/Edit -->
    <div class="modal-overlay" id="eventModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">➕ Ajouter</h3>
                <button type="button" class="modal-close" onclick="closeModal()" aria-label="Fermer">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="eventForm" onsubmit="return validateForm()">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="eventId" value="">
                <input type="hidden" name="current_image" id="currentImage" value="">
                
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" id="eventType" onchange="toggleQuizSection(); validateField(this)">
                        <option value="">-- Sélectionner --</option>
                        <option value="normal">📅 Événement Normal</option>
                        <option value="quiz">🎯 Quiz</option>
                    </select>
                    <div class="validation-msg" id="type-msg"></div>
                </div>
                
                <div class="form-group">
                    <label>Titre * (min 3 caractères - lettres, chiffres, _ et - uniquement)</label>
                    <input type="text" name="titre" id="eventTitre" placeholder="Titre" maxlength="150" oninput="filterTitreInput(this); validateTitre(this); updateCounter(this, 'titreCounter', 3)">
                    <div class="char-counter" id="titreCounter">0 / 3 caractères minimum</div>
                    <div class="validation-msg" id="titre-msg"></div>
                </div>
                
                <div class="form-group">
                    <label>Description * (min 10 caractères)</label>
                    <textarea name="description" id="eventDescription" placeholder="Description" maxlength="500" rows="3" oninput="validateField(this, 10); updateCounter(this, 'descCounter', 10)"></textarea>
                    <div class="char-counter" id="descCounter">0 / 10 caractères minimum</div>
                    <div class="validation-msg" id="description-msg"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date de début *</label>
                        <input type="datetime-local" name="date_debut" id="eventDateDebut" onchange="validateField(this)">
                        <div class="validation-msg" id="date_debut-msg"></div>
                    </div>
                    <div class="form-group">
                        <label>Date de fin *</label>
                        <input type="datetime-local" name="date_fin" id="eventDateFin" onchange="validateField(this); validateDates()">
                        <div class="validation-msg" id="date_fin-msg"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Image * (obligatoire)</label>
                    <div class="image-upload" id="imageUploadZone" onclick="document.getElementById('imageInput').click()">
                        <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;" onchange="previewImage(this); validateImage()">
                        <div>📷 Cliquez pour choisir une image</div>
                        <div style="color: var(--text-dim); font-size: 11px; margin-top: 5px;">JPG, PNG, GIF, WEBP (max 5MB)</div>
                        <img src="" class="image-preview" id="imagePreview" style="display: none;">
                    </div>
                    <div class="validation-msg" id="image-msg"></div>
                </div>
                
                <!-- Quiz Section - Original Design -->
                <div id="quizSection" class="quiz-section" style="display: none;">
                    <div class="quiz-section-title">🎯 Configuration du Quiz</div>
                    <div class="quiz-config">
                        <div class="form-group">
                            <label>Nombre de questions *</label>
                            <input type="number" id="nombreQuestions" min="1" max="10" value="1">
                        </div>
                        <button type="button" class="btn btn-purple" onclick="genererQuestions()">🔄 GÉNÉRER LES QUESTIONS</button>
                    </div>
                    <div id="questionsContainer"></div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">ANNULER</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">💾 ENREGISTRER</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Delete -->
    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-box">
            <div class="confirm-icon">⚠️</div>
            <div class="confirm-text" id="confirmText">Êtes-vous sûr ?</div>
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
        const eventsData = <?php echo json_encode($eventsJson); ?>;
        let questionCount = 0;
        
        // Auto-hide message after 5 seconds
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
        
        function openAddModal() {
            document.getElementById('eventForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('eventId').value = '';
            document.getElementById('modalTitle').textContent = '➕ Ajouter';
            document.getElementById('submitBtn').textContent = '💾 ENREGISTRER';
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('quizSection').style.display = 'none';
            document.getElementById('questionsContainer').innerHTML = '';
            questionCount = 0;
            clearValidations();
            document.getElementById('eventModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function openEditModal(id) {
            const event = eventsData.find(e => e.id == id);
            if (!event) return;
            
            document.getElementById('formAction').value = 'update';
            document.getElementById('eventId').value = event.id;
            document.getElementById('currentImage').value = event.image_url || '';
            document.getElementById('modalTitle').textContent = '✏️ Modifier';
            document.getElementById('submitBtn').textContent = '💾 ENREGISTRER';
            
            document.getElementById('eventType').value = event.type;
            document.getElementById('eventTitre').value = event.titre;
            document.getElementById('eventDescription').value = event.description;
            document.getElementById('eventDateDebut').value = formatDateForInput(event.date_debut);
            document.getElementById('eventDateFin').value = formatDateForInput(event.date_fin);
            
            if (event.image_url) {
                document.getElementById('imagePreview').src = getImagePath(event.image_url);
                document.getElementById('imagePreview').style.display = 'block';
            }
            
            document.getElementById('questionsContainer').innerHTML = '';
            questionCount = 0;
            
            if (event.type === 'quiz') {
                document.getElementById('quizSection').style.display = 'block';
                document.getElementById('nombreQuestions').value = event.questions ? event.questions.length : 1;
                if (event.questions && event.questions.length > 0) {
                    event.questions.forEach((q, idx) => addQuestionBlock(q.texte_question, q.reponses));
                }
            }
            
            clearValidations();
            document.getElementById('eventModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.getElementById('eventModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        function toggleQuizSection() {
            const type = document.getElementById('eventType').value;
            document.getElementById('quizSection').style.display = type === 'quiz' ? 'block' : 'none';
        }
        
        function genererQuestions() {
            const nb = parseInt(document.getElementById('nombreQuestions').value) || 1;
            document.getElementById('questionsContainer').innerHTML = '';
            questionCount = 0;
            for (let i = 0; i < nb; i++) addQuestionBlock();
        }
        
        function addQuestionBlock(texte = '', reponses = null) {
            const qIdx = questionCount;
            let repHtml = '';
            const nbRep = reponses ? reponses.length : 2;
            
            for (let i = 0; i < nbRep; i++) {
                const repTexte = reponses ? escapeHtml(reponses[i].texte_reponse) : '';
                const isCorrect = reponses ? reponses[i].est_correcte == 1 : false;
                repHtml += `
                    <div class="reponse-block">
                        <div class="form-group" style="margin-bottom: 8px;">
                            <label style="font-size: 11px;">Réponse ${i + 1}</label>
                            <input type="text" name="questions[${qIdx}][reponses][${i}][texte]" value="${repTexte}" placeholder="Texte de la réponse">
                        </div>
                        <label class="radio-wrapper">
                            <input type="radio" name="reponse_correcte_${qIdx}" value="${i}" ${isCorrect ? 'checked' : ''}>
                            <span class="custom-radio"></span>
                            <span class="radio-text">✓ Réponse correcte</span>
                        </label>
                    </div>
                `;
            }
            
            const html = `
                <div class="question-block" id="question-${qIdx}">
                    <div class="question-header">
                        <span class="question-title">Question ${qIdx + 1}</span>
                    </div>
                    <div class="form-group">
                        <label>Texte de la question *</label>
                        <input type="text" name="questions[${qIdx}][texte]" value="${escapeHtml(texte)}" placeholder="Entrez votre question">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre de réponses</label>
                            <input type="number" id="nbRep-${qIdx}" min="2" max="6" value="${nbRep}">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="button" class="btn btn-purple" style="font-size: 12px; padding: 10px 15px;" onclick="genererReponses(${qIdx})">🔄 Générer</button>
                        </div>
                    </div>
                    <div id="reponses-${qIdx}">${repHtml}</div>
                </div>
            `;
            document.getElementById('questionsContainer').insertAdjacentHTML('beforeend', html);
            questionCount++;
        }
        
        function genererReponses(qIdx) {
            const nb = parseInt(document.getElementById(`nbRep-${qIdx}`).value) || 2;
            let html = '';
            for (let i = 0; i < nb; i++) {
                html += `
                    <div class="reponse-block">
                        <div class="form-group" style="margin-bottom: 8px;">
                            <label style="font-size: 11px;">Réponse ${i + 1}</label>
                            <input type="text" name="questions[${qIdx}][reponses][${i}][texte]" placeholder="Texte de la réponse">
                        </div>
                        <label class="radio-wrapper">
                            <input type="radio" name="reponse_correcte_${qIdx}" value="${i}">
                            <span class="custom-radio"></span>
                            <span class="radio-text">✓ Réponse correcte</span>
                        </label>
                    </div>
                `;
            }
            document.getElementById(`reponses-${qIdx}`).innerHTML = html;
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function validateField(field, minLen = 0) {
            const val = field.value.trim();
            const msgId = field.id + '-msg';
            const msgEl = document.getElementById(msgId);
            let valid = val !== '';
            let msg = valid ? '✓ Valide' : '⚠ Obligatoire';
            
            if (minLen > 0 && val.length < minLen) {
                valid = false;
                msg = `⚠ Min ${minLen} caractères (${val.length}/${minLen})`;
            } else if (minLen > 0 && val.length >= minLen) {
                msg = `✓ ${val.length} caractères`;
            }
            
            field.classList.remove('valid', 'invalid');
            field.classList.add(valid ? 'valid' : 'invalid');
            if (msgEl) {
                msgEl.className = 'validation-msg ' + (valid ? 'valid' : 'invalid');
                msgEl.textContent = msg;
            }
            return valid;
        }
        
        function updateCounter(field, counterId, minLen) {
            const counter = document.getElementById(counterId);
            const len = field.value.length;
            counter.textContent = `${len} / ${minLen} caractères minimum`;
            counter.className = 'char-counter';
            if (len >= minLen) counter.classList.add('valid');
            else if (len > 0) counter.classList.add('warning');
        }
        
        // Filter titre input - allow only letters, numbers, _, -, and spaces
        function filterTitreInput(input) {
            // Remove any character that is not a letter, number, _, -, or space
            const filtered = input.value.replace(/[^a-zA-Z0-9_\-\s\u00C0-\u024F]/g, '');
            if (input.value !== filtered) {
                input.value = filtered;
            }
        }
        
        // Validate titre field
        function validateTitre(input) {
            const val = input.value.trim();
            const msgEl = document.getElementById('titre-msg');
            const validPattern = /^[a-zA-Z0-9_\-\s\u00C0-\u024F]+$/;
            
            input.classList.remove('valid', 'invalid');
            
            if (!val) {
                input.classList.add('invalid');
                msgEl.className = 'validation-msg invalid';
                msgEl.textContent = '⚠ Titre obligatoire';
                return false;
            }
            
            if (val.length < 3) {
                input.classList.add('invalid');
                msgEl.className = 'validation-msg invalid';
                msgEl.textContent = `⚠ Min 3 caractères (${val.length}/3)`;
                return false;
            }
            
            if (!validPattern.test(val)) {
                input.classList.add('invalid');
                msgEl.className = 'validation-msg invalid';
                msgEl.textContent = '⚠ Caractères non autorisés détectés';
                return false;
            }
            
            input.classList.add('valid');
            msgEl.className = 'validation-msg valid';
            msgEl.textContent = `✓ ${val.length} caractères`;
            return true;
        }
        
        function validateImage() {
            const input = document.getElementById('imageInput');
            const zone = document.getElementById('imageUploadZone');
            const msgEl = document.getElementById('image-msg');
            const currentImage = document.getElementById('currentImage').value;
            const isEdit = document.getElementById('formAction').value === 'update';
            
            // If editing and has current image, it's valid
            if (isEdit && currentImage) {
                zone.classList.remove('invalid');
                zone.classList.add('valid');
                if (msgEl) { msgEl.className = 'validation-msg valid'; msgEl.textContent = '✓ Image existante'; }
                return true;
            }
            
            // Check if file is selected
            if (!input.files || input.files.length === 0) {
                zone.classList.remove('valid');
                zone.classList.add('invalid');
                if (msgEl) { msgEl.className = 'validation-msg invalid'; msgEl.textContent = '⚠ Image obligatoire'; }
                return false;
            }
            
            const file = input.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            
            if (!validTypes.includes(file.type)) {
                zone.classList.remove('valid');
                zone.classList.add('invalid');
                if (msgEl) { msgEl.className = 'validation-msg invalid'; msgEl.textContent = '⚠ Format invalide (JPG, PNG, GIF, WEBP)'; }
                return false;
            }
            
            if (file.size > maxSize) {
                zone.classList.remove('valid');
                zone.classList.add('invalid');
                if (msgEl) { msgEl.className = 'validation-msg invalid'; msgEl.textContent = '⚠ Fichier trop volumineux (max 5MB)'; }
                return false;
            }
            
            zone.classList.remove('invalid');
            zone.classList.add('valid');
            if (msgEl) { msgEl.className = 'validation-msg valid'; msgEl.textContent = '✓ Image valide: ' + file.name; }
            return true;
        }
        
        function validateDates() {
            const d1 = document.getElementById('eventDateDebut').value;
            const d2 = document.getElementById('eventDateFin').value;
            const msgEl = document.getElementById('date_fin-msg');
            if (d1 && d2 && new Date(d2) <= new Date(d1)) {
                document.getElementById('eventDateFin').classList.add('invalid');
                if (msgEl) { msgEl.className = 'validation-msg invalid'; msgEl.textContent = '⚠ Date fin doit être > date début'; }
                return false;
            }
            return true;
        }
        
        function validateForm() {
            let ok = true;
            ok = validateField(document.getElementById('eventType')) && ok;
            ok = validateTitre(document.getElementById('eventTitre')) && ok;
            ok = validateField(document.getElementById('eventDescription'), 10) && ok;
            ok = validateField(document.getElementById('eventDateDebut')) && ok;
            ok = validateField(document.getElementById('eventDateFin')) && ok;
            ok = validateDates() && ok;
            ok = validateImage() && ok;
            return ok;
        }
        
        function clearValidations() {
            document.querySelectorAll('.validation-msg').forEach(el => { el.textContent = ''; el.className = 'validation-msg'; });
            document.querySelectorAll('input, select, textarea').forEach(el => el.classList.remove('valid', 'invalid'));
            document.getElementById('imageUploadZone').classList.remove('valid', 'invalid');
        }
        
        function showDeleteConfirm(id, titre) {
            document.getElementById('deleteId').value = id;
            document.getElementById('confirmText').textContent = `Supprimer "${titre}" ?`;
            document.getElementById('confirmOverlay').classList.add('active');
        }
        
        function hideDeleteConfirm() { document.getElementById('confirmOverlay').classList.remove('active'); }
        
        function formatDateForInput(d) { return new Date(d).toISOString().slice(0, 16); }
        function getImagePath(url) { if (!url) return ''; return url.startsWith('uploads/') ? '../../' + url : url; }
        function escapeHtml(t) { if (!t) return ''; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
        
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); hideDeleteConfirm(); } });
        document.getElementById('eventModal').addEventListener('click', e => { if (e.target.id === 'eventModal') closeModal(); });
    </script>
</body>
</html>
