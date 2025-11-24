<?php
session_start();
require_once __DIR__ . '/../src/books.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/../templates/header.php';

$userBooks = getBooksForUser($_SESSION['username']);

if (empty($userBooks)):
?>
    <div class="alert alert-info">Non hai ancora creato nessun libro.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($userBooks as $book):
            $bookData = getBook($_SESSION['username'], $book);
            $bookTitle = htmlspecialchars($bookData['title'] ?? $book);
            $bookLink = "book.php?user=" . urlencode($_SESSION['username']) . "&book=" . urlencode($book);
            $coverImage = $bookData['cover_image'] ?? null;
            $assetPath = $coverImage ? 'users/' . $_SESSION['username'] . '/' . $book . '/' . $coverImage : null;
            $fsPath = $coverImage ? 'data/users/' . $_SESSION['username'] . '/' . $book . '/' . $coverImage : null;
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
include __DIR__ . '/../templates/footer.php';
?>
