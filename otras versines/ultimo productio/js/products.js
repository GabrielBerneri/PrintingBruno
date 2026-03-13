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

  renderCard(product, options = {}) {
    const delay = options.delay || '';

    const div = document.createElement('div');
    div.className = `product-card reveal${delay ? ' ' + delay : ''}`;
    div.setAttribute('data-category', product.category);
    div.setAttribute('data-price', product.price);

    div.innerHTML = `
      <a class="product-link product-image" href="${this.escapeAttr(this.productUrl(product))}" aria-label="Ver detalle de ${this.escapeAttr(product.name)}">
        <img src="${this.escapeAttr(product.image_url)}" alt="${this.escapeAttr(product.name)}" loading="lazy">
      </a>
      <div class="product-info">
        <span class="product-category">${this.escapeHTML(this.categoryLabel(product.category))}</span>
        <h3 class="product-name">
          <a class="product-link" href="${this.escapeAttr(this.productUrl(product))}">${this.escapeHTML(product.name)}</a>
        </h3>
        <p class="product-description">${this.escapeHTML(product.description)}</p>
        <div class="product-footer">
          <span class="product-price">${this.formatPrice(product.price)}</span>
          <div class="product-action">
            <a class="btn btn-secondary btn-sm btn-detail" href="${this.escapeAttr(this.productUrl(product))}">Ver detalle</a>
            <button class="btn btn-primary btn-sm btn-add-cart" type="button" aria-label="Agregar ${this.escapeAttr(product.name)} al carrito" title="Agregar al carrito">+</button>
          </div>
        </div>
      </div>
    `;

    const btnAdd = div.querySelector('.btn-add-cart');
    btnAdd.addEventListener('click', () => {
      Cart.addItem({
        id: product.id,
        name: product.name,
        price: product.price,
        image_url: product.image_url
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

    return div;
  },

  categoryLabel(category) {
    const labels = {
      figuras: 'Figuras',
      decoracion: 'Decoración',
      funcional: 'Funcional',
      personalizado: 'Personalizado',
      mates: 'Mates',
      filamentos: 'Filamentos'
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

    const response = await fetch(url);
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
      const products = await this.fetchProducts({ featured: '1' });
      container.innerHTML = '';

      const delays = ['reveal-delay-1', 'reveal-delay-2', 'reveal-delay-3', 'reveal-delay-1'];
      products.forEach((product, i) => {
        const card = this.renderCard(product, { delay: delays[i % delays.length] });
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
      const products = await this.fetchProducts();
      this.renderCatalogFilters(products);
      container.innerHTML = '';

      products.forEach(product => {
        const card = this.renderCard(product);
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
      container.innerHTML = '<p style="text-align:center;grid-column:1/-1;">Error al cargar productos. Intentá de nuevo más tarde.</p>';
    }
  }
};
