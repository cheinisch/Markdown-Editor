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
        if (!selectedSrcs.length) return;

        const start = editor.selectionStart;
        const end   = editor.selectionEnd;
        const text  = selectedSrcs.map(src => {
            const name = src.split('/').pop().replace(/\.[^.]+$/, '');
            return `![${name}](${src})`;
        }).join('\n');

        replaceSelection(text, start, end, 'end');
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
        initLibrary();
    }

    function hideLibrary() {
        if (!libraryBackdrop) return;
        libraryBackdrop.classList.add('hidden');
        libraryBackdrop.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    // ── Bildbibliothek ───────────────────────────────────────────────────────

    const libraryNav         = document.getElementById('libraryNav');
    const libraryGrid        = document.getElementById('libraryGrid');
    const libraryGridWrapper = document.getElementById('libraryGridWrapper');
    const libraryDropOverlay = document.getElementById('libraryDropOverlay');
    const libraryFileInput   = document.getElementById('libraryFileInput');
    const libraryCountEl     = document.getElementById('librarySelectionCount');
    const librarySearchEl    = document.getElementById('librarySearch');
    const insertImageBtn     = document.querySelector('[data-action="insert-image"]');

    const libraryConfig = (() => {
        try { return JSON.parse(libraryBackdrop?.dataset.library || '[]'); }
        catch { return []; }
    })();

    let activeDir      = null;
    let allImages      = [];
    let selectedSrcs   = [];
    let libraryInited  = false;

    function initLibrary() {
        if (libraryInited) return;
        libraryInited = true;

        renderLibraryNav();

        if (libraryConfig.length) {
            loadImages(libraryConfig[0]);
        } else {
            setGridEmpty('Keine Verzeichnisse konfiguriert.');
        }
    }

    function renderLibraryNav() {
        if (!libraryNav) return;
        libraryNav.innerHTML = '';

        libraryConfig.forEach((dir, i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = dir.name;
            btn.className = i === 0
                ? 'w-full rounded-xl bg-white px-3 py-2 text-left font-medium shadow-sm ring-1 ring-slate-200'
                : 'w-full rounded-xl px-3 py-2 text-left text-slate-600 hover:bg-white';

            btn.addEventListener('click', () => {
                libraryNav.querySelectorAll('button').forEach(b => {
                    b.className = 'w-full rounded-xl px-3 py-2 text-left text-slate-600 hover:bg-white';
                });
                btn.className = 'w-full rounded-xl bg-white px-3 py-2 text-left font-medium shadow-sm ring-1 ring-slate-200';
                loadImages(dir);
            });

            libraryNav.appendChild(btn);
        });
    }

    async function loadImages(dir) {
        activeDir = dir;
        selectedSrcs = [];
        updateInsertBtn();
        setupDropZone(dir);

        if (!libraryGrid) return;
        libraryGrid.innerHTML = `
            <div class="col-span-full flex items-center justify-center py-16 text-slate-400 text-sm">
                Lade Bilder …
            </div>`;

        try {
            const res = await fetch(dir.path, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error(res.statusText);
            allImages = await res.json();
            renderGrid(allImages);
        } catch {
            setGridEmpty('Bilder konnten nicht geladen werden.');
        }
    }

    function createUploadTile() {
        const label = document.createElement('label');
        label.htmlFor = 'libraryFileInput';
        label.className = 'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 bg-white text-slate-400 transition-colors hover:border-blue-400 hover:bg-blue-50 hover:text-blue-500';
        label.style.aspectRatio = '4/3';
        label.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <span class="text-xs font-medium">Hochladen</span>`;
        return label;
    }

    function renderGrid(images) {
        if (!libraryGrid) return;
        libraryGrid.innerHTML = '';

        if (activeDir?.upload) {
            libraryGrid.appendChild(createUploadTile());
        }

        if (!images.length) {
            const msg = document.createElement('div');
            msg.className = 'col-span-full flex items-center justify-center py-8 text-slate-400 text-sm text-center';
            msg.textContent = activeDir?.upload
                ? 'Noch keine Bilder. Hochladen oder hierher ziehen.'
                : 'Keine Bilder in diesem Verzeichnis.';
            libraryGrid.appendChild(msg);
            return;
        }

        images.forEach(image => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.src = image.src;
            btn.className = 'overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-sm transition-all hover:border-blue-300';

            const thumb = document.createElement('div');
            thumb.className = 'aspect-video overflow-hidden bg-slate-100';
            const img = document.createElement('img');
            img.src = image.src;
            img.alt = image.name;
            img.className = 'h-full w-full object-cover';
            thumb.appendChild(img);

            const info = document.createElement('div');
            info.className = 'p-3';
            info.innerHTML = `<p class="truncate text-sm font-semibold">${image.name}</p>`
                + (image.width ? `<p class="text-xs text-slate-500">${image.width} × ${image.height}</p>` : '');

            btn.appendChild(thumb);
            btn.appendChild(info);

            btn.addEventListener('click', () => {
                const idx = selectedSrcs.indexOf(image.src);
                if (idx === -1) {
                    selectedSrcs.push(image.src);
                    btn.classList.add('border-2', 'border-blue-500');
                    btn.classList.remove('border', 'border-slate-200');
                } else {
                    selectedSrcs.splice(idx, 1);
                    btn.classList.remove('border-2', 'border-blue-500');
                    btn.classList.add('border', 'border-slate-200');
                }
                updateInsertBtn();
            });

            libraryGrid.appendChild(btn);
        });
    }

    function setGridEmpty(message) {
        if (!libraryGrid) return;
        libraryGrid.innerHTML = `<div class="col-span-full flex items-center justify-center py-16 text-slate-400 text-sm text-center">${message}</div>`;
    }

    function updateInsertBtn() {
        if (!libraryCountEl || !insertImageBtn) return;
        const n = selectedSrcs.length;
        libraryCountEl.textContent = n === 1 ? '1 Bild ausgewählt' : `${n} Bilder ausgewählt`;
        insertImageBtn.disabled = n === 0;
    }

    // Suche
    if (librarySearchEl) {
        librarySearchEl.addEventListener('input', () => {
            const q = librarySearchEl.value.trim().toLowerCase();
            renderGrid(q ? allImages.filter(i => i.name.toLowerCase().includes(q)) : allImages);
        });
    }

    // Upload per Datei-Dialog
    if (libraryFileInput) {
        libraryFileInput.addEventListener('change', async () => {
            const files = Array.from(libraryFileInput.files).filter(f => f.type.startsWith('image/'));
            if (files.length && activeDir?.upload) await uploadFiles(files, activeDir);
            libraryFileInput.value = '';
        });
    }

    // Drag & Drop Upload
    let dropZoneAbort = null;

    function setupDropZone(dir) {
        dropZoneAbort?.abort();
        dropZoneAbort = null;

        // Visuelle Hints zurücksetzen
        if (libraryGridWrapper) {
            libraryGridWrapper.style.outline = '';
            libraryGridWrapper.style.background = '';
        }
        if (libraryDropOverlay) {
            libraryDropOverlay.classList.add('hidden');
            libraryDropOverlay.classList.remove('flex');
        }

        if (!dir.upload || !libraryGridWrapper || !libraryDropOverlay) return;

        // Persistente Drop-Zone-Optik: subtiler gestrichelter Rahmen
        libraryGridWrapper.style.outline = '2px dashed #cbd5e1'; // slate-300
        libraryGridWrapper.style.outlineOffset = '-6px';
        libraryGridWrapper.style.borderRadius = '1rem';

        dropZoneAbort = new AbortController();
        const { signal } = dropZoneAbort;
        let depth = 0;

        libraryGridWrapper.addEventListener('dragenter', e => {
            e.preventDefault();
            if (++depth === 1) {
                libraryGridWrapper.style.outline = '2px dashed #60a5fa'; // blue-400
                libraryGridWrapper.style.background = 'rgba(239,246,255,0.6)';
                libraryDropOverlay.classList.remove('hidden');
                libraryDropOverlay.classList.add('flex');
            }
        }, { signal });

        libraryGridWrapper.addEventListener('dragleave', () => {
            if (--depth === 0) {
                libraryGridWrapper.style.outline = '2px dashed #cbd5e1';
                libraryGridWrapper.style.background = '';
                libraryDropOverlay.classList.add('hidden');
                libraryDropOverlay.classList.remove('flex');
            }
        }, { signal });

        libraryGridWrapper.addEventListener('dragover', e => e.preventDefault(), { signal });

        libraryGridWrapper.addEventListener('drop', async e => {
            e.preventDefault();
            depth = 0;
            libraryGridWrapper.style.outline = '2px dashed #cbd5e1';
            libraryGridWrapper.style.background = '';
            libraryDropOverlay.classList.add('hidden');
            libraryDropOverlay.classList.remove('flex');

            const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            if (files.length) await uploadFiles(files, dir);
        }, { signal });
    }

    async function uploadFiles(files, dir) {
        setGridEmpty(`Lade ${files.length} Datei${files.length > 1 ? 'en' : ''} hoch …`);

        const results = await Promise.allSettled(files.map(file => {
            const fd = new FormData();
            fd.append('file', file);
            return fetch(dir.path, { method: 'POST', body: fd });
        }));

        const failed = results.filter(r => r.status === 'rejected' || !r.value?.ok).length;
        if (failed) console.warn(`${failed} Upload(s) fehlgeschlagen.`);

        await loadImages(dir);
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

    // ── localStorage ────────────────────────────────────────────────────────

    const storageKey = editor.dataset.storageKey || null;

    if (storageKey) {
        const saved = localStorage.getItem(storageKey);
        if (saved !== null) editor.value = saved;

        editor.addEventListener('input', () => {
            localStorage.setItem(storageKey, editor.value);
        });
    }

    // ── Sync-Feld ────────────────────────────────────────────────────────────

    const fieldId    = editor.dataset.fieldId || null;
    const mdSyncEl   = document.querySelector('[data-md-sync]');
    let   syncField  = null;

    if (fieldId) {
        const existing = document.getElementById(fieldId);
        if (existing) {
            // Vorhandene Textarea des Users verwenden, PHP-Duplikat entfernen
            syncField = existing;
            mdSyncEl?.remove();
        } else {
            // Keine eigene vorhanden → PHP-gerenderte nutzen und ID zuweisen
            if (mdSyncEl) {
                mdSyncEl.id = fieldId;
                syncField = mdSyncEl;
            }
        }
    }

    if (syncField) {
        if (syncField.value !== '') editor.value = syncField.value;

        editor.addEventListener('input', () => {
            syncField.value = editor.value;
            syncField.textContent = editor.value;
        });
    }

    updatePreview();
})();