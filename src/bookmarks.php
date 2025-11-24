<?php

// --- Funzioni per la gestione dei segnalibri ---

/**
 * Recupera tutti i segnalibri di un utente.
 *
 * @param string $username
 * @return array
 */
function getBookmarks(string $username): array
{
    $bookmarksPath = __DIR__ . '/../data/users/' . $username . '/bookmarks.json';

    if (!file_exists($bookmarksPath)) {
        return [];
    }

    $bookmarksData = file_get_contents($bookmarksPath);
    if ($bookmarksData === false) {
        return [];
    }

    $bookmarks = json_decode($bookmarksData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }

    return $bookmarks;
}

/**
 * Salva i dati dei segnalibri per un utente.
 *
 * @param string $username
 * @param array $bookmarks
 * @return bool
 */
function saveBookmarks(string $username, array $bookmarks): bool
{
    $userDir = __DIR__ . '/../data/users/' . $username;
    if (!is_dir($userDir)) {
        // Prova a creare la cartella se non esiste, anche se dovrebbe esistere
        if (!mkdir($userDir, 0755, true)) {
            return false;
        }
    }
    
    $bookmarksPath = $userDir . '/bookmarks.json';
    $json_data = json_encode($bookmarks, JSON_PRETTY_PRINT);

    if (file_put_contents($bookmarksPath, $json_data) === false) {
        return false;
    }

    return true;
}

// Le funzioni add, update, delete verranno aggiunte qui
// e richiamate da un file API dedicato (es. api/bookmark_handler.php)

