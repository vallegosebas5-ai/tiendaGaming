<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isLoggedIn()) redirect('/auth/login.php');
if (empty($_SESSION['carrito'])) redirect('/cliente/carrito.php');

$carrito = $_SESSION['carrito'];
$uid     = $_SESSION['usuario_id'];
$error   = '';

// Calcular total y verificar stock
$total = 0;
$items = [];
foreach ($carrito as $pid => $item) {
    $s = $conn->prepare("SELECT id_producto, nombre, precio, stock FROM Producto WHERE id_producto=? AND estado='activo'");
    $s->bind_param('i', $pid); $s->execute();
    $prod = $s->get_result()->fetch_assoc(); $s->close();
    if (!$prod) { $error = "El producto '{$item['nombre']}' ya no está disponible."; break; }
    if ($prod['stock'] < $item['qty']) { $error = "Stock insuficiente para '{$prod['nombre']}' (disponible: {$prod['stock']})."; break; }
    $subtotal  = $prod['precio'] * $item['qty'];
    $total    += $subtotal;
    $items[]   = ['prod' => $prod, 'qty' => $item['qty'], 'subtotal' => $subtotal];
}

// Confirmar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar']) && !$error) {
    // Recalcular (seguridad)
    $total2 = 0;
    foreach ($items as $it) $total2 += $it['subtotal'];

    // Insertar Venta
    $stmt = $conn->prepare("INSERT INTO Venta (id_usuario, total, estado_venta) VALUES (?, ?, 'completada')");
    $stmt->bind_param('id', $uid, $total2);
    $stmt->execute();
    $vid = $conn->insert_id;
    $stmt->close();

    // Insertar Detalle_Venta y descontar stock
    foreach ($items as $it) {
        $pid2     = $it['prod']['id_producto'];
        $qty2     = $it['qty'];
        $sub2     = $it['subtotal'];
        $stmtD = $conn->prepare("INSERT INTO Detalle_Venta (id_venta, id_producto, cantidad, subtotal) VALUES (?,?,?,?)");
        $stmtD->bind_param('iiid', $vid, $pid2, $qty2, $sub2);
        $stmtD->execute(); $stmtD->close();

        $stmtS = $conn->prepare("UPDATE Producto SET stock = stock - ? WHERE id_producto=?");
        $stmtS->bind_param('ii', $qty2, $pid2);
        $stmtS->execute(); $stmtS->close();
    }

    // Limpiar carrito y redirigir
    $_SESSION['carrito'] = [];
    $_SESSION['flash'] = ['type' => 'success', 'msg' => "¡Compra #$vid realizada con éxito! Gracias por tu pedido."];
    redirect('/cliente/historial.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmar Compra — GameZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar-gaming navbar">
    <div class="container">
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">GAME<span>ZONE</span></a>
        <a href="<?= BASE_URL ?>/cliente/carrito.php" class="btn btn-outline-gaming btn-sm ms-auto">
            <i class="fas fa-arrow-left me-1"></i>Volver al carrito
        </a>
    </div>
</nav>

<div class="container py-4" style="max-width:700px">
    <nav><ol class="breadcrumb breadcrumb-gaming mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cliente/catalogo.php">Catálogo</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cliente/carrito.php">Carrito</a></li>
        <li class="breadcrumb-item active">Confirmar Compra</li>
    </ol></nav>

    <h4 class="mb-4" style="font-family:'Rajdhani',sans-serif;font-weight:700">
        <i class="fas fa-credit-card me-2" style="color:#7c3aed"></i>Confirmar Compra
    </h4>

    <?php if ($error): ?>
        <div class="alert-gaming-danger mb-3">
            <i class="fas fa-exclamation-circle me-2"></i><?= sanitize($error) ?>
            <br><a href="<?= BASE_URL ?>/cliente/carrito.php" style="color:#f87171">Volver al carrito →</a>
        </div>
    <?php endif; ?>

    <div class="card-gaming p-4 mb-4">
        <h6 class="mb-3" style="font-family:'Rajdhani',sans-serif;font-weight:700">
            <i class="fas fa-list me-2" style="color:#06b6d4"></i>Resumen del pedido
        </h6>
        <div class="table-responsive">
            <table class="table table-gaming mb-0">
                <thead><tr><th>Producto</th><th>Precio</th><th>Cant.</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600"><?= sanitize($it['prod']['nombre']) ?></div>
                        </td>
                        <td style="color:#94a3b8"><?= formatPrecio($it['prod']['precio']) ?></td>
                        <td><?= $it['qty'] ?></td>
                        <td style="color:#f59e0b;font-weight:700"><?= formatPrecio($it['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold" style="border-top:1px solid #1e293b">Total:</td>
                        <td style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;color:#f59e0b;border-top:1px solid #1e293b">
                            <?= formatPrecio($total) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card-gaming p-4 mb-4">
        <h6 class="mb-2" style="font-family:'Rajdhani',sans-serif;font-weight:700">
            <i class="fas fa-user me-2" style="color:#06b6d4"></i>Datos del cliente
        </h6>
        <p style="color:#94a3b8;margin:0">
            <?= sanitize($_SESSION['usuario_nombre']) ?>
        </p>
    </div>

    <div class="alert-gaming-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Al confirmar, el pedido quedará marcado como <strong>Completado</strong> y el stock se actualizará automáticamente.
    </div>

    <?php if (!$error): ?>
    <form method="post">
        <div class="d-flex gap-3">
            <button type="submit" name="confirmar" class="btn btn-gaming flex-grow-1"
                    onclick="return confirm('¿Confirmar la compra por <?= formatPrecio($total) ?>?')">
                <i class="fas fa-check-circle me-2"></i>Confirmar Compra — <?= formatPrecio($total) ?>
            </button>
            <a href="<?= BASE_URL ?>/cliente/carrito.php" class="btn btn-outline-gaming">
                <i class="fas fa-times me-1"></i>Cancelar
            </a>
        </div>
    </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
