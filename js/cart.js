/* ============================================
   PrintingBruno - Shopping Cart
   localStorage cart + MercadoPago checkout
   ============================================ */

const Cart = {
    STORAGE_KEY: 'pb_cart',
    getApiBase() {
        const currentPath = window.location.pathname || '/';
        if (currentPath.includes('/printingbruno/')) {
            return `${window.location.origin}/printingbruno/api`;
        }
        return `${window.location.origin}/api`;
    },

    // ===== Cart Data Management =====
    getItems() {
        try {
            return JSON.parse(localStorage.getItem(this.STORAGE_KEY)) || [];
        } catch { return []; }
    },

    saveItems(items) {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(items));
        this.updateBadge();
        this.renderCartDrawer();
    },

    addItem(product, quantity = 1) {
        const items = this.getItems();
        const existing = items.find(i => i.id === product.id);

        if (existing) {
            existing.quantity += quantity;
        } else {
            items.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image_url: product.image_url,
                quantity: quantity,
            });
        }

        this.saveItems(items);
        this.showNotification(`${product.name} agregado al carrito`);
    },

    removeItem(productId) {
        const items = this.getItems().filter(i => i.id !== productId);
        this.saveItems(items);
    },

    updateQuantity(productId, quantity) {
        const items = this.getItems();
        const item = items.find(i => i.id === productId);
        if (item) {
            item.quantity = Math.max(1, quantity);
            this.saveItems(items);
        }
    },

    clear() {
        localStorage.removeItem(this.STORAGE_KEY);
        this.updateBadge();
        this.renderCartDrawer();
    },

    getTotal() {
        return this.getItems().reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },

    getCount() {
        return this.getItems().reduce((sum, item) => sum + item.quantity, 0);
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
      <div class="cart-item" data-id="${item.id}">
        <div class="cart-item-image">
          <img src="${this.escapeAttr(item.image_url)}" alt="${this.escapeAttr(item.name)}">
        </div>
        <div class="cart-item-info">
          <h4 class="cart-item-name">${this.escapeHTML(item.name)}</h4>
          <div class="cart-item-price">$${item.price.toLocaleString('es-AR')}</div>
          <div class="cart-item-qty">
            <button class="qty-btn qty-minus" data-id="${item.id}">−</button>
            <span>${item.quantity}</span>
            <button class="qty-btn qty-plus" data-id="${item.id}">+</button>
          </div>
        </div>
        <button class="cart-item-remove" data-id="${item.id}" aria-label="Eliminar">✕</button>
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
                const id = parseInt(btn.dataset.id);
                const item = this.getItems().find(i => i.id === id);
                if (item && item.quantity > 1) this.updateQuantity(id, item.quantity - 1);
                else this.removeItem(id);
            });
        });

        itemsContainer.querySelectorAll('.qty-plus').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                const item = this.getItems().find(i => i.id === id);
                if (item) this.updateQuantity(id, item.quantity + 1);
            });
        });

        itemsContainer.querySelectorAll('.cart-item-remove').forEach(btn => {
            btn.addEventListener('click', () => this.removeItem(parseInt(btn.dataset.id)));
        });
    },

    toggleDrawer() {
        const drawer = document.getElementById('cartDrawer');
        const overlay = document.getElementById('cartOverlay');
        if (!drawer) return;

        const isOpen = drawer.classList.contains('open');
        drawer.classList.toggle('open');
        if (overlay) overlay.classList.toggle('active');
        document.body.style.overflow = isOpen ? '' : 'hidden';
    },

    // ===== Checkout =====
    async checkout() {
        const items = this.getItems();
        if (items.length === 0) {
            this.showNotification('El carrito está vacío');
            return;
        }

        // Show checkout modal
        this.showCheckoutModal();
    },

    showCheckoutModal() {
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
          <div class="form-floating-group" style="margin-bottom: var(--space-xl);">
            <textarea class="form-input" id="checkoutNotes" placeholder=" " rows="2" style="resize: vertical; min-height: 50px;"></textarea>
            <label class="form-floating-label" for="checkoutNotes">Notas adicionales (opcional)</label>
          </div>
          <div class="checkout-summary">
            <div class="checkout-summary-row">
              <span>Subtotal (${this.getCount()} items)</span>
              <strong>$${this.getTotal().toLocaleString('es-AR')}</strong>
            </div>
            <div class="checkout-summary-row" style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
              <span>Envío</span>
              <span>A coordinar</span>
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
                const response = await fetch(`${this.getApiBase()}/create_preference.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        items: this.getItems().map(i => ({ id: i.id, quantity: i.quantity })),
                        customer: {
                            name: document.getElementById('checkoutName').value,
                            email: document.getElementById('checkoutEmail').value,
                            phone: document.getElementById('checkoutPhone').value,
                        },
                        notes: document.getElementById('checkoutNotes')?.value || ''
                    })
                });

                const contentType = response.headers.get('content-type') || '';
                if (!response.ok || !contentType.includes('application/json')) {
                    const raw = await response.text();
                    throw new Error(`Respuesta inválida del checkout (${response.status}): ${raw.slice(0, 120)}`);
                }

                const data = await response.json();

                if (data.init_point) {
                    this.clear();
                    // sandbox_init_point solo existe con credenciales TEST
                    // En producción, init_point es la URL correcta
                    window.location.href = data.init_point;
                } else {
                    throw new Error(data.error || 'Error creating payment');
                }
            } catch (err) {
                btn.textContent = 'Pagar con MercadoPago';
                btn.disabled = false;
                this.showNotification('Error al procesar. Intentá de nuevo.');
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
