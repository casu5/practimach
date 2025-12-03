<?php 
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'config/config/conexion.php';

/* ===== ELIMINAR MATCH (POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'eliminar'
    && !empty($_POST['match_id'])) {

    $match_id = (int)$_POST['match_id'];

    if ($stmtDel = $mysqli->prepare("DELETE FROM matches WHERE id = ?")) {
        $stmtDel->bind_param("i", $match_id);
        $stmtDel->execute();
        $stmtDel->close();
    }

    // Evitamos reenvío del formulario
    header("Location: admin_matches.php");
    exit;
}

// Obtener todos los matches, uniendo con estudiantes y empresas para obtener nombres
$sql = "SELECT m.id, e.nombre AS estudiante_nombre, emp.razon_social AS empresa_nombre, m.estado, m.created_at
        FROM matches m
        JOIN estudiantes e ON m.estudiante_id = e.id
        JOIN empresas emp ON m.empresa_id = emp.id
        ORDER BY m.created_at DESC";
$result = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Matches | Admin PractiMach</title>
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

/* SIDEBAR (MISMO ESTILO) */
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

.btn-chip {
  border-radius: 999px;
  border: 1px solid #d1d5db;
  padding: .45rem .9rem;
  font-size: .85rem;
  cursor: pointer;
  background: #fff;
  display: flex;
  align-items: center;
  gap: .35rem;
}

/* CONTENIDO */
.content {
  max-width: 1100px;
  margin: 1.8rem auto 2.2rem auto;
  padding: 0 1.5rem;
}

/* FILA DE FILTROS */
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

/* TABLA MATCHES */
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

/* BADGES & BOTONES */
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

