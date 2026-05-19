<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();

if (isLoggedIn()) {
    redirect(isAdmin() ? '/admin/index.php' : '/cliente/catalogo.php');
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']     ?? '');
    $correo    = trim($_POST['correo']     ?? '');
    $pass      = trim($_POST['contrasena'] ?? '');
    $confirmar = trim($_POST['confirmar']  ?? '');

    if (!$nombre || !$correo || !$pass || !$confirmar) {
        $error = 'Completa todos los campos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        // Verificar si el correo ya existe
        $check = $conn->prepare("SELECT id_usuario FROM Usuario WHERE correo = ? LIMIT 1");
        $check->bind_param('s', $correo);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Este correo ya está registrado.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO Usuario (nombre, correo, contrasena, rol) VALUES (?, ?, ?, 'cliente')");
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            if ($stmt->execute()) {
                $success = 'Cuenta creada exitosamente. Ahora puedes iniciar sesión.';
            } else {
                $error = 'Error al crear la cuenta. Inténtalo de nuevo.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear Cuenta — GameZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-box" style="max-width:460px">
        <!-- Logo -->
        <div class="text-center mb-4">
            <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">
                <span style="font-family:'Rajdhani',sans-serif;font-size:2rem;font-weight:700;color:#f1f5f9">
                    GAME<span style="color:#7c3aed">ZONE</span>
                </span>
            </a>
            <p class="mt-1 mb-0" style="color:#94a3b8;font-size:.9rem">Crea tu cuenta de gamer</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-gaming-danger mb-3 alert-auto">
                <i class="fas fa-exclamation-circle me-2"></i><?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-gaming-success mb-3">
                <i class="fas fa-check-circle me-2"></i><?= sanitize($success) ?>
                <br><a href="<?= BASE_URL ?>/auth/login.php" style="color:#4ade80;font-weight:600">Ir al login →</a>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" onsubmit="return validateForm('regForm')" id="regForm">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-user me-1"></i> Nombre completo</label>
                <input type="text" name="nombre" class="form-control form-control-gaming"
                       placeholder="Tu nombre" required
                       value="<?= sanitize($_POST['nombre'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="fas fa-envelope me-1"></i> Correo electrónico</label>
                <input type="email" name="correo" class="form-control form-control-gaming"
                       placeholder="tu@correo.com" required
                       value="<?= sanitize($_POST['correo'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="fas fa-lock me-1"></i> Contraseña</label>
                <input type="password" name="contrasena" class="form-control form-control-gaming"
                       placeholder="Mínimo 6 caracteres" required minlength="6">
                <div class="form-text" style="color:#64748b;font-size:.78rem">Mínimo 6 caracteres</div>
            </div>

            <div class="mb-4">
                <label class="form-label"><i class="fas fa-lock me-1"></i> Confirmar contraseña</label>
                <input type="password" name="confirmar" class="form-control form-control-gaming"
                       placeholder="Repite tu contraseña" required>
            </div>

            <button type="submit" class="btn btn-gaming w-100">
                <i class="fas fa-user-plus me-2"></i>Crear Cuenta
            </button>
        </form>
        <?php endif; ?>

        <hr style="border-color:#1e293b;margin:1.5rem 0">

        <p class="text-center mb-0" style="color:#94a3b8;font-size:.9rem">
            ¿Ya tienes cuenta?
            <a href="<?= BASE_URL ?>/auth/login.php" class="fw-semibold" style="color:#7c3aed">Iniciar Sesión</a>
        </p>
        <p class="text-center mt-2">
            <a href="<?= BASE_URL ?>/index.php" style="color:#94a3b8;font-size:.85rem">
                <i class="fas fa-arrow-left me-1"></i>Volver al inicio
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
