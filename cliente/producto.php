<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isLoggedIn()) redirect('/auth/login.php');

$pid = (int)($_GET['id'] ?? 0);
if (!$pid) redirect('/cliente/catalogo.php');

$stmt = $conn->prepare("
    SELECT p.*, c.nombre_categoria
    FROM Producto p JOIN Categoria c ON p.id_categoria=c.id_categoria
    WHERE p.id_producto=? AND p.estado='activo'
");
$stmt->bind_param('i', $pid);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prod) redirect('/cliente/catalogo.php');

// Agregar al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_carrito'])) {
    $qty = max(1, min((int)($_POST['cantidad'] ?? 1), $prod['stock']));
    if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
    if (isset($_SESSION['carrito'][$pid])) {
        $newQty = min($_SESSION['carrito'][$pid]['qty'] + $qty, $prod['stock']);
        $_SESSION['carrito'][$pid]['qty'] = $newQty;
    } else {
        $_SESSION['carrito'][$pid] = ['nombre' => $prod['nombre'], 'precio' => $prod['precio'], 'qty' => $qty];
    }
    redirect('/cliente/carrito.php');
}

// Toggle favorito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_fav'])) {
    $uid = $_SESSION['usuario_id'];
    $check = $conn->prepare("SELECT id_favorito FROM Favorito WHERE id_usuario=? AND id_producto=?");
    $check->bind_param('ii', $uid, $pid);
    $check->execute(); $check->store_result();
    if ($check->num_rows > 0) {
        $del = $conn->prepare("DELETE FROM Favorito WHERE id_usuario=? AND id_producto=?");
        $del->bind_param('ii', $uid, $pid); $del->execute(); $del->close();
    } else {
        $ins = $conn->prepare("INSERT INTO Favorito (id_usuario,id_producto) VALUES (?,?)");
        $ins->bind_param('ii', $uid, $pid); $ins->execute(); $ins->close();
    }
    $check->close();
    redirect('/cliente/producto.php?id=' . $pid);
}

// Es favorito?
$favCheck = $conn->prepare("SELECT id_favorito FROM Favorito WHERE id_usuario=? AND id_producto=?");
$favCheck->bind_param('ii', $_SESSION['usuario_id'], $pid);
$favCheck->execute(); $favCheck->store_result();
$isFav = $favCheck->num_rows > 0;
$favCheck->close();

$phClass  = 'ph-' . (($prod['id_producto'] % 12) + 1);
$cartCount = array_sum(array_column($_SESSION['carrito'] ?? [], 'qty'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= sanitize($prod['nombre']) ?> — GameZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<!-- Navbar -->
<nav class="navbar-gaming navbar navbar-expand-lg">
    <div class="container">
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">GAME<span>ZONE</span></a>
        <div class="d-flex align-items-center gap-3 ms-auto">
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
    <!-- Breadcrumb -->
    <nav><ol class="breadcrumb breadcrumb-gaming mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cliente/catalogo.php">Catálogo</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cliente/catalogo.php?cat=<?= $prod['id_categoria'] ?>"><?= sanitize($prod['nombre_categoria']) ?></a></li>
        <li class="breadcrumb-item active"><?= sanitize($prod['nombre']) ?></li>
    </ol></nav>

    <div class="row g-4">
        <!-- Imagen -->
        <div class="col-md-5">
            <div class="product-detail-img <?= $phClass ?>">
                <div class="product-placeholder <?= $phClass ?>" style="width:100%;height:100%;border-radius:0">
                    <i class="fas fa-gamepad" style="font-size:4rem"></i>
                    <span style="font-size:1.1rem;max-width:80%"><?= sanitize($prod['nombre']) ?></span>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="col-md-7">
            <div class="badge-gaming pendiente mb-2 d-inline-block"><?= sanitize($prod['nombre_categoria']) ?></div>
            <h2 style="font-family:'Rajdhani',sans-serif;font-weight:700"><?= sanitize($prod['nombre']) ?></h2>
            <p style="color:#06b6d4;font-size:.9rem;letter-spacing:.05em;text-transform:uppercase;font-weight:600">
                <?= sanitize($prod['marca']) ?>
            </p>

            <div class="d-flex align-items-baseline gap-3 mb-3">
                <span style="font-family:'Rajdhani',sans-serif;font-size:2.5rem;font-weight:700;color:#f59e0b">
                    <?= formatPrecio($prod['precio']) ?>
                </span>
                <span class="<?= $prod['stock'] == 0 ? 'stock-none' : ($prod['stock'] <= 5 ? 'stock-low' : 'stock-ok') ?>">
                    <?php if ($prod['stock'] == 0): ?>
                        <i class="fas fa-ban me-1"></i>Sin stock
                    <?php elseif ($prod['stock'] <= 5): ?>
                        <i class="fas fa-exclamation-triangle me-1"></i>Últimas <?= $prod['stock'] ?> unidades
                    <?php else: ?>
                        <i class="fas fa-check-circle me-1"></i><?= $prod['stock'] ?> en stock
                    <?php endif; ?>
                </span>
            </div>

            <p style="color:#94a3b8;line-height:1.7"><?= sanitize($prod['descripcion'] ?: 'Sin descripción disponible.') ?></p>

            <?php if ($prod['stock'] > 0): ?>
            <form method="post" class="d-flex align-items-center gap-3 flex-wrap mt-3">
                <div class="qty-input">
                    <button type="button" data-qty="minus"><i class="fas fa-minus"></i></button>
                    <input type="number" name="cantidad" value="1" min="1" max="<?= $prod['stock'] ?>">
                    <button type="button" data-qty="plus"><i class="fas fa-plus"></i></button>
                </div>
                <button type="submit" name="agregar_carrito" class="btn btn-gaming">
                    <i class="fas fa-cart-plus me-2"></i>Agregar al Carrito
                </button>
            </form>
            <?php else: ?>
                <button class="btn mt-3" disabled style="background:#1a1a2e;color:#64748b;border:1px solid #1e293b;border-radius:8px;padding:.65rem 1.5rem">
                    <i class="fas fa-ban me-2"></i>Sin stock disponible
                </button>
            <?php endif; ?>

            <form method="post" class="mt-3">
                <button type="submit" name="toggle_fav" class="btn-fav <?= $isFav ? 'active' : '' ?>">
                    <i class="fas fa-heart me-2"></i>
                    <?= $isFav ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>
                </button>
            </form>

            <div class="d-flex gap-2 mt-3">
                <a href="<?= BASE_URL ?>/cliente/catalogo.php" style="color:#94a3b8;font-size:.9rem">
                    <i class="fas fa-arrow-left me-1"></i>Seguir comprando
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
