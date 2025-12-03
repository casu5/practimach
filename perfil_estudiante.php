<?php
session_start();
require_once 'config/config/conexion.php';

// Verificar sesión
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header("Location: index.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$user_id = $_SESSION['user_id'];
$msg = "";

// --- SUBIDA DE FOTO DE PERFIL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $file = $_FILES['foto_perfil'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $new_name = "estudiante_" . $user_id . "_" . time() . "." . $ext;
            $upload_dir = "assets/uploads/";
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $stmt = $mysqli->prepare("UPDATE estudiantes SET foto = ? WHERE id = ?");
                $stmt->bind_param("si", $new_name, $user_id);
                if ($stmt->execute()) {
                    $msg = "Foto actualizada correctamente.";
                } else {
                    $msg = "Error al actualizar la foto en la base de datos.";
                }
            } else {
                $msg = "Error al mover el archivo de foto.";
            }
        } else {
            $msg = "Formato de foto no permitido. Solo JPG, PNG o WEBP.";
        }
    }
}

// --- SUBIDA DE CV ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv_archivo'])) {
    $file = $_FILES['cv_archivo'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // formatos permitidos para CV
        $allowed_cv = ['pdf', 'doc', 'docx', 'odt'];

        if (in_array($ext, $allowed_cv)) {
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                $msg = "El CV es demasiado pesado. Tamaño máximo: 5 MB.";
            } else {
                $cv_dir = "assets/uploads/cv/";
                if (!is_dir($cv_dir)) {
                    mkdir($cv_dir, 0777, true);
                }

                $new_cv_name = "cv_estudiante_" . $user_id . "_" . time() . "." . $ext;

                if (move_uploaded_file($file['tmp_name'], $cv_dir . $new_cv_name)) {
                    // Guardar nombre en la BD (columna cv_archivo)
                    $stmt = $mysqli->prepare("UPDATE estudiantes SET cv_archivo = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_cv_name, $user_id);
                    if ($stmt->execute()) {
                        $msg = "CV subido/actualizado correctamente.";
                    } else {
                        $msg = "Error al actualizar el CV en la base de datos.";
                    }
                } else {
                    $msg = "Error al mover el archivo de CV.";
                }
            }
        } else {
            $msg = "Formato de CV no permitido. Solo PDF, DOC, DOCX u ODT.";
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        $msg = "Ocurrió un error al subir el CV.";
    }
}

