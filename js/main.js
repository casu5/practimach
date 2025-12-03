// Toggle menú mobile
const navToggle = document.getElementById("navToggle");
const navbar = document.querySelector(".navbar");

if (navToggle && navbar) {
  navToggle.addEventListener("click", () => {
    navbar.classList.toggle("open");
  });
}

// Tabs Login / Registro
const tabs = document.querySelectorAll(".auth-tab");
const formLogin = document.getElementById("formLogin");
const formRegistro = document.getElementById("formRegistro");

tabs.forEach(tab => {
  tab.addEventListener("click", () => {
    tabs.forEach(t => t.classList.remove("active"));
    tab.classList.add("active");

    const target = tab.dataset.tab;
    if (target === "login") {
      formLogin.classList.remove("hidden");
      formRegistro.classList.add("hidden");
    } else {
      formLogin.classList.add("hidden");
      formRegistro.classList.remove("hidden");
    }
  });
});

// Selector de rol Estudiante / Empresa
const roleToggle = document.getElementById("roleToggle");
const roleInfo = document.getElementById("roleInfo");
const regEstudiante = document.getElementById("registro-estudiante");
const regEmpresa = document.getElementById("registro-empresa");

if (roleToggle) {
  // Función para manejar required dinámicamente
  function updateRequiredFields(isEmpresa) {
    // Campos de estudiante
    const estudianteInputs = regEstudiante.querySelectorAll('input, select');
    // Campos de empresa
    const empresaInputs = regEmpresa.querySelectorAll('input, select');

    if (isEmpresa) {
      // Desactivar required en campos de estudiante
      estudianteInputs.forEach(input => input.removeAttribute('required'));
      // Activar required en campos de empresa
      empresaInputs.forEach(input => input.setAttribute('required', 'required'));
    } else {
      // Activar required en campos de estudiante
      estudianteInputs.forEach(input => input.setAttribute('required', 'required'));
      // Desactivar required en campos de empresa
      empresaInputs.forEach(input => input.removeAttribute('required'));
    }
  }

  roleToggle.addEventListener("change", () => {
    if (roleToggle.checked) {
      // Empresa
      roleInfo.innerHTML = 'Estás ingresando como <strong>Empresa</strong>.';
      regEstudiante.classList.add("hidden");
      regEmpresa.classList.remove("hidden");
      updateRequiredFields(true);
    } else {
      // Estudiante
      roleInfo.innerHTML = 'Estás ingresando como <strong>Estudiante</strong>.';
      regEmpresa.classList.add("hidden");
      regEstudiante.classList.remove("hidden");
      updateRequiredFields(false);
    }
  });

  // Inicializar con el estado correcto (por defecto estudiante)
  updateRequiredFields(roleToggle.checked);
}

// Animaciones de aparición al hacer scroll
const fadeElements = document.querySelectorAll(".fade-in");

if ("IntersectionObserver" in window) {
  const observer = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );

  fadeElements.forEach(el => observer.observe(el));
}

// --- AUTH LOGIC ---

async function sendAuthRequest(data) {
  try {
    const response = await fetch('auth_actions.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    });
    return await response.json();
  } catch (error) {
    console.error('Error:', error);
    return { success: false, message: 'Error de conexión' };
  }
}

if (formLogin) {
  formLogin.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    // Determine role based on toggle (or try both? Logic in PHP handles admin check too)
    // The UI toggle suggests the user explicitly chooses their role context.
    const isEmpresa = roleToggle && roleToggle.checked;
    const role = isEmpresa ? 'empresa' : 'estudiante';

    const data = {
      action: 'login',
      email: email,
      password: document.getElementById('loginPassword').value,
      role: role
    };

    const res = await sendAuthRequest(data);
    if (res.success) {
      window.location.href = res.redirect;
    } else {
      setTimeout(() => alert(res.message || 'Error al iniciar sesión'), 10);
    }
  });
}

if (formRegistro) {
  formRegistro.addEventListener('submit', async (e) => {
    e.preventDefault();
    const isEmpresa = roleToggle && roleToggle.checked;
    const role = isEmpresa ? 'empresa' : 'estudiante';

    let data = {
      action: 'register',
      role: role,
      email: document.getElementById('regEmail').value,
      password: document.getElementById('regPassword').value
    };

    if (isEmpresa) {
      data.razon_social = document.getElementById('regRazonSocial').value;
      data.ruc = document.getElementById('regRuc').value;
      data.sector = document.getElementById('regSector').value;
    } else {
      data.nombre = document.getElementById('regNombre').value;
      data.dni = document.getElementById('regDni').value;
      data.carrera = document.getElementById('regCarrera').value;
    }

    const res = await sendAuthRequest(data);
    if (res.success) {
      // Mostrar mensaje de éxito
      alert('¡Cuenta creada exitosamente! Por favor inicia sesión.');

      // Limpiar el formulario
      formRegistro.reset();

      // Cambiar a la pestaña de login
      tabs.forEach(t => t.classList.remove("active"));
      const loginTab = document.querySelector('[data-tab="login"]');
      if (loginTab) {
        loginTab.classList.add("active");
        formLogin.classList.remove("hidden");
        formRegistro.classList.add("hidden");
      }
    } else {
      setTimeout(() => alert(res.message || 'Error al registrarse'), 10);
    }
  });
}