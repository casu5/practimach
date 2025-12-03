<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'config/config/conexion.php';

/* ============================================================
   CONFIGURACIÓN: CARRERAS
   - Creamos tabla config_carreras si no existe
   - La llenamos la primera vez con DISTINCT estudiante.carrera
   - Permitimos AGREGAR y ELIMINAR carreras desde el panel
   ============================================================ */

// Crear tabla de carreras de configuración si no existe
$mysqli->query("
    CREATE TABLE IF NOT EXISTS config_carreras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Si la tabla está vacía, la rellenamos con las carreras que ya existan en estudiantes
$seedCount = 0;
$resCount = $mysqli->query("SELECT COUNT(*) FROM config_carreras");
if ($resCount && ($rowCount = $resCount->fetch_row())) {
    $seedCount = (int)$rowCount[0];
}
if ($seedCount === 0) {
    $resDistinct = $mysqli->query("SELECT DISTINCT carrera FROM estudiantes WHERE carrera IS NOT NULL AND carrera <> '' ORDER BY carrera ASC");
    if ($resDistinct) {
        $stmtIns = $mysqli->prepare("INSERT IGNORE INTO config_carreras (nombre) VALUES (?)");
        while ($row = $resDistinct->fetch_assoc()) {
            $nombreCarrera = $row['carrera'];
            if (trim($nombreCarrera) !== '') {
                $stmtIns->bind_param("s", $nombreCarrera);
                $stmtIns->execute();
            }
        }
        $stmtIns->close();
    }
}

// Manejo de mensajes
$mensaje = '';
$tipo_mensaje = ''; // ok | error | info

// Manejo de formularios (agregar / eliminar carrera)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['action'] ?? '';

    if ($accion === 'add_carrera') {
        $nuevaCarrera = trim($_POST['nueva_carrera'] ?? '');
        if ($nuevaCarrera === '') {
            $mensaje = "Escribe un nombre de carrera antes de agregar.";
            $tipo_mensaje = 'error';
        } else {
            $stmtAdd = $mysqli->prepare("INSERT IGNORE INTO config_carreras (nombre) VALUES (?)");
            $stmtAdd->bind_param("s", $nuevaCarrera);
            if ($stmtAdd->execute()) {
                if ($stmtAdd->affected_rows > 0) {
                    $mensaje = "Carrera agregada correctamente.";
                    $tipo_mensaje = 'ok';
                } else {
                    $mensaje = "La carrera ya existe en el catálogo.";
                    $tipo_mensaje = 'info';
                }
            } else {
                $mensaje = "Error al agregar carrera: " . $mysqli->error;
                $tipo_mensaje = 'error';
            }
            $stmtAdd->close();
        }
    } elseif ($accion === 'delete_carrera') {
        $idCarrera = (int)($_POST['carrera_id'] ?? 0);
        if ($idCarrera <= 0) {
            $mensaje = "ID de carrera no válido.";
            $tipo_mensaje = 'error';
        } else {
            $stmtDel = $mysqli->prepare("DELETE FROM config_carreras WHERE id = ?");
            $stmtDel->bind_param("i", $idCarrera);
            if ($stmtDel->execute()) {
                if ($stmtDel->affected_rows > 0) {
                    $mensaje = "Carrera eliminada correctamente.";
                    $tipo_mensaje = 'ok';
                } else {
                    $mensaje = "La carrera ya no existe o ya fue eliminada.";
                    $tipo_mensaje = 'info';
                }
            } else {
                $mensaje = "Error al eliminar carrera: " . $mysqli->error;
                $tipo_mensaje = 'error';
            }
            $stmtDel->close();
        }
    }
}

// Obtener carreras desde config_carreras
$carreras_stmt = $mysqli->query("SELECT id, nombre FROM config_carreras ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Configuración | Admin PractiMach</title>
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

/* MENSAJES */
.alert {
  margin-bottom: 1rem;
  padding: .7rem 1rem;
  border-radius: 999px;
  font-size: .8rem;
}
.alert-ok {
  background: #dcfce7;
  color: #166534;
}
.alert-error {
  background: #fee2e2;
  color: #b91c1c;
}
.alert-info {
  background: #e5e7eb;
  color: #374151;
}

/* GRID CONFIG */
.config-grid {
  display: grid;
  grid-template-columns: 1.1fr 1.1fr;
  gap: 1.2rem;
}

/* BLOQUES */
.block {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 8px 20px rgba(15,23,42,0.04);
  padding: 1rem 1.2rem 1.2rem 1.2rem;
}

.block-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: .7rem;
  gap: .5rem;
}

