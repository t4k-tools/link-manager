<?php
declare(strict_types=1);

/**
 * @file link/1/config.example.php
 * @layer Config
 * @module link-manager
 * @version 2.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Esempio di configurazione locale. Copiare in config.local.php e adattare.
 *   config.local.php e' escluso dal versionamento (.gitignore).
 *   Sono mostrati entrambi i driver: 'sqlite' (target, ADR-001) e 'mysql' (legacy).
 *
 * CRITICITA':
 *   Questo file non deve contenere credenziali reali.
 */

// --- Opzione consigliata: SQLite (database dedicato, fuori dalla docroot) ---
return [
    // 'dev' mostra gli errori a schermo; 'prod' li nasconde (solo log). Default: prod.
    'env' => 'prod',
    'db' => [
        'driver' => 'sqlite',
        'path'   => __DIR__ . '/../../storage/link_manager.sqlite',
    ],
];

// --- Opzione alternativa: MySQL legacy ---
// return [
//     'db' => [
//         'driver'   => 'mysql',
//         'host'     => 'localhost',
//         'dbname'   => 'nome_database',
//         'username' => 'utente_database',
//         'password' => 'password_database',
//         'charset'  => 'utf8mb4',
//     ],
// ];
