<?php
session_start();

require_once(__DIR__ . "/BD_Conect.php");
$db   = new Database();
$conn = $db->getConnection();

// Validar ID de publicación
if (!isset($_GET['id_pub']) || !is_numeric($_GET['id_pub'])) {
    header("Location: index.php");
    exit();
}

$id_pub = (int) $_GET['id_pub'];

// ==========================================
// 1. GESTIÓN DE ACCIONES (Llamando a Procedures)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario'])) {
    $id_usuario = $_SESSION['id_usuario'];
    
    // Si se envía un Comentario
    if (isset($_POST['comentar']) && !empty(trim($_POST['comentario']))) {
        $comentario = trim($_POST['comentario']);
        $stmt = $conn->prepare("CALL sp_agregar_comentario(:pub, :user, :texto)");
        $stmt->execute([':pub' => $id_pub, ':user' => $id_usuario, ':texto' => $comentario]);
    }
    
    // Si se envía un Like (Toggle)
    if (isset($_POST['likear'])) {
        $stmt = $conn->prepare("CALL sp_toggle_like(:pub, :user)");
        $stmt->execute([':pub' => $id_pub, ':user' => $id_usuario]);
    }
    
    // Recargar para evitar reenvío de formulario
    header("Location: ver_publicacion.php?id_pub=" . $id_pub);
    exit();
}

// ==========================================
// 2. REGISTRAR VISTA (Llamando a Procedure)
// ==========================================
// Solo registramos una vista por sesión del usuario para no spamear
if (!isset($_SESSION['vistas_registradas'][$id_pub])) {
    $stmtView = $conn->prepare("CALL sp_registrar_vista(:pub)");
    $stmtView->execute([':pub' => $id_pub]);
    $_SESSION['vistas_registradas'][$id_pub] = true;
}

// ==========================================
// 3. OBTENER DATOS DE LA PUBLICACIÓN
// ==========================================
$sql_pub = "SELECT p.*, u.nombre_completo, c.nombre_categoria, COALESCE(e.vistas, 0) as vistas, COALESCE(e.likes, 0) as likes 
            FROM publicaciones p
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            JOIN categorias c ON p.id_categoria = c.id_categoria
            LEFT JOIN estadisticas e ON p.id_publicacion = e.id_publicacion
            WHERE p.id_publicacion = :id AND p.aprobado = TRUE";
$stmtPub = $conn->prepare($sql_pub);
$stmtPub->execute([':id' => $id_pub]);
$publicacion = $stmtPub->fetch(PDO::FETCH_ASSOC);

if (!$publicacion) {
    echo "Publicación no encontrada o no aprobada.";
    exit();
}

// Obtener toda la multimedia
$stmtMedia = $conn->prepare("SELECT * FROM publicacion_archivos WHERE id_publicacion = :id ORDER BY orden ASC");
$stmtMedia->execute([':id' => $id_pub]);
$archivos = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