.block-title {
  font-size: .95rem;
  font-weight: 600;
}

.block-sub {
  font-size: .8rem;
  color: #6b7280;
}

.badge-small {
  font-size: .75rem;
  border-radius: 999px;
  padding: .15rem .55rem;
  background: #eff6ff;
  color: #1d4ed8;
}

/* LISTAS SIMPLES */
.list-simple {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: .85rem;
}

.list-simple li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: .4rem 0;
  border-bottom: 1px dashed #e5e7eb;
}

.list-simple li:last-child {
  border-bottom: none;
}

.list-label {
  color: #374151;
}

.list-meta {
  font-size: .78rem;
  color: #9ca3af;
}

/* INPUTS & BOTONES */
.input-inline {
  margin-top: .6rem;
  display: flex;
  gap: .5rem;
}

.input {
  flex: 1;
  border-radius: 999px;
  border: 1px solid #d1d5db;
  padding: .55rem .8rem;
  font-size: .8rem;
  background: #f9fafb;
  outline: none;
}

.input:focus {
  border-color: #003590;
  box-shadow: 0 0 0 2px #00359033;
  background: #fff;
}

.btn-small {
  border-radius: 999px;
  border: none;
  padding: .5rem .9rem;
  font-size: .8rem;
  cursor: pointer;
  background: #003590;
  color: #fff;
}

.btn-small.secondary {
  background: #e5e7eb;
  color: #111827;
}

/* BOTÓN ELIMINAR CARRERA (mini) */
.btn-mini {
  border-radius: 999px;
  border: none;
  padding: .2rem .6rem;
  font-size: .7rem;
  cursor: pointer;
  background: #fee2e2;
  color: #b91c1c;
}

/* TOGGLE / SWITCH FICTICIO */
.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .35rem 0;
}

.toggle-label {
  font-size: .85rem;
  color: #374151;
}

.fake-switch {
  width: 40px;
  height: 22px;
  border-radius: 999px;
  background: #22c55e;
  position: relative;
}

.fake-switch::before {
  content: "";
  position: absolute;
  top: 2px;
  right: 2px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #fff;
}

/* NOTAS */
.note {
  font-size: .78rem;
  color: #6b7280;
  margin-top: .6rem;
}

