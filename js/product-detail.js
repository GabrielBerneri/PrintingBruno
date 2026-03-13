/* ============================================
   PrintingBruno - Product Detail Page
   Loads a single product from API and renders full info
   ============================================ */

(function () {
  const root = document.getElementById('productDetailRoot');
  if (!root) return;

  const params = new URLSearchParams(window.location.search);
  const slug = params.get('slug');
  const id = params.get('id');

  function esc(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
  }

  function statusText(product) {
    const stock = Number(product.stock || 0);
    if (!product.active) return 'No disponible';
    if (stock <= 0) return 'Sin stock';
    if (stock <= 3) return `Últimas ${stock} unidad${stock === 1 ? '' : 'es'}`;
    return `Stock disponible (${stock})`;
  }

  function updateHead(product) {
    document.title = `${product.name} | PrintingBruno`;
    const pageTitle = document.getElementById('productPageTitle');
    const breadcrumbCurrent = document.getElementById('productBreadcrumbCurrent');

    if (pageTitle) {
      pageTitle.innerHTML = `${esc(product.name)} <span class="accent-text">· Detalle</span>`;
    }
    if (breadcrumbCurrent) breadcrumbCurrent.textContent = product.name;

    const desc = document.querySelector('meta[name="description"]');
    if (desc) desc.setAttribute('content', (product.description || product.name || '').slice(0, 155));
  }

  function renderProduct(product) {
    updateHead(product);

    const badgeHTML = product.badge
      ? `<span class="product-badge product-detail-badge ${esc(String(product.badge).toLowerCase())}">${esc(product.badge)}</span>`
      : '';

    root.className = 'product-detail-layout';
    root.innerHTML = `
      <div class="product-detail-media reveal visible">
        <div class="product-detail-image-wrap">
          ${badgeHTML}
          <img class="product-detail-image" src="${esc(product.image_url)}" alt="${esc(product.name)}" loading="eager">
        </div>
      </div>
      <div class="product-detail-content reveal visible">
        <span class="product-detail-category">${esc(Products.categoryLabel(product.category))}</span>
        <h1 class="product-detail-title">${esc(product.name)}</h1>
        <div class="product-detail-price">${Products.formatPrice(product.price)}</div>
        <div class="product-detail-description">${esc(product.description || 'Sin descripción por el momento.')}</div>
        <div class="product-detail-meta">
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Categoría</span>
            <span class="product-detail-meta-value">${esc(Products.categoryLabel(product.category))}</span>
          </div>
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Material</span>
            <span class="product-detail-meta-value">${esc(product.material || 'A definir')}</span>
          </div>
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Disponibilidad</span>
            <span class="product-detail-meta-value">${esc(statusText(product))}</span>
          </div>
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Código</span>
            <span class="product-detail-meta-value">#${esc(product.id)}</span>
          </div>
        </div>
        <div class="product-detail-actions">
          <button class="btn btn-primary btn-lg" id="detailAddToCart">Agregar al carrito</button>
          <a class="btn btn-secondary btn-lg" href="catalogo.html">Volver al catálogo</a>
        </div>
        <p class="product-detail-note">¿Necesitás otra variante, color o personalización? Escribinos y lo adaptamos a tu proyecto.</p>
      </div>
    `;

    const addBtn = document.getElementById('detailAddToCart');
    if (addBtn) {
      const stock = Number(product.stock || 0);
      if (!product.active || stock <= 0) {
        addBtn.disabled = true;
        addBtn.textContent = 'Sin stock';
      } else {
        addBtn.addEventListener('click', () => {
          Cart.addItem({
            id: product.id,
            name: product.name,
            price: product.price,
            image_url: product.image_url
          });
        });
      }
    }
  }

  async function loadRelatedProducts(currentProduct) {
    const section = document.getElementById('relatedProductsSection');
    const grid = document.getElementById('relatedProductsGrid');
    if (!section || !grid) return;

    try {
      const allProducts = await Products.fetchProducts();
      // Filter: same category, different product, active only
      let related = allProducts.filter(p =>
        p.category === currentProduct.category &&
        p.id !== currentProduct.id &&
        p.active
      );

      // If not enough from same category, fill with other featured products
      if (related.length < 2) {
        const others = allProducts.filter(p =>
          p.id !== currentProduct.id &&
          p.active &&
          !related.find(r => r.id === p.id)
        );
        // Shuffle others
        for (let i = others.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1));
          [others[i], others[j]] = [others[j], others[i]];
        }
        related = [...related, ...others].slice(0, 4);
      }

      // Shuffle and limit
      for (let i = related.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [related[i], related[j]] = [related[j], related[i]];
      }
      related = related.slice(0, 4);

      if (related.length === 0) return;

      const delays = ['reveal-delay-1', 'reveal-delay-2', 'reveal-delay-3', 'reveal-delay-1'];
      related.forEach((product, i) => {
        const card = Products.renderCard(product, { delay: delays[i % delays.length] });
        grid.appendChild(card);
      });

      section.style.display = '';
      Products.observeReveal(grid);
    } catch (err) {
      console.error('Error loading related products:', err);
    }
  }

  async function loadProduct() {
    if (!slug && !id) {
      root.innerHTML = `
        <h2 style="font-family:var(--font-heading);margin-bottom:var(--space-md)">Producto no encontrado</h2>
        <p style="color:var(--text-secondary);margin-bottom:var(--space-lg)">No recibimos un identificador válido para mostrar la ficha del producto.</p>
        <a href="catalogo.html" class="btn btn-primary">Ir al catálogo</a>
      `;
      return;
    }

    try {
      const url = new URL('api/products.php', window.location.href);
      if (slug) url.searchParams.set('slug', slug);
      if (!slug && id) url.searchParams.set('id', id);

      const response = await fetch(url);
      if (!response.ok) throw new Error('Producto no encontrado');
      const product = await response.json();
      renderProduct(product);
      loadRelatedProducts(product);
    } catch (error) {
      root.className = 'product-detail-empty';
      root.innerHTML = `
        <h2 style="font-family:var(--font-heading);margin-bottom:var(--space-md)">No pudimos cargar este producto</h2>
        <p style="color:var(--text-secondary);margin-bottom:var(--space-lg)">Puede que haya sido eliminado o que el enlace no sea válido.</p>
        <a href="catalogo.html" class="btn btn-primary">Volver al catálogo</a>
      `;
      console.error('Product detail error:', error);
    }
  }

  loadProduct();
})();
