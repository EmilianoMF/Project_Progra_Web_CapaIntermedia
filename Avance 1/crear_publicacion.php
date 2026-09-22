<?php
session_start();

// Solo usuarios logueados
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.html");
    exit();
}

require_once(__DIR__ . "/BD_Conect.php");
$db = new Database();
$conn = $db->getConnection();

// Cargar mundiales
$mundiales = $conn->query("SELECT id_mundial, nombre, anio FROM mundiales ORDER BY anio DESC")->fetchAll(PDO::FETCH_ASSOC);

// Cargar categorías
$categorias = $conn->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC")->fetchAll(PDO::FETCH_ASSOC);

$mensaje = "";
$error = "";

// PROCESAR FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_mundial   = $_POST['id_mundial'];
    $id_categoria = $_POST['id_categoria'];
    $seleccion    = !empty($_POST['seleccion']) ? $_POST['seleccion'] : null;
    $descripcion  = $_POST['descripcion'];
    $id_usuario   = $_SESSION['id_usuario'];

    try {

        // 1. Insertar publicación
        $stmt = $conn->prepare("
            CALL sp_crear_publicacion(
                :id_usuario,
                :id_mundial,
                :id_categoria,
                :seleccion,
                :descripcion
            )
        ");

        $stmt->bindParam(":id_usuario",   $id_usuario);
        $stmt->bindParam(":id_mundial",   $id_mundial);
        $stmt->bindParam(":id_categoria", $id_categoria);
        $stmt->bindParam(":seleccion",    $seleccion);
        $stmt->bindParam(":descripcion",  $descripcion);
        $stmt->execute();

        // Obtener el id de la publicación recién creada
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_publicacion = $result['id_publicacion'];
        $stmt->closeCursor();

        // 2. Insertar archivos si hay
        if (!empty($_FILES['archivos']['name'][0])) {

            $stmtArchivo = $conn->prepare("
                CALL sp_crear_archivo_publicacion(
                    :id_publicacion,
                    :contenido,
                    :tipo,
                    :orden
                )
            ");

            // Tipos permitidos en el servidor
            $tiposPermitidos = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/avi'
            ];

            $total = count($_FILES['archivos']['name']);

            for ($i = 0; $i < $total; $i++) {

                if ($_FILES['archivos']['error'][$i] === 0) {

                    $mime = mime_content_type($_FILES['archivos']['tmp_name'][$i]);

                    // Validación servidor
                    if (!in_array($mime, $tiposPermitidos)) {
                        continue; // ignora archivos inválidos
                    }

                    $contenido = file_get_contents($_FILES['archivos']['tmp_name'][$i]);
                    $tipo      = str_starts_with($mime, 'video') ? 'video' : 'imagen';
                    $orden     = $i + 1;

                    $stmtArchivo->bindParam(":id_publicacion", $id_publicacion);
                    $stmtArchivo->bindParam(":contenido",      $contenido, PDO::PARAM_LOB);
                    $stmtArchivo->bindParam(":tipo",           $tipo);
                    $stmtArchivo->bindParam(":orden",          $orden);
                    $stmtArchivo->execute();
                    $stmtArchivo->closeCursor();
                }
            }
        }

        $mensaje = "✅ Publicación enviada. Será visible una vez que el administrador la apruebe.";

    } catch (PDOException $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Publicación - Match.World</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="crear_publicacion.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid">

        <a class="navbar-brand fw-bold text-white" href="index.php">
            <i class="bi bi-bell me-2"></i>for
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
                    <a class="nav-link" href="index.php#mundiales">MUNDIALES</a>
                </li>

                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === "Admin"): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin.php">ADMINISTRADOR</a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link active-link" href="#">PUBLICAR</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="perfil.php">MI PERFIL</a>
                </li>


            </ul>
        </div>
    </div>
</nav>

<!-- CONTENIDO -->
<div class="container pub-container">

    <div class="pub-header">
        <h1><i class="bi bi-plus-circle me-3"></i>Nueva Publicación</h1>
        <p>Comparte un momento memorable de los mundiales.</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert-custom alert-success-custom">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-custom alert-error-custom">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="pub-card">
        <form method="POST" enctype="multipart/form-data">

            <!-- MUNDIAL -->
            <div class="mb-4">
                <label class="form-label">Mundial</label>
                <select name="id_mundial" class="form-select custom-select" required>
                    <option value="">Selecciona un mundial</option>
                    <?php foreach ($mundiales as $m): ?>
                        <option value="<?php echo $m['id_mundial']; ?>">
                            <?php echo $m['nombre'] . " " . $m['anio']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- CATEGORÍA -->
            <div class="mb-4">
                <label class="form-label">Categoría</label>
                <select name="id_categoria" class="form-select custom-select" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?php echo $c['id_categoria']; ?>">
                            <?php echo $c['nombre_categoria']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- SELECCIÓN -->
            <div class="mb-4">
                <label class="form-label">
                    Selección <span class="optional">(opcional)</span>
                </label>
                <input
                    type="text"
                    name="seleccion"
                    class="form-control custom-input"
                    placeholder="Ej: Brasil, Argentina, Francia..."
                >
            </div>

            <!-- DESCRIPCIÓN -->
            <div class="mb-4">
                <label class="form-label">Descripción</label>
                <textarea
                    name="descripcion"
                    class="form-control custom-input textarea-custom"
                    rows="4"
                    placeholder="Describe tu publicación..."
                    required
                ></textarea>
            </div>

            <!-- ARCHIVOS -->
            <div class="mb-4">
                <label class="form-label">
                    Archivos <span class="optional">(imágenes y/o videos — puedes subir varios)</span>
                </label>

                <div class="upload-area" id="uploadArea">
                    <i class="bi bi-cloud-arrow-up upload-icon"></i>
                    <p class="upload-text">Arrastra tus archivos aquí o</p>
                    <label for="archivos" class="btn btn-light upload-btn">
                        Seleccionar archivos
                    </label>
                    <input
                        type="file"
                        id="archivos"
                        name="archivos[]"
                        multiple
                        accept="image/*,video/*"
                        class="d-none"
                    >
                    <p class="upload-hint">Imágenes: JPG, PNG, GIF &nbsp;|&nbsp; Videos: MP4, MOV, AVI</p>
                </div>

                <!-- PREVIEW -->
                <div id="previewContainer" class="preview-container mt-3"></div>
            </div>

            <button type="submit" class="btn btn-light pub-btn">
                <i class="bi bi-send me-2"></i>
                Enviar publicación
            </button>

            <p class="pub-nota mt-3">
                <i class="bi bi-info-circle me-1"></i>
                Tu publicación será visible una vez que el administrador la apruebe.
            </p>

        </form>
    </div>
</div>

<footer class="footer mt-5 py-4">
    <div class="container text-center">
        <p class="mb-0">© 2026 Match.World — Proyecto Mundiales</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="crear_publicacion.js?v=<?php echo time(); ?>"></script>
</body>
</html>