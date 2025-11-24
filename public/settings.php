<?php
session_start();
require_once __DIR__ . '/../src/users.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- Gestione form ---
// TODO: Implementare la logica per il cambio password e l'upload dell'avatar.

include __DIR__ . '/../templates/header.php';
$username = $_SESSION['username'];
$userData = getUserByUsername($username);
?>

<h1>Impostazioni</h1>

<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-pane" type="button" role="tab">Profilo</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance-pane" type="button" role="tab">Aspetto</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="auth-tab" data-bs-toggle="tab" data-bs-target="#auth-pane" type="button" role="tab">Autenticazione</button>
  </li>
</ul>

<div class="tab-content pt-4" id="settingsTabsContent">
  <!-- Pannello Profilo -->
  <div class="tab-pane fade show active" id="profile-pane" role="tabpanel">
    <h3>Profilo Utente</h3>
    <form>
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($username); ?>" disabled>
            <div class="form-text">L'username non può essere modificato.</div>
        </div>
        <div class="mb-3">
            <label for="avatar" class="form-label">Avatar</label>
            <input type="file" class="form-control" id="avatar" disabled>
            <div class="form-text">(Funzione non ancora implementata)</div>
        </div>
    </form>
  </div>

  <!-- Pannello Aspetto -->
  <div class="tab-pane fade" id="appearance-pane" role="tabpanel">
    <h3>Aspetto del Sito</h3>
    <p>Puoi cambiare il tema del sito (chiaro/scuro) usando l'interruttore 🌙/☀️ in alto a destra nella barra di navigazione.</p>
    <p>La tua preferenza verrà salvata automaticamente nel browser.</p>
  </div>

  <!-- Pannello Autenticazione -->
  <div class="tab-pane fade" id="auth-pane" role="tabpanel">
    <h3>Cambia Password</h3>
    <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="mb-3">
            <label for="old_password" class="form-label">Vecchia Password</label>
            <input type="password" class="form-control" id="old_password" name="old_password" required>
        </div>
        <div class="mb-3">
            <label for="new_password" class="form-label">Nuova Password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Conferma Nuova Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary" disabled>(Funzione non ancora implementata)</button>
    </form>
  </div>
</div>


<?php
include __DIR__ . '/../templates/footer.php';
?>
