const API_URL = './api';

// State
let currentUser = JSON.parse(localStorage.getItem('user')) || null;

// DOM Elements
const authSection = document.getElementById('auth-section');
const appSidebar = document.getElementById('app-sidebar');
const appMain = document.getElementById('app-main');
const userDisplayName = document.getElementById('user-display-name');
const inputText = document.getElementById('input-text');
const outputText = document.getElementById('output-text');
const wordCountSpan = document.querySelector('.controls span');
const paraphraseBtn = document.querySelector('.btn-primary');

// Init
document.addEventListener('DOMContentLoaded', () => {
    if (currentUser) {
        showApp();
    } else {
        showAuth();
    }

    // Attach Event Listeners
    document.getElementById('login-form').addEventListener('submit', handleLogin);
    document.getElementById('register-form').addEventListener('submit', handleRegister);

    // Editor Listeners
    if (inputText) {
        // Use 'input' for content changes
        inputText.addEventListener('input', updateWordCount);

        // Handle persisted selection globally to catch drag-release outside
        document.addEventListener('mouseup', handleSelection);
        inputText.addEventListener('keyup', handleSelection);
    }

    if (outputText) {
        outputText.addEventListener('input', updateOutputWordCount);
    }
});

function updateWordCount() {
    const fullText = inputText.innerText; // Use innerText for div
    const totalWords = fullText.trim() ? fullText.trim().split(/\s+/).length : 0;

    // If there is a highlight, count that.
    const highlighted = inputText.querySelector('.highlight');
    let label = `${totalWords} Palabras`;

    // Button text update logic removed as per new requirements
    // if (highlighted) { ... } else { ... }

    wordCountSpan.textContent = label;
}

function updateOutputWordCount() {
    const fullText = outputText.value;
    const totalWords = fullText.trim() ? fullText.trim().split(/\s+/).length : 0;
    document.getElementById('output-word-count').textContent = `${totalWords} Palabras`;
}


// Variables para tooltip
let currentTooltip = null;

function handleSelection(e) {
    const selection = window.getSelection();

    // Comprobar si hay selección y si es válida
    if (!selection.rangeCount) return;

    // Verificar si la selección está dentro del editor
    const isSelectionInInput = inputText.contains(selection.anchorNode) || inputText.contains(selection.focusNode);

    if (isSelectionInInput && !selection.isCollapsed) {
        // Obtenemos el texto
        const text = selection.toString().trim();

        if (text.length > 0) {
            removeHighlights();
            try {
                const range = selection.getRangeAt(0);
                const span = document.createElement('span');
                span.className = 'highlight';
                range.surroundContents(span);

                // Re-seleccionar para UX
                selection.removeAllRanges();
                const newRange = document.createRange();
                newRange.selectNodeContents(span);
                selection.addRange(newRange);
                
                // --- Lógica de Sinónimos ---
                // Solo si es una sola palabra (sin espacios intermedios)
                if (!/\s/.test(text)) {
                    const rect = span.getBoundingClientRect();
                    showSynonymsTooltip(text, rect);
                } else {
                    removeSynonymTooltip();
                }
                // ---------------------------

            } catch (err) {
                console.log('Selection error', err);
            }
        }
    } else if (isSelectionInInput && selection.isCollapsed) {
        // Clic dentro sin selección -> limpiar
        if (inputText.contains(e.target) || e.target === inputText) {
            removeHighlights();
            removeSynonymTooltip();
        }
    } else {
        // Clic fuera -> limpiar tooltip si no es clic en el tooltip mismo
        if (e.target.closest('.synonym-tooltip')) return;
        removeSynonymTooltip();
    }

    updateWordCount();
} // End handleSelection

// Funciones para Sinónimos

function showSynonymsTooltip(word, rect) {
    removeSynonymTooltip();

    fetch(`${API_URL}/synonyms.php`, {
        method: 'POST',
        body: JSON.stringify({ text: word })
    })
    .then(res => res.json())
    .then(data => {
        if (data.synonyms && data.synonyms.length > 0) {
            renderTooltip(data.synonyms, rect);
        }
    })
    .catch(err => console.error('Error fetching synonyms:', err));
}