// Obtener comentarios
$stmtCom = $conn->prepare("
    SELECT i.comentario, i.fecha, u.nombre_completo 
    FROM interacciones i
    JOIN usuarios u ON i.id_usuario = u.id_usuario
    WHERE i.id_publicacion = :id AND i.tipo = 'Comentario'
    ORDER BY i.fecha DESC
");
$stmtCom->execute([':id' => $id_pub]);
$comentarios = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

// Verificar si el usuario actual ya dio like para pintar el corazón
$usuario_dio_like = false;
if (isset($_SESSION['id_usuario'])) {
    $checkLike = $conn->prepare("SELECT id_interaccion FROM interacciones WHERE id_publicacion = :pub AND id_usuario = :user AND tipo = 'Like'");
    $checkLike->execute([':pub' => $id_pub, ':user' => $_SESSION['id_usuario']]);
    $usuario_dio_like = ($checkLike->rowCount() > 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Publicación - Match.World</title>
    <!-- Enlace a Bootstrap y Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Tu archivo CSS separado -->
    <link rel="stylesheet" href="ver_publicacion.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container">
    <div class="mb-3 mt-4">
        <!-- Botón para regresar al mundial específico de esta publicación -->
        <a href="detalle_mundial.php?id=<?php echo $publicacion['id_mundial']; ?>" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al Mundial
        </a>
    </div>

    <div class="post-container">
        
        <!-- HEADER -->
        <div class="post-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($publicacion['nombre_completo']); ?></h5>
                <small class="text-white-50"><?php echo date('d/m/Y H:i', strtotime($publicacion['fecha_aprobacion'])); ?></small>
            </div>
            <div class="mt-2">
                <span class="badge bg-primary text-light me-1"><?php echo htmlspecialchars($publicacion['nombre_categoria']); ?></span>
                <?php if($publicacion['seleccion']): ?>
                    <span class="badge bg-success text-light"><i class="bi bi-flag me-1"></i><?php echo htmlspecialchars($publicacion['seleccion']); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- MULTIMEDIA -->
        <div class="media-container">
            <?php if (empty($archivos)): ?>
                <div class="p-5 text-white-50">Sin archivos multimedia</div>
            <?php else: ?>
                <!-- Carrusel de Bootstrap -->
                <div id="mediaCarousel" class="carousel slide" data-bs-ride="false">
                    <div class="carousel-inner">
                        <?php foreach ($archivos as $index => $archivo): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <?php if ($archivo['tipo'] === 'imagen'): ?>
                                    <img src="ver_archivo.php?id=<?php echo $archivo['id_archivo']; ?>" class="media-item" alt="Media">
                                <?php else: ?>
                                    <video src="ver_archivo.php?id=<?php echo $archivo['id_archivo']; ?>" class="media-item-video" controls></video>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Mostrar controles solo si hay más de 1 archivo -->
                    <?php if (count($archivos) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#mediaCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#mediaCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- DESCRIPCIÓN -->
        <div class="post-body">
            <p class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($publicacion['descripcion']); ?></p>
        </div>

        <!-- BARRA DE ESTADÍSTICAS Y LIKES -->
        <div class="stats-bar align-items-center">
            <span class="text-white-50"><i class="bi bi-eye"></i> <?php echo $publicacion['vistas']; ?> vistas</span>
            
            <form method="POST" class="m-0 ms-auto">
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <button type="submit" name="likear" class="btn-like <?php echo $usuario_dio_like ? 'liked' : ''; ?>">
                        <i class="bi <?php echo $usuario_dio_like ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                        <?php echo $publicacion['likes']; ?> Likes
                    </button>
                <?php else: ?>
                    <span class="btn-like text-white-50" title="Inicia sesión para dar like">
                        <i class="bi bi-heart"></i> <?php echo $publicacion['likes']; ?> Likes
                    </span>
                <?php endif; ?>
            </form>
        </div>

        <!-- SECCIÓN DE COMENTARIOS -->
        <div class="comments-section">
            <h6 class="mb-3"><i class="bi bi-chat-left-text me-2"></i>Comentarios (<?php echo count($comentarios); ?>)</h6>
            
            <!-- Formulario de comentario (Solo Logeados) -->
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="comentario" class="form-control bg-dark text-white border-secondary" placeholder="Escribe un comentario..." required>
                        <button type="submit" name="comentar" class="btn btn-primary">Comentar</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-secondary py-2 text-center small border-secondary bg-dark text-white">
                    <a href="login.html" class="text-decoration-none fw-bold text-info">Inicia sesión</a> para dejar un comentario.
                </div>
            <?php endif; ?>

            <!-- Lista de comentarios -->
            <div class="comments-list">
                <?php if (empty($comentarios)): ?>
                    <p class="text-white-50 small text-center">No hay comentarios aún. ¡Sé el primero!</p>
                <?php else: ?>
                    <?php foreach ($comentarios as $com): ?>
                        <div class="comment-box">
                            <div class="d-flex justify-content-between">
                                <strong class="small text-info"><?php echo htmlspecialchars($com['nombre_completo']); ?></strong>
                                <small class="text-white-50" style="font-size: 11px;"><?php echo date('d/m/Y H:i', strtotime($com['fecha'])); ?></small>
                            </div>
                            <p class="mb-0 mt-1 small text-light"><?php echo htmlspecialchars($com['comentario']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>