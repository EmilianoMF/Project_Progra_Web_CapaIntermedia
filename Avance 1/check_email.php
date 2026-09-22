<?php
require_once(__DIR__ . "/BD_Conect.php");

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = :correo");
    $stmt->bindParam(":correo", $_POST['correo']);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    header('Content-Type: application/json');
    echo json_encode(["exists" => $count > 0]);
    
} catch (PDOException $e) {
    
    header('Content-Type: application/json');
    echo json_encode(["error" => $e->getMessage()]);
}
?>
