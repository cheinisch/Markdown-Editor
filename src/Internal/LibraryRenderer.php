<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor\Internal;

/**
 * Erzeugt die Client-Konfiguration und das Modal-HTML der Bildbibliothek.
 *
 * @internal Nur für den internen Gebrauch innerhalb des Pakets.
 */
final class LibraryRenderer
{
    /**
     * Normalisiert die Library-Konfiguration für die Client-Seite.
     *
     * @param  array<string, mixed> $raw
     * @return array{endpoint: string, upload: bool, allowTypes: list<string>}
     */
    public static function resolveConfig(array $raw): array
    {
        return [
            'endpoint'   => (string) ($raw['endpoint']    ?? ''),
            'upload'     => (bool)   ($raw['upload']      ?? false),
            'allowTypes' => (array)  ($raw['allow_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']),
        ];
    }

    /**
     * Erzeugt das vollständige Modal-HTML der Bildbibliothek.
     *
     * @param array{endpoint: string, upload: bool, allowTypes: list<string>} $cfg
     */
    public static function renderModal(array $cfg): string
    {
        $uploadSection = $cfg['upload'] ? self::renderUploadSection($cfg['allowTypes']) : '';

        return <<<HTML

<!-- ── Bildbibliothek-Modal ─────────────────────────────────────────── -->
<div class="mdlib-backdrop" id="mdLibBackdrop" hidden aria-hidden="true"></div>
<div class="mdlib-modal" id="mdLibModal" hidden role="dialog" aria-modal="true" aria-labelledby="mdLibTitle">
  <div class="mdlib-header">
    <span class="mdlib-title" id="mdLibTitle">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      Image Library
    </span>
    <input class="mdlib-search" id="mdLibSearch" type="search" placeholder="Search images…" aria-label="Search images">
    <button class="mdlib-close" id="mdLibClose" aria-label="Close library">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  <div class="mdlib-body">
    <nav class="mdlib-sidebar" id="mdLibSidebar" aria-label="Directories"></nav>
    <div class="mdlib-content">
      <div class="mdlib-grid" id="mdLibGrid" role="listbox" aria-multiselectable="true" aria-label="Images"></div>
      <p class="mdlib-empty" id="mdLibEmpty" hidden>No images found.</p>
      <p class="mdlib-loading" id="mdLibLoading">Loading…</p>
    </div>
  </div>
{$uploadSection}
  <div class="mdlib-footer">
    <span class="mdlib-selection-info" id="mdLibSelectionInfo">No image selected</span>
    <div class="mdlib-footer-actions">
      <button class="mdlib-btn mdlib-btn--ghost" id="mdLibCancel">Cancel</button>
      <button class="mdlib-btn mdlib-btn--primary" id="mdLibInsert" disabled>Insert</button>
    </div>
  </div>
</div>
HTML;
    }

    // ------------------------------------------------------------------ //
    // Privat                                                               //
    // ------------------------------------------------------------------ //

    /**
     * Erzeugt den Upload-Bereich innerhalb des Modals.
     *
     * @param list<string> $allowTypes
     */
    private static function renderUploadSection(array $allowTypes): string
    {
        $accept     = implode(',', array_map(fn (string $t) => '.' . $t, $allowTypes));
        $typesLabel = htmlspecialchars(
            implode(', ', array_map('strtoupper', $allowTypes)),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        return <<<HTML

  <div class="mdlib-upload" id="mdLibUpload">
    <label class="mdlib-dropzone" id="mdLibDropzone">
      <input type="file" id="mdLibFileInput" multiple accept="{$accept}" class="mdlib-file-input">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <span class="mdlib-dropzone__label">Drop images here or <u>click to upload</u></span>
      <span class="mdlib-dropzone__types">{$typesLabel}</span>
    </label>
    <div class="mdlib-upload-progress" id="mdLibProgress" hidden>
      <div class="mdlib-upload-bar" id="mdLibProgressBar"></div>
    </div>
  </div>
HTML;
    }
}