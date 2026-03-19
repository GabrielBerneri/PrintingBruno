/* ============================================
   PrintingBruno - Customer account UI
   ============================================ */

const Account = {
  apiBase: 'api',
  state: {
    authenticated: false,
    customer: null,
    csrfToken: '',
    expiresAt: '',
    verifyToken: '',
    resetToken: '',
  },

  init() {
    this.cacheElements();
    this.bindAuthTabs();
    this.bindDashboardTabs();
    this.bindForms();
    this.bindQueryActions();
    this.showAuthTab('login');
    this.showDashboardTab('orders');
    this.syncModeFromQuery();
    this.loadSession();
  },

  cacheElements() {
    this.authCard = document.getElementById('authCard');
    this.dashboardCard = document.getElementById('dashboardCard');
    this.customerGreeting = document.getElementById('customerGreeting');
    this.sessionMeta = document.getElementById('sessionMeta');
    this.accountFlowStatus = document.getElementById('accountFlowStatus');
    this.loginMessage = document.getElementById('loginMessage');
    this.registerMessage = document.getElementById('registerMessage');
    this.resetMessage = document.getElementById('resetMessage');
    this.resetPasswordMessage = document.getElementById('resetPasswordMessage');
    this.profileMessage = document.getElementById('profileMessage');
    this.addressMessage = document.getElementById('addressMessage');
    this.ordersTable = document.getElementById('ordersTable');
    this.orderDetailCard = document.getElementById('orderDetailCard');
    this.orderDetailPlaceholder = document.getElementById('orderDetailPlaceholder');
    this.addressesList = document.getElementById('addressesList');
  },

  escapeHTML(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  },

  isJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';
    return contentType.includes('application/json');
  },

  updateCsrfToken(token) {
    if (token) {
      this.state.csrfToken = String(token);
    }
  },

  async request(path, options = {}) {
    const method = String(options.method || 'GET').toUpperCase();
    const headers = { ...(options.headers || {}) };

    if (options.body && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    const shouldSendCsrf = options.csrf !== false && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);
    if (shouldSendCsrf && this.state.csrfToken) {
      headers['X-CSRF-Token'] = this.state.csrfToken;
    }

    const response = await fetch(`${this.apiBase}/${path}`, {
      credentials: 'same-origin',
      ...options,
      method,
      headers,
    });

    const payload = this.isJsonResponse(response)
      ? await response.json()
      : { success: response.ok, raw: await response.text() };

    if (payload && typeof payload.csrf_token === 'string' && payload.csrf_token) {
      this.updateCsrfToken(payload.csrf_token);
    }

    if (!response.ok || payload.success === false) {
      throw new Error(payload.error || payload.message || payload.raw || 'Error inesperado');
    }

    return payload;
  },

  setMessage(node, text, type = 'info') {
    if (!node) return;
    node.textContent = text || '';
    node.style.color = type === 'error' ? '#e74c3c' : type === 'success' ? '#25D366' : 'var(--text-muted)';
  },

  formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleDateString('es-AR');
  },

  formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString('es-AR');
  },

  statusLabel(type, value) {
    const labels = {
      payment: {
        pending: 'Pago pendiente',
        under_review: 'Pago en revisión',
        approved: 'Pago aprobado',
        rejected: 'Pago rechazado',
        cancelled: 'Pago cancelado',
        refunded: 'Pago reembolsado',
        charged_back: 'Contracargo',
      },
      fulfillment: {
        queued: 'Pendiente de producción',
        in_production: 'En producción',
        ready: 'Listo para despachar',
        shipped: 'Enviado',
        delivered: 'Entregado',
        cancelled: 'Cancelado',
      },
    };

    return labels[type]?.[value] || String(value || 'Pendiente').replace(/_/g, ' ');
  },

  renderStatusBadge(type, value) {
    const normalized = String(value || 'pending').trim().toLowerCase().replace(/[^a-z0-9_]+/g, '_');
    return `<span class="order-badge ${type}-${normalized}">${this.escapeHTML(this.statusLabel(type, normalized))}</span>`;
  },

  showAuthTab(tab) {
    document.querySelectorAll('[data-auth-tab]').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.authTab === tab);
    });
    document.querySelectorAll('[data-auth-panel]').forEach(panel => {
      panel.classList.toggle('active', panel.dataset.authPanel === tab);
    });
  },

  showDashboardTab(tab) {
    document.querySelectorAll('[data-dashboard-tab]').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.dashboardTab === tab);
    });
    document.querySelectorAll('[data-dashboard-panel]').forEach(panel => {
      panel.hidden = panel.dataset.dashboardPanel !== tab;
    });
  },

  showFlowStatus(message, type = 'info') {
    if (!this.accountFlowStatus) return;
    if (!message) {
      this.accountFlowStatus.hidden = true;
      this.accountFlowStatus.style.display = 'none';
      this.accountFlowStatus.textContent = '';
      return;
    }

    this.accountFlowStatus.hidden = false;
    this.accountFlowStatus.style.display = 'block';
    this.accountFlowStatus.textContent = message;
    this.accountFlowStatus.dataset.type = type;
  },

  syncModeFromQuery() {
    const params = new URLSearchParams(window.location.search);
    this.state.verifyToken = params.get('verify') || '';
    this.state.resetToken = params.get('reset') || '';

    if (this.state.resetToken) {
      const tokenInput = document.getElementById('resetPasswordToken');
      if (tokenInput) tokenInput.value = this.state.resetToken;
      this.showAuthTab('reset-password');
      this.showFlowStatus('Ingresá la nueva contraseña para completar la recuperación.', 'info');
    }
  },

  bindAuthTabs() {
    document.querySelectorAll('[data-auth-tab]').forEach(btn => {
      btn.addEventListener('click', () => this.showAuthTab(btn.dataset.authTab));
    });
  },

  bindDashboardTabs() {
    document.querySelectorAll('[data-dashboard-tab]').forEach(btn => {
      btn.addEventListener('click', () => this.showDashboardTab(btn.dataset.dashboardTab));
    });
  },

  bindForms() {
    document.getElementById('loginForm').addEventListener('submit', (e) => this.handleLogin(e));
    document.getElementById('registerForm').addEventListener('submit', (e) => this.handleRegister(e));
    document.getElementById('resetForm').addEventListener('submit', (e) => this.handleResetRequest(e));
    document.getElementById('resetPasswordForm').addEventListener('submit', (e) => this.handleResetPassword(e));
    document.getElementById('profileForm').addEventListener('submit', (e) => this.handleProfileSave(e));
    document.getElementById('addressForm').addEventListener('submit', (e) => this.handleAddressSave(e));
    document.getElementById('addressResetBtn').addEventListener('click', () => this.resetAddressForm());
    document.getElementById('logoutBtn').addEventListener('click', () => this.handleLogout());
    ['registerFirstName', 'registerLastName', 'profileFirstName', 'profileLastName'].forEach((id) => {
      const input = document.getElementById(id);
      if (input) {
        input.addEventListener('input', () => this.syncDerivedNameFields());
      }
    });
  },

  bindQueryActions() {
    const verifyToken = this.state.verifyToken;
    if (verifyToken) {
      this.verifyEmail(verifyToken);
    }
  },

  async loadSession() {
    try {
      const data = await this.request('auth/session.php', { method: 'GET', csrf: false });
      this.updateCsrfToken(data.csrf_token);
      this.state.expiresAt = data.expires_at || '';

      if (data.authenticated) {
        this.state.authenticated = true;
        this.state.customer = data.customer || null;
        this.showDashboardView();
        await this.loadCustomerData();
        return;
      }
    } catch (error) {
      console.error('session error', error);
    }

    this.showGuestView();
    if (this.state.resetToken) {
      this.showAuthTab('reset-password');
    }
  },

  showGuestView() {
    this.state.authenticated = false;
    this.state.customer = null;
    this.dashboardCard.hidden = true;
    this.authCard.hidden = false;
    if (this.sessionMeta) {
      this.sessionMeta.hidden = true;
    }
  },

  showDashboardView() {
    this.dashboardCard.hidden = false;
    this.authCard.hidden = true;
    const customer = this.state.customer || {};
    const name = customer.full_name || [customer.first_name, customer.last_name].filter(Boolean).join(' ').trim() || customer.email || 'cliente';
    this.customerGreeting.textContent = `Hola, ${name}`;

    if (this.sessionMeta) {
      this.sessionMeta.hidden = false;
      this.sessionMeta.textContent = this.state.expiresAt
        ? `Sesión activa hasta ${this.formatDateTime(this.state.expiresAt)}`
        : 'Sesión activa';
    }
  },

  syncDerivedNameFields() {
    const registerFirst = document.getElementById('registerFirstName');
    const registerLast = document.getElementById('registerLastName');
    const registerFull = document.getElementById('registerFullName');
    if (registerFirst && registerLast && registerFull) {
      registerFull.value = [registerFirst.value.trim(), registerLast.value.trim()].filter(Boolean).join(' ');
    }

    const profileFirst = document.getElementById('profileFirstName');
    const profileLast = document.getElementById('profileLastName');
    const profileFull = document.getElementById('profileFullName');
    if (profileFirst && profileLast && profileFull) {
      profileFull.value = [profileFirst.value.trim(), profileLast.value.trim()].filter(Boolean).join(' ');
    }
  },

  async loadCustomerData() {
    await Promise.all([
      this.loadProfile(),
      this.loadOrders(),
      this.loadAddresses(),
    ]);
  },

  async handleLogin(event) {
    event.preventDefault();
    this.setMessage(this.loginMessage, 'Ingresando...');
    try {
      const data = await this.request('auth/login.php', {
        method: 'POST',
        csrf: false,
        body: JSON.stringify({
          email: document.getElementById('loginEmail').value.trim(),
          password: document.getElementById('loginPassword').value,
        }),
      });

      if (data.customer) {
        this.state.customer = data.customer;
      }
      this.updateCsrfToken(data.csrf_token);
      this.setMessage(
        this.loginMessage,
        data.verification_required ? 'Ingresaste, pero la cuenta requiere verificación de email.' : 'Sesión iniciada.',
        data.verification_required ? 'info' : 'success'
      );
      await this.loadSession();
    } catch (error) {
      this.setMessage(this.loginMessage, error.message || 'No se pudo iniciar sesión', 'error');
    }
  },

  async handleRegister(event) {
    event.preventDefault();
    this.setMessage(this.registerMessage, 'Creando cuenta...');
    try {
      const data = await this.request('auth/register.php', {
        method: 'POST',
        csrf: false,
        body: JSON.stringify({
          first_name: document.getElementById('registerFirstName').value.trim(),
          last_name: document.getElementById('registerLastName').value.trim(),
          full_name: document.getElementById('registerFullName').value.trim(),
          dni: document.getElementById('registerDni').value.trim(),
          phone: document.getElementById('registerPhone').value.trim(),
          email: document.getElementById('registerEmail').value.trim(),
          password: document.getElementById('registerPassword').value,
        }),
      });

      if (data.customer) {
        this.state.customer = data.customer;
      }
      this.updateCsrfToken(data.csrf_token);
      this.setMessage(
        this.registerMessage,
        data.verification_required ? 'Cuenta creada. Revisá tu email para verificarla.' : 'Cuenta creada. Ahora podés ingresar.',
        'success'
      );
      this.showAuthTab('login');
      document.getElementById('registerForm').reset();
    } catch (error) {
      this.setMessage(this.registerMessage, error.message || 'No se pudo registrar la cuenta', 'error');
    }
  },

  async handleResetRequest(event) {
    event.preventDefault();
    this.setMessage(this.resetMessage, 'Procesando...');
    try {
      await this.request('auth/password_reset.php', {
        method: 'POST',
        csrf: false,
        body: JSON.stringify({
          action: 'request',
          email: document.getElementById('resetEmail').value.trim(),
        }),
      });
      this.setMessage(this.resetMessage, 'Si el email existe, se envió la recuperación.', 'success');
    } catch (error) {
      this.setMessage(this.resetMessage, error.message || 'No se pudo solicitar la recuperación', 'error');
    }
  },

  async handleResetPassword(event) {
    event.preventDefault();
    const token = document.getElementById('resetPasswordToken').value.trim() || this.state.resetToken;
    if (!token) {
      this.setMessage(this.resetPasswordMessage, 'Falta el token de recuperación.', 'error');
      return;
    }

    this.setMessage(this.resetPasswordMessage, 'Actualizando contraseña...');
    try {
      await this.request('auth/password_reset.php', {
        method: 'POST',
        csrf: false,
        body: JSON.stringify({
          action: 'reset',
          token,
          password: document.getElementById('resetNewPassword').value,
          password_confirm: document.getElementById('resetConfirmPassword').value,
        }),
      });

      this.setMessage(this.resetPasswordMessage, 'Contraseña actualizada. Ya podés iniciar sesión.', 'success');
      this.showAuthTab('login');
      document.getElementById('resetPasswordForm').reset();
      this.state.resetToken = '';
    } catch (error) {
      this.setMessage(this.resetPasswordMessage, error.message || 'No se pudo actualizar la contraseña', 'error');
    }
  },

  async verifyEmail(token) {
    if (!token) return;
    this.showFlowStatus('Verificando email...', 'info');
    try {
      const data = await this.request(`auth/verify_email.php?token=${encodeURIComponent(token)}`, {
        method: 'GET',
        csrf: false,
      });
      const message = data.message || 'Email verificado correctamente.';
      this.showFlowStatus(message, 'success');
    } catch (error) {
      this.showFlowStatus(error.message || 'No se pudo verificar el email.', 'error');
    }
  },

  async handleLogout() {
    try {
      await this.request('auth/login.php', { method: 'DELETE' });
    } catch (error) {
      console.error('logout error', error);
    }
    this.state.csrfToken = '';
    this.showGuestView();
    this.showAuthTab('login');
  },

  splitNameParts(customer = {}) {
    const first = String(customer.first_name || '').trim();
    const last = String(customer.last_name || '').trim();
    if (first || last) {
      return { first_name: first, last_name: last };
    }

    const fullName = String(customer.full_name || customer.name || '').trim();
    if (!fullName) return { first_name: '', last_name: '' };

    const parts = fullName.split(/\s+/).filter(Boolean);
    if (parts.length === 1) {
      return { first_name: parts[0], last_name: '' };
    }
    return {
      first_name: parts.slice(0, -1).join(' '),
      last_name: parts.slice(-1).join(' '),
    };
  },

  buildFullName() {
    const first = document.getElementById('profileFirstName').value.trim();
    const last = document.getElementById('profileLastName').value.trim();
    const full = [first, last].filter(Boolean).join(' ').trim();
    return full || document.getElementById('profileFullName').value.trim();
  },

  async loadProfile() {
    try {
      const data = await this.request('customer/profile.php', { method: 'GET' });
      const profile = data.customer || data.profile || data;
      if (profile) {
        this.state.customer = { ...(this.state.customer || {}), ...profile };
      }

      const source = this.state.customer || {};
      const names = this.splitNameParts(source);
      document.getElementById('profileFirstName').value = source.first_name || names.first_name || '';
      document.getElementById('profileLastName').value = source.last_name || names.last_name || '';
      document.getElementById('profileFullName').value = source.full_name || [source.first_name, source.last_name].filter(Boolean).join(' ').trim() || '';
      document.getElementById('profileDni').value = source.dni || '';
      document.getElementById('profileEmail').value = source.email || '';
      document.getElementById('profilePhone').value = source.phone || '';
      this.setMessage(this.profileMessage, 'Datos cargados.');
      this.syncDerivedNameFields();
    } catch (error) {
      this.setMessage(this.profileMessage, error.message || 'No se pudo cargar el perfil', 'error');
    }
  },

  async handleProfileSave(event) {
    event.preventDefault();
    const payload = {
      first_name: document.getElementById('profileFirstName').value.trim(),
      last_name: document.getElementById('profileLastName').value.trim(),
      full_name: this.buildFullName(),
      dni: document.getElementById('profileDni').value.trim(),
      email: document.getElementById('profileEmail').value.trim(),
      phone: document.getElementById('profilePhone').value.trim(),
    };

    this.setMessage(this.profileMessage, 'Guardando...');
    try {
      const data = await this.request('customer/profile.php', {
        method: 'PUT',
        body: JSON.stringify(payload),
      });
      if (data.customer) {
        this.state.customer = data.customer;
      }
      this.setMessage(this.profileMessage, 'Datos actualizados.', 'success');
      await this.loadSession();
    } catch (error) {
      this.setMessage(this.profileMessage, error.message || 'No se pudieron guardar los datos', 'error');
    }
  },

  async loadOrders() {
    this.ordersTable.innerHTML = '<tr><td class="account-empty">Cargando pedidos...</td></tr>';
    this.showOrderDetailPlaceholder('Cargando detalle...');
    try {
      const data = await this.request('customer/orders.php', { method: 'GET' });
      const orders = Array.isArray(data.orders) ? data.orders : Array.isArray(data.items) ? data.items : [];
      this.state.orders = orders;
      if (orders.length === 0) {
        this.ordersTable.innerHTML = '<tr><td class="account-empty">No tenés pedidos todavía.</td></tr>';
        this.showOrderDetailPlaceholder('Todavía no tenés pedidos para ver en detalle.');
        return;
      }

      this.ordersTable.innerHTML = `
        <thead>
          <tr>
            <th>Orden</th>
            <th>Pago</th>
            <th>Operación</th>
            <th>Total</th>
            <th>Fecha</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          ${orders.map(order => `
            <tr data-order-row="${this.escapeHTML(order.id)}">
              <td>
                <strong>${this.escapeHTML(order.order_number || `#${order.id || ''}`)}</strong>
                ${order.payment_method ? `<div style="color:var(--text-muted);font-size:0.8rem">${this.escapeHTML(order.payment_method)}</div>` : ''}
              </td>
              <td>${this.renderStatusBadge('payment', order.payment_status || 'pending')}</td>
              <td>${this.renderStatusBadge('fulfillment', order.fulfillment_status || 'queued')}</td>
              <td style="font-weight:700;color:var(--accent)">$${Number(order.total || 0).toLocaleString('es-AR')}</td>
              <td style="color:var(--text-secondary)">${this.escapeHTML(this.formatDate(order.created_at || order.date || ''))}</td>
              <td style="text-align:right">
                <button type="button" class="btn btn-secondary btn-sm" data-order-detail="${this.escapeHTML(order.id)}">Ver detalle</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      `;

      this.ordersTable.querySelectorAll('[data-order-detail]').forEach((button) => {
        button.addEventListener('click', () => {
          this.loadOrderDetail(button.dataset.orderDetail);
        });
      });

      const currentOrderId = this.state.activeOrderId || orders[0]?.id;
      if (currentOrderId) {
        await this.loadOrderDetail(currentOrderId);
      } else {
        this.showOrderDetailPlaceholder('Seleccioná un pedido para ver el detalle.');
      }
    } catch (error) {
      this.ordersTable.innerHTML = `<tr><td class="account-empty">${this.escapeHTML(error.message || 'No se pudieron cargar los pedidos')}</td></tr>`;
      this.showOrderDetailPlaceholder(error.message || 'No se pudo cargar el detalle de pedidos.');
    }
  },

  showOrderDetailPlaceholder(message) {
    if (this.orderDetailCard) {
      this.orderDetailCard.hidden = true;
      this.orderDetailCard.innerHTML = '';
    }
    if (this.orderDetailPlaceholder) {
      this.orderDetailPlaceholder.hidden = false;
      this.orderDetailPlaceholder.textContent = message;
    }
  },

  renderOrderDetail(order) {
    if (!this.orderDetailCard) return;

    const items = Array.isArray(order.items) ? order.items : [];
    const notes = String(order.notes || '').trim();
    const paymentReference = String(order.payment_reference || '').trim();

    this.orderDetailCard.innerHTML = `
      <div class="order-detail-head">
        <div>
          <h3>${this.escapeHTML(order.order_number || `#${order.id || ''}`)}</h3>
          <p style="margin:0;color:var(--text-secondary)">Detalle actualizado del pedido seleccionado.</p>
        </div>
        <div class="order-status-stack">
          ${this.renderStatusBadge('payment', order.payment_status || 'pending')}
          ${this.renderStatusBadge('fulfillment', order.fulfillment_status || 'queued')}
        </div>
      </div>
      <div class="order-detail-meta">
        <div>
          <strong>Fecha</strong>
          <span>${this.escapeHTML(this.formatDateTime(order.created_at || ''))}</span>
        </div>
        <div>
          <strong>Total</strong>
          <span>$${Number(order.total || 0).toLocaleString('es-AR')}</span>
        </div>
        <div>
          <strong>Pago</strong>
          <span>${this.escapeHTML(this.statusLabel('payment', order.payment_status || 'pending'))}</span>
        </div>
        <div>
          <strong>Operación</strong>
          <span>${this.escapeHTML(this.statusLabel('fulfillment', order.fulfillment_status || 'queued'))}</span>
        </div>
        <div>
          <strong>Método</strong>
          <span>${this.escapeHTML(order.payment_method || 'mercadopago')}</span>
        </div>
        <div>
          <strong>Referencia</strong>
          <span>${this.escapeHTML(paymentReference || '—')}</span>
        </div>
      </div>
      ${notes ? `<div><strong style="display:block;margin-bottom:6px;color:var(--text-muted);font-size:0.82rem;text-transform:uppercase;letter-spacing:0.04em">Notas</strong><p style="margin:0;color:var(--text-secondary)">${this.escapeHTML(notes)}</p></div>` : ''}
      <div>
        <strong style="display:block;margin-bottom:8px;color:var(--text-muted);font-size:0.82rem;text-transform:uppercase;letter-spacing:0.04em">Productos</strong>
        <div class="order-detail-items">
          ${items.length > 0 ? items.map(item => `
            <article class="order-detail-item">
              <img src="${this.escapeHTML(item.image_url || 'assets/logo/logo.png')}" alt="${this.escapeHTML(item.product_name || 'Producto')}" loading="lazy">
              <div>
                <h4>${this.escapeHTML(item.product_name || 'Producto')}</h4>
                <p>${this.escapeHTML(item.variant_label || 'Variante base')}</p>
                <p>Cantidad: ${this.escapeHTML(item.quantity || 0)}</p>
              </div>
              <strong>$${Number(item.unit_price || 0).toLocaleString('es-AR')}</strong>
            </article>
          `).join('') : '<div class="account-empty">Este pedido no tiene items visibles.</div>'}
        </div>
      </div>
    `;

    if (this.orderDetailPlaceholder) {
      this.orderDetailPlaceholder.hidden = true;
    }
    this.orderDetailCard.hidden = false;
  },

  async loadOrderDetail(orderId) {
    if (!orderId) {
      this.showOrderDetailPlaceholder('Seleccioná un pedido para ver el detalle.');
      return;
    }

    const normalizedId = String(orderId);
    this.state.activeOrderId = normalizedId;
    this.ordersTable.querySelectorAll('[data-order-row]').forEach((row) => {
      row.style.background = row.dataset.orderRow === normalizedId ? 'rgba(255, 107, 43, 0.06)' : '';
    });

    this.showOrderDetailPlaceholder('Cargando detalle del pedido...');
    try {
      const data = await this.request(`customer/orders.php?id=${encodeURIComponent(normalizedId)}`, { method: 'GET' });
      if (!data.order) {
        throw new Error('No se encontró el detalle del pedido.');
      }
      this.renderOrderDetail(data.order);
    } catch (error) {
      this.showOrderDetailPlaceholder(error.message || 'No se pudo cargar el detalle del pedido.');
    }
  },

  normalizeAddress(address = {}) {
    return {
      id: address.id || '',
      label: address.label || address.nickname || address.title || 'Dirección',
      recipient_name: address.recipient_name || address.recipient || address.name || '',
      street: address.street || address.line1 || address.address1 || '',
      city: address.city || '',
      province: address.province || address.state || '',
      postal_code: address.postal_code || address.zip || '',
      phone: address.phone || '',
      notes: address.notes || '',
      is_default: Number(address.is_default ?? address.default ?? 0) === 1,
    };
  },

  renderAddressList(addresses) {
    if (!addresses.length) {
      this.addressesList.innerHTML = '<div class="account-empty">No tenés direcciones guardadas.</div>';
      return;
    }

    this.addressesList.innerHTML = addresses.map(address => `
      <article class="address-item">
        <h4>${this.escapeHTML(address.label)}${address.is_default ? ' · Principal' : ''}</h4>
        <p>${this.escapeHTML(address.recipient_name)}</p>
        <p>${this.escapeHTML(address.street)}</p>
        <p>${this.escapeHTML(address.city)}${address.province ? `, ${this.escapeHTML(address.province)}` : ''}${address.postal_code ? ` (${this.escapeHTML(address.postal_code)})` : ''}</p>
        ${address.phone ? `<p>${this.escapeHTML(address.phone)}</p>` : ''}
        ${address.notes ? `<p>${this.escapeHTML(address.notes)}</p>` : ''}
        <div class="address-actions">
          <button type="button" class="btn btn-secondary btn-sm" data-address-action="edit" data-address-id="${this.escapeHTML(address.id)}">Editar</button>
          <button type="button" class="btn btn-secondary btn-sm" data-address-action="delete" data-address-id="${this.escapeHTML(address.id)}">Eliminar</button>
        </div>
      </article>
    `).join('');

    this.addressesList.querySelectorAll('[data-address-action="edit"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const address = addresses.find(item => String(item.id) === String(btn.dataset.addressId));
        if (address) this.fillAddressForm(address);
      });
    });

    this.addressesList.querySelectorAll('[data-address-action="delete"]').forEach(btn => {
      btn.addEventListener('click', () => {
        this.handleAddressDelete(btn.dataset.addressId);
      });
    });
  },

  async loadAddresses() {
    this.addressesList.innerHTML = '<div class="account-empty">Cargando direcciones...</div>';
    try {
      const data = await this.request('customer/addresses.php', { method: 'GET' });
      const addresses = Array.isArray(data.addresses) ? data.addresses : Array.isArray(data.items) ? data.items : [];
      this.state.addresses = addresses.map(address => this.normalizeAddress(address));
      this.renderAddressList(this.state.addresses);
    } catch (error) {
      this.addressesList.innerHTML = `<div class="account-empty">${this.escapeHTML(error.message || 'No se pudieron cargar las direcciones')}</div>`;
    }
  },

  fillAddressForm(address = {}) {
    const normalized = this.normalizeAddress(address);
    document.getElementById('addressId').value = normalized.id;
    document.getElementById('addressLabel').value = normalized.label;
    document.getElementById('addressRecipient').value = normalized.recipient_name;
    document.getElementById('addressStreet').value = normalized.street;
    document.getElementById('addressCity').value = normalized.city;
    document.getElementById('addressProvince').value = normalized.province;
    document.getElementById('addressPostalCode').value = normalized.postal_code;
    document.getElementById('addressPhone').value = normalized.phone;
    document.getElementById('addressNotes').value = normalized.notes;
    document.getElementById('addressDefault').checked = normalized.is_default;
    this.setMessage(this.addressMessage, 'Editando dirección.');
  },

  resetAddressForm() {
    document.getElementById('addressForm').reset();
    document.getElementById('addressId').value = '';
    this.setMessage(this.addressMessage, '');
  },

  buildAddressPayload() {
    return {
      label: document.getElementById('addressLabel').value.trim(),
      recipient_name: document.getElementById('addressRecipient').value.trim(),
      street: document.getElementById('addressStreet').value.trim(),
      city: document.getElementById('addressCity').value.trim(),
      province: document.getElementById('addressProvince').value.trim(),
      postal_code: document.getElementById('addressPostalCode').value.trim(),
      phone: document.getElementById('addressPhone').value.trim(),
      notes: document.getElementById('addressNotes').value.trim(),
      is_default: document.getElementById('addressDefault').checked ? 1 : 0,
    };
  },

  async handleAddressSave(event) {
    event.preventDefault();
    const id = document.getElementById('addressId').value.trim();
    const payload = this.buildAddressPayload();
    this.setMessage(this.addressMessage, 'Guardando...');

    try {
      const data = await this.request(`customer/addresses.php${id ? `?id=${encodeURIComponent(id)}` : ''}`, {
        method: id ? 'PUT' : 'POST',
        body: JSON.stringify(payload),
      });
      if (data.address) {
        this.state.addresses = this.state.addresses.map(item => String(item.id) === String(data.address.id) ? this.normalizeAddress(data.address) : item);
      }
      this.setMessage(this.addressMessage, 'Dirección guardada.', 'success');
      this.resetAddressForm();
      await this.loadAddresses();
    } catch (error) {
      this.setMessage(this.addressMessage, error.message || 'No se pudo guardar la dirección', 'error');
    }
  },

  async handleAddressDelete(id) {
    if (!id) return;
    if (!window.confirm('¿Eliminar esta dirección?')) return;
    try {
      await this.request(`customer/addresses.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
      await this.loadAddresses();
    } catch (error) {
      this.setMessage(this.addressMessage, error.message || 'No se pudo eliminar la dirección', 'error');
    }
  },
};

document.addEventListener('DOMContentLoaded', () => Account.init());
