<?php
$host = "localhost";
$user = "root";
$pass = "";

// 1. Connect to MySQL server
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create Database
$sql = "CREATE DATABASE IF NOT EXISTS practimach_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database 'practimach_db' checked/created successfully.<br>\n";
} else {
    die("Error creating database: " . $conn->error);
}

// 3. Select Database
$conn->select_db("practimach_db");

// 4. Create Tables

// Admins
$sql_admins = "CREATE TABLE IF NOT EXISTS admins (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('superadmin','admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Estudiantes
$sql_estudiantes = "CREATE TABLE IF NOT EXISTS estudiantes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    carrera VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Empresas
$sql_empresas = "CREATE TABLE IF NOT EXISTS empresas (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(200) NOT NULL,
    ruc VARCHAR(20) NOT NULL UNIQUE,
    sector VARCHAR(100),
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    estado ENUM('validada', 'revision', 'bloqueada') DEFAULT 'revision',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Matches (Relación Estudiante - Empresa)
// Podría ser postulación o match directo. Asumiremos un flujo tipo Match/Postulación
$sql_matches = "CREATE TABLE IF NOT EXISTS matches (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT(11) NOT NULL,
    empresa_id INT(11) NOT NULL,
    estado ENUM('pendiente', 'aceptado', 'rechazado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
)";

$tables = [
    'admins' => $sql_admins,
    'estudiantes' => $sql_estudiantes,
    'empresas' => $sql_empresas,
    'matches' => $sql_matches
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table '$name' checked/created successfully.<br>\n";
    } else {
        echo "Error creating table '$name': " . $conn->error . "<br>\n";
    }
}

// 5. Insert Default Admin if not exists
$admin_email = 'admin@practimach.com';
$check_admin = $conn->query("SELECT * FROM admins WHERE email = '$admin_email'");
if ($check_admin->num_rows == 0) {
    // Password: admin123 (hashed if you were using password_hash, but admins.sql showed plain text or simple hash, I will use password_hash for security)
    // Wait, admins.sql used 'admin123' plain text. For security I should hash, but to be consistent with existing pattern or to improve it?
    // The user asked to make it *run*. I should improve it. I'll use password_hash.
    $pass_hash = password_hash("admin123", PASSWORD_DEFAULT);
    $sql_insert_admin = "INSERT INTO admins (nombre, email, password, rol) VALUES ('Admin Principal', '$admin_email', '$pass_hash', 'superadmin')";
    
    if ($conn->query($sql_insert_admin) === TRUE) {
        echo "Default admin user created (admin@practimach.com / admin123).<br>\n";
    } else {
        echo "Error creating default admin: " . $conn->error . "<br>\n";
    }
} else {
    echo "Default admin already exists.<br>\n";
}

$conn->close();
echo "Database setup completed.\n";
?>
