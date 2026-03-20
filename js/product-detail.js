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

  function isPublishedProduct(product) {
    return product?.active !== 0 && product?.active !== false && product?.active !== '0';
  }

  function getActiveVariants(product) {
    if (!Array.isArray(product?.variants)) return [];
    return product.variants.filter(variant => Number(variant?.active ?? 1) === 1);
  }

  function getVariantLabel(variant) {
    if (!variant) return '';
    if (window.Products?.resolveVariantLabel) {
      return Products.resolveVariantLabel(variant);
    }
    return String(variant.label || variant.primary_color || variant.secondary_color || 'Base').trim();
  }

  function isDefaultVariantLabel(label) {
    return ['base', 'única', 'unica', ''].includes(String(label || '').trim().toLowerCase());
  }

  function resolveSelectedVariant(product, selectedVariantId) {
    const variants = getActiveVariants(product);
    if (variants.length === 0) return null;

    const requestedId = Number(selectedVariantId || 0);
    const defaultId = Number(product?.default_variant_id || 0);
    return variants.find(variant => Number(variant.id) === requestedId)
      || variants.find(variant => Number(variant.id) === defaultId)
      || variants[0];
  }

  function getDisplayImageUrls(product, variant) {
    if (Array.isArray(variant?.image_urls) && variant.image_urls.length > 0) {
      return variant.image_urls;
    }
    return Array.isArray(product?.image_urls) && product.image_urls.length > 0
      ? product.image_urls
      : (product?.image_url ? [product.image_url] : []);
  }

  function getDisplayPrice(product, variant) {
    if (variant && variant.final_price != null) {
      return Number(variant.final_price || 0);
    }
    if (product?.price_from != null) {
      return Number(product.price_from || 0);
    }
    return Number(product?.price || 0);
  }

  function statusTextForStock(stock, published) {
    if (!published) return 'No disponible';
    if (stock <= 0) return 'Sin stock';
    if (stock <= 3) return `Últimas ${stock} unidad${stock === 1 ? '' : 'es'}`;
    return `Stock disponible (${stock})`;
  }

  function colorHex(colorName) {
    if (window.Products?.colorHex) {
      return Products.colorHex(colorName);
    }
    return '#ffffff';
  }

  function variantChipBackground(variant) {
    const primary = colorHex(variant?.primary_color || variant?.label);
    if (variant?.secondary_color) {
      const secondary = colorHex(variant.secondary_color);
      return `linear-gradient(135deg, ${primary} 0%, ${primary} 52%, ${secondary} 52%, ${secondary} 100%)`;
    }
    return primary;
  }

  function absoluteImageUrl(path) {
    if (!path) return '';
    return new URL(path, window.location.href).toString();
  }

  function setMetaProperty(name, content) {
    if (!content) return;
    const attr = name.startsWith('og:') ? 'property' : 'name';
    let tag = document.head.querySelector(`meta[${attr}="${name}"]`);
    if (!tag) {
      tag = document.createElement('meta');
      tag.setAttribute(attr, name);
      document.head.appendChild(tag);
    }
    tag.setAttribute('content', content);
  }

  function injectProductSchema(product, variant, stock) {
    const schema = {
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: product.name,
      description: product.description || '',
      image: getDisplayImageUrls(product, variant).filter(Boolean).map(absoluteImageUrl),
      sku: variant?.sku ? String(variant.sku) : String(product.id),
      brand: {
        '@type': 'Brand',
        name: 'PrintingBruno'
      },
      offers: {
        '@type': 'Offer',
        priceCurrency: 'ARS',
        price: getDisplayPrice(product, variant).toFixed(2),
        availability: stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        url: window.location.href
      }
    };

    let script = document.getElementById('productSchemaJsonLd');
    if (!script) {
      script = document.createElement('script');
      script.type = 'application/ld+json';
      script.id = 'productSchemaJsonLd';
      document.head.appendChild(script);
    }
    script.textContent = JSON.stringify(schema);
  }

  function updateHead(product, variant, stock) {
    document.title = `${product.name} | PrintingBruno`;
    const pageTitle = document.getElementById('productPageTitle');
    const breadcrumbCurrent = document.getElementById('productBreadcrumbCurrent');

    if (pageTitle) {
      pageTitle.innerHTML = `${esc(product.name)} <span class="accent-text">· Detalle</span>`;
    }
    if (breadcrumbCurrent) breadcrumbCurrent.textContent = product.name;

    const desc = document.querySelector('meta[name="description"]');
    if (desc) desc.setAttribute('content', (product.description || product.name || '').slice(0, 155));

    const primaryImage = getDisplayImageUrls(product, variant)[0] || product.image_url || '';
    setMetaProperty('og:title', `${product.name} | PrintingBruno`);
    setMetaProperty('og:description', (product.description || product.name || '').slice(0, 155));
    setMetaProperty('og:image', absoluteImageUrl(primaryImage));
    setMetaProperty('og:type', 'product');
    setMetaProperty('twitter:card', 'summary_large_image');
    setMetaProperty('twitter:title', `${product.name} | PrintingBruno`);
    setMetaProperty('twitter:description', (product.description || product.name || '').slice(0, 155));
    setMetaProperty('twitter:image', absoluteImageUrl(primaryImage));
    injectProductSchema(product, variant, stock);
  }

  function renderVariantSelector(product, selectedVariant) {
    const variants = getActiveVariants(product);
    if (variants.length === 0) return '';

    const showSingle = variants.length === 1 && !isDefaultVariantLabel(getVariantLabel(variants[0]));
    if (variants.length === 1 && !showSingle) return '';

    return `
      <div class="product-variant-picker">
        <div class="product-variant-picker-head">
          <span class="product-variant-picker-label">Color</span>
          <strong>${esc(getVariantLabel(selectedVariant))}</strong>
        </div>
        <div class="product-variant-options">
          ${variants.map(variant => `
            <button
              type="button"
              class="product-variant-chip${selectedVariant && Number(selectedVariant.id) === Number(variant.id) ? ' active' : ''}"
              data-variant-id="${esc(variant.id)}"
            >
              <span class="product-variant-chip-swatch" style="background:${variantChipBackground(variant)}"></span>
              <span>${esc(getVariantLabel(variant))}</span>
            </button>
          `).join('')}
        </div>
      </div>
    `;
  }

  function setupGallery(rootNode, imageUrls) {
    const mainImg = document.getElementById('productDetailMainImage');
    const imageWrap = rootNode.querySelector('.product-detail-image-wrap');
    const thumbButtons = rootNode.querySelectorAll('.product-detail-thumb');
    const prevBtn = document.getElementById('productDetailPrev');
    const nextBtn = document.getElementById('productDetailNext');
    const counter = document.getElementById('productDetailCounter');
    const zoomLens = document.getElementById('productDetailZoomLens');

    if (!mainImg || imageUrls.length === 0) return;

    let currentImageIndex = 0;

    function goToImage(index) {
      if (imageUrls.length === 0) return;
      const total = imageUrls.length;
      const normalizedIndex = ((index % total) + total) % total;
      const newSrc = imageUrls[normalizedIndex];
      if (!newSrc) return;

      currentImageIndex = normalizedIndex;
      mainImg.src = newSrc;

      thumbButtons.forEach((btn, idx) => {
        btn.classList.toggle('active', idx === currentImageIndex);
      });

      if (counter) {
        counter.textContent = `${currentImageIndex + 1} / ${total}`;
      }
    }

    thumbButtons.forEach((btn, idx) => {
      btn.addEventListener('click', () => goToImage(idx));
    });

    if (prevBtn) prevBtn.addEventListener('click', () => goToImage(currentImageIndex - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => goToImage(currentImageIndex + 1));

    if (imageUrls.length > 1 && imageWrap) {
      let touchStartX = 0;
      let isPointerDown = false;

      imageWrap.addEventListener('pointerdown', (e) => {
        touchStartX = e.clientX;
        isPointerDown = true;
      });

      imageWrap.addEventListener('pointerup', (e) => {
        if (!isPointerDown) return;
        const deltaX = e.clientX - touchStartX;
        const threshold = 40;
        if (Math.abs(deltaX) >= threshold) {
          if (deltaX < 0) goToImage(currentImageIndex + 1);
          else goToImage(currentImageIndex - 1);
        }
        isPointerDown = false;
      });

      imageWrap.addEventListener('pointercancel', () => {
        isPointerDown = false;
      });

      document.addEventListener('keydown', (e) => {
        if (!document.body.contains(rootNode)) return;
        if (e.key === 'ArrowLeft') goToImage(currentImageIndex - 1);
        if (e.key === 'ArrowRight') goToImage(currentImageIndex + 1);
      });
    }

  }

  function renderProduct(product, selectedVariantId = null) {
    const selectedVariant = resolveSelectedVariant(product, selectedVariantId);
    const imageUrls = getDisplayImageUrls(product, selectedVariant);
    const stock = selectedVariant
      ? Number(selectedVariant.available_stock ?? selectedVariant.stock ?? 0)
      : Number(product.stock || 0);
    const isAvailable = stock > 0 && isPublishedProduct(product);
    const price = getDisplayPrice(product, selectedVariant);
    const badgeHTML = product.badge
      ? `<span class="product-badge product-detail-badge ${esc(String(product.badge).toLowerCase())}">${esc(product.badge)}</span>`
      : '';
    const hasMultipleImages = imageUrls.length > 1;
    const primaryImage = imageUrls[0] || '';
    const selectedVariantLabel = getVariantLabel(selectedVariant);
    const shouldShowVariantMeta = selectedVariant && !isDefaultVariantLabel(selectedVariantLabel);
    const priceNote = selectedVariant && selectedVariant.price != null
      ? '<div class="product-detail-price-note">Esta variante tiene precio propio.</div>'
      : '';
    const stickyBarHTML = isAvailable
      ? `
        <div class="product-mobile-bar" id="productMobileBar">
          <div class="product-mobile-bar-price">${Products.formatPrice(price)}</div>
          <button class="btn btn-primary" id="detailAddToCartSticky">Agregar al carrito</button>
        </div>
      `
      : '';

    updateHead(product, selectedVariant, stock);

    root.className = 'product-detail-layout';
    root.innerHTML = `
      <div class="product-detail-media reveal visible">
        <div class="product-detail-image-wrap">
          ${badgeHTML}
          ${hasMultipleImages ? '<button type="button" class="product-detail-nav prev" id="productDetailPrev" aria-label="Imagen anterior">‹</button>' : ''}
          <img class="product-detail-image" id="productDetailMainImage" src="${esc(primaryImage)}" alt="${esc(product.name)}" loading="eager">
          <div class="product-detail-zoom-lens" id="productDetailZoomLens" aria-hidden="true"></div>
          ${hasMultipleImages ? '<button type="button" class="product-detail-nav next" id="productDetailNext" aria-label="Siguiente imagen">›</button>' : ''}
        </div>
        ${hasMultipleImages ? `<div class="product-detail-counter" id="productDetailCounter">1 / ${imageUrls.length}</div>` : ''}
        ${imageUrls.length > 1 ? `<div class="product-detail-thumbs">
          ${imageUrls.map((url, idx) => `
            <button class="product-detail-thumb${idx === 0 ? ' active' : ''}" type="button" aria-label="Ver imagen ${idx + 1}">
              <img src="${esc(url)}" alt="${esc(product.name)} - imagen ${idx + 1}" loading="lazy">
            </button>
          `).join('')}
        </div>` : ''}
      </div>
      <div class="product-detail-content reveal visible">
        <span class="product-detail-category">${esc(Products.categoryLabel(product.category))}</span>
        <h1 class="product-detail-title">${esc(product.name)}</h1>
        <div class="product-detail-price">${Products.formatPrice(price)}</div>
        ${priceNote}
        <div class="product-detail-description">${esc(product.description || 'Sin descripción por el momento.')}</div>
        ${renderVariantSelector(product, selectedVariant)}
        <div class="product-detail-meta">
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Categoría</span>
            <span class="product-detail-meta-value">${esc(Products.categoryLabel(product.category))}</span>
          </div>
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Material</span>
            <span class="product-detail-meta-value">${esc(product.material || 'A definir')}</span>
          </div>
          ${shouldShowVariantMeta ? `
            <div class="product-detail-meta-item">
              <span class="product-detail-meta-label">Variante</span>
              <span class="product-detail-meta-value">${esc(selectedVariantLabel)}</span>
            </div>
          ` : ''}
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Disponibilidad</span>
            <span class="product-detail-meta-value">${esc(statusTextForStock(stock, isPublishedProduct(product)))}</span>
          </div>
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Código</span>
            <span class="product-detail-meta-value">${esc(selectedVariant?.sku || `#${product.id}${selectedVariant ? `-${selectedVariant.id}` : ''}`)}</span>
          </div>
        </div>
        <div class="product-detail-actions">
          <button class="btn btn-primary btn-lg" id="detailAddToCart">${isAvailable ? 'Agregar al carrito' : 'Sin stock'}</button>
          <a class="btn btn-secondary btn-lg" href="catalogo.html">Volver al catálogo</a>
        </div>
        <p class="product-detail-note">¿Necesitás otra variante, color o personalización? Escribinos y lo adaptamos a tu proyecto.</p>
      </div>
      ${stickyBarHTML}
    `;

    root.querySelectorAll('.product-variant-chip').forEach(button => {
      button.addEventListener('click', () => {
        renderProduct(product, Number(button.dataset.variantId));
      });
    });

    const addBtn = document.getElementById('detailAddToCart');
    const stickyBtn = document.getElementById('detailAddToCartSticky');
    const handleAddToCart = () => {
      Cart.addItem({
        id: product.id,
        product_id: product.id,
        variant_id: selectedVariant?.id || null,
        variant_label: shouldShowVariantMeta ? selectedVariantLabel : '',
        cart_key: selectedVariant?.id ? `v:${selectedVariant.id}` : `p:${product.id}`,
        name: product.name,
        price,
        image_url: primaryImage || product.image_url
      });
    };

    if (addBtn) {
      if (!isAvailable) {
        addBtn.disabled = true;
        if (stickyBtn) stickyBtn.disabled = true;
      } else {
        addBtn.addEventListener('click', handleAddToCart);
        stickyBtn?.addEventListener('click', handleAddToCart);
      }
    }

    setupGallery(root, imageUrls);
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
