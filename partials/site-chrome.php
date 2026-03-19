<?php

function pb_nav_items(): array
{
    return [
        ['key' => 'home', 'href' => 'index.html', 'label' => 'Inicio'],
        ['key' => 'catalogo', 'href' => 'catalogo.html', 'label' => 'Catálogo'],
        ['key' => 'nosotros', 'href' => 'nosotros.html', 'label' => 'Nosotros'],
        ['key' => 'contacto', 'href' => 'contacto.html', 'label' => 'Contacto'],
        ['key' => 'cuenta', 'href' => 'mi-cuenta.php', 'label' => 'Mi cuenta'],
    ];
}

function pb_category_items(): array
{
    return [
        ['href' => 'catalogo.html#mates', 'label' => 'Mates'],
        ['href' => 'catalogo.html#personalizado', 'label' => 'Personalizado'],
        ['href' => 'catalogo.html#filamentos', 'label' => 'Filamentos'],
    ];
}

function pb_render_header(string $activePage, array $options = []): void
{
    $showCart = $options['show_cart'] ?? false;
    ?>
    <header class="header" id="header">
        <div class="container">
            <a href="index.html" class="logo">
                <img class="logo-icon" src="assets/logo/logo.png" alt="PrintingBruno">
                Printing<span>Bruno</span>
            </a>

            <nav class="nav" id="nav">
                <ul class="nav-links">
                    <?php foreach (pb_nav_items() as $item): ?>
                        <?php $isActive = $item['key'] === $activePage; ?>
                        <li>
                            <a
                                href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                                class="nav-link<?= $isActive ? ' active' : '' ?>"
                                <?= $isActive ? 'aria-current="page"' : '' ?>
                            >
                                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="nav-social">
                    <a href="https://www.instagram.com/printing.bruno/" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="https://www.tiktok.com/@printing.bruno" target="_blank" rel="noopener" aria-label="TikTok">
                        <svg class="icon-tiktok" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12.53.02c1.31-.02 2.62-.01 3.93-.02.08 1.52.64 3.08 1.75 4.16 1.12 1.08 2.58 1.57 4.09 1.79V10c-1.41-.05-2.81-.34-4.09-.87-.55-.23-1.07-.53-1.58-.84-.01 2.92.02 5.84-.02 8.75-.08 1.4-.54 2.78-1.35 3.93-1.31 1.91-3.58 3.17-5.91 3.24-1.43.08-2.87-.31-4.08-1.03-2.02-1.2-3.6-3.18-3.98-5.52-.2-1.19-.13-2.43.23-3.59.35-1.15.95-2.22 1.77-3.09 1.11-1.18 2.58-2.01 4.15-2.38 1.01-.24 2.07-.27 3.1-.11v4.08c-.68-.22-1.42-.28-2.13-.18-.79.11-1.53.48-2.09 1.05-.51.51-.86 1.16-.99 1.87-.12.63-.08 1.29.12 1.9.22.67.63 1.27 1.17 1.72.58.48 1.31.77 2.06.8.67.03 1.34-.14 1.91-.49.57-.35 1.03-.89 1.3-1.51.17-.41.27-.85.29-1.29.03-.74.02-1.48.02-2.22V.02z"></path>
                        </svg>
                    </a>
                    <?php if ($showCart): ?>
                        <button class="cart-toggle" aria-label="Carrito">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span class="cart-badge">0</span>
                        </button>
                    <?php endif; ?>
                </div>
            </nav>

            <div class="nav-overlay" id="navOverlay"></div>

            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>
    <?php
}

function pb_analytics_env_value(string $key): ?string
{
    $value = getenv($key);
    if ($value !== false && trim((string)$value) !== '') {
        return trim((string)$value);
    }

    if (!empty($_ENV[$key])) {
        return trim((string)$_ENV[$key]);
    }

    if (!empty($_SERVER[$key])) {
        return trim((string)$_SERVER[$key]);
    }

    return null;
}

function pb_read_env_key_from_file(string $path, string $targetKey): ?string
{
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return null;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        if ($key !== $targetKey) {
            continue;
        }

        $value = trim($parts[1]);
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        return trim($value);
    }

    return null;
}

