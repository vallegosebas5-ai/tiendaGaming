<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isAdmin()) redirect('/auth/login.php');

$pageTitle = 'Categorías';
$msg = '';
$msgType = 'success';

$action = $_GET['action'] ?? '';
$cid    = (int)($_GET['id'] ?? 0);

// Crear
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'crear') {
    $nombre = trim($_POST['nombre_categoria'] ?? '');
    $desc   = trim($_POST['descripcion']      ?? '');
    if (!$nombre) {
        $msg = 'El nombre es obligatorio.'; $msgType = 'danger'; $action = 'new';
    } else {
        $stmt = $conn->prepare("INSERT INTO Categoria (nombre_categoria, descripcion) VALUES (?,?)");
        $stmt->bind_param('ss', $nombre, $desc);
        $msg = $stmt->execute() ? 'Categoría creada.' : 'Error: ' . $conn->error;
        if (!$stmt->execute()) $msgType = 'danger';
        $stmt->close();
    }
}

// Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'editar') {
    $cid    = (int)$_POST['id_categoria'];
    $nombre = trim($_POST['nombre_categoria'] ?? '');
    $desc   = trim($_POST['descripcion']      ?? '');
    if (!$nombre) {
        $msg = 'El nombre es obligatorio.'; $msgType = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE Categoria SET nombre_categoria=?, descripcion=? WHERE id_categoria=?");
        $stmt->bind_param('ssi', $nombre, $desc, $cid);
        $msg = $stmt->execute() ? 'Categoría actualizada.' : 'Error: ' . $conn->error;
        if ($conn->error) $msgType = 'danger';
        $stmt->close();
    }
}

// Eliminar
if ($action === 'delete' && $cid) {
    $stmt = $conn->prepare("DELETE FROM Categoria WHERE id_categoria=?");
    $stmt->bind_param('i', $cid);
    if ($stmt->execute() && $conn->affected_rows > 0) {
        $msg = 'Categoría eliminada.';
    } else {
        $msg = 'No se puede eliminar (tiene productos asociados).';
        $msgType = 'danger';
    }
    $stmt->close();
    $action = '';
}

// Cargar para editar
$catEdit = null;
if ($action === 'edit' && $cid) {
    $s = $conn->prepare("SELECT * FROM Categoria WHERE id_categoria=?");
    $s->bind_param('i', $cid);
    $s->execute();
    $catEdit = $s->get_result()->fetch_assoc();
    $s->close();
}

$categorias = $conn->query("
    SELECT c.*, COUNT(p.id_producto) AS total_productos
    FROM Categoria c
    LEFT JOIN Producto p ON c.id_categoria = p.id_categoria
    GROUP BY c.id_categoria ORDER BY c.id_categoria DESC
");

require __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?>
    <div class="alert-gaming-<?= $msgType === 'danger' ? 'danger' : 'success' ?> mb-3 alert-auto">
        <i class="fas fa-<?= $msgType === 'danger' ? 'exclamation-circle' : 'check-circle' ?> me-2"></i>
        <?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin:0">
            <i class="fas fa-tags me-2" style="color:#7c3aed"></i>Gestión de Categorías
        </h4>
        <p style="color:#94a3b8;font-size:.85rem;margin:0">Organiza los productos por plataforma o tipo</p>
    </div>
    <a href="?action=new" class="btn btn-gaming"><i class="fas fa-plus me-2"></i>Nueva Categoría</a>
</div>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card-gaming p-4 mb-4">
    <h6 class="mb-3" style="font-family:'Rajdhani',sans-serif;font-weight:700">
        <?= $action === 'edit' ? '<i class="fas fa-edit me-2"></i>Editar Categoría' : '<i class="fas fa-plus-circle me-2"></i>Nueva Categoría' ?>
    </h6>
    <form method="post" id="catForm" onsubmit="return validateForm('catForm')">
        <input type="hidden" name="_action" value="<?= $action === 'edit' ? 'editar' : 'crear' ?>">
        <?php if ($catEdit): ?>
            <input type="hidden" name="id_categoria" value="<?= $catEdit['id_categoria'] ?>">
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre de la categoría *</label>
                <input type="text" name="nombre_categoria" class="form-control form-control-gaming"
                       value="<?= sanitize($catEdit['nombre_categoria'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Descripción</label>
                <input type="text" name="descripcion" class="form-control form-control-gaming"
                       value="<?= sanitize($catEdit['descripcion'] ?? '') ?>">
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-gaming">
                <i class="fas fa-save me-2"></i><?= $action === 'edit' ? 'Guardar' : 'Crear' ?>
            </button>
            <a href="<?= BASE_URL ?>/admin/categorias.php" class="btn btn-outline-gaming">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card-gaming p-0">
    <div class="table-responsive">
        <table class="table table-gaming w-100 mb-0">
            <thead>
                <tr><th>#</th><th>Categoría</th><th>Descripción</th><th>Productos</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php while ($c = $categorias->fetch_assoc()): ?>
                <tr>
                    <td style="color:#94a3b8"><?= $c['id_categoria'] ?></td>
                    <td style="font-weight:600"><?= sanitize($c['nombre_categoria']) ?></td>
                    <td style="color:#94a3b8;font-size:.88rem"><?= sanitize($c['descripcion'] ?: '—') ?></td>
                    <td>
                        <span class="badge-gaming pendiente"><?= $c['total_productos'] ?> productos</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?action=edit&id=<?= $c['id_categoria'] ?>" class="btn btn-sm btn-outline-gaming">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?action=delete&id=<?= $c['id_categoria'] ?>"
                               style="border:1px solid #ef4444;color:#ef4444;border-radius:8px;padding:.3rem .6rem;font-size:.85rem;text-decoration:none"
                               onclick="return confirmDelete('¿Eliminar esta categoría?')">
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
