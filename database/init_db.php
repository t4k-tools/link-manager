<?php
declare(strict_types=1);

/**
 * @file database/init_db.php
 * @layer Tooling
 * @module link-manager
 * @version 1.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Inizializza il database SQLite dallo schema e crea un utente admin iniziale
 *   se non esiste alcun utente. Pensato per chi clona il progetto da zero.
 *
 * USO (CLI):
 *   php database/init_db.php [username] [password] [email]
 *   (default: admin / changeme / admin@example.com)
 */

$root   = dirname(__DIR__);
$dbPath = $root . '/storage/link_manager.sqlite';
$schema = $root . '/database/schema_sqlite.sql';

if (!is_dir($root . '/storage')) {
    mkdir($root . '/storage', 0775, true);
}

$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdo->exec('PRAGMA foreign_keys = ON;');
$pdo->exec(file_get_contents($schema));

$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'changeme';
$email    = $argv[3] ?? 'admin@example.com';

$esistenti = (int) $pdo->query('SELECT COUNT(*) FROM link_users')->fetchColumn();
if ($esistenti === 0) {
    $st = $pdo->prepare('INSERT INTO link_users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $st->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), 'admin']);
    echo "Database creato in storage/link_manager.sqlite\n";
    echo "Utente admin: {$username} (password: {$password}) — cambiala al primo accesso!\n";
} else {
    echo "Database gia' inizializzato ({$esistenti} utenti). Schema applicato/verificato.\n";
}