// --- OBTENER DATOS DEL ESTUDIANTE ---
$stmt = $mysqli->prepare("SELECT * FROM estudiantes WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

// FOTO
$foto_path = "assets/img/default-user.png"; 
if (!empty($user['foto']) && file_exists("assets/uploads/" . $user['foto'])) {
    $foto_path = "assets/uploads/" . $user['foto'];
}

// CV
$cv_path = null;
if (!empty($user['cv_archivo']) && file_exists("assets/uploads/cv/" . $user['cv_archivo'])) {
    $cv_path = "assets/uploads/cv/" . $user['cv_archivo'];
}

// Teléfono (puede estar vacío si es antiguo)
$telefono = isset($user['telefono']) ? trim($user['telefono']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Perfil | PractiMach</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* RESET BÁSICO */
*, *::before, *::after {
  box-sizing: border-box;
}

/* ----- VARIABLES ----- */
:root {
  --pm-primary: #4f46e5;
  --pm-primary-soft: #eef2ff;
  --pm-primary-dark: #3730a3;
  --pm-bg: #020617;
  --pm-bg-soft: #0b1120;
  --pm-card-bg: #ffffff;
  --pm-border: #e5e7eb;
  --pm-muted: #6b7280;
  --pm-text: #0f172a;
  --pm-radius-lg: 20px;
  --pm-radius-md: 14px;
  --pm-shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.16);
}

/* ----- GENERAL ----- */
body {
  margin: 0;
  background: radial-gradient(circle at top left, #1d4ed8 0, #020617 52%, #020617 100%);
  font-family: "Poppins", sans-serif;
  overflow-x: hidden;
  color: var(--pm-text);
  min-height: 100vh;
}

/* ----- NAVBAR ----- */
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

/* ----- CONTENEDOR PRINCIPAL ----- */
.perfil-wrapper {
  max-width: 1120px;
  margin: 1.8rem auto 2.5rem;
  padding: 0 1.5rem 0.5rem;
}

/* ----- TARJETA PERFIL ----- */
.perfil-card {
  background: var(--pm-card-bg);
  border-radius: var(--pm-radius-lg);
  box-shadow: var(--pm-shadow-soft);
  padding: 1.8rem 1.6rem 1.8rem;
  display: grid;
  grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
  gap: 2rem;
  border: 1px solid rgba(226,232,240,0.9);
  position: relative;
  overflow: hidden;
}
.perfil-card::before {
  content: "";
  position: absolute;
  inset: -40%;
  background: radial-gradient(circle at 0 0, rgba(129,140,248,0.12), transparent 60%),
              radial-gradient(circle at 100% 100%, rgba(56,189,248,0.1), transparent 55%);
  opacity: 0.9;
  pointer-events: none;
}

/* ----- CHIP ENCABEZADO ----- */
.perfil-chip-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  margin-bottom: 1.1rem;
  position: relative;
  z-index: 1;
}
.chip-pill {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  border-radius: 999px;
  font-size: .75rem;
  padding: .2rem .7rem .2rem .2rem;
  background: rgba(239,246,255,0.95);
  border: 1px solid rgba(191,219,254,0.9);
  color: #1d4ed8;
}
.chip-pill-avatar {
  width: 22px;
  height: 22px;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at 30% 0, #bfdbfe, #1d4ed8);
  color: #eff6ff;
  font-size: .8rem;
  font-weight: 600;
}
.chip-badge {
  font-size: .75rem;
  padding: .15rem .55rem;
  border-radius: 999px;
  background: #ecfdf5;
  color: #166534;
  border: 1px solid #bbf7d0;
}

/* ----- COLUMNA IZQUIERDA ----- */
.foto-col {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

/* FOTO */
.foto-wrapper {
  position: relative;
  display: flex;
  justify-content: center;
}

.foto-box {
  width: 180px;
  height: 180px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid #4f46e5;
  background: linear-gradient(135deg, #e5e7eb, #f9fafb);
  box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25);
}
.foto-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Formulario de subida de imagen */
.upload-form {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .45rem;
}
.file-input {
  display: none;
}
.btn-subir-foto {
  background: #4f46e5;
  color: #fff;
  padding: .45rem .9rem;
  border-radius: 999px; 
  border: none;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 500;
  letter-spacing: 0.01em;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  box-shadow: 0 10px 25px rgba(79,70,229,0.4);
  transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
}
.btn-subir-foto span.icon {
  font-size: .95rem;
}
.btn-subir-foto:hover {
  background: #4338ca;
  transform: translateY(-1px);
  box-shadow: 0 12px 28px rgba(79,70,229,0.5);
}
.btn-subir-foto:active {
  transform: translateY(0);
  box-shadow: 0 6px 14px rgba(79,70,229,0.4);
}
.foto-note {
  font-size: .75rem;
  color: var(--pm-muted);
}

/* CARD CV */
.cv-card {
  margin-top: .4rem;
  background: #f9fafb;
  border-radius: var(--pm-radius-md);
  border: 1px solid rgba(226,232,240,0.95);
  padding: 0.7rem 0.9rem 0.85rem;
  font-size: .85rem;
  color: #111827;
  display: flex;
  flex-direction: column;
  gap: .4rem;
}
.cv-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
}
.cv-title {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  font-weight: 600;
  font-size: .88rem;
}
.cv-title span.icon {
  font-size: 1.1rem;
}
.cv-status {
  font-size: .75rem;
  padding: .1rem .5rem;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}
.cv-status.empty {
  background: #fef2f2;
  color: #b91c1c;
  border-color: #fecaca;
}
.cv-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
  margin-top: .25rem;
}
.btn-subir-cv {
  background: #111827;
  color: #f9fafb;
  padding: .38rem .75rem;
  border-radius: 999px;
  border: none;
  cursor: pointer;
  font-size: 0.8rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  transition: background .16s ease, transform .16s ease, box-shadow .16s ease;
}
.btn-subir-cv span.icon {
  font-size: .9rem;
}
.btn-subir-cv:hover {
  background: #020617;
  transform: translateY(-1px);
  box-shadow: 0 10px 24px rgba(15,23,42,0.35);
}
.cv-link {
  font-size: .78rem;
  color: #1d4ed8;
  text-decoration: none;
  font-weight: 500;
}
.cv-link:hover {
  text-decoration: underline;
}

