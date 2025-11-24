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
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Gestione Riassunti</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#summary-modal" id="add-summary-btn-main">
            Aggiungi Riassunto
        </button>
    </div>
    <div class="card-body">
        <?php if (isset($summaryMessage)): ?>
            <div class="alert alert-success"><?php echo $summaryMessage; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="summaries-form">
            <input type="hidden" name="action" value="update_summaries">
            <input type="hidden" id="summaries-json-input" name="summaries" value='<?php echo htmlspecialchars(json_encode(getSummaries($bookOwnerUsername, $bookName))); ?>'>

            <div class="accordion" id="summaries-accordion">
                <!-- L'accordion dei riassunti verrà inserito qui da JS -->
            </div>
            
            <button type="submit" class="btn btn-primary mt-4">Salva Modifiche Riassunti</button>
        </form>
    </div>
</div>

<!-- Modal for Summary -->
<div class="modal fade" id="summary-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="summary-modal-title">Aggiungi Riassunto</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="summary-edit-category-index">
                <input type="hidden" id="summary-edit-item-index">
                <div class="mb-3">
                    <label for="summary-category" class="form-label">Categoria</label>
                    <input type="text" class="form-control" id="summary-category" placeholder="Es: Personaggi, Luoghi...">
                </div>
                <div class="mb-3">
                    <label for="summary-title" class="form-label">Titolo</label>
                    <input type="text" class="form-control" id="summary-title" required>
                </div>
                <div class="mb-3">
                    <label for="summary-content" class="form-label">Contenuto</label>
                    <textarea class="form-control" id="summary-content" rows="5" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="save-summary-btn">Salva</button>
            </div>
        </div>
    </div>
</div>

<!-- Keyword Management -->
<div class="card">
    <div class="card-header">
        <h2>Gestione Parole Chiave</h2>
    </div>
    <div class="card-body">
        <?php if (isset($keywordMessage)): ?>
            <div class="alert alert-success"><?php echo $keywordMessage; ?></div>
        <?php endif; ?>
        
        <!-- Form principale che contiene i dati JSON nascosti -->
        <form method="POST" id="keywords-form">
            <input type="hidden" name="action" value="update_keywords">
            <input type="hidden" id="keywords-json-input" name="keywords" value='<?php echo htmlspecialchars(json_encode(getKeywords($bookOwnerUsername, $bookName))); ?>'>

            <div class="mb-3">
                <label class="form-label">Parole chiave esistenti:</label>
                <div id="keywords-list" class="d-flex flex-wrap gap-2">
                    <!-- Le parole chiave verranno inserite qui da JS -->
                </div>
            </div>

            <hr>

            <!-- Form per aggiungere una nuova parola chiave -->
            <div id="add-keyword-form">
                <div class="row g-2">
                    <div class="col-md">
                        <label for="new-keyword" class="form-label">Nuova Parola</label>
                        <input type="text" id="new-keyword" class="form-control">
                    </div>
                    <div class="col-md">
                        <label for="new-keyword-desc" class="form-label">Descrizione</label>
                        <input type="text" id="new-keyword-desc" class="form-control">
                    </div>
                    <div class="col-md-auto d-flex align-items-end">
                        <button type="button" id="add-keyword-btn" class="btn btn-secondary">Aggiungi</button>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary mt-4">Salva Modifiche Parole Chiave</button>
        </form>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    // === GESTIONE PAROLE CHIAVE (codice esistente) ===
    // ...

    // === GESTIONE RIASSUNTI ===
    const summariesJsonInput = document.getElementById('summaries-json-input');
    const summariesAccordion = document.getElementById('summaries-accordion');
    const summaryModal = new bootstrap.Modal(document.getElementById('summary-modal'));
    const summaryModalTitle = document.getElementById('summary-modal-title');
    const saveSummaryBtn = document.getElementById('save-summary-btn');
    
    const summaryCategoryInput = document.getElementById('summary-category');
    const summaryTitleInput = document.getElementById('summary-title');
    const summaryContentInput = document.getElementById('summary-content');
    const summaryEditCatIndex = document.getElementById('summary-edit-category-index');
    const summaryEditItemIndex = document.getElementById('summary-edit-item-index');

    let summaries = JSON.parse(summariesJsonInput.value || '{}');

    const renderSummaries = () => {
        summariesAccordion.innerHTML = '';
        let categoryIndex = 0;
        for (const category in summaries) {
            const catId = `cat-${categoryIndex}`;
            const accordionItem = document.createElement('div');
            accordionItem.className = 'accordion-item';
            accordionItem.innerHTML = `
                <h2 class="accordion-header" id="heading-${catId}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${catId}">
                        ${category}
                    </button>
                </h2>
                <div id="collapse-${catId}" class="accordion-collapse collapse" data-bs-parent="#summaries-accordion">
                    <div class="accordion-body">
                        <ul class="list-group">
                            ${summaries[category].map((item, itemIndex) => `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${item.title}
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary edit-summary-btn" data-cat-idx="${category}" data-item-idx="${itemIndex}">Modifica</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-summary-btn" data-cat-idx="${category}" data-item-idx="${itemIndex}">Elimina</button>
                                    </div>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                </div>`;
            summariesAccordion.appendChild(accordionItem);
            categoryIndex++;
        }
        summariesJsonInput.value = JSON.stringify(summaries);
    };

    document.getElementById('add-summary-btn-main').addEventListener('click', () => {
        summaryModalTitle.textContent = 'Aggiungi Riassunto';
        summaryEditCatIndex.value = '';
        summaryEditItemIndex.value = '';
        document.getElementById('summaries-form').reset(); // Pulisce il form della modale
    });

    summariesAccordion.addEventListener('click', e => {
        if (e.target.matches('.edit-summary-btn')) {
            const cat = e.target.dataset.catIdx;
            const itemIdx = e.target.dataset.itemIdx;
            const summary = summaries[cat][itemIdx];
            
            summaryModalTitle.textContent = 'Modifica Riassunto';
            summaryEditCatIndex.value = cat;
            summaryEditItemIndex.value = itemIdx;
            
            summaryCategoryInput.value = cat;
            summaryTitleInput.value = summary.title;
            summaryContentInput.value = summary.content;
            
            summaryModal.show();
        }
        if (e.target.matches('.delete-summary-btn')) {
            const cat = e.target.dataset.catIdx;
            const itemIdx = e.target.dataset.itemIdx;
            if(confirm('Sei sicuro di voler eliminare questo riassunto?')) {
                summaries[cat].splice(itemIdx, 1);
                if (summaries[cat].length === 0) {
                    delete summaries[cat];
                }
                renderSummaries();
            }
        }
    });

    saveSummaryBtn.addEventListener('click', () => {
        const cat = summaryCategoryInput.value.trim() || 'uncategorized';
        const title = summaryTitleInput.value.trim();
        const content = summaryContentInput.value.trim();
        const editCat = summaryEditCatIndex.value;
        const editIdx = summaryEditItemIndex.value;

        if (!title || !content) return;

        const newItem = { title, content };

        if (editCat !== '' && editIdx !== '') { // Modalità Modifica
            // Rimuovi il vecchio
            summaries[editCat].splice(editIdx, 1);
            if (summaries[editCat].length === 0) delete summaries[editCat];
        }
        
        // Aggiungi il nuovo (o quello modificato)
        if (!summaries[cat]) summaries[cat] = [];
        summaries[cat].push(newItem);

        renderSummaries();
        summaryModal.hide();
    });

    renderSummaries();

    // ... (altro JS)
});
</script>

<?php
include __DIR__ . '/../templates/footer.php';
?>