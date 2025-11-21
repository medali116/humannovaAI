<?php
require_once '../Controller/JobController.php';

// Créer une instance de JobController
$jobController = new JobController();

// Récupérer tous les jobs
$jobs = $jobController->getJobs();

$title = 'Liste des offres';
// detect role: recruiter or candidate (default candidate)
$role = isset($_GET['role']) && $_GET['role'] === 'recruiter' ? 'recruiter' : 'candidate';
require_once __DIR__ . '/layout/header.php';

// Append built-in non-DB example jobs (display-only) so examples show even when DB has rows
$samples = [
    [
        'id' => 's1',
        'title' => 'Développeur PHP Fullstack (ex.)',
        'company' => 'HUMANnova Solutions',
        'salary' => '2000 - 3500 TND',
        'description' => 'Exemple: développeur PHP expérimenté, maîtrise MVC, PDO et bonnes pratiques. Participation à toutes les phases du développement.',
        'location' => 'Tunis',
        'date_posted' => date('Y-m-d'),
        'category' => 'Informatique',
        'contract_type' => 'CDI',
        'logo' => 'https://via.placeholder.com/92x92?text=HN',
        'is_sample' => true
    ],
    [
        'id' => 's2',
        'title' => 'Data Scientist Junior (ex.)',
        'company' => 'HUMANnova AI Lab',
        'salary' => '1800 - 2600 TND',
        'description' => 'Exemple: Stage/Junior en analyse de données et apprentissage automatique. Python, pandas, scikit-learn souhaités.',
        'location' => 'Sfax',
        'date_posted' => date('Y-m-d'),
        'category' => 'Data',
        'contract_type' => 'CDD',
        'logo' => 'https://via.placeholder.com/92x92?text=AI',
        'is_sample' => true
    ],
    [
        'id' => 's3',
        'title' => 'Responsable Marketing Digital (ex.)',
        'company' => 'HUMANnova Marketing',
        'salary' => '1500 - 2800 TND',
        'description' => 'Exemple: Piloter les campagnes digitales, SEO/SEA, social media et analytics.',
        'location' => 'Remote',
        'date_posted' => date('Y-m-d'),
        'category' => 'Marketing',
        'contract_type' => 'CDI',
        'logo' => 'https://via.placeholder.com/92x92?text=MM',
        'is_sample' => true
    ]
];

foreach ($samples as $s) {
    $jobs[] = $s;
}
?>

<?php if ($role === 'recruiter'): ?>
    <div style="max-width:1180px; margin:12px auto; padding:0 18px; display:flex; justify-content:flex-end">
        <a class="btn-cta" href="AddJob.php?role=recruiter">+ Ajouter une offre</a>
    </div>
    
<?php endif; ?>

<div class="jobs-container">
    <?php if (empty($jobs)): ?>
        <div class="job-card"><div class="job-body">Aucune offre trouvée.</div></div>
    <?php endif; ?>
    
    <!-- Using jobs from the database (from JobController->getJobs()) -->

    <?php foreach ($jobs as $job): ?>
        <div class="job-card">
            <div class="job-logo">
                <?php if (!empty($job['logo'])): ?>
                    <img src="<?php echo htmlspecialchars($job['logo']); ?>" alt="logo">
                <?php else: ?>
                    <img src="https://via.placeholder.com/92x92?text=Logo" alt="logo">
                <?php endif; ?>
            </div>
            <div class="job-body">
                <h2 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h2>
                <div class="job-company"><?php echo htmlspecialchars($job['company']); ?></div>

                <div class="badges">
                    <?php if (!empty($job['category'])): ?><span class="badge"><?php echo htmlspecialchars($job['category']); ?></span><?php endif; ?>
                    <?php if (!empty($job['contract_type'])): ?><span class="badge"><?php echo htmlspecialchars($job['contract_type']); ?></span><?php endif; ?>
                    <?php if (!empty($job['salary'])): ?><span class="badge"><?php echo htmlspecialchars($job['salary']); ?></span><?php endif; ?>
                </div>

                <div class="job-desc"><?php echo htmlspecialchars(mb_strimwidth($job['description'] ?? '', 0, 220, '...')); ?></div>

                <div class="job-meta">
                    <div><svg width="14" height="14" viewBox="0 0 16 16" fill="none" style="vertical-align:middle;margin-right:6px"><path d="M8 0a5 5 0 00-5 5c0 4.167 5 11 5 11s5-6.833 5-11a5 5 0 00-5-5z" fill="#0ea5a4"/></svg><?php echo htmlspecialchars($job['location'] ?? ''); ?></div>
                    <div><?php echo htmlspecialchars($job['date_posted'] ?? ''); ?></div>
                    <?php if ($role === 'recruiter'): ?>
                        <a class="view-btn" href="UpdateJob.php?id=<?php echo htmlspecialchars($job['id']); ?>&role=recruiter">Modifier</a>
                        <a class="view-btn" href="DeleteJob.php?id=<?php echo htmlspecialchars($job['id']); ?>&role=recruiter" style="margin-left:8px;">Supprimer</a>
                    <?php else: ?>
                        <a class="view-btn" href="ViewJob.php?id=<?php echo htmlspecialchars($job['id']); ?>">Voir l'offre</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
</div>


<?php require_once __DIR__ . '/layout/footer.php'; ?>
