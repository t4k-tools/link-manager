<?php
declare(strict_types=1);

/**
 * @file link/add_link.php
 * @layer Public
 * @module link-manager
 * @version 2.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Inserimento di un nuovo link da parte dell'utente autenticato.
 *
 * @security CSRF su POST; visibilità normalizzata.
 */

require_once '1/session.php';
require_once '1/connect.php';
require_once '1/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $link = trim($_POST['link'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $descrizione = trim($_POST['descrizione'] ?? '');
    $visibilita = visibilita_normalizza($_POST['visibilita'] ?? null);
    $tags = tags_normalize($_POST['tags'] ?? '');

    if ($link && $tipo) {
        (new LinkRepository($pdo))->create((int) $_SESSION['user_id'], $link, $tipo, $descrizione, $visibilita, $tags);
        header('Location: link.php');
        exit;
    } else {
        $error = 'Inserisci almeno il link e il tipo.';
    }
}

$tagSuggest = array_keys((new LinkRepository($pdo))->tagCounts($uid = (int) $_SESSION['user_id'], $role = (string) $_SESSION['role']));

$pageTitle = 'Aggiungi Link';
require 'templates/header.php';
require 'navbar.php';
?>
    <div class="container my-4 form-container">
        <h2 class="text-center text-primary mb-4"><i class="fas fa-plus-circle"></i> Aggiungi Nuovo Link</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="bg-dark bg-opacity-75 text-white p-4 rounded shadow">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="link">Link</label>
                <input type="url" id="link" name="link" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" class="form-control" required>
                    <option value="risorse">Risorse</option>
                    <option value="myweb">MyWeb</option>
                    <option value="siti">Siti</option>
                    <option value="portali">Portali</option>
                    <option value="blog">Blog</option>
                    <option value="giornali">Giornali</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="descrizione">Descrizione</label>
                <textarea id="descrizione" name="descrizione" class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label for="visibilita">Visibilità</label>
                <select id="visibilita" name="visibilita" class="form-control">
                    <option value="privato" selected>Privato (solo io)</option>
                    <option value="condiviso">Condiviso (utenti registrati)</option>
                    <option value="pubblico">Pubblico (tutti)</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="tags">Tag</label>
                <input type="text" id="tags" name="tags" class="form-control" placeholder="es. lavoro, php, da-leggere">
                <small class="form-text text-white-50">Separati da virgola.</small>
                <?php if ($tagSuggest): ?>
                    <div class="mt-2" id="tagSuggest">
                        <?php foreach ($tagSuggest as $t): ?>
                            <button type="button" class="badge rounded-pill text-bg-light border tag-suggest mb-1" data-tag="<?= e($t) ?>">#<?= e($t) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-primary"><i class="fas fa-save"></i> Salva Link</button>
                <a href="link.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annulla</a>
            </div>
        </form>
    </div>
    <script src="js/tags.js"></script>
<?php require 'templates/footer.php'; ?>
