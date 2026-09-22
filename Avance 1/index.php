<?php
session_start();
require_once(__DIR__ . "/BD_Conect.php");
$db   = new Database();
$conn = $db->getConnection();
$mundiales = $conn->query("SELECT id_mundial, nombre, sede, anio FROM mundiales ORDER BY anio DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Match.World</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg bg-white navbar-custom shadow-sm fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
            <i class="bi bi-bell me-4"></i>
            Match.World
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="#">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="#mundiales">MUNDIALES</a></li>
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

<!-- HERO CON CAROUSEL -->
<section class="hero" id="home">

    <!-- FONDO dinámico — cambia con el carousel -->
    <div class="hero-bg-overlay"></div>
    <div class="hero-bg" id="heroBg"></div>

    <!-- CAROUSEL de mundiales (invisible, solo controla el slide) -->
    <div id="heroCarousel" class="carousel slide d-none" data-bs-ride="carousel" data-bs-interval="4000">
        <div class="carousel-inner">
            <?php foreach ($mundiales as $i => $m): ?>
            <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>"
                 data-id="<?php echo $m['id_mundial']; ?>"
                 data-nombre="<?php echo htmlspecialchars($m['nombre']); ?>"
                 data-sede="<?php echo htmlspecialchars($m['sede']); ?>"
                 data-anio="<?php echo $m['anio']; ?>">
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="container-fluid hero-inner">
        <div class="row align-items-center min-vh-100">

            <!-- TEXTO IZQUIERDA -->
            <div class="col-md-6 text-white hero-text-col">
                <p class="hero-anio" id="heroAnio"></p>
                <h1 class="hero-titulo" id="heroTitulo">Explora</h1>
                <p class="hero-sede" id="heroSede"></p>
                <a href="#" id="heroBtnExplore" class="btn btn-primary mt-3">
                    Explore <span class="ms-3">→</span>
                </a>
            </div>

            <!-- CAROUSEL DERECHA — tarjeta con imagen -->
            <div class="col-md-6 d-flex justify-content-end align-items-center">
                <div class="hero-card-wrap">

                    <div id="cardCarousel" class="carousel slide" data-bs-ride="false">
                        <div class="carousel-inner">
                            <?php foreach ($mundiales as $i => $m): ?>
                            <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                                <div class="card custom-card">
                                    <img src="ver_archivo.php?tabla=mundiales&campo=imagen&id=<?php echo $m['id_mundial']; ?>"
                                         class="card-img-top"
                                         alt="<?php echo htmlspecialchars($m['nombre']); ?>">
                                    <div class="card-overlay-text">
                                        <span><?php echo htmlspecialchars($m['nombre']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#cardCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#cardCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- EXPLORAR MUNDIALES -->
<section class="container mundiales-section" id="mundiales">
    <div class="section-header">
        <h2>Explorar los Mundiales</h2>
        <p>Descubre historia, sedes y momentos icónicos.</p>
    </div>
    <div class="row g-4">
        <?php
        $stmt2 = $conn->query("SELECT * FROM mundiales ORDER BY anio DESC");
        $todosLosMundiales = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($todosLosMundiales as $mundial):
        ?>
        <div class="col-lg-4 col-md-6 d-flex">
            <div class="mundial-card w-100">
                <div class="mundial-banner">
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($mundial['imagen']); ?>" class="mundial-banner-img">
                </div>
                <h3 class="mundial-title"><?php echo $mundial['nombre']; ?></h3>
                <p class="mundial-country"><i class="bi bi-globe-americas"></i> <?php echo $mundial['sede']; ?></p>
                <p class="mundial-year">Mundial <?php echo $mundial['anio']; ?></p>
                <p class="mundial-resena"><?php echo $mundial['reseña']; ?></p>
                <a href="detalle_mundial.php?id=<?php echo $mundial['id_mundial']; ?>" class="btn btn-light mundial-btn">Ver detalles</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<footer class="footer mt-5 py-4">
    <div class="container text-center">
        <p class="mb-0">© 2026 Match.World — Proyecto Mundiales</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="index.js?v=<?php echo time(); ?>"></script>

<?php if (isset($_SESSION['id_usuario'])): ?>
<a href="crear_publicacion.php" class="fab-publicar"><span>+</span></a>
<?php endif; ?>

</body>
</html>