<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor;

final class MarkdownEditor
{
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
        [$cssHref, ] = self::resolveAssetUrls($opts);
        return '<link rel="stylesheet" href="' . self::esc($cssHref) . '">';
    }

    /**
     * JS am Ende des <body> einbinden (marked, DOMPurify, script.js).
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
     * Editor-Markup (ohne Header) – kommt in den <body> dorthin, wo der Editor stehen soll.
     */
    public static function render(): string
    {
        return <<<'HTML'
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
    }

    /* ------------------------------------------------------------------ *
     * Interna
     * ------------------------------------------------------------------ */

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
     * @return array{0:string,1:string,2:array{include_libs:bool,marked_cdn:string,purify_cdn:string}}
     */
    private static function resolveAssetUrls(array $opts): array
    {
        // Defaults für Libraries
        $libs = [
            'include_libs' => array_key_exists('include_libs', $opts) ? (bool)$opts['include_libs'] : true,
            'marked_cdn'   => $opts['marked_cdn'] ?? 'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
            'purify_cdn'   => $opts['purify_cdn'] ?? 'https://cdn.jsdelivr.net/npm/dompurify@3.1.7/dist/purify.min.js',
        ];

        // Explizite URLs > Auto-Detect > Fallback
        $cssHref = $opts['css_href'] ?? null;
        $jsSrc   = $opts['js_src']   ?? null;

        if ($cssHref === null || $jsSrc === null) {
            $publicUrl = null;

            // 1) Wenn asset_base_url angegeben: daraus bauen
            if (!empty($opts['asset_base_url'])) {
                $publicUrl = rtrim((string)$opts['asset_base_url'], '/') . '/public';
            } else {
                // 2) Sonst: automatisch erkennen
                $publicUrl = self::detectPublicUrl();
            }

            if ($publicUrl !== null) {
                $cssHref = $cssHref ?? ($publicUrl . '/md-editor.css');
                $jsSrc   = $jsSrc   ?? ($publicUrl . '/md-editor.js');
            }
        }

        // 3) Letzter Fallback (sinnvolle Default-URL auf Basis des Vendor-Namens)
        if ($cssHref === null) { $cssHref = '/vendor/cheinisch/markdown-editor/public/md-editor.css'; }
        if ($jsSrc   === null) { $jsSrc   = '/vendor/cheinisch/markdown-editor/public/md-editor.js'; }

        return [$cssHref, $jsSrc, $libs];
    }

    /**
     * Versucht, aus dem Dateisystempfad des Pakets die öffentliche URL
     * zum Unterordner "public" zu berechnen.
     *
     * @return string|null  z. B. "/vendor/cheinisch/markdown-editor/public" oder null
     */
    private static function detectPublicUrl(): ?string
    {
        $classFile = (new \ReflectionClass(self::class))->getFileName();
        if (!$classFile) { return null; }

        $srcDir      = \dirname($classFile);                // …/markdown-editor/src
        $packageRoot = \dirname($srcDir);                   // …/markdown-editor
        $publicDir   = $packageRoot . DIRECTORY_SEPARATOR . 'public';

        $publicPath = str_replace('\\', '/', $publicDir);
        $docRoot    = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';

        if ($docRoot !== '' && str_starts_with($publicPath, rtrim($docRoot, '/'))) {
            $relative = substr($publicPath, strlen(rtrim($docRoot, '/')));
            if ($relative === '' || $relative[0] !== '/') {
                $relative = '/' . $relative;
            }
            return $relative; // z. B. "/vendor/cheinisch/markdown-editor/public"
        }
        return null;
    }

    /** HTML-escape Helper */
    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
