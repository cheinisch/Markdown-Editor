<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor\Internal;

/**
 * Erzeugt das HTML der Formatbar (Toolbar).
 *
 * @internal Nur für den internen Gebrauch innerhalb des Pakets.
 */
final class FormatbarRenderer
{
    /**
     * Baut die komplette Formatbar als HTML-String.
     *
     * @param array<string, bool> $buttons  Ergebnis von ButtonResolver::resolve()
     * @param bool                $hasLibrary  Bibliotheks-Button anzeigen?
     */
    public static function render(array $buttons, bool $hasLibrary = false): string
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

        // --- Bildbibliothek (optional) ---
        if ($hasLibrary) {
            $html .= '  <button class="btn" title="Bildbibliothek öffnen" data-action="library" id="mdLibraryBtn">'
                   . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"'
                   . ' fill="none" stroke="currentColor" stroke-width="2"'
                   . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                   . '<rect x="3" y="3" width="18" height="18" rx="2"/>'
                   . '<circle cx="8.5" cy="8.5" r="1.5"/>'
                   . '<polyline points="21 15 16 10 5 21"/>'
                   . '</svg>'
                   . '</button>' . PHP_EOL;
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
}