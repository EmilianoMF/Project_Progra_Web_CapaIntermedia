<?php
require_once(__DIR__ . "/BD_Conect.php");

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Validar correo único
    $check = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = :correo");
    $check->bindParam(":correo", $_POST['correo']);
    $check->execute();

    if ($check->fetchColumn() > 0) {
        die("❌ Este correo ya está registrado.");
    }

    $genero = $_POST['gender'];
    $genero_personalizado = null;

    if ($genero === "Otro" && !empty($_POST['genero_personalizado'])) {
        $genero_personalizado = $_POST['genero_personalizado'];
    }

    // 📸 Leer foto
    $foto = null;
    
    if (!isset($_FILES['foto'])) {
    die("❌ No se recibió ninguna fotografía.");
    }

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {

        // Validar que sea imagen real (por MIME del servidor, no del cliente)
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mimeReal = mime_content_type($_FILES['foto']['tmp_name']);

        if (!in_array($mimeReal, $tiposPermitidos)) {
            die("❌ Solo se permiten imágenes (JPG, PNG, GIF, WEBP) como foto de perfil.");
        }

        // Leer foto
        $foto = file_get_contents($_FILES['foto']['tmp_name']);

        $foto = file_get_contents($_FILES['foto']['tmp_name']);
    }

    // Procedimiento almacenado
    $stmt = $conn->prepare("CALL sp_insertar_usuario(
        :nombre,
        :fecha,
        :foto,
        :genero,
        :genero_personalizado,
        :pais,
        :nacionalidad,
        :correo,
        :contrasena
    )");

    $stmt->bindParam(":nombre", $_POST['nombre']);
    $stmt->bindParam(":fecha", $_POST['fecha']);

    // ✅ FOTO COMO BLOB
    $stmt->bindValue(":foto", $foto, PDO::PARAM_LOB);

    $stmt->bindParam(":genero", $genero);
    $stmt->bindParam(":genero_personalizado", $genero_personalizado);
    $stmt->bindParam(":pais", $_POST['pais']);
    $stmt->bindParam(":nacionalidad", $_POST['nacionalidad']);
    $stmt->bindParam(":correo", $_POST['correo']);

    $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
    $stmt->bindParam(":contrasena", $contrasena);

    $stmt->execute();

   echo "<script>
        alert('✅ Usuario registrado correctamente.');
        window.location.href = 'login.html';
    </script>";


} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>