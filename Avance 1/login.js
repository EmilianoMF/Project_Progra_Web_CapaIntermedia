const params = new URLSearchParams(window.location.search);

const error = params.get("error");

if (error === "correo") {
    alert("❌ El correo no está registrado.");
}

if (error === "password") {
    alert("❌ La contraseña es incorrecta.");
}