/* RESPONSIVE */
@media (max-width: 950px) {
  .config-grid {
    grid-template-columns: 1fr;
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
      <a href="admin_matches.php" class="sidebar-link">
        <span class="icon">❤️</span>
        <span>Matches</span>
      </a>

      <div class="sidebar-section-title">Sistema</div>
      <a href="admin_config.php" class="sidebar-link active">
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
        <span>Parámetros del sistema</span>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-title">
        <h1>Configuración del sistema</h1>
        <span>Define carreras, módulos, turnos y reglas de matching.</span>
      </div>

      <div class="topbar-actions">
        <button class="btn-primary">Guardar cambios rápidos</button>
      </div>
    </header>

    <!-- CONTENIDO -->
    <main class="content">

      <?php if (!empty($mensaje)): ?>
        <div class="alert <?php
          echo $tipo_mensaje === 'ok' ? 'alert-ok' :
               ($tipo_mensaje === 'error' ? 'alert-error' : 'alert-info');
        ?>">
          <?php echo htmlspecialchars($mensaje); ?>
        </div>
      <?php endif; ?>

      <div class="config-grid">

        <!-- BLOQUE CARRERAS -->
        <section class="block">
          <div class="block-header">
            <div>
              <div class="block-title">Carreras activas</div>
              <div class="block-sub">Carreras que podrán elegir los estudiantes.</div>
            </div>
            <span class="badge-small">Catálogo</span>
          </div>

          <ul class="list-simple">
            <?php if ($carreras_stmt && $carreras_stmt->num_rows > 0): ?>
              <?php while($carrera = $carreras_stmt->fetch_assoc()): ?>
                <li>
                  <span class="list-label"><?php echo htmlspecialchars($carrera['nombre']); ?></span>
                  <span class="list-meta">
                    <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar esta carrera del catálogo?');">
                      <input type="hidden" name="action" value="delete_carrera">
                      <input type="hidden" name="carrera_id" value="<?php echo (int)$carrera['id']; ?>">
                      <button type="submit" class="btn-mini">Eliminar</button>
                    </form>
                  </span>
                </li>
              <?php endwhile; ?>
            <?php else: ?>
              <li><span class="list-label">No hay carreras registradas.</span></li>
            <?php endif; ?>
          </ul>

          <form method="post" class="input-inline" style="margin-top:.8rem;">
            <input type="hidden" name="action" value="add_carrera">
            <input class="input" type="text" name="nueva_carrera" placeholder="Agregar nueva carrera…">
            <button type="submit" class="btn-small">Agregar</button>
          </form>

          <p class="note">
            Se guarda en la tabla <strong>config_carreras</strong>.
            La primera vez se llenó automáticamente con las carreras de <strong>estudiantes</strong>.
          </p>
        </section>

        <!-- BLOQUE MÓDULOS Y TURNOS -->
        <section class="block">
          <div class="block-header">
            <div>
              <div class="block-title">Módulos y turnos</div>
              <div class="block-sub">Configura módulos por carrera y turnos disponibles.</div>
            </div>
            <span class="badge-small">Académico</span>
          </div>

          <ul class="list-simple">
            <li>
              <span class="list-label">Módulos habilitados</span>
              <span class="list-meta">I · II · III · IV</span>
            </li>
            <li>
              <span class="list-label">Turnos</span>
              <span class="list-meta">Mañana · Tarde · Noche</span>
            </li>
          </ul>

          <form class="input-inline" onsubmit="alert('Esta sección es solo visual por ahora. Se conectará a BD en Laravel.'); return false;">
            <input class="input" type="text" placeholder="Agregar módulo (ej. V)…">
            <button class="btn-small secondary" type="submit">Agregar</button>
          </form>

          <form class="input-inline" style="margin-top:.4rem;" onsubmit="alert('Esta sección es solo visual por ahora. Se conectará a BD en Laravel.'); return false;">
            <input class="input" type="text" placeholder="Agregar turno (ej. Full Day)…">
            <button class="btn-small secondary" type="submit">Agregar</button>
          </form>

          <p class="note">Solo se muestra estático como maqueta. Luego se leerá de la BD.</p>
        </section>

        <!-- BLOQUE MATCHING -->
        <section class="block">
          <div class="block-header">
            <div>
              <div class="block-title">Parámetros de matching</div>
              <div class="block-sub">Pesos para coincidencia entre estudiante y empresa.</div>
            </div>
            <span class="badge-small">Matching</span>
          </div>

          <ul class="list-simple">
            <li>
              <span class="list-label">Peso carrera</span>
              <span class="list-meta">40%</span>
            </li>
            <li>
              <span class="list-label">Peso habilidades</span>
              <span class="list-meta">35%</span>
            </li>
            <li>
              <span class="list-label">Peso módulo actual</span>
              <span class="list-meta">15%</span>
            </li>
            <li>
              <span class="list-label">Peso turno / disponibilidad</span>
              <span class="list-meta">10%</span>
            </li>
          </ul>

          <form class="input-inline" onsubmit="alert('Los parámetros de matching aún no se guardan en BD. Será parte de la lógica en Laravel.'); return false;">
            <input class="input" type="text" placeholder="Ej. carrera=40, skills=35 … (solo diseño)">
            <button class="btn-small" type="submit">Actualizar</button>
          </form>

          <p class="note">Más adelante puedes pasar esto a una tabla <strong>parametros_matching</strong>.</p>
        </section>

        <!-- BLOQUE SEGURIDAD / OPCIONES -->
        <section class="block">
          <div class="block-header">
            <div>
              <div class="block-title">Seguridad y acceso</div>
              <div class="block-sub">Opciones generales del sistema.</div>
            </div>
            <span class="badge-small">Seguridad</span>
          </div>

          <div class="toggle-row">
            <span class="toggle-label">Solo correos institucionales</span>
            <div class="fake-switch"></div>
          </div>

          <div class="toggle-row">
            <span class="toggle-label">Revisión manual de empresas nuevas</span>
            <div class="fake-switch"></div>
          </div>

          <div class="toggle-row">
            <span class="toggle-label">Permitir login de admin</span>
            <div class="fake-switch"></div>
          </div>

          <form class="input-inline" style="margin-top:.8rem;" onsubmit="alert('En esta maqueta solo se simula el guardado del correo. En producción se guardará en BD.'); return false;">
            <input class="input" type="text" placeholder="Correo para notificaciones (ej. practimach@instituto.pe)">
            <button class="btn-small secondary" type="submit">Guardar</button>
          </form>

          <p class="note">Estos switches son visuales en el front. La lógica real se implementará más adelante.</p>
        </section>
      </div>

    </main>
  </div>
</div>

</body>
</html>
