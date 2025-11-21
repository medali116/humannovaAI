<?php
require_once '../Controller/JobController.php';

$jobController = new JobController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job = [
        'title' => $_POST['title'] ?? null,
        'company' => $_POST['company'] ?? null,
        'salary' => $_POST['salary'] ?? null,
        'description' => $_POST['description'] ?? null,
        'location' => $_POST['location'] ?? null,
        'date_posted' => $_POST['date_posted'] ?? null,
        'category' => $_POST['category'] ?? null,
        'contract_type' => $_POST['contract_type'] ?? null,
        'logo' => $_POST['logo'] ?? null
    ];

    $jobController->addJob($job);

    // preserve recruiter role in redirect
    $redirect = 'ListJobs.php?role=recruiter';
    header('Location: ' . $redirect);
    exit;
}

$title = 'Ajouter une offre';
require_once __DIR__ . '/layout/header.php';
?>

    <div style="max-width:980px; margin:28px auto; padding:0 18px;">
        <form method="POST" class="job-form">
            <h2 style="margin-top:0;">Ajouter une offre</h2>

            <!-- styles are moved to shared stylesheet: assets/styles.css -->

            <div class="form-grid">
                <div>
                    <label for="title">Title</label>
                    <input id="title" type="text" name="title" placeholder="Titre du poste" required>
                </div>

                <div>
                    <label for="company">Company</label>
                    <input id="company" type="text" name="company" placeholder="Entreprise">
                </div>

                <div>
                    <label for="salary">Salary</label>
                    <input id="salary" type="text" name="salary" placeholder="ex: 1000 - 1500 TND">
                </div>

                <div>
                    <label for="location">Location</label>
                    <input id="location" type="text" name="location" placeholder="Ville / Région">
                </div>

                <div class="full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Description de l'offre"></textarea>
                </div>

                <div>
                    <label for="date_posted">Date posted</label>
                    <input id="date_posted" type="date" name="date_posted">
                </div>

                <div>
                    <label for="category">Category</label>
                    <input id="category" type="text" name="category" placeholder="Catégorie">
                </div>

                <div>
                    <label for="contract_type">Contract type</label>
                    <input id="contract_type" type="text" name="contract_type" placeholder="CDI / CDD etc.">
                </div>

                <div>
                    <label for="logo">Logo URL</label>
                    <input id="logo" type="text" name="logo" placeholder="https://...">
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Ajouter</button>
                <a class="btn-cancel" href="ListJobs.php?role=recruiter">← Retour</a>
            </div>
        </form>
    </div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
