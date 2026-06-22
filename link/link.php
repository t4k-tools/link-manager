<?php
declare(strict_types=1);

/**
 * @file link/link.php
 * @layer Public
 * @module link-manager
 * @version 3.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Elenco dei link visibili all'utente (ADR-002), con ricerca/filtri,
 *   export CSV, vista a card e paginazione (20 per pagina) e azioni di
 *   modifica/eliminazione sui link gestibili.
 */

require_once '1/session.php';
require_once '1/connect.php';
require_once '1/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid  = (int) $_SESSION['user_id'];
$role = (string) $_SESSION['role'];

$tipo_filter = (string) ($_GET['tipo'] ?? '');
$search = (string) ($_GET['search'] ?? '');
$tag = (string) ($_GET['tag'] ?? '');

$linkRepo = new LinkRepository($pdo);
$links = $linkRepo->visibleList($uid, $role, $tipo_filter, $search, $tag);

// EXPORT CSV (prima di qualsiasi output HTML).
if (isset($_GET['export']) && $_GET['export'] == '1') {
    header('Content-Type: text/csv; charset=utf-8');
    $filename = 'link_export_' . date('Ymd_His') . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Data', 'Link', 'Tipo', 'Descrizione', 'Utente', 'Visibilità', 'Tag', 'Click']);
    foreach ($links as $link) {
        $dataOra = date('d/m/Y H:i', strtotime((string) $link['data']));
        fputcsv($output, [
            $dataOra, $link['link'], $link['tipo'], $link['descrizione'],
            $link['username'], visibilita_label($link['visibilita']),
            implode(', ', tags_to_array($link['tags'])), $link['clicks'],
        ]);
    }
    fclose($output);
    exit;
}

$tipi = ['risorse', 'myweb', 'siti', 'portali', 'blog', 'giornali'];

// --- Paginazione (20 per pagina) ---
$perPage   = 20;
$total     = count($links);
$totPagine = max(1, (int) ceil($total / $perPage));
$page      = max(1, (int) ($_GET['page'] ?? 1));
$page      = min($page, $totPagine);
$pageLinks = array_slice($links, ($page - 1) * $perPage, $perPage);

// Base query string per conservare i filtri nei link di paginazione.
$qs = [];
if ($tipo_filter !== '') {
    $qs['tipo'] = $tipo_filter;
}
if ($search !== '') {
    $qs['search'] = $search;
}
if ($tag !== '') {
    $qs['tag'] = $tag;
}
$baseQs = http_build_query($qs);
$pageUrl = static function (int $p) use ($baseQs): string {
    return 'link.php?' . ($baseQs !== '' ? $baseQs . '&' : '') . 'page=' . $p;
};

$badgeMap = ['pubblico' => 'bg-success', 'condiviso' => 'bg-info text-dark', 'privato' => 'bg-secondary'];

// Tag cloud (tag visibili all'utente con conteggio).
$tagCounts = $linkRepo->tagCounts($uid, $role);

