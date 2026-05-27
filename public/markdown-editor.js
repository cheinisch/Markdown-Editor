(() => {
    const editor = document.getElementById('editor');
    const preview = document.getElementById('preview');
    const stats = document.getElementById('stats');

    const previewPanel = document.getElementById('previewPanel');
    const editorLayout = document.getElementById('editorLayout');
    const togglePreviewBtn = document.getElementById('togglePreview');

    const libraryBackdrop = document.getElementById('libraryBackdrop');
    const openLibraryBtn = document.getElementById('openLibrary');
    const closeLibraryBtn = document.getElementById('closeLibrary');
    const cancelLibraryBtn = document.getElementById('cancelLibrary');

    if (!editor) return;

    let previewVisible = false;

    function updatePreview() {
        const raw = editor.value || '';

        if (preview && window.marked && window.DOMPurify) {
            preview.innerHTML = DOMPurify.sanitize(marked.parse(raw));
        }

        if (stats) {
            const words = raw.trim().split(/\s+/).filter(Boolean).length;
            const chars = raw.length;
            const lines = raw.split('\n').length;
            stats.textContent = `${words} Wörter · ${chars} Zeichen · ${lines} Zeilen`;
        }
    }

    function replaceSelection(text, start, end, mode = 'end') {
        editor.focus();
        editor.setRangeText(text, start, end, mode);
        editor.dispatchEvent(new Event('input', { bubbles: true }));
        updatePreview();
    }

    function insertAround(before, after = before, placeholder = '') {
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const selected = editor.value.substring(start, end) || placeholder;
        replaceSelection(before + selected + after, start, end, 'end');
    }

    function insertLine(prefix) {
        const value = editor.value;
        const start = editor.selectionStart;
        const end = editor.selectionEnd;

        const lineStart = value.lastIndexOf('\n', start - 1) + 1;

        let lineEnd;
        if (end > start && value[end - 1] === '\n') {
            lineEnd = end - 1;
        } else {
            const nextBreak = value.indexOf('\n', end);
            lineEnd = nextBreak === -1 ? value.length : nextBreak;
        }

        const selectedBlock = value.slice(lineStart, lineEnd);

        const updatedBlock = selectedBlock
            .split('\n')
            .map(line => {
                if (line.trim() === '') return line;
                if (line.startsWith(prefix)) return line;
                return prefix + line;
            })
            .join('\n');

        replaceSelection(updatedBlock, lineStart, lineEnd, 'select');
    }

    function insertLink() {
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const selected = editor.value.substring(start, end) || 'Linktext';
        replaceSelection(`[${selected}](https://example.com)`, start, end, 'end');
    }

    function insertCodeBlock() {
        insertAround("```\n", "\n```", 'code');
    }

    function insertImage() {
        insertAround('![', '](https://example.com/image.jpg)', 'Bildbeschreibung');
        hideLibrary();
    }

    function handleListContinuation(event) {
        if (event.key !== 'Enter') return;

        const start = editor.selectionStart;
        const end = editor.selectionEnd;

        if (start !== end) return;

        const value = editor.value;
        const lineStart = value.lastIndexOf('\n', start - 1) + 1;
        const currentLine = value.slice(lineStart, start);

        const unorderedMatch = currentLine.match(/^(\s*)-\s(.*)$/);
        const orderedMatch = currentLine.match(/^(\s*)(\d+)\.\s(.*)$/);

        if (!unorderedMatch && !orderedMatch) return;

        event.preventDefault();

        if (unorderedMatch) {
            const indent = unorderedMatch[1];
            const content = unorderedMatch[2];

            if (content.trim() === '') {
                replaceSelection('', lineStart, start, 'end');
            } else {
                replaceSelection('\n' + indent + '- ', start, end, 'end');
            }

            return;
        }

        if (orderedMatch) {
            const indent = orderedMatch[1];
            const number = Number(orderedMatch[2]);
            const content = orderedMatch[3];

            if (content.trim() === '') {
                replaceSelection('', lineStart, start, 'end');
            } else {
                replaceSelection('\n' + indent + (number + 1) + '. ', start, end, 'end');
            }
        }
    }

    document.querySelectorAll('[data-action]').forEach(button => {
        button.addEventListener('mousedown', event => {
            event.preventDefault();
        });

        button.addEventListener('click', () => {
            switch (button.dataset.action) {
                case 'bold':
                    insertAround('**', '**', 'fetter Text');
                    break;
                case 'italic':
                    insertAround('*', '*', 'kursiver Text');
                    break;
                case 'underline':
                    insertAround('<u>', '</u>', 'unterstrichener Text');
                    break;
                case 'h1':
                    insertLine('# ');
                    break;
                case 'list':
                    insertLine('- ');
                    break;
                case 'quote':
                    insertLine('> ');
                    break;
                case 'code':
                    insertCodeBlock();
                    break;
                case 'link':
                    insertLink();
                    break;
                case 'insert-image':
                    insertImage();
                    break;
            }
        });
    });

    if (togglePreviewBtn && previewPanel && editorLayout) {
        togglePreviewBtn.addEventListener('click', () => {
            previewVisible = !previewVisible;

            if (previewVisible) {
                previewPanel.classList.remove('hidden');
                previewPanel.classList.add('flex');
                editorLayout.classList.remove('lg:grid-cols-1');
                editorLayout.classList.add('lg:grid-cols-2');
                togglePreviewBtn.textContent = 'Vorschau ausblenden';
            } else {
                previewPanel.classList.add('hidden');
                previewPanel.classList.remove('flex');
                editorLayout.classList.remove('lg:grid-cols-2');
                editorLayout.classList.add('lg:grid-cols-1');
                togglePreviewBtn.textContent = 'Vorschau anzeigen';
            }
        });
    }

    function showLibrary() {
        if (!libraryBackdrop) return;
        libraryBackdrop.classList.remove('hidden');
        libraryBackdrop.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function hideLibrary() {
        if (!libraryBackdrop) return;
        libraryBackdrop.classList.add('hidden');
        libraryBackdrop.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    if (openLibraryBtn) openLibraryBtn.addEventListener('click', showLibrary);
    if (closeLibraryBtn) closeLibraryBtn.addEventListener('click', hideLibrary);
    if (cancelLibraryBtn) cancelLibraryBtn.addEventListener('click', hideLibrary);

    if (libraryBackdrop) {
        libraryBackdrop.addEventListener('click', event => {
            if (event.target === libraryBackdrop) hideLibrary();
        });
    }

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') hideLibrary();

        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'b') {
            event.preventDefault();
            insertAround('**', '**', 'fetter Text');
        }

        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'i') {
            event.preventDefault();
            insertAround('*', '*', 'kursiver Text');
        }

        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'u') {
            event.preventDefault();
            insertAround('<u>', '</u>', 'unterstrichener Text');
        }
    });

    editor.addEventListener('keydown', handleListContinuation);
    editor.addEventListener('input', updatePreview);

    updatePreview();
})();