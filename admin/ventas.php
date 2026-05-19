<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isAdmin()) redirect('/auth/login.php');

$pageTitle = 'Ventas';
$msg = '';

// Cambiar estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_venta'], $_POST['estado_venta'])) {
    $vid    = (int)$_POST['id_venta'];
    $estado = $_POST['estado_venta'];
    $allowed = ['pendiente','completada','cancelada'];
    if (in_array($estado, $allowed)) {
        $stmt = $conn->prepare("UPDATE Venta SET estado_venta=? WHERE id_venta=?");
        $stmt->bind_param('si', $estado, $vid);
        $msg = $stmt->execute() ? 'Estado actualizado.' : 'Error: ' . $conn->error;
        $stmt->close();
    }
}

// Detalle de venta
$detalleVid = (int)($_GET['detalle'] ?? 0);
$detalle    = [];
$ventaInfo  = null;
if ($detalleVid) {
    $s = $conn->prepare("SELECT v.*, u.nombre AS cliente, u.correo
                         FROM Venta v JOIN Usuario u ON v.id_usuario=u.id_usuario
                         WHERE v.id_venta=?");
    $s->bind_param('i', $detalleVid);
    $s->execute();
    $ventaInfo = $s->get_result()->fetch_assoc();
    $s->close();

    $sd = $conn->prepare("SELECT dv.*, p.nombre, p.marca
                          FROM Detalle_Venta dv JOIN Producto p ON dv.id_producto=p.id_producto
                          WHERE dv.id_venta=?");
    $sd->bind_param('i', $detalleVid);
    $sd->execute();
    $res = $sd->get_result();
    while ($row = $res->fetch_assoc()) $detalle[] = $row;
    $sd->close();
}

// Lista de ventas
$ventas = $conn->query("
    SELECT v.*, u.nombre AS cliente
    FROM Venta v JOIN Usuario u ON v.id_usuario=u.id_usuario
    ORDER BY v.fecha DESC
");

require __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?>
    <div class="alert-gaming-success mb-3 alert-auto">
        <i class="fas fa-check-circle me-2"></i><?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin:0">
            <i class="fas fa-shopping-bag me-2" style="color:#7c3aed"></i>Registro de Ventas
        </h4>
        <p style="color:#94a3b8;font-size:.85rem;margin:0">Historial y gestión de todos los pedidos</p>
    </div>
</div>

<?php if ($ventaInfo): ?>
<!-- Detalle de venta -->
<div class="card-gaming p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin:0">
            <i class="fas fa-receipt me-2" style="color:#06b6d4"></i>
            Detalle de Venta #<?= $ventaInfo['id_venta'] ?>
        </h6>
        <a href="<?= BASE_URL ?>/admin/ventas.php" class="btn btn-sm btn-outline-gaming">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
    <div class="row mb-3" style="font-size:.9rem">
        <div class="col-md-4"><strong>Cliente:</strong> <?= sanitize($ventaInfo['cliente']) ?></div>
        <div class="col-md-4"><strong>Correo:</strong> <?= sanitize($ventaInfo['correo']) ?></div>
        <div class="col-md-4"><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($ventaInfo['fecha'])) ?></div>
    </div>
    <div class="table-responsive">
        <table class="table table-gaming mb-0">
            <thead><tr><th>Producto</th><th>Marca</th><th>Cantidad</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($detalle as $d): ?>
                <tr>
                    <td><?= sanitize($d['nombre']) ?></td>
                    <td style="color:#94a3b8"><?= sanitize($d['marca']) ?></td>
                    <td><?= $d['cantidad'] ?></td>
                    <td style="color:#f59e0b;font-weight:700"><?= formatPrecio($d['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-bold" style="border-top:1px solid #1e293b">Total:</td>
                    <td style="color:#f59e0b;font-weight:700;font-size:1.1rem;border-top:1px solid #1e293b">
                        <?= formatPrecio($ventaInfo['total']) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <!-- Cambiar estado -->
    <form method="post" class="d-flex align-items-center gap-3 mt-3">
        <input type="hidden" name="id_venta" value="<?= $ventaInfo['id_venta'] ?>">
        <label class="form-label mb-0" style="white-space:nowrap">Cambiar estado:</label>
        <select name="estado_venta" class="form-select form-control-gaming" style="max-width:200px">
            <?php foreach (['pendiente','completada','cancelada'] as $e): ?>
                <option value="<?= $e ?>" <?= $ventaInfo['estado_venta'] === $e ? 'selected' : '' ?>>
                    <?= ucfirst($e) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-gaming btn-sm">
            <i class="fas fa-save me-1"></i>Guardar
        </button>
    </form>
</div>
<?php endif; ?>

<!-- Tabla ventas -->
<div class="card-gaming p-0">
    <div class="table-responsive">
        <table class="table table-gaming w-100 mb-0">
            <thead>
                <tr><th>#</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php
            $count = 0;
            while ($v = $ventas->fetch_assoc()):
                $count++;
            ?>
                <tr>
                    <td style="color:#94a3b8"><?= $v['id_venta'] ?></td>
                    <td style="font-weight:500"><?= sanitize($v['cliente']) ?></td>
                    <td style="color:#94a3b8;font-size:.85rem"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                    <td style="color:#f59e0b;font-weight:700"><?= formatPrecio($v['total']) ?></td>
                    <td><span class="badge-gaming <?= $v['estado_venta'] ?>"><?= ucfirst($v['estado_venta']) ?></span></td>
                    <td>
                        <a href="?detalle=<?= $v['id_venta'] ?>" class="btn btn-sm btn-outline-gaming">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if ($count === 0): ?>
                <tr><td colspan="6" class="text-center" style="color:#94a3b8;padding:3rem">
                    <i class="fas fa-inbox mb-2 d-block" style="font-size:2rem"></i>
                    Sin ventas registradas todavía
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
