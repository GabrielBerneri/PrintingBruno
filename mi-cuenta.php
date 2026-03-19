<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Mi cuenta | PrintingBruno</title>
    <link rel="stylesheet" href="css/styles.css?v=20260319-1">
    <link rel="icon" type="image/png" href="assets/logo/logo.png">
    <?php
    require_once __DIR__ . '/partials/site-chrome.php';
    pb_render_analytics_head();
    ?>
    <style>
        .account-shell {
            padding-top: calc(var(--header-height) + var(--space-3xl));
            padding-bottom: var(--space-3xl);
        }

        .account-hero {
            display: grid;
            gap: var(--space-md);
            margin-bottom: var(--space-2xl);
        }

        .account-hero h1 {
            font-family: var(--font-heading);
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.05;
            margin: 0;
        }

        .account-hero p {
            max-width: 760px;
            color: var(--text-secondary);
            margin: 0;
        }

        .account-banner {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            width: fit-content;
            padding: 0.35rem 0.75rem;
            border-radius: var(--border-radius-full);
            background: rgba(255, 107, 43, 0.1);
            color: var(--accent-light);
            border: 1px solid rgba(255, 107, 43, 0.16);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .account-grid {
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            gap: var(--space-xl);
        }

        .account-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-lg);
            padding: var(--space-xl);
            box-shadow: var(--shadow-soft);
        }

        .account-flow-status {
            display: none;
            margin-bottom: var(--space-lg);
            padding: var(--space-md);
            border-radius: var(--border-radius-md);
            border: 1px solid var(--border-subtle);
            background: var(--bg-secondary);
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .account-flow-status[data-type="success"] {
            border-color: rgba(37, 211, 102, 0.25);
            color: #25D366;
        }

        .account-flow-status[data-type="error"] {
            border-color: rgba(231, 76, 60, 0.25);
            color: #e74c3c;
        }

        .account-tabs {
            display: inline-flex;
            gap: 0.35rem;
            padding: 0.25rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-full);
            margin-bottom: var(--space-lg);
            flex-wrap: wrap;
        }

        .account-tabs button {
            border: none;
            background: transparent;
            color: var(--text-secondary);
            padding: 0.6rem 0.9rem;
            border-radius: var(--border-radius-full);
            cursor: pointer;
            font-weight: 600;
        }

        .account-tabs button.active {
            background: var(--accent);
            color: #fff;
        }

        .account-panel {
            display: none;
        }

        .account-panel.active {
            display: block;
        }

        .account-form {
            display: grid;
            gap: var(--space-md);
        }

        .account-form .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-md);
        }

        .account-form .form-row.three-cols {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .account-note {
            margin-top: var(--space-md);
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .account-message {
            margin-top: var(--space-md);
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .dashboard-shell {
            display: grid;
            gap: var(--space-lg);
        }

        .dashboard-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--space-md);
            flex-wrap: wrap;
        }

        .dashboard-tabs {
            display: inline-flex;
            gap: 0.35rem;
            padding: 0.25rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-full);
            flex-wrap: wrap;
        }

        .dashboard-tabs button {
            border: none;
            background: transparent;
            color: var(--text-secondary);
            padding: 0.55rem 0.9rem;
            border-radius: var(--border-radius-full);
            cursor: pointer;
            font-weight: 600;
        }

        .dashboard-tabs button.active {
            background: var(--accent);
            color: #fff;
        }

        .dashboard-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-lg);
            padding: var(--space-xl);
        }

        .account-table {
            width: 100%;
            border-collapse: collapse;
        }

        .account-table th,
        .account-table td {
            padding: var(--space-sm) 0;
            border-bottom: 1px solid var(--border-subtle);
            vertical-align: top;
            text-align: left;
        }

        .account-empty {
            color: var(--text-muted);
            text-align: center;
            padding: var(--space-lg) 0;
        }

        .order-detail-card {
            margin-top: var(--space-lg);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-md);
            background: var(--bg-secondary);
            padding: var(--space-lg);
            display: grid;
            gap: var(--space-md);
        }

        .order-detail-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--space-md);
            flex-wrap: wrap;
        }

        .order-detail-head h3 {
            margin: 0 0 4px;
            font-size: 1.05rem;
        }

        .order-status-stack {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .order-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            border: 1px solid transparent;
        }

        .order-badge.payment-approved,
        .order-badge.fulfillment-delivered {
            background: rgba(37, 211, 102, 0.12);
            border-color: rgba(37, 211, 102, 0.2);
            color: #25D366;
        }

        .order-badge.payment-pending,
        .order-badge.payment-under_review,
        .order-badge.fulfillment-queued,
        .order-badge.fulfillment-in_production,
        .order-badge.fulfillment-ready {
            background: rgba(255, 107, 43, 0.12);
            border-color: rgba(255, 107, 43, 0.18);
            color: var(--accent-light);
        }

        .order-badge.payment-rejected,
        .order-badge.payment-cancelled,
        .order-badge.payment-refunded,
        .order-badge.payment-charged_back,
        .order-badge.fulfillment-cancelled {
            background: rgba(231, 76, 60, 0.12);
            border-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .order-badge.fulfillment-shipped {
            background: rgba(107, 190, 240, 0.12);
            border-color: rgba(107, 190, 240, 0.18);
            color: #6bbef0;
        }

        .order-detail-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: var(--space-md);
        }

        .order-detail-meta strong {
            display: block;
            margin-bottom: 4px;
            font-size: 0.82rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .order-detail-meta span {
            color: var(--text-primary);
        }

        .order-detail-items {
            display: grid;
            gap: var(--space-sm);
        }

        .order-detail-item {
            display: grid;
            grid-template-columns: 56px 1fr auto;
            gap: var(--space-md);
            align-items: center;
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-md);
            padding: var(--space-sm) var(--space-md);
            background: rgba(255, 255, 255, 0.02);
        }

        .order-detail-item img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: var(--border-radius-sm);
            background: rgba(255, 255, 255, 0.04);
        }

        .order-detail-item h4 {
            margin: 0 0 4px;
            font-size: 0.95rem;
        }

        .order-detail-item p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.83rem;
        }

        .order-detail-placeholder {
            margin-top: var(--space-lg);
            padding: var(--space-lg);
            border: 1px dashed var(--border-subtle);
            border-radius: var(--border-radius-md);
            color: var(--text-muted);
            text-align: center;
        }

        .address-list {
            display: grid;
            gap: var(--space-md);
        }

        .address-item {
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-md);
            padding: var(--space-md);
            background: var(--bg-secondary);
        }

        .address-item h4 {
            margin: 0 0 4px;
            font-size: 0.98rem;
        }

        .address-item p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.86rem;
            line-height: 1.5;
        }

        .address-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: var(--space-sm);
        }

        @media (max-width: 960px) {
            .account-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .account-form .form-row {
                grid-template-columns: 1fr;
            }

            .account-form .form-row.three-cols {
                grid-template-columns: 1fr;
            }

            .dashboard-head {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <?php pb_render_header('cuenta', ['show_cart' => true]); ?>

    <main class="account-shell">
        <div class="container">
            <div class="account-hero">
                <span class="account-banner">Cuenta cliente</span>
                <h1>Tu cuenta, pedidos y datos en un solo lugar.</h1>
                <p>Iniciá sesión para ver tus pedidos, actualizar tus datos, administrar direcciones y volver a comprar sin repetir información.</p>
            </div>

            <div class="account-grid">
                <section class="account-card" id="authCard">
                    <div class="account-flow-status" id="accountFlowStatus" hidden></div>
                    <div class="account-tabs" id="authTabs">
                        <button type="button" class="active" data-auth-tab="login">Ingresar</button>
                        <button type="button" data-auth-tab="register">Crear cuenta</button>
                        <button type="button" data-auth-tab="reset">Recuperar acceso</button>
                    </div>

                    <div class="account-panel active" data-auth-panel="login">
                        <form class="account-form" id="loginForm">
                            <div class="form-group">
                                <label class="form-label" for="loginEmail">Email</label>
                                <input class="form-input" id="loginEmail" type="email" autocomplete="email" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="loginPassword">Contraseña</label>
                                <input class="form-input" id="loginPassword" type="password" autocomplete="current-password" required>
                            </div>
                            <button class="btn btn-primary" type="submit">Ingresar</button>
                            <div class="account-message" id="loginMessage"></div>
                        </form>
                    </div>

                    <div class="account-panel" data-auth-panel="register">
                        <form class="account-form" id="registerForm">
                            <div class="form-row three-cols">
                                <div class="form-group">
                                    <label class="form-label" for="registerFirstName">Nombre</label>
                                    <input class="form-input" id="registerFirstName" type="text" autocomplete="given-name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="registerLastName">Apellido</label>
                                    <input class="form-input" id="registerLastName" type="text" autocomplete="family-name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="registerDni">DNI</label>
                                    <input class="form-input" id="registerDni" type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="registerFullName">Nombre completo</label>
                                <input class="form-input" id="registerFullName" type="text" autocomplete="name" placeholder="Se puede completar solo" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="registerPhone">Teléfono</label>
                                <input class="form-input" id="registerPhone" type="tel" autocomplete="tel">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="registerEmail">Email</label>
                                <input class="form-input" id="registerEmail" type="email" autocomplete="email" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="registerPassword">Contraseña</label>
                                <input class="form-input" id="registerPassword" type="password" autocomplete="new-password" required>
                            </div>
                            <button class="btn btn-primary" type="submit">Crear cuenta</button>
                            <div class="account-message" id="registerMessage"></div>
                        </form>
                    </div>

                    <div class="account-panel" data-auth-panel="reset">
                        <form class="account-form" id="resetForm">
                            <div class="form-group">
                                <label class="form-label" for="resetEmail">Email de la cuenta</label>
                                <input class="form-input" id="resetEmail" type="email" autocomplete="email" required>
                            </div>
                            <button class="btn btn-secondary" type="submit">Solicitar recuperación</button>
                            <div class="account-message" id="resetMessage"></div>
                        </form>
                    </div>

                    <div class="account-panel" data-auth-panel="reset-password">
                        <form class="account-form" id="resetPasswordForm">
                            <input type="hidden" id="resetPasswordToken">
                            <div class="form-group">
                                <label class="form-label" for="resetNewPassword">Nueva contraseña</label>
                                <input class="form-input" id="resetNewPassword" type="password" autocomplete="new-password" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="resetConfirmPassword">Confirmar contraseña</label>
                                <input class="form-input" id="resetConfirmPassword" type="password" autocomplete="new-password" required>
                            </div>
                            <button class="btn btn-primary" type="submit">Cambiar contraseña</button>
                            <div class="account-message" id="resetPasswordMessage"></div>
                        </form>
                    </div>
                </section>

                <section class="account-card" id="dashboardCard" hidden>
                    <div class="dashboard-shell">
                        <div class="dashboard-head">
                            <div>
                                <span class="account-banner" style="margin-bottom:var(--space-sm)">Sesión activa</span>
                                <h2 style="font-family:var(--font-heading);margin:0" id="customerGreeting">Hola</h2>
                                <p id="sessionMeta" style="margin:6px 0 0;color:var(--text-muted);font-size:0.86rem" hidden></p>
                            </div>
                            <button class="btn btn-secondary btn-sm" id="logoutBtn" type="button">Cerrar sesión</button>
                        </div>

                        <div class="dashboard-tabs" id="dashboardTabs">
                            <button type="button" class="active" data-dashboard-tab="orders">Mis pedidos</button>
                            <button type="button" data-dashboard-tab="profile">Mis datos</button>
                            <button type="button" data-dashboard-tab="addresses">Mis direcciones</button>
                        </div>

                        <div class="dashboard-panel" data-dashboard-panel="orders">
                            <table class="account-table" id="ordersTable">
                                <tr><td class="account-empty">Cargando pedidos...</td></tr>
                            </table>
                            <div class="order-detail-placeholder" id="orderDetailPlaceholder">Seleccioná un pedido para ver el detalle.</div>
                            <div class="order-detail-card" id="orderDetailCard" hidden></div>
                        </div>

                        <div class="dashboard-panel" data-dashboard-panel="profile" hidden>
                            <form class="account-form" id="profileForm">
                                <div class="form-row three-cols">
                                    <div class="form-group">
                                        <label class="form-label" for="profileFirstName">Nombre</label>
                                        <input class="form-input" id="profileFirstName" type="text" autocomplete="given-name" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="profileLastName">Apellido</label>
                                        <input class="form-input" id="profileLastName" type="text" autocomplete="family-name" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="profileDni">DNI</label>
                                        <input class="form-input" id="profileDni" type="text" autocomplete="off">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="profileFullName">Nombre completo</label>
                                    <input class="form-input" id="profileFullName" type="text" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="profileEmail">Email</label>
                                    <input class="form-input" id="profileEmail" type="email" autocomplete="email" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="profilePhone">Teléfono</label>
                                    <input class="form-input" id="profilePhone" type="tel" autocomplete="tel">
                                </div>
                                <button class="btn btn-primary" type="submit">Guardar cambios</button>
                                <div class="account-message" id="profileMessage"></div>
                            </form>
                        </div>

                        <div class="dashboard-panel" data-dashboard-panel="addresses" hidden>
                            <div class="account-grid" style="grid-template-columns:0.9fr 1.1fr">
                                <div>
                                    <div class="address-list" id="addressesList">
                                        <div class="account-empty">Cargando direcciones...</div>
                                    </div>
                                </div>
                                <div>
                                    <form class="account-form" id="addressForm">
                                        <input type="hidden" id="addressId">
                                        <div class="form-group">
                                            <label class="form-label" for="addressLabel">Etiqueta</label>
                                            <input class="form-input" id="addressLabel" type="text" placeholder="Casa, trabajo, etc." required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="addressRecipient">Destinatario</label>
                                            <input class="form-input" id="addressRecipient" type="text" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="addressStreet">Calle y altura</label>
                                            <input class="form-input" id="addressStreet" type="text" required>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label" for="addressCity">Ciudad</label>
                                                <input class="form-input" id="addressCity" type="text" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="addressProvince">Provincia</label>
                                                <input class="form-input" id="addressProvince" type="text" required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label" for="addressPostalCode">Código postal</label>
                                                <input class="form-input" id="addressPostalCode" type="text">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="addressPhone">Teléfono</label>
                                                <input class="form-input" id="addressPhone" type="tel">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="addressNotes">Notas</label>
                                            <textarea class="form-textarea" id="addressNotes" rows="3"></textarea>
                                        </div>
                                        <label style="display:flex;align-items:center;gap:var(--space-sm);cursor:pointer">
                                            <input type="checkbox" id="addressDefault">
                                            Dirección principal
                                        </label>
                                        <div style="display:flex;gap:var(--space-sm);flex-wrap:wrap">
                                            <button class="btn btn-primary" type="submit">Guardar dirección</button>
                                            <button class="btn btn-secondary" type="button" id="addressResetBtn">Limpiar</button>
                                        </div>
                                        <div class="account-message" id="addressMessage"></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php pb_render_footer(); ?>

    <script src="js/cart.js?v=20260316-2"></script>
    <script src="js/main.js?v=20260315-4"></script>
    <script src="js/account.js?v=20260319-2"></script>
</body>

</html>
