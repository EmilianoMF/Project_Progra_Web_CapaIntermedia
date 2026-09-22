<?php
session_start();

// SOLO ADMIN
if (
    !isset($_SESSION['id_usuario']) ||
    $_SESSION['rol'] !== "Admin"
) {
    header("Location: index.php");
    exit();
}

require_once(__DIR__ . "/BD_Conect.php");
$db = new Database();
$conn = $db->getConnection();

$mensaje = "";
$error   = "";

// APROBAR O RECHAZAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    $id_publicacion = (int) $_POST['id_publicacion'];
    $accion         = $_POST['accion'];

    try {

        if ($accion === 'aprobar') {

            $stmt = $conn->prepare("CALL sp_aprobar_publicacion(:id)");
            $stmt->bindParam(":id", $id_publicacion);
            $stmt->execute();
            $stmt->closeCursor();
            $mensaje = "✅ Publicación aprobada correctamente.";

        } elseif ($accion === 'rechazar') {

            $stmt = $conn->prepare("CALL sp_rechazar_publicacion(:id)");
            $stmt->bindParam(":id", $id_publicacion);
            $stmt->execute();
            $stmt->closeCursor();
            $mensaje = "🗑️ Publicación rechazada y eliminada.";
        }

    } catch (PDOException $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// CARGAR PUBLICACIONES PENDIENTES
try {
    $pendientes = $conn->query("
        SELECT
            p.id_publicacion,
            p.descripcion,
            p.seleccion,
            p.fecha_elaboracion,
            u.nombre_completo,
            m.nombre   AS mundial,
            m.anio     AS anio,
            c.nombre_categoria,
            (SELECT COUNT(*) FROM publicacion_archivos a WHERE a.id_publicacion = p.id_publicacion) AS total_archivos,
            (SELECT a.tipo FROM publicacion_archivos a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.orden LIMIT 1) AS tipo_primer_archivo,
            (SELECT a.id_archivo FROM publicacion_archivos a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.orden LIMIT 1) AS id_primer_archivo
        FROM publicaciones p
        JOIN usuarios u ON u.id_usuario = p.id_usuario
        JOIN mundiales m ON m.id_mundial = p.id_mundial
        JOIN categorias c ON c.id_categoria = p.id_categoria
        WHERE p.aprobado = FALSE AND p.rechazado = FALSE
        ORDER BY p.fecha_elaboracion DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pendientes = [];
    $error = "❌ Error al cargar publicaciones: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrador - Match.World</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid">

        <a class="navbar-brand fw-bold text-white" href="index.php">
            <i class="bi bi-globe-americas me-2"></i>
            Match.World
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-center" id="navbarContent">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">HOME</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active-link" href="#">ADMINISTRADOR</a>
                </li>
            </ul>
        </div>

    </div>
</nav>

<!-- CONTENIDO -->
<div class="container admin-container">

    <div class="admin-header">
        <h1>Panel Administrador</h1>
        <p>Administra mundiales y publicaciones.</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert-custom alert-success-custom mb-4"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-custom alert-error-custom mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- CREAR MUNDIAL -->
    <div class="admin-card">
        <h3 class="section-title">
            <i class="bi bi-trophy-fill me-2"></i>Crear Mundial
        </h3>
        <form id="formMundial" enctype="multipart/form-data">
            <input type="text" id="nombre" class="form-control custom-input mb-4" placeholder="Nombre del mundial">
            <input type="number" id="anio" class="form-control custom-input mb-4" placeholder="Año">
            <select id="sede" class="form-select custom-input mb-4">
                <option value="">Selecciona un país</option>
            </select>
            <textarea id="resena" class="form-control custom-input textarea-custom mb-4" rows="5" placeholder="Reseña"></textarea>
            <input type="file" id="logotipo" class="form-control custom-input mb-4" accept="image/*">
            <input type="file" id="imagen" class="form-control custom-input mb-4" accept="image/*">
            <button type="submit" class="btn btn-light admin-btn">Crear Mundial</button>
        </form>
    </div>

    <!-- CREAR CATEGORÍA -->
    <div class="admin-card mt-5">
        <h3 class="section-title">
            <i class="bi bi-tags-fill me-2"></i>Crear Categoría
        </h3>
        <form id="formCategoria">
            <div class="mb-4">
                <input type="text" id="nombreCategoria" class="form-control custom-input" placeholder="Nombre de la categoría" required>
            </div>
            <button type="submit" class="btn btn-light admin-btn">Crear Categoría</button>
        </form>
    </div>

    <!-- PUBLICACIONES PENDIENTES -->
    <div class="admin-card mt-5">

        <h3 class="section-title">
            <i class="bi bi-clock-history me-2"></i>
            Publicaciones Pendientes
            <span class="badge-pendientes"><?php echo count($pendientes); ?></span>
        </h3>

        <?php if (empty($pendientes)): ?>

            <div class="no-pendientes">
                <i class="bi bi-check-circle"></i>
                <p>No hay publicaciones pendientes por revisar.</p>
            </div>

        <?php else: ?>

            <?php foreach ($pendientes as $pub): ?>

            <div class="pending-post">

                <!-- MINIATURA -->
                <div class="pending-thumb">
                    <?php if ($pub['id_primer_archivo']): ?>
                        <?php if ($pub['tipo_primer_archivo'] === 'imagen'): ?>
                            <img src="ver_archivo.php?id=<?php echo $pub['id_primer_archivo']; ?>" alt="preview">
                        <?php else: ?>
                            <div class="thumb-video">
                                <i class="bi bi-play-circle-fill"></i>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="thumb-sin-archivo">
                            <i class="bi bi-file-text"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- INFO -->
                <div class="pending-info">

                    <div class="pending-meta">
                        <span class="meta-mundial">
                            <i class="bi bi-trophy me-1"></i>
                            <?php echo htmlspecialchars($pub['mundial']); ?> <?php echo $pub['anio']; ?>
                        </span>
                        <span class="meta-categoria">
                            <?php echo htmlspecialchars($pub['nombre_categoria']); ?>
                        </span>
                        <?php if ($pub['seleccion']): ?>
                            <span class="meta-seleccion">
                                <i class="bi bi-flag me-1"></i>
                                <?php echo htmlspecialchars($pub['seleccion']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="pending-desc">
                        <?php echo htmlspecialchars($pub['descripcion']); ?>
                    </p>

                    <div class="pending-footer">
                        <span class="pending-usuario">
                            <i class="bi bi-person me-1"></i>
                            <?php echo htmlspecialchars($pub['nombre_completo']); ?>
                        </span>
                        <span class="pending-fecha">
                            <i class="bi bi-calendar me-1"></i>
                            <?php echo date('d/m/Y H:i', strtotime($pub['fecha_elaboracion'])); ?>
                        </span>
                        <span class="pending-archivos">
                            <i class="bi bi-paperclip me-1"></i>
                            <?php echo $pub['total_archivos']; ?> archivo(s)
                        </span>
                    </div>

                </div>

                <!-- BOTONES -->
                <div class="buttons-post">

                    <form method="POST">
                        <input type="hidden" name="id_publicacion" value="<?php echo $pub['id_publicacion']; ?>">
                        <input type="hidden" name="accion" value="aprobar">
                        <button type="submit" class="btn btn-success rounded-pill px-4">
                            <i class="bi bi-check-lg me-1"></i> Aprobar
                        </button>
                    </form>

                    <form method="POST">
                        <input type="hidden" name="id_publicacion" value="<?php echo $pub['id_publicacion']; ?>">
                        <input type="hidden" name="accion" value="rechazar">
                        <button type="submit" class="btn btn-danger rounded-pill px-4"
                            onclick="return confirm('¿Seguro que quieres rechazar y eliminar esta publicación?')">
                            <i class="bi bi-x-lg me-1"></i> Rechazar
                        </button>
                    </form>

                </div>

            </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>