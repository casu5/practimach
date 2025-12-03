# 📘 Guía Completa del Proyecto PractiMach

> **Documentación técnica detallada** del sistema de gestión de prácticas preprofesionales PractiMach.
> 
> **Última actualización:** 27 de Noviembre de 2025

---

## 📑 Tabla de Contenidos

1. [Visión General del Proyecto](#1-visión-general-del-proyecto)
2. [Arquitectura del Sistema](#2-arquitectura-del-sistema)
3. [Base de Datos](#3-base-de-datos)
4. [Flujo de Autenticación](#4-flujo-de-autenticación)
5. [Sistema de Matching (Tinder)](#5-sistema-de-matching-tinder)
6. [Gestión de Perfiles](#6-gestión-de-perfiles)
7. [Panel de Administración](#7-panel-de-administración)
8. [Estructura de Archivos](#8-estructura-de-archivos)
9. [Flujos de Interacción Detallados](#9-flujos-de-interacción-detallados)
10. [Problemas Comunes y Soluciones](#10-problemas-comunes-y-soluciones)

---

## 1. Visión General del Proyecto

### 🎯 Objetivo
PractiMach es una plataforma web que conecta a **estudiantes** con **empresas** para facilitar la gestión de prácticas preprofesionales. Utiliza un sistema de "matching" similar a Tinder donde estudiantes y empresas pueden dar "like" o "dislike" mutuamente.

### 👥 Roles del Sistema
- **Estudiantes**: Buscan oportunidades de prácticas preprofesionales
- **Empresas**: Buscan talento joven para sus vacantes
- **Administradores**: Gestionan el sistema completo (usuarios, matches, configuración)

### 🛠 Stack Tecnológico
- **Frontend**: HTML5, CSS3 (Vanilla), JavaScript (Vanilla)
- **Backend**: PHP 8.2.12 (Procedural/OOP híbrido)
- **Base de Datos**: MySQL/MariaDB 10.4.32
- **Servidor**: XAMPP (Apache)
- **Fuentes**: Google Fonts (Poppins)

---

## 2. Arquitectura del Sistema

### 🏗 Patrón Arquitectónico
El proyecto sigue una arquitectura **monolítica tradicional LAMP** con separación de responsabilidades:

```
┌─────────────────────────────────────────────┐
│           NAVEGADOR (Cliente)               │
│  - Renderiza HTML                           │
│  - Ejecuta JavaScript (main.js)             │
│  - Aplica estilos (estilos.css)             │
└──────────────┬──────────────────────────────┘
               │ HTTP Requests
               ↓
┌─────────────────────────────────────────────┐
│        SERVIDOR WEB (Apache)                │
│  - Procesa archivos .php                    │
│  - Sirve archivos estáticos                 │
└──────────────┬──────────────────────────────┘
               │
               ↓
┌─────────────────────────────────────────────┐
│         CAPA DE APLICACIÓN (PHP)            │
│  ┌───────────────────────────────────────┐  │
│  │ Páginas de Vista (.php con HTML)     │  │
│  │ - auth.php, index.php, perfiles, etc │  │
│  └───────────────────────────────────────┘  │
│  ┌───────────────────────────────────────┐  │
│  │ Lógica de Negocio (.php)              │  │
│  │ - auth_actions.php                    │  │
│  │ - process_swipe.php                   │  │
│  │ - process_swipe_student.php           │  │
│  └───────────────────────────────────────┘  │
│  ┌───────────────────────────────────────┐  │
│  │ Configuración                         │  │
│  │ - config/config/conexion.php          │  │
│  └───────────────────────────────────────┘  │
└──────────────┬──────────────────────────────┘
               │ MySQLi
               ↓
┌─────────────────────────────────────────────┐
│      BASE DE DATOS (MySQL/MariaDB)          │
│  - practimach_db                            │
│    • admins                                 │
│    • estudiantes                            │
│    • empresas                               │
│    • matches                                │
└─────────────────────────────────────────────┘
```

### 🔐 Gestión de Sesiones
El sistema utiliza **sesiones PHP nativas** para mantener el estado del usuario:

```php
// Se inicia en cada archivo que requiere autenticación
session_start();

// Variables de sesión utilizadas:
$_SESSION['user_id']    // ID del usuario autenticado
$_SESSION['user_role']  // Rol: 'estudiante', 'empresa', 'superadmin', 'admin'
$_SESSION['user_name']  // Nombre para mostrar en la interfaz
```

---

## 3. Base de Datos

### 📊 Esquema de Base de Datos

#### Tabla: `admins`
```sql
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,        -- Hash bcrypt
  `rol` enum('superadmin','admin') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
)
```

**Campos clave:**
- `rol`: Diferencia entre superadmin (control total) y admin (limitado)
- `password`: **DEBE estar hasheado con `password_hash()`**

#### Tabla: `estudiantes`
```sql
CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `dni` varchar(20) NOT NULL UNIQUE,
  `carrera` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,        -- Hash bcrypt
  `foto` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
)
```

**Campos clave:**
- `dni`: Único, identifica al estudiante
- `carrera`: Una de las 9 carreras disponibles en el sistema
- `foto` y `descripcion`: Opcionales, para el perfil

#### Tabla: `empresas`
```sql
CREATE TABLE `empresas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(200) NOT NULL,
  `ruc` varchar(20) NOT NULL UNIQUE,
  `sector` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,        -- Hash bcrypt
  `foto` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('validada','revision','bloqueada') DEFAULT 'revision',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
)
```

**Campos clave:**
- `ruc`: Único, identifica a la empresa
- `estado`: 
  - `revision`: Nueva empresa, pendiente de validación
  - `validada`: Empresa aprobada, puede usar el sistema
  - `bloqueada`: Empresa suspendida

#### Tabla: `matches`
```sql
CREATE TABLE `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `estado` enum('estudiante_gusta','empresa_gusta','match','rechazado') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_match_pair` (`estudiante_id`, `empresa_id`),
  FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
)
```

**Campos clave:**
- `estado`: Sistema de estados del matching
  - `estudiante_gusta`: Solo el estudiante dio like
  - `empresa_gusta`: Solo la empresa dio like
  - `match`: **Ambos dieron like** ❤️
  - `rechazado`: Al menos uno dio dislike
- **Restricción UNIQUE**: Solo puede existir UN registro por par (estudiante, empresa)

### 🔄 Lógica de Estados del Matching

```
ESTADO INICIAL: No existe registro en tabla matches
                        |
                        ↓
        ┌───────────────┴──────────────┐
        │                               │
    Estudiante da LIKE            Empresa da LIKE
        │                               │
        ↓                               ↓
  estudiante_gusta                empresa_gusta
        │                               │
        ↓                               ↓
    Si la empresa da LIKE       Si el estudiante da LIKE
        │                               │
        └───────────────┬───────────────┘
                        ↓
                    🎉 MATCH 🎉
                        
Si cualquiera da DISLIKE → rechazado
```

---

## 4. Flujo de Autenticación

### 📄 Archivos Involucrados
- `auth.php` - Interfaz de login/registro
- `js/main.js` - Lógica JavaScript del frontend
- `auth_actions.php` - Procesa login/registro en backend
- `config/config/conexion.php` - Conexión a la base de datos

### 🔐 Flujo de Login (Línea por Línea)

#### **PASO 1: Usuario ingresa credenciales en `auth.php`**

```html
<!-- Líneas 40-56 de auth.php -->
<form class="auth-form" id="formLogin">
  <input type="email" id="loginEmail" placeholder="tucorreo@instituto.edu.pe" required>
  <input type="password" id="loginPassword" placeholder="••••••••" required>
  <button type="submit">Ingresar</button>
</form>
```

#### **PASO 2: JavaScript captura el submit en `js/main.js`**

```javascript
// Líneas 116-141 de main.js

// 1. Captura el evento submit del formulario
formLogin.addEventListener('submit', async (e) => {
  e.preventDefault(); // Evita el envío tradicional del formulario
  
  // 2. Obtiene los valores ingresados
  const email = document.getElementById('loginEmail').value;
  const password = document.getElementById('loginPassword').value;
  
  // 3. Determina el rol según el toggle de la UI
  const isEmpresa = roleToggle && roleToggle.checked;
  const role = isEmpresa ? 'empresa' : 'estudiante';
  
  // 4. Construye el objeto de datos
  const data = {
    action: 'login',
    email: email,
    password: password,
    role: role
  };
  
  // 5. Envía los datos al servidor
  const res = await sendAuthRequest(data);
  
  // 6. Procesa la respuesta
  if (res.success) {
    window.location.href = res.redirect; // Redirige según el rol
  } else {
    alert(res.message); // Muestra el error
  }
});
```

#### **PASO 3: Función `sendAuthRequest()` hace la petición AJAX**

```javascript
// Líneas 100-114 de main.js

async function sendAuthRequest(data) {
  try {
    // 1. Hace una petición POST a auth_actions.php
    const response = await fetch('auth_actions.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json' // Importante: indica que enviamos JSON
      },
      body: JSON.stringify(data) // Convierte el objeto a JSON
    });
    
    // 2. Parsea la respuesta JSON
    return await response.json();
  } catch (error) {
    console.error('Error:', error);
    return { success: false, message: 'Error de conexión' };
  }
}
```

#### **PASO 4: PHP procesa el login en `auth_actions.php`**

```php
// Líneas 1-71 de auth_actions.php

<?php
// 1. Inicia la sesión para poder usar $_SESSION
session_start();

// 2. Incluye la conexión a la base de datos
require_once 'config/config/conexion.php';

// 3. Indica que la respuesta será JSON
header('Content-Type: application/json');

// 4. Lee el JSON enviado desde JavaScript
$input = json_decode(file_get_contents('php://input'), true);

// 5. Extrae los datos
$action = $input['action'] ?? ''; // 'login' o 'register'

if ($action === 'login') {
    // 6. Sanitiza el email para prevenir SQL injection
    $email = $mysqli->real_escape_string($input['email']);
    $password = $input['password']; // La contraseña NO se sanitiza (se verifica con password_verify)
    $role = $input['role'] ?? ''; // 'estudiante', 'empresa', 'admin'
    
    // 7. Determina qué tabla consultar según el rol
    $table = '';
    $redirect = '';
    $name_field = '';
    
    if ($role === 'estudiante') {
        $table = 'estudiantes';
        $redirect = 'perfil_estudiante.php';
        $name_field = 'nombre';
    } elseif ($role === 'empresa') {
        $table = 'empresas';
        $redirect = 'perfil_empresa.php';
        $name_field = 'razon_social';
    } elseif ($role === 'admin') {
        $table = 'admins';
        $redirect = 'dashboard_admin.php';
        $name_field = 'nombre';
    }
    
    // 8. Prepara la consulta SQL según el tipo de usuario
    // IMPORTANTE: Solo admins tienen columna 'rol'
    if ($table === 'admins') {
        $stmt = $mysqli->prepare("SELECT id, password, " . $name_field . " AS user_name, rol FROM " . $table . " WHERE email = ?");
    } else {
        $stmt = $mysqli->prepare("SELECT id, password, " . $name_field . " AS user_name FROM " . $table . " WHERE email = ?");
    }
    
    // 9. Valida que la consulta se preparó correctamente
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta: ' . $mysqli->error]);
        exit;
    }
    
    // 10. Ejecuta la consulta
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // 11. Verifica si encontró el usuario
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 12. Verifica la contraseña usando password_verify()
        // Compara la contraseña en texto plano con el hash almacenado
        if (password_verify($password, $user['password'])) {
            // ✅ CONTRASEÑA CORRECTA
            
            // 13. Guarda la información en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = ($role === 'admin') ? $user['rol'] : $role;
            $_SESSION['user_name'] = $user['user_name'];
            
            // 14. Responde con éxito y la URL de redirección
            echo json_encode(['success' => true, 'redirect' => $redirect]);
            exit;
        } else {
            // ❌ CONTRASEÑA INCORRECTA
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
            exit;
        }
    } else {
        // ❌ USUARIO NO ENCONTRADO
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }
}
?>
```

### 📝 Flujo de Registro

El registro sigue un flujo similar pero con validaciones adicionales:

```php
// Líneas 74-133 de auth_actions.php

elseif ($action === 'register') {
    $role = $input['role']; // 'estudiante' o 'empresa'
    $email = $mysqli->real_escape_string($input['email']);
    
    // 1. Hashea la contraseña ANTES de guardarla
    $password = password_hash($input['password'], PASSWORD_DEFAULT);
    
    if ($role === 'estudiante') {
        // 2. Extrae datos específicos de estudiante
        $nombre = $mysqli->real_escape_string($input['nombre']);
        $dni = $mysqli->real_escape_string($input['dni']);
        $carrera = $mysqli->real_escape_string($input['carrera']);
        
        // 3. Verifica duplicados (email o DNI)
        $check = $mysqli->query("SELECT id FROM estudiantes WHERE email='$email' OR dni='$dni'");
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'El correo o DNI ya están registrados.']);
            exit;
        }
        
        // 4. Inserta el nuevo estudiante
        $sql = "INSERT INTO estudiantes (nombre, dni, carrera, email, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssss", $nombre, $dni, $carrera, $email, $password);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Estudiante registrado exitosamente.'];
        } else {
            $response = ['success' => false, 'message' => 'Error en el registro: ' . $stmt->error];
        }
    } 
    elseif ($role === 'empresa') {
        // Similar para empresas...
        // Nota: El estado inicial es 'revision'
        $sql = "INSERT INTO empresas (razon_social, ruc, sector, email, password, estado) VALUES (?, ?, ?, ?, ?, 'revision')";
    }
    
    echo json_encode($response);
}
```

---

## 5. Sistema de Matching (Tinder)

### 🎴 Archivos Involucrados
- `estudiante_tinder.php` - Vista para estudiantes
- `empresa_tinder.php` - Vista para empresas
- `process_swipe_student.php` - Procesa swipes de estudiantes
- `process_swipe.php` - Procesa swipes de empresas

### 🔄 Flujo Completo del Matching

#### **Vista del Estudiante (`estudiante_tinder.php`)**

**1. Carga de perfiles de empresas**
```php
// Líneas 15-40 (aproximado)

