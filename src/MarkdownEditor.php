<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor;

final class MarkdownEditor
{
    private const ALL_BUTTONS = [
        'bold', 'italic', 'underline',
        'h1', 'list', 'quote', 'code', 'link', 'table',
        'library',
    ];

    private const GROUP1 = ['bold', 'italic', 'underline'];
    private const GROUP2 = ['h1', 'list', 'quote', 'code', 'link', 'table'];

    // ── Public API ───────────────────────────────────────────────────────────

    public static function renderHead(): string
    {
        return <<<'HTML'
<script src="https://cdn.tailwindcss.com?plugins=typography"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.7/dist/purify.min.js"></script>
HTML;
    }

    /**
     * Rendert den Markdown-Editor.
     *
     * @param array{
     *   buttons?: list<string>,
     *   localStorage?: bool|string,
     *   field?: string,
     *   library?: list<array{name: string, path: string, upload?: bool}>,
     * } $options
     *
     *   - buttons:      Sichtbare Toolbar-Buttons. Mögliche Werte:
     *                   'bold', 'italic', 'underline', 'h1', 'list',
     *                   'quote', 'code', 'link', 'library'. Standard: alle.
     *
     *   - localStorage: Inhalt in localStorage speichern.
     *                   true   → Key 'markdown-editor-content'
     *                   string → Eigener Key | false → aus (Standard)
     *
     *   - field:        ID einer <textarea> zum Synchronisieren.
     *                   Existiert sie bereits im DOM, wird sie genutzt;
     *                   sonst legt der Editor sie selbst an.
     *
     *   - library:      Verzeichnisse für die Bildbibliothek.
     *                   path darf relativ zur einbindenden PHP-Datei sein.
     *                   upload: true → Drag & Drop + Upload-Button aktiv.
     *
     * Beispiele:
     *   <?= MarkdownEditor::render() ?>
     *   <?= MarkdownEditor::render(['buttons' => ['bold', 'italic', 'link']]) ?>
     *   <?= MarkdownEditor::render(['localStorage' => 'blog-editor']) ?>
     *   <?= MarkdownEditor::render(['field' => 'post_content']) ?>
     *   <?= MarkdownEditor::render(['library' => [
     *       ['name' => 'Alle Bilder', 'path' => '/api/images'],
     *       ['name' => 'Uploads',     'path' => '../../uploads', 'upload' => true],
     *   ]]) ?>
     */
    public static function render(array $options = []): string
    {
        $visible = array_flip($options['buttons'] ?? self::ALL_BUTTONS);

        $localStorageKey = match(true) {
            isset($options['localStorage']) && is_string($options['localStorage']) => $options['localStorage'],
            isset($options['localStorage']) && $options['localStorage'] === true   => 'markdown-editor-content',
            default => null,
        };

        $fieldId = isset($options['field']) && is_string($options['field']) && $options['field'] !== ''
            ? $options['field']
            : null;

        // Pfade relativ zur einbindenden Datei auflösen
        $callerFile  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];
        $libraryDirs = self::resolveLibraryPaths($options['library'] ?? [], $callerFile);
        $maxUpload   = self::maxUploadSize();

        $show = static fn(string $key): bool => isset($visible[$key]);

        $showSeparator = (
            array_filter(self::GROUP1, $show) !== [] &&
            array_filter(self::GROUP2, $show) !== []
        );

        ob_start(); ?>
        <div class="mx-auto max-w-7xl p-6">
            <section class="overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-slate-200">

                <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3">

                    <?php if ($show('bold')): ?>
                    <button type="button" id="btn-bold" data-action="bold" class="rounded-lg px-3 py-2 text-sm font-bold hover:bg-white">B</button>
                    <?php endif; ?>

                    <?php if ($show('italic')): ?>
                    <button type="button" id="btn-italic" data-action="italic" class="rounded-lg px-3 py-2 text-sm italic hover:bg-white">I</button>
                    <?php endif; ?>

                    <?php if ($show('underline')): ?>
                    <button type="button" id="btn-underline" data-action="underline" class="rounded-lg px-3 py-2 text-sm underline hover:bg-white">U</button>
                    <?php endif; ?>

                    <?php if ($showSeparator): ?>
                    <span class="mx-1 h-6 w-px bg-slate-300"></span>
                    <?php endif; ?>

                    <?php if ($show('h1')): ?>
                    <button type="button" id="btn-h1" data-action="h1" class="rounded-lg px-3 py-2 text-sm hover:bg-white">H1</button>
                    <?php endif; ?>

