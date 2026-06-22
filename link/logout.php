<?php
declare(strict_types=1);

/**
 * @file link/logout.php
 * @layer Public
 * @module link-manager
 * @version 1.1.0
 * @modified 2026-06-21
 *
 * SCOPO:
 *   Chiudere la sessione utente in modo completo e reindirizzare al login.
 *
 * @side-effect Distrugge la sessione e invalida il cookie di sessione.
 * @security Svuota $_SESSION, invalida il cookie, distrugge la sessione.
 */

require_once '1/session.php';

// Svuota i dati di sessione.
$_SESSION = [];

// Invalida il cookie di sessione lato client.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

// Distrugge la sessione lato server.
session_destroy();

header('Location: login.php');
exit;
