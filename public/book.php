<?php
session_start();
require_once __DIR__ . '/../src/books.php';
require_once __DIR__ . '/../src/users.php';

// --- Gestione della visualizzazione di un libro ---

$bookOwnerUsername = $_GET['user'] ?? null;
$bookName = $_GET['book'] ?? null;

if (!$bookOwnerUsername || !$bookName) {
    http_response_code(400);
    echo "Parametri mancanti.";
    exit;
}

$bookData = getBook($bookOwnerUsername, $bookName);

if (!$bookData) {
    http_response_code(404);
    echo "Libro non trovato.";
    exit;
}

// --- Logica dei permessi ---
$isPublic = $bookData['is_public'] ?? false;
$isOwner = isset($_SESSION['username']) && $_SESSION['username'] === $bookData['author'];
$isAuthorizedReader = isset($_SESSION['username']) && in_array($_SESSION['username'], $bookData['authorized_users'] ?? []);
$isEditor = $isOwner || (isset($_SESSION['username']) && in_array($_SESSION['username'], $bookData['authorized_editors'] ?? []));


if (!$isPublic && !$isOwner && !$isAuthorizedReader) {
    http_response_code(403);
    include __DIR__ . '/../templates/header.php';
    echo "<h1>Accesso Negato</h1>";
    echo "<p>Non hai i permessi per visualizzare questo libro.</p>";
    include __DIR__ . '/../templates/footer.php';
    exit;
}


// --- Visualizzazione del libro ---

include __DIR__ . '/../templates/header.php';

echo '<div class="row">
    <div class="col-md-4">';
        
$coverImage = $bookData['cover_image'] ?? null;
$assetPath = $coverImage ? 'users/' . $bookOwnerUsername . '/' . $bookName . '/' . $coverImage : null;
$fsPath = $coverImage ? 'data/users/' . $bookOwnerUsername . '/' . $bookName . '/' . $coverImage : null;

if ($fsPath && file_exists(__DIR__ . '/../' . $fsPath)) {
    echo '<img src="asset.php?path=' . urlencode($assetPath) . '" class="img-fluid rounded shadow-sm" alt="Copertina di ' . htmlspecialchars($bookData['title']) . '">';
} else {
    echo '<div class="img-fluid rounded shadow-sm book-card-placeholder d-flex align-items-center justify-content-center">
            <span class="fs-4 text-white p-3 text-center">' . htmlspecialchars($bookData['title']) . '</span>
          </div>';
}

echo '</div>
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1>' . htmlspecialchars($bookData['title']) . '</h1>
                <p class="lead">di <em>' . htmlspecialchars($bookData['author']) . '</em></p>
            </div>';

if ($isEditor) {
    echo '<a href="edit_book.php?user=' . urlencode($bookOwnerUsername) . '&book=' . urlencode($bookName) . '" class="btn btn-secondary ms-3">Modifica</a>';
}

echo '</div><hr><h2>Indice dei capitoli:</h2>';

$chapters = getChapters($bookOwnerUsername, $bookName);
if (empty($chapters)) {
    echo '<div class="alert alert-info">Nessun capitolo disponibile.</div>';
} else {
    echo '<ul class="list-group">';
    foreach ($chapters as $chapterFile) {
        $chapterNumber = pathinfo($chapterFile, PATHINFO_FILENAME);
        $chapterLink = "chapter.php?user=" . urlencode($bookOwnerUsername) . "&book=" . urlencode($bookName) . "&chapter=" . urlencode($chapterNumber);
        echo '<a href="' . $chapterLink . '" class="list-group-item list-group-item-action">
                Capitolo ' . htmlspecialchars($chapterNumber) . '
              </a>';
    }
    echo '</ul>';
}

echo '</div></div>';

include __DIR__ . '/../templates/footer.php';
