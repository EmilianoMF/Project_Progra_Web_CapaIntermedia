// ===========================
// UTILIDAD: mostrar error bajo un campo
// ===========================
function mostrarError(id, mensaje) {
    limpiarError(id);
    const campo = document.getElementById(id);
    const div = document.createElement('div');
    div.id = 'error_' + id;
    div.style.cssText = `
        color: #fca5a5;
        font-size: 13px;
        margin-top: 6px;
        font-weight: 600;
    `;
    div.textContent = '⚠️ ' + mensaje;
    campo.parentNode.insertBefore(div, campo.nextSibling);
    campo.style.borderBottom = '2px solid #ef4444';
}

function limpiarError(id) {
    const prev = document.getElementById('error_' + id);
    if (prev) prev.remove();
    const campo = document.getElementById(id);
    if (campo) campo.style.borderBottom = '';
}

function limpiarTodosLosErrores() {
    ['nombre', 'anio', 'sede', 'resena', 'logotipo', 'imagen'].forEach(limpiarError);
}

// ===========================
// TIPOS DE IMAGEN PERMITIDOS
// ===========================
const TIPOS_IMAGEN = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

function esImagenValida(file) {
    return file && TIPOS_IMAGEN.includes(file.type);
}

// ===========================
// CREAR MUNDIAL — VALIDACIÓN
// ===========================
const formMundial = document.getElementById("formMundial");

formMundial.addEventListener("submit", async (e) => {
    e.preventDefault();
    limpiarTodosLosErrores();

    const nombre   = document.getElementById("nombre").value.trim();
    const anio     = document.getElementById("anio").value.trim();
    const sede     = document.getElementById("sede").value;
    const resena   = document.getElementById("resena").value.trim();
    const logotipo = document.getElementById("logotipo").files[0];
    const imagen   = document.getElementById("imagen").files[0];

    let valido = true;

    if (!nombre) {
        mostrarError("nombre", "El nombre del mundial es obligatorio.");
        valido = false;
    }

    if (!anio || anio < 1900 || anio > 2100) {
        mostrarError("anio", "Ingresa un año válido.");
        valido = false;
    }

    if (!sede) {
        mostrarError("sede", "Selecciona un país sede.");
        valido = false;
    }

    if (!resena) {
        mostrarError("resena", "La reseña es obligatoria.");
        valido = false;
    }

    if (!logotipo) {
        mostrarError("logotipo", "El logotipo es obligatorio.");
        valido = false;
    } else if (!esImagenValida(logotipo)) {
        mostrarError("logotipo", "Solo se permiten imágenes (JPG, PNG, GIF, WEBP).");
        valido = false;
    }

    if (!imagen) {
        mostrarError("imagen", "La imagen es obligatoria.");
        valido = false;
    } else if (!esImagenValida(imagen)) {
        mostrarError("imagen", "Solo se permiten imágenes (JPG, PNG, GIF, WEBP).");
        valido = false;
    }

    if (!valido) return;

    // Todo válido — enviar
    const formData = new FormData();
    formData.append("nombre",   nombre);
    formData.append("anio",     anio);
    formData.append("sede",     sede);
    formData.append("resena",   resena);
    formData.append("logotipo", logotipo);
    formData.append("imagen",   imagen);

    try {
        const response = await fetch("api/crear_mundial.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert("✅ Mundial creado exitosamente.");
            formMundial.reset();
            limpiarTodosLosErrores();
        } else {
            alert("❌ " + data.message);
        }

    } catch (error) {
        alert("❌ Error de servidor.");
    }
});

// Limpiar error al escribir/cambiar
['nombre', 'anio', 'sede', 'resena', 'logotipo', 'imagen'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => limpiarError(id));
    if (el) el.addEventListener('change', () => limpiarError(id));
});

// ===========================
// CARGAR PAÍSES
// ===========================
const selectPais = document.getElementById("sede");

async function cargarPaises() {
    try {
        const response = await fetch("https://restcountries.com/v3.1/all?fields=name");

        if (!response.ok) throw new Error("Error al obtener países");

        const paises = await response.json();

        paises.sort((a, b) => a.name.common.localeCompare(b.name.common));

        selectPais.innerHTML = `<option value="">Selecciona un país</option>`;

        paises.forEach((pais) => {
            const option = document.createElement("option");
            option.value = pais.name.common;
            option.textContent = pais.name.common;
            selectPais.appendChild(option);
        });

    } catch (error) {
        console.error("❌ ERROR:", error);
    }
}

cargarPaises();

// ===========================
// CREAR CATEGORÍA
// ===========================
const formCategoria = document.getElementById("formCategoria");

if (formCategoria) {
    formCategoria.addEventListener("submit", async (e) => {
        e.preventDefault();

        const nombreCategoria = document.getElementById("nombreCategoria").value.trim();

        if (!nombreCategoria) {
            mostrarError("nombreCategoria", "El nombre de la categoría es obligatorio.");
            return;
        }

        try {
            const response = await fetch("api/crear_categoria.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ nombre_categoria: nombreCategoria })
            });

            const data = await response.json();

            if (data.success) {
                alert("✅ Categoría creada.");
                formCategoria.reset();
                limpiarError("nombreCategoria");
            } else {
                alert("❌ " + data.message);
            }

        } catch (error) {
            alert("❌ Error del servidor.");
        }
    });

    document.getElementById("nombreCategoria")
        .addEventListener('input', () => limpiarError("nombreCategoria"));
}