function renderTooltip(synonyms, rect) {
    const tooltip = document.createElement('div');
    tooltip.className = 'synonym-tooltip';
    
    // Posicionamiento
    tooltip.style.left = `${rect.left + window.scrollX}px`;
    tooltip.style.top = `${rect.bottom + window.scrollY + 8}px`; // +8px gap

    synonyms.forEach(syn => {
        const item = document.createElement('span');
        item.className = 'synonym-item';
        item.textContent = syn;
        item.onclick = (e) => {
            e.stopPropagation();
            replaceSelectionWithSynonym(syn);
        };
        tooltip.appendChild(item);
    });

    document.body.appendChild(tooltip);
    currentTooltip = tooltip;
}

function removeSynonymTooltip() {
    if (currentTooltip) {
        currentTooltip.remove();
        currentTooltip = null;
    }
    const existing = document.querySelector('.synonym-tooltip');
    if (existing) existing.remove();
}

function replaceSelectionWithSynonym(synonym) {
    const highlight = inputText.querySelector('.highlight');
    if (highlight) {
        highlight.textContent = synonym;
        // Opcional: mantener el highlight o quitarlo. 
        // Si queremos quitarlo y dejar el texto plano:
        // const parent = highlight.parentNode;
        // parent.replaceChild(document.createTextNode(synonym), highlight);
        // Pero mantenerlo permite seguir viendo que fue editado o volver a cambiar.
        // Vamos a mantener el highlight y actualizar el conteo.
        
        // Remove tooltip logic handles itself
        removeSynonymTooltip();
        updateWordCount();
    }
}

function removeHighlights() {
    const highlights = inputText.querySelectorAll('.highlight');
    highlights.forEach(span => {
        const parent = span.parentNode;
        while (span.firstChild) {
            parent.insertBefore(span.firstChild, span);
        }
        parent.removeChild(span);
    });
    inputText.normalize();
}

// Auth Functions
function showAuth() {
    authSection.style.display = 'flex';
    appSidebar.style.display = 'none';
    appMain.style.display = 'none';
}

function showApp() {
    authSection.style.display = 'none';
    appSidebar.style.display = 'flex';
    appMain.style.display = 'block';
    userDisplayName.textContent = currentUser.username;
}

function toggleAuth(mode) {
    if (mode === 'register') {
        document.getElementById('login-form').style.display = 'none';
        document.getElementById('register-form').style.display = 'block';
    } else {
        document.getElementById('login-form').style.display = 'block';
        document.getElementById('register-form').style.display = 'none';
    }
}

async function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    try {
        const res = await fetch(`${API_URL}/auth.php`, {
            method: 'POST',
            body: JSON.stringify({ action: 'login', email, password })
        });
        const data = await res.json();

        if (data.success) {
            currentUser = data.user;
            localStorage.setItem('user', JSON.stringify(currentUser));
            showApp();
        } else {
            alert(data.message || 'Error al iniciar sesión');
        }
    } catch (err) {
        console.error(err);
        alert('Error al conectar con el servidor');
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const username = document.getElementById('reg-username').value;
    const email = document.getElementById('reg-email').value;
    const password = document.getElementById('reg-password').value;

    try {
        const res = await fetch(`${API_URL}/auth.php`, {
            method: 'POST',
            body: JSON.stringify({ action: 'register', username, email, password })
        });
        const data = await res.json();

        if (data.success) {
            alert('Registro exitoso. Por favor inicia sesión.');
            toggleAuth('login');
        } else {
            alert(data.message || 'Error en el registro');
        }
    } catch (err) {
        console.error(err);
        alert('Error al conectar con el servidor');
    }
}

function logout() {
    currentUser = null;
    localStorage.removeItem('user');
    showAuth();
}

