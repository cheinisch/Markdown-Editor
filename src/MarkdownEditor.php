<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor;

final class MarkdownEditor
{
    public static function renderHead(): string
    {
        return <<<'HTML'
<script src="https://cdn.tailwindcss.com?plugins=typography"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.7/dist/purify.min.js"></script>
HTML;
    }

    public static function render(): string
    {
        return <<<'HTML'
<div class="mx-auto max-w-7xl p-6">
    <section class="overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-slate-200">

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3">
            <button type="button" data-action="bold" class="rounded-lg px-3 py-2 text-sm font-bold hover:bg-white">B</button>
            <button type="button" data-action="italic" class="rounded-lg px-3 py-2 text-sm italic hover:bg-white">I</button>
            <button type="button" data-action="underline" class="rounded-lg px-3 py-2 text-sm underline hover:bg-white">U</button>

            <span class="mx-1 h-6 w-px bg-slate-300"></span>

            <button type="button" data-action="h1" class="rounded-lg px-3 py-2 text-sm hover:bg-white">H1</button>
            <button type="button" data-action="list" class="rounded-lg px-3 py-2 text-sm hover:bg-white">Liste</button>
            <button type="button" data-action="quote" class="rounded-lg px-3 py-2 text-sm hover:bg-white">Zitat</button>
            <button type="button" data-action="code" class="rounded-lg px-3 py-2 text-sm hover:bg-white">Code</button>
            <button type="button" data-action="link" class="rounded-lg px-3 py-2 text-sm hover:bg-white">Link</button>

            <button
                type="button"
                id="togglePreview"
                class="ml-auto rounded-xl bg-white px-4 py-2 text-sm font-semibold ring-1 ring-slate-200 hover:bg-slate-50"
            >
                Vorschau anzeigen
            </button>

            <button
                type="button"
                id="openLibrary"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
            >
                Bibliothek
            </button>
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
                ></textarea>

                <div id="stats" class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                    0 Wörter · 0 Zeichen · 0 Zeilen
                </div>
            </section>

            <section id="previewPanel" class="hidden flex-col bg-white">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Vorschau</h2>
                </div>

                <article
                    id="preview"
                    class="prose prose-slate max-w-none p-8"
                ></article>
            </section>
        </div>
    </section>
</div>

<div id="libraryBackdrop" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <section class="flex max-h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
        <header class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-lg font-bold">Bildbibliothek</h2>
                <p class="text-xs text-slate-500">Wähle ein Bild aus der Bibliothek.</p>
            </div>

            <input
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
                <nav class="space-y-1 text-sm">
                    <button type="button" class="w-full rounded-xl bg-white px-3 py-2 text-left font-medium shadow-sm ring-1 ring-slate-200">
                        Alle Bilder
                    </button>

                    <button type="button" class="w-full rounded-xl px-3 py-2 text-left text-slate-600 hover:bg-white">
                        Uploads
                    </button>

                    <button type="button" class="w-full rounded-xl px-3 py-2 text-left text-slate-600 hover:bg-white">
                        Blog
                    </button>
                </nav>
            </aside>

            <div class="overflow-y-auto p-5">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <button
                        type="button"
                        class="overflow-hidden rounded-2xl border-2 border-blue-500 bg-white text-left shadow-sm"
                    >
                        <div class="aspect-video bg-gradient-to-br from-blue-100 to-slate-200"></div>
                        <div class="p-3">
                            <p class="truncate text-sm font-semibold">hero-image.jpg</p>
                            <p class="text-xs text-slate-500">1280 × 720</p>
                        </div>
                    </button>

                    <button
                        type="button"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-sm hover:border-blue-300"
                    >
                        <div class="aspect-video bg-gradient-to-br from-emerald-100 to-slate-200"></div>
                        <div class="p-3">
                            <p class="truncate text-sm font-semibold">header.png</p>
                            <p class="text-xs text-slate-500">1024 × 768</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <footer class="flex items-center gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
            <span class="text-sm text-slate-500">1 Bild ausgewählt</span>

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
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                Einfügen
            </button>
        </footer>
    </section>
</div>
HTML;
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