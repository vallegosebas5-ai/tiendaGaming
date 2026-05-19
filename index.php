<?php
require_once __DIR__ . '/config/db.php';
session_start_once();

// Productos destacados (últimos 6 activos)
$destacados = $conn->query("
    SELECT p.*, c.nombre_categoria
    FROM Producto p JOIN Categoria c ON p.id_categoria=c.id_categoria
    WHERE p.estado='activo'
    ORDER BY p.id_producto DESC LIMIT 6
");

// Categorías con conteo
$categorias = $conn->query("
    SELECT c.*, COUNT(p.id_producto) AS total
    FROM Categoria c
    LEFT JOIN Producto p ON c.id_categoria=p.id_categoria AND p.estado='activo'
    GROUP BY c.id_categoria ORDER BY total DESC
");

$catIcons = ['PlayStation'=>'fa-playstation','Xbox'=>'fa-xbox','Nintendo'=>'fa-dragon','PC Gaming'=>'fa-desktop','Accesorios'=>'fa-headphones'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GameZone — Tu Tienda Gaming</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════════════ -->
<nav class="navbar-gaming navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">GAME<span>ZONE</span></a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"
                style="border-color:#7c3aed">
            <i class="fas fa-bars" style="color:#7c3aed"></i>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item"><a class="nav-link" href="#productos">
                    <i class="fas fa-gamepad"></i> Productos
                </a></li>
                <li class="nav-item"><a class="nav-link" href="#categorias">
                    <i class="fas fa-tags"></i> Categorías
                </a></li>
                <li class="nav-item"><a class="nav-link" href="#beneficios">
                    <i class="fas fa-star"></i> Beneficios
                </a></li>
                <li class="nav-item"><a class="nav-link" href="#nosotros">
                    <i class="fas fa-info-circle"></i> Nosotros
                </a></li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/<?= isAdmin() ? 'admin/index.php' : 'cliente/catalogo.php' ?>"
                       class="btn btn-gaming btn-sm">
                        <i class="fas fa-th-large me-1"></i>
                        <?= isAdmin() ? 'Panel Admin' : 'Ir a la tienda' ?>
                    </a>
                    <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline-gaming btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Salir
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-gaming btn-sm">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a href="<?= BASE_URL ?>/auth/registro.php" class="btn btn-gaming btn-sm">
                        <i class="fas fa-user-plus me-1"></i>Registrarse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════ -->
<section class="hero">
    <div class="container position-relative" style="z-index:2">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="fas fa-bolt me-1"></i> Nueva temporada gaming
                </div>
                <h1 class="hero-title">
                    Tu próxima<br>
                    <span class="highlight">aventura</span><br>
                    te espera<span class="highlight-c">.</span>
                </h1>
                <p class="mt-3 mb-4" style="color:#94a3b8;font-size:1.05rem;max-width:460px">
                    Los mejores videojuegos para PlayStation, Xbox, Nintendo y PC.
                    Envío gratis en todos tus pedidos.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= BASE_URL ?>/<?= isLoggedIn() ? 'cliente/catalogo.php' : 'auth/registro.php' ?>"
                       class="btn btn-gaming" style="padding:.75rem 2rem;font-size:1rem">
                        <i class="fas fa-gamepad me-2"></i>
                        <?= isLoggedIn() ? 'Ver Catálogo' : 'Empezar ahora' ?>
                    </a>
                    <a href="#productos" class="btn btn-outline-gaming" style="padding:.75rem 2rem;font-size:1rem">
                        <i class="fas fa-play-circle me-2"></i>Ver productos
                    </a>
                </div>
                <!-- Stats hero -->
                <div class="d-flex flex-wrap gap-4 mt-5">
                    <div>
                        <div style="font-family:'Rajdhani',sans-serif;font-size:2rem;font-weight:700;color:#f1f5f9">+100</div>
                        <div style="color:#94a3b8;font-size:.8rem">Títulos</div>
                    </div>
                    <div style="width:1px;background:#1e293b"></div>
                    <div>
                        <div style="font-family:'Rajdhani',sans-serif;font-size:2rem;font-weight:700;color:#f1f5f9">5</div>
                        <div style="color:#94a3b8;font-size:.8rem">Plataformas</div>
                    </div>
                    <div style="width:1px;background:#1e293b"></div>
                    <div>
                        <div style="font-family:'Rajdhani',sans-serif;font-size:2rem;font-weight:700;color:#f1f5f9">24h</div>
                        <div style="color:#94a3b8;font-size:.8rem">Despacho</div>
                    </div>
                </div>
            </div>

            <!-- Decoración visual hero -->
            <div class="col-lg-6 d-none d-lg-flex justify-content-center align-items-center">
                <div style="position:relative;width:380px;height:380px">
                    <!-- Círculo externo animado -->
                    <div style="position:absolute;inset:0;border-radius:50%;border:2px solid rgba(124,58,237,.2);animation:spin 20s linear infinite"></div>
                    <div style="position:absolute;inset:20px;border-radius:50%;border:1px solid rgba(6,182,212,.15);animation:spin 15s linear infinite reverse"></div>
                    <!-- Centro -->
                    <div style="position:absolute;inset:60px;border-radius:50%;background:linear-gradient(135deg,rgba(124,58,237,.15),rgba(6,182,212,.1));
                                display:flex;align-items:center;justify-content:center;flex-direction:column;
                                border:2px solid rgba(124,58,237,.3)">
                        <i class="fas fa-gamepad" style="font-size:4.5rem;color:rgba(124,58,237,.8);margin-bottom:.5rem"></i>
                        <span style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.4rem;color:rgba(255,255,255,.8)">GAME<span style="color:#7c3aed">ZONE</span></span>
                    </div>
                    <!-- Iconos orbitales -->
                    <?php
                    $orbitItems = [
                        ['icon'=>'fa-playstation','top'=>'5%','left'=>'40%','color'=>'#3b82f6'],
                        ['icon'=>'fa-xbox','top'=>'40%','right'=>'2%','color'=>'#22c55e','left'=>null],
                        ['icon'=>'fa-dragon','top'=>'78%','left'=>'45%','color'=>'#ef4444'],
                        ['icon'=>'fa-desktop','top'=>'35%','left'=>'0%','color'=>'#06b6d4'],
                    ];
                    foreach ($orbitItems as $o):
                        $pos = "top:{$o['top']};";
                        $pos .= isset($o['right']) ? "right:{$o['right']};" : "left:{$o['left']};";
                    ?>
                    <div style="position:absolute;<?= $pos ?>width:48px;height:48px;border-radius:50%;
                                background:rgba(10,10,26,.9);border:1px solid rgba(255,255,255,.1);
                                display:flex;align-items:center;justify-content:center">
                        <i class="fab <?= $o['icon'] ?>" style="color:<?= $o['color'] ?>;font-size:1.3rem"></i>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     CATEGORÍAS
═══════════════════════════════════════════════════ -->
<section class="section section-alt" id="categorias">
    <div class="container">
        <div class="text-center mb-5">
            <span class="divider-line"></span>
            <h2 class="section-title">Explora por <span>Plataforma</span></h2>
            <p class="section-subtitle mx-auto">Encuentra los títulos de tu consola favorita</p>
        </div>
        <div class="row g-3 justify-content-center">
        <?php
        $categorias->data_seek(0);
        while ($c = $categorias->fetch_assoc()):
            $icon = $catIcons[$c['nombre_categoria']] ?? 'fa-tag';
            $link = isLoggedIn()
                ? BASE_URL . '/cliente/catalogo.php?cat=' . $c['id_categoria']
                : BASE_URL . '/auth/login.php';
        ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= $link ?>" class="cat-card">
                    <i class="fab <?= $icon ?>" style="color:#7c3aed"></i>
                    <h5><?= sanitize($c['nombre_categoria']) ?></h5>
                    <small style="color:#64748b"><?= $c['total'] ?> productos</small>
                </a>
            </div>
        <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     PRODUCTOS DESTACADOS
═══════════════════════════════════════════════════ -->
<section class="section" id="productos">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-5 flex-wrap gap-3">
            <div>
                <span class="divider-line d-block"></span>
                <h2 class="section-title">Productos <span>Destacados</span></h2>
                <p class="section-subtitle">Los títulos más populares de esta temporada</p>
            </div>
            <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/cliente/catalogo.php" class="btn btn-outline-gaming">
                Ver todos <i class="fas fa-arrow-right ms-1"></i>
            </a>
            <?php endif; ?>
        </div>

        <div class="row g-4">
        <?php while ($p = $destacados->fetch_assoc()):
            $phClass = 'ph-' . (($p['id_producto'] % 12) + 1);
            $ico = $catIcons[$p['nombre_categoria']] ?? 'fa-gamepad';
            $link = isLoggedIn()
                ? BASE_URL . '/cliente/producto.php?id=' . $p['id_producto']
                : BASE_URL . '/auth/login.php';
        ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card-gaming h-100 d-flex flex-column">
                    <a href="<?= $link ?>">
                        <div class="product-placeholder <?= $phClass ?>">
                            <i class="fab <?= $ico ?>"></i>
                            <span><?= sanitize($p['nombre']) ?></span>
                        </div>
                    </a>
                    <div class="card-body d-flex flex-column">
                        <div class="card-marca"><?= sanitize($p['nombre_categoria']) ?></div>
                        <a href="<?= $link ?>" class="card-title text-decoration-none d-block">
                            <?= sanitize($p['nombre']) ?>
                        </a>
                        <div style="color:#94a3b8;font-size:.8rem;margin-bottom:.75rem"><?= sanitize($p['marca']) ?></div>
                        <p style="color:#64748b;font-size:.82rem;line-height:1.5;flex-grow:1">
                            <?= sanitize(mb_strimwidth($p['descripcion'] ?? '', 0, 80, '...')) ?>
                        </p>
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <span class="precio"><?= formatPrecio($p['precio']) ?></span>
                            <a href="<?= $link ?>" class="btn btn-gaming btn-sm">
                                <i class="fas fa-<?= isLoggedIn() ? 'cart-plus' : 'sign-in-alt' ?> me-1"></i>
                                <?= isLoggedIn() ? 'Ver' : 'Login' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     BENEFICIOS
═══════════════════════════════════════════════════ -->
<section class="section section-alt" id="beneficios">
    <div class="container">
        <div class="text-center mb-5">
            <span class="divider-line"></span>
            <h2 class="section-title">¿Por qué elegir <span>GameZone</span>?</h2>
        </div>
        <div class="row g-4">
            <?php
            $beneficios = [
                ['icon'=>'fa-shipping-fast', 'color'=>'#7c3aed', 'titulo'=>'Envío Gratis',
                 'desc'=>'Despacho gratuito en todos tus pedidos, sin mínimo de compra.'],
                ['icon'=>'fa-shield-alt', 'color'=>'#06b6d4', 'titulo'=>'Compra Segura',
                 'desc'=>'Tus datos protegidos con cifrado SSL y autenticación 2FA.'],
                ['icon'=>'fa-undo', 'color'=>'#22c55e', 'titulo'=>'30 días de garantía',
                 'desc'=>'No te gustó el producto? Te devolvemos tu dinero sin preguntas.'],
                ['icon'=>'fa-headset', 'color'=>'#f59e0b', 'titulo'=>'Soporte 24/7',
                 'desc'=>'Equipo de gamer disponibles para ayudarte en todo momento.'],
                ['icon'=>'fa-gamepad', 'color'=>'#ef4444', 'titulo'=>'+500 Títulos',
                 'desc'=>'El catálogo más completo de videojuegos de todas las plataformas.'],
                ['icon'=>'fa-star', 'color'=>'#a855f7', 'titulo'=>'Programa Gaming+',
                 'desc'=>'Acumula puntos en cada compra y canjéalos por descuentos exclusivos.'],
            ];
            foreach ($beneficios as $b):
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-gaming p-4 h-100">
                    <div style="width:50px;height:50px;border-radius:12px;
                                background:<?= $b['color'] ?>22;
                                display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                        <i class="fas <?= $b['icon'] ?>" style="color:<?= $b['color'] ?>;font-size:1.3rem"></i>
                    </div>
                    <h5 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin-bottom:.5rem">
                        <?= $b['titulo'] ?>
                    </h5>
                    <p style="color:#94a3b8;font-size:.9rem;margin:0"><?= $b['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     NOSOTROS / CTA
═══════════════════════════════════════════════════ -->
<section class="section" id="nosotros">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="divider-line d-block"></span>
                <h2 class="section-title">La tienda gamer <span>que buscabas</span></h2>
                <p style="color:#94a3b8;margin:1rem 0 1.5rem">
                    En GameZone somos apasionados de los videojuegos. Fundada por gamers para gamers,
                    ofrecemos los mejores títulos al mejor precio con la experiencia de compra más simple
                    y segura del mercado.
                </p>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2" style="color:#94a3b8">
                        <i class="fas fa-check-circle" style="color:#22c55e"></i>
                        Autenticación de dos factores en cada inicio de sesión
                    </div>
                    <div class="d-flex align-items-center gap-2" style="color:#94a3b8">
                        <i class="fas fa-check-circle" style="color:#22c55e"></i>
                        Contraseñas cifradas con BCrypt
                    </div>
                    <div class="d-flex align-items-center gap-2" style="color:#94a3b8">
                        <i class="fas fa-check-circle" style="color:#22c55e"></i>
                        Carrito y favoritos personalizados por usuario
                    </div>
                    <div class="d-flex align-items-center gap-2" style="color:#94a3b8">
                        <i class="fas fa-check-circle" style="color:#22c55e"></i>
                        Panel administrativo completo
                    </div>
                </div>
                <div class="mt-4 d-flex gap-3 flex-wrap">
                    <a href="<?= BASE_URL ?>/auth/registro.php" class="btn btn-gaming">
                        <i class="fas fa-user-plus me-2"></i>Crear cuenta gratis
                    </a>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-gaming">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar sesión
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <!-- Tarjetas de características -->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="card-gaming p-3 text-center">
                            <div style="font-family:'Rajdhani',sans-serif;font-size:2.5rem;font-weight:700;color:#7c3aed">99%</div>
                            <div style="color:#94a3b8;font-size:.85rem">Satisfacción</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card-gaming p-3 text-center">
                            <div style="font-family:'Rajdhani',sans-serif;font-size:2.5rem;font-weight:700;color:#06b6d4">24h</div>
                            <div style="color:#94a3b8;font-size:.85rem">Entrega</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card-gaming p-3 text-center">
                            <div style="font-family:'Rajdhani',sans-serif;font-size:2.5rem;font-weight:700;color:#22c55e">500+</div>
                            <div style="color:#94a3b8;font-size:.85rem">Títulos</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card-gaming p-3 text-center">
                            <div style="font-family:'Rajdhani',sans-serif;font-size:2.5rem;font-weight:700;color:#f59e0b">5★</div>
                            <div style="color:#94a3b8;font-size:.85rem">Calificación</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     BANNER PROMOCIONAL
═══════════════════════════════════════════════════ -->
<section style="background:linear-gradient(135deg,rgba(124,58,237,.15),rgba(6,182,212,.1));border-top:1px solid #1e293b;border-bottom:1px solid #1e293b;padding:3rem 0">
    <div class="container text-center">
        <h3 style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:2rem">
            <i class="fas fa-bolt me-2" style="color:#f59e0b"></i>
            Envío <span style="color:#7c3aed">GRATIS</span> en todos los pedidos
        </h3>
        <p style="color:#94a3b8;margin:.5rem 0 1.5rem">Sin mínimo de compra. Regístrate hoy y recibe tu primera orden sin costo.</p>
        <a href="<?= BASE_URL ?>/auth/registro.php" class="btn btn-gaming" style="padding:.75rem 2.5rem;font-size:1rem">
            <i class="fas fa-user-plus me-2"></i>Únete a GameZone
        </a>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════ -->
<footer class="footer-gaming">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="footer-brand mb-2">GAME<span>ZONE</span></div>
                <p style="font-size:.88rem;line-height:1.7">
                    Tu tienda de videojuegos online con el mayor catálogo de PlayStation, Xbox, Nintendo, PC y accesorios gaming.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" style="color:#94a3b8;font-size:1.2rem"><i class="fab fa-facebook"></i></a>
                    <a href="#" style="color:#94a3b8;font-size:1.2rem"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color:#94a3b8;font-size:1.2rem"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color:#94a3b8;font-size:1.2rem"><i class="fab fa-youtube"></i></a>
                    <a href="#" style="color:#94a3b8;font-size:1.2rem"><i class="fab fa-twitch"></i></a>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <h5>Tienda</h5>
                <a href="#productos">Destacados</a>
                <a href="#categorias">Categorías</a>
                <a href="<?= BASE_URL ?>/auth/registro.php">Registrarse</a>
                <a href="<?= BASE_URL ?>/auth/login.php">Iniciar Sesión</a>
            </div>
            <div class="col-md-2 col-6">
                <h5>Plataformas</h5>
                <a href="#">PlayStation</a>
                <a href="#">Xbox</a>
                <a href="#">Nintendo</a>
                <a href="#">PC Gaming</a>
                <a href="#">Accesorios</a>
            </div>
            <div class="col-md-4">
                <h5>Newsletter Gaming</h5>
                <p style="font-size:.85rem">Recibe las últimas novedades y ofertas exclusivas</p>
                <div class="d-flex gap-2">
                    <input type="email" class="form-control form-control-gaming" placeholder="tu@correo.com">
                    <button class="btn btn-gaming btn-sm px-3">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="mt-3" style="font-size:.8rem;color:#64748b">
                    <i class="fas fa-lock me-1"></i>Datos protegidos con 2FA y BCrypt
                </div>
            </div>
        </div>
        <hr style="border-color:#1e293b">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2" style="font-size:.82rem">
            <span>&copy; <?= date('Y') ?> GameZone. Todos los derechos reservados.</span>
            <div class="d-flex gap-3">
                <a href="#" style="color:#64748b">Privacidad</a>
                <a href="#" style="color:#64748b">Términos</a>
                <a href="#" style="color:#64748b">Soporte</a>
            </div>
        </div>
    </div>
</footer>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
