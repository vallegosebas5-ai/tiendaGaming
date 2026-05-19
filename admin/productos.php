<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isAdmin()) redirect('/auth/login.php');

$pageTitle = 'Productos';
$msg = '';
$msgType = 'success';

// ── ACCIONES ─────────────────────────────────────────
$action = $_GET['action'] ?? '';
$pid    = (int)($_GET['id'] ?? 0);

// Insertar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'crear') {
    $nombre      = trim($_POST['nombre']      ?? '');
    $id_cat      = (int)($_POST['id_categoria'] ?? 0);
    $marca       = trim($_POST['marca']       ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = (float)($_POST['precio']   ?? 0);
    $stock       = (int)($_POST['stock']      ?? 0);
    $estado      = $_POST['estado'] ?? 'activo';

    if (!$nombre || !$id_cat || $precio <= 0) {
        $msg = 'Nombre, categoría y precio son obligatorios.';
        $msgType = 'danger';
        $action = 'new';
    } else {
        $stmt = $conn->prepare("INSERT INTO Producto (id_categoria,nombre,marca,descripcion,precio,stock,estado)
                                VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('isssdis', $id_cat, $nombre, $marca, $descripcion, $precio, $stock, $estado);
        if ($stmt->execute()) {
            $msg = 'Producto creado correctamente.';
        } else {
            $msg = 'Error al crear: ' . $conn->error;
            $msgType = 'danger';
        }
        $stmt->close();
    }
}

// Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'editar') {
    $pid         = (int)($_POST['id_producto'] ?? 0);
    $nombre      = trim($_POST['nombre']        ?? '');
    $id_cat      = (int)($_POST['id_categoria'] ?? 0);
    $marca       = trim($_POST['marca']         ?? '');
    $descripcion = trim($_POST['descripcion']   ?? '');
    $precio      = (float)($_POST['precio']     ?? 0);
    $stock       = (int)($_POST['stock']        ?? 0);
    $estado      = $_POST['estado']             ?? 'activo';

    if (!$nombre || !$id_cat || $precio <= 0) {
        $msg = 'Nombre, categoría y precio son obligatorios.';
        $msgType = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE Producto SET id_categoria=?,nombre=?,marca=?,descripcion=?,precio=?,stock=?,estado=? WHERE id_producto=?");
        $stmt->bind_param('isssdisi', $id_cat, $nombre, $marca, $descripcion, $precio, $stock, $estado, $pid);
        if ($stmt->execute()) {
            $msg = 'Producto actualizado.';
        } else {
            $msg = 'Error: ' . $conn->error;
            $msgType = 'danger';
        }
        $stmt->close();
    }
}

// Eliminar
if ($action === 'delete' && $pid) {
    $stmt = $conn->prepare("DELETE FROM Producto WHERE id_producto = ?");
    $stmt->bind_param('i', $pid);
    if ($stmt->execute()) {
        $msg = 'Producto eliminado.';
    } else {
        $msg = 'No se puede eliminar (tiene ventas asociadas).';
        $msgType = 'danger';
    }
    $stmt->close();
    $action = '';
}

// Cargar categorías para el select
$cats = $conn->query("SELECT id_categoria, nombre_categoria FROM Categoria ORDER BY nombre_categoria");

// Cargar producto a editar
$prodEdit = null;
if ($action === 'edit' && $pid) {
    $s = $conn->prepare("SELECT * FROM Producto WHERE id_producto = ?");
    $s->bind_param('i', $pid);
    $s->execute();
    $prodEdit = $s->get_result()->fetch_assoc();
    $s->close();
}

