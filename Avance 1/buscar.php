<?php
session_start();

require_once(__DIR__ . "/BD_Conect.php");
$db   = new Database();
$conn = $db->getConnection();

// Cargar catálogos iniciales para los desplegables de búsqueda
$categorias      = $conn->query("SELECT * FROM categorias ORDER BY nombre_categoria")->fetchAll(PDO::FETCH_ASSOC);
$anios_mundiales = $conn->query("SELECT DISTINCT anio FROM mundiales ORDER BY anio DESC")->fetchAll(PDO::FETCH_ASSOC);
$sedes_mundiales = $conn->query("SELECT DISTINCT sede FROM mundiales ORDER BY sede ASC")->fetchAll(PDO::FETCH_ASSOC);

// Captura de variables desde la barra
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_anio      = isset($_GET['anio']) ? $_GET['anio'] : '';
$filtro_sede      = isset($_GET['sede']) ? $_GET['sede'] : '';
$filtro_usuario   = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';

// --- LLAMADA A STORED PROCEDURE: sp_buscar_publicaciones_avanzado ---
$stmt = $conn->prepare("CALL sp_buscar_publicaciones_avanzado(:cat, :anio, :sede, :user)");
$stmt->bindValue(':cat', $filtro_categoria !== '' ? (int)$filtro_categoria : null, PDO::PARAM_INT);
$stmt->bindValue(':anio', $filtro_anio !== '' ? (int)$filtro_anio : null, PDO::PARAM_INT);
$stmt->bindValue(':sede', $filtro_sede !== '' ? $filtro_sede : null, PDO::PARAM_STR);
$stmt->bindValue(':user', $filtro_usuario !== '' ? $filtro_usuario : null, PDO::PARAM_STR);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor(); // Cerramos cursor correctamente
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar Publicaciones - Match.World</title>
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
                <?php if (isset($_SESSION['row']) && $_SESSION['rol'] === "Admin"): ?>
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
                <input type="search" name="usuario" class="form-control rounded-pill ps-5 pe-5 border-secondary text-dark bg-white" placeholder="Buscar usuario..." value="<?php echo htmlspecialchars($filtro_usuario); ?>">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                <button type="submit" class="btn btn-dark position-absolute top-50 end-0 translate-middle-y rounded-pill me-1 px-3">Search</button>
            </form>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="fw-bold"><i class="bi bi-search me-3"></i>Buscador Avanzado</h1>
            <p class="text-white-50">Encuentra publicaciones específicas cruzando criterios globales</p>
        </div>
    </div>

    <div class="search-box-panel">
        <form method="GET" action="buscar.php" class="row g-3">
            <div class="col-md-3">
                <label class="form-label text-white-50 small fw-bold">Categoría</label>
                <select name="categoria" class="form-select bg-dark text-white border-secondary rounded-3">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php echo ($filtro_categoria == $cat['id_categoria']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label text-white-50 small fw-bold">Año del Mundial</label>
                <select name="anio" class="form-select bg-dark text-white border-secondary rounded-3">
                    <option value="">Todos</option>
                    <?php foreach ($anios_mundiales as $a): ?>
                        <option value="<?php echo $a['anio']; ?>" <?php echo ($filtro_anio == $a['anio']) ? 'selected' : ''; ?>><?php echo $a['anio']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label text-white-50 small fw-bold">País Sede</label>
                <select name="sede" class="form-select bg-dark text-white border-secondary rounded-3">
                    <option value="">Todos los países</option>
                    <?php foreach ($sedes_mundiales as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['sede']); ?>" <?php echo ($filtro_sede == $s['sede']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['sede']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label text-white-50 small fw-bold">Usuario Autor</label>
                <div class="input-group">
                    <input type="text" name="usuario" class="form-control bg-dark text-white border-secondary" placeholder="Nombre de usuario..." value="<?php echo htmlspecialchars($filtro_usuario); ?>">
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-funnel-fill"></i></button>
                </div>
            </div>
        </form>
        <?php if($filtro_categoria !== '' || $filtro_anio !== '' || $filtro_sede !== '' || $filtro_usuario !== ''): ?>
            <div class="text-end mt-2"><a href="buscar.php" class="btn btn-outline-light btn-sm rounded-pill">Limpiar Filtros</a></div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fs-5 mb-0"><i class="bi bi-collection me-2"></i>Resultados de búsqueda</h3>
        <span class="badge bg-secondary px-3 py-2 rounded-pill"><?php echo count($resultados); ?> resultado(s)</span>
    </div>

    <?php if (empty($resultados)): ?>
        <div class="sin-publicaciones py-5 text-center">
            <i class="bi bi-search text-white-50 mb-3 fs-1 d-block"></i>
            <h4 class="text-white-50">No hay coincidencias</h4>
            <p class="text-muted small">Intenta ajustar los desplegables de búsqueda.</p>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-5">
            <?php foreach ($resultados as $pub): ?>
            <div class="col-lg-4 col-md-6">
                <a href="ver_publicacion.php?id_pub=<?php echo $pub['id_publicacion']; ?>" class="pub-link">
                    <div class="pub-card">
                        <?php if ($pub['id_primer_archivo']): ?>
                            <div class="pub-media">
                                <?php if ($pub['tipo_primer_archivo'] === 'imagen'): ?>
                                    <img src="ver_archivo.php?id=<?php echo $pub['id_primer_archivo']; ?>" alt="pub" class="pub-img">
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
                                <div class="text-white-50 small ms-2">
                                    <span class="me-2"><i class="bi bi-eye"></i> <?php echo $pub['vistas']; ?></span>
                                    <span><i class="bi bi-heart-fill text-danger"></i> <?php echo $pub['likes']; ?></span>
                                </div>
                            </div>
                            
                            <div>
                                <span class="badge-mundial">
                                    <i class="bi bi-trophy-fill me-1"></i> 
                                    <?php echo htmlspecialchars($pub['nombre_mundial']); ?> (<?php echo $pub['anio']; ?>)
                                </span>
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

<footer class="footer mt-auto py-4 border-top border-secondary border-opacity-10 text-center text-white-50">
    <p class="mb-0">© 2026 Match.World — Proyecto Mundiales</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>