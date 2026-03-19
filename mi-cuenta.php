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

        .account-grid.account-grid-dashboard {
            grid-template-columns: minmax(0, 1fr);
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

        .account-verification-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--space-md);
            flex-wrap: wrap;
            padding: var(--space-lg);
            border-radius: var(--border-radius-lg);
            border: 1px solid rgba(255, 107, 43, 0.18);
            background: linear-gradient(135deg, rgba(255, 107, 43, 0.12), rgba(255, 255, 255, 0.03));
        }

        .account-verification-banner strong {
            display: block;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }

        .account-verification-banner p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            max-width: 680px;
        }

        .orders-overview {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .orders-overview-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-md);
            padding: var(--space-md);
        }

        .orders-overview-card strong {
            display: block;
            color: var(--text-muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .orders-overview-card span {
            display: block;
            margin-top: 8px;
            font-family: var(--font-heading);
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .orders-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--space-md);
            flex-wrap: wrap;
            margin-bottom: var(--space-lg);
        }

        .orders-filters {
            display: inline-flex;
            gap: 0.35rem;
            padding: 0.25rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-full);
            flex-wrap: wrap;
        }

        .orders-filters button {
            border: none;
            background: transparent;
            color: var(--text-secondary);
            padding: 0.55rem 0.9rem;
            border-radius: var(--border-radius-full);
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .orders-filters button.active {
            background: var(--accent);
            color: #fff;
        }

        .orders-filter-note {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .orders-list {
            display: grid;
            gap: var(--space-md);
        }

        .account-empty {
            color: var(--text-muted);
            text-align: center;
            padding: var(--space-lg) 0;
        }

        .order-card {
            display: grid;
            gap: var(--space-md);
            padding: var(--space-lg);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--border-subtle);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
            transition: border-color var(--transition-fast), transform var(--transition-fast), background var(--transition-fast);
            cursor: pointer;
        }

        .order-card.active {
            border-color: rgba(255, 107, 43, 0.26);
            background: rgba(255, 107, 43, 0.06);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.16);
        }

        .order-card:focus-visible {
            outline: 2px solid rgba(255, 107, 43, 0.4);
            outline-offset: 2px;
        }

        .order-card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--space-md);
            flex-wrap: wrap;
        }

        .order-card-order {
            display: grid;
            gap: 4px;
        }

        .order-card-order strong {
            font-family: var(--font-heading);
            font-size: 1.05rem;
            line-height: 1.1;
        }

        .order-card-order span {
            color: var(--text-muted);
            font-size: 0.84rem;
        }

        .order-card-preview {
            display: grid;
            grid-template-columns: 64px 1fr;
            gap: var(--space-md);
            align-items: center;
        }

        .order-card-preview img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: var(--border-radius-md);
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-subtle);
        }

        .order-card-preview strong {
            display: block;
            margin-bottom: 4px;
            font-size: 0.96rem;
        }

        .order-card-preview p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .order-card-meta {
            display: flex;
            gap: var(--space-sm);
            flex-wrap: wrap;
        }

        .order-card-meta .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.38rem 0.7rem;
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-secondary);
            font-size: 0.82rem;
        }

        .order-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--space-md);
            flex-wrap: wrap;
        }

        .order-card-total {
            display: grid;
            gap: 4px;
        }

        .order-card-total strong {
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .order-card-total span {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            line-height: 1;
            color: var(--accent);
        }

        .order-card-expand-hint {
            color: var(--text-muted);
            font-size: 0.84rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .order-card-expand-hint strong {
            color: var(--text-primary);
            font-weight: 700;
        }

        .order-inline-note {
            padding: 0.85rem 1rem;
            border-radius: var(--border-radius-md);
            border: 1px solid rgba(255, 107, 43, 0.14);
            background: rgba(255, 107, 43, 0.08);
            color: var(--text-secondary);
            font-size: 0.86rem;
            line-height: 1.5;
        }

        .order-inline-note strong {
            color: var(--accent-light);
        }

        .order-card-detail {
            margin-top: var(--space-sm);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-md);
            background: var(--bg-secondary);
            padding: var(--space-lg);
            display: grid;
            gap: var(--space-md);
        }

        .order-card-detail-state {
            padding: var(--space-sm) 0;
            color: var(--text-muted);
            font-size: 0.9rem;
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

            .orders-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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

            .orders-overview {
                grid-template-columns: 1fr;
            }

            .order-card-preview {
                grid-template-columns: 1fr;
            }

            .order-card-preview img {
                width: 100%;
                height: 180px;
            }

            .order-card-footer {
                align-items: stretch;
            }

            .order-card-expand-hint {
                width: 100%;
                text-align: left;
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

            <div class="account-grid" id="accountGrid">
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

                        <div class="account-verification-banner" id="accountVerificationNotice" hidden>
                            <div>
                                <strong>Verificá tu email para terminar de activar la cuenta</strong>
                                <p>Mientras tanto podés navegar y ver tu cuenta, pero la verificación te permite recuperar acceso y vincular pedidos hechos como invitado con más seguridad.</p>
                            </div>
                            <button class="btn btn-primary btn-sm" id="resendVerificationBtn" type="button">Reenviar email</button>
                        </div>

                        <div class="dashboard-tabs" id="dashboardTabs">
                            <button type="button" class="active" data-dashboard-tab="orders">Mis pedidos</button>
                            <button type="button" data-dashboard-tab="profile">Mis datos</button>
                            <button type="button" data-dashboard-tab="addresses">Mis direcciones</button>
                        </div>

                        <div class="dashboard-panel" data-dashboard-panel="orders">
                            <div class="orders-overview" id="ordersOverview">
                                <div class="orders-overview-card"><strong>Total pedidos</strong><span id="ordersOverviewTotal">—</span></div>
                                <div class="orders-overview-card"><strong>Pendientes de pago</strong><span id="ordersOverviewPending">—</span></div>
                                <div class="orders-overview-card"><strong>En producción</strong><span id="ordersOverviewProduction">—</span></div>
                                <div class="orders-overview-card"><strong>Enviados</strong><span id="ordersOverviewShipped">—</span></div>
                            </div>
                            <div class="orders-toolbar">
                                <div class="orders-filters" id="ordersFilters">
                                    <button type="button" class="active" data-order-filter="all">Todos</button>
                                    <button type="button" data-order-filter="pending">Pendientes</button>
                                    <button type="button" data-order-filter="production">En producción</button>
                                    <button type="button" data-order-filter="shipped">Enviados</button>
                                    <button type="button" data-order-filter="cancelled">Cancelados</button>
                                </div>
                                <div class="orders-filter-note" id="ordersFilterNote">Cargando pedidos...</div>
                            </div>
                            <div class="orders-list" id="ordersList">
                                <div class="account-empty">Cargando pedidos...</div>
                            </div>
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
    <script src="js/account.js?v=20260319-3"></script>
</body>

</html>
