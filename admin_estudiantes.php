<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'config/config/conexion.php';

/*
 * IMPORTANTE (solo si no lo hiciste antes):
 *
 * ALTER TABLE estudiantes ADD COLUMN modulo VARCHAR(50) NULL;
 * ALTER TABLE estudiantes ADD COLUMN turno VARCHAR(50) NULL;
 * ALTER TABLE estudiantes ADD COLUMN estado ENUM('Activo','Pendiente','Bloqueado') NOT NULL DEFAULT 'Activo';
 */

// Filtros desde GET
$busqueda       = isset($_GET['q']) ? trim($_GET['q']) : '';
$filtroCarrera  = isset($_GET['carrera']) ? trim($_GET['carrera']) : '';
$filtroModulo   = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';
$filtroEstado   = isset($_GET['estado']) ? trim($_GET['estado']) : '';

// Acción ELIMINAR estudiante
if (isset($_GET['accion'], $_GET['id']) && $_GET['accion'] === 'eliminar') {
    $idEst = (int) $_GET['id'];

    if ($idEst > 0) {
        // Opcional: obtener foto / cv para borrar archivos físicos si quieres
        $stmtFiles = $mysqli->prepare("SELECT foto, cv_archivo FROM estudiantes WHERE id = ?");
        if ($stmtFiles) {
            $stmtFiles->bind_param("i", $idEst);
            $stmtFiles->execute();
            $resFiles = $stmtFiles->get_result();
            if ($rowFiles = $resFiles->fetch_assoc()) {
                // Borrar foto
                if (!empty($rowFiles['foto'])) {
                    $fotoPath = "assets/uploads/" . $rowFiles['foto'];
                    if (file_exists($fotoPath)) {
                        @unlink($fotoPath);
                    }
                }
                // Borrar CV
                if (!empty($rowFiles['cv_archivo'])) {
                    $cvPath = "assets/uploads/cv/" . $rowFiles['cv_archivo'];
                    if (file_exists($cvPath)) {
                        @unlink($cvPath);
                    }
                }
            }
            $stmtFiles->close();
        }

        // Eliminar estudiante de la BD
        if ($stmtDel = $mysqli->prepare("DELETE FROM estudiantes WHERE id = ?")) {
            $stmtDel->bind_param("i", $idEst);
            $stmtDel->execute();
            $stmtDel->close();
        }

        // Redirigir para evitar re-envío de la acción al refrescar
        header("Location: admin_estudiantes.php");
        exit;
    }
}

// Construir consulta dinámica con filtros
$sql    = "SELECT * FROM estudiantes WHERE 1=1";
$params = [];
$types  = "";

// Búsqueda por nombre / DNI / email
if ($busqueda !== '') {
    $sql .= " AND (nombre LIKE ? OR dni LIKE ? OR email LIKE ?)";
    $like = "%{$busqueda}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= "sss";
}

// Filtro por carrera
if ($filtroCarrera !== '') {
    $sql .= " AND carrera = ?";
    $params[] = $filtroCarrera;
    $types   .= "s";
}

// Filtro por módulo
if ($filtroModulo !== '') {
    $sql .= " AND modulo = ?";
    $params[] = $filtroModulo;
    $types   .= "s";
}

// Filtro por estado
if ($filtroEstado !== '') {
    $sql .= " AND estado = ?";
    $params[] = $filtroEstado;
    $types   .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_estudiantes = $result->num_rows;

// Lista de carreras (para filtro, debe coincidir con auth.php)
$carreras_filter = [
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
<title>Estudiantes | Admin PractiMach</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
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

/* FOOTER SIDEBAR */
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

/* MAIN */
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

.btn-primary {
  border-radius: 999px;
  border: 1px solid #003590;
  padding: .45rem .9rem;
  font-size: .85rem;
  cursor: pointer;
  background: #003590;
  color: #fff;
  display: flex;
  align-items: center;
  gap: .35rem;
}

.btn-primary:hover {
  background: #002b6b;
}

/* CONTENIDO */
.content {
  max-width: 1100px;
  margin: 1.8rem auto 2.2rem auto;
  padding: 0 1.5rem;
}

/* FILTROS */
.filters-row {
  display: flex;
  gap: .75rem;
  flex-wrap: wrap;
  margin-bottom: 1.2rem;
}

.input-search {
  flex: 1;
  min-width: 230px;
  border-radius: 999px;
  border: 1px solid #d1d5db;
  padding: .5rem 1rem;
  font-size: .85rem;
  outline: none;
}

.input-search:focus {
  border-color: #003590;
  box-shadow: 0 0 0 1px #00359040;
}

.select-filter {
  border-radius: 999px;
  border: 1px solid #d1d5db;
  padding: .5rem .9rem;
  font-size: .85rem;
  background: #fff;
  min-width: 150px;
}

/* BLOQUE TABLA */
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
  gap: .5rem;
  flex-wrap: wrap;
}

.block-header h2 {
  margin: 0;
  font-size: 1.05rem;
}

.block-header span {
  font-size: .8rem;
  color: #6b7280;
}

/* TABLA ESTUDIANTES */
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
  padding: .55rem .45rem;
  text-align: left;
}

