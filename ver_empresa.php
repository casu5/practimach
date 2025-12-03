<?php
session_start();
require_once 'config/config/conexion.php';

// Solo estudiantes pueden ver perfiles de empresas
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header("Location: auth.php");
    exit;
}

$estudiante_id = $_SESSION['user_id'];
$empresa_id    = isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : 0;

if ($empresa_id <= 0) {
    header("Location: historial_likes.php");
    exit;
}

// Datos del estudiante para el navbar
$stmt_estudiante = $mysqli->prepare("SELECT nombre, foto FROM estudiantes WHERE id = ?");
$stmt_estudiante->bind_param("i", $estudiante_id);
$stmt_estudiante->execute();
$res_estudiante = $stmt_estudiante->get_result();
$estudiante_data = $res_estudiante->fetch_assoc();

$estudiante_foto_path = "assets/img/default-user.png";
if (!empty($estudiante_data['foto']) && file_exists("assets/uploads/" . $estudiante_data['foto'])) {
    $estudiante_foto_path = "assets/uploads/" . $estudiante_data['foto'];
}

// Traer la empresa SOLO si tiene relación con el estudiante (like / match)
$stmt_emp = $mysqli->prepare("
    SELECT emp.*
    FROM empresas emp
    JOIN matches m ON m.empresa_id = emp.id
    WHERE emp.id = ? AND m.estudiante_id = ?
    LIMIT 1
");
$stmt_emp->bind_param("ii", $empresa_id, $estudiante_id);
$stmt_emp->execute();
$res_emp = $stmt_emp->get_result();
$empresa = $res_emp->fetch_assoc();

if (!$empresa) {
    // No hay relación (seguridad extra)
    header("Location: historial_likes.php");
    exit;
}

$logo_path = "assets/img/default-company.png";
if (!empty($empresa['foto']) && file_exists("assets/uploads/" . $empresa['foto'])) {
    $logo_path = "assets/uploads/" . $empresa['foto'];
}

// Teléfono de la empresa (puede estar vacío en registros antiguos)
$telefono_empresa = isset($empresa['telefono']) ? trim($empresa['telefono']) : '';

// Estado (si usas el mismo campo que en perfil_empresa)
$estadoClass = 'bg-revision';
$estadoText  = 'En revisión';
if (isset($empresa['estado'])) {
    if ($empresa['estado'] === 'validada') {
        $estadoClass = 'bg-validada';
        $estadoText  = 'Validada';
    } elseif ($empresa['estado'] === 'bloqueada') {
        $estadoClass = 'bg-bloqueada';
        $estadoText  = 'Bloqueada';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($empresa['razon_social']); ?> | PractiMach</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
*,
*::before,
*::after {
  box-sizing: border-box;
}

/* VARIABLES */
:root {
  --pm-bg: #020617;
  --pm-card-bg: #ffffff;
  --pm-border: #e5e7eb;
  --pm-muted: #6b7280;
  --pm-text: #0f172a;
  --pm-radius-lg: 20px;
  --pm-shadow-soft: 0 18px 45px rgba(15,23,42,0.18);
}

/* GENERAL */
body {
  margin: 0;
  background: radial-gradient(circle at top left, #1d4ed8 0, #020617 52%, #020617 100%);
  font-family: "Poppins", sans-serif;
  color: var(--pm-text);
  min-height: 100vh;
  overflow-x: hidden;
}

/* NAVBAR (MISMO ESTILO PANEL ESTUDIANTE) */
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

/* CONTENEDOR */
.wrapper {
  max-width: 1120px;
  margin: 2.1rem auto 3.5rem;
  padding: 0 1.5rem;
}

/* CABECERA PÁGINA */
.page-header {
  color: #f9fafb;
  margin-bottom: 1.4rem;
}
.page-title {
  font-size: 1.6rem;
  font-weight: 600;
  margin: 0 0 .2rem 0;
}
.page-subtitle {
  font-size: .9rem;
  color: #cbd5f5;
}

/* CARD PERFIL EMPRESA */
.card {
  background: var(--pm-card-bg);
  border-radius: var(--pm-radius-lg);
  box-shadow: var(--pm-shadow-soft);
  padding: 1.8rem 1.6rem 1.6rem;
  border: 1px solid rgba(226,232,240,0.95);
  position: relative;
  overflow: hidden;
}
.card::before {
  content: "";
  position: absolute;
  inset: -40%;
  background: radial-gradient(circle at 0 0, rgba(251,191,36,0.16), transparent 60%),
              radial-gradient(circle at 100% 100%, rgba(129,140,248,0.18), transparent 55%);
  opacity: 0.9;
  pointer-events: none;
}
.card-inner {
  position: relative;
  z-index: 1;
}

/* HEADER DENTRO DE LA CARD */
.card-header {
  display: flex;
  gap: 1.4rem;
  align-items: center;
  margin-bottom: 1.3rem;
}
.card-logo {
  width: 96px;
  height: 96px;
  border-radius: 26px;
  overflow: hidden;
  background: linear-gradient(135deg, #e5e7eb, #f9fafb);
  box-shadow: 0 14px 36px rgba(15,23,42,0.35);
  border: 2px solid rgba(191,219,254,0.9);
  flex-shrink: 0;
}
.card-logo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.card-header-info h1 {
  margin: 0;
  font-size: 1.4rem;
}
.card-header-info p {
  margin: .2rem 0;
  font-size: .9rem;
  color: var(--pm-muted);
}
.badges-row {
  margin-top: .35rem;
  display: flex;
  flex-wrap: wrap;
  gap: .4rem;
  font-size: .78rem;
}
.badge-sector {
  padding: .16rem .6rem;
  border-radius: 999px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
}

/* ESTADO EMPRESA */
.badge-estado {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  padding: .18rem .6rem;
  border-radius: 999px;
  font-size: .78rem;
  font-weight: 600;
  border-width: 1px;
  border-style: solid;
}
.bg-revision {
  background: #fffbeb;
  color: #92400e;
  border-color: #fed7aa;
}
.bg-validada {
  background: #ecfdf5;
  color: #166534;
  border-color: #bbf7d0;
}
.bg-bloqueada {
  background: #fef2f2;
  color: #b91c1c;
  border-color: #fecaca;
}

/* SECCIONES */
.section-title {
  font-size: .9rem;
  font-weight: 600;
  margin-top: 1rem;
  margin-bottom: .3rem;
  color: #111827;
}
.data-box {
  background: #f9fafb;
  padding: .7rem .9rem;
  border-radius: 12px;
  border: 1px solid rgba(229,231,235,0.98);
  font-size: .9rem;
  color: #111827;
  word-break: break-word;
}
.data-box.desc {
  min-height: 80px;
}

/* BOTÓN VOLVER */
.btn-back {
  margin-top: 1.4rem;
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .65rem 1.1rem;
  border-radius: 999px;
  border: none;
  background: #020617;
  color: #f9fafb;
  font-size: .9rem;
  font-weight: 500;
  cursor: pointer;
  box-shadow: 0 14px 30px rgba(15,23,42,0.55);
  transition: .2s;
}
.btn-back span.icon {
  font-size: 1.1rem;
}
.btn-back:hover {
  transform: translateY(-1px);
  box-shadow: 0 18px 40px rgba(15,23,42,0.75);
}

/* MENÚ INFERIOR MÓVIL (MISMO QUE OTROS) */
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
  .card-header {
    flex-direction: column;
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
  .wrapper {
    padding-inline: 1rem;
    margin-top: 1.6rem;
  }
}

/* Ocultar bottom-menu en PC */
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
        <a href="historial_likes.php">
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
<div class="wrapper">

  <div class="page-header">
    <div class="page-title">Perfil de empresa</div>
    <div class="page-subtitle">
      Detalle de <strong><?php echo htmlspecialchars($empresa['razon_social']); ?></strong>.
    </div>
  </div>

  <div class="card">
    <div class="card-inner">

      <div class="card-header">
        <div class="card-logo">
          <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($empresa['razon_social']); ?>">
        </div>

        <div class="card-header-info">
          <h1><?php echo htmlspecialchars($empresa['razon_social']); ?></h1>
          <p><?php echo htmlspecialchars($empresa['sector']); ?></p>

          <div class="badges-row">
            <span class="badge-sector">🏢 Sector: <?php echo htmlspecialchars($empresa['sector']); ?></span>
            <?php if (isset($empresa['estado'])): ?>
              <span class="badge-estado <?php echo $estadoClass; ?>">
                <span>●</span>
                <span><?php echo $estadoText; ?></span>
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="section-title">RUC</div>
      <div class="data-box">
        <?php echo htmlspecialchars($empresa['ruc']); ?>
      </div>

      <div class="section-title">Correo de contacto</div>
      <div class="data-box">
        <?php echo htmlspecialchars($empresa['email']); ?>
      </div>

      <div class="section-title">Teléfono de contacto</div>
      <div class="data-box">
        <?php
          if ($telefono_empresa !== '') {
              echo htmlspecialchars($telefono_empresa);
          } else {
              echo '<span style="color:#6b7280;">Sin teléfono registrado</span>';
          }
        ?>
      </div>

      <div class="section-title">Descripción de la empresa</div>
      <div class="data-box desc">
        <?php echo nl2br(htmlspecialchars($empresa['descripcion'])); ?>
      </div>

      <button class="btn-back" onclick="window.location.href='historial_likes.php';">
        <span class="icon">←</span>
        <span>Volver a mis likes</span>
      </button>

    </div>
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
