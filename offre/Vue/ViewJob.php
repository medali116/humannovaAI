<?php
require_once '../Controller/JobController.php';

$jobController = new JobController();

if (!isset($_GET['id'])) {
    header('Location: ListJobs.php');
    exit;
}

$id = $_GET['id'];
$job = $jobController->getJobById($id);

// If job not found in DB, allow a small inline fallback for sample/example IDs (no extra files)
if (!$job || !is_array($job)) {
    // treat ids like s1, s2, s3 as sample examples and render static content
    if (preg_match('/^s(\d+)$/i', (string)$id, $m)) {
        $sampleIndex = (int)$m[1];
        $samples = [
            1 => [
                'id' => 's1',
                'title' => 'Développeur PHP Fullstack (ex.)',
                'company' => 'HUMANnova Solutions',
                'salary' => '2000 - 3500 TND',
                'description' => 'Exemple: développeur PHP expérimenté, maîtrise MVC, PDO et bonnes pratiques. Participation à toutes les phases du développement.',
                'location' => 'Tunis',
                'date_posted' => date('Y-m-d'),
                'category' => 'Informatique',
                'contract_type' => 'CDI',
                'logo' => 'https://via.placeholder.com/120x120?text=HN'
            ],
            2 => [
                'id' => 's2',
                'title' => 'Data Scientist Junior (ex.)',
                'company' => 'HUMANnova AI Lab',
                'salary' => '1800 - 2600 TND',
                'description' => 'Exemple: Stage/Junior en analyse de données et apprentissage automatique. Python, pandas, scikit-learn souhaités.',
                'location' => 'Sfax',
                'date_posted' => date('Y-m-d'),
                'category' => 'Data',
                'contract_type' => 'CDD',
                'logo' => 'https://via.placeholder.com/120x120?text=AI'
            ],
            3 => [
                'id' => 's3',
                'title' => 'Responsable Marketing Digital (ex.)',
                'company' => 'HUMANnova Marketing',
                'salary' => '1500 - 2800 TND',
                'description' => 'Exemple: Piloter les campagnes digitales, SEO/SEA, social media et analytics.',
                'location' => 'Remote',
                'date_posted' => date('Y-m-d'),
                'category' => 'Marketing',
                'contract_type' => 'CDI',
                'logo' => 'https://via.placeholder.com/120x120?text=MM'
            ]
        ];

        if (isset($samples[$sampleIndex])) {
            $job = $samples[$sampleIndex];
        }
    }
}

// If still no job, redirect back to list (preserve role)
if (!$job || !is_array($job)) {
    $roleQuery = (isset($_GET['role']) && $_GET['role'] === 'recruiter') ? '?role=recruiter' : '';
    header('Location: ListJobs.php' . $roleQuery);
    exit;
}

$title = $job['title'] ?? 'Offre';
require_once __DIR__ . '/layout/header.php';
?>

<div class="site-top" style="max-width:1180px; margin-bottom:18px">
    <div class="container">
        <div style="display:flex;gap:18px;align-items:flex-start">
            <div style="width:120px;height:120px;background:#fff;border-radius:8px;overflow:hidden;flex:0 0 120px">
                <?php if (!empty($job['logo'])): ?>
                    <img src="<?php echo htmlspecialchars($job['logo']); ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                    <img src="https://via.placeholder.com/120x120?text=Logo" style="width:100%;height:100%;object-fit:cover">
                <?php endif; ?>
            </div>
            <div>
                <h2 style="margin:0 0 6px"><?php echo htmlspecialchars($job['title']); ?></h2>
                <div style="color:var(--accent); font-weight:700"><?php echo htmlspecialchars($job['company']); ?></div>
                <div style="margin-top:10px;color:var(--muted)"><?php echo nl2br(htmlspecialchars($job['description'])); ?></div>
                <div style="margin-top:12px;color:var(--muted)">
                    <strong>Lieu:</strong> <?php echo htmlspecialchars($job['location']); ?> &nbsp; • &nbsp; <strong>Date:</strong> <?php echo htmlspecialchars($job['date_posted']); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
