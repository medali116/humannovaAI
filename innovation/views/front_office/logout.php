<?php
session_start();
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';

$authController = new AuthController($pdo);
$result = $authController->logout();

// Redirect to sign-in page
header('Location: sign-in.php');
exit();
?>
