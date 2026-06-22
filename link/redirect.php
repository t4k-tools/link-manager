<?php
declare(strict_types=1);

/**
 * @file link/redirect.php
 * @layer Public
 * @module link-manager
 * @version 2.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Reindirizzare verso l'URL di un link e incrementarne il contatore click.
 *
 * @side-effect Incremento clicks; redirect HTTP.
 * @security Accetta solo URL http/https validi (mitiga open redirect / schemi pericolosi).
 */

require_once '1/connect.php';

$id = $_GET['id'] ?? null;

if ($id !== null && ctype_digit((string) $id)) {
    $repo = new LinkRepository($pdo);
    $url = $repo->findUrl((int) $id);

    if ($url !== null) {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $valido = in_array($scheme, ['http', 'https'], true)
            && filter_var($url, FILTER_VALIDATE_URL) !== false;

        if ($valido) {
            $repo->incrementClicks((int) $id);
            header('Location: ' . $url);
            exit;
        }
    }
}

// Fallback: link assente o non valido.
header('Location: my_link.php');
exit;