.block-table th {
  font-weight: 600;
  color: #4b5563;
}

.block-table tr:nth-child(even) td {
  background: #f9fafb;
}

/* BADGES & BOTONES TABLA */
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

.badge-bloqueado {
  background: #fee2e2;
  color: #b91c1c;
}

.table-actions {
  display: flex;
  gap: .4rem;
}

.btn-table {
  border-radius: 999px;
  border: none;
  padding: .32rem .65rem;
  font-size: .75rem;
  cursor: pointer;
}

.btn-ver {
  background: #e5e7eb;
  color: #111827;
}

.btn-eliminar {
  background: #fee2e2;
  color: #b91c1c;
}

/* MODAL VER ESTUDIANTE */
.modal {
  position: fixed;
  inset: 0;
  background: rgba(15,23,42,0.45);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 999;
}

.modal.is-open {
  display: flex;
}

.modal-dialog {
  background: #ffffff;
  border-radius: 1.1rem;
  max-width: 460px;
  width: 100%;
  padding: 1.3rem 1.4rem 1.4rem;
  box-shadow: 0 18px 45px rgba(15,23,42,0.35);
  position: relative;
}

.modal-close {
  position: absolute;
  top: .6rem;
  right: .6rem;
  border: none;
  background: transparent;
  font-size: 1.3rem;
  cursor: pointer;
  color: #6b7280;
}

.modal-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: .9rem;
}

.modal-avatar {
  width: 72px;
  height: 72px;
  border-radius: 999px;
  overflow: hidden;
  background: #e5e7eb;
}

.modal-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.modal-title-block h2 {
  margin: 0;
  font-size: 1.2rem;
}

.modal-title-block p {
  margin: .1rem 0 0 0;
  font-size: .85rem;
  color: #6b7280;
}

.modal-body-section {
  margin-top: .4rem;
  font-size: .85rem;
}

.modal-body-section strong {
  display: inline-block;
  min-width: 85px;
  color: #4b5563;
}

.modal-descripcion {
  margin-top: .6rem;
  font-size: .85rem;
  color: #374151;
}

/* RESPONSIVE */
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

