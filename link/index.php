<?php
declare(strict_types=1);

/**
 * @file link/index.php
 * @layer Public
 * @module link-manager
 * @version 1.1.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Landing pubblica con accesso al login e ai link pubblici.
 */
$pageTitle = 'Benvenuto su Link Manager';
$pageStyles = <<<'HTML'
    <style>
        body {
            background: url('2/background.webp') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .overlay {
            background-color: rgba(0,0,0,0.7);
            padding: 3rem;
            border-radius: 1rem;
            text-align: center;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
            color: white;
        }
        h1 { font-weight: bold; }
    </style>
HTML;
require 'templates/header.php';
?>
    <div class="overlay">
        <h1 class="mt-3"><i class="fas fa-database"></i> Link Manager</h1>
        <p class="lead">Gestione avanzata dei link condivisi. Accedi per gestire i tuoi contenuti oppure esplora quelli pubblici.</p>
        <div class="d-grid gap-2 d-md-flex justify-content-center mt-4">
            <a href="login.php" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt"></i> Accedi</a>
            <a href="my_link.php" class="btn btn-outline-light btn-lg"><i class="fas fa-globe"></i> Vai alla pagina dei link</a>
        </div>
    </div>
<?php require 'templates/footer.php'; ?>
