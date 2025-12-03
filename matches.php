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

// Obtener matches confirmados para este estudiante
$sql = "SELECT 
            m.*,
            emp.razon_social,
            emp.sector,
            emp.foto,
            emp.telefono AS telefono_empresa
        FROM matches m
        JOIN empresas emp ON m.empresa_id = emp.id
        WHERE m.estudiante_id = ? 
          AND m.estado = 'match'
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
<title>Mis Matches | PractiMach</title>
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

/* NAVBAR (mismo estilo PractiMach) */
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
.matches-wrapper {
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

/* TARJETA CONTENEDORA DE MATCHES */
.matches-card {
  background: var(--pm-card-bg);
  border-radius: var(--pm-radius-lg);
  box-shadow: var(--pm-shadow-soft);
  padding: 1.5rem 1.4rem 1.6rem;
  border: 1px solid rgba(226,232,240,0.95);
  position: relative;
  overflow: hidden;
}
.matches-card::before {
  content: "";
  position: absolute;
  inset: -40%;
  background: radial-gradient(circle at 0 0, rgba(244,114,182,0.12), transparent 60%),
              radial-gradient(circle at 100% 100%, rgba(129,140,248,0.18), transparent 55%);
  opacity: 0.9;
  pointer-events: none;
}

/* LISTA MATCHES */
.matches-list {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* TARJETA DE CADA MATCH */
.match-card {
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
.match-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 36px rgba(15,23,42,0.12);
  border-color: rgba(165,180,252,0.9);
}

/* FOTO / LOGO EMPRESA */
.match-img {
  width: 76px;
  height: 76px;
  border-radius: 18px;
  overflow: hidden;
  background: linear-gradient(135deg, #e5e7eb, #f9fafb);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
}
.match-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* INFO EMPRESA */
.match-info {
  display: flex;
  flex-direction: column;
  gap: .25rem;
}
.match-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: #0f172a;
}
.match-sector {
  font-size: .85rem;
  color: #4b5563;
}
.match-meta {
  display: flex;
  flex-wrap: wrap;
  gap: .4rem;
  margin-top: .2rem;
  font-size: .78rem;
  color: #6b7280;
}
.match-badge {
  padding: .15rem .55rem;
  border-radius: 999px;
  background: #ecfdf5;
  color: #15803d;
  border: 1px solid #bbf7d0;
  font-size: .78rem;
  display: inline-flex;
  align-items: center;
  gap: .25rem;
}
.match-meta-pill {
  padding: .1rem .55rem;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

/* BOTONES */
.match-actions {
  display: flex;
  flex-direction: column;
  gap: .45rem;
}
.btn {
  padding: .55rem .9rem;
  border-radius: 999px;
  border: none;
  cursor: pointer;
  font-weight: 500;
  font-size: .85rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .35rem;
  transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
}
.btn span.icon {
  font-size: .95rem;
}
.btn-msj {
  background: #111827;
  color: #f9fafb;
  box-shadow: 0 8px 20px rgba(15,23,42,0.4);
}
.btn-msj:hover {
  background: #020617;
  transform: translateY(-1px);
  box-shadow: 0 12px 28px rgba(15,23,42,0.45);
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
  .match-card {
    grid-template-columns: auto minmax(0, 1fr);
    grid-template-rows: auto auto;
    align-items: flex-start;
  }
  .match-actions {
    grid-column: 1 / -1;
    flex-direction: row;
    justify-content: flex-end;
    margin-top: .6rem;
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
  .matches-wrapper {
    padding-inline: 1rem;
    margin-top: 1.4rem;
  }
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }
  .match-card {
    grid-template-columns: minmax(0, 1fr);
    text-align: left;
  }
  .match-img {
    margin: 0 auto .7rem auto;
  }
  .match-actions {
    justify-content: center;
  }
}

/* DESACTIVAR MENÚ INFERIOR EN PC */
@media(min-width: 801px) {
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
        <a href="matches.php" class="active">
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
<div class="matches-wrapper">
  <div class="page-header">
    <div>
      <div class="page-title">Mis matches ❤️</div>
      <div class="page-subtitle">
        Aquí verás las empresas que también están interesadas en tu perfil.
      </div>
    </div>
    <div class="page-pill">
      🔥 Match confirmado
    </div>
  </div>

  <div class="matches-card">
    <?php if ($matches_result->num_rows > 0): ?>
      <div class="matches-list">
        <?php while($match = $matches_result->fetch_assoc()): 
          $empresa_foto_path = "assets/img/default-company.png";
          if (!empty($match['foto']) && file_exists("assets/uploads/" . $match['foto'])) {
              $empresa_foto_path = "assets/uploads/" . $match['foto'];
          }

          // WhatsApp: usar teléfono de la empresa
          $telefono_empresa = $match['telefono_empresa'] ?? '';
          $waUrl = '';
          if (!empty($telefono_empresa)) {
              // Dejar solo dígitos (quita +, espacios, etc.)
              $telefono_limpio = preg_replace('/\D+/', '', $telefono_empresa);
              if ($telefono_limpio !== '') {
                  $mensaje = "Hola, soy " . $estudiante_data['nombre'] . " y vi que tenemos un match en PractiMach. Me gustaría coordinar más detalles sobre las prácticas.";
                  $waUrl = "https://wa.me/" . $telefono_limpio . "?text=" . urlencode($mensaje);
              }
          }
        ?>
          <div class="match-card">
            <div class="match-img">
              <img src="<?php echo htmlspecialchars($empresa_foto_path); ?>" alt="<?php echo htmlspecialchars($match['razon_social']); ?>">
            </div>

            <div class="match-info">
              <div class="match-title">
                <?php echo htmlspecialchars($match['razon_social']); ?>
              </div>
              <div class="match-sector">
                Sector: <?php echo htmlspecialchars($match['sector']); ?>
              </div>
              <div class="match-meta">
                <div class="match-badge">
                  🔥 Match confirmado
                </div>
                <div class="match-meta-pill">
                  Empresa interesada en tu perfil
                </div>
              </div>
            </div>

            <div class="match-actions">
              <?php if ($waUrl): ?>
                <a class="btn btn-msj" href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank">
                  <span class="icon">✉️</span>
                  <span>Contactar</span>
                </a>
              <?php else: ?>
                <button class="btn btn-msj" type="button" onclick="alert('Esta empresa aún no ha registrado un número de WhatsApp.');">
                  <span class="icon">✉️</span>
                  <span>Contactar</span>
                </button>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-emoji">🙂</div>
        <div class="empty-title">Aún no tienes matches</div>
        <div class="empty-text">
          Sigue explorando ofertas en <strong>“Buscar prácticas”</strong> y dando like a las empresas que te interesen.
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
