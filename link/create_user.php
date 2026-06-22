<?php
declare(strict_types=1);

/**
 * @file link/create_user.php
 * @layer Public
 * @module link-manager
 * @version 2.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Creazione di un nuovo utente. Riservata agli amministratori.
 *
 * @security Solo admin; CSRF su POST; password con password_hash().
 */

require_once '1/session.php';
require_once '1/connect.php';
require_once '1/csrf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($username && $email && $password && $role) {
        $repo = new UserRepository($pdo);
        if (!$repo->existsUsernameOrEmail($username, $email)) {
            $repo->create($username, $email, password_hash($password, PASSWORD_DEFAULT), $role);
            header('Location: elenco_user.php');
            exit;
        } else {
            $error = 'Username o email già esistenti.';
        }
    } else {
        $error = 'Compila tutti i campi.';
    }
}

$pageTitle = 'Crea Utente';
require 'templates/header.php';
require 'navbar.php';
?>
    <div class="container my-4 form-container">
        <h1 class="text-center text-primary mb-4"><i class="fas fa-user-plus"></i> Crea Nuovo Utente</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="bg-dark bg-opacity-75 text-white p-4 rounded shadow">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="role">Ruolo</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-primary"><i class="fas fa-save"></i> Crea</button>
                <a href="elenco_user.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annulla</a>
            </div>
        </form>
    </div>
<?php require 'templates/footer.php'; ?>
