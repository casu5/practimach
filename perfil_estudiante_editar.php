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
$error = "";

// --- LÓGICA DE ACTUALIZACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = $mysqli->real_escape_string($_POST['nombre'] ?? '');
    $dni         = $mysqli->real_escape_string($_POST['dni'] ?? '');
    $carrera     = $mysqli->real_escape_string($_POST['carrera'] ?? '');
    $email       = $mysqli->real_escape_string($_POST['email'] ?? '');
    $telefono    = $mysqli->real_escape_string($_POST['telefono'] ?? '');
    $descripcion = $mysqli->real_escape_string($_POST['descripcion'] ?? '');

    // Validación básica
    if (empty($nombre) || empty($dni) || empty($carrera) || empty($email) || empty($telefono)) {
        $error = "Todos los campos obligatorios deben ser llenados (incluido el teléfono).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    } else {
        // Verificar DNI y Email duplicados (excluyendo el propio usuario)
        $stmt_check = $mysqli->prepare("SELECT id FROM estudiantes WHERE (dni = ? OR email = ?) AND id != ?");
        $stmt_check->bind_param("ssi", $dni, $email, $user_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($res_check->num_rows > 0) {
            $error = "El DNI o Correo ya están registrados por otro usuario.";
        } else {
            $stmt = $mysqli->prepare("
                UPDATE estudiantes 
                SET nombre = ?, dni = ?, carrera = ?, email = ?, descripcion = ?, telefono = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssssi", $nombre, $dni, $carrera, $email, $descripcion, $telefono, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $nombre; // Actualizar nombre en sesión
                $msg = "Perfil actualizado correctamente.";
                header("Location: perfil_estudiante.php?msg=" . urlencode($msg));
                exit;
            } else {
                $error = "Error al actualizar el perfil: " . $mysqli->error;
            }
        }
    }
}

// --- OBTENER DATOS DEL ESTUDIANTE ---
$stmt = $mysqli->prepare("SELECT * FROM estudiantes WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    header("Location: auth.php");
    exit;
}

// Foto para navbar
$estudiante_foto_path = "assets/img/default-user.png"; 
if (!empty($user['foto']) && file_exists("assets/uploads/" . $user['foto'])) {
    $estudiante_foto_path = "assets/uploads/" . $user['foto'];
}

