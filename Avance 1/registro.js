const form = document.getElementById("signupForm");
const genderSelect = document.getElementById("gender");
const otherField = document.getElementById("other-gender-field");
const otherInput = otherField.querySelector("input");
const passwordInput = document.getElementById("password");
const photoUpload = document.getElementById("photo-upload");
const preview = document.getElementById("preview");

// Mostrar campo extra si seleccionan "Otro"
genderSelect.addEventListener("change", function() {
    if (this.value === "Otro") {
        otherField.style.display = "block";
    } else {
        otherField.style.display = "none";
        otherInput.value = "";
    }
});

// Validación de contraseña (mínimo 8, mayúscula, minúscula, número, especial)
function validarPassword(pass) {
    const tieneMinuscula = /[a-záéíóúüñ]/.test(pass);
    const tieneMayuscula = /[A-ZÁÉÍÓÚÜÑ]/.test(pass);
    const tieneNumero    = /\d/.test(pass);
    const tieneEspecial  = /[^a-zA-ZáéíóúüÁÉÍÓÚÜñÑ\d\s]/.test(pass);
    const longitudMinima = pass.length >= 8;

    return tieneMinuscula && tieneMayuscula && tieneNumero && tieneEspecial && longitudMinima;
}

// Máximo permitido para BLOB (64 KB)
const MAX_SIZE = 65535; 


form.addEventListener("submit", async function(e) {
    e.preventDefault(); // detener envío


    
    // 1. Primero validar que eligió algo
    if (!genderSelect.value) {
        alert("Por favor selecciona un género.");
        genderSelect.focus();
        return;
    }

    // 2. Luego validar si eligió Otro que lo especifique
    if (genderSelect.value === "Otro" && otherInput.value.trim() === "") {
        alert("Por favor especifica tu género si seleccionas 'Otro'.");
        otherInput.focus();
        return;
    }

    // 🔑 Contraseña
    if (!validarPassword(passwordInput.value)) {
        alert("La contraseña debe tener mínimo 8 caracteres, incluir mayúscula, minúscula, número y un carácter especial.");
        passwordInput.focus();
        return;
    }

    // 🎂 Edad mínima
    const fechaNacimiento = new Date(document.querySelector("[name='fecha']").value);
    const hoy = new Date();
    let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
    const mes = hoy.getMonth() - fechaNacimiento.getMonth();
    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
        edad--;
    }
    if (edad < 12) {
        alert("Debes tener al menos 12 años para registrarte.");
        return;
    }

    // 📧 Correo único
    const correo = document.getElementById("correo").value;
    const correoError = document.getElementById("correo-error");
    correoError.textContent = ""; // limpiar mensaje previo

    try {
        const response = await fetch("check_email.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "correo=" + encodeURIComponent(correo)
        });
        const data = await response.json();

        if (data.exists) {
            correoError.textContent = "❌ Este correo ya está registrado. Por favor usa otro.";
            return; // detener aquí, no enviar
        }
    } catch (err) {
        correoError.textContent = "❌ Error al verificar el correo. Intenta de nuevo.";
        return;
    }

    // Justo antes de e.target.submit():
    const foto = photoUpload.files[0];
    if (!foto) {
        alert("❌ Debes seleccionar una fotografía de perfil.");
        return;
    }
    if (!foto.type.startsWith("image/")) {
        alert("❌ Solo se permiten imágenes como foto de perfil.");
        return;
    }

    // ✅ Si todo pasa, enviar formulario
    e.target.submit();

});



photoUpload.addEventListener("change", function() {
    const file = photoUpload.files[0];

    if (!file) return;

    if (!file.type.startsWith("image/")) {
        alert("❌ Solo se permiten imágenes (JPG, PNG, GIF, WEBP).");
        photoUpload.value = null;  // ← resetea el input, se queda en la página
        preview.style.display = "none";
        return;
    }

    if (file.size > MAX_SIZE) {
        alert("❌ La fotografía excede el tamaño permitido (64 KB).");
        photoUpload.value = null;  // ← resetea el input
        preview.style.display = "none";
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = "block";
    };
    reader.readAsDataURL(file);
});


