/**
 * md-editor.js
 * Markdown-Editor mit Live-Vorschau, Formatbar und Headline-Dropdown.
 *
 * Abhängigkeiten (müssen vor diesem Script geladen sein):
 *   - marked.js   (https://cdn.jsdelivr.net/npm/marked/marked.min.js)
 *   - DOMPurify   (https://cdn.jsdelivr.net/npm/dompurify@3.1.7/dist/purify.min.js)
 */

(function () {
    'use strict';

    // ------------------------------------------------------------------ //
    // DOM-Referenzen                                                       //
    // ------------------------------------------------------------------ //

    const editor          = document.getElementById('editor');
    const preview         = document.getElementById('preview');
    const statsEl         = document.getElementById('stats');
    const grid            = document.getElementById('grid');
    const formatbar       = document.getElementById('formatbar');
    const toggleBtn       = document.getElementById('toggle');
    const previewCard     = document.getElementById('previewCard');
    const headlineToggle  = document.getElementById('headlineToggle');
    const headlineDropdown = document.getElementById('headlineDropdown');

    // ------------------------------------------------------------------ //
    // Zustand                                                              //
    // ------------------------------------------------------------------ //

    let previewOpen = false;

    // ------------------------------------------------------------------ //
    // Initial-Inhalt                                                       //
    // ------------------------------------------------------------------ //

    const DEFAULT_MD = [
        '# Willkommen 👋',
        '',
        'Dies ist ein **einfacher Markdown-Editor** mit Live-Vorschau.',
        '',
        '- Öffne die Vorschau mit dem Auge-Button rechts',
        '- Die Ansicht teilt sich bei breiten Screens',
        '',
        '**Fett**, *Kursiv*, <u>Unterstrichen</u>, `Code`',
        '',
        '> Zitat-Beispiel',
        '',
        '## H2-Überschrift',
        '### H3-Überschrift',
    ].join('\n');

    // Priorität: Datei-Inhalt (PHP) > localStorage > eingebautes Beispiel
    editor.value = (window.MD_FILE_CONTENT ?? null)
        ?? localStorage.getItem('md-editor-content')
        ?? DEFAULT_MD;

    // Einmal genutzt zurücksetzen, damit Navigationen danach localStorage greifen
    delete window.MD_FILE_CONTENT;

    // ------------------------------------------------------------------ //
    // marked konfigurieren                                                 //
    // ------------------------------------------------------------------ //

    marked.setOptions({
        gfm:       true,
        breaks:    false,
        mangle:    false,
        headerIds: true,
    });

    // ------------------------------------------------------------------ //
    // Rendering + Statistiken                                              //
    // ------------------------------------------------------------------ //

    function render() {
        const raw = editor.value;

        if (previewOpen) {
            preview.innerHTML = DOMPurify.sanitize(marked.parse(raw));
        }

        const words = (raw.trim().match(/\b\w+\b/g) || []).length;
        const chars = raw.length;
        const lines = raw.split('\n').length;

        statsEl.textContent = `${words} Wörter · ${chars} Zeichen · ${lines} Zeilen`;
        localStorage.setItem('md-editor-content', raw);
    }

    editor.addEventListener('input', render);
    render();

    // ------------------------------------------------------------------ //
    // Einfüge-Helpers                                                      //
    // ------------------------------------------------------------------ //

    /**
     * Fügt `before` vor und `after` nach der aktuellen Selektion ein.
     * Ist nichts selektiert, wird der Cursor zwischen before/after gesetzt.
     *
     * @param {string} before
     * @param {string} [after]
     */
    function insertAtSelection(before, after = '') {
        const start = editor.selectionStart ?? 0;
        const end   = editor.selectionEnd   ?? 0;
        const sel   = editor.value.slice(start, end);

        editor.value = editor.value.slice(0, start) + before + sel + after + editor.value.slice(end);

        const pos = start + before.length + sel.length + after.length;
        editor.focus();
        editor.setSelectionRange(pos, pos);
        render();
    }

    /**
     * Umschließt die Selektion mit `prefix` (und optional `suffix`).
     * Ohne suffix wird prefix auf beiden Seiten verwendet.
     *
     * @param {string} prefix
     * @param {string} [suffix]
     */
    function surround(prefix, suffix) {
        insertAtSelection(prefix, suffix ?? prefix);
    }

    /**
     * Fügt `prefix` am Anfang der aktuellen Zeile ein.
     *
     * @param {string} prefix  z. B. "# " oder "> "
     */
    function insertLine(prefix) {
        const start     = editor.selectionStart ?? editor.value.length;
        const lineStart = editor.value.lastIndexOf('\n', Math.max(0, start - 1)) + 1;

        editor.value = editor.value.slice(0, lineStart) + prefix + editor.value.slice(lineStart);

        const pos = lineStart + prefix.length;
        editor.focus();
        editor.setSelectionRange(pos, pos);
        render();
    }

    // ------------------------------------------------------------------ //
    // Vorschau-Toggle                                                      //
    // ------------------------------------------------------------------ //

    function openPreview() {
        previewOpen = true;
        toggleBtn.setAttribute('aria-pressed', 'true');
        previewCard.hidden = false;
        grid.classList.add('is-split');
        preview.innerHTML = DOMPurify.sanitize(marked.parse(editor.value));
    }

    function closePreview() {
        previewOpen = false;
        toggleBtn.setAttribute('aria-pressed', 'false');
        previewCard.hidden = true;
        grid.classList.remove('is-split');
    }

    toggleBtn.addEventListener('click', () => {
        previewOpen ? closePreview() : openPreview();
    });

    // ------------------------------------------------------------------ //
    // Headline-Dropdown                                                    //
    // ------------------------------------------------------------------ //

    /** Öffnet das Dropdown und setzt aria-expanded. */
    function openHeadlineDropdown() {
        headlineDropdown.hidden = false;
        headlineToggle.setAttribute('aria-expanded', 'true');

        // Ersten Eintrag fokussieren für Keyboard-Navigation
        const first = headlineDropdown.querySelector('[role="menuitem"]');
        if (first) first.focus();
    }

    /** Schließt das Dropdown und gibt den Fokus an den Toggle zurück. */
    function closeHeadlineDropdown(returnFocus = false) {
        headlineDropdown.hidden = true;
        headlineToggle.setAttribute('aria-expanded', 'false');
        if (returnFocus) headlineToggle.focus();
    }

    headlineToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        headlineDropdown.hidden ? openHeadlineDropdown() : closeHeadlineDropdown(true);
    });

    // Schließen bei Klick außerhalb des Dropdowns
    document.addEventListener('click', (e) => {
        if (
            !headlineDropdown.hidden &&
            !headlineToggle.contains(e.target) &&
            !headlineDropdown.contains(e.target)
        ) {
            closeHeadlineDropdown();
        }
    });

    // Keyboard-Navigation innerhalb des Dropdowns (↑ ↓ Escape Enter)
    headlineDropdown.addEventListener('keydown', (e) => {
        const items  = Array.from(headlineDropdown.querySelectorAll('[role="menuitem"]'));
        const active = document.activeElement;
        const idx    = items.indexOf(active);

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                items[(idx + 1) % items.length]?.focus();
                break;
            case 'ArrowUp':
                e.preventDefault();
                items[(idx - 1 + items.length) % items.length]?.focus();
                break;
            case 'Escape':
                e.preventDefault();
                closeHeadlineDropdown(true);
                break;
            case 'Tab':
                closeHeadlineDropdown();
                break;
        }
    });

    // ------------------------------------------------------------------ //
    // Formatbar – Event-Delegation                                         //
    // ------------------------------------------------------------------ //

    /** Prefix-Map für H1–H6 */
    const HEADLINE_PREFIXES = {
        h1: '# ',
        h2: '## ',
        h3: '### ',
        h4: '#### ',
        h5: '##### ',
        h6: '###### ',
    };

    formatbar.addEventListener('click', (e) => {
        const btn = e.target.closest('button');

        // Toggle und Headline-Opener sind eigene Handler
        if (!btn || btn.id === 'toggle' || btn.id === 'headlineToggle') return;

        // Dropdown schließen sobald ein Item gewählt wurde
        if (btn.closest('#headlineDropdown')) {
            closeHeadlineDropdown();
        }

        const action = btn.dataset.action;

        switch (action) {

            // --- Zeichenformatierung ---
            case 'bold':
                surround('**');
                break;
            case 'underline':
                surround('<u>', '</u>');
                break;
            case 'italic':
                surround('*');
                break;

            // --- Headlines (H1–H6) ---
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                insertLine(HEADLINE_PREFIXES[action]);
                break;

            // --- Block-/Struktur-Elemente ---
            case 'list':
                insertLine('- ');
                break;
            case 'quote':
                insertLine('> ');
                break;
            case 'code':
                insertAtSelection('\n```\n', '\n```\n');
                break;
            case 'table':
                insertAtSelection('\n| Spalte | Spalte |\n|--------|--------|\n| A      | B      |\n');
                break;

            // --- Einfüge-Elemente ---
            case 'link':
                insertAtSelection('[', '](https://)');
                break;
            case 'image':
                insertAtSelection('![', '](https://)');
                break;
        }
    });

    // ------------------------------------------------------------------ //
    // Keyboard-Shortcuts                                                   //
    // ------------------------------------------------------------------ //

    document.addEventListener('keydown', (e) => {
        const mod = e.ctrlKey || e.metaKey;
        if (!mod) return;

        switch (e.key.toLowerCase()) {
            case 'b':
                e.preventDefault();
                surround('**');
                break;
            case 'i':
                e.preventDefault();
                surround('*');
                break;
            case 'u':
                e.preventDefault();
                surround('<u>', '</u>');
                break;
        }
    });

})();