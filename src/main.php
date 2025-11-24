<?php
session_start();
require_once __DIR__ . '/books.php';

// Se l'utente non è loggato, reindirizza alla pagina di login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include l'header
include __DIR__ . '/../templates/header.php';
$username = $_SESSION['username'];
?>

<!-- Hero Section -->
<div class="p-5 mb-4 bg-body-tertiary rounded-3">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold">Ciao, <?php echo htmlspecialchars($username); ?>!</h1>
        <p class="col-md-8 fs-4">Benvenuto nella tua libreria personale. Da qui puoi gestire i tuoi libri, creare nuovi capolavori e riprendere la lettura da dove l'avevi interrotta.</p>
        <button class="btn btn-primary btn-lg" type="button">Crea un nuovo libro</button>
    </div>
</div>

<!-- Sezione I tuoi Libri -->
<h2>I tuoi libri</h2>
<hr>

<?php
$userBooks = getBooksForUser($username);

if (empty($userBooks)):
?>
    <div class="alert alert-info">
        Non hai ancora creato nessun libro. Inizia subito dal pulsante qui sopra!
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($userBooks as $book):
            $bookData = getBook($username, $book);
            $bookTitle = htmlspecialchars($bookData['title'] ?? $book);
            $bookLink = "book.php?user=" . urlencode($username) . "&book=" . urlencode($book);
            $coverImage = $bookData['cover_image'] ?? null;
            // Correzione: il percorso deve essere relativo alla posizione del file php
            $assetPath = $coverImage ? 'users/' . $username . '/' . $book . '/' . $coverImage : null;
            $fsPath = $coverImage ? 'data/users/' . $username . '/' . $book . '/' . $coverImage : null;
        ?>
            <div class="col-md-4 col-lg-3 col-xl-2">
                <a href="<?php echo $bookLink; ?>" class="text-decoration-none text-reset">
                    <div class="card h-100 shadow-sm book-card">
                        <?php if ($fsPath && file_exists(__DIR__ . '/../' . $fsPath)): ?>
                            <img src="asset.php?path=<?php echo urlencode($assetPath); ?>" class="card-img-top" alt="Copertina di <?php echo $bookTitle; ?>">
                        <?php else: ?>
                            <div class="card-img-top book-card-placeholder d-flex align-items-center justify-content-center">
                                <span class="fs-5 text-white p-3 text-center"><?php echo $bookTitle; ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $bookTitle; ?></h5>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<?php
// Include il footer
include __DIR__ . '/../templates/footer.php';
