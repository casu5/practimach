<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'config/config/conexion.php';

// Verificar sesión y rol de empresa
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'empresa') {
    header("Location: auth.php");
    exit;
}

$empresa_id = $_SESSION['user_id'];

$msg   = "";
$error = "";

// --- ELIMINAR / RECHAZAR LIKE (EMPRESA NO LO QUIERE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_like_id'])) {
    $like_id = (int) $_POST['eliminar_like_id'];

    // Marcamos el match como "rechazado" para que no vuelva a salir
    $stmt_del = $mysqli->prepare("UPDATE matches SET estado = 'rechazado' WHERE id = ? AND empresa_id = ?");
    $stmt_del->bind_param("ii", $like_id, $empresa_id);

    if ($stmt_del->execute()) {
        $msg = "Like eliminado correctamente.";
    } else {
        $error = "Error al eliminar el like. Inténtalo nuevamente.";
    }
}

// Obtener datos de la empresa para el navbar
$stmt_empresa = $mysqli->prepare("SELECT razon_social, foto FROM empresas WHERE id = ?");
$stmt_empresa->bind_param("i", $empresa_id);
$stmt_empresa->execute();
$res_empresa = $stmt_empresa->get_result();
$empresa_data = $res_empresa->fetch_assoc();

$empresa_foto_path = "assets/img/default-company.png"; 
if (!empty($empresa_data['foto']) && file_exists("assets/uploads/" . $empresa_data['foto'])) {
    $empresa_foto_path = "assets/uploads/" . $empresa_data['foto'];
}

// Obtener estudiantes que le dieron 'like' a esta empresa (que no estén rechazados)
$sql = "SELECT 
            m.id AS match_id,
            m.created_at, 
            m.estado, 
            e.id AS estudiante_id, 
            e.nombre AS estudiante_nombre, 
            e.carrera, 
            e.foto 
        FROM matches m 
        JOIN estudiantes e ON m.estudiante_id = e.id 
        WHERE m.empresa_id = ? AND m.estado != 'rechazado' 
        ORDER BY m.created_at DESC";
$stmt_matches = $mysqli->prepare($sql);
$stmt_matches->bind_param("i", $empresa_id);
$stmt_matches->execute();
$matches_result = $stmt_matches->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Likes recibidos | PractiMach</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* RESET */
*,
*::before,
*::after {
  box-sizing: border-box;
}

/* VARIABLES */
:root {
  --pm-primary: #4f46e5;
  --pm-primary-soft: #eef2ff;
  --pm-bg: #020617;
  --pm-card-bg: #ffffff;
  --pm-border: #e5e7eb;
  --pm-muted: #6b7280;
  --pm-text: #0f172a;
  --pm-radius-lg: 20px;
  --pm-shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.18);
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

/* NAVBAR (MISMO ESTILO QUE PERFIL_EMPRESA) */
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

/* CONTENIDO PRINCIPAL */
.wrapper {
  max-width: 1120px;
  margin: 2.1rem auto 3.5rem;
  padding: 0 1.5rem 3.5rem 1.5rem;
}
.page-header {
  margin-bottom: 1.4rem;
  color: #f9fafb;
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-end;
}
.title {
  font-size: 1.8rem;
  margin: 0 0 .3rem 0;
  font-weight: 600;
}
.subtitle {
  font-size: .95rem;
  color: #cbd5f5;
}
.header-pill {
  font-size: .8rem;
  padding: .25rem .7rem;
  border-radius: 999px;
  border: 1px solid rgba(191,219,254,0.8);
  background: rgba(15,23,42,0.85);
  color: #e5e7eb;
}

/* ALERTAS */
.alert {
  padding: .75rem 1rem;
  border-radius: 10px;
  margin-bottom: 1rem;
  text-align: center;
  font-size: .88rem;
}
.alert-success {
  background: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}
