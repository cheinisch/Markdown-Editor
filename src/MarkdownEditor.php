<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor;

final class MarkdownEditor
{
    /**
     * Alle verfügbaren Button-Schlüssel in ihrer Standard-Reihenfolge.
     */
    private const ALL_BUTTONS = [
        'bold', 'italic', 'underline',
        'h1', 'list', 'quote', 'code', 'link',
        'library',
    ];

    /** Gruppe 1 (Formatierung): Trennlinie wird nur gerendert, wenn mind. ein Button
     *  aus Gruppe 1 UND mind. einer aus Gruppe 2 sichtbar ist. */
    private const GROUP1 = ['bold', 'italic', 'underline'];
    private const GROUP2 = ['h1', 'list', 'quote', 'code', 'link'];

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
     * @param array{buttons?: list<string>, localStorage?: bool|string} $options
     *   - buttons:      Welche Buttons angezeigt werden sollen.
     *                   Mögliche Werte: 'bold', 'italic', 'underline',
     *                   'h1', 'list', 'quote', 'code', 'link', 'library'.
     *                   Standard: alle Buttons.
     *   - localStorage: Inhalt automatisch in localStorage speichern.
     *                   true   → Default-Key 'markdown-editor-content'
     *                   string → Eigener Key
     *                   false  → deaktiviert (Standard)
     *   - field:        ID eines versteckten <textarea>-Feldes unterhalb des Editors.
     *                   Der Inhalt wird bei jeder Eingabe synchronisiert und beim
     *                   Laden der Seite von dort wiederhergestellt (z. B. für Formulare).
     *
     * Beispiele:
     *   <?= MarkdownEditor::render() ?>
     *   <?= MarkdownEditor::render(['buttons' => ['bold', 'italic', 'link']]) ?>
     *   <?= MarkdownEditor::render(['localStorage' => true]) ?>
     *   <?= MarkdownEditor::render(['localStorage' => 'blog-editor']) ?>
     *   <?= MarkdownEditor::render(['field' => 'post_content']) ?>
     *   <?= MarkdownEditor::render(['library' => [
     *       ['name' => 'Alle Bilder', 'path' => '/api/images'],
     *       ['name' => 'Blog',        'path' => '/api/images/blog', 'upload' => true],
     *       ['name' => 'Produkte',    'path' => '/api/images/products'],
     *   ]]) ?>
     *
     * Library-Endpunkt erwartet:
     *   GET  {path}  →  JSON: [{ src, name, width?, height? }, ...]
     *   POST {path}  →  FormData mit 'file' (nur bei upload: true)
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

        $libraryDirs = $options['library'] ?? [];

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
                            <?= $fieldId !== null ? 'data-field-id="' . htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
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
            data-library="<?= htmlspecialchars(json_encode(array_values($libraryDirs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
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
                    <span id="librarySelectionCount" class="text-sm text-slate-500">0 Bilder ausgewählt</span>

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

        if ($publicDir === false) {
            return '';
        }

        $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');

        if ($documentRoot === false) {
            return '';
        }

        $publicDir = str_replace(DIRECTORY_SEPARATOR, '/', $publicDir);
        $documentRoot = str_replace(DIRECTORY_SEPARATOR, '/', $documentRoot);

        if (!str_starts_with($publicDir, $documentRoot)) {
            return '';
        }

        return rtrim(substr($publicDir, strlen($documentRoot)), '/');
    }
}