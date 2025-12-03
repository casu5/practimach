<?php
session_start();
require_once 'config/config/conexion.php';

// Verificar sesión
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'empresa') {
    header("Location: index.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$user_id = $_SESSION['user_id'];
$msg = "";

// --- LÓGICA DE SUBIDA DE FOTO (LOGO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $file = $_FILES['foto_perfil'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            // Nombre único
            $new_name = "empresa_" . $user_id . "_" . time() . "." . $ext;
            $upload_dir = "assets/uploads/";
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                // Actualizar BD
                $stmt = $mysqli->prepare("UPDATE empresas SET foto = ? WHERE id = ?");
                $stmt->bind_param("si", $new_name, $user_id);
                if ($stmt->execute()) {
                    $msg = "Logo actualizado correctamente.";
                } else {
                    $msg = "Error al actualizar base de datos.";
                }
            } else {
                $msg = "Error al mover el archivo.";
            }
        } else {
            $msg = "Formato no permitido. Solo JPG, PNG o WEBP.";
        }
    }
}

// --- OBTENER DATOS DE LA EMPRESA ---
$stmt = $mysqli->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

// Teléfono (puede estar vacío si es registro antiguo)
$telefono_empresa = isset($user['telefono']) ? trim($user['telefono']) : '';

// Definir logo (si no tiene, usar default)
$foto_path = "assets/img/default-company.png"; 
if (!empty($user['foto']) && file_exists("assets/uploads/" . $user['foto'])) {
    $foto_path = "assets/uploads/" . $user['foto'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Perfil Empresa | PractiMach</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* RESET */
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

/* NAVBAR */
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

/* CONTENIDO PERFIL */
.wrapper {
  max-width: 1120px;
  margin: 2.1rem auto 3rem;
  padding: 0 1.5rem 3.5rem 1.5rem;
}
.profile-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  gap: 2rem;
  align-items: flex-start;
}

/* ALERTA */
.alert {
  padding: .75rem 1rem;
  border-radius: 10px;
  margin-bottom: 1.2rem;
  text-align: center;
  font-size: .9rem;
}
.alert-info {
  background: #dbeafe;
  color: #1e40af;
  border: 1px solid #bfdbfe;
}

/* TARJETA LOGO */
.logo-card {
  background: rgba(15,23,42,0.92);
  border-radius: var(--pm-radius-lg);
  padding: 1.4rem 1.2rem 1.5rem;
  border: 1px solid rgba(148,163,184,0.55);
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.5);
  color: #e5e7eb;
  position: relative;
  overflow: hidden;
}
.logo-card::before {
  content: "";
  position: absolute;
  inset: -30%;
  background: radial-gradient(circle at 0 0, rgba(56,189,248,0.25), transparent 65%),
              radial-gradient(circle at 100% 100%, rgba(129,140,248,0.28), transparent 55%);
  opacity: 0.9;
  pointer-events: none;
}
.logo-card-inner {
  position: relative;
  z-index: 1;
}
.logo-title {
  font-size: .9rem;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #cbd5f5;
  margin-bottom: .5rem;
}
.logo-box {
  width: 180px;
  height: 180px;
  border-radius: 24px;
  overflow: hidden;
  border: 2px solid rgba(191,219,254,0.9);
  background: radial-gradient(circle, #e5e7eb, #cbd5e1);
  box-shadow: 0 16px 40px rgba(15,23,42,0.7);
  margin-bottom: 1rem;
}
.logo-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.upload-form {
  display: flex;
  flex-direction: column;
  gap: .5rem;
}
.file-input {
  display: none;
}
.btn-change-logo {
    background: #f9fafb;
    color: #111827;
    padding: .55rem .85rem;
    border-radius: 999px;
    border: 1px solid rgba(209, 213, 219, 0.95);
    cursor: pointer;
    font-size: .8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    transition: background .2s ease, transform .15s ease, box-shadow .2s ease;
}
.btn-change-logo span.icon {
  font-size: 1rem;
}
.btn-change-logo:hover {
    background: #e5e7eb;
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(15,23,42,0.45);
}
.logo-hint {
  font-size: .75rem;
  color: #cbd5f5;
}

/* TARJETA INFO EMPRESA */
.info-card {
  background: var(--pm-card-bg);
  border-radius: var(--pm-radius-lg);
  box-shadow: var(--pm-shadow-soft);
  padding: 1.6rem 1.5rem 1.4rem;
  border: 1px solid rgba(226,232,240,0.95);
  position: relative;
  overflow: hidden;
}
.info-card::before {
  content: "";
  position: absolute;
  inset: -40%;
  background: radial-gradient(circle at 100% 0, rgba(251,191,36,0.18), transparent 60%);
  opacity: 0.9;
  pointer-events: none;
}
.info-inner {
  position: relative;
  z-index: 1;
}
.info-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 1rem;
}
.info-title-block h1 {
  margin: 0;
  font-size: 1.55rem;
  letter-spacing: -.01em;
}
.info-sub {
  margin-top: .25rem;
  font-size: .9rem;
  color: var(--pm-muted);
}

/* ESTADO EMPRESA */
.badge-estado {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  padding: .2rem .7rem;
  border-radius: 999px;
  font-size: .8rem;
  font-weight: 600;
  border-width: 1px;
  border-style: solid;
}
.badge-estado.bg-revision {
  background: #fffbeb;
  color: #92400e;
  border-color: #fed7aa;
}
.badge-estado.bg-validada {
  background: #ecfdf5;
  color: #166534;
  border-color: #bbf7d0;
}
.badge-estado.bg-bloqueada {
  background: #fef2f2;
  color: #b91c1c;
  border-color: #fecaca;
}