/* ----- COLUMNA DERECHA (INFO) ----- */
.perfil-info {
  position: relative;
  z-index: 1;
}
.perfil-header {
  margin-bottom: 1.4rem;
}
.perfil-info h1 {
  margin: 0;
  font-size: 1.7rem;
  letter-spacing: -.01em;
}
.perfil-sub-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .6rem;
  margin-top: .45rem;
}
.sub-text {
  font-size: .9rem;
  color: var(--pm-muted);
}
.badge-carrera {
  font-size: .8rem;
  padding: .18rem .6rem;
  border-radius: 999px;
  background: var(--pm-primary-soft);
  color: var(--pm-primary-dark);
  border: 1px solid rgba(199,210,254,0.9);
}

/* BLOQUES DE DATOS */
.section-title {
  font-weight: 600;
  margin-top: 1.1rem;
  margin-bottom: .25rem;
  font-size: .9rem;
  color: #4b5563;
}
.section-title span {
  font-size: .75rem;
  font-weight: 500;
  color: #9ca3af;
}
.data-box {
  background: #f9fafb;
  padding: 0.75rem 0.9rem;
  border-radius: var(--pm-radius-md);
  border: 1px solid rgba(229,231,235,0.95);
  font-size: .9rem;
  color: #111827;
  margin-bottom: .3rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
}
.data-box-key {
  font-weight: 500;
  font-size: .9rem;
}
.data-box-value {
  font-size: .9rem;
  color: #374151;
  text-align: right;
  max-width: 60%;
}

/* DESCRIPCIÓN */
.data-box-full {
  background: #f9fafb;
  padding: 0.85rem 0.95rem;
  border-radius: var(--pm-radius-md);
  border: 1px solid rgba(229,231,235,0.95);
  font-size: .9rem;
  color: #111827;
  margin-bottom: .3rem;
  min-height: 70px;
}

