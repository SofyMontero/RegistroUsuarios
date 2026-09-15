<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';

use Huella\Controllers\BiometricController;

requerir_admin_api();

$controller = new BiometricController();
$controller->createUser($_POST, $_FILES);
