<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isLoggedIn()) redirect('/auth/login.php');

// Quitar favorito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quitar_fav'])) {
    $pid = (int)$_POST['id_producto'];
    $uid = $_SESSION['usuario_id'];
    $stmt = $conn->prepare("DELETE FROM Favorito WHERE id_usuario=? AND id_producto=?");
    $stmt->bind_param('ii', $uid, $pid);
    $stmt->execute();
    $stmt->close();
    redirect('/cliente/favoritos.php');
}

// Agregar al carrito desde favoritos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_carrito'])) {
    $pid = (int)$_POST['id_producto'];
    $s = $conn->prepare("SELECT nombre, precio, stock FROM Producto WHERE id_producto=? AND estado='activo'");
    $s->bind_param('i', $pid); $s->execute();
    $prod = $s->get_result()->fetch_assoc(); $s->close();
    if ($prod && $prod['stock'] > 0) {
        if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
        if (isset($_SESSION['carrito'][$pid])) {
            $_SESSION['carrito'][$pid]['qty'] = min($_SESSION['carrito'][$pid]['qty'] + 1, $prod['stock']);
        } else {
            $_SESSION['carrito'][$pid] = ['nombre' => $prod['nombre'], 'precio' => $prod['precio'], 'qty' => 1];
        }
    }
    redirect('/cliente/carrito.php');
}

$uid  = $_SESSION['usuario_id'];
$stmt = $conn->prepare("
    SELECT p.*, c.nombre_categoria, f.fecha AS fecha_fav
    FROM Favorito f
    JOIN Producto p ON f.id_producto = p.id_producto
    JOIN Categoria c ON p.id_categoria = c.id_categoria
    WHERE f.id_usuario = ?
    ORDER BY f.fecha DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$favoritos = $stmt->get_result();
$stmt->close();

$cartCount = array_sum(array_column($_SESSION['carrito'] ?? [], 'qty'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis Favoritos — GameZone</title>
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
    <nav><ol class="breadcrumb breadcrumb-gaming mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cliente/catalogo.php">Catálogo</a></li>
        <li class="breadcrumb-item active">Mis Favoritos</li>
    </ol></nav>

    <h4 class="mb-4" style="font-family:'Rajdhani',sans-serif;font-weight:700">
        <i class="fas fa-heart me-2" style="color:#ef4444"></i>Mis Favoritos
    </h4>

    <?php
    $rows = [];
    while ($r = $favoritos->fetch_assoc()) $rows[] = $r;
    ?>

    <?php if (empty($rows)): ?>
        <div class="text-center py-5">
            <i class="fas fa-heart mb-3 d-block" style="font-size:4rem;color:#1e293b"></i>
            <h5 style="font-family:'Rajdhani',sans-serif;color:#94a3b8">No tienes favoritos aún</h5>
            <p style="color:#64748b">Agrega juegos a tus favoritos desde el catálogo</p>
            <a href="<?= BASE_URL ?>/cliente/catalogo.php" class="btn btn-gaming mt-2">
                <i class="fas fa-gamepad me-2"></i>Explorar Catálogo
            </a>
        </div>
    <?php else: ?>
        <div class="row g-3">
        <?php foreach ($rows as $p):
            $phClass = 'ph-' . (($p['id_producto'] % 12) + 1);
        ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card-gaming h-100 d-flex flex-column">
                    <a href="<?= BASE_URL ?>/cliente/producto.php?id=<?= $p['id_producto'] ?>">
                        <div class="product-placeholder <?= $phClass ?>">
                            <i class="fas fa-gamepad"></i>
                            <span><?= sanitize($p['nombre']) ?></span>
                        </div>
                    </a>
                    <div class="card-body d-flex flex-column">
                        <div class="card-marca"><?= sanitize($p['nombre_categoria']) ?></div>
                        <a href="<?= BASE_URL ?>/cliente/producto.php?id=<?= $p['id_producto'] ?>"
                           class="card-title text-decoration-none d-block"><?= sanitize($p['nombre']) ?></a>
                        <div style="color:#94a3b8;font-size:.78rem;margin-bottom:.5rem"><?= sanitize($p['marca']) ?></div>
                        <div class="d-flex align-items-center justify-content-between mt-auto mb-2">
                            <span class="precio"><?= formatPrecio($p['precio']) ?></span>
                            <span style="font-size:.75rem;color:#64748b">
                                <?= date('d/m/Y', strtotime($p['fecha_fav'])) ?>
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if ($p['stock'] > 0): ?>
                            <form method="post" class="flex-grow-1">
                                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                <button type="submit" name="agregar_carrito" class="btn btn-gaming w-100 btn-sm">
                                    <i class="fas fa-cart-plus me-1"></i>Al carrito
                                </button>
                            </form>
                            <?php else: ?>
                                <button disabled class="btn btn-sm flex-grow-1" style="background:#1a1a2e;color:#64748b;border:1px solid #1e293b;border-radius:8px">Sin stock</button>
                            <?php endif; ?>
                            <form method="post">
                                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                <button type="submit" name="quitar_fav" class="btn-fav active" title="Quitar de favoritos">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
