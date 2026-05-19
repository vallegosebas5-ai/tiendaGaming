<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
if (!isAdmin()) redirect('/auth/login.php');

$pageTitle = 'Usuarios';
$msg = '';

// Cambiar rol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_usuario'], $_POST['rol'])) {
    $uid = (int)$_POST['id_usuario'];
    $rol = $_POST['rol'];
    if ($uid === $_SESSION['usuario_id']) {
        $msg = 'No puedes cambiar tu propio rol.';
    } elseif (in_array($rol, ['admin','cliente'])) {
        $stmt = $conn->prepare("UPDATE Usuario SET rol=? WHERE id_usuario=?");
        $stmt->bind_param('si', $rol, $uid);
        $msg = $stmt->execute() ? 'Rol actualizado.' : 'Error: ' . $conn->error;
        $stmt->close();
    }
}

// Eliminar usuario
$action = $_GET['action'] ?? '';
$uid    = (int)($_GET['id'] ?? 0);
if ($action === 'delete' && $uid && $uid !== $_SESSION['usuario_id']) {
    $stmt = $conn->prepare("DELETE FROM Usuario WHERE id_usuario=?");
    $stmt->bind_param('i', $uid);
    if ($stmt->execute() && $conn->affected_rows > 0) {
        $msg = 'Usuario eliminado.';
    } else {
        $msg = 'No se puede eliminar (tiene ventas asociadas).';
    }
    $stmt->close();
}

$usuarios = $conn->query("
    SELECT u.*, COUNT(v.id_venta) AS total_compras
    FROM Usuario u
    LEFT JOIN Venta v ON u.id_usuario = v.id_usuario
    GROUP BY u.id_usuario ORDER BY u.fecha_registro DESC
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
            <i class="fas fa-users me-2" style="color:#7c3aed"></i>Usuarios Registrados
        </h4>
        <p style="color:#94a3b8;font-size:.85rem;margin:0">Gestiona cuentas y roles</p>
    </div>
</div>

<div class="card-gaming p-0">
    <div class="table-responsive">
        <table class="table table-gaming w-100 mb-0">
            <thead>
                <tr><th>#</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Compras</th><th>Registro</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php while ($u = $usuarios->fetch_assoc()): ?>
                <tr>
                    <td style="color:#94a3b8"><?= $u['id_usuario'] ?></td>
                    <td>
                        <div style="font-weight:600"><?= sanitize($u['nombre']) ?></div>
                    </td>
                    <td style="color:#94a3b8;font-size:.88rem"><?= sanitize($u['correo']) ?></td>
                    <td><span class="badge-gaming <?= $u['rol'] ?>"><?= ucfirst($u['rol']) ?></span></td>
                    <td style="text-align:center"><?= $u['total_compras'] ?></td>
                    <td style="color:#94a3b8;font-size:.82rem"><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                    <td>
                        <?php if ($u['id_usuario'] !== $_SESSION['usuario_id']): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                            <select name="rol" class="form-select form-control-gaming d-inline-block"
                                    style="width:auto;font-size:.8rem;padding:.2rem .5rem"
                                    onchange="this.form.submit()">
                                <option value="cliente" <?= $u['rol']==='cliente'?'selected':'' ?>>Cliente</option>
                                <option value="admin"   <?= $u['rol']==='admin'  ?'selected':'' ?>>Admin</option>
                            </select>
                        </form>
                        <?php else: ?>
                            <span style="color:#64748b;font-size:.8rem">(Tú)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
