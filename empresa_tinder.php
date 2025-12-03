<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'config/config/conexion.php';

// Verificar sesión y rol de empresa
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'empresa') {
    header("Location: auth.php"); // Redirigir a login si no es empresa
    exit;
}

$empresa_id = $_SESSION['user_id'];

// Datos de la empresa para navbar
$stmt_empresa = $mysqli->prepare("SELECT razon_social, foto FROM empresas WHERE id = ?");
$stmt_empresa->bind_param("i", $empresa_id);
$stmt_empresa->execute();
$res_empresa = $stmt_empresa->get_result();
$empresa_data = $res_empresa->fetch_assoc();

$empresa_foto_path = "assets/img/default-company.png";
if (!empty($empresa_data['foto']) && file_exists("assets/uploads/" . $empresa_data['foto'])) {
    $empresa_foto_path = "assets/uploads/" . $empresa_data['foto'];
}

// Obtener estudiantes disponibles para swipe
// Excluye los que la empresa ya rechazó, dio like o tienen match.
// INCLUYE estudiantes con estado 'estudiante_gusta' (esperando respuesta de la empresa)
$stmt = $mysqli->prepare("
    SELECT id, nombre, carrera, foto, descripcion 
    FROM estudiantes 
    WHERE id NOT IN (
        SELECT estudiante_id 
        FROM matches 
        WHERE empresa_id = ? 
        AND estado IN ('empresa_gusta', 'match', 'rechazado')
    )
    LIMIT 10
");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$estudiantes_result = $stmt->get_result();

$estudiantes_data = [];
while($estudiante = $estudiantes_result->fetch_assoc()) {
    $foto_path = "assets/img/default-user.png"; // Imagen por defecto
    if (!empty($estudiante['foto']) && file_exists("assets/uploads/" . $estudiante['foto'])) {
        $foto_path = "assets/uploads/" . $estudiante['foto'];
    }
    $estudiante['foto_url'] = $foto_path;
    $estudiantes_data[] = $estudiante;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PractiMach | Buscar Estudiantes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --pm-primary: #4f46e5;
      --pm-bg: #020617;
      --pm-card-bg: #ffffff;
      --pm-border: #e5e7eb;
      --pm-radius-lg: 22px;
      --pm-shadow-soft: 0 22px 55px rgba(15, 23, 42, 0.45);
    }

    /* ---------------------- GENERAL ---------------------- */
    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      font-family: "Poppins", sans-serif;
      overflow-x: hidden;
      background:
        radial-gradient(circle at 0% 0%, rgba(129,140,248,0.18), transparent 52%),
        radial-gradient(circle at 100% 0%, rgba(239,68,68,0.15), transparent 55%),
        #020617;
      color: #f9fafb;
    }

    /* ---------------------- NAVBAR SUPERIOR EMPRESA ---------------------- */
    .navbar {
      width: 100%;
      position: sticky;
      top: 0;
      z-index: 80;
      backdrop-filter: blur(14px);
      background: linear-gradient(to right, rgba(15,23,42,0.96), rgba(15,23,42,0.9));
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

    /* ---------------------- CONTENEDOR PRINCIPAL ---------------------- */
    .main-wrapper {
      max-width: 1120px;
      margin: 2.1rem auto 3.5rem;
      padding: 0 1.5rem 3.5rem 1.5rem;
    }

    .page-header {
      margin-bottom: 1.6rem;
      color: #f9fafb;
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      align-items: flex-end;
    }

    .page-title {
      font-size: 1.8rem;
      margin: 0 0 .3rem 0;
      font-weight: 600;
    }

    .page-subtitle {
      font-size: .95rem;
      color: #cbd5f5;
      max-width: 460px;
    }

    .header-pill {
      font-size: .8rem;
      padding: .25rem .7rem;
      border-radius: 999px;
      border: 1px solid rgba(191,219,254,0.8);
      background: rgba(15,23,42,0.85);
      color: #e5e7eb;
      white-space: nowrap;
    }

    /* ---------------------- CONTENEDOR TINDER ---------------------- */
    .tinder-shell {
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 70vh;
    }

    .tinder-container {
      width: 100%;
      max-width: 460px;
      height: 80vh;
      position: relative;
    }

    .tinder-card {
      width: 100%;
      background: var(--pm-card-bg);
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
      border: 1px solid rgba(226,232,240,0.95);
      min-height: 72vh;
    }

    /* ---------------------- HEADER IMAGEN ---------------------- */
    .card-header {
      position: relative;
      height: 320px;
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
      padding: .9rem 1.2rem 1rem;
      background: linear-gradient(to top, rgba(15,23,42,0.9), transparent 80%);
      color: #f9fafb;
    }

    .card-overlay h2 {
      margin: 0 0 .15rem 0;
      font-size: 1.45rem;
    }

    .overlay-pill {
      display: inline-block;
      padding: .22rem .7rem;
      border-radius: 999px;
      border: 1px solid rgba(248,250,252,0.85);
      font-size: .78rem;
      background: rgba(15,23,42,0.65);
    }

    /* ---------------------- CUERPO CARD ---------------------- */
    .card-body {
      padding: .9rem 1.3rem 0;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: .35rem;
      font-size: .9rem;
      color: #374151;
    }

    .info-row {
      font-size: .9rem;
      color: #374151;
    }

    .info-row strong {
      font-weight: 600;
      color: #111827;
    }

    .description-box {
      margin-top: .3rem;
      padding: .55rem .7rem;
      border-radius: 12px;
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      max-height: 120px;
      overflow-y: auto;
      font-size: .88rem;
    }

    /* ---------------------- BOTONES SWIPE ---------------------- */
    .tinder-bottom {
      margin-top: .9rem;
      padding: 0 0 1.2rem 0;
      display: flex;
      justify-content: center;
      gap: 3.6rem;
    }

    .btn-circle {
      width: 74px;
      height: 74px;
      border-radius: 50%;
      border: none;
      background: white;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 1.9rem;
      cursor: pointer;
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.4);
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-circle:hover {
      transform: scale(1.1);
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.5);
    }

    .btn-no {
      color: #e50914;
    }

    .btn-like {
      color: #00c853;
    }

    /* ---------------------- EMPTY STATE ---------------------- */
    .empty-card {
      position: relative;
      text-align: center;
      padding: 2rem 1.5rem;
      top: 0;
      left: 0;
      transform: none;
      background: rgba(15,23,42,0.98);
      color: #e5e7eb;
      border-radius: var(--pm-radius-lg);
      border: 1px solid rgba(148,163,184,0.5);
      box-shadow: var(--pm-shadow-soft);
    }

    .empty-card p {
      margin: .2rem 0;
      font-size: .9rem;
      color: #cbd5f5;
    }

    /* ---------------------- MENÚ INFERIOR MÓVIL ---------------------- */
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

    /* ---------------------- RESPONSIVE ---------------------- */
    @media (max-width: 900px) {
      .nav-inner {
        padding-inline: 1rem;
      }
      .main-wrapper {
        padding-inline: 1rem;
      }
    }

    @media (max-width: 860px) {
      .nav-links {
        display: none;
      }
    }

    @media (max-width: 768px) {
      .tinder-container {
        max-width: 380px;
        height: 78vh;
      }
      .card-header {
        height: 280px;
      }
      .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: .6rem;
      }
      .header-pill {
        align-self: flex-start;
      }
    }

    @media (min-width: 801px) {
      .bottom-menu {
        display: none;
      }
    }
  </style>
