/* ============================================
   PrintingBruno - Shopping Cart
   localStorage cart + MercadoPago checkout
   ============================================ */

const Cart = {
    STORAGE_KEY: 'pb_cart',
    CHECKOUT_TOKEN_KEY: 'pb_checkout_token',
    getApiBase() {
        const currentPath = window.location.pathname || '/';
        if (currentPath.includes('/printingbruno/')) {
            return `${window.location.origin}/printingbruno/api`;
        }
        return `${window.location.origin}/api`;
    },

    itemKey(item) {
        if (item && item.cart_key) {
            return String(item.cart_key);
        }
        const variantId = Number(item?.variant_id || 0);
        const productId = Number(item?.product_id || item?.id || 0);
        return variantId > 0 ? `v:${variantId}` : `p:${productId}`;
    },

    normalizeItem(item) {
        const productId = Number(item?.product_id || item?.id || 0);
        if (!Number.isFinite(productId) || productId <= 0) {
            return null;
        }

        const variantId = Number(item?.variant_id || 0);
        return {
            id: productId,
            product_id: productId,
            variant_id: variantId > 0 ? variantId : null,
            variant_label: String(item?.variant_label || '').trim(),
            cart_key: this.itemKey(item),
            name: String(item?.name || ''),
            price: Number(item?.price || 0),
            image_url: String(item?.image_url || ''),
            quantity: Math.max(1, Number(item?.quantity || 1) || 1),
            transfer_discount: Number(item?.transfer_discount || 0) === 1 ? 1 : 0,
        };
    },

    // ===== Cart Data Management =====
    getItems() {
        try {
            const parsed = JSON.parse(localStorage.getItem(this.STORAGE_KEY)) || [];
            if (!Array.isArray(parsed)) return [];
            return parsed.map(item => this.normalizeItem(item)).filter(Boolean);
        } catch { return []; }
    },

    saveItems(items) {
        const normalized = (items || []).map(item => this.normalizeItem(item)).filter(Boolean);
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(normalized));
        this.updateBadge();
        this.renderCartDrawer();
    },

    addItem(product, quantity = 1) {
        const items = this.getItems();
        const incoming = this.normalizeItem({ ...product, quantity });
        if (!incoming) return;
        const incomingKey = this.itemKey(incoming);
        const existing = items.find(i => this.itemKey(i) === incomingKey);

        if (existing) {
            existing.quantity += quantity;
        } else {
            items.push(incoming);
        }

        this.saveItems(items);
        this.showNotification(`${incoming.name} agregado al carrito`);
    },

    removeItem(itemKey) {
        const normalizedKey = String(itemKey);
        const items = this.getItems().filter(i => this.itemKey(i) !== normalizedKey);
        this.saveItems(items);
    },

    updateQuantity(itemKey, quantity) {
        const items = this.getItems();
        const normalizedKey = String(itemKey);
        const item = items.find(i => this.itemKey(i) === normalizedKey);
        if (item) {
            item.quantity = Math.max(1, quantity);
            this.saveItems(items);
        }
    },

    clear() {
        localStorage.removeItem(this.STORAGE_KEY);
        sessionStorage.removeItem(this.CHECKOUT_TOKEN_KEY);
        this.updateBadge();
        this.renderCartDrawer();
    },

    getCheckoutToken() {
        let token = sessionStorage.getItem(this.CHECKOUT_TOKEN_KEY);
        if (!token) {
            token = `${Date.now()}-${Math.random().toString(36).slice(2, 12)}`;
            sessionStorage.setItem(this.CHECKOUT_TOKEN_KEY, token);
        }
        return token;
    },

    getTotal() {
        return this.getItems().reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },

    // Suma de subtotales de items que tienen habilitado el descuento por transferencia/efectivo
    getTransferDiscountEligibleSubtotal() {
        return this.getItems().reduce((sum, item) => sum + (item.transfer_discount === 1 ? item.price * item.quantity : 0), 0);
    },

    getTransferDiscountAmount() {
        return Math.round(this.getTransferDiscountEligibleSubtotal() * 0.10 * 100) / 100;
    },

    getCount() {
        return this.getItems().reduce((sum, item) => sum + item.quantity, 0);
    },

    normalizeCustomerAddress(address = {}) {
        return {
            id: Number(address.id || 0) || null,
            label: String(address.label || address.nickname || 'Dirección').trim(),
            recipient_name: String(address.recipient_name || address.recipient || '').trim(),
            street: String(address.street || address.line1 || '').trim(),
            city: String(address.city || '').trim(),
            province: String(address.province || address.state || '').trim(),
            postal_code: String(address.postal_code || address.zip || '').trim(),
            phone: String(address.phone || '').trim(),
            notes: String(address.notes || '').trim(),
            is_default: Number(address.is_default || 0) === 1,
        };
    },

    async fetchCheckoutCustomerContext() {
        const context = {
            authenticated: false,
            customer: null,
            addresses: [],
        };

        try {
            const sessionRes = await fetch(`${this.getApiBase()}/auth/session.php`, {
                method: 'GET',
                credentials: 'same-origin',
            });
            const sessionData = await sessionRes.json();
            if (!sessionRes.ok || !sessionData.authenticated) {
                return context;
            }

            context.authenticated = true;
            context.customer = sessionData.customer || null;

            try {
                const addressRes = await fetch(`${this.getApiBase()}/customer/addresses.php`, {
                    method: 'GET',
                    credentials: 'same-origin',
                });
                const addressData = await addressRes.json();
                if (addressRes.ok) {
                    context.addresses = (Array.isArray(addressData.addresses) ? addressData.addresses : [])
                        .map(address => this.normalizeCustomerAddress(address));
                }
            } catch (_) {
                context.addresses = [];
            }
        } catch (_) {
            return context;
        }

        return context;
    },

    applyCheckoutAddressToForm(address = {}) {
        const normalized = this.normalizeCustomerAddress(address);
        const recipient = document.getElementById('checkoutRecipient');
        const street = document.getElementById('checkoutStreet');
        const city = document.getElementById('checkoutCity');
        const province = document.getElementById('checkoutProvince');
        const postalCode = document.getElementById('checkoutPostalCode');

        if (recipient) recipient.value = normalized.recipient_name || '';
        if (street) street.value = normalized.street || '';
        if (city) city.value = normalized.city || '';
        if (province) province.value = normalized.province || '';
        if (postalCode) postalCode.value = normalized.postal_code || '';
    },

    buildCheckoutShippingPayload() {
        const savedAddressSelect = document.getElementById('checkoutSavedAddress');
        const savedAddressGroup = document.getElementById('checkoutSavedAddressGroup');
        const recipient = document.getElementById('checkoutRecipient');
        const street = document.getElementById('checkoutStreet');
        const city = document.getElementById('checkoutCity');
        const province = document.getElementById('checkoutProvince');
        const postalCode = document.getElementById('checkoutPostalCode');
        const phone = document.getElementById('checkoutPhone');

        const savedAddressId = Number(savedAddressSelect?.value || 0) || null;
        const payload = {
            customer_address_id: savedAddressId,
            use_saved_address: savedAddressId ? 1 : 0,
            recipient_name: recipient?.value?.trim() || '',
            street: street?.value?.trim() || '',
            city: city?.value?.trim() || '',
            province: province?.value?.trim() || '',
            postal_code: postalCode?.value?.trim() || '',
            phone: phone?.value?.trim() || '',
        };

        const hasAnyAddressData = payload.recipient_name !== ''
            || payload.street !== ''
            || payload.city !== ''
            || payload.province !== ''
            || payload.postal_code !== ''
            || !!payload.customer_address_id;

        const hasVisibleSavedAddressSelector = !!savedAddressSelect && !!savedAddressGroup && !savedAddressGroup.hidden;
        return hasAnyAddressData || hasVisibleSavedAddressSelector ? payload : null;
    },

    async prefillCheckoutCustomerContext(overlay) {
        const context = await this.fetchCheckoutCustomerContext();
        const nameInput = document.getElementById('checkoutName');
        const emailInput = document.getElementById('checkoutEmail');
        const phoneInput = document.getElementById('checkoutPhone');
        const helperText = overlay.querySelector('#checkoutAccountHint');
        const savedAddressGroup = document.getElementById('checkoutSavedAddressGroup');
        const savedAddressSelect = document.getElementById('checkoutSavedAddress');

        if (context.customer) {
            const fullName = String(context.customer.full_name || '').trim();
            if (nameInput && !nameInput.value.trim()) nameInput.value = fullName;
            if (emailInput && !emailInput.value.trim()) emailInput.value = String(context.customer.email || '').trim();
            if (phoneInput && !phoneInput.value.trim()) phoneInput.value = String(context.customer.phone || '').trim();
        }

        if (helperText) {
            helperText.style.display = 'block';
            helperText.textContent = context.authenticated
                ? 'La dirección elegida va a quedar guardada en este pedido para que el admin la vea tal como fue comprada.'
                : 'Si completás una dirección ahora, también va a quedar asociada al pedido.';
        }

        if (!savedAddressGroup || !savedAddressSelect) {
            return;
        }

        if (!context.addresses.length) {
            savedAddressGroup.hidden = true;
            return;
        }

        savedAddressGroup.hidden = false;
        savedAddressSelect.innerHTML = `
            <option value="">Ingresar otra dirección</option>
            ${context.addresses.map(address => `
                <option value="${this.escapeAttr(address.id)}">
                    ${this.escapeHTML(address.label || 'Dirección')} · ${this.escapeHTML(address.street || '')}
                </option>
            `).join('')}
        `;

        const defaultAddress = context.addresses.find(address => address.is_default) || context.addresses[0];
        if (defaultAddress) {
            savedAddressSelect.value = String(defaultAddress.id);
            this.applyCheckoutAddressToForm(defaultAddress);
        }

        savedAddressSelect.addEventListener('change', () => {
            const selectedId = Number(savedAddressSelect.value || 0);
            if (!selectedId) {
                this.applyCheckoutAddressToForm({});
                return;
            }
            const selected = context.addresses.find(address => Number(address.id) === selectedId);
            if (selected) {
                this.applyCheckoutAddressToForm(selected);
            }
        });
    },

    // ===== UI: Badge =====
    updateBadge() {
        const badges = document.querySelectorAll('.cart-badge');
        const count = this.getCount();
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    },

    // ===== UI: Notification =====
    showNotification(message) {
        // Remove existing
        document.querySelectorAll('.cart-notification').forEach(n => n.remove());

        const notif = document.createElement('div');
        notif.className = 'cart-notification';
        notif.innerHTML = `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
      <span>${this.escapeHTML(message)}</span>
    `;
        document.body.appendChild(notif);

        requestAnimationFrame(() => notif.classList.add('show'));
        setTimeout(() => {
            notif.classList.remove('show');
            setTimeout(() => notif.remove(), 300);
        }, 2500);
    },

    // ===== UI: Cart Drawer =====
    renderCartDrawer() {
        const drawer = document.getElementById('cartDrawer');
        if (!drawer) return;

        const items = this.getItems();
        const total = this.getTotal();
        const itemsContainer = drawer.querySelector('.cart-drawer-items');
        const totalEl = drawer.querySelector('.cart-drawer-total-amount');
        const emptyMsg = drawer.querySelector('.cart-drawer-empty');
        const footer = drawer.querySelector('.cart-drawer-footer');

        if (!itemsContainer) return;

        if (items.length === 0) {
            itemsContainer.innerHTML = '';
            if (emptyMsg) {
                emptyMsg.style.display = 'flex';
                emptyMsg.style.flexDirection = 'column';
                emptyMsg.style.alignItems = 'center';
                emptyMsg.style.justifyContent = 'center';
                emptyMsg.style.gap = '1rem';
                emptyMsg.style.padding = '3rem 1rem';
                emptyMsg.style.color = 'var(--text-muted)';
                emptyMsg.innerHTML = `
                  <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.5;">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                  </svg>
                  <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-secondary);">Tu carrito está vacío</span>
                  <p style="font-size: 0.9rem; text-align: center; max-width: 80%;">Explorá nuestro catálogo y sumá los productos que más te gusten.</p>
                  <a href="catalogo.html" class="btn btn-secondary btn-sm" style="margin-top: 1rem;" onclick="Cart.toggleDrawer(false)">Ver Catálogo</a>
                `;
            }
            if (footer) footer.style.display = 'none';
            return;
        }

        if (emptyMsg) emptyMsg.style.display = 'none';
        if (footer) footer.style.display = 'block';

        itemsContainer.innerHTML = items.map(item => `
      <div class="cart-item" data-key="${this.escapeAttr(this.itemKey(item))}">
        <div class="cart-item-image">
          <img src="${this.escapeAttr(item.image_url)}" alt="${this.escapeAttr(item.name)}">
        </div>
        <div class="cart-item-info">
          <h4 class="cart-item-name">${this.escapeHTML(item.name)}</h4>
          ${item.variant_label ? `<div class="cart-item-variant">${this.escapeHTML(item.variant_label)}</div>` : ''}
          <div class="cart-item-price">$${item.price.toLocaleString('es-AR')}</div>
          <div class="cart-item-qty">
            <button class="qty-btn qty-minus" data-key="${this.escapeAttr(this.itemKey(item))}">−</button>
            <span>${item.quantity}</span>
            <button class="qty-btn qty-plus" data-key="${this.escapeAttr(this.itemKey(item))}">+</button>
          </div>
        </div>
        <button class="cart-item-remove" data-key="${this.escapeAttr(this.itemKey(item))}" aria-label="Eliminar">✕</button>
      </div>
    `).join('');

        if (totalEl) totalEl.textContent = `$${total.toLocaleString('es-AR')}`;
        
        // Agregar mensaje aclaratorio del envío
        let shippingNotice = footer.querySelector('.cart-shipping-notice');
        if (!shippingNotice) {
            shippingNotice = document.createElement('p');
            shippingNotice.className = 'cart-shipping-notice';
            shippingNotice.style.fontSize = '0.8rem';
            shippingNotice.style.color = 'var(--text-muted)';
            shippingNotice.style.textAlign = 'center';
            shippingNotice.style.marginTop = '0.75rem';
            shippingNotice.style.marginBottom = '0';
            shippingNotice.textContent = 'El costo de envío se coordina por WhatsApp.';
            footer.appendChild(shippingNotice);
        }

        // Event listeners
        itemsContainer.querySelectorAll('.qty-minus').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.key;
                const item = this.getItems().find(i => this.itemKey(i) === key);
                if (item && item.quantity > 1) this.updateQuantity(key, item.quantity - 1);
                else this.removeItem(key);
            });
        });

        itemsContainer.querySelectorAll('.qty-plus').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.key;
                const item = this.getItems().find(i => this.itemKey(i) === key);
                if (item) this.updateQuantity(key, item.quantity + 1);
            });
        });

        itemsContainer.querySelectorAll('.cart-item-remove').forEach(btn => {
            btn.addEventListener('click', () => this.removeItem(btn.dataset.key));
        });
    },

    closeMobileMenu() {
        const menuToggle = document.getElementById('menuToggle');
        const nav = document.getElementById('nav');
        const navOverlay = document.getElementById('navOverlay');

        if (menuToggle) menuToggle.classList.remove('active');
        if (nav) nav.classList.remove('open');
        if (navOverlay) navOverlay.classList.remove('active');
        document.body.style.overflow = '';
    },

    toggleDrawer(forceOpen = null) {
        const drawer = document.getElementById('cartDrawer');
        const overlay = document.getElementById('cartOverlay');
        if (!drawer) return;

        const isOpen = drawer.classList.contains('open');
        const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !isOpen;

        if (shouldOpen) {
            this.closeMobileMenu();
        }

        drawer.classList.toggle('open', shouldOpen);
        if (overlay) overlay.classList.toggle('active', shouldOpen);
        document.body.style.overflow = shouldOpen ? 'hidden' : '';
    },

    // ===== Checkout =====
    async checkout() {
        const items = this.getItems();
        if (items.length === 0) {
            this.showNotification('El carrito está vacío');
            return;
        }

        // Show checkout modal
        await this.showCheckoutModal();
    },

    async showCheckoutModal() {
        // Remove existing
        document.querySelectorAll('.checkout-modal-overlay').forEach(m => m.remove());

        const overlay = document.createElement('div');
        overlay.className = 'checkout-modal-overlay';
        // Floating labels styling setup can be done dynamically or via CSS. We'll use CSS mostly, 
        // but here's the HTML payload structured for it
        overlay.innerHTML = `
      <div class="checkout-modal">
        <button class="checkout-modal-close" aria-label="Cerrar">✕</button>
        <h2>Finalizar Compra</h2>
        <p style="color: var(--text-secondary); margin-bottom: var(--space-xl);">Completá tus datos para continuar al pago.</p>
        <form id="checkoutForm">
          <div class="form-floating-group">
            <input type="text" class="form-input" id="checkoutName" required placeholder=" ">
            <label class="form-floating-label" for="checkoutName">Nombre completo *</label>
          </div>
          <div class="form-floating-group">
            <input type="email" class="form-input" id="checkoutEmail" required placeholder=" ">
            <label class="form-floating-label" for="checkoutEmail">Email *</label>
            <div class="email-validation-icon"></div>
          </div>
          <div class="form-floating-group">
            <input type="tel" class="form-input" id="checkoutPhone" required placeholder=" ">
            <label class="form-floating-label" for="checkoutPhone">Teléfono (WhatsApp activo) *</label>
          </div>
          <div class="form-group" style="margin-bottom: var(--space-lg);">
            <label style="display:block;font-size:0.85rem;color:var(--text-secondary);margin-bottom:0.5rem;font-weight:500;">Dirección de entrega</label>
            <p id="checkoutAccountHint" style="display:none;margin:0 0 0.75rem;font-size:0.82rem;color:var(--text-muted);line-height:1.5;"></p>
            <div id="checkoutSavedAddressGroup" hidden style="margin-bottom:0.75rem;">
              <label for="checkoutSavedAddress" style="display:block;font-size:0.8rem;color:var(--text-secondary);margin-bottom:0.35rem;">Direcciones guardadas</label>
              <select id="checkoutSavedAddress" class="form-select">
                <option value="">Ingresar otra dirección</option>
              </select>
            </div>
            <div class="form-floating-group">
              <input type="text" class="form-input" id="checkoutRecipient" placeholder=" ">
              <label class="form-floating-label" for="checkoutRecipient">Destinatario</label>
            </div>
            <div class="form-floating-group">
              <input type="text" class="form-input" id="checkoutStreet" placeholder=" ">
              <label class="form-floating-label" for="checkoutStreet">Calle y altura</label>
            </div>
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:var(--space-md);">
              <div class="form-floating-group">
                <input type="text" class="form-input" id="checkoutCity" placeholder=" ">
                <label class="form-floating-label" for="checkoutCity">Ciudad</label>
              </div>
              <div class="form-floating-group">
                <input type="text" class="form-input" id="checkoutProvince" placeholder=" ">
                <label class="form-floating-label" for="checkoutProvince">Provincia</label>
              </div>
            </div>
            <div class="form-floating-group" style="margin-bottom:0;">
              <input type="text" class="form-input" id="checkoutPostalCode" placeholder=" ">
              <label class="form-floating-label" for="checkoutPostalCode">Código postal</label>
            </div>
          </div>
          <div class="form-floating-group" style="margin-bottom: var(--space-xl);">
            <textarea class="form-input" id="checkoutNotes" placeholder=" " rows="2" style="resize: vertical; min-height: 50px;"></textarea>
            <label class="form-floating-label" for="checkoutNotes">Notas adicionales (opcional)</label>
          </div>
          <div class="form-group" style="margin-bottom: var(--space-xl);">
            <label style="display: block; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem; font-weight: 500;">Medio de Pago *</label>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
              <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.95rem;">
                <input type="radio" name="checkoutPayment" value="mercadopago" checked> MercadoPago
              </label>
              <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.95rem;">
                <input type="radio" name="checkoutPayment" value="transferencia"> Transferencia ${this.getTransferDiscountEligibleSubtotal() > 0 ? '<span style="background:#22c55e;color:#fff;font-size:0.68rem;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:2px;">10% OFF en productos seleccionados</span>' : ''}
              </label>
              <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.95rem;">
                <input type="radio" name="checkoutPayment" value="efectivo"> Efectivo ${this.getTransferDiscountEligibleSubtotal() > 0 ? '<span style="background:#22c55e;color:#fff;font-size:0.68rem;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:2px;">10% OFF en productos seleccionados</span>' : ''}
              </label>
            </div>
          </div>
          <div class="checkout-summary">
            <div class="checkout-summary-row">
              <span>Subtotal (${this.getCount()} items)</span>
              <strong>$${this.getTotal().toLocaleString('es-AR')}</strong>
            </div>
            <div class="checkout-summary-row" id="checkoutDiscountRow" style="margin-top: 0.5rem; display: none; color: #22c55e; font-weight: 600;">
              <span>Descuento 10% (productos seleccionados)</span>
              <span id="checkoutDiscountAmount"></span>
            </div>
            <div class="checkout-summary-row" style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
              <span>Envío</span>
              <span>A coordinar</span>
            </div>
            <div class="checkout-summary-row" style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border);">
              <span><strong>Total</strong></span>
              <strong id="checkoutTotal">$${this.getTotal().toLocaleString('es-AR')}</strong>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;" id="checkoutSubmitBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            Pagar con MercadoPago
          </button>
          <p style="text-align: center; font-size: 0.8rem; color: var(--text-muted); margin-top: var(--space-md); display: flex; align-items: center; justify-content: center; gap: 5px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            Pago 100% seguro a través de MercadoPago.
          </p>
        </form>
      </div>
    `;

        document.body.appendChild(overlay);
        requestAnimationFrame(() => overlay.classList.add('active'));
        await this.prefillCheckoutCustomerContext(overlay);

        // Handle payment method change
        const paymentRadios = document.querySelectorAll('input[name="checkoutPayment"]');
        const submitBtn = document.getElementById('checkoutSubmitBtn');
        const secureText = overlay.querySelector('p:last-of-type');
        const subtotal = this.getTotal();

        paymentRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                const isDescuento = ['transferencia', 'efectivo'].includes(e.target.value);
                const discountRow = document.getElementById('checkoutDiscountRow');
                const discountAmountEl = document.getElementById('checkoutDiscountAmount');
                const totalEl = document.getElementById('checkoutTotal');

                const descuento = this.getTransferDiscountAmount();
                if (isDescuento && descuento > 0) {
                    const totalFinal = Math.round((subtotal - descuento) * 100) / 100;
                    discountRow.style.display = '';
                    discountAmountEl.textContent = `-$${descuento.toLocaleString('es-AR')}`;
                    totalEl.textContent = `$${totalFinal.toLocaleString('es-AR')}`;
                } else {
                    discountRow.style.display = 'none';
                    totalEl.textContent = `$${subtotal.toLocaleString('es-AR')}`;
                }

                if (e.target.value === 'mercadopago') {
                    submitBtn.innerHTML = `
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Pagar con MercadoPago
                    `;
                    secureText.innerHTML = `
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Pago 100% seguro a través de MercadoPago.
                    `;
                } else {
                    submitBtn.textContent = 'Confirmar Pedido';
                    secureText.textContent = e.target.value === 'transferencia'
                        ? 'Al confirmar el pedido vas a ver los datos bancarios para transferir.'
                        : 'Abonás en efectivo al retirar o recibir el pedido.';
                }
            });
        });

        // Inline Validation for Email
        const emailInput = document.getElementById('checkoutEmail');
        emailInput.addEventListener('input', (e) => {
            const val = e.target.value;
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
            if (val.length > 0) {
                emailInput.style.borderColor = isValid ? '#25D366' : '#ff4757';
            } else {
                emailInput.style.borderColor = '';
            }
        });

        // Close
        overlay.querySelector('.checkout-modal-close').addEventListener('click', () => {
            overlay.classList.remove('active');
            setTimeout(() => overlay.remove(), 300);
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                setTimeout(() => overlay.remove(), 300);
            }
        });

        // Submit
        document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('checkoutSubmitBtn');
            btn.textContent = 'Procesando...';
            btn.disabled = true;

            try {
                const paymentMethod = document.querySelector('input[name="checkoutPayment"]:checked').value;
                const response = await fetch(`${this.getApiBase()}/create_preference.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        items: this.getItems().map(i => ({
                            id: i.product_id,
                            product_id: i.product_id,
                            variant_id: i.variant_id || null,
                            quantity: i.quantity
                        })),
                        customer: {
                            name: document.getElementById('checkoutName').value,
                            email: document.getElementById('checkoutEmail').value,
                            phone: document.getElementById('checkoutPhone').value,
                        },
                        shipping_address: this.buildCheckoutShippingPayload(),
                        notes: document.getElementById('checkoutNotes')?.value || '',
                        payment_method: paymentMethod,
                        idempotency_key: this.getCheckoutToken()
                    })
                });

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const raw = await response.text();
                    throw new Error(`Respuesta inválida del checkout (${response.status}): ${raw.slice(0, 120)}`);
                }

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Error creating order');
                }

                if (data.init_point) {
                    this.clear();
                    // sandbox_init_point solo existe con credenciales TEST
                    // En producción, init_point es la URL correcta
                    window.location.href = data.init_point;
                } else if (data.success_url) {
                    this.clear();
                    window.location.href = data.success_url;
                } else {
                    throw new Error(data.error || 'Error creating order');
                }
            } catch (err) {
                const checkedPayment = document.querySelector('input[name="checkoutPayment"]:checked').value;
                if (checkedPayment === 'mercadopago') {
                    btn.innerHTML = `
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Pagar con MercadoPago
                    `;
                } else {
                    btn.textContent = 'Confirmar Pedido';
                }
                btn.disabled = false;
                this.showNotification(err.message || 'Error al procesar. Intentá de nuevo.');
                console.error('Checkout error:', err);
            }
        });
    },

    // ===== HTML Escaping (anti-XSS) =====
    escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    },

    escapeAttr(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML.replace(/"/g, '&quot;');
    },

    // ===== Init =====
    init() {
        this.updateBadge();
        this.renderCartDrawer();

        // Cart toggle button
        document.querySelectorAll('.cart-toggle').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleDrawer();
            });
        });

        // Cart overlay close
        const overlay = document.getElementById('cartOverlay');
        if (overlay) {
            overlay.addEventListener('click', () => this.toggleDrawer());
        }

        // Checkout button
        document.querySelectorAll('.cart-checkout-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.toggleDrawer();
                this.checkout();
            });
        });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => Cart.init());
