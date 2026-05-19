<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();

if (isLoggedIn()) {
    redirect(isAdmin() ? '/admin/index.php' : '/cliente/catalogo.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $pass   = trim($_POST['contrasena'] ?? '');

    if (!$correo || !$pass) {
        $error = 'Completa todos los campos.';
    } else {
        $stmt = $conn->prepare("SELECT id_usuario, nombre, contrasena, rol FROM Usuario WHERE correo = ? LIMIT 1");
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($pass, $user['contrasena'])) {
            // Generar código 2FA
            $code = generateCode();
            $stmt2 = $conn->prepare("UPDATE Usuario SET codigo_2fa = ?, estado_2fa = 0 WHERE id_usuario = ?");
            $stmt2->bind_param('si', $code, $user['id_usuario']);
            $stmt2->execute();
            $stmt2->close();

            // Guardar en sesión temporal
            $_SESSION['pre_2fa_id']     = $user['id_usuario'];
            $_SESSION['pre_2fa_nombre'] = $user['nombre'];
            $_SESSION['pre_2fa_rol']    = $user['rol'];
            $_SESSION['pre_2fa_code']   = $code;

            redirect('/auth/verificar_2fa.php');
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión — GameZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-box">
        <!-- Logo -->
        <div class="text-center mb-4">
            <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">
                <span style="font-family:'Rajdhani',sans-serif;font-size:2rem;font-weight:700;color:#f1f5f9">
                    GAME<span style="color:#7c3aed">ZONE</span>
                </span>
            </a>
            <p class="mt-1 mb-0" style="color:#94a3b8;font-size:.9rem">Inicia sesión en tu cuenta</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-gaming-danger mb-3 alert-auto">
                <i class="fas fa-exclamation-circle me-2"></i><?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" onsubmit="return validateForm('loginForm')" id="loginForm">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-envelope me-1"></i> Correo electrónico</label>
                <input type="email" name="correo" class="form-control form-control-gaming"
                       placeholder="tu@correo.com" required
                       value="<?= sanitize($_POST['correo'] ?? '') ?>">
            </div>

            <div class="mb-4">
                <label class="form-label"><i class="fas fa-lock me-1"></i> Contraseña</label>
                <input type="password" name="contrasena" class="form-control form-control-gaming"
                       placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-gaming w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
            </button>
        </form>

        <hr style="border-color:#1e293b;margin:1.5rem 0">

        <p class="text-center mb-0" style="color:#94a3b8;font-size:.9rem">
            ¿No tienes cuenta?
            <a href="<?= BASE_URL ?>/auth/registro.php" class="fw-semibold" style="color:#7c3aed">Regístrate</a>
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
