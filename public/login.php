<?php
session_start();

// Se l'utente è già loggato, reindirizza alla home
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Includi l'header
include __DIR__ . '/../templates/header.php';

?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Login</h2>
                <form action="../src/auth.php" method="post">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Includi il footer
include __DIR__ . '/../templates/footer.php';
?>
