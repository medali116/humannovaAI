<?php
$controller = $_GET['controller'] ?? null;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If this entry is requested directly without a controller, redirect to the
// template index so the site is displayed with the template CSS/structure.
if ($controller === null) {
    header("Location: /templatemo_600_prism_flux/templatemo_600_prism_flux/index.php");
    exit;
}

$controller = $_GET['controller'] ?? 'article';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

$controllerFile = "Controllers/" . ucfirst($controller) . "Controller.php";
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerClass = ucfirst($controller) . "Controller";
    $obj = new $controllerClass();

    if ($id !== null && method_exists($obj, $action)) {
        $obj->$action($id);
    } elseif (method_exists($obj, $action)) {
        $obj->$action();
    } else {
        echo "Action not found!";
    }
} else {
    echo "Controller not found!";
}
?>
