<?php
declare(strict_types=1);

/**
 * @file link/elenco_user.php
 * @layer Public
 * @module link-manager
 * @version 2.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Elenco degli utenti. Riservato agli amministratori.
 */

require_once '1/session.php';
require_once '1/connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$users = (new UserRepository($pdo))->all();

$pageTitle = 'Elenco Utenti';
require 'templates/header.php';
require 'navbar.php';
?>
    <div class="container my-4">
        <h2><i class="fas fa-users"></i> Elenco Utenti</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Username</th><th>Email</th><th>Ruolo</th><th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= e($user['username']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e($user['role']) ?></td>
                            <td>
                                <a href="modifica_user.php?id=<?= (int) $user['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Modifica
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php require 'templates/footer.php'; ?>
