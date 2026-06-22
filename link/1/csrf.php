<?php
declare(strict_types=1);

/**
 * @file link/1/csrf.php
 * @layer Security
 * @module link-manager
 * @version 1.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Protezione CSRF per i form POST con side-effect. Richiede una sessione
 *   gia' avviata (1/session.php).
 *
 * @security Token per sessione, confronto con hash_equals().
 */

/**
 * Restituisce il token CSRF della sessione, generandolo se assente.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Restituisce il campo hidden da inserire nei form POST.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida il token CSRF inviato via POST. Interrompe la richiesta se non valido.
 *
 * @side-effect Termina l'esecuzione con HTTP 400 in caso di token mancante/errato.
 */
function csrf_validate(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (
        !is_string($token) || $token === ''
        || empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(400);
        exit('Richiesta non valida (controllo CSRF fallito).');
    }
}
