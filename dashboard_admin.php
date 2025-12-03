<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'config/config/conexion.php';

/* ==== FILTRO POR RANGO DE FECHAS ==== 
   range = 30  -> últimos 30 días
   (cualquier otra cosa / vacío) -> todo histórico
*/
$filterRange = (isset($_GET['range']) && $_GET['range'] === '30') ? 30 : 0;

/* Helper para contar sin romper si hay error */
function getCountSafe($mysqli, $sql) {
    $res = $mysqli->query($sql);
    if ($res && ($row = $res->fetch_row())) {
        return (int)$row[0];
    }
    return 0;
}

/* ===== CONTADORES PRINCIPALES ===== */
if ($filterRange === 30) {
    // Solo últimos 30 días
    $countEstudiantes = getCountSafe(
        $mysqli,
        "SELECT COUNT(*) 
         FROM estudiantes 
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
    );

    $countEmpresas = getCountSafe(
        $mysqli,
        "SELECT COUNT(*) 
         FROM empresas 
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
    );

    $countMatchesActivos = getCountSafe(
        $mysqli,
        "SELECT COUNT(*) 
         FROM matches 
         WHERE estado IN ('match','aceptado')
           AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
    );
} else {
    // Todo histórico
    $countEstudiantes = getCountSafe($mysqli, "SELECT COUNT(*) FROM estudiantes");
    $countEmpresas    = getCountSafe($mysqli, "SELECT COUNT(*) FROM empresas");
    $countMatchesActivos = getCountSafe(
        $mysqli,
        "SELECT COUNT(*) FROM matches WHERE estado IN ('match','aceptado')"
    );
}

/* Empresas en revisión (solo si existe la columna estado) */
$countEmpresasRevision = 0;
$colEstado = $mysqli->query("SHOW COLUMNS FROM empresas LIKE 'estado'");
if ($colEstado && $colEstado->num_rows > 0) {
    if ($filterRange === 30) {
        $countEmpresasRevision = getCountSafe(
            $mysqli,
            "SELECT COUNT(*) 
             FROM empresas 
             WHERE estado = 'revision'
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
    } else {
        $countEmpresasRevision = getCountSafe(
            $mysqli,
            "SELECT COUNT(*) 
             FROM empresas 
             WHERE estado = 'revision'"
        );
    }
}

/* ===== ÚLTIMOS ESTUDIANTES ===== */
if ($filterRange === 30) {
    $latestEstudiantes = $mysqli->query(
        "SELECT nombre, carrera 
         FROM estudiantes 
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         ORDER BY created_at DESC 
         LIMIT 3"
    );
} else {
    $latestEstudiantes = $mysqli->query(
        "SELECT nombre, carrera 
         FROM estudiantes 
         ORDER BY created_at DESC 
         LIMIT 3"
    );
}

/* ===== ÚLTIMAS EMPRESAS ===== */
if ($filterRange === 30) {
    $latestEmpresas = $mysqli->query(
        "SELECT razon_social, sector 
         FROM empresas 
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         ORDER BY created_at DESC 
         LIMIT 3"
    );
} else {
    $latestEmpresas = $mysqli->query(
        "SELECT razon_social, sector 
         FROM empresas 
         ORDER BY created_at DESC 
         LIMIT 3"
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard Admin | PractiMach</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* RESET */
*,
*::before,
*::after {
  box-sizing: border-box;
}

/* GENERAL */
body {
  margin: 0;
  font-family: "Poppins", sans-serif;
  background: #f3f4f6;
  color: #111827;
}

/* LAYOUT GENERAL */
.admin-layout {
  display: flex;
  min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
  width: 240px;
  background: #0f172a;
  color: #e5e7eb;
  display: flex;
  flex-direction: column;
  padding: 1rem 1rem 1.5rem 1rem;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .6rem .4rem 1rem .4rem;
  border-bottom: 1px solid rgba(55,65,81,0.7);
  margin-bottom: 1rem;
}

.sidebar-logo span {
  font-size: 1.15rem;
  font-weight: 600;
}

.sidebar-logo-badge {
  font-size: .75rem;
  background: #e50914;
  padding: .1rem .45rem;
  border-radius: 999px;
  font-weight: 500;
}

.sidebar-menu {
  flex: 1;
}

.sidebar-section-title {
  font-size: .75rem;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #9ca3af;
  padding: .4rem .6rem;
  margin-top: .4rem;
  margin-bottom: .4rem;
}

.sidebar-link {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .55rem .7rem;
  border-radius: .7rem;
  cursor: pointer;
  font-size: .9rem;
  color: #e5e7eb;
  text-decoration: none;
  transition: background .2s, color .2s, transform .1s;
}

.sidebar-link span.icon {
  font-size: 1.1rem;
}

.sidebar-link:hover {
  background: #1f2937;
  transform: translateX(2px);
}

.sidebar-link.active {
  background: linear-gradient(135deg, #e50914, #003590);
  color: #fff;
}

/* FOOTER SIDEBAR (INFO ADMIN) */
.sidebar-footer {
  border-top: 1px solid rgba(55,65,81,0.7);
  padding-top: .8rem;
  margin-top: .5rem;
  display: flex;
  align-items: center;
  gap: .6rem;
}

.sidebar-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #003590;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: .9rem;
  font-weight: 600;
  color: #fff;
}

.sidebar-footer-text {
  font-size: .8rem;
}

.sidebar-footer-text strong {
  display: block;
}

/* MAIN AREA */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f3f4f6;
}

/* TOPBAR */
.topbar {
  background: #ffffff;
  border-bottom: 1px solid #e5e7eb;
  padding: .8rem 1.8rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.topbar-title {
  display: flex;
  flex-direction: column;
  gap: .1rem;
}

.topbar-title h1 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
}

.topbar-title span {
  font-size: .85rem;
  color: #6b7280;
}

.topbar-actions {
  display: flex;
  gap: .75rem;
  flex-wrap: wrap;
}

.btn-chip,
.btn-primary {
  border-radius: 999px;
  border: 1px solid #d1d5db;
  padding: .45rem .9rem;
  font-size: .85rem;
  cursor: pointer;
  background: #fff;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  text-decoration: none;
  color: #111827;
}

.btn-primary {
  background: #003590;
  color: #fff;
  border-color: #003590;
}

/* Chip activo */
.btn-chip-active {
  border-color: #003590;
  color: #003590;
  background: #e0ecff;
}

/* CONTENIDO GENERAL */
.content {
  max-width: 1100px;
  margin: 1.8rem auto 2.2rem auto;
  padding: 0 1.5rem;
}

/* CARDS RESUMEN */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.2rem;
  margin-bottom: 2rem;
}

.card-resumen {
  background: #ffffff;
  border-radius: 16px;
  padding: 1rem 1.1rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 8px 20px rgba(15,23,42,0.04);
  display: flex;
  flex-direction: column;
  gap: .35rem;
}

.card-label {
  font-size: .8rem;
  color: #6b7280;
}

.card-number-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.card-number {
  font-size: 1.5rem;
  font-weight: 600;
}

.card-extra {
  font-size: .8rem;
  color: #9ca3af;
}

.card-pill {
  font-size: .8rem;
  padding: .15rem .5rem;
  border-radius: 999px;
  display: inline-block;
  margin-top: .1rem;
}

.card-pill.green {
  background: #dcfce7;
  color: #166534;
}

.card-pill.red {
  background: #fee2e2;
  color: #b91c1c;
}

.card-pill.yellow {
  background: #fef3c7;
  color: #92400e;
}

/* SECCIÓN INFERIOR */
.bottom-grid {
  display: grid;
  grid-template-columns: 2fr 2fr;
  gap: 1.5rem;
}

/* BLOQUES TABLA */
.block {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 8px 20px rgba(15,23,42,0.04);
  padding: 1.1rem 1.2rem;
}

.block-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: .8rem;
}

