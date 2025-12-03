<?php
session_start();
require_once 'config/config/conexion.php';

// Solo empresas pueden ver este perfil
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'empresa') {
    header("Location: auth.php");
    exit;
}

$empresa_id    = $_SESSION['user_id'];
$estudiante_id = isset($_GET['estudiante_id']) ? (int)$_GET['estudiante_id'] : 0;

if ($estudiante_id <= 0) {
    header("Location: historial_likes_empresa.php");
    exit;
}

// Datos de la empresa para el navbar
$stmt_empresa = $mysqli->prepare("SELECT razon_social, foto FROM empresas WHERE id = ?");
$stmt_empresa->bind_param("i", $empresa_id);
$stmt_empresa->execute();
$res_empresa = $stmt_empresa->get_result();
$empresa_data = $res_empresa->fetch_assoc();

$empresa_foto_path = "assets/img/default-company.png";
if (!empty($empresa_data['foto']) && file_exists("assets/uploads/" . $empresa_data['foto'])) {
    $empresa_foto_path = "assets/uploads/" . $empresa_data['foto'];
}

// Traer estudiante SOLO si tiene relación con la empresa (like / match)
$stmt = $mysqli->prepare("
    SELECT e.*
    FROM estudiantes e
    JOIN matches m ON m.estudiante_id = e.id
    WHERE e.id = ? AND m.empresa_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $estudiante_id, $empresa_id);
$stmt->execute();
$res = $stmt->get_result();
$estudiante = $res->fetch_assoc();

if (!$estudiante) {
    // No existe o no está relacionado con la empresa
    header("Location: historial_likes_empresa.php");
    exit;
}

// Foto del estudiante
$foto_path = "assets/img/default-user.png";
if (!empty($estudiante['foto']) && file_exists("assets/uploads/" . $estudiante['foto'])) {
    $foto_path = "assets/uploads/" . $estudiante['foto'];
}

// Teléfono del estudiante (puede estar vacío si es registro antiguo)
$telefono_estudiante = isset($estudiante['telefono']) ? trim($estudiante['telefono']) : '';

/* ===== CV DEL ESTUDIANTE =====
   - Columna BD: cv_archivo
   - Carpeta: practiimachfront/assets/uploads/cv
   Desde este archivo (en la raíz del proyecto) usamos ruta relativa:
   assets/uploads/cv/ARCHIVO
*/
$cvFilename   = $estudiante['cv_archivo'] ?? '';
$cvDisponible = false;
$cvUrl        = "";
$cvEsPdf      = false;

if (!empty($cvFilename)) {
    $cvDiskPath = "assets/uploads/cv/" . $cvFilename;          // ruta en disco
    if (file_exists($cvDiskPath)) {
        $cvDisponible = true;
        $cvUrl = "assets/uploads/cv/" . rawurlencode($cvFilename); // URL pública
        $ext = strtolower(pathinfo($cvFilename, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $cvEsPdf = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Perfil de <?php echo htmlspecialchars($estudiante['nombre']); ?> | PractiMach</title>
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

/* NAVBAR (PANEL EMPRESA) */
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
  box-shadow: 0 8px 22px rgba(79,70,229,0.7);
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

/* CARD PERFIL ESTUDIANTE */
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
  background: radial-gradient(circle at 0 0, rgba(56,189,248,0.16), transparent 60%),
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
.card-avatar {
  width: 96px;
  height: 96px;
  border-radius: 999px;
  overflow: hidden;
  background: linear-gradient(135deg, #e5e7eb, #f9fafb);
  box-shadow: 0 14px 36px rgba(15,23,42,0.35);
  border: 2px solid rgba(191,219,254,0.9);
  flex-shrink: 0;
}
.card-avatar img {
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
.badge-carrera {
  padding: .16rem .6rem;
  border-radius: 999px;
  background: #ecfdf5;
  border: 1px solid #bbf7d0;
  color: #166534;
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

/* CV */
.cv-box {
  background: #f9fafb;
  border-radius: 14px;
  border: 1px solid rgba(229,231,235,0.98);
  padding: .9rem .95rem 1rem;
}
.cv-actions {
  display: flex;
  flex-wrap: wrap;
  gap: .6rem;
  align-items: center;
  margin-bottom: .7rem;
}
.btn-download {
  padding: .45rem .9rem;
  border-radius: 999px;
  border: none;
  background: #111827;
  color: #f9fafb;
  font-size: .85rem;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  box-shadow: 0 10px 24px rgba(15,23,42,0.45);
  transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
}
.btn-download:hover {
  background: #020617;
  transform: translateY(-1px);
  box-shadow: 0 14px 30px rgba(15,23,42,0.65);
}
.cv-note {
  font-size: .8rem;
  color: #6b7280;
}
.cv-preview {
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
}
.cv-preview iframe,
.cv-preview embed {
  width: 100%;
  height: 420px;
  border: none;
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
          <span class="dot"></span>Likes recibidos
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
    <div class="page-title">Perfil del estudiante</div>
    <div class="page-subtitle">
      Detalle de <strong><?php echo htmlspecialchars($estudiante['nombre']); ?></strong>.
    </div>
  </div>

  <div class="card">
    <div class="card-inner">

      <div class="card-header">
        <div class="card-avatar">
          <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="<?php echo htmlspecialchars($estudiante['nombre']); ?>">
        </div>
        <div class="card-header-info">
          <h1><?php echo htmlspecialchars($estudiante['nombre']); ?></h1>
          <p><?php echo htmlspecialchars($estudiante['email']); ?></p>
          <div class="badges-row">
            <span class="badge-carrera">🎓 <?php echo htmlspecialchars($estudiante['carrera']); ?></span>
          </div>
        </div>
      </div>

      <div class="section-title">DNI</div>
      <div class="data-box">
        <?php echo htmlspecialchars($estudiante['dni']); ?>
      </div>

      <div class="section-title">Teléfono de contacto</div>
      <div class="data-box">
        <?php
          if ($telefono_estudiante !== '') {
              echo htmlspecialchars($telefono_estudiante);
          } else {
              echo '<span style="color:#6b7280;">Sin teléfono registrado</span>';
          }
        ?>
      </div>

      <div class="section-title">Descripción del estudiante</div>
      <div class="data-box desc">
        <?php echo nl2br(htmlspecialchars($estudiante['descripcion'])); ?>
      </div>

      <div class="section-title">Currículum (CV)</div>
      <div class="cv-box">
        <?php if ($cvDisponible): ?>
          <div class="cv-actions">
            <a class="btn-download" href="<?php echo htmlspecialchars($cvUrl); ?>" download>
              <span>⬇️ Descargar CV</span>
            </a>
            <span class="cv-note">
              <?php if ($cvEsPdf): ?>
                Vista previa disponible abajo (PDF).
              <?php else: ?>
                Formato no PDF, descárgalo para verlo.
              <?php endif; ?>
            </span>
          </div>

          <?php if ($cvEsPdf): ?>
            <div class="cv-preview">
              <embed src="<?php echo htmlspecialchars($cvUrl); ?>#view=FitH" type="application/pdf">
            </div>
          <?php endif; ?>

        <?php else: ?>
          <span class="cv-note">El estudiante aún no ha subido su CV.</span>
        <?php endif; ?>
      </div>

      <button class="btn-back" onclick="window.location.href='historial_likes_empresa.php';">
        <span class="icon">←</span>
        <span>Volver a Likes recibidos</span>
      </button>

    </div>
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
