<?php
declare(strict_types=1);

/**
 * @file link/login.php
 * @layer Public
 * @module link-manager
 * @version 1.2.0
 * @modified 2026-06-22
 *
 * SCOPO:
 *   Autenticazione utente. Rigenera l'ID di sessione dopo il login.
 *
 * @side-effect Imposta i dati di sessione e reindirizza alla dashboard.
 * @security CSRF su POST; password verificate con password_verify().
 */

require_once '1/session.php';
require_once '1/connect.php';
require_once '1/csrf.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $user = (new UserRepository($pdo))->findByUsername($username);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Previene la session fixation: nuovo ID dopo l'autenticazione.
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Credenziali non valide.';
        }
    } else {
        $error = 'Inserisci username e password.';
    }
}

$pageTitle = 'Login';
$pageStyles = <<<'HTML'
    <style>
        body {
            background: url('2/login_background.webp') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background-color: rgba(0,0,0,0.75);
            padding: 2rem;
            border-radius: 1rem;
            color: white;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }
        @media (max-width: 576px) {
            .login-box {
                border-radius: 0;
                max-width: 100%;
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
        }
        .form-control {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        .form-control:focus {
            background-color: rgba(255,255,255,0.2);
            color: white;
        }
    </style>
HTML;
require 'templates/header.php';
?>
    <div class="login-box">
        <h2 class="text-center mb-4"><i class="fas fa-user-lock"></i> Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100"><i class="fas fa-sign-in-alt"></i> Accedi</button>
        </form>
    </div>
<?php require 'templates/footer.php'; ?>