.block-header h2 {
  margin: 0;
  font-size: 1.05rem;
}

.block-header span {
  font-size: .8rem;
  color: #6b7280;
}

.block-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .8rem;
}

.block-table thead {
  background: #f3f4f6;
}

.block-table th,
.block-table td {
  padding: .5rem .35rem;
  text-align: left;
}

.block-table th {
  font-weight: 600;
  color: #4b5563;
}

.block-table tr:nth-child(even) td {
  background: #f9fafb;
}

.badge-estado {
  font-size: .75rem;
  padding: .12rem .5rem;
  border-radius: 999px;
}

.badge-activo {
  background: #dcfce7;
  color: #166534;
}

.badge-pendiente {
  background: #fef3c7;
  color: #92400e;
}

/* RESPONSIVE */
@media (max-width: 1024px) {
  .cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 850px) {
  .admin-layout {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
    flex-direction: row;
    align-items: flex-start;
    overflow-x: auto;
    padding-bottom: .8rem;
  }

  .sidebar-logo {
    border-bottom: none;
    border-right: 1px solid rgba(55,65,81,0.7);
    margin-bottom: 0;
    padding-right: 1rem;
    margin-right: .5rem;
  }

  .sidebar-menu {
    display: flex;
    align-items: center;
  }

  .sidebar-section-title {
    display: none;
  }

  .sidebar-link {
    white-space: nowrap;
  }

  .sidebar-footer {
    display: none;
  }

  .content {
    margin-top: 1rem;
  }
}

@media (max-width: 700px) {
  .cards-grid {
    grid-template-columns: 1fr;
  }

  .bottom-grid {
    grid-template-columns: 1fr;
  }
}
</style>
</head>

<body>

