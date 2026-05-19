<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isAdmin()) redirect('/auth/login.php');

$pageTitle = 'Dashboard';

// Estadísticas
$total_ventas    = $conn->query("SELECT COUNT(*) FROM Venta")->fetch_row()[0];
$monto_ventas    = $conn->query("SELECT COALESCE(SUM(total),0) FROM Venta WHERE estado_venta='completada'")->fetch_row()[0];
$total_productos = $conn->query("SELECT COUNT(*) FROM Producto WHERE estado='activo'")->fetch_row()[0];
$total_usuarios  = $conn->query("SELECT COUNT(*) FROM Usuario WHERE rol='cliente'")->fetch_row()[0];
$total_cats      = $conn->query("SELECT COUNT(*) FROM Categoria")->fetch_row()[0];

// Ventas recientes
$ventas_rec = $conn->query("
    SELECT v.id_venta, u.nombre, v.fecha, v.total, v.estado_venta
    FROM Venta v JOIN Usuario u ON v.id_usuario = u.id_usuario
    ORDER BY v.fecha DESC LIMIT 8
");

// Productos con poco stock
$poco_stock = $conn->query("
    SELECT nombre, stock, marca FROM Producto
    WHERE estado='activo' AND stock <= 5
    ORDER BY stock ASC LIMIT 5
");

require __DIR__ . '/includes/header.php';
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-shopping-bag"></i></div>
            <div>
                <div class="stat-value"><?= $total_ventas ?></div>
                <div class="stat-label">Total Ventas</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="stat-value"><?= formatPrecio($monto_ventas) ?></div>
                <div class="stat-label">Ingresos Completados</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon cyan"><i class="fas fa-gamepad"></i></div>
            <div>
                <div class="stat-value"><?= $total_productos ?></div>
                <div class="stat-label">Productos Activos</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-value"><?= $total_usuarios ?></div>
                <div class="stat-label">Clientes Registrados</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Ventas recientes -->
    <div class="col-lg-8">
        <div class="card-gaming p-0">
            <div class="d-flex align-items-center justify-content-between p-3" style="border-bottom:1px solid #1e293b">
                <h6 class="mb-0" style="font-family:'Rajdhani',sans-serif;font-weight:700">
                    <i class="fas fa-clock me-2" style="color:#7c3aed"></i>Ventas Recientes
                </h6>
                <a href="<?= BASE_URL ?>/admin/ventas.php" class="btn btn-sm btn-outline-gaming">Ver todas</a>
            </div>
            <div class="table-responsive">
                <table class="table table-gaming w-100 mb-0">
                    <thead>
                        <tr>
                            <th>#</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($v = $ventas_rec->fetch_assoc()): ?>
                        <tr>
                            <td style="color:#94a3b8">#<?= $v['id_venta'] ?></td>
                            <td><?= sanitize($v['nombre']) ?></td>
                            <td style="color:#94a3b8;font-size:.85rem"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                            <td style="color:#f59e0b;font-weight:700"><?= formatPrecio($v['total']) ?></td>
                            <td><span class="badge-gaming <?= $v['estado_venta'] ?>"><?= ucfirst($v['estado_venta']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($total_ventas == 0): ?>
                        <tr><td colspan="5" class="text-center" style="color:#94a3b8;padding:2rem">Sin ventas aún</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel derecho -->
    <div class="col-lg-4">
        <!-- Poco stock -->
        <div class="card-gaming p-3 mb-3">
            <h6 class="mb-3" style="font-family:'Rajdhani',sans-serif;font-weight:700">
                <i class="fas fa-exclamation-triangle me-2" style="color:#f59e0b"></i>Bajo Stock
            </h6>
            <?php
            $rows = [];
            while ($p = $poco_stock->fetch_assoc()) $rows[] = $p;
            if (empty($rows)):
            ?>
                <p style="color:#94a3b8;font-size:.85rem">Todos los productos tienen stock suficiente.</p>
            <?php else: ?>
                <?php foreach ($rows as $p): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div style="font-size:.88rem;font-weight:500"><?= sanitize($p['nombre']) ?></div>
                        <div style="font-size:.75rem;color:#94a3b8"><?= sanitize($p['marca']) ?></div>
                    </div>
                    <span class="badge-gaming <?= $p['stock'] == 0 ? 'cancelada' : 'pendiente' ?>">
                        <?= $p['stock'] ?> uds.
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Accesos rápidos -->
        <div class="card-gaming p-3">
            <h6 class="mb-3" style="font-family:'Rajdhani',sans-serif;font-weight:700">
                <i class="fas fa-bolt me-2" style="color:#06b6d4"></i>Accesos Rápidos
            </h6>
            <div class="d-grid gap-2">
                <a href="<?= BASE_URL ?>/admin/productos.php?action=new" class="btn btn-gaming btn-sm">
                    <i class="fas fa-plus me-2"></i>Nuevo Producto
                </a>
                <a href="<?= BASE_URL ?>/admin/categorias.php?action=new" class="btn btn-outline-gaming btn-sm">
                    <i class="fas fa-folder-plus me-2"></i>Nueva Categoría
                </a>
                <a href="<?= BASE_URL ?>/admin/usuarios.php" class="btn btn-outline-gaming btn-sm">
                    <i class="fas fa-users me-2"></i>Ver Usuarios (<?= $total_usuarios ?>)
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
