<?php
session_start();
require_once __DIR__ . '/../src/books.php';
require_once __DIR__ . '/../src/users.php';
require_once __DIR__ . '/../src/lib/Parsedown.php';

// --- Gestione della visualizzazione di un capitolo ---

$bookOwnerUsername = $_GET['user'] ?? null;
$bookName = $_GET['book'] ?? null;
$chapterNumber = $_GET['chapter'] ?? null;

if (!$bookOwnerUsername || !$bookName || !$chapterNumber) {
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

// --- Logica dei permessi (identica a book.php) ---
$isPublic = $bookData['is_public'] ?? false;
$isOwner = isset($_SESSION['username']) && $_SESSION['username'] === $bookData['author'];
$isAuthorizedReader = isset($_SESSION['username']) && in_array($_SESSION['username'], $bookData['authorized_users'] ?? []);

if (!$isPublic && !$isOwner && !$isAuthorizedReader) {
    http_response_code(403);
    include __DIR__ . '/../templates/header.php';
    echo "<h1>Accesso Negato</h1><p>Non hai i permessi per visualizzare questo capitolo.</p>";
    include __DIR__ . '/../templates/footer.php';
    exit;
}

// --- Recupero e parsing del capitolo ---
$chapterContent = getChapter($bookOwnerUsername, $bookName, (int)$chapterNumber);

if ($chapterContent === null) {
    http_response_code(404);
    echo "Capitolo non trovato.";
    exit;
}

$Parsedown = new Parsedown();
$htmlContent = $Parsedown->text($chapterContent);

// (codice gestione parole chiave esistente)

// --- Aggiunta dei numeri di riga per i segnalibri ---
$dom = new DOMDocument();
// Sopprimi gli errori di HTML non perfettamente valido e imposta l'encoding
@$dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

$lineNumber = 1;
foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
    if ($node instanceof DOMElement) {
        $node->setAttribute('data-line', (string)$lineNumber++);
        $class = $node->getAttribute('class');
        $node->setAttribute('class', trim($class . ' line-wrapper'));
    }
}
$htmlContent = $dom->saveHTML($dom->getElementsByTagName('body')->item(0));


// --- Visualizzazione del capitolo ---

include __DIR__ . '/../templates/header.php';

echo "<h1>" . htmlspecialchars($bookData['title']) . "</h1>";

echo "<div class='reader-container'>";

// Colonna sinistra per l'elenco dei segnalibri
echo "<div class='reader-left-col'>";
echo "<h3>Segnalibri</h3><p>(Work in progress)</p>";
echo "</div>";

// Colonna centrale per il testo del capitolo
echo "<div class='reader-content'>";
echo $htmlContent;
echo "</div>";

// Colonna destra per la gestione dei riassunti
echo "<div class='reader-right-col'>";
echo "<h3>Riassunti</h3>";
$summaries = getSummaries($bookOwnerUsername, $bookName);

if (empty($summaries)) {
    echo "<p>Nessun riassunto disponibile.</p>";
} else {
    foreach ($summaries as $category => $summaryItems) {
        echo "<details class='summary-category'>";
        echo "<summary>" . htmlspecialchars($category) . "</summary>";
        echo "<div>";
        foreach ($summaryItems as $summary) {
            echo "<div class='summary-item'>";
            echo "<h4>" . htmlspecialchars($summary['title']) . "</h4>";
            echo "<p>" . nl2br(htmlspecialchars($summary['content'])) . "</p>";
            echo "</div>";
        }
        echo "</div>";
        echo "</details>";
    }
}
echo "</div>";

echo "</div>"; // Fine reader-container

echo '<script>
// Pass PHP variables to JavaScript
const BOOK_ID = "' . urlencode($bookOwnerUsername . '/' . $bookName) . '";
const CURRENT_CHAPTER = "' . $chapterNumber . '";

document.addEventListener("DOMContentLoaded", () => {
    const readerContent = document.querySelector(".reader-content");
    const rightCol = document.querySelector(".reader-right-col");
    const leftCol = document.querySelector(".reader-left-col");

    // --- Gestione Segnalibri ---
    async function loadBookmarks() {
        // ... (implementazione esistente)
    }
    async function updateBookmark(chapter, index, name) {
        // ... (implementazione esistente)
    }
    async function deleteBookmark(chapter, index) {
        // ... (implementazione esistente)
    }

    loadBookmarks();
    
    // ... (altra logica JS come i gestori di eventi) ...
});
</script>';

include __DIR__ . '/../templates/footer.php';