// Lista de productos
$productos = $conn->query("
    SELECT p.*, c.nombre_categoria
    FROM Producto p JOIN Categoria c ON p.id_categoria = c.id_categoria
    ORDER BY p.id_producto DESC
");

require __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?>
    <div class="alert-gaming-<?= $msgType === 'danger' ? 'danger' : 'success' ?> mb-3 alert-auto">
        <i class="fas fa-<?= $msgType === 'danger' ? 'exclamation-circle' : 'check-circle' ?> me-2"></i>
        <?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<!-- Header de sección -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin:0">
            <i class="fas fa-gamepad me-2" style="color:#7c3aed"></i>Gestión de Productos
        </h4>
        <p style="color:#94a3b8;font-size:.85rem;margin:0">Agrega, edita y elimina videojuegos y accesorios</p>
    </div>
    <a href="?action=new" class="btn btn-gaming">
        <i class="fas fa-plus me-2"></i>Nuevo Producto
    </a>
</div>

<!-- Formulario Crear / Editar -->
<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card-gaming p-4 mb-4">
    <h6 class="mb-3" style="font-family:'Rajdhani',sans-serif;font-weight:700">
        <?= $action === 'edit' ? '<i class="fas fa-edit me-2"></i>Editar Producto' : '<i class="fas fa-plus-circle me-2"></i>Nuevo Producto' ?>
    </h6>
    <form method="post" id="prodForm" onsubmit="return validateForm('prodForm')">
        <input type="hidden" name="_action" value="<?= $action === 'edit' ? 'editar' : 'crear' ?>">
        <?php if ($prodEdit): ?>
            <input type="hidden" name="id_producto" value="<?= $prodEdit['id_producto'] ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre del producto *</label>
                <input type="text" name="nombre" class="form-control form-control-gaming"
                       value="<?= sanitize($prodEdit['nombre'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Categoría *</label>
                <select name="id_categoria" class="form-select form-control-gaming" required>
                    <option value="">Seleccionar...</option>
                    <?php
                    $cats->data_seek(0);
                    while ($c = $cats->fetch_assoc()):
                        $sel = ($prodEdit['id_categoria'] ?? '') == $c['id_categoria'] ? 'selected' : '';
                    ?>
                        <option value="<?= $c['id_categoria'] ?>" <?= $sel ?>><?= sanitize($c['nombre_categoria']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Marca</label>
                <input type="text" name="marca" class="form-control form-control-gaming"
                       value="<?= sanitize($prodEdit['marca'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Precio ($) *</label>
                <input type="number" name="precio" step="0.01" min="0.01"
                       class="form-control form-control-gaming"
                       value="<?= $prodEdit['precio'] ?? '' ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock *</label>
                <input type="number" name="stock" min="0"
                       class="form-control form-control-gaming"
                       value="<?= $prodEdit['stock'] ?? 0 ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control form-control-gaming" rows="3"><?= sanitize($prodEdit['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select form-control-gaming">
                    <option value="activo"   <?= ($prodEdit['estado'] ?? 'activo') === 'activo'   ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= ($prodEdit['estado'] ?? '')       === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-gaming">
                <i class="fas fa-save me-2"></i><?= $action === 'edit' ? 'Guardar Cambios' : 'Crear Producto' ?>
            </button>
            <a href="<?= BASE_URL ?>/admin/productos.php" class="btn btn-outline-gaming">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Tabla de productos -->
<div class="card-gaming p-0">
    <div class="p-3 d-flex align-items-center gap-3" style="border-bottom:1px solid #1e293b">
        <div class="search-box flex-grow-1" style="max-width:320px">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" class="form-control form-control-gaming"
                   placeholder="Buscar por nombre, marca..." style="padding-left:2.8rem">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-gaming w-100 mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="prodTable">
            <?php while ($p = $productos->fetch_assoc()): ?>
                <tr data-search="<?= strtolower(sanitize($p['nombre']) . ' ' . sanitize($p['marca'])) ?>">
                    <td style="color:#94a3b8"><?= $p['id_producto'] ?></td>
                    <td>
                        <div style="font-weight:600"><?= sanitize($p['nombre']) ?></div>
                        <div style="font-size:.78rem;color:#94a3b8"><?= sanitize($p['marca']) ?></div>
                    </td>
                    <td><span class="badge-gaming pendiente"><?= sanitize($p['nombre_categoria']) ?></span></td>
                    <td style="color:#f59e0b;font-weight:700"><?= formatPrecio($p['precio']) ?></td>
                    <td>
                        <span class="<?= $p['stock'] == 0 ? 'stock-none' : ($p['stock'] <= 5 ? 'stock-low' : 'stock-ok') ?>">
                            <i class="fas fa-box me-1"></i><?= $p['stock'] ?>
                        </span>
                    </td>
                    <td><span class="badge-gaming <?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?action=edit&id=<?= $p['id_producto'] ?>"
                               class="btn btn-sm btn-outline-gaming" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?action=delete&id=<?= $p['id_producto'] ?>"
                               class="btn btn-sm" title="Eliminar"
                               style="border:1px solid #ef4444;color:#ef4444;border-radius:8px;padding:.3rem .6rem"
                               onclick="return confirmDelete('¿Eliminar este producto?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