.alert-error {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

/* TARJETAS DE LIKES */
.likes-panel {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* CARD */
.like-card {
  display: flex;
  align-items: stretch;
  background: var(--pm-card-bg);
  padding: 1.2rem 1.3rem;
  border-radius: var(--pm-radius-lg);
  border: 1px solid rgba(226,232,240,0.95);
  box-shadow: var(--pm-shadow-soft);
  transition: .25s;
}
.like-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 22px 55px rgba(15,23,42,0.25);
}

/* COLUMNA IZQUIERDA (N°) */
.like-left {
  min-width: 80px;
  text-align: center;
  font-size: .85rem;
  color: var(--pm-muted);
  display: flex;
  flex-direction: column;
  justify-content: center;
  border-right: 1px solid #e5e7eb;
  margin-right: 1.3rem;
}
.like-left-label {
  font-weight: 600;
  text-transform: uppercase;
  font-size: .75rem;
  letter-spacing: .05em;
  color: #6b7280;
}
.like-left-index {
  margin-top: .3rem;
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
}

/* COLUMNA CENTRAL */
.like-center {
  flex: 1;
  display: flex;
  gap: 1.1rem;
  align-items: center;
}

/* FOTO ESTUDIANTE */
.like-img {
  width: 70px;
  height: 70px;
  border-radius: 999px;
  overflow: hidden;
  border: 3px solid rgba(59,130,246,0.25);
  flex-shrink: 0;
}
.like-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* INFO TEXTO */
.like-info h3 {
  margin: 0;
  font-size: 1.1rem;
}
.like-info p {
  margin: .2rem 0;
  font-size: .9rem;
  color: #4b5563;
}

/* ESTADOS */
.estado {
  font-size: .85rem;
  font-weight: 600;
}
.estado.espera {
  color: #f59e0b;
}
.estado.match {
  color: #16a34a;
}
.estado.rechazado {
  color: #dc2626;
}

/* COLUMNA DERECHA BOTÓN */
.like-actions {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: .5rem;
  margin-left: 1.3rem;
}

/* BOTONES */
.btn-primary {
  padding: .6rem 1.1rem;
  border-radius: 999px;
  border: none;
  background: #111827;
  color: #fff;
  font-size: .9rem;
  font-weight: 500;
  cursor: pointer;
  transition: .25s;
  display: inline-flex;
  align-items: center;
  gap: .3rem;
}
.btn-primary:hover {
  background: #020617;
  box-shadow: 0 14px 30px rgba(15,23,42,0.5);
}

.btn-danger {
  padding: .5rem 1rem;
  border-radius: 999px;
  border: 1px solid #fecaca;
  background: #fef2f2;
  color: #b91c1c;
  font-size: .85rem;
  font-weight: 500;
  cursor: pointer;
  transition: .25s;
  display: inline-flex;
  align-items: center;
  gap: .3rem;
}
.btn-danger:hover {
  background: #fee2e2;
  box-shadow: 0 10px 22px rgba(248,113,113,0.35);
  transform: translateY(-1px);
}

/* EMPTY STATE */
.empty-box {
  text-align: center;
  padding: 2.2rem 1.6rem;
  background: rgba(248,250,252,0.98);
  border-radius: var(--pm-radius-lg);
  border: 1px solid rgba(226,232,240,0.95);
  box-shadow: var(--pm-shadow-soft);
  color: #0f172a;
}
.empty-box p {
  margin: 0;
  font-size: .95rem;
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
  .wrapper {
    padding-inline: 1rem;
  }
}
@media (max-width: 860px) {
  .nav-inner {
    padding-inline: 1rem;
  }
  .nav-links {
    display: none;
  }
}
@media (max-width: 750px) {
  .like-card {
    flex-direction: column;
    align-items: stretch;
  }
  .like-left {
    border-right: none;
    border-bottom: 1px solid #e5e7eb;
    margin-right: 0;
    margin-bottom: .9rem;
    padding-bottom: .6rem;
    align-items: center;
  }
  .like-center {
    margin-bottom: .9rem;
  }
  .like-actions {
    margin-left: 0;
    flex-direction: row;
    justify-content: flex-end;
  }
  .btn-primary,
  .btn-danger {
    width: 100%;
    justify-content: center;
  }
}
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
          <small>Panel empresa</small>
        </div>
      </div>
      <div class="nav-links">
        <a href="empresa_tinder.php">
          <span class="dot"></span>Buscar talentos
        </a>
        <a href="matches_empresa.php">
          <span class="dot"></span>Matches
        </a>
        <a href="historial_likes_empresa.php" class="active">
          <span class="dot"></span>Likes
        </a>
        <a href="perfil_empresa.php">
          <span class="dot"></span>Perfil
        </a>
        <a href="logout.php">
          <span class="dot"></span>Salir
        </a>
      </div>
    </div>

    <div class="nav-profile">
      <img src="<?php echo htmlspecialchars($empresa_foto_path); ?>" alt="<?php echo htmlspecialchars($empresa_data['razon_social']); ?>">
      <div class="nav-profile-info">
        <span class="nav-profile-name">
          <?php echo htmlspecialchars($empresa_data['razon_social']); ?>
        </span>
        <span class="nav-profile-role">Empresa · PractiMach</span>
      </div>
    </div>
  </div>
</nav>

<!-- CONTENIDO -->
<div class="wrapper">

  <div class="page-header">
    <div>
      <h1 class="title">Likes</h1>
      <div class="subtitle">
        Estudiantes que marcaron <strong>“Me interesa”</strong> en tu empresa.
      </div>
    </div>
    <div class="header-pill">
      👀 Revisa, genera matches o elimina likes
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
  <?php endif; ?>

  <div class="likes-panel">
    <?php if ($matches_result->num_rows > 0): ?>
      <?php $i = 0; while($match = $matches_result->fetch_assoc()): 
        $i++;
        $estudiante_foto_path = "assets/img/default-user.png";
        if (!empty($match['foto']) && file_exists("assets/uploads/" . $match['foto'])) {
            $estudiante_foto_path = "assets/uploads/" . $match['foto'];
        }

        $estadoClass = 'espera';
        $estadoText = '⏳ En espera de revisión';
        if($match['estado'] == 'aceptado') { $estadoClass = 'match'; $estadoText = '🔥 Match generado'; }
        elseif($match['estado'] == 'rechazado') { $estadoClass = 'rechazado'; $estadoText = '❌ Sin match / Rechazado'; }
      ?>
        <!-- LIKE <?php echo $i; ?> -->
        <div class="like-card">
          <div class="like-left">
            <div class="like-left-label">Alumno</div>
            <div class="like-left-index"><?php echo $i; ?></div>
          </div>

          <div class="like-center">
            <div class="like-img">
              <img src="<?php echo htmlspecialchars($estudiante_foto_path); ?>" alt="<?php echo htmlspecialchars($match['estudiante_nombre']); ?>">
            </div>

            <div class="like-info">
              <h3><?php echo htmlspecialchars($match['estudiante_nombre']); ?></h3>
              <p>Carrera: <?php echo htmlspecialchars($match['carrera']); ?></p>
              <p>Fecha de like: <?php echo (new DateTime($match['created_at']))->format('d/m/Y'); ?></p>
              <p class="estado <?php echo $estadoClass; ?>"><?php echo $estadoText; ?></p>
            </div>
          </div>

          <div class="like-actions">
            <!-- VER PERFIL DEL ESTUDIANTE -->
            <button class="btn-primary" onclick="location.href='ver_estudiante.php?estudiante_id=<?php echo (int)$match['estudiante_id']; ?>'">
              <span>Ver perfil</span> <span>👤</span>
            </button>

            <!-- ELIMINAR LIKE -->
            <form method="POST" style="margin:0;">
              <input type="hidden" name="eliminar_like_id" value="<?php echo (int)$match['match_id']; ?>">
              <button type="submit" class="btn-danger" onclick="return confirm('¿Eliminar este like? Esta acción no puede deshacerse.');">
                <span>Eliminar like</span> <span>🗑️</span>
              </button>
            </form>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-box">
        <p>No has recibido likes de estudiantes aún.</p>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- MENÚ INFERIOR MÓVIL -->
<div class="bottom-menu">
  <a href="empresa_tinder.php"><i>🔥</i>Buscar</a>
  <a href="matches_empresa.php"><i>❤️</i>Matches</a>
  <a href="historial_likes_empresa.php"><i>⭐</i>Likes</a>
  <a href="perfil_empresa.php"><i>👤</i>Perfil</a>
</div>

</body>
</html>