// Obtiene empresas que:
// - Estén validadas
// - NO tengan un match/rechazo previo con este estudiante
$sql = "SELECT e.id, e.razon_social, e.sector, e.foto, e.descripcion 
        FROM empresas e
        WHERE e.estado = 'validada'
        AND e.id NOT IN (
            SELECT empresa_id 
            FROM matches 
            WHERE estudiante_id = ? 
            AND estado IN ('match', 'rechazado')
        )
        ORDER BY RAND()  -- Orden aleatorio
        LIMIT 20";        -- Máximo 20 perfiles
```

**2. Renderizado de tarjetas**
```php
<?php while($empresa = $result->fetch_assoc()): ?>
<div class="card" data-empresa-id="<?php echo $empresa['id']; ?>">
    <img src="<?php echo $empresa['foto'] ?? 'assets/img/default-company.png'; ?>">
    <h2><?php echo htmlspecialchars($empresa['razon_social']); ?></h2>
    <p><?php echo htmlspecialchars($empresa['sector']); ?></p>
</div>
<?php endwhile; ?>
```

**3. JavaScript maneja los swipes**
```javascript
// Pseudo-código del comportamiento esperado

let currentCard = document.querySelector('.card.active');

// Al hacer clic en botón de LIKE
btnLike.addEventListener('click', () => {
    const empresaId = currentCard.dataset.empresaId;
    
    // Envía el like al servidor
    fetch('process_swipe_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            empresa_id: empresaId,
            action: 'like'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.message === '¡Match confirmado!') {
                showMatchAnimation(); // 🎉
            }
            showNextCard();
        }
    });
});
```

#### **Backend: `process_swipe_student.php`**

```php
// Flujo completo línea por línea