<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <span>PractiMach</span>
      <span class="sidebar-logo-badge">Admin</span>
    </div>

    <div class="sidebar-menu">
      <div class="sidebar-section-title">General</div>
      <a href="dashboard_admin.php" class="sidebar-link active">
        <span class="icon">📊</span>
        <span>Dashboard</span>
      </a>
      <a href="admin_estudiantes.php" class="sidebar-link">
        <span class="icon">🧑‍🎓</span>
        <span>Estudiantes</span>
      </a>
      <a href="admin_empresas.php" class="sidebar-link">
        <span class="icon">🏢</span>
        <span>Empresas</span>
      </a>
      <a href="admin_matches.php" class="sidebar-link">
        <span class="icon">❤️</span>
        <span>Matches</span>
      </a>

      <div class="sidebar-section-title">Sistema</div>
      <a href="admin_config.php" class="sidebar-link">
        <span class="icon">⚙️</span>
        <span>Configuración</span>
      </a>
      <a href="logout.php" class="sidebar-link">
        <span class="icon">🚪</span>
        <span>Salir</span>
      </a>
    </div>

    <div class="sidebar-footer">
      <div class="sidebar-avatar">A</div>
      <div class="sidebar-footer-text">
        <strong>Admin</strong>
        <span>Panel de control</span>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-title">
        <h1>Dashboard general</h1>
        <span>Resumen de estudiantes, empresas y matches dentro de PractiMach.</span>
      </div>

      <div class="topbar-actions">
        <!-- Filtro últimos 30 días -->
        <a href="dashboard_admin.php?range=30"
           class="btn-chip<?php echo ($filterRange === 30) ? ' btn-chip-active' : ''; ?>">
          Últimos 30 días ▾
        </a>

        <!-- Filtro todo histórico -->
        <a href="dashboard_admin.php"
           class="btn-chip<?php echo ($filterRange === 0) ? ' btn-chip-active' : ''; ?>">
          Todos los programas ▾
        </a>

        <button class="btn-primary" onclick="location.reload();">⟳ Actualizar</button>
      </div>
    </header>

    <!-- CONTENIDO -->
    <main class="content">

      <!-- CARDS RESUMEN -->
      <section class="cards-grid">
        <div class="card-resumen">
          <span class="card-label">Estudiantes registrados</span>
          <div class="card-number-row">
            <span class="card-number"><?php echo $countEstudiantes; ?></span>
            <span class="card-extra">+12 este mes</span>
          </div>
          <span class="card-pill green">Crecimiento estable</span>
        </div>

        <div class="card-resumen">
          <span class="card-label">Empresas registradas</span>
          <div class="card-number-row">
            <span class="card-number"><?php echo $countEmpresas; ?></span>
            <span class="card-extra">+3 nuevas</span>
          </div>
          <span class="card-pill green">Buen flujo</span>
        </div>

        <div class="card-resumen">
          <span class="card-label">Matches activos</span>
          <div class="card-number-row">
            <span class="card-number"><?php echo $countMatchesActivos; ?></span>
            <span class="card-extra">8 en proceso</span>
          </div>
          <span class="card-pill yellow">Requieren seguimiento</span>
        </div>

        <div class="card-resumen">
          <span class="card-label">Cuentas por validar</span>
          <div class="card-number-row">
            <span class="card-number"><?php echo $countEmpresasRevision; ?></span>
            <span class="card-extra">4 empresas / 5 alumnos</span>
          </div>
          <span class="card-pill red">Revisión pendiente</span>
        </div>
      </section>

      <!-- TABLAS -->
      <section class="bottom-grid">

        <!-- ÚLTIMOS ESTUDIANTES -->
        <div class="block">
          <div class="block-header">
            <h2>Últimos estudiantes</h2>
            <span>Registrados recientemente</span>
          </div>

          <table class="block-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Carrera</th>
                <th>Mód.</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($latestEstudiantes && $latestEstudiantes->num_rows > 0): ?>
                <?php while($estudiante = $latestEstudiantes->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($estudiante['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($estudiante['carrera']); ?></td>
                    <td>-</td> <!-- Módulo no está en la BD aún -->
                    <td><span class="badge-estado badge-activo">Activo</span></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No hay estudiantes registrados.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- ÚLTIMAS EMPRESAS -->
        <div class="block">
          <div class="block-header">
            <h2>Últimas empresas</h2>
            <span>En proceso de vinculación</span>
          </div>

          <table class="block-table">
            <thead>
              <tr>
                <th>Empresa</th>
                <th>Sector</th>
                <th>Ciudad</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($latestEmpresas && $latestEmpresas->num_rows > 0): ?>
                <?php while($empresa = $latestEmpresas->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($empresa['razon_social']); ?></td>
                    <td><?php echo htmlspecialchars($empresa['sector']); ?></td>
                    <td>-</td> <!-- Ciudad no está en la BD aún -->
                    <td><span class="badge-estado badge-activo">Activo</span></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No hay empresas registradas.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </section>

    </main>
  </div>
</div>

</body>
</html>
