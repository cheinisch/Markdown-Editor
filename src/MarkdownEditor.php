<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor;

use cheinisch\MarkdownEditor\Internal\AssetResolver;
use cheinisch\MarkdownEditor\Internal\ButtonResolver;
use cheinisch\MarkdownEditor\Internal\FormatbarRenderer;
use cheinisch\MarkdownEditor\Internal\LibraryRenderer;

/**
 * Markdown-Editor – öffentliche API.
 *
 * Einziger Import der für Nutzer nötig ist:
 *   use cheinisch\MarkdownEditor\MarkdownEditor;
 *
 * Alle internen Klassen (Internal\*) sind Implementierungsdetails
 * und können sich zwischen Minor-Versionen ändern.
 */
final class MarkdownEditor
{
    // ------------------------------------------------------------------ //
    // Library-API (Facade)                                                 //
    // ------------------------------------------------------------------ //

    /**
     * Leitet einen Library-API-Request weiter.
     * Erspart den separaten Import von MarkdownLibrary.
     *
     * Typische Integration ganz oben in index.php:
     *
     *   if (isset($_GET['md-library'])) {
     *       MarkdownEditor::handleLibraryRequest([
     *           'dirs' => [
     *               __DIR__ . '/uploads'    => '/uploads',
     *               __DIR__ . '/assets/img' => '/assets/img',
     *           ],
     *           'upload'      => true,
     *           'upload_dir'  => __DIR__ . '/uploads',
     *           'upload_url'  => '/uploads',
     *           'allow_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
     *       ]);
     *       exit;
     *   }
     *
     * @param array{
     *   dirs:         array<string, string>,
     *   upload?:      bool,
     *   upload_dir?:  string,
     *   upload_url?:  string,
     *   allow_types?: list<string>
     * } $config
     */
    public static function handleLibraryRequest(array $config): void
    {
        MarkdownLibrary::handleRequest($config);
    }

    // ------------------------------------------------------------------ //
    // Asset-Tags                                                           //
    // ------------------------------------------------------------------ //

    /**
     * CSS-Tag für den <head>.
     *
     * @param array{
     *   asset_base_url?: string,
     *   css_href?:       string,
     *   include_libs?:   bool,
     *   marked_cdn?:     string,
     *   purify_cdn?:     string
     * } $opts
     */
    public static function renderHeadAssets(array $opts = []): string
    {
        [$cssHref] = AssetResolver::resolve($opts);

        return '<link rel="stylesheet" href="' . self::esc($cssHref) . '">';
    }

    /**
     * JS-Tags für das Ende des <body>.
     *
     * @param array{
     *   asset_base_url?: string,
     *   js_src?:         string,
     *   include_libs?:   bool,
     *   marked_cdn?:     string,
     *   purify_cdn?:     string
     * } $opts
     */
    public static function renderFootAssets(array $opts = []): string
    {
        [, $jsSrc, $libs] = AssetResolver::resolve($opts);

        $tags = '';

        if ($libs['include_libs']) {
            $tags .= '<script src="' . self::esc($libs['marked_cdn']) . '"></script>' . PHP_EOL;
            $tags .= '<script src="' . self::esc($libs['purify_cdn']) . '"></script>' . PHP_EOL;
        }

        $tags .= '<script src="' . self::esc($jsSrc) . '"></script>';

        return $tags;
    }

    // ------------------------------------------------------------------ //
    // Editor-Markup                                                        //
    // ------------------------------------------------------------------ //

    /**
     * Gibt das vollständige Editor-HTML aus.
     *
     * @param array{
     *   buttons?:       list<string>|array<string, bool>,
     *   local_storage?: bool,
     *   library?:       array{
     *     endpoint:     string,
     *     upload?:      bool,
     *     allow_types?: list<string>
     *   }
     * } $opts
     *
     * Beispiele
     * ---------
     * Alle Buttons (Standard):
     *   MarkdownEditor::render();
     *
     * Nur bestimmte Buttons:
     *   MarkdownEditor::render(['buttons' => ['bold', 'italic', 'link']]);
     *
     * localStorage deaktivieren:
     *   MarkdownEditor::render(['local_storage' => false]);
     *
     * Bildbibliothek aktivieren:
     *   MarkdownEditor::render([
     *       'library' => [
     *           'endpoint'    => '/?md-library=1',
     *           'upload'      => true,
     *           'allow_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
     *       ],
     *   ]);
     */
    public static function render(array $opts = []): string
    {
        $buttons = ButtonResolver::resolve(
            array_key_exists('buttons', $opts) ? $opts['buttons'] : null
        );

        $localStorage = ($opts['local_storage'] ?? true) !== false ? 'true' : 'false';

        $library     = isset($opts['library']) ? LibraryRenderer::resolveConfig($opts['library']) : null;
        $libraryAttr = $library !== null
            ? ' data-library="' . self::esc((string) json_encode($library)) . '"'
            : '';
        $libraryHtml = $library !== null ? LibraryRenderer::renderModal($library) : '';

        $bar = FormatbarRenderer::render($buttons, $library !== null);

        return <<<HTML
<div class="wrap"{$libraryAttr}>
  <div class="grid" id="grid">

    <!-- Editor-Card -->
    <section class="card" id="editorCard">
      <h6>Editor</h6>

      {$bar}

      <textarea id="editor" placeholder="Schreibe hier Markdown …" data-localstorage="{$localStorage}"></textarea>
      <div class="stats" id="stats">0 Wörter · 0 Zeichen · 0 Zeilen</div>
    </section>

    <!-- Vorschau (Start: versteckt) -->
    <section class="card" id="previewCard" hidden>
      <h6>Vorschau</h6>
      <article class="preview prose" id="preview"></article>
    </section>

  </div>
  <div class="footer">Tipp: Inhalte werden automatisch lokal gespeichert.</div>
  {$libraryHtml}
</div>
HTML;
    }

    // ------------------------------------------------------------------ //
    // Intern                                                               //
    // ------------------------------------------------------------------ //

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}