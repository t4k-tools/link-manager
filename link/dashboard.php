<?php
declare(strict_types=1);

/**
 * @file link/dashboard.php
 * @layer Public
 * @module link-manager
 * @version 2.0.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Dashboard con statistiche dei link filtrate per visibilità (ADR-002):
 *   admin globale, user limitato al proprio scope.
 */

require_once '1/session.php';
require_once '1/connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid  = (int) $_SESSION['user_id'];
$role = (string) $_SESSION['role'];

$linkRepo    = new LinkRepository($pdo);
$counts      = $linkRepo->statsByVisibility($uid, $role);
$user_counts = $linkRepo->countPerUser($uid, $role);
$click_rows  = $linkRepo->clicksList($uid, $role);

$pageTitle = 'Dashboard Statistiche';
require 'templates/header.php';
require 'navbar.php';
?>
    <div class="container my-4">
        <h2><i class="fas fa-chart-bar"></i> Statistiche Sistema</h2>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="card-title text-success"><i class="fas fa-eye"></i> Pubblici</h4>
                        <p class="display-4"><?= (int) $counts['pubblico'] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="card-title text-info"><i class="fas fa-users"></i> Condivisi</h4>
                        <p class="display-4"><?= (int) $counts['condiviso'] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="card-title text-secondary"><i class="fas fa-lock"></i> Privati</h4>
                        <p class="display-4"><?= (int) $counts['privato'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <h4><i class="fas fa-users"></i> Link per Utente</h4>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-primary">
                    <tr><th>Utente</th><th>Totale Link</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($user_counts as $row): ?>
                        <tr>
                            <td><?= e($row['username']) ?></td>
                            <td><?= (int) $row['total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h4><i class="fas fa-mouse-pointer"></i> Click per Link</h4>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-primary">
                    <tr><th>Link</th><th>Click</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($click_rows as $row): ?>
                        <tr>
                            <td><?= e($row['link']) ?></td>
                            <td><?= (int) $row['clicks'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php require 'templates/footer.php'; ?>
