<?php
session_start();
require_once __DIR__ . '/../src/books.php';
require_once __DIR__ . '/../src/lock.php';

// --- Parameter and Permission Checks ---
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

// --- Logica dei permessi di modifica ---
$isOwner = isset($_SESSION['username']) && $_SESSION['username'] === $bookData['author'];
$isEditor = $isOwner || (isset($_SESSION['username']) && in_array($_SESSION['username'], $bookData['authorized_editors'] ?? []));

if (!$isEditor) {
    http_response_code(403);
    include __DIR__ . '/../templates/header.php';
    echo "<h1>Accesso Negato</h1>";
    echo "<p>Non hai i permessi per modificare questo libro.</p>";
    include __DIR__ . '/../templates/footer.php';
    exit;
}

// --- Lock Handling ---
$bookPath = __DIR__ . '/../data/users/' . $bookOwnerUsername . '/' . $bookName;
$lockStatus = checkLock($bookPath, $_SESSION['user_id']);

if ($lockStatus['status'] === 'locked' && $lockStatus['owner'] !== $_SESSION['user_id']) {
    include __DIR__ . '/../templates/header.php';
    echo "<h1>Accesso Negato</h1>";
    echo "<p>Questo libro è attualmente in modifica da un altro utente (ID: " . htmlspecialchars($lockStatus['owner']) . ").</p>";
    include __DIR__ . '/../templates/footer.php';
    exit;
}

acquireLock($bookPath, $_SESSION['user_id']);

// --- POST Form Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_keywords':
                $keywordsJson = $_POST['keywords'] ?? '{}';
                $keywords = json_decode($keywordsJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (updateKeywords($bookOwnerUsername, $bookName, $keywords)) {
                        $keywordMessage = "Parole chiave salvate con successo!";
                    } else {
                        $keywordMessage = "Errore durante il salvataggio delle parole chiave.";
                    }
                } else {
                    $keywordMessage = "Formato JSON non valido.";
                }
                break;
            case 'update_summaries':
                $summariesJson = $_POST['summaries'] ?? '{}';
                $summaries = json_decode($summariesJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (updateSummaries($bookOwnerUsername, $bookName, $summaries)) {
                        $summaryMessage = "Riassunti salvati con successo!";
                    } else {
                        $summaryMessage = "Errore durante il salvataggio dei riassunti.";
                    }
                } else {
                    $summaryMessage = "Formato JSON non valido per i riassunti.";
                }
                break;
        }
    }
}

// --- Start HTML ---
include __DIR__ . '/../templates/header.php';
?>

<h1>Modifica: <?php echo htmlspecialchars($bookData['title']); ?></h1>

<!-- Chapter Management -->
<div id="chapter-management" class="card mb-4">
    <div class="card-header">
        <h2>Gestione Capitoli</h2>
    </div>
    <div class="card-body">
        <div class='list-group mb-3'>
            <?php
            $chapters = getChapters($bookOwnerUsername, $bookName);
            if (empty($chapters)) {
                echo "<p class='mb-0'>Nessun capitolo in questo libro.</p>";
            } else {
                foreach ($chapters as $chapterFile) {
                    $chapterNumber = pathinfo($chapterFile, PATHINFO_FILENAME);
                    echo "<div class='list-group-item d-flex justify-content-between align-items-center' data-chapter-num='{$chapterNumber}'>";
                    echo "<span>Capitolo {$chapterNumber}</span>";
                    echo "<div class='chapter-actions'>";
                    echo "<a href='edit_chapter.php?user={$bookOwnerUsername}&book={$bookName}&chapter={$chapterNumber}' class='btn btn-sm btn-outline-secondary me-2'>Modifica</a>";
                    echo "<button class='btn btn-sm btn-danger delete-chapter-btn'>Elimina</button>";
                    echo "</div></div>";
                }
            }
            ?>
        </div>
        <button id="add-chapter-btn" class="btn btn-success">Aggiungi nuovo capitolo</button>
    </div>
</div>


<!-- Summary Management -->
<div class="card mb-4">
    <div class="card-header">
        <h2>Gestione Riassunti</h2>
    </div>
    <div class="card-body">
        <?php if (isset($summaryMessage)): ?>
            <div class="alert <?php echo (strpos($summaryMessage, 'Errore') === false) ? 'alert-success' : 'alert-danger'; ?>"><?php echo $summaryMessage; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="update_summaries">
            <div class="mb-3">
                <label for="summaries" class="form-label">Modifica i riassunti (formato JSON):</label>
                <textarea name="summaries" id="summaries" class="form-control" rows="8"><?php echo htmlspecialchars(json_encode(getSummaries($bookOwnerUsername, $bookName), JSON_PRETTY_PRINT)); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Salva Riassunti</button>
        </form>
    </div>
</div>

<!-- Keyword Management -->
<div class="card">
    <div class="card-header">
        <h2>Gestione Parole Chiave</h2>
    </div>
    <div class="card-body">
        <?php if (isset($keywordMessage)): ?>
            <div class="alert <?php echo (strpos($keywordMessage, 'Errore') === false) ? 'alert-success' : 'alert-danger'; ?>"><?php echo $keywordMessage; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="update_keywords">
            <div class="mb-3">
                <label for="keywords" class="form-label">Modifica le parole chiave (formato JSON):</label>
                <textarea name="keywords" id="keywords" class="form-control" rows="8"><?php echo htmlspecialchars(json_encode(getKeywords($bookOwnerUsername, $bookName), JSON_PRETTY_PRINT)); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Salva Parole Chiave</button>
        </form>
    </div>
</div>


<script>
// ... (codice javascript esistente) ...
</script>

<?php
include __DIR__ . '/../templates/footer.php';
?>