$pageTitle = 'Visualizza Link';
require 'templates/header.php';
require 'navbar.php';
?>
    <div class="container my-4">
        <h2 class="mb-3"><i class="fas fa-list"></i> Visualizza Link</h2>

        <!-- Ricerca e filtri -->
        <form method="get" class="card card-body bg-light mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="search" class="form-label mb-1">Ricerca</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="search" name="search" value="<?= e($search) ?>" class="form-control form-control-lg" placeholder="Cerca nella descrizione..." autofocus>
                    </div>
                </div>
                <div class="col-7 col-md-3">
                    <label for="tipo" class="form-label mb-1">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Tutti --</option>
                        <?php foreach ($tipi as $t): ?>
                            <option value="<?= e($t) ?>" <?= ($t === $tipo_filter) ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-5 col-md-4 d-flex gap-1">
                    <button class="btn btn-primary flex-grow-1" type="submit"><i class="fas fa-search"></i> Cerca</button>
                    <a href="link.php" class="btn btn-outline-secondary" title="Reset"><i class="fas fa-times"></i></a>
                    <a href="link.php?export=1<?= $tipo_filter ? '&tipo=' . urlencode($tipo_filter) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-success" title="Esporta CSV"><i class="fas fa-file-csv"></i></a>
                </div>
            </div>
        </form>

        <?php if ($tagCounts): ?>
            <div class="mb-3">
                <span class="text-muted small me-1"><i class="fas fa-tags"></i> Tag:</span>
                <?php
                $maxC = max($tagCounts);
                foreach ($tagCounts as $tg => $c):
                    $size = 0.8 + 0.6 * ($c / $maxC);
                    $attivo = ($tg === strtolower($tag));
                    ?>
                    <a href="link.php?tag=<?= urlencode($tg) ?>"
                       class="badge rounded-pill text-decoration-none me-1 mb-1 <?= $attivo ? 'bg-primary' : 'text-bg-light border' ?>"
                       style="font-size: <?= number_format($size, 2) ?>rem;">#<?= e($tg) ?> <span class="opacity-75">(<?= $c ?>)</span></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="text-muted small mb-2">
            <?= $total ?> link trovati<?= $total > $perPage ? ' — pagina ' . $page . ' di ' . $totPagine : '' ?>.
        </p>

        <?php if ($tag !== ''): ?>
            <p class="mb-3">
                <span class="text-muted small">Filtro tag:</span>
                <span class="badge bg-primary">#<?= e($tag) ?></span>
                <a href="link.php" class="small ms-1"><i class="fas fa-times"></i> rimuovi</a>
            </p>
        <?php endif; ?>

        <?php if (empty($pageLinks)): ?>
            <div class="alert alert-info">Nessun risultato trovato.</div>
        <?php endif; ?>

        <!-- Vista a card -->
        <?php foreach ($pageLinks as $link): ?>
            <?php
            $vis = $link['visibilita'];
            $badge = $badgeMap[$vis] ?? 'bg-secondary';
            $gestibile = puo_gestire_link((int) $link['user_id'], $uid, $role);
            ?>
            <div class="card mb-2 shadow-sm">
                <div class="card-body py-2">
                    <!-- Riga 1: data+ora | tipo | link -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="text-muted small text-nowrap"><i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime((string) $link['data'])) ?></span>
                        <span class="badge bg-light text-dark border text-uppercase"><?= e($link['tipo']) ?></span>
                        <a href="redirect.php?id=<?= (int) $link['id'] ?>" target="_blank" rel="noopener" class="fw-semibold text-break">
                            <i class="fas fa-external-link-alt"></i> <?= e($link['link']) ?>
                        </a>
                    </div>

                    <!-- Riga 2: descrizione -->
                    <?php if (!empty($link['descrizione'])): ?>
                        <p class="mb-2 text-break"><?= e($link['descrizione']) ?></p>
                    <?php endif; ?>

                    <!-- Riga 3: utente | visibilità | click | azioni -->
                    <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">
                        <span><i class="fas fa-user"></i> <?= e($link['username']) ?></span>
                        <span class="badge <?= $badge ?>"><?= e(visibilita_label($vis)) ?></span>
                        <span><i class="fas fa-mouse-pointer"></i> <?= (int) $link['clicks'] ?> click</span>
                        <?php foreach (tags_to_array($link['tags']) as $tg): ?>
                            <a href="link.php?tag=<?= urlencode($tg) ?>" class="badge rounded-pill text-bg-light border text-decoration-none" title="Filtra per tag">#<?= e($tg) ?></a>
                        <?php endforeach; ?>
                        <?php if ($gestibile): ?>
                            <span class="ms-auto d-flex gap-1">
                                <a href="edit_link.php?id=<?= (int) $link['id'] ?>" class="btn btn-sm btn-warning" title="Modifica"><i class="fas fa-edit"></i></a>
                                <form method="post" action="delete_link.php" class="d-inline" onsubmit="return confirm('Eliminare questo link?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                                    <button class="btn btn-sm btn-danger" title="Elimina"><i class="fas fa-trash"></i></button>
                                </form>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Paginazione -->
        <?php if ($totPagine > 1): ?>
            <nav aria-label="Paginazione link">
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
<?php require 'templates/footer.php'; ?>
