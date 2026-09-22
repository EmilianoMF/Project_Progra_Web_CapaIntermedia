<?php
session_start();

require_once(__DIR__ . "/BD_Conect.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.html");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$id_usuario = $_SESSION['id_usuario'];

// --- 1. OBTENER DATOS DEL USUARIO ---
$stmt = $conn->prepare("
    SELECT nombre_completo, correo, nacionalidad, fecha_registro, foto
    FROM usuarios
    WHERE id_usuario = :id
");
$stmt->bindParam(":id", $id_usuario);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// --- 2. OBTENER ESTADÍSTICAS DEL USUARIO ---

// A. Estadísticas de Publicaciones (Totales, Aprobadas y Pendientes)
$stmtPubs = $conn->prepare("
    SELECT 
        COUNT(*) AS total_pubs,
        SUM(CASE WHEN aprobado = 1 THEN 1 ELSE 0 END) AS pubs_aprobadas,
        SUM(CASE WHEN aprobado = 0 AND rechazado = 0 THEN 1 ELSE 0 END) AS pubs_pendientes
    FROM publicaciones
    WHERE id_usuario = :id
");
$stmtPubs->execute([':id' => $id_usuario]);
$statsPubs = $stmtPubs->fetch(PDO::FETCH_ASSOC);

// --- 3. OBTENER PUBLICACIONES PARA EL PERFIL ---
$stmtMisPubs = $conn->prepare("
    SELECT id_publicacion, descripcion, aprobado, fecha_elaboracion 
    FROM publicaciones 
    WHERE id_usuario = :id 
    ORDER BY fecha_elaboracion DESC
");
$stmtMisPubs->execute([':id' => $id_usuario]);
$misPublicaciones = $stmtMisPubs->fetchAll(PDO::FETCH_ASSOC);

// B. Estadísticas de Vistas y Likes Totales
$stmtViewsLikes = $conn->prepare("
    SELECT 
        COALESCE(SUM(e.vistas), 0) AS total_vistas,
        COALESCE(SUM(e.likes), 0) AS total_likes
    FROM estadisticas e
    JOIN publicaciones p ON e.id_publicacion = p.id_publicacion
    WHERE p.id_usuario = :id
");
$stmtViewsLikes->execute([':id' => $id_usuario]);
$statsViewsLikes = $stmtViewsLikes->fetch(PDO::FETCH_ASSOC);

// C. Estadísticas de Comentarios Recibidos
$stmtComentarios = $conn->prepare("
    SELECT COUNT(*) AS total_comentarios
    FROM interacciones i
    JOIN publicaciones p ON i.id_publicacion = p.id_publicacion
    WHERE p.id_usuario = :id AND i.tipo = 'Comentario'
");
$stmtComentarios->execute([':id' => $id_usuario]);
$statsComentarios = $stmtComentarios->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Perfil - Match.World</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="perfil.css?v=<?php echo time(); ?>">

</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white" href="index.php">
            <i class="bi bi-globe-americas me-2"></i> Match.World
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-center" id="navbarContent">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#mundiales">MUNDIALES</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container profile-container">
    <div class="profile-card">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($usuario['foto']); ?>" class="profile-photo" alt="Foto">
            </div>

            <div class="col-md-9">
                <h1 class="profile-name"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></h1>
                <div class="profile-info">
                    <p><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($usuario['correo']); ?></p>
                    <p><i class="bi bi-calendar-check-fill"></i> Miembro desde: <?php echo date("d/m/Y", strtotime($usuario['fecha_registro'])); ?></p>
                    <p><i class="bi bi-globe-americas"></i> Nacionalidad: <?php echo htmlspecialchars($usuario['nacionalidad']); ?></p>
                </div>
            </div>
        </div>

        <div class="row profile-actions g-4">
            
            <div class="col-md-4" id="btnMisPublicaciones">
                <div class="action-card" id="btnMisPublicaciones">
                    <i class="bi bi-images"></i>
                    <h4>Mis Publicaciones</h4>
                    <p>Consulta y administra todas tus publicaciones.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="action-card" id="btnEditarPerfil">
                    <i class="bi bi-pencil-square"></i>
                    <h4>Editar Perfil</h4>
                    <p>Modifica tu información y personaliza tu cuenta.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="action-card" id="btnEstadisticas">
                    <i class="bi bi-bar-chart-fill"></i>
                    <h4>Estadísticas Detalladas</h4>
                    <p>Visualiza tu actividad, likes y rendimiento.</p>
                </div>
            </div>

        </div>

        <div id="contenidoPerfil" class="mt-5"></div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Objeto de Usuario (Usado por tu archivo editar_perfil.js)
const usuario = {
    nombre: "<?php echo addslashes($usuario['nombre_completo']); ?>",
    correo: "<?php echo addslashes($usuario['correo']); ?>",
    nacionalidad: "<?php echo addslashes($usuario['nacionalidad']); ?>"
};

// Objeto de Estadísticas que PHP calculó
const stats = {
    total_pubs: <?php echo $statsPubs['total_pubs'] ?? 0; ?>,
    pubs_aprobadas: <?php echo $statsPubs['pubs_aprobadas'] ?? 0; ?>,
    pubs_pendientes: <?php echo $statsPubs['pubs_pendientes'] ?? 0; ?>,
    total_vistas: <?php echo $statsViewsLikes['total_vistas'] ?? 0; ?>,
    total_likes: <?php echo $statsViewsLikes['total_likes'] ?? 0; ?>,
    total_comentarios: <?php echo $statsComentarios['total_comentarios'] ?? 0; ?>
};

// Lógica al hacer click en el botón "Estadísticas Detalladas"
document.getElementById('btnEstadisticas').addEventListener('click', function() {
    const contenedor = document.getElementById('contenidoPerfil');
    
    // Inyectamos el HTML de las estadísticas
    contenedor.innerHTML = `
        <div class="p-4 bg-dark bg-opacity-25 rounded-4 border border-secondary border-opacity-25">
            <h3 class="fw-bold mb-4 text-white"><i class="bi bi-bar-chart-fill me-2 text-warning"></i>Tu Rendimiento Global</h3>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stat-box">
                        <i class="bi bi-eye-fill text-info"></i>
                        <h5>Vistas Totales</h5>
                        <h2>${stats.total_vistas}</h2>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="stat-box">
                        <i class="bi bi-heart-fill text-danger"></i>
                        <h5>Likes Recibidos</h5>
                        <h2>${stats.total_likes}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-box">
                        <i class="bi bi-chat-dots-fill text-success"></i>
                        <h5>Comentarios</h5>
                        <h2>${stats.total_comentarios}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-box">
                        <i class="bi bi-journal-text text-primary"></i>
                        <h5>Publicaciones</h5>
                        <h2>${stats.total_pubs}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-box">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <h5>Aprobadas</h5>
                        <h2>${stats.pubs_aprobadas}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-box">
                        <i class="bi bi-hourglass-split text-warning"></i>
                        <h5>Pendientes</h5>
                        <h2>${stats.pubs_pendientes}</h2>
                    </div>
                </div>
            </div>
        </div>
    `;
});

// Objeto con tus publicaciones
const misPublicaciones = <?php echo json_encode($misPublicaciones); ?>;

// Lógica al hacer click en "Mis Publicaciones"
document.getElementById('btnMisPublicaciones').addEventListener('click', function() {
    const contenedor = document.getElementById('contenidoPerfil');
    
    if (misPublicaciones.length === 0) {
        contenedor.innerHTML = `<div class="alert alert-info">Aún no has realizado ninguna publicación.</div>`;
        return;
    }

    let html = `
        <div class="p-4 bg-dark bg-opacity-25 rounded-4 border border-secondary border-opacity-25">
            <h3 class="fw-bold mb-4 text-white"><i class="bi bi-images me-2 text-primary"></i>Tus Publicaciones</h3>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${misPublicaciones.map(pub => `
                            <tr>
                                <td>${pub.descripcion.length > 50 ? pub.descripcion.substring(0, 50) + '...' : pub.descripcion}</td>
                                <td>
                                    <span class="badge ${pub.aprobado == 1 ? 'bg-success' : 'bg-warning text-dark'}">
                                        ${pub.aprobado == 1 ? 'Aprobado' : 'Pendiente'}
                                    </span>
                                </td>
                                <td>${new Date(pub.fecha_elaboracion).toLocaleDateString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    contenedor.innerHTML = html;
});
</script>

<script src="editar_perfil.js"></script>

</body>
</html>