// Lista de carreras (debe coincidir con auth.php)
$carreras = [
    "ADG – Asistencia de Dirección y Gerencia",
    "APSTI – Arquitectura de Plataformas y Servicios de Tecnologías de Información",
    "CO – Contabilidad",
    "CC – Construcción Civil",
    "PA – Producción Agropecuaria",
    "EI – Electricidad Industrial",
    "EO – Electrónica Industrial",
    "MPI – Mecánica de Producción Industrial",
    "MA – Mecatrónica Automotriz"
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Perfil | PractiMach</title>
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
  --pm-primary-dark: #3730a3;
  --pm-bg: #020617;
  --pm-card-bg: #ffffff;
  --pm-border: #e5e7eb;
  --pm-muted: #6b7280;
  --pm-text: #0f172a;
  --pm-radius-lg: 20px;
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

/* NAVBAR (igual que historial_likes.php estudiante) */
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
.edit-wrapper {
  max-width: 1120px;
  margin: 1.8rem auto 3.5rem;
  padding: 0 1.5rem 3.5rem 1.5rem;
}
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

/* CARD FORM */
.edit-card {
  background: var(--pm-card-bg);
  border-radius: var(--pm-radius-lg);
  box-shadow: var(--pm-shadow-soft);
  padding: 1.6rem 1.5rem 1.5rem;
  border: 1px solid rgba(226,232,240,0.95);
  position: relative;
  overflow: hidden;
}
.edit-card::before {
  content: "";
  position: absolute;
  inset: -40%;
  background: radial-gradient(circle at 0 0, rgba(59,130,246,0.15), transparent 60%),
              radial-gradient(circle at 100% 100%, rgba(251,191,36,0.18), transparent 50%);
  opacity: 0.9;
  pointer-events: none;
}
.edit-inner {
  position: relative;
  z-index: 1;
}

/* ALERTAS */
.alert {
  padding: .75rem 1rem;
  border-radius: 10px;
  margin-bottom: 1rem;
  text-align: center;
  font-size: .88rem;
}
.alert-error {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}
.alert-success {
  background: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}

/* FORM */
.form-edit .form-group {
  margin-bottom: .9rem;
}
.form-edit .form-group label {
  display: block;
  margin-bottom: .3rem;
  font-weight: 500;
  font-size: .86rem;
  color: #111827;
}
.form-edit .form-group input,
.form-edit .form-group select,
.form-edit .form-group textarea {
    width: 100%;
    padding: 0.55rem 0.7rem;
    border-radius: 10px;
    border: 1px solid rgba(209, 213, 219, 0.9);
    background-color: #f9fafb;
    font-size: 0.85rem;
    font-family: inherit;
    outline: none;
    transition: border 0.25s ease, box-shadow 0.25s ease, background 0.25s ease, transform 0.25s ease;
}
.form-edit .form-group input:focus,
.form-edit .form-group select:focus,
.form-edit .form-group textarea:focus {
    border-color: var(--pm-primary);
    background-color: #ffffff;
    box-shadow: 0 0 0 1px rgba(79, 70, 229, 0.25);
    transform: translateY(-1px);
}
.form-edit .form-group textarea {
    min-height: 100px;
    resize: vertical;
}

/* BOTÓN */
.btn-submit {
    width: 100%;
    padding: 0.8rem;
    border: none;
    border-radius: 999px;
    background: #111827;
    color: #fff;
    font-size: .95rem;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    margin-top: 1.1rem;
    box-shadow: 0 14px 30px rgba(15,23,42,0.55);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
}
.btn-submit:hover {
    background: #020617;
    transform: translateY(-1px);
    box-shadow: 0 18px 40px rgba(15,23,42,0.7);
}

/* MENÚ INFERIOR MÓVIL (igual que historial_likes.php) */
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
@media (max-width: 830px) {
  .nav-inner {
    padding-inline: 1rem;
  }
  .nav-links {
    display: none;
  }
}
@media (max-width: 640px) {
  .edit-wrapper {
    padding-inline: 1rem;
    margin-top: 1.4rem;
  }
  .page-header {
    flex-direction: column;
    align-items: flex-start;
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
      <img src="<?php echo htmlspecialchars($estudiante_foto_path); ?>" alt="<?php echo htmlspecialchars($user['nombre']); ?>">
      <div class="nav-profile-info">
        <span class="nav-profile-name">
          <?php echo htmlspecialchars($user['nombre']); ?>
        </span>
        <span class="nav-profile-role">Estudiante · PractiMach</span>
      </div>
    </div>
  </div>
</nav>

<!-- CONTENIDO -->
<div class="edit-wrapper">
  <div class="page-header">
    <div>
      <div class="page-title">Editar perfil</div>
      <div class="page-subtitle">
        Actualiza tus datos para que las empresas te conozcan mejor.
      </div>
    </div>
    <div class="page-pill">
      ✏️ Datos de estudiante
    </div>
  </div>

  <div class="edit-card">
    <div class="edit-inner">

      <?php if ($msg): ?>
        <div class="alert alert-success"><?php echo $msg; ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
      <?php endif; ?>

      <form action="" method="POST" class="form-edit">
        <div class="form-group">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" 
                   value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
        </div>

        <div class="form-group">
            <label for="dni">DNI</label>
            <input type="text" id="dni" name="dni" 
                   value="<?php echo htmlspecialchars($user['dni']); ?>" required>
        </div>

        <div class="form-group">
            <label for="carrera">Carrera</label>
            <select id="carrera" name="carrera" required>
                <?php foreach ($carreras as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>" 
                      <?php echo ($user['carrera'] == $c) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono / Celular</label>
            <input type="text" id="telefono" name="telefono"
                   value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción personal</label>
            <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($user['descripcion']); ?></textarea>
        </div>
        
        <button type="submit" class="btn-submit">
          <span>Guardar cambios</span> <span>💾</span>
        </button>
      </form>

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
