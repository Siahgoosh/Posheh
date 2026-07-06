<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$config = config('database.connections.mysql');
$database = $config['database'] ?? 'posheh';
$host = $config['host'] ?? 'mysql';
$port = $config['port'] ?? '3306';
$username = $config['username'] ?? 'posheh';
$password = $config['password'] ?? 'secret';

$rootPassword = env('MYSQL_ROOT_PASSWORD', 'secret');

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port}",
        'root',
        $rootPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException) {
    // Fall back to the configured application user.
    $pdo = new PDO(
        "mysql:host={$host};port={$port}",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

$pdo->exec(
    "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
);

echo "Database '{$database}' is ready.\n";