// App Logic
function switchView(view) {
    document.getElementById('view-editor').style.display = 'none';
    document.getElementById('view-history').style.display = 'none';

    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));

    if (view === 'editor') {
        document.getElementById('view-editor').style.display = 'block';
        document.querySelector('a[onclick="switchView(\'editor\')"]').classList.add('active');
        document.getElementById('project-title-input').value = 'Nuevo Proyecto';
    } else if (view === 'history') {
        document.getElementById('view-history').style.display = 'block';
        document.querySelector('a[onclick="switchView(\'history\')"]').classList.add('active');
        document.getElementById('project-title-input').value = 'Mi Biblioteca';
        loadHistory();
    }
}




// Auto-save logic
let autoSaveTimeout;
const autoSaveDelay = 2000; // 2 seconds

function triggerAutoSave() {
    const statusSpan = document.getElementById('save-status');
    statusSpan.textContent = 'Guardando...';

    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        saveProject(true);
    }, autoSaveDelay);
}

// Add listeners for auto-save
document.addEventListener('DOMContentLoaded', () => {
    // ... existing listeners ...

    const inputs = [inputText, outputText, document.getElementById('project-title-input')];
    inputs.forEach(el => {
        if (el) {
            el.addEventListener('input', triggerAutoSave);
            // Also trigger on contenteditable changes if needed, but 'input' covers it for divs and textareas
        }
    });
});

async function saveProject(silent = false) {
    const title = document.getElementById('project-title-input').value;
    const originalText = inputText.innerText;
    const paraphrasedText = outputText.value;
    const saveStatus = document.getElementById('save-status');

    if (!originalText.trim()) {
        if (!silent) alert('El proyecto está vacío.');
        return;
    }

    const saveBtn = document.querySelector('.btn-primary[onclick="saveProject()"]');
    let originalBtnText = '';

    if (!silent) {
        originalBtnText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Guardando...';
        saveBtn.disabled = true;
    }

    try {
        const res = await fetch(`${API_URL}/save_project.php`, {
            method: 'POST',
            body: JSON.stringify({
                user_id: currentUser.id,
                title: title,
                original_text: originalText,
                paraphrased_text: paraphrasedText
            })
        });
        const data = await res.json();

        if (data.success) {
            if (!silent) {
                alert('Proyecto guardado exitosamente');
            } else {
                saveStatus.textContent = 'Guardado';
                setTimeout(() => { saveStatus.textContent = ''; }, 3000);
            }
            loadHistory();
        } else {
            console.error('Auto-save failed:', data.message);
            if (!silent) alert(data.message || 'Error al guardar el proyecto');
            if (silent) saveStatus.textContent = 'Error al guardar';
        }

    } catch (err) {
        console.error(err);
        if (!silent) alert('Error al conectar con el servidor');
        if (silent) saveStatus.textContent = 'Error de conexión';
    } finally {
        if (!silent) {
            saveBtn.innerHTML = originalBtnText;
            saveBtn.disabled = false;
        }
    }
}


async function paraphraseText() {
    const originalText = inputText.innerText;

    if (!originalText.trim()) return alert('Por favor ingresa un texto para parafrasear');

    const btn = document.getElementById('btn-paraphrase');
    const originalBtnText = btn.innerHTML;
    btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Procesando...';
    btn.disabled = true;

    try {
        const res = await fetch(`${API_URL}/paraphrase.php`, {
            method: 'POST',
            body: JSON.stringify({
                text: originalText,
                user_id: currentUser.id,
                mode: 'creative'
            })
        });
        const data = await res.json();

        // Typewriter effect
        outputText.value = '';
        let i = 0;
        const typeWriter = () => {
            if (i < data.paraphrased.length) {
                outputText.value += data.paraphrased.charAt(i);
                i++;
                setTimeout(typeWriter, 10);
            } else {
                updateOutputWordCount();
            }
        };
        typeWriter();

    } catch (err) {
        console.error(err);
        alert('Error al procesar el texto');
    } finally {
        btn.innerHTML = originalBtnText;
        btn.disabled = false;
    }
}

// Copy to clipboard with feedback
document.querySelector('.ri-file-copy-line').onclick = function () {
    const text = outputText.value;
    if (!text) return;

    navigator.clipboard.writeText(text).then(() => {
        const originalTitle = this.getAttribute('title');
        this.setAttribute('title', '¡Copiado!');
        this.style.color = 'var(--secondary)';

        setTimeout(() => {
            this.setAttribute('title', originalTitle);
            this.style.color = '';
        }, 2000);
    });
};



