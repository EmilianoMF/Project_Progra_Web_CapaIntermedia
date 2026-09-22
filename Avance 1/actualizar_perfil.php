<?php

session_start();

require_once(__DIR__ . "/BD_Conect.php");

header("Content-Type: application/json");

try {

    if (!isset($_SESSION['id_usuario'])) {

        echo json_encode([
            "success" => false,
            "message" => "Sesión no válida."
        ]);

        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();

    // =========================
    // DATOS
    // =========================
    $id_usuario = $_SESSION['id_usuario'];

    $nombre =
    $_POST['nombre'];

    $correo =
    $_POST['correo'];

    $nacionalidad =
    $_POST['nacionalidad'];

    // =========================
    // PASSWORD
    // =========================
    $password = null;

    if (!empty($_POST['password'])) {

        $password =
        password_hash(
            $_POST['password'],
            PASSWORD_BCRYPT
        );
    }

    // =========================
    // FOTO
    // =========================
    $foto = null;

    if (
        isset($_FILES['foto']) &&
        $_FILES['foto']['error'] === UPLOAD_ERR_OK
    ) {

        $foto =
        file_get_contents(
            $_FILES['foto']['tmp_name']
        );
    }

    // =========================
    // PROCEDURE
    // =========================
    $stmt = $conn->prepare("CALL sp_actualizar_usuario(

        :id_usuario,
        :nombre,
        :correo,
        :nacionalidad,
        :password,
        :foto

    )");

    $stmt->bindParam(
        ":id_usuario",
        $id_usuario
    );

    $stmt->bindParam(
        ":nombre",
        $nombre
    );

    $stmt->bindParam(
        ":correo",
        $correo
    );

    $stmt->bindParam(
        ":nacionalidad",
        $nacionalidad
    );

    // PASSWORD
    $stmt->bindValue(
        ":password",
        $password,
        PDO::PARAM_STR
    );

    // FOTO BLOB
    $stmt->bindValue(
        ":foto",
        $foto,
        PDO::PARAM_LOB
    );

    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Perfil actualizado."
    ]);

} catch(PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>