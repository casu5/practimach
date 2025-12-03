<?php
// auth.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Acceso | PractiMach</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/estilos.css?v=2.0" />

  <style>
    .status-box {
      margin-top: 1rem;
      padding: 0.8rem 1rem;
      border-radius: 6px;
      font-size: 0.9rem;
      background: #f5f5f5;
      border: 1px solid #ddd;
    }
    .status-box.ok {
      border-color: #28a745;
      color: #1f6b32;
      background: #e6f8ea;
    }
    .status-box.error {
      border-color: #dc3545;
      color: #842029;
      background: #f8d7da;
    }
    .hidden {
      display: none !important;
    }
    .input-with-button {
      display: flex;
      gap: 0.5rem;
      align-items: stretch;
    }
    .input-with-button input {
      flex: 1;
    }
    .btn-small {
      padding: 0 12px;
      font-size: 0.8rem;
      white-space: nowrap;
    }
    .readonly-input {
      background: #f0f0f0;
      cursor: not-allowed;
    }
  </style>
</head>
<body class="auth-body">
  <div class="bg-blur bg-blur-1"></div>
  <div class="bg-blur bg-blur-2"></div>

  <div class="auth-container">
    <div class="auth-card animate-pop">
      <div class="auth-header">
        <div class="logo">
          <span class="logo-icon">P</span>
          <span class="logo-text"><a href="index.php">PractiMach</span></a>
        </div>
        <p class="auth-subtitle">Inicia sesión o crea tu cuenta para continuar.</p>
        <div class="auth-tabs">
          <button class="auth-tab active" data-tab="login">Iniciar sesión</button>
          <button class="auth-tab" data-tab="registro" id="registro">Registrarme</button>
        </div>
      </div>

      <!-- Selector tipo de usuario -->
      <div class="role-switch">
        <span class="role-label">Estudiante</span>
        <label class="switch">
          <input type="checkbox" id="roleToggle">
          <span class="slider"></span>
        </label>
        <span class="role-label">Empresa</span>
      </div>
      <p class="role-info" id="roleInfo">Estás ingresando como <strong>Estudiante</strong>.</p>

      <!-- FORM LOGIN -->
      <form class="auth-form" id="formLogin">
        <div class="form-group">
          <label for="loginEmail">Correo institucional</label>
          <input type="email" id="loginEmail" placeholder="tucorreo@instituto.edu.pe" required>
        </div>
        <div class="form-group">
          <label for="loginPassword">Contraseña</label>
          <input type="password" id="loginPassword" placeholder="••••••••" required>
        </div>
        <div class="form-footer">
          <label class="checkbox">
            <input type="checkbox"> Recordarme
          </label>
          <a href="#" class="link-sm">Olvidé mi contraseña</a>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Ingresar</button>
      </form>

      <!-- FORM REGISTRO -->
      <form class="auth-form hidden" id="formRegistro">
        <!-- REGISTRO ESTUDIANTE -->
        <div id="registro-estudiante">
          <div class="form-group">
            <label for="regNombre">Nombre completo</label>
            <input type="text" id="regNombre" placeholder="Nombre y apellidos" required>
          </div>
          <div class="form-group">
            <label for="regDni">DNI</label>
            <input type="text" id="regDni" placeholder="12345678" required>
          </div>
          <div class="form-group">
            <label for="regCarrera">Carrera</label>
            <select id="regCarrera" required>
              <option value="" disabled selected>Selecciona tu carrera</option>
              <option value="ADG – Asistencia de Dirección y Gerencia">ADG – Asistencia de Dirección y Gerencia</option>
              <option value="APSTI – Arquitectura de Plataformas y Servicios de Tecnologías de Información">APSTI – Arquitectura de Plataformas y Servicios de Tecnologías de Información</option>
              <option value="CO – Contabilidad">CO – Contabilidad</option>
              <option value="CC – Construcción Civil">CC – Construcción Civil</option>
              <option value="PA – Producción Agropecuaria">PA – Producción Agropecuaria</option>
              <option value="EI – Electricidad Industrial">EI – Electricidad Industrial</option>
              <option value="EO – Electrónica Industrial">EO – Electrónica Industrial</option>
              <option value="MPI – Mecánica de Producción Industrial">MPI – Mecánica de Producción Industrial</option>
              <option value="MA – Mecatrónica Automotriz">MA – Mecatrónica Automotriz</option>
            </select>
          </div>
          <!-- 📱 TELÉFONO ESTUDIANTE (OBLIGATORIO) -->
          <div class="form-group">
            <label for="regTelefonoEstudiante">Teléfono / Celular</label>
            <input type="text" id="regTelefonoEstudiante" placeholder="Ej: 987654321" inputmode="tel" required>
          </div>
        </div>

        <!-- REGISTRO EMPRESA -->
        <div id="registro-empresa" class="hidden">
          <div class="form-group">
            <label for="regRuc">RUC</label>
            <div class="input-with-button">
              <input type="text" id="regRuc" placeholder="12345678901" maxlength="11">
              <button type="button" id="btnBuscarRuc" class="btn btn-secondary btn-small">Buscar RUC</button>
            </div>
          </div>

          <div class="form-group">
            <label for="regRazonSocial">Razón social (SUNAT)</label>
            <input type="text" id="regRazonSocial" class="readonly-input" readonly>
          </div>

          <!-- Campos adicionales devueltos por la API -->
          <div class="form-group">
            <label for="regDireccionSunat">Dirección (SUNAT)</label>
            <input type="text" id="regDireccionSunat" class="readonly-input" readonly>
          </div>

          <div class="form-group">
            <label for="regDepartamentoSunat">Departamento (SUNAT)</label>
            <input type="text" id="regDepartamentoSunat" class="readonly-input" readonly>
          </div>

          <div class="form-group">
            <label for="regProvinciaSunat">Provincia (SUNAT)</label>
            <input type="text" id="regProvinciaSunat" class="readonly-input" readonly>
          </div>

          <div class="form-group">
            <label for="regDistritoSunat">Distrito (SUNAT)</label>
            <input type="text" id="regDistritoSunat" class="readonly-input" readonly>
          </div>

          <!-- Campo oculto para id_departamento -->
          <input type="hidden" id="regIdDepartamentoSunat">

          <div class="form-group">
            <label for="regSector">Sector</label>
            <select id="regSector" name="regSector">
              <option value="" disabled selected>Selecciona el sector</option>
              <option value="Tecnología / Software">Tecnología / Software</option>
              <option value="Marketing / Publicidad">Marketing / Publicidad</option>
              <option value="Servicios Profesionales">Servicios Profesionales</option>
              <option value="Educación">Educación</option>
              <option value="Logística / Transporte">Logística / Transporte</option>
              <option value="Manufactura / Industria">Manufactura / Industria</option>
              <option value="Salud / Farmacéutico">Salud / Farmacéutico</option>
              <option value="Finanzas / Bancario">Finanzas / Bancario</option>
              <option value="Retail / Consumo Masivo">Retail / Consumo Masivo</option>
              <option value="Consultoría">Consultoría</option>
              <option value="Comercio Electrónico">Comercio Electrónico</option>
            </select>
          </div>

          <!-- 📱 TELÉFONO EMPRESA (OBLIGATORIO) -->
          <div class="form-group">
            <label for="regTelefonoEmpresa">Teléfono de contacto</label>
            <input type="text" id="regTelefonoEmpresa" placeholder="Ej: 987654321" inputmode="tel" required>
          </div>

          <div id="empresaStatus" class="status-box hidden"></div>
        </div>

        <!-- CAMPOS COMUNES -->
        <div class="form-group">
          <label for="regEmail">Correo</label>
          <input type="email" id="regEmail" placeholder="correo@ejemplo.com" required>
        </div>
        <div class="form-group">
          <label for="regPassword">Contraseña</label>
          <input type="password" id="regPassword" placeholder="Mínimo 8 caracteres" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Crear cuenta</button>
      </form>

      <p class="auth-hint">
        Al continuar aceptas los <a href="#" class="link-sm">términos y condiciones</a>.
      </p>
      <p style="text-align:center; margin-top: 1rem;">
        <a href="admin_login.php" class="link-sm">Acceso Administradores</a>
      </p>
    </div>

    <div class="auth-side">
      <h2>Una plataforma para gestionar todas tus prácticas.</h2>
      <p>Accede a paneles diseñados para estudiantes, empresas y administradores, con métricas claras y flujo intuitivo.</p>
      <ul class="auth-list">
        <li>✔ Seguimiento de postulaciones</li>
        <li>✔ Gestión de convenios</li>
        <li>✔ Reportes automáticos</li>
      </ul>
    </div>
  </div>

  <!-- JS general -->
  <script src="js/main.js?v=3.0"></script>

  <!-- JS específico de esta vista -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Tabs login / registro
      const tabs        = document.querySelectorAll('.auth-tab');
      const formLogin   = document.getElementById('formLogin');
      const formRegistro= document.getElementById('formRegistro');

      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          tabs.forEach(t => t.classList.remove('active'));
          tab.classList.add('active');

          const target = tab.dataset.tab;
          if (target === 'login') {
            formLogin.classList.remove('hidden');
            formRegistro.classList.add('hidden');
          } else {
            formLogin.classList.add('hidden');
            formRegistro.classList.remove('hidden');
          }
        });
      });

      const roleToggle    = document.getElementById('roleToggle');
      const roleInfo      = document.getElementById('roleInfo');
      const regEst        = document.getElementById('registro-estudiante');
      const regEmp        = document.getElementById('registro-empresa');
      const empresaStatus = document.getElementById('empresaStatus');

      const inputRuc      = document.getElementById('regRuc');
      const btnBuscarRuc  = document.getElementById('btnBuscarRuc');

      let rucData = null; // datos del RUC válidos

      function limpiarRucData() {
        rucData = null;

        const rs  = document.getElementById('regRazonSocial');
        const dir = document.getElementById('regDireccionSunat');
        const dep = document.getElementById('regDepartamentoSunat');
        const prov= document.getElementById('regProvinciaSunat');
        const dist= document.getElementById('regDistritoSunat');
        const idd = document.getElementById('regIdDepartamentoSunat');

        if (rs)  rs.value  = '';
        if (dir) dir.value = '';
        if (dep) dep.value = '';
        if (prov)prov.value= '';
        if (dist)dist.value= '';
        if (idd) idd.value = '';
      }

      // Cambio Estudiante / Empresa
      if (roleToggle) {
        roleToggle.addEventListener('change', () => {
          const isEmpresa = roleToggle.checked;

          if (isEmpresa) {
            roleInfo.innerHTML = 'Estás ingresando como <strong>Empresa</strong>.';
            regEst.classList.add('hidden');
            regEmp.classList.remove('hidden');
          } else {
            roleInfo.innerHTML = 'Estás ingresando como <strong>Estudiante</strong>.';
            regEst.classList.remove('hidden');
            regEmp.classList.add('hidden');
          }

          limpiarRucData();
          if (empresaStatus) {
            empresaStatus.classList.add('hidden');
            empresaStatus.classList.remove('ok', 'error');
            empresaStatus.innerHTML = '';
          }
        });
      }

      // Buscar RUC
      if (btnBuscarRuc && inputRuc) {
        btnBuscarRuc.addEventListener('click', async () => {
          const ruc = inputRuc.value.trim();

          limpiarRucData();

          if (!ruc || ruc.length !== 11 || !/^[0-9]+$/.test(ruc)) {
            if (empresaStatus) {
              empresaStatus.classList.remove('hidden', 'ok');
              empresaStatus.classList.add('error');
              empresaStatus.innerHTML = '❌ El RUC debe tener exactamente 11 dígitos numéricos.';
            }
            return;
          }

          if (empresaStatus) {
            empresaStatus.classList.remove('hidden', 'ok', 'error');
            empresaStatus.innerHTML = '⏳ Buscando información del RUC en SUNAT...';
          }

          try {
            const body = new URLSearchParams();
            body.append('ruc', ruc);

            const resp = await fetch('ruc_lookup.php', {
              method: 'POST',
              body: body
            });

            const data = await resp.json();

            if (!empresaStatus) return;

            if (data.success) {
              empresaStatus.classList.remove('error');
              empresaStatus.classList.add('ok');
              empresaStatus.innerHTML = '✅ RUC válido. Completa los demás campos para crear la cuenta.';

              rucData = data.data;

              const rs   = document.getElementById('regRazonSocial');
              const dir  = document.getElementById('regDireccionSunat');
              const dep  = document.getElementById('regDepartamentoSunat');
              const prov = document.getElementById('regProvinciaSunat');
              const dist = document.getElementById('regDistritoSunat');
              const idd  = document.getElementById('regIdDepartamentoSunat');

              if (rs)   rs.value  = data.data.razon_social    || '';
              if (dir)  dir.value = data.data.direccion       || '';
              if (dep)  dep.value = data.data.departamento    || '';
              if (prov) prov.value= data.data.provincia       || '';
              if (dist) dist.value= data.data.distrito        || '';
              if (idd)  idd.value = data.data.id_departamento || '';

            } else {
              empresaStatus.classList.remove('ok');
              empresaStatus.classList.add('error');
              empresaStatus.innerHTML = '❌ ' + data.message;
            }
          } catch (err) {
            if (empresaStatus) {
              empresaStatus.classList.remove('ok');
              empresaStatus.classList.add('error');
              empresaStatus.innerHTML = '❌ Error al conectar con el servidor: ' + err;
            }
          }
        });
      }

      // Submit registro
      if (formRegistro) {
        formRegistro.addEventListener('submit', async (e) => {
          e.preventDefault();
          e.stopImmediatePropagation(); // evita que otro JS viejo también procese el submit

          const isEmpresa = roleToggle && roleToggle.checked;

          // =======================
          // REGISTRO ESTUDIANTE
          // =======================
          if (!isEmpresa) {
            const nombre    = document.getElementById('regNombre').value.trim();
            const dni       = document.getElementById('regDni').value.trim();
            const carrera   = document.getElementById('regCarrera').value;
            const telefonoS = document.getElementById('regTelefonoEstudiante').value.trim();
            const email     = document.getElementById('regEmail').value.trim();
            const password  = document.getElementById('regPassword').value;

            if (!nombre || !dni || !carrera || !telefonoS || !email || !password) {
              alert('❌ Completa todos los campos del formulario.');
              return;
            }

            if (!/^[0-9]{8}$/.test(dni)) {
              alert('❌ El DNI debe tener exactamente 8 dígitos numéricos.');
              return;
            }

            if (!/^[0-9]{6,}$/.test(telefonoS)) {
              alert('❌ El teléfono debe tener solo números y al menos 6 dígitos.');
              return;
            }

            // 🔴 YA NO SE VALIDA LA LONGITUD MÍNIMA DE LA CONTRASEÑA

            try {
              const body = new URLSearchParams();
              body.append('nombre', nombre);
              body.append('dni', dni);
              body.append('carrera', carrera);
              body.append('telefono', telefonoS); // 👈 se envía como "telefono"
              body.append('email', email);
              body.append('password', password);

              const resp = await fetch('estudiante_registro.php', {
                method: 'POST',
                body: body
              });

              const data = await resp.json();

              if (data.success) {
                alert('✅ ' + data.message);
                window.location.reload();
              } else {
                alert('❌ ' + data.message);
              }
            } catch (err) {
              alert('❌ Error al conectar con el servidor: ' + err);
            }

            return; // importante: no seguir al flujo empresa
          }

          // =======================
          // REGISTRO EMPRESA
          // =======================
          const ruc       = inputRuc.value.trim();
          const sector    = document.getElementById('regSector').value;
          const email     = document.getElementById('regEmail').value.trim();
          const password  = document.getElementById('regPassword').value;
          const telefonoE = document.getElementById('regTelefonoEmpresa').value.trim();

          if (!rucData) {
            if (empresaStatus) {
              empresaStatus.classList.remove('hidden', 'ok');
              empresaStatus.classList.add('error');
              empresaStatus.innerHTML = '❌ Primero busca y valida el RUC antes de crear la cuenta.';
            }
            return;
          }

          if (!sector) {
            if (empresaStatus) {
              empresaStatus.classList.remove('hidden', 'ok');
              empresaStatus.classList.add('error');
              empresaStatus.innerHTML = '❌ Selecciona el sector de la empresa.';
            }
            return;
          }

          if (!telefonoE) {
            if (empresaStatus) {
              empresaStatus.classList.remove('hidden', 'ok');
              empresaStatus.classList.add('error');
              empresaStatus.innerHTML = '❌ Ingresa el teléfono de contacto.';
            }
            return;
          }

          if (!email || !password) {
            if (empresaStatus) {
              empresaStatus.classList.remove('hidden', 'ok');
              empresaStatus.classList.add('error');
              empresaStatus.innerHTML = '❌ Ingresa correo y contraseña.';
            }
            return;
          }

          if (empresaStatus) {
            empresaStatus.classList.remove('hidden', 'ok', 'error');
            empresaStatus.innerHTML = '⏳ Registrando empresa, por favor espera...';
          }

          try {
            const body = new URLSearchParams();
            body.append('ruc', ruc);
            body.append('sector', sector);
            body.append('email', email);
            body.append('password', password);
            body.append('telefono', telefonoE); // 🔑 Enviamos teléfono al backend

            const resp = await fetch('empresa_registro.php', {
              method: 'POST',
              body: body
            });

            const data = await resp.json();

            if (!empresaStatus) return;

            if (data.success) {
              empresaStatus.classList.remove('error');
              empresaStatus.classList.add('ok');
              empresaStatus.innerHTML = '✅ ' + data.message;

              setTimeout(() => {
                window.location.reload();
              }, 2500);
            } else {
              empresaStatus.classList.remove('ok');
              empresaStatus.classList.add('error');
              empresaStatus.innerHTML = '❌ ' + data.message;
            }
          } catch (err) {
            if (empresaStatus) {
              empresaStatus.classList.remove('ok');
              empresaStatus.classList.add('error');
              empresaStatus.innerHTML = '❌ Error al conectar con el servidor: ' + err;
            }
          }
        });
      }
    });
  </script>
</body>
</html>
