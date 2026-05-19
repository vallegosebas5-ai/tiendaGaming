<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isLoggedIn()) redirect('/auth/login.php');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$uid = $_SESSION['usuario_id'];

// Detalle de venta específica
$detalleVid = (int)($_GET['venta'] ?? 0);
$detalle    = [];
$ventaInfo  = null;
if ($detalleVid) {
    $s = $conn->prepare("SELECT * FROM Venta WHERE id_venta=? AND id_usuario=?");
    $s->bind_param('ii', $detalleVid, $uid);
    $s->execute();
    $ventaInfo = $s->get_result()->fetch_assoc();
    $s->close();

    if ($ventaInfo) {
        $sd = $conn->prepare("SELECT dv.*, p.nombre, p.marca FROM Detalle_Venta dv JOIN Producto p ON dv.id_producto=p.id_producto WHERE dv.id_venta=?");
        $sd->bind_param('i', $detalleVid);
        $sd->execute();
        $rd = $sd->get_result();
        while ($r = $rd->fetch_assoc()) $detalle[] = $r;
        $sd->close();
    }
}

$ventas = $conn->prepare("SELECT * FROM Venta WHERE id_usuario=? ORDER BY fecha DESC");
$ventas->bind_param('i', $uid);
$ventas->execute();
$res = $ventas->get_result();
$listaVentas = [];
while ($v = $res->fetch_assoc()) $listaVentas[] = $v;
$ventas->close();

$cartCount = array_sum(array_column($_SESSION['carrito'] ?? [], 'qty'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis Compras — GameZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar-gaming navbar navbar-expand-lg">
    <div class="container">
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">GAME<span>ZONE</span></a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <a href="<?= BASE_URL ?>/cliente/catalogo.php" class="btn btn-outline-gaming btn-sm">
                <i class="fas fa-gamepad me-1"></i>Catálogo
            </a>
            <a href="<?= BASE_URL ?>/cliente/carrito.php" class="btn btn-gaming btn-sm position-relative">
                <i class="fas fa-shopping-cart me-1"></i>Carrito
                <?php if ($cartCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                          style="background:#f59e0b;color:#000;font-size:.7rem"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <nav><ol class="breadcrumb breadcrumb-gaming mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cliente/catalogo.php">Catálogo</a></li>
        <li class="breadcrumb-item active">Mis Compras</li>
    </ol></nav>

    <?php if ($flash): ?>
        <div class="alert-gaming-success mb-4 alert-auto">
            <i class="fas fa-check-circle me-2"></i><?= sanitize($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <h4 class="mb-4" style="font-family:'Rajdhani',sans-serif;font-weight:700">
        <i class="fas fa-history me-2" style="color:#7c3aed"></i>Historial de Compras
    </h4>

    <?php if ($ventaInfo): ?>
    <!-- Detalle de la venta -->
    <div class="card-gaming p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin:0">
                <i class="fas fa-receipt me-2" style="color:#06b6d4"></i>
                Pedido #<?= $ventaInfo['id_venta'] ?>
            </h6>
            <a href="<?= BASE_URL ?>/cliente/historial.php" class="btn btn-sm btn-outline-gaming">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
        <div class="row mb-3" style="font-size:.88rem">
            <div class="col-sm-6">
                <span style="color:#94a3b8">Fecha:</span>
                <?= date('d/m/Y H:i', strtotime($ventaInfo['fecha'])) ?>
            </div>
            <div class="col-sm-6">
                <span style="color:#94a3b8">Estado:</span>
                <span class="badge-gaming <?= $ventaInfo['estado_venta'] ?> ms-1">
                    <?= ucfirst($ventaInfo['estado_venta']) ?>
                </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-gaming mb-0">
                <thead><tr><th>Producto</th><th>Marca</th><th>Cantidad</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($detalle as $d): ?>
                    <tr>
                        <td style="font-weight:500"><?= sanitize($d['nombre']) ?></td>
                        <td style="color:#94a3b8"><?= sanitize($d['marca']) ?></td>
                        <td><?= $d['cantidad'] ?></td>
                        <td style="color:#f59e0b;font-weight:700"><?= formatPrecio($d['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold" style="border-top:1px solid #1e293b">Total:</td>
                        <td style="font-family:'Rajdhani',sans-serif;font-size:1.3rem;font-weight:700;color:#f59e0b;border-top:1px solid #1e293b">
                            <?= formatPrecio($ventaInfo['total']) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($listaVentas)): ?>
        <div class="text-center py-5">
            <i class="fas fa-history mb-3 d-block" style="font-size:4rem;color:#1e293b"></i>
            <h5 style="font-family:'Rajdhani',sans-serif;color:#94a3b8">Sin compras todavía</h5>
            <a href="<?= BASE_URL ?>/cliente/catalogo.php" class="btn btn-gaming mt-3">
                <i class="fas fa-gamepad me-2"></i>Ir al Catálogo
            </a>
        </div>
    <?php else: ?>
        <div class="card-gaming p-0">
            <div class="table-responsive">
                <table class="table table-gaming w-100 mb-0">
                    <thead><tr><th>#</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($listaVentas as $v): ?>
                        <tr>
                            <td style="color:#94a3b8"><?= $v['id_venta'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                            <td style="color:#f59e0b;font-weight:700"><?= formatPrecio($v['total']) ?></td>
                            <td><span class="badge-gaming <?= $v['estado_venta'] ?>"><?= ucfirst($v['estado_venta']) ?></span></td>
                            <td>
                                <a href="?venta=<?= $v['id_venta'] ?>" class="btn btn-sm btn-outline-gaming">
                                    <i class="fas fa-eye me-1"></i>Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