                    <?php if ($show('list')): ?>
                    <button type="button" id="btn-list" data-action="list" class="rounded-lg px-3 py-2 text-sm hover:bg-white">Liste</button>
                    <?php endif; ?>

                    <?php if ($show('quote')): ?>
                    <button type="button" id="btn-quote" data-action="quote" class="rounded-lg px-3 py-2 text-sm hover:bg-white">Zitat</button>
                    <?php endif; ?>

                    <?php if ($show('code')): ?>
                    <button type="button" id="btn-code" data-action="code" class="rounded-lg px-3 py-2 text-sm hover:bg-white">Code</button>
                    <?php endif; ?>

                    <?php if ($show('link')): ?>
                    <button type="button" id="btn-link" data-action="link" class="rounded-lg px-3 py-2 text-sm hover:bg-white">Link</button>
                    <?php endif; ?>

                    <?php if ($show('table')): ?>
                    <div class="relative">
                        <button type="button" id="btn-table" class="rounded-lg px-3 py-2 text-sm hover:bg-white">Tabelle</button>
                        <div id="tablePopover" class="absolute left-0 top-full z-30 mt-2 hidden w-60 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-slate-200">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Tabelle einfügen</p>
                            <div class="mb-3 grid grid-cols-2 gap-2">
                                <label class="flex flex-col gap-1 text-xs text-slate-500">
                                    Spalten
                                    <input type="number" id="tableCols" value="3" min="1" max="10"
                                        class="rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-blue-500">
                                </label>
                                <label class="flex flex-col gap-1 text-xs text-slate-500">
                                    Zeilen
                                    <input type="number" id="tableRows" value="2" min="1" max="20"
                                        class="rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-blue-500">
                                </label>
                            </div>
                            <button type="button" id="tableInsert"
                                class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                Einfügen
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button
                        type="button"
                        id="togglePreview"
                        class="ml-auto rounded-xl bg-white px-4 py-2 text-sm font-semibold ring-1 ring-slate-200 hover:bg-slate-50"
                    >
                        Vorschau anzeigen
                    </button>

                    <?php if ($show('library')): ?>
                    <button
                        type="button"
                        id="openLibrary"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                    >
                        Bibliothek
                    </button>
                    <?php endif; ?>

                </div>

                <div id="editorLayout" class="grid min-h-[700px] grid-cols-1 lg:grid-cols-1">
                    <section class="flex flex-col">
                        <div class="border-b border-slate-100 px-5 py-3">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Editor</h2>
                        </div>

