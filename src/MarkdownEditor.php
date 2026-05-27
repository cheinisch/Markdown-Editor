<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor;

final class MarkdownEditor
{
    /**
     * Alle bekannten Button-Namen.
     * Reihenfolge bestimmt die Darstellung in der Formatbar.
     *
     * Nicht enthalten: „Headline"-Dropdown und Vorschau-Toggle –
     * diese sind immer vorhanden und nicht konfigurierbar.
     */
    private const KNOWN_BUTTONS = [
        'bold', 'underline', 'italic',
        'list', 'quote', 'code', 'table',
        'link', 'image',
    ];

    // ------------------------------------------------------------------ //
    // Öffentliche API                                                      //
    // ------------------------------------------------------------------ //

    /**
     * CSS im <head> einbinden.
     *
     * @param array{
     *   asset_base_url?: string,
     *   css_href?: string
     * } $opts
     */
    public static function renderHeadAssets(array $opts = []): string
    {
        [$cssHref] = self::resolveAssetUrls($opts);

        return '<link rel="stylesheet" href="' . self::esc($cssHref) . '">';
    }

    /**
     * JS am Ende des <body> einbinden (marked, DOMPurify, md-editor.js).
     *
     * @param array{
     *   asset_base_url?: string,
     *   js_src?: string,
     *   include_libs?: bool,
     *   marked_cdn?: string,
     *   purify_cdn?: string
     * } $opts
     */
    public static function renderFootAssets(array $opts = []): string
    {
        [, $jsSrc, $libs] = self::resolveAssetUrls($opts);

        $tags = '';

        if ($libs['include_libs']) {
            $tags .= '<script src="' . self::esc($libs['marked_cdn']) . '"></script>' . PHP_EOL;
            $tags .= '<script src="' . self::esc($libs['purify_cdn']) . '"></script>' . PHP_EOL;
        }

        $tags .= '<script src="' . self::esc($jsSrc) . '"></script>';

        return $tags;
    }

    /**
     * Editor-Markup – kommt in den <body> dorthin, wo der Editor stehen soll.
     *
     * @param array{
     *   buttons?: list<string>
     * } $opts
     *
     * Beispiele
     * ---------
     * Alle Buttons anzeigen (Key weglassen):
     *   MarkdownEditor::render();
     *
     * Nur bestimmte Buttons anzeigen:
     *   MarkdownEditor::render(['buttons' => ['bold', 'italic', 'link', 'code']]);
     *
     * Verfügbare Button-Namen:
     *   bold, underline, italic, list, quote, code, table, link, image
     *
     * Headline-Dropdown und Vorschau-Toggle sind immer sichtbar.
     */
    public static function render(array $opts = []): string
    {
        // buttons-Key nicht gesetzt → alle anzeigen
        // buttons-Key gesetzt (auch leer) → nur gelistete anzeigen
        $buttons = array_key_exists('buttons', $opts)
            ? self::resolveButtons($opts['buttons'])
            : array_fill_keys(self::KNOWN_BUTTONS, true);

        $bar = self::renderFormatbar($buttons);

        return <<<HTML
<div class="wrap">
  <div class="grid" id="grid">

    <!-- Editor-Card -->
    <section class="card" id="editorCard">
      <h6>Editor</h6>

      {$bar}

      <textarea id="editor" placeholder="Schreibe hier Markdown …"></textarea>
      <div class="stats" id="stats">0 Wörter · 0 Zeichen · 0 Zeilen</div>
    </section>

    <!-- Vorschau (Start: versteckt) -->
    <section class="card" id="previewCard" hidden>
      <h6>Vorschau</h6>
      <article class="preview prose" id="preview"></article>
    </section>

  </div>
  <div class="footer">Tipp: Inhalte werden automatisch lokal gespeichert.</div>
</div>
HTML;
    }

    // ------------------------------------------------------------------ //
    // Private Hilfsmethoden                                                //
    // ------------------------------------------------------------------ //

