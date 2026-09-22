<?php
session_start();

require_once(__DIR__ . "/BD_Conect.php");
$db   = new Database();
$conn = $db->getConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_mundial = (int) $_GET['id'];

// --- LLAMADA A STORED PROCEDURE: sp_obtener_mundial_detalle ---
$stmt = $conn->prepare("CALL sp_obtener_mundial_detalle(:id)");
$stmt->bindParam(":id", $id_mundial, PDO::PARAM_INT);
$stmt->execute();
$mundial = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor(); // Libera la conexión para la siguiente consulta obligatoriamente

if (!$mundial) {
    header("Location: index.php");
    exit();
}

// Cargar categorías para el filtro
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nombre_categoria")->fetchAll(PDO::FETCH_ASSOC);

// Parámetros capturados por GET para los filtros
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_seleccion = isset($_GET['seleccion']) ? trim($_GET['seleccion']) : '';
$orden            = isset($_GET['orden']) ? $_GET['orden'] : 'recientes';

// --- LLAMADA A STORED PROCEDURE: sp_filtrar_publicaciones_mundial ---
$pubs = $conn->prepare("CALL sp_filtrar_publicaciones_mundial(:id, :cat, :sel, :orden)");
$pubs->bindValue(':id', $id_mundial, PDO::PARAM_INT);
$pubs->bindValue(':cat', $filtro_categoria !== '' ? (int)$filtro_categoria : null, PDO::PARAM_INT);
$pubs->bindValue(':sel', $filtro_seleccion !== '' ? $filtro_seleccion : null, PDO::PARAM_STR);
$pubs->bindValue(':orden', $orden, PDO::PARAM_STR);
$pubs->execute();
$publicaciones = $pubs->fetchAll(PDO::FETCH_ASSOC);
$pubs->closeCursor();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($mundial['nombre']); ?> - Match.World</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="custom_styles.css?v=<?php echo time(); ?>">
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white navbar-custom shadow-sm fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <i class="bi bi-bell me-3 text-dark"></i> Match.World
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#mundiales">MUNDIALES</a></li>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === "Admin"): ?>
                    <li class="nav-item"><a class="nav-link" href="admin.php">ADMINISTRADOR</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <li class="nav-item"><a class="nav-link" href="perfil.php">MI PERFIL</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">LOG OUT</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.html">LOG IN</a></li>
                    <li class="nav-item"><a class="nav-link" href="registro.html">SIGN UP</a></li>
                <?php endif; ?>
            </ul>

            <form action="buscar.php" method="GET" class="position-relative" style="width: 300px;">
                <input type="search" name="usuario" class="form-control rounded-pill ps-5 pe-5 border-secondary text-dark bg-white" placeholder="Search user...">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                <button type="submit" class="btn btn-dark position-absolute top-50 end-0 translate-middle-y rounded-pill me-1 px-3">Search</button>
            </form>
        </div>
    </div>
</nav>

<div class="mundial-hero">
    <div class="hero-bg" style="background-image: url('ver_archivo.php?tabla=mundiales&campo=logotipo&id=<?php echo $mundial['id_mundial']; ?>')"></div>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <div class="mundial-logo-wrap">
            <img src="ver_archivo.php?tabla=mundiales&campo=imagen&id=<?php echo $mundial['id_mundial']; ?>" class="mundial-logo-img">
        </div>
        <div class="mundial-info">
            <span class="mundial-anio"><?php echo $mundial['anio']; ?></span>
            <h1 class="mundial-nombre"><?php echo htmlspecialchars($mundial['nombre']); ?></h1>
            <p class="mundial-sede"><i class="bi bi-geo-alt-fill me-2"></i><?php echo htmlspecialchars($mundial['sede']); ?></p>
            <p class="mundial-resena"><?php echo htmlspecialchars($mundial['reseña']); ?></p>
        </div>
    </div>
</div>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-grid me-2"></i>Publicaciones</h2>
        <span class="badge bg-secondary px-3 py-2 rounded-pill"><?php echo count($publicaciones); ?> publicación(es)</span>
    </div>

    <div class="filtros-bar">
        <form method="GET" action="detalle_mundial.php" class="row g-3 align-items-end">
            <input type="hidden" name="id" value="<?php echo $id_mundial; ?>">
            <div class="col-md-3">
                <label class="form-label text-white-50 small">Categoría</label>
                <select name="categoria" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php echo ($filtro_categoria == $cat['id_categoria']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-white-50 small">Selección / País</label>
                <input type="text" name="seleccion" class="form-control bg-dark text-white border-secondary" placeholder="Ej: México" value="<?php echo htmlspecialchars($filtro_seleccion); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label text-white-50 small">Ordenar por</label>
                <select name="orden" class="form-select bg-dark text-white border-secondary">
                    <option value="recientes" <?php echo ($orden == 'recientes') ? 'selected' : ''; ?>>Más recientes</option>
                    <option value="antiguas" <?php echo ($orden == 'antiguas') ? 'selected' : ''; ?>>Más antiguas</option>
                    <option value="populares" <?php echo ($orden == 'populares') ? 'selected' : ''; ?>>Más populares</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-2"></i>Aplicar Filtros</button>
            </div>
        </form>
    </div>

    <?php if (empty($publicaciones)): ?>
        <div class="sin-publicaciones">
            <i class="bi bi-camera fs-1 d-block mb-2"></i>
            <p>No se encontraron publicaciones con estos filtros.</p>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-5">
            <?php foreach ($publicaciones as $pub): ?>
            <div class="col-lg-4 col-md-6">
                <a href="ver_publicacion.php?id_pub=<?php echo $pub['id_publicacion']; ?>" class="pub-link">
                    <div class="pub-card">
                        <?php if ($pub['id_primer_archivo']): ?>
                            <div class="pub-media">
                                <?php if ($pub['tipo_primer_archivo'] === 'imagen'): ?>
                                    <img src="ver_archivo.php?id=<?php echo $pub['id_primer_archivo']; ?>" alt="img" class="pub-img">
                                <?php else: ?>
                                    <video src="ver_archivo.php?id=<?php echo $pub['id_primer_archivo']; ?>" class="pub-img" muted></video>
                                <?php endif; ?>
                                <?php if ($pub['total_archivos'] > 1): ?>
                                    <span class="pub-multi"><i class="bi bi-images me-1"></i><?php echo $pub['total_archivos']; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="pub-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="tag-categoria"><?php echo htmlspecialchars($pub['nombre_categoria']); ?></span>
                                    <?php if ($pub['seleccion']): ?>
                                        <span class="tag-seleccion"><i class="bi bi-flag me-1"></i><?php echo htmlspecialchars($pub['seleccion']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-white-50 small">
                                    <span class="me-2"><i class="bi bi-eye"></i> <?php echo $pub['vistas']; ?></span>
                                    <span><i class="bi bi-heart-fill text-danger"></i> <?php echo $pub['likes']; ?></span>
                                </div>
                            </div>
                            <p class="pub-desc"><?php echo htmlspecialchars($pub['descripcion']); ?></p>
                            <div class="pub-footer">
                                <span><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($pub['nombre_completo']); ?></span>
                                <span><?php echo date('d/m/Y', strtotime($pub['fecha_aprobacion'])); ?></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['id_usuario'])): ?>
<a href="crear_publicacion.php" class="fab-publicar"><span>+</span></a>
<?php endif; ?>

<footer class="footer mt-5 py-4 border-top border-secondary border-opacity-10 text-center text-white-50">
    <p class="mb-0">© 2026 Match.World — Proyecto Mundiales</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>