<?php
session_start();
require_once __DIR__ . '/../src/books.php';
require_once __DIR__ . '/../src/lock.php'; // Potrebbe servire per verificare il lock prima di operare

header('Content-Type: application/json');

// --- API per la gestione dei capitoli ---

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso negato']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;
$username = $_SESSION['username'];
list($bookOwnerUsername, $bookName) = explode('/', $bookId, 2);

// --- Permission Check ---
// (Dovresti avere una funzione helper per questo, ma per ora lo facciamo qui)
$bookData = getBook($bookOwnerUsername, $bookName);
$isOwner = $username === $bookData['author'];
$isEditor = $isOwner || in_array($username, $bookData['authorized_editors'] ?? []);
if (!$isEditor) {
    http_response_code(403);
    echo json_encode(['error' => 'Non hai i permessi per modificare questo libro.']);
    exit;
}

$chaptersPath = __DIR__ . '/../data/users/' . $bookOwnerUsername . '/' . $bookName . '/chapters';

switch ($action) {
    case 'add':
        $chapters = getChapters($bookOwnerUsername, $bookName);
        $nextChapterNum = count($chapters) + 1;
        $newChapterPath = $chaptersPath . '/' . $nextChapterNum . '.md';
        if (file_put_contents($newChapterPath, '# Nuovo Capitolo') !== false) {
            echo json_encode(['success' => true, 'new_chapter' => $nextChapterNum]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Impossibile creare il nuovo capitolo.']);
        }
        break;

    case 'delete':
        $chapterNum = $data['chapter_num'] ?? null;
        if (!$chapterNum) { /* ... validazione ... */ }

        $chapterPath = $chaptersPath . '/' . $chapterNum . '.md';
        if (file_exists($chapterPath) && unlink($chapterPath)) {
            // Rinomina i file successivi per colmare il buco
            $chapters = getChapters($bookOwnerUsername, $bookName);
            for ($i = (int)$chapterNum; $i <= count($chapters); $i++) {
                $oldPath = $chaptersPath . '/' . ($i + 1) . '.md';
                $newPath = $chaptersPath . '/' . $i . '.md';
                if (file_exists($oldPath)) {
                    rename($oldPath, $newPath);
                }
            }
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Impossibile eliminare il capitolo.']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Azione non valida.']);
        break;
}

