<?php
declare(strict_types=1);

/**
 * @file link/delete_link.php
 * @layer Public
 * @module link-manager
 * @version 2.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Eliminare un link. Consentito solo via POST, con token CSRF, al
 *   proprietario del link o a un admin (ADR-002).
 *
 * @side-effect DELETE su link_links.
 * @security POST-only, CSRF, autorizzazione lato server per proprietario/admin.
 */

require_once '1/session.php';
require_once '1/connect.php';
require_once '1/csrf.php';
require_once '1/visibility.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Niente side-effect via GET: solo POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metodo non consentito.');
}

csrf_validate();

$id = $_POST['id'] ?? null;
if ($id !== null && ctype_digit((string) $id)) {
    $repo = new LinkRepository($pdo);
    $row = $repo->find((int) $id);

    if ($row !== null
        && puo_gestire_link((int) $row['user_id'], (int) $_SESSION['user_id'], (string) $_SESSION['role'])
    ) {
        $repo->delete((int) $id);
    }
}

header('Location: link.php');
exit;
