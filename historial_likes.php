<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'config/config/conexion.php';

// Verificar sesión y rol de estudiante
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header("Location: auth.php");
    exit;
}

$estudiante_id = $_SESSION['user_id'];

// Obtener datos del estudiante para el navbar
$stmt_estudiante = $mysqli->prepare("SELECT nombre, foto FROM estudiantes WHERE id = ?");
$stmt_estudiante->bind_param("i", $estudiante_id);
$stmt_estudiante->execute();
$res_estudiante = $stmt_estudiante->get_result();
$estudiante_data = $res_estudiante->fetch_assoc();

$estudiante_foto_path = "assets/img/default-user.png"; 
if (!empty($estudiante_data['foto']) && file_exists("assets/uploads/" . $estudiante_data['foto'])) {
    $estudiante_foto_path = "assets/uploads/" . $estudiante_data['foto'];
}

/* =========================
   ELIMINAR LIKE (MISMO ARCHIVO)
   ============================ */
if (isset($_GET['eliminar']) && isset($_GET['match_id'])) {
    $match_id = (int)$_GET['match_id'];

    if ($match_id > 0) {
        // Solo puede eliminar likes que sean suyos
        $stmt_delete = $mysqli->prepare("UPDATE matches SET estado = 'rechazado' WHERE id = ? AND estudiante_id = ?");
        $stmt_delete->bind_param("ii", $match_id, $estudiante_id);
        $stmt_delete->execute();
    }

    // Volver al historial para evitar re-envíos
    header("Location: historial_likes.php");
    exit;
}

// Obtener empresas a las que el estudiante le dio 'like'
// 👉 ahora traemos también m.id AS match_id para poder eliminar
$sql = "SELECT 
            m.id AS match_id,
            m.created_at, 
            m.estado, 
            emp.id AS empresa_id, 
            emp.razon_social, 
            emp.sector, 
            emp.foto 
        FROM matches m 
        JOIN empresas emp ON m.empresa_id = emp.id 
        WHERE m.estudiante_id = ? AND m.estado != 'rechazado' 
        ORDER BY m.created_at DESC";
$stmt_matches = $mysqli->prepare($sql);
$stmt_matches->bind_param("i", $estudiante_id);
$stmt_matches->execute();
$matches_result = $stmt_matches->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Historial de Likes | PractiMach</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* RESET BÁSICO */
*, *::before, *::after {
  box-sizing: border-box;
}

/* VARIABLES */
:root {
  --pm-primary: #4f46e5;
  --pm-primary-soft: #eef2ff;
  --pm-primary-dark: #3730a3;
  --pm-bg: #020617;
  --pm-card-bg: #ffffff;
  --pm-border: #e5e7eb;
  --pm-muted: #6b7280;
  --pm-text: #0f172a;
  --pm-radius-lg: 20px;
  --pm-radius-md: 14px;
  --pm-shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.16);
}

