<?php

// --- Funzioni per la gestione degli utenti ---

/**
 * Restituisce i dati di un utente cercandolo per username.
 *
 * @param string $username L'username da cercare.
 * @return array|null I dati dell'utente come array associativo o null se non trovato.
 */
function getUserByUsername(string $username): ?array
{
    $userDir = __DIR__ . '/../data/users/' . $username;
    $profilePath = $userDir . '/profile.json';

    if (!is_dir($userDir) || !file_exists($profilePath)) {
        return null;
    }

    $profileData = file_get_contents($profilePath);
    if ($profileData === false) {
        // Potrebbe essere utile loggare l'errore
        return null;
    }

    $userData = json_decode($profileData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Potrebbe essere utile loggare l'errore
        return null;
    }

    return $userData;
}

// Altre funzioni (createUser, updateUser, etc.) verranno aggiunte in seguito.
