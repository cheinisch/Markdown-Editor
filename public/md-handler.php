<?php
declare(strict_types=1);

/**
 * Integrierter Handler für die Markdown-Editor Bildbibliothek.
 *
 * Diese Datei wird automatisch genutzt wenn in render() fsPath angegeben wird.
 * Nicht manuell aufrufen oder verändern.
 *
 * GET  ?dir=<key> → JSON-Liste der Dateien im Verzeichnis
 * POST ?dir=<key> → Datei hochladen (FormData: 'file')
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$key = $_GET['dir'] ?? '';
$dir = $_SESSION['_md_dirs'][$key] ?? null;

if (!$dir || empty($dir['fs'])) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode(['error' => 'Unbekanntes Verzeichnis.']);
    exit;
}

// MarkdownEditor laden (Composer oder direkt)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../src/MarkdownEditor.php';
}

use cheinisch\MarkdownEditor\MarkdownEditor;

MarkdownEditor::handleRequest(
    fsDirectory: $dir['fs'],
    webPath:     $dir['web'],
    options:     $dir['options'] ?? [],
);