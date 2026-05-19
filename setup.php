<?php
// =====================================================
// SETUP INICIAL — Crear usuario administrador
// IMPORTANTE: Elimina este archivo después de usarlo.
// =====================================================
require_once __DIR__ . '/config/db.php';

$creado = false;
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? 'Administrador');
    $correo = trim($_POST['correo'] ?? '');
    $pass   = trim($_POST['pass']   ?? '');

    if (!$nombre || !$correo || !$pass) {
        $error = 'Completa todos los campos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo inválido.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO Usuario (nombre, correo, contrasena, rol) VALUES (?,?,?,'admin')");
        $stmt->bind_param('sss', $nombre, $correo, $hash);
        if ($stmt->execute()) {
            $creado = true;
        } else {
            $error = 'El correo ya está registrado o hubo un error: ' . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup – Tienda Gaming</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #0a0a1a; color: #e2e8f0; font-family: sans-serif; }
        .card { background: #1a1a2e; border: 1px solid #7c3aed; }
        h1 span { color: #7c3aed; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
<div class="card p-4" style="max-width:420px;width:100%">
    <h1 class="h4 mb-1">&#9881; Setup <span>Tienda Gaming</span></h1>
    <p class="text-secondary small mb-3">Crea el usuario administrador. <strong>Elimina este archivo después.</strong></p>

    <?php if ($creado): ?>
        <div class="alert alert-success">
            &#10003; Administrador creado correctamente.<br>
            <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-sm mt-2" style="background:#7c3aed;color:#fff">Ir al Login</a>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control bg-dark text-light border-secondary"
                       value="Administrador">
            </div>
            <div class="mb-3">
                <label class="form-label">Correo</label>
                <input type="email" name="correo" class="form-control bg-dark text-light border-secondary"
                       placeholder="admin@gaming.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="pass" class="form-control bg-dark text-light border-secondary"
                       placeholder="Mínimo 6 caracteres">
            </div>
            <button type="submit" class="btn w-100" style="background:#7c3aed;color:#fff">
                Crear Administrador
            </button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
