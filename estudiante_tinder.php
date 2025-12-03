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

// Obtener datos del estudiante para navbar
$stmt_estudiante = $mysqli->prepare("SELECT nombre, foto FROM estudiantes WHERE id = ?");
$stmt_estudiante->bind_param("i", $estudiante_id);
$stmt_estudiante->execute();
$res_estudiante = $stmt_estudiante->get_result();
$estudiante_data = $res_estudiante->fetch_assoc();

$estudiante_foto_path = "assets/img/default-user.png";
if (!empty($estudiante_data['foto']) && file_exists("assets/uploads/" . $estudiante_data['foto'])) {
    $estudiante_foto_path = "assets/uploads/" . $estudiante_data['foto'];
}

// Obtener empresas disponibles para swipe
// Excluye solo las que el estudiante ya rechazó, le dio like o ya tienen match
// INCLUYE empresas con estado 'empresa_gusta' (esperando respuesta del estudiante)
$stmt = $mysqli->prepare("
    SELECT id, razon_social, sector, foto, descripcion 
    FROM empresas 
    WHERE id NOT IN (
        SELECT empresa_id 
        FROM matches 
        WHERE estudiante_id = ? 
        AND estado IN ('estudiante_gusta', 'match', 'rechazado')
    )
    LIMIT 10
");
$stmt->bind_param("i", $estudiante_id);
$stmt->execute();
$empresas_result = $stmt->get_result();

$empresas_data = [];
while($empresa = $empresas_result->fetch_assoc()) {
    $foto_path = "assets/img/default-company.png"; // Imagen por defecto
    if (!empty($empresa['foto']) && file_exists("assets/uploads/" . $empresa['foto'])) {
        $foto_path = "assets/uploads/" . $empresa['foto'];
    }
    $empresa['foto_url'] = $foto_path;
    $empresas_data[] = $empresa;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PractiMach | Buscar Empresas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    /* RESET */
    *, *::before, *::after {
      box-sizing: border-box;
    }

    :root {
      --pm-primary: #4f46e5;
      --pm-bg: #020617;
      --pm-card-bg: #ffffff;
      --pm-radius-lg: 24px;
      --pm-shadow-soft: 0 22px 60px rgba(15, 23, 42, 0.40);
    }

    /* GENERAL */
    body {
      margin: 0;
      background: radial-gradient(circle at top left, #1d4ed8 0, #020617 52%, #020617 100%);
      font-family: "Poppins", sans-serif;
      overflow-x: hidden;
      min-height: 100vh;
      color: #0f172a;
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

    /* CONTENEDOR GENERAL */
    .tinder-wrapper {
      max-width: 1120px;
      margin: 1.8rem auto 3.5rem;
      padding: 0 1.5rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1.2rem;
    }

    .page-header {
      width: 100%;
      max-width: 1120px;
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      color: #f9fafb;
      margin-bottom: .5rem;
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

    /* CONTENEDOR TINDER */
    .tinder-container {
      width: 100%;
      max-width: 460px;
      height: 82vh;
      position: relative;
    }

    .tinder-card {
      width: 100%;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(18px);
      border-radius: var(--pm-radius-lg);
      box-shadow: var(--pm-shadow-soft);
      position: absolute;
      top: 0;
      left: 0;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.25s ease-out;
      border: 1px solid rgba(226,232,240,0.98);
      min-height: 74vh;
    }

    /* HEADER IMAGEN */
    .card-header {
      position: relative;
      height: 330px;
      overflow: hidden;
    }
    .card-header img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .card-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: .9rem 1.2rem;
      background: linear-gradient(to top, rgba(15,23,42,0.9), transparent);
      color: #fff;
    }
    .card-overlay h2 {
      margin: 0 0 .15rem 0;
      font-size: 1.4rem;
    }
    .overlay-pill {
      display: inline-block;
      padding: .2rem .65rem;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.75);
      font-size: .78rem;
      background: rgba(15,23,42,0.35);
    }

    /* BODY */
    .card-body {
      padding: .9rem 1.3rem 0;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: .3rem;
      font-size: .9rem;
      color: #374151;
    }
    .info-row {
      font-size: .9rem;
      color: #444;
    }
    .info-row strong {
      font-weight: 600;
      color: #111827;
    }

    /* MENSAJE SIN EMPRESAS */
    .empty-card {
      position: relative;
      text-align: center;
      padding: 2rem 1.5rem;
      background: rgba(248,250,252,0.98);
      border-radius: var(--pm-radius-lg);
      border: 1px solid rgba(226,232,240,0.98);
      color: #0f172a;
      min-height: auto;
      box-shadow: var(--pm-shadow-soft);
    }
    .empty-card h2 {
      margin-top: .2rem;
      margin-bottom: .4rem;
    }
    .empty-card p {
      margin: 0;
      font-size: .9rem;
      color: #6b7280;
    }

    /* BOTONES SWIPE */
    .tinder-bottom {
      margin-top: .8rem;
      padding: 0 0 1.2rem 0;
      display: flex;
      justify-content: center;
      gap: 3.5rem;
    }
    .btn-circle {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      border: none;
      background: #f9fafb;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 1.9rem;
      cursor: pointer;
      box-shadow: 0 10px 24px rgba(15,23,42,0.35);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-circle:hover {
      transform: scale(1.08);
      box-shadow: 0 14px 30px rgba(15,23,42,0.5);
    }
    .btn-no {
      color: #ef4444;
    }
    .btn-like {
      color: #22c55e;
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
      .tinder-wrapper {
        padding-inline: 1rem;
        margin-top: 1.4rem;
      }
      .page-header {
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
    @media (max-width: 800px) {
      .tinder-container {
        max-width: 380px;
        height: 78vh;
      }
      .card-header {
        height: 280px;
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
          <small>Panel estudiante</small>
        </div>
      </div>
      <div class="nav-links">
        <a href="estudiante_tinder.php" class="active">
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
<div class="tinder-wrapper">

  <div class="page-header">
    <div>
      <div class="page-title">Buscar empresas 🔥</div>
      <div class="page-subtitle">
        Desliza para indicar qué empresas te interesan. Si ellas también te eligen, se creará un match.
      </div>
    </div>
    <div class="page-pill">
      👈 No me interesa &nbsp;·&nbsp; Me interesa 👉
    </div>
  </div>

  <div class="tinder-container">
    <?php if (empty($empresas_data)): ?>
      <div class="tinder-card empty-card" style="position:relative; top:0; left:0; transform:none;">
        <div style="font-size:2rem;">😅</div>
        <h2>No hay empresas disponibles por ahora.</h2>
        <p>Vuelve más tarde, pueden registrarse nuevas empresas en cualquier momento.</p>
      </div>
    <?php else: ?>
      <?php foreach (array_reverse($empresas_data) as $index => $empresa): ?>
        <div
          class="tinder-card"
          id="emp_<?php echo $empresa['id']; ?>"
          style="z-index: <?php echo count($empresas_data) - $index; ?>"
        >
          <div class="card-header">
            <img src="<?php echo htmlspecialchars($empresa['foto_url']); ?>" alt="<?php echo htmlspecialchars($empresa['razon_social']); ?>" />
            <div class="card-overlay">
              <h2><?php echo htmlspecialchars($empresa['razon_social']); ?></h2>
              <span class="overlay-pill"><?php echo htmlspecialchars($empresa['sector']); ?></span>
            </div>
          </div>

          <div class="card-body">
            <p class="info-row">
              <strong>Descripción:</strong>
              <?php echo htmlspecialchars($empresa['descripcion']); ?>
            </p>
          </div>

          <div class="tinder-bottom">
            <button
              class="btn-circle btn-no"
              onclick="swipe('emp_<?php echo $empresa['id']; ?>', <?php echo $empresa['id']; ?>, 'reject')"
            >
              ❌
            </button>
            <button
              class="btn-circle btn-like"
              onclick="swipe('emp_<?php echo $empresa['id']; ?>', <?php echo $empresa['id']; ?>, 'like')"
            >
              ❤️
            </button>
          </div>
        </div>
      <?php endforeach; ?>
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

<script>
  async function swipe(cardId, empresaId, action) {
    const card = document.getElementById(cardId);

    // Animación de swipe (igual que antes)
    if (action === 'reject') {
      card.style.transform = "translateX(-500px) rotate(-15deg)";
    } else {
      card.style.transform = "translateX(500px) rotate(15deg)";
    }

    setTimeout(async () => {
      card.remove();

      const response = await fetch('process_swipe_student.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          empresa_id: empresaId,
          action: action
        })
      });

      const result = await response.json();

      if (!result.success) {
        alert('Error al procesar la acción: ' + result.message);
      }

      // Si no hay más tarjetas, recargar o mostrar mensaje
      if (document.querySelectorAll('.tinder-card').length === 0) {
        setTimeout(() => {
          window.location.reload();
        }, 500);
      }

    }, 250);
  }
</script>

</body>
</html>
