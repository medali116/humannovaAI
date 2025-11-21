<?php
require_once '../Controller/JobController.php';

// Créer une instance de JobController
$jobController = new JobController();

// Validate id
if (!isset($_GET['id'])) {
	// nothing to delete; redirect back
	$role = isset($_GET['role']) && $_GET['role'] === 'recruiter' ? '?role=recruiter' : '';
	header('Location: ListJobs.php' . $role);
	exit;
}

$jobId = $_GET['id'];

$jobController->deleteJob($jobId);

// redirect back to recruiter list if role present
$role = isset($_GET['role']) && $_GET['role'] === 'recruiter' ? '?role=recruiter' : '';
header('Location: ListJobs.php' . $role);

exit;
?>

