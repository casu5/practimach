<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Acceso Administrador | PractiMach</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/estilos.css" />
  <style>
    /* Estilos específicos para esta página si son necesarios, aunque ya usa estilos.css */
    .auth-container { grid-template-columns: 1fr; }
    .auth-side { display: none; }
    .auth-card { max-width: 400px; margin: 2rem auto; }
    .auth-tabs, .role-switch, .role-info { display: none; } /* Ocultar elementos de usuario */
    .auth-subtitle { text-align: center; }
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
          <span class="logo-text">PractiMach</span>
        </div>
        <p class="auth-subtitle">Acceso exclusivo para administradores.</p>
      </div>

      <!-- FORM LOGIN ADMIN -->
      <form class="auth-form" id="formAdminLogin">
        <div class="form-group">
          <label for="adminEmail">Correo de Administrador</label>
          <input type="email" id="adminEmail" placeholder="admin@practimach.com" required>
        </div>
        <div class="form-group">
          <label for="adminPassword">Contraseña</label>
          <input type="password" id="adminPassword" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Ingresar como Admin</button>
      </form>

      <p class="auth-hint" style="text-align:center; margin-top: 1.5rem;">
        <a href="auth.php" class="link-sm">Volver al login de usuarios</a>
      </p>
    </div>
  </div>

  <script>
    document.getElementById('formAdminLogin').addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('adminEmail').value;
      const password = document.getElementById('adminPassword').value;

      const data = {
        action: 'login',
        email: email,
        password: password,
        role: 'admin' // Especificamos el rol 'admin'
      };

      try {
        const response = await fetch('auth_actions.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        });
        const res = await response.json();

        if (res.success) {
          window.location.href = res.redirect;
        } else {
          setTimeout(() => alert(res.message || 'Error al iniciar sesión de administrador'), 10);
        }
      } catch (error) {
        console.error('Error:', error);
        setTimeout(() => alert('Error de conexión con el servidor.'), 10);
      }
    });
  </script>
</body>
</html>