function clearText() {
    inputText.innerText = ''; // Use innerText
    outputText.value = '';
    updateWordCount();
    updateOutputWordCount();
}

async function loadHistory() {
    const container = document.getElementById('history-container');
    container.innerHTML = '<p style="color:var(--text-muted)">Cargando...</p>';

    try {
        const res = await fetch(`${API_URL}/history.php?user_id=${currentUser.id}`);
        const projects = await res.json();

        container.innerHTML = '';
        if (projects.length === 0) {
            container.innerHTML = '<p style="color:var(--text-muted)">No hay historial disponible.</p>';
            return;
        }

        projects.forEach(p => {
            const card = document.createElement('div');
            card.className = 'history-card fade-in';
            // Add click handler to load project
            card.onclick = () => loadProjectIntoEditor(p);
            card.style.cursor = 'pointer';
            card.style.position = 'relative';

            card.innerHTML = `
                <div style="position: absolute; top: 10px; right: 10px;">
                    <button class="btn-icon-delete" onclick="deleteProject(event, ${p.id})" title="Eliminar">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
                <div style="margin-bottom: 0.5rem; color: var(--primary); font-weight: 600; padding-right: 2rem;">${p.title}</div>
                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">
                    ${new Date(p.created_at).toLocaleDateString()}
                </div>
                <div style="font-size: 0.95rem; line-height: 1.5; color: var(--text-main); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                    ${p.paraphrased_text || p.original_text}
                </div>
            `;
            container.appendChild(card);
        });
    } catch (err) {
        container.innerHTML = '<p>Error loading history</p>';
    }
}

// Make functions global
window.loadProjectIntoEditor = function (project) {
    // Switch to editor view
    switchView('editor');

    // Populate fields
    document.getElementById('project-title-input').value = project.title;
    inputText.innerText = project.original_text || '';
    outputText.value = project.paraphrased_text || '';
    updateWordCount();
    updateOutputWordCount();
};

window.deleteProject = async function (e, id) {
    console.log('Deleting project', id);
    if (e) {
        e.stopPropagation(); // Prevent card click
        e.preventDefault();
    }

    if (!confirm('¿Estás seguro de que deseas eliminar este proyecto?')) return;

    try {
        const res = await fetch(`${API_URL}/delete_project.php`, {
            method: 'POST',
            body: JSON.stringify({
                id: id,
                user_id: currentUser.id
            })
        });
        const data = await res.json();

        if (data.success) {
            // Reload history logic
            loadHistory();
        } else {
            alert(data.message || 'Error al eliminar');
        }
    } catch (err) {
        console.error(err);
        alert('Error al conectar con el servidor');
    }
};

let recognition;
let isRecording = false;

window.toggleDictation = function () {
    const micBtn = document.getElementById('mic-btn');
    const outputArea = document.getElementById('output-text');

    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        alert('Lo sentimos, tu navegador no soporta dictado por voz.');
        return;
    }

    if (isRecording) {
        recognition.stop();
        return;
    }

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.lang = 'es-ES';
    recognition.interimResults = false;
    recognition.continuous = true;

    recognition.onstart = function () {
        isRecording = true;
        micBtn.classList.add('recording');
        micBtn.classList.remove('ri-mic-line');
        micBtn.classList.add('ri-mic-fill');
    };

    recognition.onend = function () {
        isRecording = false;
        micBtn.classList.remove('recording');
        micBtn.classList.add('ri-mic-line');
        micBtn.classList.remove('ri-mic-fill');
    };

    recognition.onerror = function (event) {
        console.error('Speech recognition error', event.error);
        recognition.stop();
    };

    recognition.onresult = function (event) {
        const transcript = event.results[event.results.length - 1][0].transcript;

        // Append text where cursor is or at end
        // Simple append for now
        outputArea.value += (outputArea.value.length > 0 ? ' ' : '') + transcript;
        updateOutputWordCount();
    };

    recognition.start();
};