/* BOTÓN EDITAR */
.btn-editar {
  margin-top: 1.6rem;
  width: 100%;
  background: linear-gradient(90deg, #111827, #020617);
  color: #f9fafb;
  border: none;
  padding: 0.9rem 1rem;
  border-radius: 999px;
  font-size: .95rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  box-shadow: 0 14px 35px rgba(15,23,42,0.35);
  transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}
.btn-editar span.icon {
  font-size: 1rem;
}
.btn-editar:hover {
  transform: translateY(-1px);
  box-shadow: 0 18px 40px rgba(15,23,42,0.45);
}
.btn-editar:active {
  transform: translateY(0);
  box-shadow: 0 8px 20px rgba(15,23,42,0.4);
}

/* ALERTAS */
.alert {
  padding: 10px 12px;
  border-radius: 999px;
  margin-bottom: 16px;
  text-align: center;
  font-size: .85rem;
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}
.alert span.icon {
  font-size: 1rem;
}

/* RESPONSIVE */
@media (max-width: 930px) {
  .perfil-card {
    grid-template-columns: minmax(0, 1fr);
    padding: 1.5rem 1.3rem 1.6rem;
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
  .perfil-wrapper {
    padding-inline: 1rem;
    margin-top: 1.4rem;
  }
  .perfil-card {
    padding: 1.3rem 1.1rem 1.4rem;
  }
  .foto-box {
    width: 150px;
    height: 150px;
  }
  .perfil-info h1 {
    font-size: 1.45rem;
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
        <a href="perfil_estudiante.php" class="active">
          <span class="dot"></span>Mi perfil
        </a>
        <a href="logout.php">
          <span class="dot"></span>Salir
        </a>
      </div>
    </div>

    <div class="nav-profile">
      <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Perfil">
      <div class="nav-profile-info">
        <span class="nav-profile-name">
          <?php echo htmlspecialchars($user['nombre']); ?>
        </span>
        <span class="nav-profile-role">Estudiante · PractiMach</span>
      </div>
    </div>
  </div>
</nav>

<!-- CONTENIDO PERFIL -->
<div class="perfil-wrapper">

  <?php if($msg): ?>
    <div class="alert">
      <span class="icon">ℹ️</span>
      <span><?php echo $msg; ?></span>
    </div>
  <?php endif; ?>

  <div class="perfil-card">

    <!-- COLUMNA IZQUIERDA (FOTO + CV + ACCIONES) -->
    <div class="foto-col">
      <div class="perfil-chip-row">
        <div class="chip-pill">
          <div class="chip-pill-avatar">
            <?php echo strtoupper(substr($user['nombre'], 0, 1)); ?>
          </div>
          <span>Perfil de estudiante</span>
        </div>
        <div class="chip-badge">Activo</div>
      </div>

      <div class="foto-wrapper">
        <div class="foto-box">
          <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto de perfil">
        </div>
      </div>

      <!-- Formulario de subida de imagen -->
      <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
        <label for="foto_perfil" class="btn-subir-foto">
          <span class="icon">📷</span>
          <span>Cambiar foto</span>
        </label>
        <input type="file" name="foto_perfil" id="foto_perfil" class="file-input" accept="image/*" onchange="this.form.submit()">
        <div class="foto-note">Formatos permitidos: JPG, PNG o WEBP.</div>
      </form>

      <!-- CARD CV -->
      <div class="cv-card">
        <div class="cv-header">
          <div class="cv-title">
            <span class="icon">📄</span>
            <span>Currículum (CV)</span>
          </div>
          <?php if ($cv_path): ?>
            <div class="cv-status">CV cargado</div>
          <?php else: ?>
            <div class="cv-status empty">Sin CV</div>
          <?php endif; ?>
        </div>

        <div class="cv-actions">
          <!-- Formulario para subir CV -->
          <form action="" method="POST" enctype="multipart/form-data">
            <label for="cv_archivo" class="btn-subir-cv">
              <span class="icon">⬆️</span>
              <span><?php echo $cv_path ? 'Actualizar CV' : 'Subir CV'; ?></span>
            </label>
            <input
              type="file"
              name="cv_archivo"
              id="cv_archivo"
              class="file-input"
              accept=".pdf,.doc,.docx,.odt"
              onchange="this.form.submit()"
            >
          </form>

          <?php if ($cv_path): ?>
            <a href="<?php echo htmlspecialchars($cv_path); ?>" target="_blank" class="cv-link">
              Ver CV actual
            </a>
          <?php else: ?>
            <span style="font-size:.74rem; color:#6b7280;">
              Sube tu CV en PDF o Word para que las empresas puedan descargarlo.
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- COLUMNA DERECHA (INFO) -->
    <div class="perfil-info">
      <div class="perfil-header">
        <h1><?php echo htmlspecialchars($user['nombre']); ?></h1>
        <div class="perfil-sub-row">
          <p class="sub-text">
            Estudiante de <?php echo htmlspecialchars($user['carrera']); ?>
          </p>
          <span class="badge-carrera">
            🎓 <?php echo htmlspecialchars($user['carrera']); ?>
          </span>
        </div>
      </div>

      <!-- INFO ESTRUCTURADA -->
      <div class="section-title">
        Información académica <span>· Datos principales</span>
      </div>
      <div class="data-box">
        <div class="data-box-key">Carrera</div>
        <div class="data-box-value">
          <?php echo htmlspecialchars($user['carrera']); ?>
        </div>
      </div>

      <div class="section-title">
        Identificación <span>· Documentos</span>
      </div>
      <div class="data-box">
        <div class="data-box-key">DNI</div>
        <div class="data-box-value">
          <?php echo htmlspecialchars($user['dni']); ?>
        </div>
      </div>

      <div class="section-title">
        Contacto <span>· Datos de contacto</span>
      </div>
      <div class="data-box">
        <div class="data-box-key">Correo</div>
        <div class="data-box-value">
          <?php echo htmlspecialchars($user['email']); ?>
        </div>
      </div>
      <div class="data-box">
        <div class="data-box-key">Teléfono</div>
        <div class="data-box-value">
          <?php
            if ($telefono !== '') {
                echo htmlspecialchars($telefono);
            } else {
                echo '<span style="color:#9ca3af;">Sin registrar</span>';
            }
          ?>
        </div>
      </div>

      <div class="section-title">
        Descripción personal <span>· Preséntate a las empresas</span>
      </div>
      <div class="data-box-full">
        <?php 
          $descripcion = trim($user['descripcion']);
          if ($descripcion === '') {
              echo '<span style="color:#9ca3af;font-size:0.9rem;">Aún no has agregado una descripción. Usa el botón "Editar perfil" para contarle a las empresas quién eres, tus intereses y tus fortalezas.</span>';
          } else {
              echo nl2br(htmlspecialchars($descripcion));
          }
        ?>
      </div>

      <button class="btn-editar" onclick="location.href='perfil_estudiante_editar.php'">
        <span class="icon">✏️</span>
        <span>Editar perfil</span>
      </button>
    </div>

  </div>

</div>

</body>
</html>
