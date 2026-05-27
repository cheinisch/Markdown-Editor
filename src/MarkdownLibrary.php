<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor;

/**
 * MarkdownLibrary
 *
 * Server-seitiger Handler für die Bildbibliothek des Markdown-Editors.
 * Stellt eine JSON-API für Verzeichnis-Listing und Datei-Upload bereit.
 *
 * Integration (z. B. in index.php):
 *
 *   if (isset($_GET['md-library'])) {
 *       MarkdownLibrary::handleRequest([
 *           'dirs' => [
 *               __DIR__ . '/uploads'      => '/uploads',
 *               __DIR__ . '/assets/img'   => '/assets/img',
 *           ],
 *           'upload'      => true,
 *           'upload_dir'  => __DIR__ . '/uploads',
 *           'upload_url'  => '/uploads',
 *           'allow_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
 *       ]);
 *       exit;
 *   }
 */
final class MarkdownLibrary
{
    private const DEFAULT_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    // ------------------------------------------------------------------ //
    // Öffentliche API                                                      //
    // ------------------------------------------------------------------ //

    /**
     * Nimmt den eingehenden Request entgegen und gibt eine JSON-Antwort aus.
     *
     * @param array{
     *   dirs:         array<string, string>,
     *   upload?:      bool,
     *   upload_dir?:  string,
     *   upload_url?:  string,
     *   allow_types?: list<string>
     * } $config
     *
     * dirs-Format: [Dateisystempfad => öffentliche URL]
     *   z. B. [ __DIR__ . '/uploads' => '/uploads' ]
     */
    public static function handleRequest(array $config): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        $action     = $_GET['action'] ?? 'list';
        $uploadOn   = (bool) ($config['upload'] ?? false);

        try {
            switch ($action) {
                case 'list':
                    echo json_encode(self::listFiles($config), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;

                case 'upload':
                    if (!$uploadOn) {
                        http_response_code(403);
                        echo json_encode(['ok' => false, 'error' => 'Upload is disabled.']);
                        break;
                    }
                    echo json_encode(self::handleUpload($config), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['ok' => false, 'error' => "Unknown action: {$action}"]);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------ //
    // Listing                                                              //
    // ------------------------------------------------------------------ //

    /**
     * Scannt alle konfigurierten Verzeichnisse und gibt eine strukturierte
     * Liste zurück, die das JS für die Ordner-Navigation nutzt.
     *
     * @return array{dirs: list<array{name: string, url_prefix: string, files: list<array{name: string, url: string, rel_path: string, size: int, width: int|null, height: int|null}>}>}
     */
    private static function listFiles(array $config): array
    {
        $dirs       = $config['dirs']        ?? [];
        $allowTypes = $config['allow_types'] ?? self::DEFAULT_TYPES;
        $result     = [];

        foreach ($dirs as $fsPath => $urlPrefix) {
            $fsPath    = rtrim((string) $fsPath, '/\\');
            $urlPrefix = rtrim((string) $urlPrefix, '/');

            if (!is_dir($fsPath) || !is_readable($fsPath)) {
                continue;
            }

            $files = self::scanDirectory($fsPath, $urlPrefix, $allowTypes);

            $result[] = [
                'name'       => basename($fsPath),
                'url_prefix' => $urlPrefix,
                'files'      => $files,
            ];
        }

        return ['dirs' => $result];
    }

    /**
     * Rekursiver Verzeichnis-Scanner.
     *
     * @param  list<string> $allowTypes
     * @return list<array{name: string, url: string, rel_path: string, size: int, width: int|null, height: int|null}>
     */
    private static function scanDirectory(string $baseDir, string $urlPrefix, array $allowTypes): array
    {
        $files    = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());

            if (!in_array($ext, $allowTypes, true)) {
                continue;
            }

            $absPath  = $file->getRealPath();
            $relPath  = str_replace('\\', '/', substr($absPath, strlen($baseDir) + 1));
            $url      = $urlPrefix . '/' . $relPath;

            // Bilddimensionen (nur für Raster-Formate)
            $width = $height = null;
            if ($ext !== 'svg' && function_exists('getimagesize')) {
                $info = @getimagesize($absPath);
                if ($info) {
                    [$width, $height] = $info;
                }
            }

            $files[] = [
                'name'     => $file->getFilename(),
                'url'      => $url,
                'rel_path' => $relPath,
                'size'     => (int) $file->getSize(),
                'width'    => $width,
                'height'   => $height,
            ];
        }

        // Alphabetisch sortieren
        usort($files, fn ($a, $b) => strnatcasecmp($a['rel_path'], $b['rel_path']));

        return $files;
    }

    // ------------------------------------------------------------------ //
    // Upload                                                               //
    // ------------------------------------------------------------------ //

    /**
     * Verarbeitet einen Datei-Upload.
     *
     * @return array{ok: bool, name?: string, url?: string, error?: string}
     */
    private static function handleUpload(array $config): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['ok' => false, 'error' => 'POST required.'];
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['file']['error'] ?? -1;
            return ['ok' => false, 'error' => "Upload error code: {$code}"];
        }

        $uploadDir  = rtrim((string) ($config['upload_dir']  ?? ''), '/\\');
        $uploadUrl  = rtrim((string) ($config['upload_url']  ?? ''), '/');
        $allowTypes = $config['allow_types'] ?? self::DEFAULT_TYPES;

        if ($uploadDir === '') {
            return ['ok' => false, 'error' => 'upload_dir not configured.'];
        }

        $file = $_FILES['file'];

        // Erweiterung prüfen
        $ext = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowTypes, true)) {
            return ['ok' => false, 'error' => "File type .{$ext} is not allowed."];
        }

        // MIME-Typ prüfen
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return ['ok' => false, 'error' => "MIME type {$mime} is not allowed."];
        }

        // Verzeichnis anlegen falls nötig
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return ['ok' => false, 'error' => 'Could not create upload directory.'];
        }

        // Dateiname bereinigen + Kollisionen vermeiden
        $filename = self::sanitizeFilename($file['name']);
        $dest     = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        // Bei Namenskollision Suffix anhängen
        if (file_exists($dest)) {
            $name     = pathinfo($filename, PATHINFO_FILENAME);
            $filename = $name . '_' . time() . '.' . $ext;
            $dest     = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'error' => 'Could not move uploaded file.'];
        }

        return [
            'ok'   => true,
            'name' => $filename,
            'url'  => $uploadUrl !== '' ? $uploadUrl . '/' . $filename : null,
        ];
    }

    // ------------------------------------------------------------------ //
    // Hilfsmethoden                                                        //
    // ------------------------------------------------------------------ //

    /** Bereinigt einen Dateinamen auf sichere ASCII-Zeichen. */
    private static function sanitizeFilename(string $name): string
    {
        $name = mb_strtolower($name);
        $name = (string) preg_replace('/\s+/', '-', $name);
        $name = (string) preg_replace('/[^a-z0-9._-]/', '', $name);
        $name = trim($name, '.-');

        return $name ?: 'upload';
    }
}