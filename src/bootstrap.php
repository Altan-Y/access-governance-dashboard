<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');

require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Security.php';

$storage = Storage::create(DATA_DIR);
$auth = new Auth();
$security = new Security();
