<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Recuperar acceso | PrintingBruno</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" type="image/png" href="assets/logo/logo.png">
    <style>
        .admin-reset-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: var(--space-2xl);
            background:
                radial-gradient(circle at top, rgba(255, 107, 43, 0.12), transparent 40%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.02), transparent 35%),
                var(--bg-primary);
        }

        .admin-reset-card {
            width: min(100%, 460px);
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-lg);
            padding: var(--space-2xl);
            box-shadow: var(--shadow-soft);
        }

        .admin-reset-card h1 {
            font-family: var(--font-heading);
            font-size: 1.8rem;
            margin-bottom: var(--space-sm);
        }

        .admin-reset-card p {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: var(--space-lg);
        }

        .admin-reset-card .form-input {
            width: 100%;
        }

        .admin-reset-meta {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: var(--space-sm);
        }

        .admin-reset-error,
        .admin-reset-success {
            font-size: 0.9rem;
            margin-top: var(--space-md);
            display: none;
        }

        .admin-reset-error { color: #ff7b7b; }
        .admin-reset-success { color: #72e39a; }

        .admin-reset-back {
            margin-top: var(--space-lg);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main class="admin-reset-shell">
        <section class="admin-reset-card">
            <div class="logo" style="justify-content:center;margin-bottom:var(--space-xl)">
                <img class="logo-icon" src="assets/logo/logo.png" alt="PrintingBruno">Printing<span>Bruno</span>
            </div>

            <div id="requestResetView">
                <h1>Recuperar acceso</h1>
                <p>Ingresá tu usuario o email del panel. Si existe, te enviamos un enlace de recuperación al correo configurado.</p>
                <form id="requestResetForm">
                    <div class="form-group">
                        <input type="text" class="form-input" id="resetIdentity" placeholder="Usuario o email" autocomplete="username email" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%" id="requestResetBtn">Enviar enlace</button>
                    <p class="admin-reset-meta">Si el usuario no tiene email propio, el enlace se envía al correo principal configurado del sitio.</p>
                    <p class="admin-reset-error" id="requestResetError"></p>
                    <p class="admin-reset-success" id="requestResetSuccess"></p>
                </form>
            </div>

            <div id="confirmResetView" style="display:none">
                <h1>Restablecer contraseña</h1>
                <p id="confirmResetLead">Validando enlace...</p>
                <form id="confirmResetForm" style="display:none">
                    <div class="form-group">
                        <input type="password" class="form-input" id="newAdminPassword" placeholder="Nueva contraseña" autocomplete="new-password" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-input" id="newAdminPasswordConfirm" placeholder="Repetir contraseña" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%" id="confirmResetBtn">Guardar contraseña</button>
                    <p class="admin-reset-meta">Usá al menos 12 caracteres, con letras y números.</p>
                    <p class="admin-reset-error" id="confirmResetError"></p>
                    <p class="admin-reset-success" id="confirmResetSuccess"></p>
                </form>
            </div>

            <a href="admin.html" class="admin-reset-back">← Volver al login del panel</a>
        </section>
    </main>

    <script>
        const API = 'api/admin/password_reset.php';
        const token = new URLSearchParams(window.location.search).get('token') || '';

        const requestView = document.getElementById('requestResetView');
        const confirmView = document.getElementById('confirmResetView');
        const requestError = document.getElementById('requestResetError');
        const requestSuccess = document.getElementById('requestResetSuccess');
        const confirmError = document.getElementById('confirmResetError');
        const confirmSuccess = document.getElementById('confirmResetSuccess');
        const confirmLead = document.getElementById('confirmResetLead');
        const confirmForm = document.getElementById('confirmResetForm');

        function showMessage(el, text, ok = false) {
            if (!el) return;
            el.textContent = text;
            el.style.display = text ? 'block' : 'none';
            if (ok) return;
        }

        async function validateToken() {
            requestView.style.display = 'none';
            confirmView.style.display = '';
            confirmForm.style.display = 'none';
            showMessage(confirmError, '');
            showMessage(confirmSuccess, '');

            try {
                const res = await fetch(`${API}?token=${encodeURIComponent(token)}`);
                const data = await res.json();
                if (!res.ok || !data.valid) {
                    throw new Error(data.error || 'El enlace es inválido o ya venció.');
                }

                confirmLead.textContent = `Vas a cambiar la contraseña del usuario ${data.username}.`;
                confirmForm.style.display = '';
            } catch (error) {
                confirmLead.textContent = error.message || 'No se pudo validar el enlace.';
                showMessage(confirmError, error.message || 'No se pudo validar el enlace.');
            }
        }

        document.getElementById('requestResetForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = document.getElementById('requestResetBtn');
            button.disabled = true;
            button.textContent = 'Enviando...';
            showMessage(requestError, '');
            showMessage(requestSuccess, '');

            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'request',
                        identity: document.getElementById('resetIdentity').value.trim()
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.error || 'No se pudo enviar el enlace.');
                }
                showMessage(requestSuccess, data.message || 'Revisá el correo configurado.', true);
            } catch (error) {
                showMessage(requestError, error.message || 'No se pudo enviar el enlace.');
            } finally {
                button.disabled = false;
                button.textContent = 'Enviar enlace';
            }
        });

        document.getElementById('confirmResetForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = document.getElementById('confirmResetBtn');
            button.disabled = true;
            button.textContent = 'Guardando...';
            showMessage(confirmError, '');
            showMessage(confirmSuccess, '');

            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'reset',
                        token,
                        password: document.getElementById('newAdminPassword').value,
                        password_confirm: document.getElementById('newAdminPasswordConfirm').value
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.error || 'No se pudo actualizar la contraseña.');
                }

                showMessage(confirmSuccess, data.message || 'Contraseña actualizada.', true);
                confirmLead.textContent = 'Contraseña actualizada. Ya podés volver al panel.';
                confirmForm.style.display = 'none';
            } catch (error) {
                showMessage(confirmError, error.message || 'No se pudo actualizar la contraseña.');
            } finally {
                button.disabled = false;
                button.textContent = 'Guardar contraseña';
            }
        });

        if (token) {
            validateToken();
        }
    </script>
</body>
</html>
