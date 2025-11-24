<?php
session_start();
require_once __DIR__ . '/../src/books.php';

$bookOwnerUsername = $_GET['user'] ?? null;
$bookName = $_GET['book'] ?? null;
$chapterNumber = $_GET['chapter'] ?? null;

// --- Permission check ---
$bookData = getBook($bookOwnerUsername, $bookName);
$isOwner = isset($_SESSION['username']) && $_SESSION['username'] === $bookData['author'];
$isEditor = $isOwner || (isset($_SESSION['username']) && in_array($_SESSION['username'], $bookData['authorized_editors'] ?? []));

if (!$isEditor) {
    http_response_code(403);
    echo "Accesso negato.";
    exit;
}

$chapterContent = getChapter($bookOwnerUsername, $bookName, (int)$chapterNumber);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newContent = $_POST['content'] ?? '';
    
    if (updateChapter($bookOwnerUsername, $bookName, (int)$chapterNumber, $newContent)) {
        $saveMessage = "Contenuto salvato con successo!";
        $chapterContent = $newContent; // Aggiorna il contenuto mostrato
    } else {
        $saveMessage = "Errore durante il salvataggio.";
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1>Modifica Capitolo <?php echo htmlspecialchars($chapterNumber); ?></h1>
    </div>
    <div class="card-body">
        <?php if (isset($saveMessage)): ?>
            <div class="alert <?php echo (strpos($saveMessage, 'Errore') === false) ? 'alert-success' : 'alert-danger'; ?>"><?php echo $saveMessage; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <textarea name="content" class="form-control chapter-editor" rows="20"><?php echo htmlspecialchars($chapterContent); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Salva Modifiche</button>
            <a href="edit_book.php?user=<?php echo urlencode($bookOwnerUsername); ?>&book=<?php echo urlencode($bookName); ?>" class="btn btn-secondary">Torna alla gestione</a>
        </form>
    </div>
</div>

<?php
include __DIR__ . '/../templates/footer.php';
?>
