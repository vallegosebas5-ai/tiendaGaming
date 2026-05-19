<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isLoggedIn()) redirect('/auth/login.php');

// Agregar al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_carrito'])) {
    $pid = (int)$_POST['id_producto'];
    $qty = max(1, (int)($_POST['cantidad'] ?? 1));

    // Verificar stock
    $s = $conn->prepare("SELECT nombre, precio, stock FROM Producto WHERE id_producto=? AND estado='activo'");
    $s->bind_param('i', $pid);
    $s->execute();
    $prod = $s->get_result()->fetch_assoc();
    $s->close();

    if ($prod && $prod['stock'] > 0) {
        if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
        $qty = min($qty, $prod['stock']);
        if (isset($_SESSION['carrito'][$pid])) {
            $newQty = min($_SESSION['carrito'][$pid]['qty'] + $qty, $prod['stock']);
            $_SESSION['carrito'][$pid]['qty'] = $newQty;
        } else {
            $_SESSION['carrito'][$pid] = [
                'nombre' => $prod['nombre'],
                'precio' => $prod['precio'],
                'qty'    => $qty
            ];
        }
        $_SESSION['flash'] = ['type' => 'success', 'msg' => '"' . $prod['nombre'] . '" agregado al carrito.'];
    }
    redirect('/cliente/catalogo.php');
}

// Agregar a favoritos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_fav'])) {
    $pid = (int)$_POST['id_producto'];
    $uid = $_SESSION['usuario_id'];

    $check = $conn->prepare("SELECT id_favorito FROM Favorito WHERE id_usuario=? AND id_producto=?");
    $check->bind_param('ii', $uid, $pid);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $del = $conn->prepare("DELETE FROM Favorito WHERE id_usuario=? AND id_producto=?");
        $del->bind_param('ii', $uid, $pid);
        $del->execute();
        $del->close();
    } else {
        $ins = $conn->prepare("INSERT INTO Favorito (id_usuario, id_producto) VALUES (?,?)");
        $ins->bind_param('ii', $uid, $pid);
        $ins->execute();
        $ins->close();
    }
    $check->close();
    redirect('/cliente/catalogo.php');
}

// Flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Filtros
$catFiltro = (int)($_GET['cat'] ?? 0);
$where = "WHERE p.estado='activo'";
$params = [];
$types  = '';
if ($catFiltro) {
    $where .= " AND p.id_categoria=?";
    $params[] = $catFiltro;
    $types .= 'i';
}

// Productos
$sql = "SELECT p.*, c.nombre_categoria FROM Producto p JOIN Categoria c ON p.id_categoria=c.id_categoria $where ORDER BY p.id_producto DESC";
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $productos = $stmt->get_result();
} else {
    $productos = $conn->query($sql);
}

// Categorías
$categorias = $conn->query("SELECT * FROM Categoria ORDER BY nombre_categoria");

// IDs de favoritos del usuario
$favs_raw = $conn->prepare("SELECT id_producto FROM Favorito WHERE id_usuario=?");
$favs_raw->bind_param('i', $_SESSION['usuario_id']);
$favs_raw->execute();
$favRes = $favs_raw->get_result();
$favIds = [];
while ($r = $favRes->fetch_assoc()) $favIds[] = $r['id_producto'];
$favs_raw->close();

