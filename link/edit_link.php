<?php
declare(strict_types=1);

/**
 * @file link/edit_link.php
 * @layer Public
 * @module link-manager
 * @version 2.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Modifica di un link. Consentita al proprietario o a un admin (ADR-002).
 *
 * @security CSRF su POST; autorizzazione lato server (proprietario/admin).
 */

require_once '1/session.php';
require_once '1/connect.php';
require_once '1/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: link.php');
    exit;
}

$linkRepo = new LinkRepository($pdo);
$link = $linkRepo->find((int) $id);

if (!$link) {
    header('Location: link.php');
    exit;
}

// Autorizzazione: solo proprietario o admin (ADR-002).
if (!puo_gestire_link((int) $link['user_id'], (int) $_SESSION['user_id'], (string) $_SESSION['role'])) {
    header('Location: link.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $new_link = trim($_POST['link'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $descrizione = trim($_POST['descrizione'] ?? '');
    $visibilita = visibilita_normalizza($_POST['visibilita'] ?? null);
    $tags = tags_normalize($_POST['tags'] ?? '');

    if ($new_link && $tipo) {
        $linkRepo->update((int) $id, $new_link, $tipo, $descrizione, $visibilita, $tags);
        header('Location: link.php');
        exit;
    }
}

$tipi = ['risorse', 'myweb', 'siti', 'portali', 'blog', 'giornali'];
$tagSuggest = array_keys($linkRepo->tagCounts((int) $_SESSION['user_id'], (string) $_SESSION['role']));

$pageTitle = 'Modifica Link';
require 'templates/header.php';
require 'navbar.php';
?>
    <div class="container py-4">
        <h1><i class="fas fa-edit"></i> Modifica Link</h1>
        <form method="post" class="bg-dark bg-opacity-75 text-white p-4 rounded shadow">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="link">Link</label>
                <input type="url" id="link" name="link" value="<?= e($link['link']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" class="form-control" required>
                    <?php foreach ($tipi as $t): ?>
                        <option value="<?= e($t) ?>" <?= ($t === $link['tipo']) ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="descrizione">Descrizione</label>
                <textarea id="descrizione" name="descrizione" class="form-control"><?= e($link['descrizione']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="visibilita">Visibilità</label>
                <select id="visibilita" name="visibilita" class="form-control">
                    <?php foreach (VISIBILITA_VALIDE as $v): ?>
                        <option value="<?= e($v) ?>" <?= ($v === $link['visibilita']) ? 'selected' : '' ?>><?= e(visibilita_label($v)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="tags">Tag</label>
                <input type="text" id="tags" name="tags" class="form-control" value="<?= e(implode(', ', tags_to_array($link['tags'] ?? ''))) ?>" placeholder="es. lavoro, php, da-leggere">
                <small class="form-text text-white-50">Separati da virgola.</small>
                <?php if ($tagSuggest): ?>
                    <div class="mt-2" id="tagSuggest">
                        <?php foreach ($tagSuggest as $t): ?>
                            <button type="button" class="badge rounded-pill text-bg-light border tag-suggest mb-1" data-tag="<?= e($t) ?>">#<?= e($t) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <button class="btn btn-primary"><i class="fas fa-save"></i> Salva</button>
            <a href="link.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annulla</a>
        </form>
    </div>
    <script src="js/tags.js"></script>
<?php require 'templates/footer.php'; ?>
