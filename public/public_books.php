<?php
session_start();
require_once __DIR__ . '/../src/books.php';
include __DIR__ . '/../templates/header.php';
?>

<h1>Libri Pubblici</h1>
<p class="lead">Scopri i lavori condivisi dagli altri autori della piattaforma.</p>
<hr>

<?php
$publicBooks = getAllPublicBooks();

if (empty($publicBooks)):
?>
    <div class="alert alert-info">Al momento non ci sono libri pubblici.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($publicBooks as $bookData):
            $bookTitle = htmlspecialchars($bookData['title'] ?? 'Senza Titolo');
            $bookOwner = htmlspecialchars($bookData['owner']);
            $bookName = htmlspecialchars($bookData['book_name']);
            $bookLink = "book.php?user=" . urlencode($bookOwner) . "&book=" . urlencode($bookName);
            $coverImage = $bookData['cover_image'] ?? null;
            $assetPath = $coverImage ? 'users/' . $bookOwner . '/' . $bookName . '/' . $coverImage : null;
            $fsPath = $coverImage ? 'data/users/' . $bookOwner . '/' . $bookName . '/' . $coverImage : null;
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
                            <p class="card-text"><small class="text-muted">di <?php echo $bookOwner; ?></small></p>
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
