<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();

// Si no hay sesión de pre-2FA, redirigir al login
if (!isset($_SESSION['pre_2fa_id'])) {
    redirect('/auth/login.php');
}

$error   = '';
$userId  = $_SESSION['pre_2fa_id'];
$nombre  = $_SESSION['pre_2fa_nombre'];
$rol     = $_SESSION['pre_2fa_rol'];
$code    = $_SESSION['pre_2fa_code'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingresado = trim(str_replace(' ', '', $_POST['codigo'] ?? ''));

    if (!$ingresado) {
        $error = 'Ingresa el código de verificación.';
    } elseif ($ingresado !== $code) {
        $error = 'Código incorrecto. Inténtalo de nuevo.';
    } else {
        // Código correcto — marcar 2FA como completado
        $stmt = $conn->prepare("UPDATE Usuario SET estado_2fa = 1, codigo_2fa = NULL WHERE id_usuario = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        // Limpiar sesión temporal y crear sesión completa
        unset($_SESSION['pre_2fa_id'], $_SESSION['pre_2fa_nombre'],
              $_SESSION['pre_2fa_rol'], $_SESSION['pre_2fa_code']);

        $_SESSION['usuario_id']     = $userId;
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_rol']    = $rol;

        redirect($rol === 'admin' ? '/admin/index.php' : '/cliente/catalogo.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación 2FA — GameZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-box text-center" style="max-width:400px">
        <!-- Logo -->
        <span style="font-family:'Rajdhani',sans-serif;font-size:1.8rem;font-weight:700;color:#f1f5f9">
            GAME<span style="color:#7c3aed">ZONE</span>
        </span>

        <div class="mt-3 mb-1">
            <div style="width:56px;height:56px;border-radius:50%;background:rgba(245,158,11,.15);border:2px solid rgba(245,158,11,.4);
                        display:flex;align-items:center;justify-content:center;margin:0 auto">
                <i class="fas fa-shield-alt" style="color:#f59e0b;font-size:1.5rem"></i>
            </div>
        </div>

        <h5 class="mt-3 mb-1" style="font-family:'Rajdhani',sans-serif">Verificación en 2 pasos</h5>
        <p style="color:#94a3b8;font-size:.9rem">Hola, <strong style="color:#e2e8f0"><?= sanitize($nombre) ?></strong></p>

        <!-- Código simulado visible en pantalla -->
        <div class="mb-3">
            <p style="color:#94a3b8;font-size:.82rem;margin-bottom:.3rem">
                <i class="fas fa-info-circle me-1"></i>
                Tu código de verificación es:
            </p>
            <div class="code-display"><?= htmlspecialchars($code) ?></div>
            <p style="color:#64748b;font-size:.75rem">
                (En producción este código se enviaría a tu correo)
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert-gaming-danger mb-3 alert-auto text-start">
                <i class="fas fa-exclamation-circle me-2"></i><?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" id="tfaForm" onsubmit="return validateForm('tfaForm')">
            <div class="mb-3 text-start">
                <label class="form-label">
                    <i class="fas fa-key me-1"></i> Ingresa el código de 6 dígitos
                </label>
                <input type="text" name="codigo" class="form-control form-control-gaming text-center"
                       placeholder="0 0 0 0 0 0"
                       maxlength="6"
                       style="font-size:1.4rem;letter-spacing:.4em;font-family:'Rajdhani',sans-serif"
                       autocomplete="one-time-code" required>
            </div>

            <button type="submit" class="btn btn-gaming w-100">
                <i class="fas fa-check-circle me-2"></i>Verificar y Entrar
            </button>
        </form>

        <hr style="border-color:#1e293b;margin:1.5rem 0">
        <a href="<?= BASE_URL ?>/auth/login.php" style="color:#94a3b8;font-size:.85rem">
            <i class="fas fa-arrow-left me-1"></i>Volver al login
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
    // Auto-submit cuando se ingresan 6 dígitos
    document.querySelector('[name="codigo"]').addEventListener('input', function() {
        if (this.value.replace(/\s/g,'').length === 6) {
            this.closest('form').submit();
        }
    });
</script>
</body>
</html>