function pb_ga4_measurement_id(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $fromEnv = pb_analytics_env_value('GA4_MEASUREMENT_ID');
    if ($fromEnv !== null) {
        return $cached = $fromEnv;
    }

    $projectRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
    $sharedRoot = dirname($projectRoot);
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $isLocalhost = $host !== '' && preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host) === 1;

    $paths = [
        $sharedRoot . DIRECTORY_SEPARATOR . '.env',
        $projectRoot . DIRECTORY_SEPARATOR . '.env',
    ];

    if ($isLocalhost) {
        $paths = array_reverse($paths);
    }

    foreach ($paths as $path) {
        $value = pb_read_env_key_from_file($path, 'GA4_MEASUREMENT_ID');
        if ($value !== null && $value !== '') {
            return $cached = $value;
        }
    }

    return $cached = '';
}

function pb_render_analytics_head(): void
{
    $measurementIdRaw = pb_ga4_measurement_id();
    if ($measurementIdRaw === '') {
        return;
    }

    $measurementId = htmlspecialchars($measurementIdRaw, ENT_QUOTES, 'UTF-8');
    ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $measurementId ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= $measurementId ?>');
    </script>
    <?php
}

function pb_render_footer(): void
{
    ?>
    <footer class="footer" id="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="index.html" class="logo">
                        <img class="logo-icon" src="assets/logo/logo.png" alt="PrintingBruno">
                        Printing<span>Bruno</span>
                    </a>
                    <p>Transformamos tus ideas en objetos reales con impresión 3D de alta calidad. Cada pieza se trabaja con criterio, detalle y atención directa.</p>
                    <div class="footer-social">
                        <a href="https://www.instagram.com/printing.bruno/" target="_blank" rel="noopener" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                        </a>
                        <a href="https://www.tiktok.com/@printing.bruno" target="_blank" rel="noopener" aria-label="TikTok">
                            <svg class="icon-tiktok" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12.53.02c1.31-.02 2.62-.01 3.93-.02.08 1.52.64 3.08 1.75 4.16 1.12 1.08 2.58 1.57 4.09 1.79V10c-1.41-.05-2.81-.34-4.09-.87-.55-.23-1.07-.53-1.58-.84-.01 2.92.02 5.84-.02 8.75-.08 1.4-.54 2.78-1.35 3.93-1.31 1.91-3.58 3.17-5.91 3.24-1.43.08-2.87-.31-4.08-1.03-2.02-1.2-3.6-3.18-3.98-5.52-.2-1.19-.13-2.43.23-3.59.35-1.15.95-2.22 1.77-3.09 1.11-1.18 2.58-2.01 4.15-2.38 1.01-.24 2.07-.27 3.1-.11v4.08c-.68-.22-1.42-.28-2.13-.18-.79.11-1.53.48-2.09 1.05-.51.51-.86 1.16-.99 1.87-.12.63-.08 1.29.12 1.9.22.67.63 1.27 1.17 1.72.58.48 1.31.77 2.06.8.67.03 1.34-.14 1.91-.49.57-.35 1.03-.89 1.3-1.51.17-.41.27-.85.29-1.29.03-.74.02-1.48.02-2.22V.02z"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="footer-heading">Links Rápidos</h4>
                    <ul class="footer-links">
                        <?php foreach (pb_nav_items() as $item): ?>
                            <li>
                                <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-heading">Categorías</h4>
                    <ul class="footer-links">
                        <?php foreach (pb_category_items() as $item): ?>
                            <li>
                                <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-heading">Contacto</h4>
                    <ul class="footer-contact">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            Argentina
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <a href="mailto:printingbruno.22@gmail.com">printingbruno.22@gmail.com</a>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                            Lun - Sáb: 9:00 - 20:00
                        </li>
                    </ul>

                    <div class="footer-payments">
                        <p class="footer-payments-title">Medios de pago</p>
                        <div class="footer-payments-icons">
                            <div class="payment-icon">Mercado Pago</div>
                            <div class="payment-icon">Transferencia</div>
                            <div class="payment-icon">Efectivo</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© <?= date('Y') ?> PrintingBruno. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
    <?php
}

function pb_render_cart_drawer(): void
{
    ?>
    <div id="cartOverlay"></div>
    <div class="cart-drawer" id="cartDrawer">
        <div class="cart-drawer-header">
            <h3>🛒 Tu Carrito</h3>
            <button class="cart-drawer-close" onclick="Cart.toggleDrawer()" aria-label="Cerrar">✕</button>
        </div>
        <div class="cart-drawer-items"></div>
        <div class="cart-drawer-empty"><span>🛒</span>Tu carrito está vacío</div>
        <div class="cart-drawer-footer" style="display:none">
            <div class="cart-drawer-total">
                <span>Total</span>
                <span class="cart-drawer-total-amount">$0</span>
            </div>
            <button class="btn btn-primary btn-lg cart-checkout-btn" style="width:100%">Finalizar Compra</button>
        </div>
    </div>
    <?php
}
