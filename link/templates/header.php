<?php
declare(strict_types=1);

/**
 * @file link/templates/header.php
 * @layer Template
 * @module link-manager
 * @version 1.1.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Intestazione HTML condivisa (doctype, head con CDN e CSS comune, apertura body).
 *   Variabili attese: $pageTitle (string), $pageStyles (string, opz.), $bodyClass (string, opz.).
 */
$pageTitle  = isset($pageTitle) ? (string) $pageTitle : 'Link Manager';
$pageStyles = isset($pageStyles) ? (string) $pageStyles : '';
$bodyClass  = isset($bodyClass) ? (string) $bodyClass : '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
<?= $pageStyles ?>
</head>
<body<?= $bodyClass !== '' ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