/* GENERAL */
body {
  margin: 0;
  background: radial-gradient(circle at top left, #1d4ed8 0, #020617 52%, #020617 100%);
  font-family: "Poppins", sans-serif;
  overflow-x: hidden;
  color: var(--pm-text);
  min-height: 100vh;
}

/* NAVBAR (mismo estilo que las otras vistas) */
.navbar {
  width: 100%;
  position: sticky;
  top: 0;
  z-index: 80;
  backdrop-filter: blur(14px);
  background: linear-gradient(to right, rgba(15,23,42,0.94), rgba(15,23,42,0.88));
  border-bottom: 1px solid rgba(148, 163, 184, 0.35);
}
.nav-inner {
  max-width: 1120px;
  margin: 0 auto;
  padding: .7rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.nav-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.nav-logo {
  font-size: 1.25rem;
  font-weight: 600;
  color: #e5e7eb;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.nav-logo-badge {
  width: 32px;
  height: 32px;
  border-radius: 12px;
  background: radial-gradient(circle at 20% 0, #a5b4fc, #4f46e5);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #f9fafb;
  font-weight: 700;
  font-size: .9rem;
  box-shadow: 0 8px 22px rgba(79, 70, 229, 0.7);
}
.nav-logo small {
  display: block;
  font-size: .7rem;
  color: #9ca3af;
  font-weight: 400;
}
.nav-links {
  display: flex;
  gap: 1.1rem;
  font-size: .9rem;
}
.nav-links a {
  text-decoration: none;
  color: #9ca3af;
  font-weight: 500;
  padding: 0.35rem 0.8rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  transition: background .2s ease, color .2s ease, transform .15s ease;
}
.nav-links a span.dot {
  width: 6px;
  height: 6px;
  border-radius: 999px;
  background: rgba(148,163,184,0.7);
}
.nav-links a:hover {
  background: rgba(15,23,42,0.75);
  color: #e5e7eb;
  transform: translateY(-1px);
}
.nav-links a.active {
  background: rgba(129,140,248,0.25);
  color: #e5e7eb;
}
.nav-links a.active span.dot {
  background: #a5b4fc;
}
.nav-profile {
  display: flex;
  align-items: center;
  gap: .65rem;
  color: #e5e7eb;
}
.nav-profile img {
  width: 36px;
  height: 36px;
  border-radius: 999px;
  object-fit: cover;
  border: 2px solid rgba(129,140,248,0.8);
  box-shadow: 0 0 0 2px rgba(15,23,42,0.9);
}
.nav-profile-info {
  display: flex;
  flex-direction: column;
}
.nav-profile-name {
  font-size: .9rem;
  font-weight: 500;
}
.nav-profile-role {
  font-size: .7rem;
  color: #9ca3af;
}

/* CONTENEDOR PRINCIPAL */
.likes-wrapper {
  max-width: 1120px;
  margin: 1.8rem auto 3rem;
  padding: 0 1.5rem;
}

/* ENCABEZADO PÁGINA */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1rem;
  margin-bottom: 1.5rem;
  color: #f9fafb;
}
.page-title {
  font-size: 1.7rem;
  font-weight: 600;
  letter-spacing: -.01em;
}
.page-subtitle {
  font-size: .9rem;
  color: #cbd5f5;
}
.page-pill {
  font-size: .8rem;
  padding: .25rem .7rem;
  border-radius: 999px;
  border: 1px solid rgba(191,219,254,0.8);
  background: rgba(15,23,42,0.85);
  color: #e5e7eb;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
}

/* TARJETA CONTENEDORA */
.likes-card {
  background: var(--pm-card-bg);
  border-radius: var(--pm-radius-lg);
  box-shadow: var(--pm-shadow-soft);
  padding: 1.5rem 1.4rem 1.6rem;
  border: 1px solid rgba(226,232,240,0.95);
  position: relative;
  overflow: hidden;
}
.likes-card::before {
  content: "";
  position: absolute;
  inset: -40%;
  background: radial-gradient(circle at 0 0, rgba(251,191,36,0.18), transparent 60%),
              radial-gradient(circle at 100% 100%, rgba(129,140,248,0.18), transparent 55%);
  opacity: 0.9;
  pointer-events: none;
}

/* LISTA LIKES */
.likes-list {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* CARD DE CADA LIKE */
.like-card {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 1.1rem;
  background: rgba(248,250,252,0.98);
  padding: 1.05rem 1.1rem;
  border-radius: 18px;
  border: 1px solid rgba(229,231,235,0.98);
  box-shadow: 0 12px 28px rgba(15,23,42,0.06);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.like-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 36px rgba(15,23,42,0.12);
  border-color: rgba(250,204,21,0.95);
}

/* FOTO EMPRESA */
.like-img {
  width: 72px;
  height: 72px;
  border-radius: 18px;
  overflow: hidden;
  background: linear-gradient(135deg, #e5e7eb, #f9fafb);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
}
.like-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* INFO EMPRESA */
.like-info {
  display: flex;
  flex-direction: column;
  gap: .25rem;
}
.like-info h3 {
  margin: 0;
  font-size: 1.02rem;
  color: #0f172a;
}
.like-info p {
  margin: .1rem 0;
  font-size: .86rem;
  color: #4b5563;
}
.like-meta {
  margin-top: .25rem;
  display: flex;
  flex-wrap: wrap;
  gap: .4rem;
  font-size: .78rem;
}
.badge-fecha {
  padding: .12rem .55rem;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

/* ESTADOS */
.estado {
  font-size: .78rem;
  font-weight: 600;
  padding: .14rem .55rem;
  border-radius: 999px;
}
.estado.espera {
  color: #92400e;
  background: #fffbeb;
  border: 1px solid #fed7aa;
}
.estado.match {
  color: #166534;
  background: #ecfdf5;
  border: 1px solid #bbf7d0;
}
.estado.rechazado {
  color: #b91c1c;
  background: #fef2f2;
  border: 1px solid #fecaca;
}

/* ACCIONES (VER PERFIL + ELIMINAR LIKE) */
.like-actions {
  display: flex;
  flex-direction: column;
  gap: .4rem;
}

/* BOTÓN VER PERFIL */
.btn-ver {
  padding: .55rem .9rem;
  border-radius: 999px;
  border: none;
  background: #111827;
  color: #fff;
  font-size: .85rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  box-shadow: 0 10px 24px rgba(15,23,42,0.45);
  transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
}
.btn-ver span.icon {
  font-size: .95rem;
}
.btn-ver:hover {
  background: #020617;
  transform: translateY(-1px);
  box-shadow: 0 14px 30px rgba(15,23,42,0.6);
}

/* BOTÓN ELIMINAR */
.btn-delete {
  padding: .55rem .9rem;
  border-radius: 999px;
  border: none;
  background: #b91c1c;
  color: #fff;
  font-size: .85rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  box-shadow: 0 10px 24px rgba(185,28,28,0.45);
  transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
}
.btn-delete span.icon {
  font-size: .95rem;
}
.btn-delete:hover {
  background: #7f1d1d;
  transform: translateY(-1px);
  box-shadow: 0 14px 30px rgba(127,29,29,0.6);
}

/* ESTADO VACÍO */
.empty-state {
  position: relative;
  z-index: 1;
  text-align: center;
  padding: 2rem 1.2rem;
  background: rgba(248,250,252,0.98);
  border-radius: 18px;
  border: 1px solid rgba(229,231,235,0.98);
}
.empty-emoji {
  font-size: 2rem;
  margin-bottom: .4rem;
}
.empty-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: .3rem;
}
.empty-text {
  font-size: .9rem;
  color: #6b7280;
}

/* MENÚ INFERIOR MÓVIL */
.bottom-menu {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  background: rgba(15,23,42,0.97);
  border-top: 1px solid rgba(31,41,55,0.9);
  display: flex;
  justify-content: space-around;
  padding: .45rem 0 .5rem;
  z-index: 200;
}
.bottom-menu a {
  text-decoration: none;
  text-align: center;
  color: #e5e7eb;
  font-size: .75rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .1rem;
}
.bottom-menu i {
  font-size: 1.35rem;
}

/* RESPONSIVE */
@media (max-width: 900px) {
  .like-card {
    grid-template-columns: auto minmax(0, 1fr);
    grid-template-rows: auto auto;
    align-items: flex-start;
  }
}
@media (max-width: 830px) {
  .nav-inner {
    padding-inline: 1rem;
  }
  .nav-links {
    display: none;
  }
}
@media (max-width: 640px) {
  .likes-wrapper {
    padding-inline: 1rem;
    margin-top: 1.4rem;
  }
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }
  .like-card {
    grid-template-columns: minmax(0, 1fr);
    text-align: left;
  }
  .like-img {
    margin: 0 auto .7rem auto;
  }
  .like-actions {
    flex-direction: row;
    justify-content: flex-end;
  }
}

/* DESACTIVAR MENÚ INFERIOR EN PC */
@media (min-width: 801px) {
  .bottom-menu {
    display: none !important;
  }
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-inner">
    <div class="nav-left">
      <div class="nav-logo">
        <div class="nav-logo-badge">P</div>
        <div>
          PractiMach
          <small>Panel estudiante</small>
        </div>
      </div>
      <div class="nav-links">
        <a href="estudiante_tinder.php">
          <span class="dot"></span>Buscar prácticas
        </a>
        <a href="matches.php">
          <span class="dot"></span>Matches
        </a>
        <a href="historial_likes.php" class="active">
          <span class="dot"></span>Mis likes
        </a>
        <a href="perfil_estudiante.php">
          <span class="dot"></span>Mi perfil
        </a>
        <a href="logout.php">
          <span class="dot"></span>Salir
        </a>
      </div>
    </div>

    <div class="nav-profile">
      <img src="<?php echo htmlspecialchars($estudiante_foto_path); ?>" alt="<?php echo htmlspecialchars($estudiante_data['nombre']); ?>">
      <div class="nav-profile-info">
        <span class="nav-profile-name">
          <?php echo htmlspecialchars($estudiante_data['nombre']); ?>
        </span>
        <span class="nav-profile-role">Estudiante · PractiMach</span>
      </div>
    </div>
  </div>
</nav>

<!-- CONTENIDO -->
<div class="likes-wrapper">
  <div class="page-header">
    <div>
      <div class="page-title">Historial de likes ⭐</div>
      <div class="page-subtitle">
        Empresas a las que les diste “Me interesa”. Algunas pueden convertirse en match más adelante.
      </div>
    </div>
    <div class="page-pill">
      ⭐ Actividad reciente
    </div>
  </div>

  <div class="likes-card">
    <?php if ($matches_result->num_rows > 0): ?>
      <div class="likes-list">
        <?php while($match = $matches_result->fetch_assoc()): 
          $empresa_foto_path = "assets/img/default-company.png";
          if (!empty($match['foto']) && file_exists("assets/uploads/" . $match['foto'])) {
              $empresa_foto_path = "assets/uploads/" . $match['foto'];
          }

          $estadoClass = 'espera';
          $estadoText = '⏳ En espera de respuesta de la empresa';
          if ($match['estado'] == 'aceptado') {
              $estadoClass = 'match';
              $estadoText = '🔥 Match confirmado';
          } elseif ($match['estado'] == 'rechazado') {
              $estadoClass = 'rechazado';
              $estadoText = '❌ Rechazado por empresa';
          }
        ?>
          <div class="like-card">
            <div class="like-img">
              <img src="<?php echo htmlspecialchars($empresa_foto_path); ?>" alt="<?php echo htmlspecialchars($match['razon_social']); ?>">
            </div>

            <div class="like-info">
              <h3><?php echo htmlspecialchars($match['razon_social']); ?></h3>
              <p>Sector: <?php echo htmlspecialchars($match['sector']); ?></p>
              <div class="like-meta">
                <span class="badge-fecha">
                  📅 Like: <?php echo (new DateTime($match['created_at']))->format('d/m/Y'); ?>
                </span>
                <span class="estado <?php echo $estadoClass; ?>">
                  <?php echo $estadoText; ?>
                </span>
              </div>
            </div>

            <div class="like-actions">
              <!-- VER PERFIL EMPRESA -->
              <button class="btn-ver" onclick="location.href='ver_empresa.php?empresa_id=<?php echo $match['empresa_id']; ?>';">
                <span class="icon">👁️</span>
                <span>Ver perfil</span>
              </button>

              <!-- QUITAR LIKE (MISMO ARCHIVO) -->
              <button 
                class="btn-delete"
                onclick="if(confirm('¿Seguro que quieres quitar este like?')) { window.location='historial_likes.php?eliminar=1&match_id=<?php echo $match['match_id']; ?>'; }">
                <span class="icon">🗑️</span>
                <span>Quitar like</span>
              </button>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-emoji">✨</div>
        <div class="empty-title">Aún no has dado like</div>
        <div class="empty-text">
          Entra a <strong>“Buscar prácticas”</strong>, explora empresas y marca las que te interesen con “Me interesa”.
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- MENÚ INFERIOR MÓVIL -->
<div class="bottom-menu">
  <a href="estudiante_tinder.php"><i>🔥</i>Buscar</a>
  <a href="matches.php"><i>❤️</i>Matches</a>
  <a href="historial_likes.php"><i>⭐</i>Likes</a>
  <a href="perfil_estudiante.php"><i>👤</i>Perfil</a>
</div>

</body>
</html>
