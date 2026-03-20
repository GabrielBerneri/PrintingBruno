<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Catálogo de productos de impresión 3D de PrintingBruno. Figuras, decoración, piezas funcionales y más.">
    <title>Catálogo | PrintingBruno</title>
    <link rel="stylesheet" href="css/styles.css?v=20260319-4">
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

    <!-- PAGE HEADER -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Nuestro <span class="accent-text">Catálogo</span></h1>
            <div class="page-breadcrumb">
                <a href="index.html">Inicio</a>
                <span class="separator">/</span>
                <span>Catálogo</span>
            </div>
        </div>
    </section>

    <!-- CATALOG -->
    <section class="section">
        <div class="container">
            <div class="catalog-layout">
                <!-- Sidebar Filters -->
                <aside class="catalog-sidebar">
                    <div class="filter-group">
                        <h3 class="filter-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Categorías
                        </h3>
                        <label class="filter-option active" data-filter="all">
                            <input type="radio" name="category" value="all" checked> Todos los productos
                        </label>
                        <label class="filter-option" data-filter="mates">
                            <input type="radio" name="category" value="mates"> Mates
                        </label>
                        <label class="filter-option" data-filter="personalizado">
                            <input type="radio" name="category" value="personalizado"> Personalizado
                        </label>
                        <label class="filter-option" data-filter="filamentos">
                            <input type="radio" name="category" value="filamentos"> Filamentos
                        </label>

                        <div class="filter-divider"></div>

                        <div class="filter-meta">
                            <span class="filter-meta-count" id="productCount">Cargando productos...</span>
                            <label class="filter-sort-wrap" for="sortSelect">
                                <span class="filter-sort-label">Ordenar por</span>
                                <select id="sortSelect" class="filter-sort-select">
                                    <option value="default">Destacados</option>
                                    <option value="name-asc">Nombre A-Z</option>
                                    <option value="name-desc">Nombre Z-A</option>
                                    <option value="price-asc">Precio: Menor a Mayor</option>
                                    <option value="price-desc">Precio: Mayor a Menor</option>
                                    <option value="newest">Más recientes</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <a href="https://wa.me/5491137022937?text=Hola!%20Quiero%20pedir%20algo%20personalizado"
                        target="_blank" class="btn btn-whatsapp" style="width: 100%; margin-top: var(--space-md);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        Pedido Personalizado
                    </a>
                </aside>

                <!-- Products -->
                <div>
                    <!-- Search Bar -->
                    <div class="catalog-search">
                        <svg class="catalog-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="catalogSearch" class="catalog-search-input" placeholder="Buscar productos..." autocomplete="off">
                        <button class="catalog-search-clear" id="catalogSearchClear" type="button" aria-label="Limpiar búsqueda" style="display:none">✕</button>
                    </div>

                    <div class="products-grid" id="catalogGrid">
                        <!-- Products loaded dynamically from API -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="cta-bg"></div>
        <div class="container">
            <div class="cta-content reveal">
                <h2 class="cta-title">¿No encontrás lo que buscás?</h2>
                <p class="cta-text">Podemos crear cualquier pieza a medida. Escribinos y contanos tu idea.</p>
                <div class="cta-buttons">
                    <a href="https://wa.me/5491137022937?text=Hola!%20Quiero%20un%20producto%20personalizado"
                        target="_blank" class="btn btn-whatsapp btn-lg">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
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
    <script>Products.loadCatalog('catalogGrid');</script>
</body>

</html>
