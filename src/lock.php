<?php

// --- Funzioni per la gestione del lock di modifica ---

define('LOCK_EXPIRATION_TIME', 300); // 5 minuti in secondi

/**
 * Controlla lo stato del lock su un libro.
 *
 * @param string $bookPath La cartella del libro.
 * @param string $currentUserId L'ID dell'utente corrente.
 * @return array ['status' => 'locked'|'expired'|'free', 'owner' => 'user_id'|null]
 */
function checkLock(string $bookPath, string $currentUserId): array
{
    $lockFile = $bookPath . '/edit.lock';

    if (!file_exists($lockFile)) {
        return ['status' => 'free', 'owner' => null];
    }

    $lockData = json_decode(file_get_contents($lockFile), true);

    if (!$lockData || !isset($lockData['user_id']) || !isset($lockData['timestamp'])) {
        // File di lock corrotto, consideralo libero
        return ['status' => 'free', 'owner' => null];
    }

    if ($lockData['user_id'] === $currentUserId) {
        return ['status' => 'locked', 'owner' => $currentUserId]; // È il nostro stesso lock
    }

    if (time() - $lockData['timestamp'] > LOCK_EXPIRATION_TIME) {
        return ['status' => 'expired', 'owner' => $lockData['user_id']];
    }

    return ['status' => 'locked', 'owner' => $lockData['user_id']];
}

/**
 * Acquisisce il lock per un utente.
 *
 * @param string $bookPath
 * @param string $userId
 * @return bool
 */
function acquireLock(string $bookPath, string $userId): bool
{
    $lockFile = $bookPath . '/edit.lock';
    $data = [
        'user_id' => $userId,
        'timestamp' => time()
    ];

    return file_put_contents($lockFile, json_encode($data)) !== false;
}

/**
 * Rilascia il lock.
 *
 * @param string $bookPath
 * @return bool
 */
function releaseLock(string $bookPath): bool
{
    $lockFile = $bookPath . '/edit.lock';

    if (file_exists($lockFile)) {
        return unlink($lockFile);
    }

    return true;
}
