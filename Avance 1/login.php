<?php
session_start();

require_once(__DIR__ . "/BD_Conect.php");

try {

    $db = new Database();
    $conn = $db->getConnection();

    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    // Llamar procedimiento
    $stmt = $conn->prepare("CALL sp_login_usuario(:correo)");
    $stmt->bindParam(":correo", $correo);

    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si existe
    if (!$usuario) {
        header("Location: login.html?error=correo");
        exit();
    }

    // Verificar contraseña
    if (!password_verify($contrasena, $usuario['contrasena'])) {
        header("Location: login.html?error=password");
        exit();
    }

    // Crear sesión
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nombre'] = $usuario['nombre_completo'];
    $_SESSION['correo'] = $usuario['correo'];
    $_SESSION['rol'] = $usuario['rol'];

    // Redireccionar
    header("Location: index.php");
    exit();

} catch (PDOException $e) {

    echo "❌ Error: " . $e->getMessage();

}
?>