<?php
declare(strict_types=1);
$root = dirname(__DIR__);
@unlink($root . '/data/app.sqlite');
@unlink($root . '/data/demo.json');
require_once $root . '/src/bootstrap.php';
echo "Demo data reset. Storage mode: {$storage->mode()}\n";
