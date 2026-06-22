<?php
declare(strict_types=1);

/**
 * @file link/tags.php
 * @layer Public
 * @module link-manager
 * @version 1.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Elenco pubblico di tutti i tag (link pubblici) su 6 colonne, con il numero
 *   di elementi. Ogni voce rimanda all'elenco filtrato per quel tag.
 */

require_once '1/connect.php';

$tagCounts = (new LinkRepository($pdo))->publicTagCounts();
arsort($tagCounts); // ordina per numero di elementi (desc)

$pageTitle = 'Tutti i tag';
require 'templates/header.php';
?>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="fas fa-database"></i> Link Manager</a>
            <a href="login.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-in-alt"></i> Accedi</a>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-3"><i class="fas fa-tags"></i> Tutti i tag</h2>
        <p><a href="my_link.php">&laquo; Torna ai link pubblici</a></p>

        <?php if (empty($tagCounts)): ?>
            <div class="alert alert-info">Nessun tag disponibile.</div>
        <?php else: ?>
            <p class="text-muted small"><?= count($tagCounts) ?> tag. Il numero indica gli elementi ed è cliccabile.</p>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-2">
                <?php foreach ($tagCounts as $tg => $c): ?>
                    <div class="col">
                        <a href="my_link.php?tag=<?= urlencode($tg) ?>"
                           class="d-flex justify-content-between align-items-center border rounded px-2 py-1 text-decoration-none"
                           title="Mostra i link con il tag <?= e($tg) ?>">
                            <span class="text-truncate me-1">#<?= e($tg) ?></span>
                            <span class="badge text-bg-primary"><?= $c ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php require 'templates/footer.php'; ?>