</head>

<body>

  <!-- NAVBAR EMPRESA -->
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
          <a href="empresa_tinder.php" class="active">
            <span class="dot"></span>Buscar talentos
          </a>
          <a href="matches_empresa.php">
            <span class="dot"></span>Matches
          </a>
          <a href="historial_likes_empresa.php">
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

  <!-- CONTENIDO PRINCIPAL -->
  <div class="main-wrapper">

    <div class="page-header">
      <div>
        <h1 class="page-title">Buscar Estudiantes</h1>
        <div class="page-subtitle">
          Desliza para descubrir perfiles de estudiantes que podrían encajar con tu empresa.
          Marca ❤️ para mostrar interés o ❌ para descartar.
        </div>
      </div>
      <div class="header-pill">
        🎯 Talento joven para tus prácticas
      </div>
    </div>

    <div class="tinder-shell">
      <div class="tinder-container">
        <?php if (empty($estudiantes_data)): ?>
          <div class="tinder-card empty-card">
            <div style="font-size:2.2rem; margin-bottom:.4rem;">😌</div>
            <h2 style="margin-bottom:.4rem;">No hay estudiantes disponibles por ahora.</h2>
            <p>Vuelve más tarde o revisa nuevamente tu panel.</p>
          </div>
        <?php else: ?>
          <?php foreach (array_reverse($estudiantes_data) as $index => $estudiante): ?>
            <div class="tinder-card"
                 id="est_<?php echo $estudiante['id']; ?>"
                 style="z-index: <?php echo count($estudiantes_data) - $index; ?>">
              <div class="card-header">
                <img src="<?php echo htmlspecialchars($estudiante['foto_url']); ?>"
                     alt="<?php echo htmlspecialchars($estudiante['nombre']); ?>" />
                <div class="card-overlay">
                  <h2><?php echo htmlspecialchars($estudiante['nombre']); ?></h2>
                  <span class="overlay-pill"><?php echo htmlspecialchars($estudiante['carrera']); ?></span>
                </div>
              </div>

              <div class="card-body">
                <p class="info-row">
                  <strong>Descripción:</strong>
                </p>
                <div class="description-box">
                  <?php echo nl2br(htmlspecialchars($estudiante['descripcion'])); ?>
                </div>
              </div>

              <div class="tinder-bottom">
                <button class="btn-circle btn-no"
                        onclick="swipe('est_<?php echo $estudiante['id']; ?>', <?php echo $estudiante['id']; ?>, 'reject')">
                  ❌
                </button>
                <button class="btn-circle btn-like"
                        onclick="swipe('est_<?php echo $estudiante['id']; ?>', <?php echo $estudiante['id']; ?>, 'like')">
                  ❤️
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
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

  <script>
    async function swipe(cardId, estudianteId, action) {
      const card = document.getElementById(cardId);

      if (!card) return;

      // Animación de swipe
      if (action === 'reject') {
          card.style.transform = "translateX(-500px) rotate(-15deg)";
      } else {
          card.style.transform = "translateX(500px) rotate(15deg)";
      }

      // Eliminar tarjeta después de la animación
      setTimeout(async () => {
          card.remove();

          // Enviar acción al backend
          const response = await fetch('process_swipe.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                  estudiante_id: estudianteId,
                  action: action
              })
          });

          let result;
          try {
              result = await response.json();
          } catch (e) {
              alert('Error al procesar la acción en el servidor.');
              return;
          }

          if (!result.success) {
              alert('Error al procesar la acción: ' + result.message);
          }

          // Si no hay más tarjetas, recargar
          if (document.querySelectorAll('.tinder-card').length === 0) {
              setTimeout(() => {
                  window.location.reload();
              }, 500);
          }
      }, 250); // Duración de la animación
    }
  </script>

</body>
</html>
