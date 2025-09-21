// Elemente
const editor      = document.getElementById('editor');
const preview     = document.getElementById('preview');
const statsEl     = document.getElementById('stats');
const grid        = document.getElementById('grid');
const toggleBtn   = document.getElementById('toggle');
const formatbar   = document.getElementById('formatbar');
const previewCard = document.getElementById('previewCard');

// Start: nur Editor sichtbar
let previewOpen = false;

// Initial Markdown + Restore
const DEFAULT_MD = `# Willkommen 👋
Dies ist ein **einfacher Markdown-Editor** mit Live-Vorschau.

- Öffne die Vorschau mit dem Auge-Button rechts
- Die Ansicht teilt sich bei breiten Screens

**Fett**, *Kursiv*, \`Code\`
`;

const saved = localStorage.getItem('md-editor-content');
editor.value = saved ?? DEFAULT_MD;

// marked konfigurieren
marked.setOptions({ gfm:true, breaks:false, mangle:false, headerIds:true });

// Rendering
function render(){
  const raw = editor.value;

  if (previewOpen){
    const html = marked.parse(raw);
    preview.innerHTML = DOMPurify.sanitize(html);
  }

  const words = (raw.trim().match(/\b\w+\b/g)||[]).length;
  const chars = raw.length;
  const lines = raw.split("\n").length;
  statsEl.textContent = `${words} Wörter · ${chars} Zeichen · ${lines} Zeilen`;

  localStorage.setItem('md-editor-content', raw);
}
editor.addEventListener('input', render);
render();

// Einfüge-Helper
function insertAtSelection(before, after = ""){
  const start = editor.selectionStart || 0;
  const end   = editor.selectionEnd || 0;
  const sel   = editor.value.slice(start, end);
  editor.value = editor.value.slice(0,start) + before + sel + after + editor.value.slice(end);
  const pos = start + before.length + sel.length + after.length;
  editor.focus(); editor.setSelectionRange(pos, pos);
  render();
}
function surround(prefix, suffix){ insertAtSelection(prefix, suffix ?? prefix); }
function insertLine(prefix){
  const start = editor.selectionStart || editor.value.length;
  const lineStart = editor.value.lastIndexOf("\n", Math.max(0, start-1)) + 1;
  editor.value = editor.value.slice(0,lineStart) + prefix + editor.value.slice(lineStart);
  const pos = lineStart + prefix.length; editor.focus(); editor.setSelectionRange(pos, pos);
  render();
}

// Toggle Vorschau (eigener Listener)
function togglePreview(){
  previewOpen = !previewOpen;
  toggleBtn.setAttribute('aria-pressed', String(previewOpen));
  if (previewOpen){
    previewCard.hidden = false;
    grid.classList.add('is-split');
    preview.innerHTML = DOMPurify.sanitize(marked.parse(editor.value)); // initial render
  } else {
    grid.classList.remove('is-split');
    previewCard.hidden = true;
  }
}
toggleBtn.addEventListener('click', togglePreview);

// Delegation: Formatbar-Buttons (Toggle wird ignoriert)
formatbar.addEventListener('click', (e)=>{
  const btn = e.target.closest('button');
  if (!btn || btn.id === 'toggle') return;
  switch(btn.dataset.action){
    case 'bold':  surround('**'); break;
    case 'italic': surround('*'); break;
    case 'h1':    insertLine('# '); break;
    case 'list':  insertLine('- '); break;
    case 'link':  insertAtSelection('[', '](https://)'); break;
    case 'code':  insertAtSelection('\n```\n','\n```\n'); break;
    case 'table': insertAtSelection('\n| Spalte | Spalte |\n|---|---|\n| A | B |\n'); break;
  }
});

// Shortcuts
document.addEventListener('keydown', (e)=>{
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='b'){ e.preventDefault(); surround('**'); }
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='i'){ e.preventDefault(); surround('*'); }
});
