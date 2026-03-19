<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Detalle de producto de PrintingBruno.">
    <title>Producto | PrintingBruno</title>
    <link rel="stylesheet" href="css/styles.css?v=20260319-1">
    <link rel="icon" type="image/png" href="assets/logo/logo.png">
    <?php
    require_once __DIR__ . '/partials/site-chrome.php';
    pb_render_analytics_head();
    ?>
</head>

<body>
    <?php
    pb_render_header('catalogo', ['show_cart' => true]);
    ?>

    <section class="page-header">
        <div class="container">
            <h1 class="page-title" id="productPageTitle">Detalle del <span class="accent-text">Producto</span></h1>
            <div class="page-breadcrumb">
                <a href="index.html">Inicio</a>
                <span class="separator">/</span>
                <a href="catalogo.html">Catálogo</a>
                <span class="separator">/</span>
                <span id="productBreadcrumbCurrent">Producto</span>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div id="productDetailRoot" class="product-detail-empty">
                <h2 style="font-family:var(--font-heading);margin-bottom:var(--space-md)">Cargando producto...</h2>
                <p style="color:var(--text-secondary)">Estamos trayendo toda la información del producto.</p>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-bg"></div>
        <div class="container">
            <div class="cta-content reveal">
                <h2 class="cta-title">¿Querés personalizar este producto?</h2>
                <p class="cta-text">Escribinos por WhatsApp y te ayudamos a llevarlo a tu idea exacta.</p>
                <div class="cta-buttons">
                    <a href="https://wa.me/5491137022937?text=Hola!%20Quiero%20consultar%20por%20un%20producto" target="_blank" class="btn btn-whatsapp btn-lg">
                        Consultanos
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php pb_render_footer(); ?>
    <?php pb_render_cart_drawer(); ?>

    <script src="js/cart.js?v=20260316-2"></script>
    <script src="js/products.js?v=20260319-1"></script>
    <script src="js/main.js?v=20260315-4"></script>
    <script src="js/product-detail.js?v=20260316-2"></script>
</body>

</html>
