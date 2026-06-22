<?php
declare(strict_types=1);

/**
 * @file link/1/session.php
 * @layer Security
 * @module link-manager
 * @version 1.0.0
 * @modified 2026-06-21
 *
 * SCOPO:
 *   Avvio centralizzato e indurito della sessione PHP. Imposta i flag di
 *   sicurezza del cookie di sessione prima di session_start(). Da includere
 *   al posto della chiamata diretta session_start() nelle pagine.
 *
 * CRITICITA':
 *   - I flag del cookie vanno impostati PRIMA di avviare la sessione.
 *   - use_strict_mode riduce il rischio di session fixation.
 *   - secure viene attivato solo su connessione HTTPS, per non rompere HTTP.
 *
 * @security Cookie HttpOnly + SameSite=Lax; Secure su HTTPS; use_strict_mode.
 */

// Idempotente: se la sessione e' gia' attiva non fare nulla.
if (session_status() === PHP_SESSION_ACTIVE) {
    return;
}

// HTTPS rilevato: attiva il flag Secure solo in quel caso.
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

// use_strict_mode: rifiuta ID di sessione non inizializzati dal server.
ini_set('session.use_strict_mode', '1');

// Preserva path/domain/lifetime esistenti, indurisce solo i flag di sicurezza.
$current = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $current['lifetime'],
    'path'     => $current['path'],
    'domain'   => $current['domain'],
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