    /**
     * Baut aus der Nutzer-Eingabe eine vollständige Button-Map (name => bool).
     *
     * Unterstützte Formate:
     *   ['bold', 'italic', 'link']           – einfache Liste, nur diese anzeigen
     *   ['bold' => true, 'italic' => false]  – explizite Key-Bool-Map
     *   Mischformen sind erlaubt.
     *
     * Unbekannte Button-Namen werden stillschweigend ignoriert.
     *
     * @param  array<int|string, string|bool> $userButtons
     * @return array<string, bool>
     */
    private static function resolveButtons(array $userButtons): array
    {
        // Alle auf false – nur explizit gelistete werden aktiviert
        $resolved = array_fill_keys(self::KNOWN_BUTTONS, false);

        foreach ($userButtons as $key => $value) {
            if (is_int($key) && is_string($value)) {
                // Format: ['bold', 'italic', …]
                if (array_key_exists($value, $resolved)) {
                    $resolved[$value] = true;
                }
            } elseif (is_string($key) && array_key_exists($key, $resolved)) {
                // Format: ['bold' => true, 'image' => false, …]
                $resolved[$key] = (bool) $value;
            }
        }

        return $resolved;
    }

    /**
     * Baut die komplette Formatbar als HTML-String.
     *
     * @param array<string, bool> $buttons
     */
    private static function renderFormatbar(array $buttons): string
    {
        $html = '<div class="formatbar" id="formatbar" role="toolbar" aria-label="Formatierung">' . PHP_EOL;

        // --- Zeichenformatierung ---
        if ($buttons['bold']) {
            $html .= '  <button class="btn" title="Fett (Ctrl/⌘+B)" data-action="bold"><b>B</b></button>' . PHP_EOL;
        }
        if ($buttons['underline']) {
            $html .= '  <button class="btn" title="Unterstrichen (Ctrl/⌘+U)" data-action="underline"><u>U</u></button>' . PHP_EOL;
        }
        if ($buttons['italic']) {
            $html .= '  <button class="btn" title="Kursiv (Ctrl/⌘+I)" data-action="italic"><i>I</i></button>' . PHP_EOL;
        }

        // --- Headline-Dropdown (immer vorhanden, nicht konfigurierbar) ---
        $html .= <<<'DROPDOWN'
  <div class="btn-group" id="headlineGroup">
    <button class="btn btn--dropdown"
            id="headlineToggle"
            title="Überschrift wählen (H1–H6)"
            aria-haspopup="true"
            aria-expanded="false"
            data-action="headline-toggle">
      Headline <span class="btn__caret" aria-hidden="true">▾</span>
    </button>
    <div class="dropdown" id="headlineDropdown" hidden role="menu">
      <button class="dropdown__item" data-action="h1" role="menuitem">H1 – Hauptüberschrift</button>
      <button class="dropdown__item" data-action="h2" role="menuitem">H2 – Abschnitt</button>
      <button class="dropdown__item" data-action="h3" role="menuitem">H3 – Unterabschnitt</button>
      <button class="dropdown__item" data-action="h4" role="menuitem">H4</button>
      <button class="dropdown__item" data-action="h5" role="menuitem">H5</button>
      <button class="dropdown__item" data-action="h6" role="menuitem">H6</button>
    </div>
  </div>
DROPDOWN;

        $html .= PHP_EOL;

        // --- Block-/Struktur-Elemente ---
        if ($buttons['list']) {
            $html .= '  <button class="btn" title="Liste" data-action="list">• List</button>' . PHP_EOL;
        }
        if ($buttons['quote']) {
            $html .= '  <button class="btn" title="Zitat (Blockquote)" data-action="quote">❝</button>' . PHP_EOL;
        }
        if ($buttons['code']) {
            $html .= '  <button class="btn" title="Codeblock" data-action="code">{ }</button>' . PHP_EOL;
        }
        if ($buttons['table']) {
            $html .= '  <button class="btn" title="Tabelle" data-action="table">⌗</button>' . PHP_EOL;
        }

        // --- Einfüge-Elemente ---
        if ($buttons['link']) {
            $html .= '  <button class="btn" title="Link einfügen" data-action="link">🔗</button>' . PHP_EOL;
        }
        if ($buttons['image']) {
            $html .= '  <button class="btn" title="Bild einfügen" data-action="image">🖼</button>' . PHP_EOL;
        }

        // --- Spacer + Vorschau-Toggle (immer vorhanden) ---
        $html .= '  <span class="formatbar__spacer" aria-hidden="true"></span>' . PHP_EOL;
        $html .= <<<'TOGGLE'
  <button class="btn"
          id="toggle"
          title="Vorschau ein-/ausblenden"
          aria-pressed="false"
          aria-label="Vorschau umschalten">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24" height="24" aria-hidden="true">
      <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33
               0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-480H200v480Zm280-80q-82
               0-146.5-44.5T240-440q29-71 93.5-115.5T480-600q82 0 146.5 44.5T720-440q-29
               71-93.5 115.5T480-280Zm0-60q56 0 102-26.5t72-73.5q-26-47-72-73.5T480-540q-56
               0-102 26.5T306-440q26 47 72 73.5T480-340Zm0-100Zm0 60q25 0 42.5-17.5T540-440q0-25-17.5-42.5T480-500q-25
               0-42.5 17.5T420-440q0 25 17.5 42.5T480-380Z"/>
    </svg>
  </button>
TOGGLE;

        $html .= PHP_EOL . '</div>';

        return $html;
    }

