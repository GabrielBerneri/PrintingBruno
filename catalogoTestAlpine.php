<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Catálogo de productos de impresión 3D de PrintingBruno. [Alpine.js test]">
    <title>Catálogo Alpine | PrintingBruno</title>
    <link rel="stylesheet" href="css/styles.css?v=20260331-1">
    <link rel="icon" type="image/png" href="assets/logo/logo.png">
    <!-- Alpine.js CDN — sin build step -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Estilos adicionales específicos de esta vista Alpine */
        .alpine-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--color-accent, #ff6b2b);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: 10px;
            vertical-align: middle;
        }
        .product-card-variant-dot {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.5);
            box-shadow: 0 0 0 1px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: transform 0.15s;
        }
        .product-card-variant-dot:hover { transform: scale(1.2); }
        .product-card-variants {
            display: flex;
            gap: 5px;
            align-items: center;
            margin-bottom: 8px;
        }
        .product-card-variant-more {
            font-size: 0.75rem;
            color: var(--color-text-muted, #888);
        }
        [x-cloak] { display: none !important; }
        .catalog-loading-bar {
            height: 3px;
            background: var(--color-accent, #ff6b2b);
            animation: loadbar 1.2s infinite ease-in-out;
            border-radius: 2px;
            margin-bottom: var(--space-lg, 24px);
        }
        @keyframes loadbar {
            0%   { width: 0%; opacity: 1; }
            70%  { width: 85%; opacity: 1; }
            100% { width: 100%; opacity: 0; }
        }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . '/partials/site-chrome.php';
    pb_render_header('catalogo', ['show_cart' => true]);
    ?>

    <!-- PAGE HEADER -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">
                Nuestro <span class="accent-text">Catálogo</span>
                <span class="alpine-badge">Alpine.js</span>
            </h1>
            <div class="page-breadcrumb">
                <a href="index.html">Inicio</a>
                <span class="separator">/</span>
                <a href="catalogo.php">Catálogo</a>
                <span class="separator">/</span>
                <span>Vista Alpine</span>
            </div>
        </div>
    </section>

    <!-- CATALOG — controlado 100% por Alpine.js -->
    <section class="section" x-data="catalogApp()" x-init="init()">
        <div class="container">

            <!-- Barra de carga -->
            <div class="catalog-loading-bar" x-show="loading" x-cloak></div>

            <div class="catalog-layout">

                <!-- ===== SIDEBAR FILTERS ===== -->
                <aside class="catalog-sidebar">
                    <div class="filter-group">
                        <h3 class="filter-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Categorías
                        </h3>

                        <!-- "Todos" -->
                        <label class="filter-option" :class="{ active: activeCategory === 'all' }">
                            <input type="radio" name="category" value="all"
                                   x-model="activeCategory"> Todos los productos
                        </label>

                        <!-- Categorías dinámicas desde los productos -->
                        <template x-for="cat in availableCategories" :key="cat.value">
                            <label class="filter-option" :class="{ active: activeCategory === cat.value }">
                                <input type="radio" name="category" :value="cat.value"
                                       x-model="activeCategory">
                                <span x-text="cat.label"></span>
                            </label>
                        </template>

                        <div class="filter-divider"></div>

                        <div class="filter-meta">
                            <!-- Contador reactivo -->
                            <span class="filter-meta-count"
                                  x-text="loading ? 'Cargando productos...' : `Mostrando ${filteredProducts.length} producto${filteredProducts.length !== 1 ? 's' : ''}`">
                            </span>
                            <label class="filter-sort-wrap">
                                <span class="filter-sort-label">Ordenar por</span>
                                <select class="filter-sort-select" x-model="sortOrder">
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
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Pedido Personalizado
                    </a>
                </aside>

                <!-- ===== PRODUCTS AREA ===== -->
                <div>
                    <!-- Search bar -->
                    <div class="catalog-search">
                        <svg class="catalog-search-icon" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" class="catalog-search-input"
                               placeholder="Buscar productos..."
                               autocomplete="off"
                               x-model.debounce.300ms="searchQuery">
                        <button class="catalog-search-clear" type="button"
                                aria-label="Limpiar búsqueda"
                                x-show="searchQuery.length > 0"
                                x-cloak
                                @click="searchQuery = ''">✕</button>
                    </div>

                    <!-- Grid de productos -->
                    <div class="products-grid">

                        <!-- Skeleton mientras carga -->
                        <template x-if="loading">
                            <template x-for="i in 6" :key="i">
                                <div class="product-card">
                                    <div class="skeleton-img skeleton"></div>
                                    <div class="product-info">
                                        <div class="skeleton-text short skeleton"></div>
                                        <div class="skeleton-text title skeleton"></div>
                                        <div class="skeleton-text medium skeleton"></div>
                                        <div class="product-footer" style="border:none">
                                            <div class="skeleton-text short skeleton" style="margin:0"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </template>

                        <!-- Error state -->
                        <template x-if="!loading && error">
                            <div class="catalog-empty-state" style="grid-column:1/-1">
                                <svg width="72" height="72" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.6">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <h3 x-text="error"></h3>
                                <p>Intentá de nuevo en unos minutos.</p>
                                <button class="btn btn-primary" @click="init()">Reintentar</button>
                            </div>
                        </template>

                        <!-- Empty filtered state -->
                        <template x-if="!loading && !error && filteredProducts.length === 0">
                            <div class="catalog-empty-state" style="grid-column:1/-1">
                                <svg width="72" height="72" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.6">
                                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                    <line x1="3" y1="6" x2="21" y2="6"></line>
                                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                                </svg>
                                <h3>Sin resultados</h3>
                                <p>No hay productos que coincidan con tu búsqueda.</p>
                                <button class="btn btn-primary" @click="searchQuery = ''; activeCategory = 'all'">
                                    Ver todos
                                </button>
                            </div>
                        </template>

                        <!-- Product cards -->
                        <template x-if="!loading && !error">
                            <template x-for="product in filteredProducts" :key="product.id">
                                <div class="product-card reveal visible"
                                     :class="{ 'has-secondary-image': secondaryImage(product) }"
                                     :data-category="product.category">

                                    <!-- Imagen -->
                                    <a class="product-link product-image"
                                       :href="productUrl(product)"
                                       :aria-label="'Ver detalle de ' + product.name">

                                        <!-- Badges -->
                                        <template x-if="product.badge">
                                            <span class="product-badge"
                                                  :class="badgeClass(product.badge)"
                                                  x-text="product.badgeLabel || product.badge">
                                            </span>
                                        </template>
                                        <template x-if="product.stock > 0 && product.stock <= 3">
                                            <span class="product-badge urgency"
                                                  x-text="'Últimas ' + product.stock">
                                            </span>
                                        </template>

                                        <img :src="primaryImage(product)"
                                             :alt="product.name"
                                             loading="lazy"
                                             class="product-card-main-image">

                                        <!-- Hover image -->
                                        <template x-if="secondaryImage(product)">
                                            <img :src="secondaryImage(product)"
                                                 :alt="product.name + ' vista 2'"
                                                 loading="lazy"
                                                 class="product-card-secondary-image"
                                                 aria-hidden="true">
                                        </template>
                                    </a>

                                    <!-- Info -->
                                    <div class="product-info">
                                        <span class="product-category"
                                              x-text="categoryLabel(product.category)">
                                        </span>
                                        <h3 class="product-name">
                                            <a class="product-link"
                                               :href="productUrl(product)"
                                               x-text="product.name">
                                            </a>
                                        </h3>
                                        <p class="product-description" x-text="product.description"></p>

                                        <!-- Color swatches -->
                                        <template x-if="activeVariants(product).length > 1">
                                            <div class="product-card-variants">
                                                <template x-for="(v, idx) in activeVariants(product).slice(0,4)" :key="v.id">
                                                    <span class="product-card-variant-dot"
                                                          :title="variantLabel(v)"
                                                          :style="'background:' + swatchBg(v)">
                                                    </span>
                                                </template>
                                                <template x-if="activeVariants(product).length > 4">
                                                    <span class="product-card-variant-more"
                                                          x-text="'+' + (activeVariants(product).length - 4)">
                                                    </span>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Sin stock -->
                                        <template x-if="product.stock <= 0">
                                            <div class="product-stock-status out-of-stock">
                                                Sin stock disponible
                                            </div>
                                        </template>

                                        <!-- Footer: precio + acciones -->
                                        <div class="product-footer"
                                             :class="{ 'product-footer-variant': activeVariants(product).length > 1 }">
                                            <span class="product-price" x-text="priceText(product)"></span>
                                            <div class="product-action">
                                                <a class="btn btn-secondary btn-sm btn-detail"
                                                   :href="productUrl(product)">Ver detalle</a>
                                                <button class="btn btn-primary btn-sm btn-add-cart"
                                                        :class="{ 'has-label': activeVariants(product).length > 1, 'added': addedMap[product.id] }"
                                                        :disabled="product.stock <= 0"
                                                        @click="handleAddToCart(product)"
                                                        :aria-label="activeVariants(product).length > 1 ? 'Elegir variante de ' + product.name : 'Agregar ' + product.name + ' al carrito'"
                                                        :style="addedMap[product.id] ? 'background:#25D366;color:#fff' : ''">
                                                    <span x-text="cartBtnLabel(product)"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </template>

                    </div><!-- /products-grid -->
                </div>
            </div><!-- /catalog-layout -->
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
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Consultanos
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php pb_render_footer(); ?>
    <?php pb_render_cart_drawer(); ?>

    <script src="js/cart.js?v=20260331-1"></script>
    <script src="js/main.js?v=20260331-1"></script>

    <script>
    /* ============================================================
       Alpine.js — Catalog App
       Toda la lógica de filtros, búsqueda, orden y carrito
       vive aquí como un objeto de datos reactivo.
    ============================================================ */
    function catalogApp() {
        return {
            /* ---- Estado ---- */
            allProducts:      [],
            loading:          true,
            error:            null,
            activeCategory:   'all',
            searchQuery:      '',
            sortOrder:        'default',
            addedMap:         {},   // { [productId]: true } para feedback visual

            /* ---- Computed: categorías únicas desde los productos ---- */
            get availableCategories() {
                const labels = {
                    figuras: 'Figuras', decoracion: 'Decoración', funcional: 'Funcional',
                    personalizado: 'Personalizado', mates: 'Mates', filamentos: 'Filamentos',
                    jarras: 'Jarras', insumos: 'Insumos', impresoras: 'Impresoras', llaveros: 'Llaveros'
                };
                const unique = [...new Set(this.allProducts.map(p => p.category).filter(Boolean))];
                return unique
                    .sort((a, b) => (labels[a] || a).localeCompare(labels[b] || b, 'es'))
                    .map(v => ({ value: v, label: labels[v] || v }));
            },

            /* ---- Computed: productos filtrados + ordenados ---- */
            get filteredProducts() {
                let list = this.allProducts;

                // Filtro por categoría
                if (this.activeCategory !== 'all') {
                    list = list.filter(p => p.category === this.activeCategory);
                }

                // Búsqueda por texto
                const q = this.searchQuery.trim().toLowerCase();
                if (q) {
                    list = list.filter(p =>
                        (p.name || '').toLowerCase().includes(q) ||
                        (p.description || '').toLowerCase().includes(q) ||
                        (p.category || '').toLowerCase().includes(q)
                    );
                }

                // Ordenamiento
                list = [...list];
                if (this.sortOrder === 'name-asc')   list.sort((a,b) => a.name.localeCompare(b.name, 'es'));
                if (this.sortOrder === 'name-desc')  list.sort((a,b) => b.name.localeCompare(a.name, 'es'));
                if (this.sortOrder === 'price-asc')  list.sort((a,b) => this.displayPrice(a) - this.displayPrice(b));
                if (this.sortOrder === 'price-desc') list.sort((a,b) => this.displayPrice(b) - this.displayPrice(a));
                if (this.sortOrder === 'newest')     list.sort((a,b) => (b.id || 0) - (a.id || 0));

                return list;
            },

            /* ---- Lifecycle ---- */
            async init() {
                this.loading = true;
                this.error   = null;
                try {
                    const res = await fetch('api/products.php', { cache: 'no-store' });
                    if (!res.ok) throw new Error('Error al cargar productos');
                    const data = await res.json();
                    this.allProducts = data.products || [];
                } catch (e) {
                    this.error = 'No pudimos cargar el catálogo.';
                } finally {
                    this.loading = false;
                }
            },

            /* ---- Helpers de producto ---- */
            activeVariants(product) {
                if (!Array.isArray(product?.variants)) return [];
                return product.variants.filter(v => Number(v?.active ?? 1) === 1);
            },

            defaultVariant(product) {
                const variants = this.activeVariants(product);
                if (!variants.length) return null;
                const prefId = Number(product?.default_variant_id || 0);
                return variants.find(v => Number(v.id) === prefId) || variants[0];
            },

            displayPrice(product) {
                const v = this.defaultVariant(product);
                if (v?.final_price != null) return Number(v.final_price);
                if (product?.price_from != null) return Number(product.price_from);
                return Number(product?.price || 0);
            },

            priceText(product) {
                const variants = this.activeVariants(product);
                const hasRange = variants.length > 1 &&
                    Number(product?.price_from || 0) !== Number(product?.price_to || 0);
                const price = '$' + Math.round(this.displayPrice(product)).toLocaleString('es-AR');
                return hasRange ? 'Desde ' + price : price;
            },

            primaryImage(product) {
                const v = this.defaultVariant(product);
                const imgs = this.imageUrls(product, v);
                return imgs[0] || '';
            },

            secondaryImage(product) {
                const v = this.defaultVariant(product);
                const imgs = this.imageUrls(product, v);
                return imgs[1] || null;
            },

            imageUrls(product, variant = null) {
                const vi = Array.isArray(variant?.image_urls) && variant.image_urls.length
                    ? variant.image_urls : [];
                if (vi.length) return vi;
                if (Array.isArray(product?.image_urls) && product.image_urls.length) return product.image_urls;
                return product?.image_url ? [product.image_url] : [];
            },

            variantLabel(v) {
                return String(v?.label || v?.primary_color || v?.secondary_color || 'Base').trim();
            },

            swatchBg(v) {
                const hex = {
                    rojo:'#d83b3b', blanco:'#f4f4f1', negro:'#1a1a1a', gris:'#8c8f96',
                    azul:'#2f63d8', verde:'#2f9d63', dorado:'#c9a227', celeste:'#6bbef0', rosa:'#ef7ca8'
                };
                const resolve = name => hex[String(name||'').trim().toLowerCase()] || '#ccc';
                const primary   = resolve(v.primary_color || v.label);
                const secondary = v.secondary_color ? resolve(v.secondary_color) : null;
                return secondary
                    ? `linear-gradient(135deg,${primary} 0%,${primary} 52%,${secondary} 52%,${secondary} 100%)`
                    : primary;
            },

            productUrl(product) {
                return product?.slug
                    ? `producto.php?slug=${encodeURIComponent(product.slug)}`
                    : `producto.php?id=${encodeURIComponent(product?.id ?? '')}`;
            },

            categoryLabel(cat) {
                const map = {
                    figuras:'Figuras', decoracion:'Decoración', funcional:'Funcional',
                    personalizado:'Personalizado', mates:'Mates', filamentos:'Filamentos',
                    jarras:'Jarras', insumos:'Insumos', impresoras:'Impresoras', llaveros:'Llaveros'
                };
                return map[cat] || cat;
            },

            badgeClass(badge) {
                const b = String(badge||'').trim().toLowerCase();
                if (b.includes('nuevo')) return 'new';
                if (b.includes('popular') || b.includes('vendido')) return 'popular';
                return 'sale';
            },

            /* ---- Carrito ---- */
            cartBtnLabel(product) {
                if (product.stock <= 0) return '—';
                if (this.addedMap[product.id]) return '✓';
                if (this.activeVariants(product).length > 1) return 'Elegir';
                return '+';
            },

            handleAddToCart(product) {
                if (product.stock <= 0) return;

                // Si tiene variantes, redirigir al detalle
                if (this.activeVariants(product).length > 1) {
                    window.location.href = this.productUrl(product);
                    return;
                }

                const variant = this.defaultVariant(product);
                const label   = this.variantLabel(variant);
                const imgs    = this.imageUrls(product, variant);

                Cart.addItem({
                    id:            product.id,
                    product_id:    product.id,
                    variant_id:    variant?.id || null,
                    variant_label: variant && label !== 'Base' ? label : '',
                    cart_key:      variant?.id ? `v:${variant.id}` : `p:${product.id}`,
                    name:          product.name,
                    price:         this.displayPrice(product),
                    image_url:     imgs[0] || product.image_url
                });

                // Feedback visual reactivo
                this.addedMap = { ...this.addedMap, [product.id]: true };
                setTimeout(() => {
                    const updated = { ...this.addedMap };
                    delete updated[product.id];
                    this.addedMap = updated;
                }, 1500);
            }
        };
    }
    </script>

</body>
</html>