<?php
session_start();
require_once 'config/config/conexion.php';
header('Content-Type: application/json');

// 1. Verifica autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$estudiante_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$empresa_id = $input['empresa_id'] ?? null;
$action = $input['action'] ?? null; // 'like' o 'reject'

// 2. Valida datos
if (!$empresa_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

// 3. Busca si ya existe un registro para este par
$stmt_find = $mysqli->prepare("SELECT estado FROM matches WHERE estudiante_id = ? AND empresa_id = ?");
$stmt_find->bind_param("ii", $estudiante_id, $empresa_id);
$stmt_find->execute();
$res_find = $stmt_find->get_result();
$existing_match = $res_find->fetch_assoc();

// 4. Procesamiento según la acción
if ($action === 'like') {
    if ($existing_match) {
        // YA EXISTE UN REGISTRO
        
        $current_estado = $existing_match['estado'];
        $new_estado = 'estudiante_gusta'; // Por defecto
        
        // 5. Lógica de transición de estados
        if ($current_estado === 'empresa_gusta') {
            // ¡La empresa ya había dado like!
            $new_estado = 'match'; // 🎉 MATCH!
        } elseif ($current_estado === 'rechazado') {
            // Alguien había rechazado antes, ahora estudiante da like
            $new_estado = 'estudiante_gusta';
        }
        
        // 6. Actualiza el estado
        $stmt_update = $mysqli->prepare("UPDATE matches SET estado = ? WHERE estudiante_id = ? AND empresa_id = ?");
        $stmt_update->bind_param("sii", $new_estado, $estudiante_id, $empresa_id);
        
        if ($stmt_update->execute()) {
            $message = ($new_estado === 'match') ? '¡Match confirmado!' : 'Like registrado.';
            $response = ['success' => true, 'message' => $message];
        }
        
    } else {
        // NO EXISTE REGISTRO, es el primero en dar like
        
        $new_estado = 'estudiante_gusta';
        $stmt_insert = $mysqli->prepare("INSERT INTO matches (estudiante_id, empresa_id, estado) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iis", $estudiante_id, $empresa_id, $new_estado);
        
        if ($stmt_insert->execute()) {
            $response = ['success' => true, 'message' => 'Like registrado.'];
        }
    }
} elseif ($action === 'reject') {
    // Similar lógica pero con estado 'rechazado'
}

echo json_encode($response);
$mysqli->close();
?>
```

### 📊 Tabla de Transiciones de Estado

| Estado Actual | Acción | Usuario | Nuevo Estado | ¿Notificar? |
|--------------|--------|---------|--------------|-------------|
| (no existe) | LIKE | Estudiante | `estudiante_gusta` | No |
| (no existe) | LIKE | Empresa | `empresa_gusta` | No |
| `estudiante_gusta` | LIKE | Empresa | **`match`** | ✅ Sí |
| `empresa_gusta` | LIKE | Estudiante | **`match`** | ✅ Sí |
| `estudiante_gusta` | REJECT | Empresa | `rechazado` | No |
| `empresa_gusta` | REJECT | Estudiante | `rechazado` | No |
| `rechazado` | LIKE | Cualquiera | `estudiante_gusta` o `empresa_gusta` | No |
| `match` | (cualquier acción) | Cualquiera | `match` (no cambia) | No |

---

## 6. Gestión de Perfiles

### 👤 Perfil de Estudiante

#### **Visualización: `perfil_estudiante.php`**
```php
// 1. Carga datos del estudiante autenticado
$estudiante_id = $_SESSION['user_id'];

$sql = "SELECT * FROM estudiantes WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $estudiante_id);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();

// 2. Muestra la información
?>
<div class="profile-header">
    <img src="<?php echo $estudiante['foto'] ?? 'assets/img/default-user.png'; ?>">
    <h1><?php echo htmlspecialchars($estudiante['nombre']); ?></h1>
    <p><?php echo htmlspecialchars($estudiante['carrera']); ?></p>
</div>
```

#### **Edición: `perfil_estudiante_editar.php`**
```php
// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Procesa la imagen si se subió
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'estudiante_' . $estudiante_id . '_' . time() . '.' . $ext;
            $upload_path = 'assets/img/' . $new_filename;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                $foto = $upload_path;
            }
        }
    }
    
    // 2. Actualiza la base de datos
    $sql = "UPDATE estudiantes SET 
            nombre = ?, 
            descripcion = ?, 
            foto = ? 
            WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $descripcion, $foto, $estudiante_id);
    $stmt->execute();
    
    // 3. Redirige al perfil
    header("Location: perfil_estudiante.php");
}
```

---

## 7. Panel de Administración

### 🎛 Dashboard (`dashboard_admin.php`)

**Estadísticas en tiempo real:**
```php
// 1. Total de estudiantes
$total_estudiantes = $mysqli->query("SELECT COUNT(*) as total FROM estudiantes")->fetch_assoc()['total'];

