<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>PractiMach | Conecta Talento y Empresa</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/estilos.css" />
</head>
<body>
  <!-- Fondo decorativo -->
  <div class="bg-blur bg-blur-1"></div>
  <div class="bg-blur bg-blur-2"></div>

  <!-- NAVBAR -->
  <header class="navbar">
    <div class="container navbar-inner">
      <div class="logo">
        <span class="logo-icon">P</span>
        <span class="logo-text">PractiMach</span>
      </div>
      <nav class="nav-links">
        <a href="#como-funciona" class="nav-link">Cómo funciona</a>
        <a href="#beneficios" class="nav-link">Beneficios</a>
        <a href="#empresas" class="nav-link">Empresas</a>
        <a href="#contacto" class="nav-link">Contacto</a>
      </nav>
      <div class="nav-actions">
        <a href="auth.php" class="btn btn-outline">Ingresar</a>
        <a href="auth.php#registro" class="btn btn-primary">Crear cuenta</a>
      </div>
      <button class="nav-toggle" id="navToggle">
        ☰
      </button>
    </div>
  </header>

  <!-- HERO -->
  <section class="hero">
    <div class="container hero-inner">
      <div class="hero-content">
        <h1 class="hero-title">
          Tu puente entre <span class="highlight">la universidad</span> y el <span class="highlight">mundo laboral</span>.
        </h1>
        <p class="hero-subtitle">
          PractiMach conecta estudiantes con empresas para prácticas preprofesionales reales,
          con seguimiento, métricas y resultados visibles.
        </p>
        <div class="hero-buttons">
          <a href="auth.php#registro-estudiante" class="btn btn-primary btn-lg">Soy estudiante</a>
          <a href="auth.php#registro-empresa" class="btn btn-secondary btn-lg">Soy empresa</a>
        </div>
        <div class="hero-meta">
          <div class="meta-item">
            <span class="meta-number">+120</span>
            <span class="meta-label">Estudiantes colocados</span>
          </div>
          <div class="meta-item">
            <span class="meta-number">+30</span>
            <span class="meta-label">Empresas aliadas</span>
          </div>
          <div class="meta-item">
            <span class="meta-number">95%</span>
            <span class="meta-label">Satisfacción</span>
          </div>
        </div>
      </div>

      <!-- Tarjetas animadas estilo dashboard -->
      <div class="hero-cards">
        <div class="card card-main animate-float">
          <h3>Panel Estudiante</h3>
          <p>Encuentra vacantes, postula en un clic y sigue el estado en tiempo real.</p>
          <div class="pill pill-success">3 nuevas vacantes hoy</div>
          <ul class="mini-list">
            <li><span class="dot dot-green"></span>CV 100% completado</li>
            <li><span class="dot dot-blue"></span>2 postulaciones en proceso</li>
            <li><span class="dot dot-red"></span>1 entrevista pendiente</li>
          </ul>
        </div>

        <div class="card card-small card-right animate-float-delay">
          <h4>Empresas activas</h4>
          <div class="avatar-row">
            <div class="avatar">A</div>
            <div class="avatar">B</div>
            <div class="avatar">C</div>
            <div class="avatar more">+12</div>
          </div>
          <p class="card-note">Publicando nuevas ofertas cada semana.</p>
        </div>

        <div class="card card-small card-bottom animate-float-alt">
          <h4>Postulación destacada</h4>
          <p><strong>Kevin Pardo</strong> · Dev Backend Jr.</p>
          <div class="tag-row">
            <span class="tag">PHP</span>
            <span class="tag">Laravel</span>
            <span class="tag">MySQL</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CÓMO FUNCIONA -->
  <section class="section" id="como-funciona">
    <div class="container">
      <h2 class="section-title">¿Cómo funciona PractiMach?</h2>
      <p class="section-subtitle">En 3 pasos simples para estudiantes y empresas.</p>

      <div class="grid-3">
        <div class="step-card fade-in">
          <span class="step-number">1</span>
          <h3>Crea tu perfil</h3>
          <p>Completa tu información, habilidades, CV y disponibilidad. Tu perfil será tu carta de presentación.</p>
        </div>
        <div class="step-card fade-in">
          <span class="step-number">2</span>
          <h3>Conéctate con empresas</h3>
          <p>Explora las vacantes filtradas por carrera, horario y modalidad. Postula con un clic.</p>
        </div>
        <div class="step-card fade-in">
          <span class="step-number">3</span>
          <h3>Seguimiento y feedback</h3>
          <p>Ve el estado de cada postulación y recibe retroalimentación directa de las empresas.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- BENEFICIOS -->
  <section class="section section-alt" id="beneficios">
    <div class="container">
      <h2 class="section-title">Beneficios para tu institución</h2>
      <p class="section-subtitle">Control, métricas y trazabilidad de las prácticas preprofesionales.</p>

      <div class="grid-2">
        <div class="benefits-list fade-in">
          <div class="benefit-item">
            <h3>Dashboard en tiempo real</h3>
            <p>Visualiza cuántos estudiantes están en prácticas, dónde y con qué desempeño.</p>
          </div>
          <div class="benefit-item">
            <h3>Convenios centralizados</h3>
            <p>Administra tus empresas aliadas en un solo lugar, con historial y renovaciones.</p>
          </div>
          <div class="benefit-item">
            <h3>Reportes automáticos</h3>
            <p>Genera informes para acreditaciones y auditorías en segundos.</p>
          </div>
        </div>

        <div class="card dashboard-preview fade-in">
          <h3>Panel Admin</h3>
          <div class="stats-row">
            <div class="stat-card">
              <span class="stat-label">Estudiantes en prácticas</span>
              <span class="stat-value">64</span>
              <span class="stat-trend up">+8 este mes</span>
            </div>
            <div class="stat-card">
              <span class="stat-label">Empresas activas</span>
              <span class="stat-value">27</span>
              <span class="stat-trend">Estable</span>
            </div>
          </div>
          <div class="chart-placeholder">
            <span>Gráfico de colocaciones (Chart.js)</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- EMPRESAS -->
  <section class="section" id="empresas">
    <div class="container">
      <h2 class="section-title">Para empresas aliadas</h2>
      <p class="section-subtitle">Encuentra talento joven, motivado y con base académica sólida.</p>

      <div class="grid-3">
        <div class="company-card fade-in">
          <h3>Publica vacantes en minutos</h3>
          <p>Define perfil, habilidades requeridas y duración. El sistema sugiere candidatos ideales.</p>
        </div>
        <div class="company-card fade-in">
          <h3>Filtra y evalúa postulantes</h3>
          <p>Revisa CV, habilidades y estado académico desde un panel limpio y rápido.</p>
        </div>
        <div class="company-card fade-in">
          <h3>Gestiona tu historial</h3>
          <p>Ten registro de todos los estudiantes que pasaron por tu empresa y su desempeño.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACTO / FOOTER -->
  <footer class="footer" id="contacto">
    <div class="container footer-inner">
      <div>
        <div class="logo footer-logo">
          <span class="logo-icon">P</span>
          <span class="logo-text">PractiMach</span>
        </div>
        <p class="footer-text">Plataforma de gestión de prácticas preprofesionales.</p>
      </div>
      <div>
        <h4>Contacto</h4>
        <p>Correo: practimach@instituto.edu.pe</p>
        <p>Teléfono: +51 999 999 999</p>
      </div>
      <div>
        <h4>Accesos</h4>
        <a href="auth.php" class="footer-link">Acceder</a><br>
        <a href="auth.php#registro" class="footer-link">Crear cuenta</a>
      </div>
    </div>
    <div class="footer-bottom">
      © 2025 PractiMach · Desarrollado con Laravel & MySQL
    </div>
  </footer>

  <script src="js/main.js"></script>
</body>
</html>
