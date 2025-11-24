<?php

// --- Funzioni per la gestione dei libri ---

/**
 * Restituisce un elenco di tutti i libri posseduti da un utente.
 *
 * @param string $username
 * @return array Elenco dei nomi dei libri.
 */
function getBooksForUser(string $username): array
{
    $userBooksDir = __DIR__ . '/../data/users/' . $username;
    $books = [];

    if (!is_dir($userBooksDir)) {
        return [];
    }

    $items = scandir($userBooksDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'profile.json') {
            continue;
        }
        if (is_dir($userBooksDir . '/' . $item)) {
            $books[] = $item;
        }
    }

    return $books;
}

/**
 * Recupera i metadati di un libro specifico.
 *
 * @param string $username
 * @param string $bookName
 * @return array|null
 */
function getBook(string $username, string $bookName): ?array
{
    $bookPath = __DIR__ . '/../data/users/' . $username . '/' . $bookName . '/book.json';

    if (!file_exists($bookPath)) {
        return null;
    }

    $bookData = file_get_contents($bookPath);
    if ($bookData === false) {
        return null;
    }

    return json_decode($bookData, true);
}

/**
 * Recupera il contenuto di un capitolo specifico.
 *
 * @param string $username
 * @param string $bookName
 * @param int $chapterNumber
 * @return string|null
 */
function getChapter(string $username, string $bookName, int $chapterNumber): ?string
{
    $chapterPath = __DIR__ . '/../data/users/' . $username . '/' . $bookName . '/chapters/' . $chapterNumber . '.md';

    if (!file_exists($chapterPath)) {
        return null;
    }

    return file_get_contents($chapterPath);
}

/**
 * Recupera l'elenco dei file dei capitoli per un libro.
 *
 * @param string $username
 * @param string $bookName
 * @return array
 */
function getChapters(string $username, string $bookName): array
{
    $chaptersPath = __DIR__ . '/../data/users/' . $username . '/' . $bookName . '/chapters';
    $chapters = [];

    if (!is_dir($chaptersPath)) {
        return [];
    }

    $files = scandir($chaptersPath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $chapters[] = $file;
        }
    }
    
    natsort($chapters); // Ordina i capitoli in modo naturale (1, 2, 10)

    return array_values($chapters);
}

/**
 * Recupera le parole chiave e le loro descrizioni per un libro.
 *
 * @param string $username
 * @param string $bookName
 * @return array
 */
function getKeywords(string $username, string $bookName): array
{
    $keywordsPath = __DIR__ . '/../data/users/' . $username . '/' . $bookName . '/keywords.json';

    if (!file_exists($keywordsPath)) {
        return [];
    }

    $keywordsData = file_get_contents($keywordsPath);
    if ($keywordsData === false) {
        return [];
    }

    $keywords = json_decode($keywordsData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }

    return $keywords;
}

function updateChapter(string $username, string $bookName, int $chapterNumber, string $content): bool
{
    $chapterPath = __DIR__ . '/../data/users/' . $username . '/' . $bookName . '/chapters/' . $chapterNumber . '.md';
    
    // Assicurati che la cartella esista
    if (!is_dir(dirname($chapterPath))) {
        return false;
    }

    if (file_put_contents($chapterPath, $content) === false) {
        return false;
    }

    return true;
}

function updateKeywords(string $username, string $bookName, array $keywords): bool
{
    $keywordsPath = __DIR__ . '/../data/users/' . $username . '/' . $bookName . '/keywords.json';
    
    $json_data = json_encode($keywords, JSON_PRETTY_PRINT);
    if ($json_data === false) {
        return false;
    }

    if (file_put_contents($keywordsPath, $json_data) === false) {
        return false;
    }

    return true;
}

function getSummaries(string $username, string $bookName): array
{
    $summariesPath = __DIR__ . '/../data/users/' . $username . '/' . $bookName . '/summaries.json';

    if (!file_exists($summariesPath)) {
        return [];
    }

    $summariesData = file_get_contents($summariesPath);
    if ($summariesData === false) {
        return [];
    }

    $summaries = json_decode($summariesData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }

    return $summaries;
}

function updateSummaries(string $username, string $bookName, array $summaries): bool
{
    $summariesPath = __DIR__ . '/../data/users/' . $username . '/' . $bookName . '/summaries.json';
    
    $json_data = json_encode($summaries, JSON_PRETTY_PRINT);
    if ($json_data === false) {
        return false;
    }

    if (file_put_contents($summariesPath, $json_data) === false) {
        return false;
    }

    return true;
}

function getAllPublicBooks(): array
{
    $allPublicBooks = [];
    $usersDir = __DIR__ . '/../data/users';
    
    if (!is_dir($usersDir)) {
        return [];
    }

    $users = scandir($usersDir);
    foreach ($users as $user) {
        if ($user === '.' || $user === '..') continue;
        
        $userPath = $usersDir . '/' . $user;
        if (is_dir($userPath)) {
            $books = getBooksForUser($user);
            foreach ($books as $bookName) {
                $bookData = getBook($user, $bookName);
                if ($bookData && ($bookData['is_public'] ?? false)) {
                    // Aggiungi info utili per creare i link
                    $bookData['book_name'] = $bookName;
                    $bookData['owner'] = $user;
                    $allPublicBooks[] = $bookData;
                }
            }
        }
    }

    return $allPublicBooks;
}

// Altre funzioni (createBook, updateBook, etc.) verranno aggiunte in seguito.
