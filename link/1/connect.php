<?php
declare(strict_types=1);

/**
 * @file link/1/connect.php
 * @layer Config
 * @module link-manager
 * @version 1.2.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Inizializzare la connessione PDO al database del Link Manager.
 *   Supporta driver 'sqlite' (target, ADR-001) e 'mysql' (legacy).
 *   Espone la variabile $pdo usata dal resto del progetto.
 *
 * CRITICITA':
 *   Le credenziali e i percorsi non devono essere presenti in questo file:
 *   la configurazione privata viene caricata da config.local.php, escluso
 *   dal versionamento.
 */

// --- Gestione errori: nessun dettaglio tecnico all'utente, sempre nei log ---
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', '0');

// --- Caricamento configurazione privata ---
$configPath = __DIR__ . '/config.local.php';

if (!is_file($configPath)) {
    error_log('Configurazione database mancante: config.local.php non trovato.');
    http_response_code(500);
    exit('Configurazione applicativa non disponibile.');
}

$config = require $configPath;

// In ambiente di sviluppo mostra gli errori a schermo (config: 'env' => 'dev').
if (($config['env'] ?? 'prod') === 'dev') {
    ini_set('display_errors', '1');
}

// --- Connessione PDO ---
try {
    $db = $config['db'] ?? [];
    $driver = (string) ($db['driver'] ?? '');

    if ($driver === 'sqlite') {
        $path = (string) ($db['path'] ?? '');
        if ($path === '') {
            throw new RuntimeException('Percorso database SQLite mancante.');
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // PRAGMA coerenti con MIGRATION_MYSQL_SQLITE.md.
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA synchronous = NORMAL;');
        $pdo->exec('PRAGMA busy_timeout = 5000;');
        $pdo->exec('PRAGMA temp_store = MEMORY;');
    } elseif ($driver === 'mysql') {
        $host     = (string) ($db['host'] ?? '');
        $dbname   = (string) ($db['dbname'] ?? '');
        $charset  = (string) ($db['charset'] ?? 'utf8mb4');
        $username = (string) ($db['username'] ?? '');
        $password = (string) ($db['password'] ?? '');

        if ($host === '' || $dbname === '' || $username === '') {
            throw new RuntimeException('Configurazione database MySQL incompleta.');
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $dbname, $charset);

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
    } else {
        throw new RuntimeException('Driver database non supportato: ' . $driver);
    }
} catch (Throwable $exception) {
    // Errore tecnico solo nei log, messaggio generico all'utente.
    error_log('Errore connessione database Link Manager: ' . $exception->getMessage());
    http_response_code(500);
    exit('Errore temporaneo di connessione al database.');
}

// --- Support e repository condivisi (disponibili dopo la connessione) ---
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/visibility.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/LinkRepository.php';
