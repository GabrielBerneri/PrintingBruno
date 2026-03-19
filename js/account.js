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
    orders: [],
    orderDetails: {},
    orderDetailErrors: {},
    orderDetailLoadingId: '',
    activeOrderId: '',
    ordersFilter: 'all',
  },

  init() {
    this.cacheElements();
    this.bindAuthTabs();
    this.bindDashboardTabs();
    this.bindOrderFilters();
    this.bindForms();
    this.bindQueryActions();
    this.showAuthTab('login');
    this.showDashboardTab('orders');
    this.syncModeFromQuery();
    this.loadSession();
  },

  cacheElements() {
    this.accountGrid = document.getElementById('accountGrid');
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
    this.ordersList = document.getElementById('ordersList');
    this.ordersFilterNote = document.getElementById('ordersFilterNote');
    this.addressesList = document.getElementById('addressesList');
    this.accountVerificationNotice = document.getElementById('accountVerificationNotice');
    this.resendVerificationBtn = document.getElementById('resendVerificationBtn');
    this.ordersOverviewTotal = document.getElementById('ordersOverviewTotal');
    this.ordersOverviewPending = document.getElementById('ordersOverviewPending');
    this.ordersOverviewProduction = document.getElementById('ordersOverviewProduction');
    this.ordersOverviewShipped = document.getElementById('ordersOverviewShipped');
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

  paymentMethodLabel(value) {
    const labels = {
      mercadopago: 'Mercado Pago',
      transferencia: 'Transferencia',
      efectivo: 'Efectivo',
    };
    const normalized = String(value || 'mercadopago').trim().toLowerCase();
    return labels[normalized] || normalized;
  },

  isOrderPending(order = {}) {
    return ['pending', 'under_review'].includes(String(order.payment_status || '').trim().toLowerCase());
  },

  isOrderCancelled(order = {}) {
    const paymentStatus = String(order.payment_status || '').trim().toLowerCase();
    const fulfillmentStatus = String(order.fulfillment_status || '').trim().toLowerCase();
    return ['rejected', 'cancelled', 'refunded', 'charged_back'].includes(paymentStatus) || fulfillmentStatus === 'cancelled';
  },

  isOrderInProduction(order = {}) {
    const fulfillmentStatus = String(order.fulfillment_status || '').trim().toLowerCase();
    return ['queued', 'in_production', 'ready'].includes(fulfillmentStatus) && !this.isOrderCancelled(order);
  },

  isOrderShipped(order = {}) {
    const fulfillmentStatus = String(order.fulfillment_status || '').trim().toLowerCase();
    return ['shipped', 'delivered'].includes(fulfillmentStatus);
  },

  getOrderInlineHint(order = {}) {
    const paymentMethod = String(order.payment_method || '').trim().toLowerCase();
    const paymentStatus = String(order.payment_status || '').trim().toLowerCase();

    if (paymentMethod === 'transferencia' && ['pending', 'under_review'].includes(paymentStatus)) {
      return 'Tu pedido está reservado. Cuando confirmemos la transferencia, pasa a producción.';
    }

    if (paymentMethod === 'efectivo' && ['pending', 'under_review'].includes(paymentStatus)) {
      return 'El pago en efectivo se coordina al retirar o recibir el pedido.';
    }

    return '';
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

  bindOrderFilters() {
    document.querySelectorAll('[data-order-filter]').forEach(btn => {
      btn.addEventListener('click', async () => {
        this.state.ordersFilter = btn.dataset.orderFilter || 'all';
        const visibleOrders = this.getFilteredOrders();
        const activeVisible = visibleOrders.find(order => String(order.id) === String(this.state.activeOrderId || ''));
        const targetOrder = activeVisible || visibleOrders[0];
        if (targetOrder) {
          await this.loadOrderDetail(targetOrder.id);
        } else {
          this.state.activeOrderId = '';
          this.renderOrdersList();
        }
      });
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
    if (this.resendVerificationBtn) {
      this.resendVerificationBtn.addEventListener('click', () => this.handleResendVerification());
    }
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
    if (this.accountGrid) {
      this.accountGrid.classList.remove('account-grid-dashboard');
    }
    this.state.orders = [];
    this.state.orderDetails = {};
    this.state.orderDetailErrors = {};
    this.state.orderDetailLoadingId = '';
    this.state.activeOrderId = '';
    if (this.sessionMeta) {
      this.sessionMeta.hidden = true;
    }
    this.updateVerificationNotice();
  },

  showDashboardView() {
    this.dashboardCard.hidden = false;
    this.authCard.hidden = true;
    if (this.accountGrid) {
      this.accountGrid.classList.add('account-grid-dashboard');
    }
    const customer = this.state.customer || {};
    const name = customer.full_name || [customer.first_name, customer.last_name].filter(Boolean).join(' ').trim() || customer.email || 'cliente';
    this.customerGreeting.textContent = `Hola, ${name}`;

    if (this.sessionMeta) {
      this.sessionMeta.hidden = false;
      this.sessionMeta.textContent = this.state.expiresAt
        ? `Sesión activa hasta ${this.formatDateTime(this.state.expiresAt)}`
        : 'Sesión activa';
    }
    this.updateVerificationNotice();
  },

  updateVerificationNotice() {
    if (!this.accountVerificationNotice) return;
    const customer = this.state.customer || {};
    const shouldShow = this.state.authenticated && !customer.is_verified;
    this.accountVerificationNotice.hidden = !shouldShow;
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

  async handleResendVerification() {
    if (!this.resendVerificationBtn) return;
    const originalText = this.resendVerificationBtn.textContent;
    this.resendVerificationBtn.disabled = true;
    this.resendVerificationBtn.textContent = 'Enviando...';

    try {
      const data = await this.request('auth/resend_verification.php', {
        method: 'POST',
        body: JSON.stringify({}),
      });
      this.showFlowStatus(data.message || 'Te reenviamos el email de verificación.', 'success');
    } catch (error) {
      this.showFlowStatus(error.message || 'No se pudo reenviar el email de verificación.', 'error');
    } finally {
      this.resendVerificationBtn.disabled = false;
      this.resendVerificationBtn.textContent = originalText;
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
      this.showDashboardView();
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

  setOrdersFilterNote(text) {
    if (!this.ordersFilterNote) return;
    this.ordersFilterNote.textContent = text || '';
  },

  renderOrdersOverview(orders = []) {
    const total = orders.length;
    const pending = orders.filter(order => this.isOrderPending(order)).length;
    const production = orders.filter(order => this.isOrderInProduction(order)).length;
    const shipped = orders.filter(order => this.isOrderShipped(order)).length;

    if (this.ordersOverviewTotal) this.ordersOverviewTotal.textContent = total.toLocaleString('es-AR');
    if (this.ordersOverviewPending) this.ordersOverviewPending.textContent = pending.toLocaleString('es-AR');
    if (this.ordersOverviewProduction) this.ordersOverviewProduction.textContent = production.toLocaleString('es-AR');
    if (this.ordersOverviewShipped) this.ordersOverviewShipped.textContent = shipped.toLocaleString('es-AR');
  },

  getFilteredOrders() {
    const filter = this.state.ordersFilter || 'all';
    const orders = Array.isArray(this.state.orders) ? this.state.orders : [];

    return orders.filter((order) => {
      switch (filter) {
        case 'pending':
          return this.isOrderPending(order);
        case 'production':
          return this.isOrderInProduction(order);
        case 'shipped':
          return this.isOrderShipped(order);
        case 'cancelled':
          return this.isOrderCancelled(order);
        default:
          return true;
      }
    });
  },

  renderOrderDetailContent(order) {
    const items = Array.isArray(order.items) ? order.items : [];
    const notes = String(order.notes || '').trim();
    const paymentReference = String(order.payment_reference || '').trim();
    const inlineHint = this.getOrderInlineHint(order);

    return `
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
          <span>${this.escapeHTML(this.paymentMethodLabel(order.payment_method || 'mercadopago'))}</span>
        </div>
        <div>
          <strong>Referencia</strong>
          <span>${this.escapeHTML(paymentReference || '—')}</span>
        </div>
      </div>
      ${inlineHint ? `<div class="order-inline-note"><strong>Próximo paso:</strong> ${this.escapeHTML(inlineHint)}</div>` : ''}
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
  },

  renderOrdersList() {
    const orders = this.getFilteredOrders();
    document.querySelectorAll('[data-order-filter]').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.orderFilter === this.state.ordersFilter);
    });

    if (!this.ordersList) return;

    if (!orders.length) {
      this.ordersList.innerHTML = '<div class="account-empty">No hay pedidos en este estado.</div>';
      this.setOrdersFilterNote(`Mostrando 0 de ${Array.isArray(this.state.orders) ? this.state.orders.length : 0} pedidos`);
      return;
    }

    this.setOrdersFilterNote(`Mostrando ${orders.length} de ${Array.isArray(this.state.orders) ? this.state.orders.length : orders.length} pedidos`);
    this.ordersList.innerHTML = orders.map((order) => {
      const preview = order.first_item || null;
      const orderId = String(order.id);
      const isActive = orderId === String(this.state.activeOrderId || '');
      const activeClass = isActive ? ' active' : '';
      const detail = this.state.orderDetails[orderId] || null;
      const detailError = this.state.orderDetailErrors[orderId] || '';
      const isLoading = this.state.orderDetailLoadingId === orderId;
      const itemCount = Number(order.item_count || 0);
      const unitsCount = Number(order.units_count || 0);
      const inlineHint = this.getOrderInlineHint(order);
      let detailMarkup = '';

      if (isActive) {
        if (isLoading) {
          detailMarkup = '<div class="order-card-detail"><div class="order-card-detail-state">Cargando detalle del pedido...</div></div>';
        } else if (detailError) {
          detailMarkup = `<div class="order-card-detail"><div class="order-card-detail-state">${this.escapeHTML(detailError)}</div></div>`;
        } else if (detail) {
          detailMarkup = `<div class="order-card-detail">${this.renderOrderDetailContent(detail)}</div>`;
        }
      }

      return `
        <article class="order-card${activeClass}" data-order-card="${this.escapeHTML(order.id)}" tabindex="0" role="button" aria-expanded="${isActive ? 'true' : 'false'}">
          <div class="order-card-head">
            <div class="order-card-order">
              <strong>${this.escapeHTML(order.order_number || `#${order.id || ''}`)}</strong>
              <span>${this.escapeHTML(this.formatDate(order.created_at || order.date || ''))}</span>
            </div>
            <div class="order-status-stack">
              ${this.renderStatusBadge('payment', order.payment_status || 'pending')}
              ${this.renderStatusBadge('fulfillment', order.fulfillment_status || 'queued')}
            </div>
          </div>

          <div class="order-card-preview">
            <img src="${this.escapeHTML(preview?.image_url || 'assets/logo/logo.png')}" alt="${this.escapeHTML(preview?.product_name || 'Pedido')}" loading="lazy">
            <div>
              <strong>${this.escapeHTML(preview?.product_name || 'Pedido sin vista previa')}</strong>
              <p>${this.escapeHTML(preview?.variant_label || (itemCount > 0 ? 'Variante base' : 'Sin items visibles'))}</p>
              <p>${itemCount > 1 ? `${itemCount} productos en el pedido` : itemCount === 1 ? '1 producto en el pedido' : 'Sin items en el resumen'}${unitsCount > 0 ? ` · ${unitsCount} unidad${unitsCount === 1 ? '' : 'es'}` : ''}</p>
            </div>
          </div>

          <div class="order-card-meta">
            <span class="meta-chip">${this.escapeHTML(this.paymentMethodLabel(order.payment_method || 'mercadopago'))}</span>
            <span class="meta-chip">${this.escapeHTML(this.statusLabel('payment', order.payment_status || 'pending'))}</span>
            <span class="meta-chip">${this.escapeHTML(this.statusLabel('fulfillment', order.fulfillment_status || 'queued'))}</span>
          </div>

          ${inlineHint ? `<div class="order-inline-note"><strong>Próximo paso:</strong> ${this.escapeHTML(inlineHint)}</div>` : ''}

          <div class="order-card-footer">
            <div class="order-card-total">
              <strong>Total</strong>
              <span>$${Number(order.total || 0).toLocaleString('es-AR')}</span>
            </div>
            <div class="order-card-expand-hint">
              <strong>${isActive ? 'Detalle desplegado' : 'Tocá para ver el detalle'}</strong>
            </div>
          </div>

          ${detailMarkup}
        </article>
      `;
    }).join('');

    this.ordersList.querySelectorAll('[data-order-card]').forEach((card) => {
      const open = () => this.toggleOrderDetail(card.dataset.orderCard);
      card.addEventListener('click', open);
      card.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          open();
        }
      });
    });
  },

  async loadOrders() {
    this.state.orderDetails = {};
    this.state.orderDetailErrors = {};
    this.state.orderDetailLoadingId = '';
    this.state.activeOrderId = this.state.activeOrderId || '';
    if (this.ordersList) {
      this.ordersList.innerHTML = '<div class="account-empty">Cargando pedidos...</div>';
    }
    this.renderOrdersOverview([]);
    this.setOrdersFilterNote('Cargando pedidos...');
    try {
      const data = await this.request('customer/orders.php', { method: 'GET' });
      const orders = Array.isArray(data.orders) ? data.orders : Array.isArray(data.items) ? data.items : [];
      this.state.orders = orders;
      this.renderOrdersOverview(orders);

      if (orders.length === 0) {
        if (this.ordersList) {
          this.ordersList.innerHTML = '<div class="account-empty">No tenés pedidos todavía.</div>';
        }
        this.setOrdersFilterNote('Todavía no registrás compras.');
        return;
      }

      const availableIds = new Set(orders.map(order => String(order.id)));
      if (!availableIds.has(String(this.state.activeOrderId || ''))) {
        this.state.activeOrderId = String(orders[0].id);
      }

      this.renderOrdersList();
      const visibleOrders = this.getFilteredOrders();
      const currentOrderId = visibleOrders.find(order => String(order.id) === String(this.state.activeOrderId || ''))?.id
        || visibleOrders[0]?.id
        || orders[0]?.id;

      if (currentOrderId) {
        await this.loadOrderDetail(currentOrderId);
      }
    } catch (error) {
      if (this.ordersList) {
        this.ordersList.innerHTML = `<div class="account-empty">${this.escapeHTML(error.message || 'No se pudieron cargar los pedidos')}</div>`;
      }
      this.setOrdersFilterNote('No se pudo cargar el historial.');
    }
  },

  async toggleOrderDetail(orderId) {
    const normalizedId = String(orderId || '');
    if (!normalizedId) return;

    if (String(this.state.activeOrderId || '') === normalizedId) {
      this.state.activeOrderId = '';
      this.renderOrdersList();
      return;
    }

    await this.loadOrderDetail(normalizedId);
  },

  async loadOrderDetail(orderId) {
    if (!orderId) {
      this.state.activeOrderId = '';
      this.renderOrdersList();
      return;
    }

    const normalizedId = String(orderId);
    this.state.activeOrderId = normalizedId;
    if (this.state.orderDetails[normalizedId]) {
      this.renderOrdersList();
      return;
    }
    this.state.orderDetailErrors[normalizedId] = '';
    this.state.orderDetailLoadingId = normalizedId;
    this.renderOrdersList();
    try {
      const data = await this.request(`customer/orders.php?id=${encodeURIComponent(normalizedId)}`, { method: 'GET' });
      if (!data.order) {
        throw new Error('No se encontró el detalle del pedido.');
      }
      this.state.orderDetails[normalizedId] = data.order;
      this.state.orderDetailLoadingId = '';
      this.state.orderDetailErrors[normalizedId] = '';
      this.renderOrdersList();
    } catch (error) {
      this.state.orderDetailLoadingId = '';
      this.state.orderDetailErrors[normalizedId] = error.message || 'No se pudo cargar el detalle del pedido.';
      this.renderOrdersList();
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