    /**
     * Liefert [cssHref, jsSrc, libs].
     *
     * @param array{
     *   asset_base_url?: string,
     *   css_href?: string,
     *   js_src?: string,
     *   include_libs?: bool,
     *   marked_cdn?: string,
     *   purify_cdn?: string
     * } $opts
     * @return array{0: string, 1: string, 2: array{include_libs: bool, marked_cdn: string, purify_cdn: string}}
     */
    private static function resolveAssetUrls(array $opts): array
    {
        $libs = [
            'include_libs' => array_key_exists('include_libs', $opts) ? (bool) $opts['include_libs'] : true,
            'marked_cdn'   => $opts['marked_cdn'] ?? 'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
            'purify_cdn'   => $opts['purify_cdn'] ?? 'https://cdn.jsdelivr.net/npm/dompurify@3.1.7/dist/purify.min.js',
        ];

        $cssHref = $opts['css_href'] ?? null;
        $jsSrc   = $opts['js_src']   ?? null;

        if ($cssHref === null || $jsSrc === null) {
            $publicUrl = null;

            if (!empty($opts['asset_base_url'])) {
                $publicUrl = rtrim((string) $opts['asset_base_url'], '/') . '/public';
            } else {
                $publicUrl = self::detectPublicUrl();
            }

            if ($publicUrl !== null) {
                $cssHref ??= $publicUrl . '/md-editor.css';
                $jsSrc   ??= $publicUrl . '/md-editor.js';
            }
        }

        $cssHref ??= '/vendor/cheinisch/markdown-editor/public/md-editor.css';
        $jsSrc   ??= '/vendor/cheinisch/markdown-editor/public/md-editor.js';

        return [$cssHref, $jsSrc, $libs];
    }

    /**
     * Versucht, aus dem Dateisystempfad des Pakets die öffentliche URL
     * zum Unterordner „public" zu berechnen.
     *
     * @return string|null  z. B. "/vendor/cheinisch/markdown-editor/public"
     */
    private static function detectPublicUrl(): ?string
    {
        $classFile = (new \ReflectionClass(self::class))->getFileName();

        if (!$classFile) {
            return null;
        }

        $publicDir  = \dirname(\dirname($classFile)) . DIRECTORY_SEPARATOR . 'public';
        $publicPath = str_replace('\\', '/', $publicDir);
        $docRoot    = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));

        if ($docRoot !== '' && str_starts_with($publicPath, rtrim($docRoot, '/'))) {
            $relative = substr($publicPath, strlen(rtrim($docRoot, '/')));

            return ($relative === '' || $relative[0] !== '/') ? '/' . $relative : $relative;
        }

        return null;
    }

    /** HTML-Sonderzeichen escapen */
    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}