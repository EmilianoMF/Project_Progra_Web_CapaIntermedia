<?php

session_start();

header("Content-Type: application/json");

require_once(__DIR__ . "/../BD_Conect.php");

// SOLO ADMIN
if (
    !isset($_SESSION['id_usuario']) ||
    $_SESSION['rol'] !== "Admin"
) {

    echo json_encode([
        "success" => false,
        "message" => "No autorizado."
    ]);

    exit();
}

try {

    $db = new Database();
    $conn = $db->getConnection();

    // DATOS
    $nombre = $_POST['nombre'];
    $anio = $_POST['anio'];
    $sede = $_POST['sede'];
    $resena = $_POST['resena'];

    // VALIDAR CAMPOS VACÍOS
    if (empty($_POST['nombre']) || empty($_POST['anio']) || 
        empty($_POST['sede'])   || empty($_POST['resena'])) {
        echo json_encode(["success" => false, "message" => "Todos los campos son obligatorios."]);
        exit();
    }

    // VALIDAR IMÁGENES
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (empty($_FILES['logotipo']['tmp_name']) || 
        !in_array(mime_content_type($_FILES['logotipo']['tmp_name']), $tiposPermitidos)) {
        echo json_encode(["success" => false, "message" => "El logotipo debe ser una imagen válida (JPG, PNG, GIF, WEBP)."]);
        exit();
    }

    if (empty($_FILES['imagen']['tmp_name']) || 
        !in_array(mime_content_type($_FILES['imagen']['tmp_name']), $tiposPermitidos)) {
        echo json_encode(["success" => false, "message" => "La imagen debe ser válida (JPG, PNG, GIF, WEBP)."]);
        exit();
    }

    // IMÁGENES
    $logotipo =
    file_get_contents(
        $_FILES['logotipo']['tmp_name']
    );

    $imagen =
    file_get_contents(
        $_FILES['imagen']['tmp_name']
    );

    // PROCEDURE
    $stmt = $conn->prepare("

        CALL sp_crear_mundial(

            :nombre,
            :anio,
            :sede,
            :logotipo,
            :imagen,
            :resena

        )

    ");

    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":anio", $anio);
    $stmt->bindParam(":sede", $sede);

    $stmt->bindParam(
        ":logotipo",
        $logotipo,
        PDO::PARAM_LOB
    );

    $stmt->bindParam(
        ":imagen",
        $imagen,
        PDO::PARAM_LOB
    );

    $stmt->bindParam(":resena", $resena);

    $stmt->execute();

    echo json_encode([
        "success" => true
    ]);

} catch(PDOException $e){

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>