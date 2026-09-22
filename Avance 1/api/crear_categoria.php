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

// RECIBIR JSON
$data =
json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['nombre_categoria']) ||
    empty(trim($data['nombre_categoria']))
) {

    echo json_encode([
        "success" => false,
        "message" => "Nombre vacío."
    ]);

    exit();
}

try {

    $db = new Database();
    $conn = $db->getConnection();

    $nombre =
    trim($data['nombre_categoria']);

    // INSERTAR
    $stmt = $conn->prepare("
        INSERT INTO categorias(nombre_categoria)
        VALUES(:nombre)
    ");

    $stmt->bindParam(":nombre", $nombre);

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