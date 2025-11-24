<?php
session_start();
require_once __DIR__ . '/../src/bookmarks.php';

header('Content-Type: application/json');

// --- API per la gestione dei segnalibri ---

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso negato']);
    exit;
}

$username = $_SESSION['username'];
$action = null;
$requestData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);
    $action = $requestData['action'] ?? null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestData = $_GET;
    $action = $requestData['action'] ?? null;
}

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Azione mancante']);
    exit;
}

switch ($action) {
    case 'add':
        // (codice esistente per 'add' - non modificato)
        break;
        
    case 'get':
        $bookId = $requestData['book_id'] ?? null;
        if (!$bookId) {
            http_response_code(400);
            echo json_encode(['error' => 'Book ID mancante']);
            exit;
        }
        $allBookmarks = getBookmarks($username);
        $bookBookmarks = $allBookmarks[$bookId] ?? [];
        echo json_encode($bookBookmarks);
        break;

    case 'delete':
        $bookId = $requestData['book_id'] ?? null;
        $chapter = $requestData['chapter'] ?? null;
        $index = $requestData['index'] ?? null;

        if (!$bookId || !$chapter || $index === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati incompleti per la cancellazione']);
            exit;
        }

        $bookmarks = getBookmarks($username);
        if (isset($bookmarks[$bookId][$chapter][$index])) {
            array_splice($bookmarks[$bookId][$chapter], (int)$index, 1);
            // Re-index array to prevent issues if a middle element is removed
            $bookmarks[$bookId][$chapter] = array_values($bookmarks[$bookId][$chapter]);
            
            if (saveBookmarks($username, $bookmarks)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Impossibile salvare le modifiche.']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Segnalibro non trovato.']);
        }
        break;

    case 'update':
        $bookId = $requestData['book_id'] ?? null;
        $chapter = $requestData['chapter'] ?? null;
        $index = $requestData['index'] ?? null;
        $newName = $requestData['name'] ?? null;

        if (!$bookId || !$chapter || $index === null || !$newName) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati incompleti per l\'aggiornamento']);
            exit;
        }

        $bookmarks = getBookmarks($username);
        if (isset($bookmarks[$bookId][$chapter][$index])) {
            $bookmarks[$bookId][$chapter][(int)$index]['name'] = htmlspecialchars($newName);
            
            if (saveBookmarks($username, $bookmarks)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Impossibile salvare le modifiche.']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Segnalibro non trovato.']);
        }
        break;

    // Altri casi (delete, update) da implementare
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Azione non valida']);
        break;
}

