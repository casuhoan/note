<?php
// Script proxy per servire file dalla cartella /data in modo sicuro

$basePath = __DIR__ . '/../data/';
$requestedPath = $_GET['path'] ?? '';

// --- Validazione di sicurezza ---

// 1. Assicurati che il percorso non sia vuoto
if (empty($requestedPath)) {
    http_response_code(400);
    exit('Path mancante.');
}

// 2. Normalizza il percorso per risolvere i segmenti '..' e './'
$realRequestedPath = realpath($basePath . $requestedPath);
$realBasePath = realpath($basePath);

// 3. Controlla che il percorso richiesto sia effettivamente DENTRO la cartella /data
// Questo previene la directory traversal (es. /asset.php?path=../../.env)
if ($realRequestedPath === false || strpos($realRequestedPath, $realBasePath) !== 0) {
    http_response_code(403);
    exit('Accesso non autorizzato.');
}

// 4. Controlla che il file esista
if (!file_exists($realRequestedPath) || is_dir($realRequestedPath)) {
    http_response_code(404);
    exit('File non trovato.');
}


// --- Servi il file ---

// Determina il Content-Type in base all'estensione
$extension = strtolower(pathinfo($realRequestedPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
];
$contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

// Servi il file
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($realRequestedPath));
readfile($realRequestedPath);
exit;