.badge-cerrado {
  background: #e5e7eb;
  color: #4b5563;
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

/* MODAL DETALLE MATCH */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15,23,42,0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.modal-hidden {
  display: none;
}

.modal-box {
  background: #ffffff;
  border-radius: 16px;
  max-width: 420px;
  width: 90%;
  padding: 1.4rem 1.5rem 1.3rem;
  box-shadow: 0 20px 50px rgba(15,23,42,0.35);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: .9rem;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.1rem;
}

.modal-close {
  border: none;
  background: transparent;
  font-size: 1.2rem;
  cursor: pointer;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: .55rem;
}

.modal-field {
  font-size: .85rem;
}

.modal-field span.label {
  font-weight: 600;
  color: #374151;
}

.modal-field span.value {
  color: #111827;
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

  /* Ocultamos columna Puesto en pantallas pequeñas para que no reviente */
  .block-table th:nth-child(3),
  .block-table td:nth-child(3) {
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
      <a href="admin_estudiantes.php" class="sidebar-link">
        <span class="icon">🧑‍🎓</span>
        <span>Estudiantes</span>
      </a>
      <a href="admin_empresas.php" class="sidebar-link">
        <span class="icon">🏢</span>
        <span>Empresas</span>
      </a>
      <a href="admin_matches.php" class="sidebar-link active">
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
        <span>Gestión de matches</span>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-title">
        <h1>Matches</h1>
        <span>Control de emparejamientos entre estudiantes y empresas.</span>
      </div>

      <div class="topbar-actions">
        <button class="btn-chip">Últimos 30 días ▾</button>
        <button class="btn-chip">Todos los estados ▾</button>
      </div>
    </header>

    <!-- CONTENIDO -->
    <main class="content">

      <!-- FILTROS (solo visual por ahora) -->
      <div class="filters-row">
        <input type="text" class="input-search" placeholder="Buscar por estudiante, empresa o puesto…">

        <select class="select-filter">
          <option value="">Estado</option>
          <option>Activo</option>
          <option>Pendiente</option>
          <option>Cerrado</option>
        </select>

        <select class="select-filter">
          <option value="">Modalidad</option>
          <option>Remoto</option>
          <option>Presencial</option>
          <option>Mixto</option>
        </select>
      </div>

      <!-- TABLA MATCHES -->
      <section class="block">
        <div class="block-header">
          <h2>Listado de matches</h2>
          <span>Conectado a base de datos (PHP + MySQL).</span>
        </div>

        <table class="block-table">
          <thead>
            <tr>
              <th>Estudiante</th>
              <th>Empresa</th>
              <th>Puesto</th>
              <th>Modalidad</th>
              <th>Fecha match</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while($match = $result->fetch_assoc()): 
                // Asignar clase según estado
                $estadoClass = 'badge-pendiente';
                if ($match['estado'] === 'aceptado' || $match['estado'] === 'match') {
                  $estadoClass = 'badge-activo';
                } elseif ($match['estado'] === 'rechazado') {
                  $estadoClass = 'badge-cerrado';
                }
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($match['estudiante_nombre']); ?></td>
                  <td><?php echo htmlspecialchars($match['empresa_nombre']); ?></td>
                  <td>-</td> <!-- Puesto no está en la BD aún -->
                  <td>-</td> <!-- Modalidad no está en la BD aún -->
                  <td><?php echo (new DateTime($match['created_at']))->format('d/m/Y'); ?></td>
                  <td>
                    <span class="badge-estado <?php echo $estadoClass; ?>">
                      <?php echo htmlspecialchars(ucfirst($match['estado'])); ?>
                    </span>
                  </td>
                  <td>
                    <div class="table-actions">
                      <button
                        type="button"
                        class="btn-table btn-ver"
                        data-id="<?php echo (int)$match['id']; ?>"
                        data-estudiante="<?php echo htmlspecialchars($match['estudiante_nombre']); ?>"
                        data-empresa="<?php echo htmlspecialchars($match['empresa_nombre']); ?>"
                        data-estado="<?php echo htmlspecialchars($match['estado']); ?>"
                        data-fecha="<?php echo (new DateTime($match['created_at']))->format('d/m/Y H:i'); ?>"
                      >
                        Ver
                      </button>
                      <button
                        type="button"
                        class="btn-table btn-eliminar"
                        data-id="<?php echo (int)$match['id']; ?>"
                        data-estudiante="<?php echo htmlspecialchars($match['estudiante_nombre']); ?>"
                        data-empresa="<?php echo htmlspecialchars($match['empresa_nombre']); ?>"
                      >
                        Eliminar
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" style="text-align:center; padding: 2rem;">No hay matches registrados aún.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

    </main>
  </div>
</div>

<!-- FORMULARIO OCULTO PARA ELIMINAR MATCH -->
<form id="deleteForm" method="POST" style="display:none;">
  <input type="hidden" name="action" value="eliminar">
  <input type="hidden" name="match_id" id="deleteMatchId">
</form>

<!-- MODAL DETALLE MATCH -->
<div id="modalOverlay" class="modal-overlay modal-hidden">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Detalle del match</h3>
      <button type="button" id="modalClose" class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <div class="modal-field">
        <span class="label">Estudiante: </span>
        <span class="value" id="modalEstudiante"></span>
      </div>
      <div class="modal-field">
        <span class="label">Empresa: </span>
        <span class="value" id="modalEmpresa"></span>
      </div>
      <div class="modal-field">
        <span class="label">Puesto: </span>
        <span class="value">-</span>
      </div>
      <div class="modal-field">
        <span class="label">Modalidad: </span>
        <span class="value">-</span>
      </div>
      <div class="modal-field">
        <span class="label">Fecha de match: </span>
        <span class="value" id="modalFecha"></span>
      </div>
      <div class="modal-field">
        <span class="label">Estado: </span>
        <span class="value" id="modalEstado"></span>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Modal
  const overlay       = document.getElementById('modalOverlay');
  const btnClose      = document.getElementById('modalClose');
  const modalEst      = document.getElementById('modalEstudiante');
  const modalEmp      = document.getElementById('modalEmpresa');
  const modalFecha    = document.getElementById('modalFecha');
  const modalEstado   = document.getElementById('modalEstado');

  function openModal() {
    overlay.classList.remove('modal-hidden');
  }
  function closeModal() {
    overlay.classList.add('modal-hidden');
  }

  btnClose.addEventListener('click', closeModal);
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) closeModal();
  });

  document.querySelectorAll('.btn-ver').forEach(function(btn) {
    btn.addEventListener('click', function() {
      modalEst.textContent    = btn.dataset.estudiante || '';
      modalEmp.textContent    = btn.dataset.empresa || '';
      modalFecha.textContent  = btn.dataset.fecha || '';
      modalEstado.textContent = btn.dataset.estado || '';
      openModal();
    });
  });

  // Eliminar match
  const deleteForm    = document.getElementById('deleteForm');
  const deleteMatchId = document.getElementById('deleteMatchId');

  document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const id        = btn.dataset.id;
      const est       = btn.dataset.estudiante || '';
      const emp       = btn.dataset.empresa || '';
      const msg = '¿Seguro que deseas eliminar este match?\n\n' +
                  'Estudiante: ' + est + '\n' +
                  'Empresa: ' + emp + '\n\n' +
                  'Esta acción no se puede deshacer.';
      if (confirm(msg)) {
        deleteMatchId.value = id;
        deleteForm.submit();
      }
    });
  });
});
</script>

</body>
</html>
