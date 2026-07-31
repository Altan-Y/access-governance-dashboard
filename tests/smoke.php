<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

function assertTrue(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

assertTrue(count($storage->groups()) === 8, 'demo groups load');
assertTrue(count($storage->jobRoles()) === 5, 'demo job roles load');
assertTrue($auth->attempt('altan@example.test', 'demo123'), 'valid demo login');
$user = $auth->user();
assertTrue($user !== null, 'authenticated user available');
assertTrue($storage->canEdit($storage->find('group', 1) ?? [], $user['email']), 'owner can edit assigned group');
assertTrue(!$storage->canEdit($storage->find('group', 5) ?? [], $user['email']), 'user cannot edit unassigned group');
assertTrue($storage->updateDescription('group', 1, 'Smoke test description', $user['email']), 'authorized update succeeds');
assertTrue(($storage->find('group', 1)['description'] ?? '') === 'Smoke test description', 'updated value persists');
assertTrue(!$storage->updateDescription('group', 5, 'Unauthorized update', $user['email']), 'unauthorized update is rejected');

echo "All smoke tests passed using {$storage->mode()} storage.\n";