                        <textarea
                            id="editor"
                            class="min-h-[620px] resize-y overflow-auto p-5 font-mono text-sm leading-7 outline-none"
                            placeholder="Schreibe hier Markdown ..."
                            spellcheck="false"
                            <?= $localStorageKey !== null ? 'data-storage-key="' . htmlspecialchars($localStorageKey, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                            <?= $fieldId !== null       ? 'data-field-id="'    . htmlspecialchars($fieldId,         ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                        ></textarea>

                        <div id="stats" class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                            0 Wörter · 0 Zeichen · 0 Zeilen
                        </div>
                    </section>

                    <section id="previewPanel" class="hidden flex-col bg-white">
                        <div class="border-b border-slate-100 px-5 py-3">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Vorschau</h2>
                        </div>
                        <article id="preview" class="prose prose-slate max-w-none p-8"></article>
                    </section>
                </div>
            </section>
        </div>

        <?php if ($fieldId !== null): ?>
        <textarea data-md-sync hidden></textarea>
        <?php endif; ?>

        <?php if ($show('library')): ?>
        <div
            id="libraryBackdrop"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
            data-library="<?= htmlspecialchars(json_encode($libraryDirs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
            data-max-upload="<?= htmlspecialchars($maxUpload, ENT_QUOTES, 'UTF-8') ?>"
        >
            <section class="flex max-h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
                <header class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-bold">Bildbibliothek</h2>
                        <p class="text-xs text-slate-500">Wähle ein Bild aus der Bibliothek.</p>
                    </div>

                    <input
                        id="librarySearch"
                        class="ml-auto w-64 rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-blue-500"
                        placeholder="Bilder suchen ..."
                    >

                    <button
                        type="button"
                        id="closeLibrary"
                        class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                    >
                        ✕
                    </button>
                </header>

                <div class="grid min-h-[460px] flex-1 grid-cols-1 overflow-hidden md:grid-cols-[220px_1fr]">
                    <aside class="border-b border-slate-200 bg-slate-50 p-4 md:border-b-0 md:border-r">
                        <nav id="libraryNav" class="space-y-1 text-sm"></nav>
                    </aside>

                    <div id="libraryGridWrapper" class="relative overflow-y-auto p-5">
                        <div id="libraryGrid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"></div>
                        <input id="libraryFileInput" type="file" accept="image/*" multiple class="sr-only">
                        <div id="libraryDropOverlay" class="pointer-events-none absolute inset-0 hidden items-center justify-center rounded-2xl border-2 border-dashed border-blue-400 bg-blue-50/80">
                            <div class="text-center">
                                <p class="text-base font-semibold text-blue-600">Bilder hier ablegen</p>
                                <p class="text-sm text-blue-400">JPG, PNG, GIF, WebP</p>
                            </div>
                        </div>
                    </div>
                </div>

                <footer class="flex items-center gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="flex items-baseline gap-2">
                        <span id="librarySelectionCount" class="text-sm text-slate-500">0 Bilder ausgewählt</span>
                        <span id="libraryMaxUpload" class="hidden text-xs text-slate-400"></span>
                    </div>

                    <button
                        type="button"
                        id="cancelLibrary"
                        class="ml-auto rounded-xl px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                    >
                        Abbrechen
                    </button>

                    <button
                        type="button"
                        data-action="insert-image"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-40"
                        disabled
                    >
                        Einfügen
                    </button>
                </footer>
            </section>
        </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Kombinierter GET/POST-Handler für Bildbibliothek-Endpunkte.
     *
     * GET  → gibt JSON-Array der Bilder zurück
     * POST → speichert hochgeladene Datei, gibt Bild-Objekt zurück
     *
     * Nutzung (z. B. in /api/images.php):
     *   MarkdownEditor::handleRequest(
     *       fsDirectory: __DIR__ . '/../../uploads/images',
     *       webPath:     '/uploads/images'
     *   );
     *
     * @param string $fsDirectory Absoluter Dateisystempfad zum Bildordner
     * @param string $webPath     Web-URL-Präfix für Bild-src-Attribute
     * @param array{types?: list<string>, maxSize?: int, listExtensions?: list<string>} $options
     *   - types:          Erlaubte MIME-Types beim Upload (Standard: gängige Bildformate)
     *   - maxSize:        Max. Dateigröße in Bytes (Standard: PHP upload_max_filesize)
     *   - listExtensions: Dateierweiterungen die gelistet werden (Standard: Bildformate).
     *                     Rekursive Suche wird per ?recursive=1 vom Client aktiviert.
     */
    public static function handleRequest(
        string $fsDirectory,
        string $webPath,
        array  $options = [],
    ): never {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        try {
            match ($_SERVER['REQUEST_METHOD'] ?? 'GET') {
                'POST'    => self::handleUploadRequest($fsDirectory, $webPath, $options),
                'GET'     => self::handleListRequest($fsDirectory, $webPath, $options),
                default   => self::jsonError(405, 'Method not allowed'),
            };
        } catch (\Throwable $e) {
            self::jsonError(500, $e->getMessage());
        }

        exit;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private static function handleListRequest(string $fsDir, string $webPath, array $options = []): void
    {
        if (!is_dir($fsDir)) {
            self::jsonError(404, 'Verzeichnis existiert nicht.');
            return;
        }

        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];
        $exts      = array_map('strtolower', $options['listExtensions'] ?? $imageExts);
        $recursive = !empty($_GET['recursive']);
        $files     = [];
        $fsDir     = rtrim($fsDir, '/\\');

        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fsDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                $ext = strtolower($file->getExtension());
                if (!in_array($ext, $exts, true)) continue;

                $rel   = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($fsDir)));
                $isImg = in_array($ext, $imageExts, true);
                $entry = [
                    'src'   => rtrim($webPath, '/') . $rel,
                    'name'  => $file->getFilename(),
                    'type'  => $isImg ? 'image' : 'file',
                    'mtime' => $file->getMTime(),
                ];
                if ($isImg) $entry += self::imageSize($file->getPathname());
                $files[] = $entry;
            }
        } else {
            $pattern = $fsDir . DIRECTORY_SEPARATOR . '*.{' . implode(',', $exts) . '}';
            foreach (glob($pattern, GLOB_BRACE) ?: [] as $path) {
                $ext   = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $isImg = in_array($ext, $imageExts, true);
                $entry = [
                    'src'   => rtrim($webPath, '/') . '/' . basename($path),
                    'name'  => basename($path),
                    'type'  => $isImg ? 'image' : 'file',
                    'mtime' => filemtime($path),
                ];
                if ($isImg) $entry += self::imageSize($path);
                $files[] = $entry;
            }
        }

        usort($files, static fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        echo json_encode(array_map(static fn($f) => array_diff_key($f, ['mtime' => 0]), $files));
    }

    private static function imageSize(string $path): array
    {
        [$w, $h] = @getimagesize($path) ?: [null, null];
        return ['width' => $w, 'height' => $h];
    }

    private static function handleUploadRequest(string $fsDir, string $webPath, array $options): void
    {
        if (empty($_FILES['file'])) {
            self::jsonError(400, 'Keine Datei empfangen.');
            return;
        }

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            self::jsonError(400, 'Upload-Fehler (Code ' . $file['error'] . ').');
            return;
        }

        // MIME-Typ prüfen
        $allowed  = $options['types'] ?? ['image/jpeg','image/png','image/gif','image/webp','image/avif'];
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowed, true)) {
            self::jsonError(415, 'Ungültiger Dateityp: ' . $mimeType);
            return;
        }

        // Größe prüfen
        $maxBytes = $options['maxSize'] ?? self::iniSizeToBytes(ini_get('upload_max_filesize'));
        if ($file['size'] > $maxBytes) {
            self::jsonError(413, 'Datei überschreitet das Limit.');
            return;
        }

        // Verzeichnis anlegen
        if (!is_dir($fsDir) && !mkdir($fsDir, 0755, true)) {
            self::jsonError(500, 'Verzeichnis konnte nicht erstellt werden.');
            return;
        }

        // Dateiname bereinigen
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $stem     = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
        $filename = trim($stem, '-') . '_' . substr(uniqid(), -6) . '.' . $ext;
        $target   = rtrim($fsDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            self::jsonError(500, 'Datei konnte nicht gespeichert werden.');
            return;
        }

        [$width, $height] = @getimagesize($target) ?: [null, null];

        echo json_encode([
            'src'    => rtrim($webPath, '/') . '/' . $filename,
            'name'   => $filename,
            'width'  => $width,
            'height' => $height,
        ]);
    }

    private static function resolveLibraryPaths(array $dirs, string $callerFile): array
    {
        if (empty($dirs)) return [];

        $callerDir = dirname($callerFile);
        $docRoot   = rtrim(
            str_replace(DIRECTORY_SEPARATOR, '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''),
            '/'
        );

        return array_values(array_map(function (array $dir) use ($callerDir, $docRoot): array {
            $path = trim($dir['path'] ?? '');

            // Absoluter Web-Pfad oder URL → unverändert lassen
            if ($path === '' || str_starts_with($path, '/') || preg_match('#^https?://#', $path)) {
                return $dir;
            }

            // Relativen Pfad vom Caller-Verzeichnis auflösen
            $abs = realpath($callerDir . DIRECTORY_SEPARATOR . $path);
            if ($abs === false) return $dir;

            $abs = str_replace(DIRECTORY_SEPARATOR, '/', $abs);

            // In Web-Pfad umwandeln
            if ($docRoot !== '' && str_starts_with($abs, $docRoot)) {
                $dir['path'] = '/' . ltrim(substr($abs, strlen($docRoot)), '/');
            }

            return $dir;
        }, $dirs));
    }

    private static function maxUploadSize(): string
    {
        $upload = ini_get('upload_max_filesize') ?: '2M';
        $post   = ini_get('post_max_size')       ?: '8M';

        return self::iniSizeToBytes($upload) <= self::iniSizeToBytes($post) ? $upload : $post;
    }

    private static function iniSizeToBytes(string $val): int
    {
        $val  = trim($val);
        $unit = strtolower(substr($val, -1));
        $n    = (int) $val;

        return match ($unit) {
            'g'     => $n * 1_073_741_824,
            'm'     => $n * 1_048_576,
            'k'     => $n * 1_024,
            default => $n,
        };
    }

    private static function jsonError(int $status, string $message): void
    {
        http_response_code($status);
        echo json_encode(['error' => $message]);
    }

    public static function renderFoot(): string
    {
        $publicPath = self::detectPublicPath();

        return sprintf(
            '<script src="%s/markdown-editor.js"></script>',
            htmlspecialchars($publicPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    private static function detectPublicPath(): string
    {
        $publicDir = realpath(__DIR__ . '/../public');
        if ($publicDir === false) return '';

        $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        if ($documentRoot === false) return '';

        $publicDir    = str_replace(DIRECTORY_SEPARATOR, '/', $publicDir);
        $documentRoot = str_replace(DIRECTORY_SEPARATOR, '/', $documentRoot);

        if (!str_starts_with($publicDir, $documentRoot)) return '';

        return rtrim(substr($publicDir, strlen($documentRoot)), '/');
    }
}