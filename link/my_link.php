<?php
declare(strict_types=1);

/**
 * @file link/my_link.php
 * @layer Public
 * @module link-manager
 * @version 2.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Pagina pubblica dei link (visibilità 'pubblico'), in stile backend:
 *   ricerca, vista a card, paginazione (50/pagina) e colonna tag laterale.
 */

require_once '1/connect.php';

$search = (string) ($_GET['search'] ?? '');
$tag = (string) ($_GET['tag'] ?? '');

$repo = new LinkRepository($pdo);
$links = $repo->publicSearch($search, $tag);

// --- Paginazione (50 per pagina) ---
$perPage   = 50;
$total     = count($links);
$totPagine = max(1, (int) ceil($total / $perPage));
$page      = max(1, (int) ($_GET['page'] ?? 1));
$page      = min($page, $totPagine);
$pageLinks = array_slice($links, ($page - 1) * $perPage, $perPage);

// --- Tag pubblici ordinati per numero (sidebar: primi 50) ---
$tagCounts = $repo->publicTagCounts();
arsort($tagCounts);
$topTags = array_slice($tagCounts, 0, 50, true);

// Query base per conservare i filtri nella paginazione.
$qs = [];
if ($search !== '') {
    $qs['search'] = $search;
}
if ($tag !== '') {
    $qs['tag'] = $tag;
}
$baseQs = http_build_query($qs);
$pageUrl = static function (int $p) use ($baseQs): string {
    return 'my_link.php?' . ($baseQs !== '' ? $baseQs . '&' : '') . 'page=' . $p;
};

$pageTitle = 'Link Pubblici';
require 'templates/header.php';
?>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="fas fa-database"></i> Link Manager</a>
            <a href="login.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-in-alt"></i> Accedi</a>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-3"><i class="fas fa-globe"></i> Link Pubblici</h2>

        <div class="row g-4">
            <!-- Colonna principale -->
            <div class="col-lg-8">
                <form method="get" class="card card-body bg-light mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" value="<?= e($search) ?>" class="form-control form-control-lg" placeholder="Cerca tra i link pubblici..." autofocus>
                        <button class="btn btn-primary" type="submit">Cerca</button>
                        <a href="my_link.php" class="btn btn-outline-secondary" title="Reset"><i class="fas fa-times"></i></a>
                    </div>
                </form>

                <p class="text-muted small mb-2">
                    <?= $total ?> link pubblici<?= $total > $perPage ? ' — pagina ' . $page . ' di ' . $totPagine : '' ?>.
                </p>

                <?php if ($tag !== ''): ?>
                    <p class="mb-3">
                        <span class="text-muted small">Filtro tag:</span>
                        <span class="badge bg-primary">#<?= e($tag) ?></span>
                        <a href="my_link.php" class="small ms-1"><i class="fas fa-times"></i> rimuovi</a>
                    </p>
                <?php endif; ?>

                <?php if (empty($pageLinks)): ?>
                    <div class="alert alert-info">Nessun link pubblico disponibile.</div>
                <?php endif; ?>

                <?php foreach ($pageLinks as $link): ?>
                    <div class="card mb-2 shadow-sm">
                        <div class="card-body py-2">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="text-muted small text-nowrap"><i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime((string) $link['data'])) ?></span>
                                <span class="badge bg-light text-dark border text-uppercase"><?= e($link['tipo']) ?></span>
                                <a href="redirect.php?id=<?= (int) $link['id'] ?>" target="_blank" rel="noopener" class="fw-semibold text-break">
                                    <i class="fas fa-external-link-alt"></i> <?= e($link['link']) ?>
                                </a>
                            </div>
                            <?php if (!empty($link['descrizione'])): ?>
                                <p class="mb-2 text-break"><?= e($link['descrizione']) ?></p>
                            <?php endif; ?>
                            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                                <span><i class="fas fa-mouse-pointer"></i> <?= (int) $link['clicks'] ?> click</span>
                                <?php foreach (tags_to_array($link['tags']) as $tg): ?>
                                    <a href="my_link.php?tag=<?= urlencode($tg) ?>" class="badge rounded-pill text-bg-light border text-decoration-none">#<?= e($tg) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($totPagine > 1): ?>
                    <nav aria-label="Paginazione link pubblici">
                        <ul class="pagination justify-content-center mt-3">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= e($pageUrl(max(1, $page - 1))) ?>">&laquo;</a>
                            </li>
                            <?php for ($p = 1; $p <= $totPagine; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= e($pageUrl($p)) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totPagine ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= e($pageUrl(min($totPagine, $page + 1))) ?>">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <!-- Colonna tag -->
            <aside class="col-lg-4">
                <div class="position-sticky" style="top: 1rem;">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-tags"></i> Tags</h5>
                        <?php if (empty($topTags)): ?>
                            <p class="text-muted small mb-0">Nessun tag.</p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <?php foreach ($topTags as $tg => $c): ?>
                                    <a href="my_link.php?tag=<?= urlencode($tg) ?>"
                                       class="badge rounded-pill text-decoration-none <?= ($tg === strtolower($tag)) ? 'bg-primary' : 'text-bg-light border' ?>">#<?= e($tg) ?> <span class="opacity-75">(<?= $c ?>)</span></a>
                                <?php endforeach; ?>
                            </div>
                            <a href="tags.php" class="small">Tutti i tag (<?= count($tagCounts) ?>) &raquo;</a>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
<?php require 'templates/footer.php'; ?>
