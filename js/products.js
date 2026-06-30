/* ============================================
   PrintingBruno - Dynamic Product Loading
   Fetches products from API and renders cards
   ============================================ */

const Products = {
  API_URL: 'api/products.php',

  formatPrice(price) {
    return '$' + Math.round(price).toLocaleString('es-AR');
  },

  productUrl(product) {
    if (product?.slug) {
      return `producto.html?slug=${encodeURIComponent(product.slug)}`;
    }
    return `producto.html?id=${encodeURIComponent(product?.id ?? '')}`;
  },

  getActiveVariants(product) {
    if (!Array.isArray(product?.variants)) return [];
    return product.variants.filter(variant => Number(variant?.active ?? 1) === 1);
  },

  getDefaultVariant(product) {
    const variants = this.getActiveVariants(product);
    if (variants.length === 0) return null;
    const preferredId = Number(product?.default_variant_id || 0);
    return variants.find(variant => Number(variant.id) === preferredId) || variants[0];
  },

  resolveVariantLabel(variant) {
    if (!variant) return '';
    return String(
      variant.label ||
      variant.primary_color_name ||
      variant.primary_color ||
      variant.secondary_color_name ||
      variant.secondary_color ||
      'Base'
    ).trim();
  },

  resolveDisplayPrice(product, variant = null) {
    if (variant && variant.final_price != null) {
      return Number(variant.final_price || 0);
    }
    if (product?.price_from != null) {
      return Number(product.price_from || 0);
    }
    return Number(product?.price || 0);
  },

  resolveImageUrls(product, variant = null) {
    const variantImages = Array.isArray(variant?.image_urls) && variant.image_urls.length > 0
      ? variant.image_urls
      : [];
    if (variantImages.length > 0) {
      return variantImages;
    }
    return Array.isArray(product?.image_urls) && product.image_urls.length > 0
      ? product.image_urls
      : (product?.image_url ? [product.image_url] : []);
  },

  colorHex(colorName) {
    const colors = {
      rojo: '#d83b3b',
      blanco: '#f4f4f1',
      negro: '#1a1a1a',
      gris: '#8c8f96',
      azul: '#2f63d8',
      verde: '#2f9d63',
      dorado: '#c9a227',
      celeste: '#6bbef0',
      rosa: '#ef7ca8',
    };
    return colors[String(colorName || '').trim().toLowerCase()] || '#ffffff';
  },

  renderVariantSwatches(variants) {
    if (!Array.isArray(variants) || variants.length === 0) return '';
    const visible = variants.slice(0, 4);
    return `
      <div class="product-card-variants" aria-label="Variantes disponibles">
        ${visible.map(variant => {
          const primary = variant.primary_color_hex || this.colorHex(variant.primary_color_name || variant.primary_color || variant.label);
          const secondary = variant.secondary_color_hex || (variant.secondary_color_name || variant.secondary_color ? this.colorHex(variant.secondary_color_name || variant.secondary_color) : null);
          const background = secondary
            ? `linear-gradient(135deg, ${primary} 0%, ${primary} 52%, ${secondary} 52%, ${secondary} 100%)`
            : primary;
          return `<span class="product-card-variant-dot" title="${this.escapeAttr(this.resolveVariantLabel(variant))}" style="background:${background}"></span>`;
        }).join('')}
        ${variants.length > visible.length ? `<span class="product-card-variant-more">+${variants.length - visible.length}</span>` : ''}
      </div>
    `;
  },

  renderCard(product, options = {}) {
    const delay = options.delay || '';
    const variants = this.getActiveVariants(product);
    const defaultVariant = this.getDefaultVariant(product);
    const hasMultipleVariants = variants.length > 1;
    const stock = Number(product?.stock || 0);
    const isAvailable = stock > 0;
    const imageUrls = this.resolveImageUrls(product, defaultVariant);
    const primaryImage = imageUrls[0] || '';
    const enableGallery = Boolean(options.enableGallery) && imageUrls.length > 1;
    const secondaryImage = !enableGallery ? (imageUrls[1] || '') : '';
    const displayPrice = this.resolveDisplayPrice(product, defaultVariant);
    const priceText = hasMultipleVariants && Number(product?.price_from || 0) !== Number(product?.price_to || 0)
      ? `Desde ${this.formatPrice(displayPrice)}`
      : this.formatPrice(displayPrice);
    const dotsHTML = enableGallery
      ? `<div class="product-image-dots" aria-hidden="true">${imageUrls.map((_, idx) =>
          `<button class="product-image-dot${idx === 0 ? ' active' : ''}" type="button" data-index="${idx}" aria-label="Imagen ${idx + 1}"></button>`
        ).join('')}</div>`
      : '';
    const navHTML = enableGallery
      ? `
        <button class="product-image-nav prev" type="button" aria-label="Imagen anterior">‹</button>
        <button class="product-image-nav next" type="button" aria-label="Siguiente imagen">›</button>
      `
      : '';

    const stockBadge = stock > 0 && stock <= 3
      ? `<span class="product-badge urgency">Ultimas ${stock}</span>`
      : '';
    const customBadge = product.badge
      ? `<span class="product-badge ${this.badgeClass(product.badge)}">${this.escapeHTML(product.badgeLabel || product.badge)}</span>`
      : '';
    const transferDiscountPct = Number(product.transfer_discount || 0);
    const transferDiscountBadge = transferDiscountPct > 0
      ? `<span class="product-badge transfer-discount">${transferDiscountPct}% OFF transferencia/efectivo</span>`
      : '';

    const div = document.createElement('div');
    div.className = `product-card reveal${secondaryImage ? ' has-secondary-image' : ''}${enableGallery ? ' has-gallery' : ''}${delay ? ' ' + delay : ''}`;
    div.setAttribute('data-category', product.category);
    div.setAttribute('data-price', product.price);

    div.innerHTML = `
      <a class="product-link product-image" href="${this.escapeAttr(this.productUrl(product))}" aria-label="Ver detalle de ${this.escapeAttr(product.name)}">
        ${customBadge}
        ${stockBadge}
        <img class="product-card-main-image${enableGallery ? ' product-card-gallery-image' : ''}" src="${this.escapeAttr(primaryImage)}" alt="${this.escapeAttr(product.name)}" loading="lazy">
        ${secondaryImage ? `<img class="product-card-secondary-image" src="${this.escapeAttr(secondaryImage)}" alt="${this.escapeAttr(product.name)} vista 2" loading="lazy" aria-hidden="true">` : ''}
        ${navHTML}
        ${dotsHTML}
      </a>
      <div class="product-info">
        <span class="product-category">${this.escapeHTML(this.categoryLabel(product.category))}</span>
        <h3 class="product-name">
          <a class="product-link" href="${this.escapeAttr(this.productUrl(product))}">${this.escapeHTML(product.name)}</a>
        </h3>
        <p class="product-description">${this.escapeHTML(product.description)}</p>
        <div class="product-card-extra">
          ${hasMultipleVariants ? this.renderVariantSwatches(variants) : ''}
          ${!isAvailable ? '<div class="product-stock-status out-of-stock">Sin stock disponible</div>' : ''}
          ${transferDiscountBadge}
        </div>
        <div class="product-footer${hasMultipleVariants ? ' product-footer-variant' : ''}">
          <span class="product-price">${priceText}</span>
          <div class="product-action">
            <a class="btn btn-secondary btn-sm btn-detail" href="${this.escapeAttr(this.productUrl(product))}">Ver detalle</a>
            <button class="btn btn-primary btn-sm btn-add-cart" type="button" aria-label="${this.escapeAttr(hasMultipleVariants ? `Elegir variante de ${product.name}` : `Agregar ${product.name} al carrito`)}" title="${this.escapeAttr(hasMultipleVariants ? 'Elegir variante' : 'Agregar al carrito')}" ${isAvailable ? '' : 'disabled'}>${isAvailable ? '+' : '—'}</button>
          </div>
        </div>
      </div>
    `;

    const btnAdd = div.querySelector('.btn-add-cart');
    if (!isAvailable) {
      btnAdd.classList.add('disabled');
    }

    btnAdd.addEventListener('click', () => {
      if (!isAvailable) return;
      if (hasMultipleVariants) {
        window.location.href = this.productUrl(product);
        return;
      }

      const chosenVariant = defaultVariant;
      const chosenLabel = this.resolveVariantLabel(chosenVariant);
      const chosenImageUrls = this.resolveImageUrls(product, chosenVariant);
      Cart.addItem({
        id: product.id,
        product_id: product.id,
        variant_id: chosenVariant?.id || null,
        variant_label: chosenVariant && chosenLabel !== 'Base' ? chosenLabel : '',
        cart_key: chosenVariant?.id ? `v:${chosenVariant.id}` : `p:${product.id}`,
        name: product.name,
        price: this.resolveDisplayPrice(product, chosenVariant),
        image_url: chosenImageUrls[0] || product.image_url,
        transfer_discount: Number(product.transfer_discount || 0)
      });

      // Feedback visual del botón
      const originalText = btnAdd.innerHTML;
      btnAdd.innerHTML = '✓';
      btnAdd.style.backgroundColor = '#25D366';
      btnAdd.style.color = '#fff';
      
      setTimeout(() => {
        btnAdd.innerHTML = originalText;
        btnAdd.style.backgroundColor = '';
        btnAdd.style.color = '';
      }, 1500);
    });

    if (enableGallery) {
      const imageLink = div.querySelector('.product-image');
      const mainImg = div.querySelector('.product-card-main-image');
      const prevBtn = div.querySelector('.product-image-nav.prev');
      const nextBtn = div.querySelector('.product-image-nav.next');
      const dotButtons = Array.from(div.querySelectorAll('.product-image-dot'));

      let currentIndex = 0;
      let pointerStartX = 0;
      let pointerActive = false;
      let suppressClick = false;

      const setImage = (index) => {
        const total = imageUrls.length;
        if (!total || !mainImg) return;
        const normalized = ((index % total) + total) % total;
        currentIndex = normalized;
        mainImg.src = imageUrls[currentIndex];
        mainImg.alt = `${product.name} - imagen ${currentIndex + 1}`;
        dotButtons.forEach((dot, idx) => dot.classList.toggle('active', idx === currentIndex));
      };

      prevBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        setImage(currentIndex - 1);
      });

      nextBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        setImage(currentIndex + 1);
      });

      dotButtons.forEach((dot) => {
        dot.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const targetIndex = parseInt(dot.getAttribute('data-index') || '0', 10);
          setImage(targetIndex);
        });
      });

      imageLink?.addEventListener('pointerdown', (e) => {
        pointerStartX = e.clientX;
        pointerActive = true;
      });

      imageLink?.addEventListener('pointerup', (e) => {
        if (!pointerActive) return;
        const deltaX = e.clientX - pointerStartX;
        const threshold = 35;
        if (Math.abs(deltaX) >= threshold) {
          suppressClick = true;
          if (deltaX < 0) setImage(currentIndex + 1);
          else setImage(currentIndex - 1);
          window.setTimeout(() => { suppressClick = false; }, 120);
        }
        pointerActive = false;
      });

      imageLink?.addEventListener('pointercancel', () => {
        pointerActive = false;
      });

      imageLink?.addEventListener('click', (e) => {
        if (suppressClick) {
          e.preventDefault();
          e.stopPropagation();
          return;
        }
      });
    }

    return div;
  },

  badgeClass(badge) {
    const normalized = String(badge || '').trim().toLowerCase();
    if (normalized.includes('nuevo')) return 'new';
    if (normalized.includes('popular') || normalized.includes('vendido')) return 'popular';
    return 'sale';
  },

  categoryLabel(category) {
    const labels = {
      figuras: 'Figuras',
      decoracion: 'Decoración',
      funcional: 'Funcional',
      personalizado: 'Personalizado',
      mates: 'Mates',
      filamentos: 'Filamentos',
      jarras: 'Jarras',
      insumos: 'Insumos',
      impresoras: 'Impresoras',
      llaveros: 'Llaveros'
    };
    return labels[category] || category;
  },

  renderCatalogFilters(products = []) {
    const group = document.querySelector('.catalog-sidebar .filter-group');
    if (!group) return;

    const categories = [...new Set(
      products
        .map(p => p.category)
        .filter(Boolean)
    )].sort((a, b) => this.categoryLabel(a).localeCompare(this.categoryLabel(b), 'es'));

    const optionsHTML = [
      `<label class="filter-option active" data-filter="all">
        <input type="radio" name="category" value="all" checked> Todos los productos
      </label>`,
      ...categories.map(category => `
      <label class="filter-option" data-filter="${this.escapeAttr(category)}">
        <input type="radio" name="category" value="${this.escapeAttr(category)}"> ${this.escapeHTML(this.categoryLabel(category))}
      </label>`)
    ].join('');

    group.querySelectorAll('.filter-option').forEach(el => el.remove());
    group.insertAdjacentHTML('beforeend', optionsHTML);
  },

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

  observeReveal(container) {
    const reveals = container.querySelectorAll('.reveal');
    if (reveals.length === 0) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    reveals.forEach(el => observer.observe(el));
  },

  async fetchProducts(params = {}) {
    const url = new URL(this.API_URL, window.location.href);
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));

    const response = await fetch(url, { cache: 'no-store' });
    if (!response.ok) throw new Error('Error al cargar productos');
    const data = await response.json();
    return data.products || [];
  },

  async loadFeatured(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    // Mostrar 4 tarjetas Skeleton mientras carga
    container.innerHTML = Array(4).fill().map(() => `
      <div class="product-card">
        <div class="skeleton-img skeleton"></div>
        <div class="product-info">
          <div class="skeleton-text short skeleton"></div>
          <div class="skeleton-text title skeleton"></div>
          <div class="skeleton-text medium skeleton"></div>
          <div class="product-footer" style="border: none;">
            <div class="skeleton-text short skeleton" style="margin: 0;"></div>
          </div>
        </div>
      </div>
    `).join('');

    try {
      const products = await this.fetchProducts({ featured: '1', in_stock: '1' });
      container.innerHTML = '';

      const delays = ['reveal-delay-1', 'reveal-delay-2', 'reveal-delay-3', 'reveal-delay-1'];
      if (products.length === 0) {
        container.innerHTML = this.emptyStateHTML('Todavia no hay destacados disponibles.', 'Explora el catalogo completo para ver todos los productos.');
        return;
      }
      products.forEach((product, i) => {
        const card = this.renderCard(product, { delay: delays[i % delays.length], enableGallery: false });
        container.appendChild(card);
      });

      this.observeReveal(container);
    } catch (err) {
      console.error('Error loading featured products:', err);
      container.innerHTML = '<p style="text-align:center;grid-column:1/-1;">Error al cargar productos. Intentá de nuevo más tarde.</p>';
    }
  },

  async loadCatalog(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    // Mostrar 6 tarjetas Skeleton mientras carga
    container.innerHTML = Array(6).fill().map(() => `
      <div class="product-card">
        <div class="skeleton-img skeleton"></div>
        <div class="product-info">
          <div class="skeleton-text short skeleton"></div>
          <div class="skeleton-text title skeleton"></div>
          <div class="skeleton-text medium skeleton"></div>
          <div class="product-footer" style="border: none;">
            <div class="skeleton-text short skeleton" style="margin: 0;"></div>
          </div>
        </div>
      </div>
    `).join('');

    try {
      const products = await this.fetchProducts({});
      this.renderCatalogFilters(products);
      container.innerHTML = '';

      if (products.length === 0) {
        container.innerHTML = this.emptyStateHTML('No encontramos productos publicados.', 'Probá nuevamente en unos minutos o escribinos para consultar stock.');
        return;
      }

      products.forEach(product => {
        const card = this.renderCard(product, { enableGallery: true });
        container.appendChild(card);
      });

      this.observeReveal(container);

      // Update product count
      const productCount = document.getElementById('productCount');
      if (productCount) {
        productCount.textContent = `Mostrando ${products.length} producto${products.length !== 1 ? 's' : ''}`;
      }

      // Notify that catalog is loaded (for hash-based filtering)
      document.dispatchEvent(new CustomEvent('catalogLoaded'));
    } catch (err) {
      console.error('Error loading catalog:', err);
      container.innerHTML = this.emptyStateHTML('No pudimos cargar el catalogo.', 'Intenta nuevamente en unos minutos.');
    }
  },

  emptyStateHTML(title, description) {
    return `
      <div class="catalog-empty-state" style="grid-column:1/-1;">
        <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
        <h3>${this.escapeHTML(title)}</h3>
        <p>${this.escapeHTML(description)}</p>
        <a class="btn btn-primary" href="catalogo.html">Ver catalogo completo</a>
      </div>
    `;
  }
};
