const btnEditar = document.getElementById("btnEditarPerfil");
const contenedor = document.getElementById("contenidoPerfil");

// ===============================
// VALIDAR PASSWORD
// ===============================
function validarPassword(pass) {

    const regex =
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

    return regex.test(pass);
}

// ===============================
// TAMAÑO MÁXIMO FOTO
// ===============================
const MAX_SIZE = 65535;

// ===============================
// CLICK EDITAR PERFIL
// ===============================
btnEditar.addEventListener("click", () => {

    contenedor.innerHTML = `

    <div class="profile-card">

        <h2 class="mb-4 fw-bold">
            Editar Perfil
        </h2>

        <form id="editForm"
              enctype="multipart/form-data">

            <div class="row">

                <!-- NOMBRE -->
                <div class="col-md-6 mb-3">

                    <input type="text"
                           class="form-control custom-input"
                           id="nombre"
                           value="${usuario.nombre}"
                           placeholder="Nombre completo"
                           required>

                </div>

                <!-- CORREO -->
                <div class="col-md-6 mb-3">

                    <input type="email"
                           class="form-control custom-input"
                           id="correo"
                           value="${usuario.correo}"
                           placeholder="Correo"
                           required>

                    <span id="correo-error"
                          class="text-danger small">
                    </span>

                </div>

            </div>

            <div class="row">

                <!-- NACIONALIDAD -->
                <div class="col-md-6 mb-3">

                    <input type="text"
                           class="form-control custom-input"
                           id="nacionalidad"
                           value="${usuario.nacionalidad}"
                           placeholder="Nacionalidad"
                           required>

                </div>

                <!-- PASSWORD -->
                <div class="col-md-6 mb-3">

                    <input type="password"
                           class="form-control custom-input"
                           id="password"
                           placeholder="Nueva contraseña">

                </div>

            </div>

            <!-- FOTO -->
            <div class="mb-4">

                <label for="foto"
                       class="custom-file-upload">

                    <i class="bi bi-cloud-upload"></i>
                    Cambiar Foto

                </label>

                <input type="file"
                       id="foto"
                       name="foto"
                       accept="image/*"
                       hidden>

            </div>

            <!-- PREVIEW -->
            <img id="previewFoto"
                 class="mb-4"
                 style="
                 width:120px;
                 height:120px;
                 object-fit:cover;
                 border-radius:50%;
                 border:2px solid white;
                 display:none;
                 ">

            <!-- BOTÓN -->
            <button type="submit"
                    class="btn btn-light
                           px-5
                           py-2
                           rounded-pill
                           fw-bold">

                Guardar Cambios

            </button>

        </form>

    </div>

    `;

    // ===============================
    // FORM
    // ===============================
    const form = document.getElementById("editForm");

    // ===============================
    // FOTO PREVIEW
    // ===============================
    const fotoInput =
    document.getElementById("foto");

    fotoInput.addEventListener("change", function() {

        const file = this.files[0];

        if (!file) return;

        // Validar imagen
        if (!file.type.startsWith("image/")) {

            alert("❌ Solo se permiten imágenes.");
            return;
        }

        // Validar tamaño
        if (file.size > MAX_SIZE) {

            alert("❌ La imagen supera 64 KB.");
            return;
        }

        // Preview
        const reader = new FileReader();

        reader.onload = function(e) {

            const preview =
            document.getElementById("previewFoto");

            preview.src = e.target.result;
            preview.style.display = "block";
        };

        reader.readAsDataURL(file);

    });

    // ===============================
    // SUBMIT
    // ===============================
    form.addEventListener("submit", async function(e){

        e.preventDefault();

        const password =
        document.getElementById("password").value;

        // ===========================
        // VALIDAR PASSWORD
        // ===========================
        if(password !== ""){

            if(!validarPassword(password)){

                alert(
                    "La contraseña debe tener mínimo 8 caracteres, mayúscula, minúscula, número y carácter especial."
                );

                return;
            }
        }

        // ===========================
        // VALIDAR CORREO
        // ===========================
        const correo =
        document.getElementById("correo").value;

        const correoError =
        document.getElementById("correo-error");

        correoError.textContent = "";

        try {

            const response = await fetch(
                "check_email.php",
                {
                    method: "POST",

                    headers: {
                        "Content-Type":
                        "application/x-www-form-urlencoded"
                    },

                    body:
                    "correo=" +
                    encodeURIComponent(correo)
                }
            );

            const data = await response.json();

            // Si existe y no es el mismo
            if (
                data.exists &&
                correo !== usuario.correo
            ) {

                correoError.textContent =
                "❌ Ese correo ya está registrado.";

                return;
            }

        } catch(err){

            correoError.textContent =
            "❌ Error al validar correo.";

            return;
        }

        // ===========================
        // TODO OK
        // ===========================
        const formData = new FormData();

        formData.append(
            "nombre",
            document.getElementById("nombre").value
        );

        formData.append(
            "correo",
            document.getElementById("correo").value
        );

        formData.append(
            "nacionalidad",
            document.getElementById("nacionalidad").value
        );

        formData.append(
            "password",
            document.getElementById("password").value
        );

        // FOTO
        const foto =
        document.getElementById("foto").files[0];

        if(foto){

            formData.append("foto", foto);
        }

        // ENVIAR
        const response = await fetch(
            "actualizar_perfil.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();

        if(data.success){

            alert("✅ Perfil actualizado.");

            location.reload();

        }else{

            alert("❌ " + data.message);
        }

        // AQUÍ DESPUÉS VAS A HACER:
        // fetch("actualizar_perfil.php")

    });

});