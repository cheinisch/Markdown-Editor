<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor;

/**
 * MarkdownEditor
 *
 * Rendert ein HTML-Snippet für den Editor. Die CSS/JS-Assets werden
 * standardmäßig unter /vendor/cheinisch/markdown-editor/public/... erwartet.
 *
 * Beispiel (plain PHP):
 *   require __DIR__ . '/vendor/autoload.php';
 *   echo \cheinisch\MarkdownEditor\MarkdownEditor::render([
 *     'asset_base_url' => '/vendor/cheinisch/markdown-editor', // optional, ist Default
 *   ]);
 *
 * Konfig-Optionen:
 * - asset_base_url: Basis-URL zu deinem Paket (Default: /vendor/cheinisch/markdown-editor)
 * - css_href:       Exakte URL zur CSS-Datei (überschreibt asset_base_url)
 * - js_src:         Exakte URL zur JS-Datei  (überschreibt asset_base_url)
 * - include_libs:   Ob marked + DOMPurify per CDN geladen werden (Default: true)
 * - marked_cdn:     CDN-URL für marked (Default: jsDelivr)
 * - purify_cdn:     CDN-URL für DOMPurify (Default: jsDelivr)
 */
final class MarkdownEditor
{
    /**
     * @param array{
     *   asset_base_url?: string,
     *   css_href?: string,
     *   js_src?: string,
     *   include_libs?: bool,
     *   marked_cdn?: string,
     *   purify_cdn?: string
     * } $opts
     */
    public static function render(array $opts = []): string
    {
        // Basis-URL (öffentlich erreichbar)
        $base = rtrim($opts['asset_base_url'] ?? '/vendor/cheinisch/markdown-editor', '/');

        // Standardpfade unterhalb des Paket-Ordners /public
        $cssHref = $opts['css_href'] ?? ($base . '/public/md-editor.css');
        $jsSrc   = $opts['js_src']   ?? ($base . '/public/md-editor.js');

        // CDN-Libs (kannst du deaktivieren oder überschreiben)
        $includeLibs = array_key_exists('include_libs', $opts) ? (bool)$opts['include_libs'] : true;
        $markedCdn   = $opts['marked_cdn'] ?? 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        $purifyCdn   = $opts['purify_cdn'] ?? 'https://cdn.jsdelivr.net/npm/dompurify@3.1.7/dist/purify.min.js';

        // HEAD (CSS)
        $head  = '<link rel="stylesheet" href="' . self::esc($cssHref) . '">';

        // optionale CDN-Skripte
        $libTags = '';
        if ($includeLibs) {
            $libTags =
                '<script src="' . self::esc($markedCdn) . '"></script>' . PHP_EOL .
                '<script src="' . self::esc($purifyCdn) . '"></script>';
        }

        // BODY-Markup (Editor + Preview, ohne Header)
        $body = <<<'HTML'
<div class="wrap">
  <div class="grid" id="grid">
    <!-- Editor-Card -->
    <section class="card" id="editorCard">
      <h6>Editor</h6>

      <!-- Button-Leiste direkt am Eingabefeld -->
      <div class="formatbar" id="formatbar" role="toolbar" aria-label="Formatierung">
        <button class="btn" title="Fett (Ctrl/⌘+B)" data-action="bold"><b>B</b></button>
        <button class="btn" title="Kursiv (Ctrl/⌘+I)" data-action="italic"><i>I</i></button>
        <button class="btn" title="Überschrift" data-action="h1">H1</button>
        <button class="btn" title="Liste" data-action="list">• List</button>
        <button class="btn" title="Link" data-action="link">🔗</button>
        <button class="btn" title="Codeblock" data-action="code">{ }</button>
        <button class="btn" title="Tabelle" data-action="table">⌗</button>

        <span class="formatbar__spacer" aria-hidden="true"></span>

        <!-- Toggle: Vorschau ein-/ausblenden (rechts) mit Auge-SVG -->
        <button class="btn" id="toggle" title="Vorschau ein-/ausblenden" aria-pressed="false" aria-label="Vorschau umschalten">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24" height="24" aria-hidden="true">
            <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-480H200v480Zm280-80q-82 0-146.5-44.5T240-440q29-71 93.5-115.5T480-600q82 0 146.5 44.5T720-440q-29 71-93.5 115.5T480-280Zm0-60q56 0 102-26.5t72-73.5q-26-47-72-73.5T480-540q-56 0-102 26.5T306-440q26 47 72 73.5T480-340Zm0-100Zm0 60q25 0 42.5-17.5T540-440q0-25-17.5-42.5T480-500q-25 0-42.5 17.5T420-440q0 25 17.5 42.5T480-380Z"/>
          </svg>
        </button>
      </div>

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

        // App-Script + optional CDN
        $tail =
            $libTags . PHP_EOL .
            '<script src="' . self::esc($jsSrc) . '"></script>';

        return $head . PHP_EOL . $body . PHP_EOL . $tail;
    }

    /** HTML-escape Helper */
    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
