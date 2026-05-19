<?php
// =====================================================
// Configuración de Base de Datos
// =====================================================

define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tienda_gaming');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8">
    <title>Error de Conexión</title>
    <style>
        body{background:#0a0a1a;color:#e2e8f0;font-family:sans-serif;display:flex;
             align-items:center;justify-content:center;min-height:100vh;margin:0}
        .box{background:#1a1a2e;border:1px solid #7c3aed;padding:2rem;border-radius:12px;max-width:500px}
        h2{color:#f87171}code{color:#06b6d4}
    </style></head><body>
    <div class="box">
        <h2>&#9888; Error de Conexión</h2>
        <p>No se pudo conectar a la base de datos <code>tienda_gaming</code>.</p>
        <p>Verifica que:</p>
        <ul>
            <li>XAMPP esté corriendo (Apache + MySQL)</li>
            <li>Hayas importado <code>database/tienda_gaming.sql</code> en phpMyAdmin</li>
            <li>Hayas ejecutado <code>setup.php</code> para crear el administrador</li>
        </ul>
        <small style="color:#64748b">Error: ' . htmlspecialchars($conn->connect_error) . '</small>
    </div></body></html>');
}

$conn->set_charset('utf8mb4');

// ── URL base automática ─────────────────────────────
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot   = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? getcwd()));
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

if ($docRoot && strpos($projectRoot, $docRoot) === 0) {
    $relPath = substr($projectRoot, strlen($docRoot));
    $relPath = str_replace(' ', '%20', $relPath);
} else {
    $relPath = '';
}

define('BASE_URL', $protocol . '://' . $host . $relPath);

// ── Helpers ─────────────────────────────────────────
function session_start_once() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function isAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

function sanitize($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function generateCode() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function formatPrecio($precio) {
    return '$' . number_format($precio, 2);
}
