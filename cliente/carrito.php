<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isLoggedIn()) redirect('/auth/login.php');

if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// Actualizar cantidad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $pid = (int)$_POST['id_producto'];
    $qty = max(1, (int)$_POST['cantidad']);
    // Validar stock
    $s = $conn->prepare("SELECT stock FROM Producto WHERE id_producto=?");
    $s->bind_param('i', $pid); $s->execute();
    $row = $s->get_result()->fetch_assoc(); $s->close();
    if ($row) {
        $_SESSION['carrito'][$pid]['qty'] = min($qty, $row['stock']);
    }
    redirect('/cliente/carrito.php');
}

// Eliminar item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $pid = (int)$_POST['id_producto'];
    unset($_SESSION['carrito'][$pid]);
    redirect('/cliente/carrito.php');
}

// Vaciar carrito
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = [];
    redirect('/cliente/carrito.php');
}

$carrito = $_SESSION['carrito'] ?? [];
$total   = 0;
$cartCount = array_sum(array_column($carrito, 'qty'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrito — GameZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<!-- Navbar -->
<nav class="navbar-gaming navbar navbar-expand-lg">
    <div class="container">
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">GAME<span>ZONE</span></a>
        <div class="ms-auto">
            <a href="<?= BASE_URL ?>/cliente/catalogo.php" class="btn btn-outline-gaming btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Seguir comprando
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <nav><ol class="breadcrumb breadcrumb-gaming mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cliente/catalogo.php">Catálogo</a></li>
        <li class="breadcrumb-item active">Carrito</li>
    </ol></nav>

    <h4 class="mb-4" style="font-family:'Rajdhani',sans-serif;font-weight:700">
        <i class="fas fa-shopping-cart me-2" style="color:#7c3aed"></i>Mi Carrito
        <?php if ($cartCount > 0): ?>
            <span style="font-size:1rem;color:#94a3b8;font-family:'Exo 2',sans-serif">(<?= $cartCount ?> items)</span>
        <?php endif; ?>
    </h4>

    <?php if (empty($carrito)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart mb-3 d-block" style="font-size:4rem;color:#1e293b"></i>
            <h5 style="font-family:'Rajdhani',sans-serif;color:#94a3b8">Tu carrito está vacío</h5>
            <a href="<?= BASE_URL ?>/cliente/catalogo.php" class="btn btn-gaming mt-3">
                <i class="fas fa-gamepad me-2"></i>Explorar Catálogo
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Items del carrito -->
                <?php foreach ($carrito as $pid => $item):
                    $subtotal = $item['precio'] * $item['qty'];
                    $total += $subtotal;
                    $phClass = 'ph-' . (($pid % 12) + 1);
                ?>
                <div class="cart-item">
                    <div class="cart-thumb-ph <?= $phClass ?>">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div style="font-weight:600;font-size:1rem"><?= sanitize($item['nombre']) ?></div>
                        <div style="color:#94a3b8;font-size:.85rem">Precio unitario: <?= formatPrecio($item['precio']) ?></div>
                    </div>
                    <!-- Actualizar cantidad -->
                    <form method="post" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="id_producto" value="<?= $pid ?>">
                        <div class="qty-input">
                            <button type="button" data-qty="minus"><i class="fas fa-minus"></i></button>
                            <input type="number" name="cantidad" value="<?= $item['qty'] ?>" min="1"
                                   onchange="this.form.submit()">
                            <button type="button" data-qty="plus" onclick="this.closest('form').submit()"><i class="fas fa-plus"></i></button>
                        </div>
                        <button type="submit" name="update_qty" class="btn btn-sm btn-outline-gaming">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </form>
                    <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.2rem;color:#f59e0b;min-width:80px;text-align:right">
                        <?= formatPrecio($subtotal) ?>
                    </div>
                    <!-- Eliminar -->
                    <form method="post">
                        <input type="hidden" name="id_producto" value="<?= $pid ?>">
                        <button type="submit" name="remove_item"
                                style="background:transparent;border:1px solid #ef4444;color:#ef4444;border-radius:8px;padding:.35rem .65rem;cursor:pointer;transition:all .2s"
                                onmouseover="this.style.background='#ef4444';this.style.color='#fff'"
                                onmouseout="this.style.background='transparent';this.style.color='#ef4444'"
                                title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>

                <div class="mt-2">
                    <a href="?vaciar=1" style="color:#ef4444;font-size:.85rem"
                       onclick="return confirm('¿Vaciar el carrito?')">
                        <i class="fas fa-trash me-1"></i>Vaciar carrito
                    </a>
                </div>
            </div>

            <!-- Resumen -->
            <div class="col-lg-4">
                <div class="cart-total-box">
                    <h5 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin-bottom:1.5rem">
                        Resumen del Pedido
                    </h5>
                    <?php foreach ($carrito as $pid => $item): ?>
                    <div class="d-flex justify-content-between mb-1" style="font-size:.88rem">
                        <span style="color:#94a3b8"><?= sanitize($item['nombre']) ?> x<?= $item['qty'] ?></span>
                        <span><?= formatPrecio($item['precio'] * $item['qty']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <hr style="border-color:#1e293b;margin:1rem 0">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal</span>
                        <span><?= formatPrecio($total) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span style="color:#94a3b8;font-size:.85rem">Envío</span>
                        <span style="color:#22c55e;font-size:.85rem">Gratis</span>
                    </div>
                    <hr style="border-color:#1e293b;margin:0 0 1rem">
                    <div class="d-flex justify-content-between mb-4">
                        <strong style="font-family:'Rajdhani',sans-serif;font-size:1.1rem">Total</strong>
                        <strong style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;color:#f59e0b">
                            <?= formatPrecio($total) ?>
                        </strong>
                    </div>
                    <a href="<?= BASE_URL ?>/cliente/checkout.php" class="btn btn-gaming w-100">
                        <i class="fas fa-credit-card me-2"></i>Confirmar Compra
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
