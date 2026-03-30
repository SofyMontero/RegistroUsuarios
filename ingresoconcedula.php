<?php

require_once __DIR__ . '/app/bootstrap.php';

use Huella\Controllers\BiometricController;

$controller = new BiometricController();
$controller->registerByDocument($_GET + $_POST);
