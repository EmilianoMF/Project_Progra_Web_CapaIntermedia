<?php
session_start();

require_once(__DIR__ . "/BD_Conect.php");
$db   = new Database();
$conn = $db->getConnection();

// --- MODO 1: archivo de publicacion_archivos (?id=X) ---
if (isset($_GET['id']) && is_numeric($_GET['id']) && !isset($_GET['tabla'])) {

    $id   = (int) $_GET['id'];

    // Solo mostramos archivos de publicaciones aprobadas y no rechazadas
    // Si el usuario no está logueado, solo puede ver archivos de publicaciones públicas (aprobadas)
    $stmt = $conn->prepare("
        SELECT pa.contenido, pa.tipo
        FROM publicacion_archivos pa
        JOIN publicaciones p ON p.id_publicacion = pa.id_publicacion
        WHERE pa.id_archivo = :id
          AND (
              p.aprobado = TRUE AND p.rechazado = FALSE
              OR :id_usuario IS NOT NULL
          )
    ");

    $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":id_usuario", $id_usuario);
    $stmt->execute();
    $archivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$archivo) { http_response_code(404); exit(); }

    header("Content-Type: " . ($archivo['tipo'] === 'video' ? "video/mp4" : "image/jpeg"));
    echo $archivo['contenido'];
    exit();
}

// --- MODO 2: imagen/logotipo de mundiales (?tabla=mundiales&campo=logotipo&id=X) ---
if (isset($_GET['tabla'], $_GET['campo'], $_GET['id'])) {

    $id    = (int) $_GET['id'];
    $campo = $_GET['campo'] === 'logotipo' ? 'logotipo' : 'imagen';

    $stmt = $conn->prepare("SELECT {$campo} FROM mundiales WHERE id_mundial = :id");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fila || !$fila[$campo]) { http_response_code(404); exit(); }

    header("Content-Type: image/jpeg");
    echo $fila[$campo];
    exit();
}

http_response_code(400);