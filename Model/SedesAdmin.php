<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Huella\Controllers\BiometricController;

$controller = new BiometricController();
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

switch ($action) {
    case 'create':
        $controller->createHeadquarters($_POST);
        break;
    case 'delete':
        $controller->deleteHeadquarters($_POST);
        break;
    default:
        $controller->listHeadquarters();
        break;
}
