<?php // navbar.php 2505032250 ?>
<?php if (isset($_SESSION['user_id'])): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <img src="2/icon_mysql.png" alt="Logo" style="height:30px;"> Link Manager
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="create_user.php"><i class="fas fa-user-plus"></i> Crea nuovo utente</a></li>
                    <li class="nav-item"><a class="nav-link" href="elenco_user.php"><i class="fas fa-users"></i> Elenco utenti</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="link.php"><i class="fas fa-list"></i> Visualizza link</a></li>
                <li class="nav-item"><a class="nav-link" href="add_link.php"><i class="fas fa-plus-circle"></i> Aggiungi nuovo link</a></li>
            </ul>
            <span class="navbar-text text-white me-3">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
            </span>
            <a href="logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>
<?php endif; ?>