// 2. Total de empresas validadas
$total_empresas = $mysqli->query("SELECT COUNT(*) as total FROM empresas WHERE estado='validada'")->fetch_assoc()['total'];

// 3. Total de matches confirmados
$total_matches = $mysqli->query("SELECT COUNT(*) as total FROM matches WHERE estado='match'")->fetch_assoc()['total'];

// 4. Empresas pendientes de validación
$empresas_pendientes = $mysqli->query("SELECT COUNT(*) as total FROM empresas WHERE estado='revision'")->fetch_assoc()['total'];
```

### 📋 Gestión de Empresas (`admin_empresas.php`)

**Validación de empresas:**
```php
// Endpoint para cambiar estado de empresa
if (isset($_POST['validar_empresa'])) {
    $empresa_id = $_POST['empresa_id'];
    $nuevo_estado = $_POST['nuevo_estado']; // 'validada', 'bloqueada'
    
    $stmt = $mysqli->prepare("UPDATE empresas SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $empresa_id);
    
    if ($stmt->execute()) {
        $mensaje = "Empresa " . ($nuevo_estado === 'validada' ? 'validada' : 'bloqueada') . " correctamente.";
    }
}
```

---

## 8. Estructura de Archivos

```
practiimachfront/
│
├── 📁 assets/
│   └── 📁 img/
│       ├── default-user.png
│       └── default-company.png
│
├── 📁 config/
│   └── 📁 config/
│       └── conexion.php              # ⚙️ Configuración de BD
│
├── 📁 css/
│   └── estilos.css                   # 🎨 Estilos globales
│
├── 📁 js/
│   └── main.js                       # ⚡ Lógica JavaScript
│
├── 📄 index.php                      # 🏠 Landing page
├── 📄 auth.php                       # 🔐 Login/Registro
├── 📄 auth_actions.php               # 🔐 Procesamiento auth
│
├── 👤 ESTUDIANTE:
├── 📄 perfil_estudiante.php          # Perfil
├── 📄 perfil_estudiante_editar.php   # Editar perfil
├── 📄 estudiante_tinder.php          # Swipe de empresas
├── 📄 matches.php                    # Matches confirmados
├── 📄 historial_likes.php            # Likes dados/recibidos
├── 📄 process_swipe_student.php      # Procesa swipes
│
├── 🏢 EMPRESA:
├── 📄 perfil_empresa.php             # Perfil
├── 📄 empresa_editar.php             # Editar perfil
├── 📄 empresa_tinder.php             # Swipe de estudiantes
├── 📄 matches_empresa.php            # Matches confirmados
├── 📄 historial_likes_empresa.php    # Likes dados/recibidos
├── 📄 process_swipe.php              # Procesa swipes
│
├── 👑 ADMIN:
├── 📄 admin_login.php                # Login de admin
├── 📄 dashboard_admin.php            # Dashboard principal
├── 📄 admin_estudiantes.php          # Gestión de estudiantes
├── 📄 admin_empresas.php             # Gestión de empresas
├── 📄 admin_matches.php              # Gestión de matches
├── 📄 admin_config.php               # Configuración
│
├── 📄 logout.php                     # Cerrar sesión
├── 📄 setup_db.php                   # Instalador de BD
├── 📄 practimach_db.sql              # 💾 Base de datos
│
└── 📄 guiadeproyecto.md              # 📘 Esta guía
```

---

## 9. Flujos de Interacción Detallados

### 🔄 Flujo: Registro → Login → Match

```
1. USUARIO VISITA index.php
   ↓
2. Clic en "Crear cuenta"
   ↓
3. Redirige a auth.php#registro
   ↓
4. Llena formulario de registro
   - Nombre, DNI, Carrera, Email, Contraseña (si es estudiante)
   - O Razón Social, RUC, Sector, Email, Contraseña (si es empresa)
   ↓
5. JavaScript (main.js) captura submit
   ↓
6. Envía datos JSON a auth_actions.php
   ↓
7. auth_actions.php:
   - Hashea la contraseña con password_hash()
   - Verifica duplicados (email/DNI/RUC)
   - Inserta en la tabla correspondiente
   - Responde con success: true
   ↓
8. JavaScript muestra mensaje de éxito
   ↓
9. Cambia automáticamente a pestaña "Iniciar sesión"
   ↓
10. Usuario ingresa credenciales
   ↓
11. JavaScript envía login a auth_actions.php
   ↓
12. auth_actions.php:
    - Busca usuario por email en la tabla correspondiente
    - Verifica password con password_verify()
    - Crea sesión con $_SESSION
    - Responde con redirect URL
   ↓
13. JavaScript redirige a:
    - perfil_estudiante.php (si es estudiante)
    - perfil_empresa.php (si es empresa)
    - dashboard_admin.php (si es admin)
   ↓
14. Usuario completa su perfil (opcional)
   ↓
15. Usuario va a estudiante_tinder.php o empresa_tinder.php
   ↓
16. Ve tarjetas de perfiles
   ↓
17. Da LIKE a un perfil
   ↓
18. JavaScript envía acción a process_swipe_student.php o process_swipe.php
   ↓
19. Backend verifica si ya existe registro en matches
   ↓
20. Si el otro ya había dado LIKE:
    - Cambia estado a 'match'
    - Responde con "¡Match confirmado!"
    - Frontend muestra animación de match 🎉
   ↓
21. Usuario puede ver sus matches en matches.php o matches_empresa.php
```

### 🔍 Flujo: Administrador valida una empresa

```
1. Admin hace login en admin_login.php
   ↓
2. Redirige a dashboard_admin.php
   ↓
3. Ve estadísticas generales
   ↓
4. Clic en "Gestionar Empresas"
   ↓
5. Redirige a admin_empresas.php
   ↓
6. Consulta SQL muestra todas las empresas con filtro por estado
   ↓
7. Admin ve empresa con estado='revision'
   ↓
8. Clic en botón "Validar"
   ↓
9. Envía POST con empresa_id y nuevo_estado='validada'
   ↓
10. admin_empresas.php ejecuta UPDATE
    UPDATE empresas SET estado='validada' WHERE id=?
   ↓
11. Empresa ahora puede aparecer en el tinder de estudiantes
```

---

## 10. Problemas Comunes y Soluciones

### ❌ Error: "Error de conexión" al iniciar sesión

**Causa:** El archivo `auth_actions.php` tenía un bug donde intentaba seleccionar la columna `rol` de las tablas `estudiantes` y `empresas`, que no existe.

**Solución aplicada:**
```php
// ANTES (INCORRECTO):
$stmt = $mysqli->prepare("SELECT id, password, $name_field AS user_name, rol FROM $table WHERE email = ?");
if ($table !== 'admins') {
    $stmt = $mysqli->prepare("SELECT id, password, $name_field AS user_name FROM $table WHERE email = ?");
}

// DESPUÉS (CORRECTO):
if ($table === 'admins') {
    $stmt = $mysqli->prepare("SELECT id, password, $name_field AS user_name, rol FROM $table WHERE email = ?");
} else {
    $stmt = $mysqli->prepare("SELECT id, password, $name_field AS user_name FROM $table WHERE email = ?");
}
```

### ❌ Error: Contraseña siempre incorrecta

**Causa:** Las contraseñas en la base de datos estaban en texto plano, pero `password_verify()` espera un hash bcrypt.

**Solución:**
```php
// Script para convertir contraseñas existentes
$password_plana = 'admin123';
$password_hash = password_hash($password_plana, PASSWORD_DEFAULT);

UPDATE admins SET password='$password_hash' WHERE email='admin@practimach.com';
```

### ❌ Error: Los matches no se registran

**Causa:** La restricción `UNIQUE (estudiante_id, empresa_id)` impide duplicados, pero el código intentaba insertar en lugar de actualizar.

**Solución:** Ver archivo `PROBLEMA_MATCHES_SOLUCIONADO.md` con el fix completo.

### ❌ Error: No aparecen perfiles en el Tinder

**Causa:** La consulta SQL filtraba incorrectamente o no había perfiles que cumplieran los criterios.

**Solución:** Ver archivo `PROBLEMA_PERFILES_NO_APARECIAN.md`.

---

## 📌 Notas Importantes

### Seguridad
- ✅ **Contraseñas:** Siempre usar `password_hash()` y `password_verify()`
- ✅ **SQL Injection:** Usar prepared statements con `bind_param()`
- ✅ **XSS:** Usar `htmlspecialchars()` al mostrar datos de usuarios
- ✅ **Sesiones:** Validar `$_SESSION['user_role']` en cada página protegida

### Convenciones del Código
- **Nombres de variables PHP:** snake_case (`$estudiante_id`)
- **Nombres de clases CSS:** kebab-case (`.auth-card`)
- **Nombres de funciones JS:** camelCase (`sendAuthRequest()`)

### Performance
- Las consultas SQL usan `LIMIT` para evitar cargas masivas
- Los perfiles en Tinder se cargan con `ORDER BY RAND() LIMIT 20`
- Las imágenes deben optimizarse antes de subir

---

## 🔗 Recursos Adicionales

- **Documentación PHP:** https://www.php.net/docs.php
- **Documentación MySQL:** https://dev.mysql.com/doc/
- **MDN Web Docs:** https://developer.mozilla.org/

---

**Versión:** 1.0
**Autor:** Equipo PractiMach
**Última revisión:** 27 de Noviembre de 2025