@media (max-width: 650px) {
  .block-table {
    font-size: .75rem;
  }

  /* Ocultamos columna "Turno" en muy pequeño para que no reviente */
  .block-table th:nth-child(5),
  .block-table td:nth-child(5) {
    display: none;
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
      <a href="dashboard_admin.php" class="sidebar-link">
        <span class="icon">📊</span>
        <span>Dashboard</span>
      </a>
      <a href="admin_estudiantes.php" class="sidebar-link active">
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
        <span>Gestión de estudiantes</span>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-title">
        <h1>Estudiantes</h1>
        <span>Gestiona los estudiantes registrados en PractiMach.</span>
      </div>

      <div class="topbar-actions">
        <button class="btn-primary" type="button" onclick="alert('Registrar estudiante desde Admin: cuando quieras lo montamos 😊');">
          + Registrar estudiante
        </button>
      </div>
    </header>

    <!-- CONTENIDO -->
    <main class="content">

      <!-- FILTROS -->
      <form method="get" class="filters-row">
        <input
          type="text"
          name="q"
          class="input-search"
          placeholder="Buscar por nombre, DNI o correo…"
          value="<?php echo htmlspecialchars($busqueda); ?>"
        >

        <select name="carrera" class="select-filter">
          <option value="">Todas las carreras</option>
          <?php foreach ($carreras_filter as $carrera): ?>
            <option
              value="<?php echo htmlspecialchars($carrera); ?>"
              <?php echo ($filtroCarrera === $carrera) ? 'selected' : ''; ?>
            >
              <?php echo htmlspecialchars($carrera); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select name="modulo" class="select-filter">
          <option value="">Módulo</option>
          <option value="Módulo I"   <?php echo ($filtroModulo === 'Módulo I')   ? 'selected' : ''; ?>>Módulo I</option>
          <option value="Módulo II"  <?php echo ($filtroModulo === 'Módulo II')  ? 'selected' : ''; ?>>Módulo II</option>
          <option value="Módulo III" <?php echo ($filtroModulo === 'Módulo III') ? 'selected' : ''; ?>>Módulo III</option>
          <option value="Módulo IV"  <?php echo ($filtroModulo === 'Módulo IV')  ? 'selected' : ''; ?>>Módulo IV</option>
        </select>

        <select name="estado" class="select-filter">
          <option value="">Estado</option>
          <option value="Activo"    <?php echo ($filtroEstado === 'Activo')    ? 'selected' : ''; ?>>Activo</option>
          <option value="Pendiente" <?php echo ($filtroEstado === 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
          <option value="Bloqueado" <?php echo ($filtroEstado === 'Bloqueado') ? 'selected' : ''; ?>>Bloqueado</option>
        </select>

        <button type="submit" class="btn-primary">Filtrar</button>
      </form>

      <!-- TABLA ESTUDIANTES -->
      <section class="block">
        <div class="block-header">
          <h2>Listado de estudiantes</h2>
          <span><?php echo $total_estudiantes; ?> estudiante(s) encontrados.</span>
        </div>

        <table class="block-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>DNI</th>
              <th>Carrera</th>
              <th>Módulo</th>
              <th>Turno</th>
              <th>Correo</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while($estudiante = $result->fetch_assoc()): ?>
              <?php
                $modulo = isset($estudiante['modulo']) && $estudiante['modulo'] !== '' ? $estudiante['modulo'] : '-';
                $turno  = isset($estudiante['turno'])  && $estudiante['turno']  !== '' ? $estudiante['turno']  : '-';
                $estado = isset($estudiante['estado']) && $estudiante['estado'] !== '' ? $estudiante['estado'] : 'Activo';

                $badgeClass = 'badge-activo';
                if ($estado === 'Pendiente') {
                  $badgeClass = 'badge-pendiente';
                } elseif ($estado === 'Bloqueado') {
                  $badgeClass = 'badge-bloqueado';
                }

                $telefono   = isset($estudiante['telefono'])   ? $estudiante['telefono']   : '';
                $descripcion= isset($estudiante['descripcion'])? $estudiante['descripcion']: '';

                $fotoModalPath = "assets/img/default-user.png";
                if (!empty($estudiante['foto']) && file_exists("assets/uploads/" . $estudiante['foto'])) {
                    $fotoModalPath = "assets/uploads/" . $estudiante['foto'];
                }
              ?>
              <tr>
                <td><?php echo htmlspecialchars($estudiante['nombre']); ?></td>
                <td><?php echo htmlspecialchars($estudiante['dni']); ?></td>
                <td><?php echo htmlspecialchars($estudiante['carrera']); ?></td>
                <td><?php echo htmlspecialchars($modulo); ?></td>
                <td><?php echo htmlspecialchars($turno); ?></td>
                <td><?php echo htmlspecialchars($estudiante['email']); ?></td>
                <td>
                  <span class="badge-estado <?php echo $badgeClass; ?>">
                    <?php echo htmlspecialchars($estado); ?>
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <!-- VER: abre modal con datos -->
                    <button
                      type="button"
                      class="btn-table btn-ver btn-ver-estudiante"
                      data-nombre="<?php echo htmlspecialchars($estudiante['nombre']); ?>"
                      data-dni="<?php echo htmlspecialchars($estudiante['dni']); ?>"
                      data-carrera="<?php echo htmlspecialchars($estudiante['carrera']); ?>"
                      data-modulo="<?php echo htmlspecialchars($modulo); ?>"
                      data-turno="<?php echo htmlspecialchars($turno); ?>"
                      data-email="<?php echo htmlspecialchars($estudiante['email']); ?>"
                      data-telefono="<?php echo htmlspecialchars($telefono); ?>"
                      data-estado="<?php echo htmlspecialchars($estado); ?>"
                      data-descripcion="<?php echo htmlspecialchars($descripcion); ?>"
                      data-foto="<?php echo htmlspecialchars($fotoModalPath); ?>"
                    >
                      Ver
                    </button>

                    <!-- ELIMINAR -->
                    <a
                      href="admin_estudiantes.php?accion=eliminar&id=<?php echo (int)$estudiante['id']; ?>"
                      class="btn-table btn-eliminar"
                      onclick="return confirm('¿Seguro que quieres eliminar este estudiante? Esta acción no se puede deshacer.');"
                    >
                      Eliminar
                    </a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" style="text-align:center; padding: 2rem;">
                No hay estudiantes registrados aún.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </section>

    </main>
  </div>
</div>

<!-- MODAL VER ESTUDIANTE -->
<div id="modal-estudiante" class="modal">
  <div class="modal-dialog">
    <button type="button" class="modal-close" id="modal-close">&times;</button>

    <div class="modal-header">
      <div class="modal-avatar">
        <img id="modal-foto" src="assets/img/default-user.png" alt="Foto estudiante">
      </div>
      <div class="modal-title-block">
        <h2 id="modal-nombre">Nombre estudiante</h2>
        <p id="modal-email">correo@example.com</p>
      </div>
    </div>

    <div class="modal-body">
      <div class="modal-body-section">
        <p><strong>DNI:</strong> <span id="modal-dni"></span></p>
        <p><strong>Carrera:</strong> <span id="modal-carrera"></span></p>
        <p><strong>Módulo:</strong> <span id="modal-modulo"></span></p>
        <p><strong>Turno:</strong> <span id="modal-turno"></span></p>
        <p><strong>Teléfono:</strong> <span id="modal-telefono"></span></p>
        <p><strong>Estado:</strong> <span id="modal-estado"></span></p>
      </div>

      <div class="modal-descripcion">
        <strong>Descripción:</strong>
        <div id="modal-descripcion-text"></div>
      </div>
    </div>
  </div>
</div>

<script>
// Lógica modal Ver Estudiante
const modal      = document.getElementById('modal-estudiante');
const btnClose   = document.getElementById('modal-close');

const modalFoto        = document.getElementById('modal-foto');
const modalNombre      = document.getElementById('modal-nombre');
const modalEmail       = document.getElementById('modal-email');
const modalDni         = document.getElementById('modal-dni');
const modalCarrera     = document.getElementById('modal-carrera');
const modalModulo      = document.getElementById('modal-modulo');
const modalTurno       = document.getElementById('modal-turno');
const modalTelefono    = document.getElementById('modal-telefono');
const modalEstado      = document.getElementById('modal-estado');
const modalDescripcion = document.getElementById('modal-descripcion-text');

function abrirModalDesdeBoton(btn) {
  modalFoto.src       = btn.dataset.foto || 'assets/img/default-user.png';
  modalNombre.textContent   = btn.dataset.nombre || '';
  modalEmail.textContent    = btn.dataset.email || '';
  modalDni.textContent      = btn.dataset.dni || '';
  modalCarrera.textContent  = btn.dataset.carrera || '';
  modalModulo.textContent   = btn.dataset.modulo || '-';
  modalTurno.textContent    = btn.dataset.turno || '-';
  modalTelefono.textContent = btn.dataset.telefono || '-';
  modalEstado.textContent   = btn.dataset.estado || 'Activo';
  modalDescripcion.textContent = btn.dataset.descripcion || 'Sin descripción.';

  modal.classList.add('is-open');
}

function cerrarModal() {
  modal.classList.remove('is-open');
}

document.querySelectorAll('.btn-ver-estudiante').forEach(btn => {
  btn.addEventListener('click', () => abrirModalDesdeBoton(btn));
});

btnClose.addEventListener('click', cerrarModal);

modal.addEventListener('click', (e) => {
  if (e.target === modal) {
    cerrarModal();
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    cerrarModal();
  }
});
</script>

</body>
</html>
