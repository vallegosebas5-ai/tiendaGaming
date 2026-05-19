<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? 'Admin' ?> — GameZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-wrapper">
<!-- ── Sidebar ─────────────────────────────────── -->
<aside class="admin-sidebar">
    <a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-brand">
        GAME<span>ZONE</span> <small style="font-size:.65rem;color:#94a3b8;display:block;margin-top:-4px;letter-spacing:.04em">ADMIN PANEL</small>
    </a>

    <div class="sidebar-label">Principal</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/admin/index.php" class="<?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
    </nav>

    <div class="sidebar-label">Catálogo</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/admin/productos.php" class="<?= ($pageTitle ?? '') === 'Productos' ? 'active' : '' ?>">
            <i class="fas fa-gamepad"></i> Productos
        </a>
        <a href="<?= BASE_URL ?>/admin/categorias.php" class="<?= ($pageTitle ?? '') === 'Categorías' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Categorías
        </a>
    </nav>

    <div class="sidebar-label">Ventas</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/admin/ventas.php" class="<?= ($pageTitle ?? '') === 'Ventas' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i> Ventas
        </a>
    </nav>

    <div class="sidebar-label">Usuarios</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/admin/usuarios.php" class="<?= ($pageTitle ?? '') === 'Usuarios' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Usuarios
        </a>
    </nav>

    <div class="sidebar-label">Sitio</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/index.php" target="_blank">
            <i class="fas fa-external-link-alt"></i> Ver Tienda
        </a>
        <a href="<?= BASE_URL ?>/auth/logout.php" style="color:#f87171 !important">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </nav>
</aside>

<!-- ── Contenido principal ────────────────────── -->
<div class="admin-content">
    <!-- Topbar -->
    <div class="admin-topbar">
        <h6 class="mb-0" style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.1rem">
            <?= sanitize($pageTitle ?? 'Panel Admin') ?>
        </h6>
        <div class="d-flex align-items-center gap-3">
            <span style="color:#94a3b8;font-size:.85rem">
                <i class="fas fa-user-shield me-1" style="color:#7c3aed"></i>
                <?= sanitize($_SESSION['usuario_nombre'] ?? 'Admin') ?>
            </span>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline-gaming">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
    <div class="admin-main">
