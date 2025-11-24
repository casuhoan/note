<?php
session_start();
require_once __DIR__ . '/../src/lock.php';

// API per rilasciare il lock

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// sendBeacon invia i dati come FormData, non JSON
$action = $_POST['action'] ?? null;
$bookPath = $_POST['book_path'] ?? null;

if ($action === 'release' && $bookPath) {
    // Aggiungi un ulteriore controllo per assicurarti che solo il proprietario del lock possa rilasciarlo
    $lockData = json_decode(@file_get_contents($bookPath . '/edit.lock'), true);
    
    if ($lockData && $lockData['user_id'] === $_SESSION['user_id']) {
        releaseLock($bookPath);
    }
    // Non inviare nessuna risposta, sendBeacon non la elabora
}