/* SECCIONES */
.section-title {
  margin-top: 1.1rem;
  margin-bottom: .3rem;
  font-weight: 600;
  font-size: .9rem;
  color: #111827;
}
.data-box {
  background: #f9fafb;
  padding: 0.75rem .9rem;
  border-radius: 12px;
  border: 1px solid rgba(229,231,235,0.98);
  margin-bottom: .4rem;
  font-size: .9rem;
  color: #1f2937;
  word-break: break-word;
}
.data-box.desc {
  min-height: 80px;
}

/* BOTÓN EDITAR */
.btn-editar {
  margin-top: 1.4rem;
  width: 100%;
  background-color: #111827;
  border: none;
  padding: .9rem 1rem;
  color: #f9fafb;
  border-radius: 999px;
  font-size: .95rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .4rem;
  box-shadow: 0 14px 30px rgba(15,23,42,0.55);
  transition: background .2s ease, transform .15s ease, box-shadow .2s ease;
}
.btn-editar span.icon {
  font-size: 1rem;
}
.btn-editar:hover {
  background-color: #020617;
  transform: translateY(-1px);
  box-shadow: 0 18px 40px rgba(15,23,42,0.7);
}

/* MENÚ INFERIOR MÓVIL EMPRESA */
.bottom-menu {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  background: rgba(15,23,42,0.98);
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
@media(max-width: 800px) {
  .nav-inner {
    padding-inline: 1rem;
  }
  .nav-links {
    display: none;
  }
  .profile-layout {
    grid-template-columns: minmax(0, 1fr);
  }
  .wrapper {
    padding-inline: 1rem;
    margin-top: 1.6rem;
    margin-bottom: 4.5rem; /* espacio para que no tape el bottom-menu */
  }
  .info-header {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media(min-width: 801px) {
  .bottom-menu {
    display: none;
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
        <a href="historial_likes_empresa.php">
          <span class="dot"></span>Likes
        </a>
        <a href="perfil_empresa.php" class="active">
          <span class="dot"></span>Perfil
        </a>
        <a href="logout.php">
          <span class="dot"></span>Salir
        </a>
      </div>
    </div>

    <div class="nav-profile">
      <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Logo">
      <div class="nav-profile-info">
        <span class="nav-profile-name">
          <?php echo htmlspecialchars($user['razon_social']); ?>
        </span>
        <span class="nav-profile-role">Empresa · PractiMach</span>
      </div>
    </div>
  </div>
</nav>

<!-- CONTENIDO -->
<div class="wrapper">

  <?php if($msg): ?>
    <div class="alert alert-info"><?php echo $msg; ?></div>
  <?php endif; ?>

  <div class="profile-layout">

    <!-- COLUMNA LOGO -->
    <div class="logo-card">
      <div class="logo-card-inner">
        <div class="logo-title">Logo de la empresa</div>
        <div class="logo-box">
          <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Logo empresa">
        </div>
        
        <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
          <label for="foto_perfil" class="btn-change-logo">
            <span class="icon">🖼️</span>
            <span>Cambiar logo</span>
          </label>
          <input type="file" name="foto_perfil" id="foto_perfil" class="file-input" accept="image/*" onchange="this.form.submit()">
          <span class="logo-hint">Formatos permitidos: JPG, PNG, WEBP.</span>
        </form>
      </div>
    </div>

    <!-- COLUMNA INFO -->
    <div class="info-card">
      <div class="info-inner">

        <div class="info-header">
          <div class="info-title-block">
            <h1><?php echo htmlspecialchars($user['razon_social']); ?></h1>
            <div class="info-sub">
              Sector: <?php echo htmlspecialchars($user['sector']); ?>
            </div>
          </div>

          <?php 
            $estadoClass = 'bg-revision';
            $estadoText  = 'En revisión';
            if ($user['estado'] == 'validada') {
              $estadoClass = 'bg-validada';
              $estadoText  = 'Validada';
            } elseif ($user['estado'] == 'bloqueada') {
              $estadoClass = 'bg-bloqueada';
              $estadoText  = 'Bloqueada';
            }
          ?>
          <span class="badge-estado <?php echo $estadoClass; ?>">
            <span>●</span>
            <span>Estado: <?php echo $estadoText; ?></span>
          </span>
        </div>

        <div class="section-title">Razón Social</div>
        <div class="data-box"><?php echo htmlspecialchars($user['razon_social']); ?></div>

        <div class="section-title">RUC</div>
        <div class="data-box"><?php echo htmlspecialchars($user['ruc']); ?></div>

        <div class="section-title">Correo de contacto</div>
        <div class="data-box">
          <?php echo htmlspecialchars($user['email']); ?>
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
          <?php echo nl2br(htmlspecialchars($user['descripcion'])); ?>
        </div>

        <button class="btn-editar" onclick="location.href='empresa_editar.php'">
          <span class="icon">✏️</span>
          <span>Editar perfil de empresa</span>
        </button>

      </div>
    </div>

  </div>
</div>

<!-- MENÚ INFERIOR MÓVIL EMPRESA -->
<div class="bottom-menu">
  <a href="empresa_tinder.php"><i>🔥</i>Buscar</a>
  <a href="matches_empresa.php"><i>❤️</i>Matches</a>
  <a href="historial_likes_empresa.php"><i>⭐</i>Likes</a>
  <a href="perfil_empresa.php"><i>👤</i>Perfil</a>
</div>

</body>
</html>
