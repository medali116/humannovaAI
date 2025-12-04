<?php
require_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get all events with questions for quiz
$stmt = $db->query("SELECT * FROM evenements ORDER BY date_debut DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get questions and answers for quiz events
$quizData = [];
foreach ($events as $event) {
    if ($event['type'] === 'quiz') {
        $stmt = $db->prepare("
            SELECT q.id as question_id, q.texte_question, q.ordre as q_ordre,
                   r.id as reponse_id, r.texte_reponse, r.est_correcte, r.ordre as r_ordre
            FROM questions q
            LEFT JOIN reponses r ON q.id = r.question_id
            WHERE q.evenement_id = :event_id
            ORDER BY q.ordre, r.ordre
        ");
        $stmt->execute([':event_id' => $event['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $questions = [];
        foreach ($rows as $row) {
            if ($row['question_id']) {
                if (!isset($questions[$row['question_id']])) {
                    $questions[$row['question_id']] = [
                        'id' => $row['question_id'],
                        'texte' => $row['texte_question'],
                        'ordre' => $row['q_ordre'],
                        'reponses' => []
                    ];
                }
                if ($row['reponse_id']) {
                    $questions[$row['question_id']]['reponses'][] = [
                        'id' => $row['reponse_id'],
                        'texte' => $row['texte_reponse'],
                        'est_correcte' => $row['est_correcte']
                    ];
                }
            }
        }
        $quizData[$event['id']] = array_values($questions);
    }
}

// Stats
$stmt = $db->query("SELECT COUNT(*) as total FROM evenements WHERE type = 'normal'");
$totalEvents = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM evenements WHERE type = 'quiz'");
$totalQuiz = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM evenements WHERE date_debut <= NOW() AND date_fin >= NOW()");
$activeEvents = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM evenements WHERE date_fin < NOW()");
$endedEvents = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - Human Nova AI</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .events-page-container { position: relative; z-index: 1; }
        
        .header {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(20px);
            padding: 15px 40px;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        
        .logo { font-size: 24px; font-weight: 900; text-decoration: none; }
        .logo .prism { color: var(--accent-cyan); }
        .logo .flux { color: var(--accent-purple); }
        
        .header-right { display: flex; align-items: center; gap: 15px; }
        .nav-link-btn {
            color: #000;
            text-decoration: none;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 25px;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            transition: all 0.3s;
            font-size: 13px;
        }
        .nav-link-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,255,255,0.3);
        }
        
        .notification-bell {
            position: relative;
            cursor: pointer;
            font-size: 24px;
            padding: 10px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            width: 48px; height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .notification-bell:hover { background: rgba(0,255,255,0.1); }
        .notification-badge {
            position: absolute;
            top: 2px; right: 2px;
            background: var(--accent-orange);
            color: #000;
            font-size: 11px;
            font-weight: 700;
            min-width: 20px; height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .main-section { padding-top: 100px; min-height: 100vh; }
        
        .section-header { text-align: center; padding: 40px 30px 30px; }
        .section-icon { font-size: 50px; margin-bottom: 15px; animation: bounce 2s infinite; }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .section-title {
            font-size: 42px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .section-subtitle { color: var(--text-secondary); font-size: 16px; }
        
        /* Mini Stats */
        .mini-stats {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 25px 0 35px;
            flex-wrap: wrap;
        }
        .mini-stat {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 30px;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            animation: fadeInUp 0.5s ease backwards;
        }
        .mini-stat:nth-child(1) { animation-delay: 0.1s; }
        .mini-stat:nth-child(2) { animation-delay: 0.2s; }
        .mini-stat:nth-child(3) { animation-delay: 0.3s; }
        .mini-stat:nth-child(4) { animation-delay: 0.4s; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .mini-stat:hover {
            border-color: var(--accent-cyan);
            transform: scale(1.05);
        }
        .mini-stat-number {
            font-size: 22px;
            font-weight: 800;
            color: var(--accent-cyan);
        }
        .mini-stat-label {
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .mini-stat.quiz .mini-stat-number { color: var(--accent-purple); }
        .mini-stat.encours .mini-stat-number { color: var(--accent-green); }
        .mini-stat.termine .mini-stat-number { color: var(--accent-red); }
        
        /* Filter Buttons */
        .filter-section {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 40px;
            flex-wrap: wrap;
            padding: 0 20px;
        }
        .filter-btn {
            padding: 12px 24px;
            border-radius: 25px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.05);
            color: var(--text-secondary);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
        }
        .filter-btn:hover, .filter-btn.active {
            background: var(--accent-cyan);
            color: #000;
            border-color: var(--accent-cyan);
            transform: translateY(-2px);
        }
        .filter-btn.events-filter:hover, .filter-btn.events-filter.active {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
            color: #fff;
        }
        .filter-btn.quiz-filter:hover, .filter-btn.quiz-filter.active {
            background: var(--accent-purple);
            border-color: var(--accent-purple);
            color: #fff;
        }
        .filter-btn.encours-filter:hover, .filter-btn.encours-filter.active {
            background: var(--accent-green);
            border-color: var(--accent-green);
            color: #000;
        }
        .filter-btn.termine-filter:hover, .filter-btn.termine-filter.active {
            background: var(--accent-red);
            border-color: var(--accent-red);
            color: #fff;
        }
        
        /* Search */
        .search-section {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            padding: 0 20px;
        }
        .search-bar {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 50px;
            padding: 14px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 400px;
            max-width: 100%;
            transition: all 0.3s;
        }
        .search-bar:focus-within {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 25px rgba(0,255,255,0.2);
        }
        .search-input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
        }
        .search-input::placeholder { color: var(--text-dim); }
        
        /* Events Grid */
        .events-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px 60px;
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 30px;
        }
        .event-card {
            background: linear-gradient(145deg, var(--carbon-medium), var(--carbon-dark));
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease backwards;
        }
        .event-card:hover {
            transform: translateY(-12px);
            border-color: var(--accent-cyan);
            box-shadow: 0 25px 60px rgba(0,255,255,0.2);
        }
        .card-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .event-card:hover .card-image img { transform: scale(1.1); }
        .card-badges {
            position: absolute;
            top: 15px; left: 15px; right: 15px;
            display: flex;
            justify-content: space-between;
        }
        .status-badge, .type-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-active { background: rgba(0,255,136,0.9); color: #000; }
        .status-upcoming { background: rgba(255,149,0,0.9); color: #000; }
        .status-ended { background: rgba(255,51,51,0.9); color: #fff; }
        .type-event { background: rgba(0,255,255,0.9); color: #000; }
        .type-quiz { background: rgba(153,69,255,0.9); color: #fff; }
        .card-content { padding: 25px; }
        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        .card-date {
            color: var(--accent-cyan);
            font-size: 13px;
            margin-bottom: 15px;
        }
        .card-description {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .card-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: #000;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .card-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,255,255,0.4);
        }
        .card-btn:disabled {
            background: var(--metal-dark);
            color: var(--text-dim);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .quiz-btn {
            background: linear-gradient(135deg, var(--accent-purple), #7c3aed);
            color: #fff;
        }
        .quiz-btn:hover { box-shadow: 0 10px 30px rgba(153,69,255,0.4); }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            display: none;
        }
        .empty-icon { font-size: 80px; margin-bottom: 20px; opacity: 0.5; }
        .empty-state h3 { color: var(--text-secondary); font-size: 24px; margin-bottom: 10px; }
        .empty-state p { color: var(--text-dim); }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 2000;
            padding: 30px;
            overflow-y: auto;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: var(--carbon-medium);
            border: 2px solid var(--accent-cyan);
            border-radius: 20px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalIn 0.4s ease;
        }
        .modal-box.quiz-modal { border-color: var(--accent-purple); }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.9) translateY(-30px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            padding: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--accent-cyan);
        }
        .modal-box.quiz-modal .modal-title { color: var(--accent-purple); }
        .modal-close {
            width: 44px; height: 44px;
            background: rgba(255,51,51,0.15);
            border: 2px solid var(--accent-red);
            border-radius: 50%;
            color: var(--accent-red);
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-close:hover {
            background: var(--accent-red);
            color: #fff;
            transform: rotate(90deg);
        }
        .modal-body { padding: 25px; }
        .event-info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .event-info-label { color: var(--text-secondary); }
        .event-info-value { color: var(--text-primary); font-weight: 600; }
        .event-closed-notice {
            background: rgba(255,51,51,0.1);
            border: 1px solid var(--accent-red);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .event-closed-notice p { color: var(--accent-red); margin: 5px 0; }
        
        /* Form */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            color: var(--accent-cyan);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 12px;
            text-transform: uppercase;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 14px;
            background: var(--carbon-dark);
            border: 2px solid var(--metal-dark);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--accent-cyan);
            outline: none;
        }
        .form-group input.valid, .form-group textarea.valid { border-color: var(--accent-green); }
        .form-group input.invalid, .form-group textarea.invalid { border-color: var(--accent-red); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .validation-msg { font-size: 12px; margin-top: 5px; }
        .validation-msg.valid { color: var(--accent-green); }
        .validation-msg.invalid { color: var(--accent-red); }
        .char-counter { font-size: 11px; color: var(--text-dim); text-align: right; margin-top: 5px; }
        .char-counter.valid { color: var(--accent-green); }
        .char-counter.warning { color: var(--accent-orange); }
        .file-upload {
            border: 2px dashed var(--metal-light);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload:hover { border-color: var(--accent-cyan); }
        .file-upload.has-file { border-color: var(--accent-green); background: rgba(0,255,136,0.05); }
        .form-actions { display: flex; gap: 15px; margin-top: 25px; }
        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-secondary { background: var(--metal-dark); color: var(--text-primary); }
        .btn-primary { background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue)); color: #000; }
        .btn-primary:hover { box-shadow: 0 10px 30px rgba(0,255,255,0.4); }
        .btn-purple { background: linear-gradient(135deg, var(--accent-purple), #7c3aed); color: #fff; }
        .btn-purple:hover { box-shadow: 0 10px 30px rgba(153,69,255,0.4); }
        
        /* Quiz Styles */
        .quiz-question {
            background: rgba(153,69,255,0.1);
            border: 1px solid rgba(153,69,255,0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .quiz-question-number {
            color: var(--accent-purple);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .quiz-question-text {
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .quiz-options { display: flex; flex-direction: column; gap: 12px; }
        .quiz-option {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: var(--carbon-dark);
            border: 2px solid var(--metal-dark);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .quiz-option:hover { border-color: var(--accent-purple); }
        .quiz-option.selected {
            border-color: var(--accent-purple);
            background: rgba(153,69,255,0.15);
        }
        .quiz-option input { display: none; }
        .quiz-option-radio {
            width: 24px; height: 24px;
            border: 2px solid var(--metal-light);
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .quiz-option.selected .quiz-option-radio {
            border-color: var(--accent-purple);
            background: var(--accent-purple);
        }
        .quiz-option.selected .quiz-option-radio::after {
            content: '✓';
            color: #fff;
            font-weight: 700;
        }
        .quiz-option-text { color: var(--text-primary); font-size: 15px; }
        
        /* Quiz Results */
        .quiz-results {
            text-align: center;
            padding: 40px 20px;
        }
        .quiz-results-icon { font-size: 80px; margin-bottom: 20px; }
        .quiz-results-title { font-size: 28px; font-weight: 800; margin-bottom: 10px; }
        .quiz-results-title.success { color: var(--accent-green); }
        .quiz-results-title.warning { color: var(--accent-orange); }
        .quiz-results-title.error { color: var(--accent-red); }
        .quiz-results-score {
            font-size: 48px;
            font-weight: 900;
            margin: 20px 0;
        }
        .quiz-results-details {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
        }
        .quiz-result-item {
            text-align: center;
        }
        .quiz-result-value {
            font-size: 36px;
            font-weight: 800;
        }
        .quiz-result-value.correct { color: var(--accent-green); }
        .quiz-result-value.wrong { color: var(--accent-red); }
        .quiz-result-label {
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
        }
        
        /* Notifications */
        .notifications-panel {
            position: fixed;
            top: 80px; right: 20px;
            width: 400px;
            max-height: 500px;
            background: var(--carbon-medium);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            z-index: 1500;
            display: none;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }
        .notifications-panel.active { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .notif-header h3 { color: var(--accent-cyan); font-size: 16px; }
        .notif-search { padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .notif-search input {
            width: 100%;
            padding: 12px;
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 8px;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .notif-search button {
            width: 100%;
            padding: 12px;
            background: var(--accent-cyan);
            border: none;
            border-radius: 8px;
            color: #000;
            font-weight: 700;
            cursor: pointer;
        }
        .notif-list { max-height: 300px; overflow-y: auto; padding: 15px; }
        .notification-item {
            background: var(--carbon-dark);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--accent-orange);
        }
        .notification-item.approuve { border-left-color: var(--accent-green); }
        .notification-item.rejete { border-left-color: var(--accent-red); }
        .notification-icon { font-size: 24px; margin-bottom: 8px; }
        .notification-event { font-weight: 600; color: var(--text-primary); margin-bottom: 5px; }
        .notification-message { font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; }
        .notification-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 700;
        }
        .notification-status.en_attente { background: rgba(255,149,0,0.2); color: var(--accent-orange); }
        .notification-status.approuve { background: rgba(0,255,136,0.2); color: var(--accent-green); }
        .notification-status.rejete { background: rgba(255,51,51,0.2); color: var(--accent-red); }
        .notification-date { font-size: 11px; color: var(--text-dim); margin-top: 8px; }
        
        /* Success Overlay */
        .success-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 3000;
            align-items: center;
            justify-content: center;
        }
        .success-overlay.active { display: flex; }
        .success-box {
            background: var(--carbon-medium);
            border: 2px solid var(--accent-green);
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            animation: bounceIn 0.6s ease;
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-icon { font-size: 80px; margin-bottom: 20px; }
        .success-title { font-size: 28px; color: var(--accent-green); margin-bottom: 10px; }
        .success-message { color: var(--text-secondary); }
        
        @media (max-width: 768px) {
            .header { padding: 15px 20px; }
            .events-grid { grid-template-columns: 1fr; }
            .notifications-panel { width: calc(100% - 40px); }
            .form-row { grid-template-columns: 1fr; }
            .mini-stats { gap: 10px; }
            .mini-stat { padding: 8px 15px; }
            .filter-section { gap: 8px; }
            .filter-btn { padding: 10px 16px; font-size: 12px; }
            .quiz-results-details { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>
    <div class="events-page-container">
        <header class="header">
            <a href="../../index.php" class="logo">
                <span class="prism">HUMAN</span><span class="flux">NOVA AI</span>
            </a>
            <div class="header-right">
                <a href="../../views/admin/manage-events.php" class="nav-link-btn">ÉVÉNEMENTS</a>
                <div class="notification-bell" onclick="toggleNotifications()">
                    🔔
                    <span class="notification-badge">!</span>
                </div>
            </div>
        </header>
        
        <main class="main-section">
            <div class="section-header">
                <div class="section-icon">🎪</div>
                <h1 class="section-title">NOS ÉVÉNEMENTS</h1>
                <p class="section-subtitle">Découvrez et participez à nos événements exclusifs</p>
            </div>
            
            <div class="mini-stats">
                <div class="mini-stat">
                    <span class="mini-stat-number"><?php echo $totalEvents; ?></span>
                    <span class="mini-stat-label">Événements</span>
                </div>
                <div class="mini-stat quiz">
                    <span class="mini-stat-number"><?php echo $totalQuiz; ?></span>
                    <span class="mini-stat-label">Quiz</span>
                </div>
                <div class="mini-stat encours">
                    <span class="mini-stat-number"><?php echo $activeEvents; ?></span>
                    <span class="mini-stat-label">En cours</span>
                </div>
                <div class="mini-stat termine">
                    <span class="mini-stat-number"><?php echo $endedEvents; ?></span>
                    <span class="mini-stat-label">Terminé</span>
                </div>
            </div>
            
            <div class="filter-section">
                <button class="filter-btn active" data-filter="all">Tous</button>
                <button class="filter-btn events-filter" data-filter="normal">📅 Événements</button>
                <button class="filter-btn quiz-filter" data-filter="quiz">🎯 Quiz</button>
                <button class="filter-btn encours-filter" data-filter="encours">✅ En cours</button>
                <button class="filter-btn termine-filter" data-filter="termine">🔒 Terminé</button>
            </div>
            
            <div class="search-section">
                <div class="search-bar">
                    <span>🔍</span>
                    <input type="text" class="search-input" id="searchInput" placeholder="Rechercher un événement...">
                </div>
            </div>
            
            <div class="events-container">
                <div class="events-grid" id="eventsGrid">
                    <?php foreach($events as $index => $event): 
                        $now = new DateTime();
                        $start = new DateTime($event['date_debut']);
                        $end = new DateTime($event['date_fin']);
                        
                        if ($now < $start) {
                            $status = ['class' => 'status-upcoming', 'label' => '⏳ BIENTÔT', 'canParticipate' => false, 'statusKey' => 'upcoming'];
                        } elseif ($now > $end) {
                            $status = ['class' => 'status-ended', 'label' => '🔒 TERMINÉ', 'canParticipate' => false, 'statusKey' => 'ended'];
                        } else {
                            $status = ['class' => 'status-active', 'label' => '✅ EN COURS', 'canParticipate' => true, 'statusKey' => 'active'];
                        }
                        
                        $typeClass = $event['type'] === 'quiz' ? 'type-quiz' : 'type-event';
                        $typeBadge = $event['type'] === 'quiz' ? '🎯 QUIZ' : '📅 ÉVÉNEMENT';
                        $btnClass = $event['type'] === 'quiz' ? 'card-btn quiz-btn' : 'card-btn';
                        
                        $imgUrl = 'https://via.placeholder.com/400x200/1a1a1a/00ffff?text=Event';
                        if (!empty($event['image_url'])) {
                            $imgUrl = strpos($event['image_url'], 'http') === 0 ? $event['image_url'] : '../../' . $event['image_url'];
                        }
                    ?>
                    <div class="event-card" data-type="<?php echo $event['type']; ?>" data-status="<?php echo $status['statusKey']; ?>" style="animation-delay: <?php echo $index * 0.1; ?>s">
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($event['titre']); ?>" onerror="this.src='https://via.placeholder.com/400x200/1a1a1a/00ffff?text=Event'">
                            <div class="card-badges">
                                <span class="status-badge <?php echo $status['class']; ?>"><?php echo $status['label']; ?></span>
                                <span class="type-badge <?php echo $typeClass; ?>"><?php echo $typeBadge; ?></span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($event['titre']); ?></h3>
                            <div class="card-date">📅 <?php echo date('d M Y', strtotime($event['date_debut'])); ?> - <?php echo date('d M Y', strtotime($event['date_fin'])); ?></div>
                            <p class="card-description"><?php echo htmlspecialchars(mb_substr($event['description'], 0, 100)); ?><?php echo strlen($event['description']) > 100 ? '...' : ''; ?></p>
                            <button class="<?php echo $btnClass; ?>" onclick="showDetails(<?php echo $event['id']; ?>)" <?php echo !$status['canParticipate'] ? 'disabled' : ''; ?>>
                                <?php echo $status['canParticipate'] ? ($event['type'] === 'quiz' ? '🎯 Participer au Quiz' : '👁️ Voir les détails') : $status['label']; ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="empty-state" id="emptyState">
                    <div class="empty-icon">🎪</div>
                    <h3>Aucun événement trouvé</h3>
                    <p>Essayez de modifier vos filtres</p>
                </div>
            </div>
        </main>
        
        <div class="notifications-panel" id="notificationsPanel">
            <div class="notif-header"><h3>🔔 Mes Participations</h3></div>
            <div class="notif-search">
                <input type="email" id="checkEmail" placeholder="Entrez votre email...">
                <button onclick="checkMyStatus()">Vérifier mes participations</button>
            </div>
            <div class="notif-list" id="notificationsList">
                <div class="empty-state" style="display: block; padding: 30px;">
                    <div class="empty-icon">📧</div>
                    <p>Entrez votre email pour voir vos participations</p>
                </div>
            </div>
        </div>
        
        <div class="modal-overlay" id="eventModal">
            <div class="modal-box" id="modalBox">
                <div class="modal-header">
                    <h3 class="modal-title" id="modalTitle">Détails</h3>
                    <button class="modal-close" onclick="closeModal()">✕</button>
                </div>
                <div class="modal-body" id="modalContent"></div>
            </div>
        </div>
        
        <div class="success-overlay" id="successOverlay">
            <div class="success-box">
                <div class="success-icon">✅</div>
                <h2 class="success-title">Participation envoyée !</h2>
                <p class="success-message">Votre demande a été soumise avec succès</p>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '../../api/participation.php';
        const eventsData = <?php echo json_encode($events); ?>;
        const quizData = <?php echo json_encode($quizData); ?>;
        let currentFilter = 'all';
        let currentQuizAnswers = {};

        function getEventStatus(event) {
            const now = new Date();
            const start = new Date(event.date_debut);
            const end = new Date(event.date_fin);
            if (now < start) return { status: 'upcoming', label: '⏳ BIENTÔT', canParticipate: false };
            if (now > end) return { status: 'ended', label: '🔒 TERMINÉ', canParticipate: false };
            return { status: 'active', label: '✅ EN COURS', canParticipate: true };
        }

        function filterEvents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.event-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const type = card.dataset.type;
                const status = card.dataset.status;
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const desc = card.querySelector('.card-description').textContent.toLowerCase();
                
                const matchesSearch = title.includes(searchTerm) || desc.includes(searchTerm);
                let matchesFilter = false;

                if (currentFilter === 'all') {
                    matchesFilter = status !== 'ended';
                } else if (currentFilter === 'normal') {
                    matchesFilter = type === 'normal' && status !== 'ended';
                } else if (currentFilter === 'quiz') {
                    matchesFilter = type === 'quiz' && status !== 'ended';
                } else if (currentFilter === 'encours') {
                    matchesFilter = status === 'active';
                } else if (currentFilter === 'termine') {
                    matchesFilter = status === 'ended';
                }

                if (matchesSearch && matchesFilter) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('emptyState').style.display = visibleCount === 0 ? 'block' : 'none';
        }

        function showDetails(id) {
            const e = eventsData.find(ev => ev.id == id);
            if (!e) return;

            const status = getEventStatus(e);
            const isQuiz = e.type === 'quiz';
            let imgUrl = e.image_url ? (e.image_url.startsWith('http') ? e.image_url : '../../' + e.image_url) : 'https://via.placeholder.com/400x200/1a1a1a/00ffff?text=Event';

            document.getElementById('modalBox').className = isQuiz ? 'modal-box quiz-modal' : 'modal-box';

            let content = '<img src="' + imgUrl + '" style="width:100%; border-radius:12px; margin-bottom:20px;" onerror="this.src=\'https://via.placeholder.com/400x200/1a1a1a/00ffff?text=Event\'">';
            content += '<p style="color:var(--text-secondary); line-height:1.8; margin-bottom:20px;">' + esc(e.description) + '</p>';
            content += '<div class="event-info-row"><span class="event-info-label">Type</span><span class="event-info-value">' + (isQuiz ? '🎯 Quiz' : '📅 Événement') + '</span></div>';
            content += '<div class="event-info-row"><span class="event-info-label">Début</span><span class="event-info-value">' + formatDate(e.date_debut) + '</span></div>';
            content += '<div class="event-info-row"><span class="event-info-label">Fin</span><span class="event-info-value">' + formatDate(e.date_fin) + '</span></div>';
            content += '<div class="event-info-row"><span class="event-info-label">Statut</span><span class="event-info-value">' + status.label + '</span></div>';

            if (!status.canParticipate) {
                if (status.status === 'upcoming') {
                    content += '<div class="event-closed-notice" style="border-color: var(--accent-orange);"><p style="color: var(--accent-orange);">⏳ Cet événement n\'a pas encore commencé</p></div>';
                } else {
                    content += '<div class="event-closed-notice"><p>🔒 Cet événement est terminé</p></div>';
                }
            } else if (isQuiz) {
                // Quiz Form
                const questions = quizData[e.id] || [];
                if (questions.length === 0) {
                    content += '<div class="event-closed-notice" style="border-color: var(--accent-orange);"><p style="color: var(--accent-orange);">⚠️ Ce quiz n\'a pas encore de questions</p></div>';
                } else {
                    content += '<div id="quizContainer">';
                    content += '<h3 style="color: var(--accent-purple); margin: 30px 0 20px;">📝 Informations personnelles</h3>';
                    content += '<div class="form-row">';
                    content += '<div class="form-group"><label>Nom *</label><input type="text" id="quizNom" placeholder="Votre nom"><div class="validation-msg" id="quiz-nom-msg"></div></div>';
                    content += '<div class="form-group"><label>Prénom *</label><input type="text" id="quizPrenom" placeholder="Votre prénom"><div class="validation-msg" id="quiz-prenom-msg"></div></div>';
                    content += '</div>';
                    content += '<div class="form-group"><label>Email *</label><input type="email" id="quizEmail" placeholder="votre@email.com"><div class="validation-msg" id="quiz-email-msg"></div></div>';
                    
                    content += '<h3 style="color: var(--accent-purple); margin: 30px 0 20px;">🎯 Questions du Quiz (' + questions.length + ')</h3>';
                    
                    questions.forEach((q, qIndex) => {
                        content += '<div class="quiz-question">';
                        content += '<div class="quiz-question-number">Question ' + (qIndex + 1) + ' / ' + questions.length + '</div>';
                        content += '<div class="quiz-question-text">' + esc(q.texte) + '</div>';
                        content += '<div class="quiz-options">';
                        
                        q.reponses.forEach((r, rIndex) => {
                            content += '<label class="quiz-option" onclick="selectOption(this, ' + q.id + ', ' + r.id + ')">';
                            content += '<input type="radio" name="question_' + q.id + '" value="' + r.id + '">';
                            content += '<div class="quiz-option-radio"></div>';
                            content += '<span class="quiz-option-text">' + esc(r.texte) + '</span>';
                            content += '</label>';
                        });
                        
                        content += '</div></div>';
                    });
                    
                    content += '<div class="form-actions"><button type="button" class="btn btn-secondary" onclick="closeModal()">← Retour</button><button type="button" class="btn btn-purple" onclick="submitQuiz(' + e.id + ', ' + questions.length + ')">✓ Valider le Quiz</button></div>';
                    content += '</div>';
                    content += '<div id="quizResults" style="display:none;"></div>';
                }
            } else {
                // Event participation form
                content += '<form onsubmit="submitParticipation(event, ' + e.id + ')" style="margin-top: 25px;">';
                content += '<div class="form-row">';
                content += '<div class="form-group"><label>Nom *</label><input type="text" id="partNom" placeholder="Votre nom" oninput="validateName(this, \'nom-msg\')"><div class="validation-msg" id="nom-msg"></div></div>';
                content += '<div class="form-group"><label>Prénom *</label><input type="text" id="partPrenom" placeholder="Votre prénom" oninput="validateName(this, \'prenom-msg\')"><div class="validation-msg" id="prenom-msg"></div></div>';
                content += '</div>';
                content += '<div class="form-group"><label>Email *</label><input type="email" id="partEmail" placeholder="votre@email.com" oninput="validateEmail(this)"><div class="validation-msg" id="email-msg"></div></div>';
                content += '<div class="form-group"><label>Commentaire * (min 10 caractères)</label><textarea id="partComment" rows="3" placeholder="Pourquoi souhaitez-vous participer?" oninput="validateComment(this)"></textarea><div class="char-counter" id="commentCharCount">0 / 10 caractères minimum</div><div class="validation-msg" id="comment-msg"></div></div>';
                content += '<div class="form-group"><label>Fichier (optionnel)</label><div class="file-upload" id="fileUploadZone" onclick="document.getElementById(\'partFile\').click()"><input type="file" id="partFile" style="display:none" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" onchange="showFileName(this)"><span>📎 Cliquez pour ajouter un fichier</span><br><small id="fileText" style="color:var(--text-dim)">PDF, DOC, JPG, PNG, ZIP (max 5MB)</small></div></div>';
                content += '<div class="form-actions"><button type="button" class="btn btn-secondary" onclick="closeModal()">← Retour</button><button type="submit" class="btn btn-primary">🚀 Envoyer</button></div>';
                content += '</form>';
            }

            document.getElementById('modalTitle').innerHTML = (isQuiz ? '🎯 ' : '📋 ') + esc(e.titre);
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('eventModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            currentQuizAnswers = {};
        }

        function selectOption(element, questionId, answerId) {
            const parent = element.closest('.quiz-question');
            parent.querySelectorAll('.quiz-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            element.querySelector('input').checked = true;
            currentQuizAnswers[questionId] = answerId;
        }

        function submitQuiz(eventId, totalQuestions) {
            const nom = document.getElementById('quizNom').value.trim();
            const prenom = document.getElementById('quizPrenom').value.trim();
            const email = document.getElementById('quizEmail').value.trim();
            
            // Validate
            let valid = true;
            if (nom.length < 2) {
                document.getElementById('quiz-nom-msg').innerHTML = '<span class="validation-msg invalid">⚠ Min 2 caractères</span>';
                valid = false;
            } else {
                document.getElementById('quiz-nom-msg').innerHTML = '<span class="validation-msg valid">✓ Valide</span>';
            }
            
            if (prenom.length < 2) {
                document.getElementById('quiz-prenom-msg').innerHTML = '<span class="validation-msg invalid">⚠ Min 2 caractères</span>';
                valid = false;
            } else {
                document.getElementById('quiz-prenom-msg').innerHTML = '<span class="validation-msg valid">✓ Valide</span>';
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                document.getElementById('quiz-email-msg').innerHTML = '<span class="validation-msg invalid">⚠ Email invalide</span>';
                valid = false;
            } else {
                document.getElementById('quiz-email-msg').innerHTML = '<span class="validation-msg valid">✓ Email valide</span>';
            }
            
            if (Object.keys(currentQuizAnswers).length < totalQuestions) {
                alert('Veuillez répondre à toutes les questions !');
                return;
            }
            
            if (!valid) return;
            
            // Calculate score
            const questions = quizData[eventId];
            let correct = 0;
            let wrong = 0;
            
            questions.forEach(q => {
                const selectedAnswer = currentQuizAnswers[q.id];
                const correctAnswer = q.reponses.find(r => r.est_correcte == 1);
                if (correctAnswer && selectedAnswer == correctAnswer.id) {
                    correct++;
                } else {
                    wrong++;
                }
            });
            
            const percentage = Math.round((correct / totalQuestions) * 100);
            let resultClass = 'error';
            let resultIcon = '😞';
            let resultText = 'Continuez à apprendre !';
            
            if (percentage >= 80) {
                resultClass = 'success';
                resultIcon = '🎉';
                resultText = 'Excellent travail !';
            } else if (percentage >= 50) {
                resultClass = 'warning';
                resultIcon = '👍';
                resultText = 'Pas mal !';
            }
            
            // Show results
            document.getElementById('quizContainer').style.display = 'none';
            document.getElementById('quizResults').style.display = 'block';
            document.getElementById('quizResults').innerHTML = `
                <div class="quiz-results">
                    <div class="quiz-results-icon">${resultIcon}</div>
                    <div class="quiz-results-title ${resultClass}">${resultText}</div>
                    <div class="quiz-results-score" style="color: var(--accent-${resultClass === 'success' ? 'green' : resultClass === 'warning' ? 'orange' : 'red'})">${percentage}%</div>
                    <div class="quiz-results-details">
                        <div class="quiz-result-item">
                            <div class="quiz-result-value correct">${correct}</div>
                            <div class="quiz-result-label">✓ Correctes</div>
                        </div>
                        <div class="quiz-result-item">
                            <div class="quiz-result-value wrong">${wrong}</div>
                            <div class="quiz-result-label">✗ Incorrectes</div>
                        </div>
                    </div>
                    <p style="color: var(--text-secondary); margin-top: 20px;">Participant: ${esc(prenom)} ${esc(nom)} (${esc(email)})</p>
                    <div class="form-actions" style="margin-top: 30px;">
                        <button class="btn btn-purple" onclick="closeModal()">Fermer</button>
                    </div>
                </div>
            `;
            
            // Save quiz result to database
            fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'soumettreQuiz',
                    evenement_id: eventId,
                    nom: nom,
                    prenom: prenom,
                    email: email,
                    reponses: currentQuizAnswers
                })
            });
        }

        function closeModal() {
            document.getElementById('eventModal').classList.remove('active');
            document.body.style.overflow = '';
            currentQuizAnswers = {};
        }

        function validateName(input, msgId) {
            const val = input.value.trim();
            const msgEl = document.getElementById(msgId);
            input.classList.remove('valid', 'invalid');
            if (val.length < 2) { 
                input.classList.add('invalid'); 
                msgEl.className = 'validation-msg invalid'; 
                msgEl.textContent = '⚠ Min 2 caractères'; 
                return false; 
            }
            input.classList.add('valid'); 
            msgEl.className = 'validation-msg valid'; 
            msgEl.textContent = '✓ Valide'; 
            return true;
        }

        function validateEmail(input) {
            const val = input.value.trim();
            const msgEl = document.getElementById('email-msg');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            input.classList.remove('valid', 'invalid');
            if (!emailRegex.test(val)) { input.classList.add('invalid'); msgEl.className = 'validation-msg invalid'; msgEl.textContent = '⚠ Email invalide'; return false; }
            input.classList.add('valid'); msgEl.className = 'validation-msg valid'; msgEl.textContent = '✓ Email valide'; return true;
        }

        function validateComment(input) {
            const val = input.value.trim();
            const msgEl = document.getElementById('comment-msg');
            const counter = document.getElementById('commentCharCount');
            counter.textContent = val.length + ' / 10 caractères minimum';
            counter.className = 'char-counter' + (val.length >= 10 ? ' valid' : val.length > 0 ? ' warning' : '');
            input.classList.remove('valid', 'invalid');
            if (val.length < 10) { input.classList.add('invalid'); msgEl.className = 'validation-msg invalid'; msgEl.textContent = '⚠ Min 10 caractères'; return false; }
            input.classList.add('valid'); msgEl.className = 'validation-msg valid'; msgEl.textContent = '✓ ' + val.length + ' caractères'; return true;
        }

        function showFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileText').textContent = '📄 ' + input.files[0].name;
                document.getElementById('fileUploadZone').classList.add('has-file');
            }
        }

        async function submitParticipation(event, eventId) {
            event.preventDefault();
            
            const nom = document.getElementById('partNom');
            const prenom = document.getElementById('partPrenom');
            const email = document.getElementById('partEmail');
            const comment = document.getElementById('partComment');
            
            if (!validateName(nom, 'nom-msg') || !validateName(prenom, 'prenom-msg') || !validateEmail(email) || !validateComment(comment)) return;

            const formData = new FormData();
            formData.append('action', 'soumettreParticipation');
            formData.append('evenement_id', eventId);
            formData.append('nom', nom.value.trim());
            formData.append('prenom', prenom.value.trim());
            formData.append('email', email.value.trim());
            formData.append('commentaire', comment.value.trim());

            const fileInput = document.getElementById('partFile');
            if (fileInput.files[0]) formData.append('fichier', fileInput.files[0]);

            try {
                const response = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    closeModal();
                    document.getElementById('successOverlay').classList.add('active');
                    setTimeout(() => document.getElementById('successOverlay').classList.remove('active'), 3000);
                } else {
                    alert(data.message || 'Erreur');
                }
            } catch (error) { alert('Erreur de connexion'); }
        }

        function toggleNotifications() {
            document.getElementById('notificationsPanel').classList.toggle('active');
        }

        async function checkMyStatus() {
            const email = document.getElementById('checkEmail').value.trim();
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { alert('Email invalide'); return; }

            try {
                const response = await fetch(API_URL + '?action=getByEmail&email=' + encodeURIComponent(email));
                const data = await response.json();
                const list = document.getElementById('notificationsList');

                if (!data.success || !data.data || data.data.length === 0) {
                    list.innerHTML = '<div class="empty-state" style="display:block;padding:30px;"><div class="empty-icon">📭</div><p>Aucune participation trouvée</p></div>';
                    return;
                }

                let html = '';
                data.data.forEach(p => {
                    let icon = '⏳', msg = 'En cours d\'examen';
                    if (p.statut === 'approuve') { icon = '✅'; msg = 'Approuvée'; }
                    else if (p.statut === 'rejete') { icon = '❌'; msg = 'Refusée'; }
                    html += '<div class="notification-item ' + p.statut + '"><div class="notification-icon">' + icon + '</div><div class="notification-event">' + esc(p.evenement_titre) + '</div><div class="notification-message">' + msg + '</div><span class="notification-status ' + p.statut + '">' + p.statut.replace('_', ' ').toUpperCase() + '</span></div>';
                });
                list.innerHTML = html;
            } catch (error) { alert('Erreur'); }
        }

        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter;
                filterEvents();
            });
        });

        document.getElementById('searchInput').addEventListener('input', filterEvents);

        function esc(str) { if (!str) return ''; const div = document.createElement('div'); div.textContent = str; return div.innerHTML; }
        function formatDate(d) { return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }); }

        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
        document.getElementById('eventModal').addEventListener('click', e => { if (e.target.id === 'eventModal') closeModal(); });

        filterEvents();
    </script>
</body>
</html>