$cartCount = array_sum(array_column($_SESSION['carrito'] ?? [], 'qty'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo — GameZone</title>
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
            <a href="<?= BASE_URL ?>/cliente/favoritos.php" class="nav-link position-relative">
                <i class="fas fa-heart" style="color:#ef4444"></i>
                <span style="color:#94a3b8;font-size:.85rem"> Favoritos (<?= count($favIds) ?>)</span>
            </a>
            <a href="<?= BASE_URL ?>/cliente/carrito.php" class="btn btn-gaming btn-sm position-relative">
                <i class="fas fa-shopping-cart me-1"></i> Carrito
                <?php if ($cartCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                          style="background:#f59e0b;color:#000;font-size:.7rem"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown">
                <button class="btn btn-outline-gaming btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-1"></i><?= sanitize($_SESSION['usuario_nombre']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="background:#111128;border:1px solid #1e293b">
                    <li><a class="dropdown-item" style="color:#e2e8f0" href="<?= BASE_URL ?>/cliente/historial.php">
                        <i class="fas fa-history me-2"></i>Mis Compras
                    </a></li>
                    <li><hr class="dropdown-divider" style="border-color:#1e293b"></li>
                    <li><a class="dropdown-item" style="color:#f87171" href="<?= BASE_URL ?>/auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4">
    <?php if ($flash): ?>
        <div class="alert-gaming-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> mb-3 alert-auto">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= sanitize($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sidebar filtros -->
        <div class="col-lg-3">
            <div class="card-gaming p-3">
                <h6 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin-bottom:1rem">
                    <i class="fas fa-filter me-2" style="color:#7c3aed"></i>Plataforma
                </h6>
                <div class="d-flex flex-column gap-1">
                    <a href="<?= BASE_URL ?>/cliente/catalogo.php"
                       class="<?= !$catFiltro ? 'btn-gaming' : 'btn-outline-gaming' ?> btn btn-sm text-start">
                        <i class="fas fa-th me-2"></i>Todos
                    </a>
                    <?php
                    $categorias->data_seek(0);
                    $catIcons = ['PlayStation'=>'fa-playstation','Xbox'=>'fa-xbox','Nintendo'=>'fa-dragon','PC Gaming'=>'fa-desktop','Accesorios'=>'fa-headphones'];
                    while ($c = $categorias->fetch_assoc()):
                        $icon = $catIcons[$c['nombre_categoria']] ?? 'fa-tag';
                    ?>
                    <a href="?cat=<?= $c['id_categoria'] ?>"
                       class="<?= $catFiltro == $c['id_categoria'] ? 'btn-gaming' : 'btn-outline-gaming' ?> btn btn-sm text-start">
                        <i class="fab <?= $icon ?> me-2"></i><?= sanitize($c['nombre_categoria']) ?>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Productos -->
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h5 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin:0">
                    <i class="fas fa-gamepad me-2" style="color:#7c3aed"></i>Catálogo Gaming
                </h5>
                <div class="search-box" style="max-width:280px">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control form-control-gaming"
                           placeholder="Buscar juego o accesorio..." style="padding-left:2.8rem">
                </div>
            </div>

            <div class="row g-3" id="prodGrid">
            <?php
            $count = 0;
            while ($p = $productos->fetch_assoc()):
                $count++;
                $isFav = in_array($p['id_producto'], $favIds);
                $phClass = 'ph-' . (($p['id_producto'] % 12) + 1);
                $icons = ['PlayStation'=>'fa-playstation','Xbox'=>'fa-xbox','Nintendo'=>'fa-dragon','PC Gaming'=>'fa-desktop','Accesorios'=>'fa-headphones'];
                $ico = $icons[$p['nombre_categoria']] ?? 'fa-gamepad';
                $searchText = strtolower($p['nombre'] . ' ' . $p['marca'] . ' ' . $p['nombre_categoria']);
            ?>
            <div class="col-sm-6 col-xl-4" data-search="<?= sanitize($searchText) ?>">
                <div class="card-gaming h-100 d-flex flex-column">
                    <!-- Imagen / placeholder -->
                    <a href="<?= BASE_URL ?>/cliente/producto.php?id=<?= $p['id_producto'] ?>">
                        <div class="product-placeholder <?= $phClass ?>">
                            <i class="fab <?= $ico ?>"></i>
                            <span><?= sanitize($p['nombre']) ?></span>
                        </div>
                    </a>
                    <div class="card-body d-flex flex-column">
                        <div class="card-marca"><?= sanitize($p['nombre_categoria']) ?></div>
                        <a href="<?= BASE_URL ?>/cliente/producto.php?id=<?= $p['id_producto'] ?>"
                           class="card-title text-decoration-none d-block"><?= sanitize($p['nombre']) ?></a>
                        <div style="color:#94a3b8;font-size:.8rem;margin-bottom:.5rem"><?= sanitize($p['marca']) ?></div>

                        <div class="d-flex align-items-center justify-content-between mt-auto pt-2">
                            <div class="precio"><?= formatPrecio($p['precio']) ?></div>
                            <div class="<?= $p['stock'] == 0 ? 'stock-none' : ($p['stock'] <= 5 ? 'stock-low' : 'stock-ok') ?>" style="font-size:.78rem">
                                <?= $p['stock'] == 0 ? '<i class="fas fa-ban me-1"></i>Sin stock' : '<i class="fas fa-box me-1"></i>' . $p['stock'] . ' uds.' ?>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-2">
                            <?php if ($p['stock'] > 0): ?>
                            <form method="post" class="flex-grow-1">
                                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                <input type="hidden" name="cantidad" value="1">
                                <button type="submit" name="agregar_carrito" class="btn btn-gaming w-100 btn-sm">
                                    <i class="fas fa-cart-plus me-1"></i>Agregar
                                </button>
                            </form>
                            <?php else: ?>
                                <button class="btn btn-sm flex-grow-1" disabled
                                        style="background:#1a1a2e;color:#64748b;border:1px solid #1e293b;border-radius:8px">
                                    Sin stock
                                </button>
                            <?php endif; ?>

                            <form method="post">
                                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                <button type="submit" name="toggle_fav"
                                        class="btn-fav <?= $isFav ? 'active' : '' ?>"
                                        title="<?= $isFav ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            </div>

            <?php if ($count === 0): ?>
                <div class="text-center py-5" id="noResults">
                    <i class="fas fa-search mb-3 d-block" style="font-size:3rem;color:#7c3aed"></i>
                    <h5 style="font-family:'Rajdhani',sans-serif">No hay productos disponibles</h5>
                    <p style="color:#94a3b8">Prueba con otra categoría</p>
                </div>
            <?php endif; ?>
            <div id="noResults" style="display:none" class="text-center py-4">
                <i class="fas fa-search mb-2 d-block" style="font-size:2rem;color:#7c3aed"></i>
                <p style="color:#94a3b8">Sin resultados para tu búsqueda</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
