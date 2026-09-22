const inputArchivos    = document.getElementById('archivos');
const previewContainer = document.getElementById('previewContainer');
const uploadArea       = document.getElementById('uploadArea');
const formulario       = document.querySelector('form');

// Tipos permitidos
const TIPOS_IMAGEN = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const TIPOS_VIDEO  = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/avi'];
const TIPOS_VALIDOS = [...TIPOS_IMAGEN, ...TIPOS_VIDEO];

// ===========================
// VALIDAR ARCHIVOS
// ===========================
function validarArchivos(archivos) {
    const invalidos = [];

    Array.from(archivos).forEach((file) => {
        if (!TIPOS_VALIDOS.includes(file.type)) {
            invalidos.push(file.name);
        }
    });

    return invalidos;
}

// ===========================
// MOSTRAR ERROR DE VALIDACIÓN
// ===========================
function mostrarErrorArchivos(nombresInvalidos) {
    let errorEl = document.getElementById('errorArchivos');

    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.id = 'errorArchivos';
        errorEl.style.cssText = `
            background: rgba(220,38,38,0.15);
            border: 1px solid rgba(220,38,38,0.3);
            color: #fca5a5;
            border-radius: 12px;
            padding: 12px 16px;
            margin-top: 10px;
            font-size: 14px;
            font-weight: 600;
        `;
        uploadArea.parentNode.insertBefore(errorEl, previewContainer);
    }

    errorEl.innerHTML = `
        ⚠️ Los siguientes archivos no son válidos y fueron ignorados:<br>
        <span style="font-weight:400; opacity:0.85">${nombresInvalidos.join(', ')}</span><br>
        <small style="opacity:0.6">Solo se aceptan imágenes (JPG, PNG, GIF, WEBP) y videos (MP4, MOV, AVI).</small>
    `;
}

function limpiarErrorArchivos() {
    const errorEl = document.getElementById('errorArchivos');
    if (errorEl) errorEl.remove();
}

// ===========================
// GENERAR PREVIEW
// ===========================
function generarPreview(archivos) {
    previewContainer.innerHTML = '';
    limpiarErrorArchivos();

    const validos   = Array.from(archivos).filter(f => TIPOS_VALIDOS.includes(f.type));
    const invalidos = Array.from(archivos).filter(f => !TIPOS_VALIDOS.includes(f.type));

    if (invalidos.length > 0) {
        mostrarErrorArchivos(invalidos.map(f => f.name));
    }

    validos.forEach((file) => {
        const reader = new FileReader();
        const item   = document.createElement('div');
        item.classList.add('preview-item');

        const tipo = file.type.startsWith('video') ? 'video' : 'imagen';

        reader.onload = (e) => {
            if (tipo === 'imagen') {
                item.innerHTML = `
                    <img src="${e.target.result}" alt="preview">
                    <span class="preview-tipo">imagen</span>
                `;
            } else {
                item.innerHTML = `
                    <video src="${e.target.result}" muted></video>
                    <span class="preview-tipo">video</span>
                `;
            }
            previewContainer.appendChild(item);
        };

        reader.readAsDataURL(file);
    });

    return validos;
}

// ===========================
// EVENTO: SELECCIÓN DE ARCHIVOS
// ===========================
inputArchivos.addEventListener('change', () => {
    generarPreview(inputArchivos.files);
});

// ===========================
// DRAG & DROP
// ===========================
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    inputArchivos.files = e.dataTransfer.files;
    inputArchivos.dispatchEvent(new Event('change'));
});

// ===========================
// VALIDACIÓN AL ENVIAR
// ===========================
formulario.addEventListener('submit', (e) => {
    const archivos  = inputArchivos.files;
    const invalidos = validarArchivos(archivos);

    if (invalidos.length > 0 && invalidos.length === archivos.length) {
        // Todos los archivos son inválidos
        e.preventDefault();
        mostrarErrorArchivos(invalidos);
        return;
    }
});