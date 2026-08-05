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
    return ['base', 'única', 'unica', 'sin definir', ''].includes(String(label || '').trim().toLowerCase());
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
    const primary = variant?.primary_color_hex || colorHex(variant?.primary_color || variant?.label);
    if (variant?.secondary_color) {
      const secondary = variant?.secondary_color_hex || colorHex(variant.secondary_color);
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
    const allVariants = getActiveVariants(product);
    if (allVariants.length === 0) return '';

    // Solo mostrar variantes con color definido (no "Base", no "Sin definir")
    const UNDEFINED_COLOR = new Set(['', 'sin definir', 'base', 'única', 'unica']);
    const hasDefinedColor = v => {
      const p = String(v.primary_color_name || v.primary_color || '').trim().toLowerCase();
      const s = String(v.secondary_color_name || v.secondary_color || '').trim().toLowerCase();
      return !UNDEFINED_COLOR.has(p) || !UNDEFINED_COLOR.has(s);
    };
    const variants = allVariants.filter(v => hasDefinedColor(v) && !isDefaultVariantLabel(getVariantLabel(v)));
    if (variants.length === 0) return '';

    // Mostrar aunque sea 1 variante si tiene color real (informativo)
    const showSingle = variants.length === 1 && !isDefaultVariantLabel(getVariantLabel(variants[0]));
    if (variants.length === 1 && !showSingle) return '';

    return `
      <div class="product-variant-picker">
        <div class="product-variant-picker-head">
          <span class="product-variant-picker-label">Color</span>
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

    if (imageWrap && mainImg && zoomLens && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      const moveZoom = (e) => {
        const rect = imageWrap.getBoundingClientRect();
        const x = Math.min(Math.max(e.clientX - rect.left, 0), rect.width);
        const y = Math.min(Math.max(e.clientY - rect.top, 0), rect.height);
        const xPercent = rect.width ? (x / rect.width) * 100 : 50;
        const yPercent = rect.height ? (y / rect.height) * 100 : 50;
        imageWrap.style.setProperty('--zoom-x', `${xPercent}%`);
        imageWrap.style.setProperty('--zoom-y', `${yPercent}%`);
      };

      imageWrap.addEventListener('mouseenter', () => imageWrap.classList.add('zoom-active'));
      imageWrap.addEventListener('mouseleave', () => imageWrap.classList.remove('zoom-active'));
      imageWrap.addEventListener('mousemove', moveZoom);
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
        <div id="productDetailMainPrice" class="product-detail-price">${Products.formatPrice(price)}</div>
        <div id="installmentBadge" class="installment-badge" style="display:none"></div>
        ${Number(product.transfer_discount || 0) > 0 ? `
          <div id="productDetailTransferPrice" class="product-detail-price-discounted">${Products.formatPrice(price * (1 - Number(product.transfer_discount) / 100))} <span style="font-weight:400;font-size:0.85em;">con transferencia/efectivo</span></div>
          <span class="product-badge transfer-discount" style="position:static;display:inline-block;margin-top:0;margin-bottom:var(--space-md);">${Number(product.transfer_discount)}% OFF transferencia/efectivo</span>
        ` : ''}
        ${priceNote}
        <div class="product-detail-description">${esc(product.description || 'Sin descripción por el momento.')}</div>
        ${renderVariantSelector(product, selectedVariant)}
        <div class="product-detail-meta">
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Categoría</span>
            <span class="product-detail-meta-value">${esc(Products.categoryLabel(product.category))}</span>
          </div>
          ${product.material && product.material.trim() && product.material.trim().toLowerCase() !== 'a definir' ? `
          <div class="product-detail-meta-item">
            <span class="product-detail-meta-label">Material</span>
            <span class="product-detail-meta-value">${esc(product.material)}</span>
          </div>` : ''}
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
    const priceRef = { value: price };
    const paymentMethodRef = { value: 'mercadopago' };
    const installmentsRef = { value: null };
    const handleAddToCart = () => {
      Cart.addItem({
        id: product.id,
        product_id: product.id,
        variant_id: selectedVariant?.id || null,
        variant_label: shouldShowVariantMeta ? selectedVariantLabel : '',
        cart_key: selectedVariant?.id ? `v:${selectedVariant.id}` : `p:${product.id}`,
        name: product.name,
        price: priceRef.value,
        image_url: primaryImage || product.image_url,
        transfer_discount: priceRef.value === price ? Number(product.transfer_discount || 0) : 0,
        payment_method: paymentMethodRef.value,
        installments: installmentsRef.value
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
    const transferDiscount = Number(product.transfer_discount || 0);
    if (product.installments_enabled || transferDiscount > 0) {
      loadInstallmentBadge(price, product.installment_prices || {}, priceRef, paymentMethodRef, installmentsRef, root, transferDiscount);
    }
  }

  async function loadInstallmentBadge(basePrice, installmentPrices, priceRef, paymentMethodRef, installmentsRef, root, transferDiscount) {
    if (!basePrice || basePrice <= 0) return;
    const badge = document.getElementById('installmentBadge');
    if (!badge) return;

    const fmt = n => Number(n).toLocaleString('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 });
    const entries = Object.entries(installmentPrices)
      .map(([n, p]) => [parseInt(n), parseFloat(p)])
      .filter(([n, p]) => n > 0 && p > 0)
      .sort(([a], [b]) => a - b);

    const updatePriceDisplay = (newPrice, method = 'mercadopago', cuotas = null) => {
      priceRef.value = newPrice;
      paymentMethodRef.value = method;
      installmentsRef.value = cuotas;
      const mainEl = root.querySelector('#productDetailMainPrice');
      if (mainEl) mainEl.textContent = Products.formatPrice(newPrice);
      const transferEl = root.querySelector('#productDetailTransferPrice');
      if (transferEl) transferEl.style.display = 'none';
    };

    const transferPrice = transferDiscount > 0 ? Math.round(basePrice * (1 - transferDiscount / 100)) : null;

    if (entries.length > 0 || transferPrice) {
      // Ocultar el precio de transferencia separado (ya va integrado como opción)
      const transferEl = root.querySelector('#productDetailTransferPrice');
      if (transferEl) transferEl.style.display = 'none';

      const allOptions = [];
      if (transferPrice) {
        allOptions.push({ label: `Transferencia / Efectivo (${transferDiscount}% OFF)`, price: transferPrice, perMonth: null, method: 'transferencia', cuotas: null });
      }
      allOptions.push({ label: '1 pago MercadoPago', price: basePrice, perMonth: null, method: 'mercadopago', cuotas: 1 });
      entries.forEach(([n, p]) => allOptions.push({ label: `${n} cuotas sin interés`, price: p, perMonth: p / n, method: 'mercadopago', cuotas: n }));

      const minPrice = Math.min(...allOptions.map(o => o.price));
      badge.innerHTML = `
        <div style="margin:6px 0 2px;font-size:0.8rem;font-weight:600;color:var(--text-muted);letter-spacing:0.03em">OPCIONES DE PAGO</div>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px">
          ${allOptions.map(opt => `
            <label data-price="${opt.price}" style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:9px 12px;border-radius:8px;border:1px solid var(--border-light);transition:border-color .15s,background .15s">
              <input type="radio" name="installmentOption" value="${opt.price}" data-method="${opt.method}" data-cuotas="${opt.cuotas ?? ''}" style="accent-color:var(--accent);flex-shrink:0">
              <span style="flex:1;font-size:0.9rem;font-weight:500">${opt.label}${opt.price === minPrice ? ' <span style="font-size:0.72rem;font-weight:600;color:#22c55e;background:rgba(34,197,94,0.1);padding:1px 7px;border-radius:10px;border:1px solid rgba(34,197,94,0.25);vertical-align:middle">Mejor precio</span>' : ''}</span>
              ${opt.perMonth ? `<strong style="color:var(--accent)">${fmt(opt.perMonth)}<span style="font-weight:400;font-size:0.8em">/mes</span></strong>` : ''}
              <span style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap">Total ${fmt(opt.price)}</span>
            </label>
          `).join('')}
        </div>`;
      badge.style.display = '';

      badge.querySelectorAll('input[name="installmentOption"]').forEach(r => {
        r.addEventListener('change', () => {
          const method = r.dataset.method || 'mercadopago';
          const cuotas = r.dataset.cuotas ? Number(r.dataset.cuotas) : null;
          updatePriceDisplay(Number(r.value), method, cuotas);
          badge.querySelectorAll('label[data-price]').forEach(l => {
            const sel = l.dataset.price === r.value;
            l.style.borderColor = sel ? 'var(--accent)' : 'var(--border-light)';
            l.style.background = sel ? 'color-mix(in srgb, var(--accent) 8%, transparent)' : '';
          });
        });
      });
      return;
    }

    // Fallback: badge calculado por API
    try {
      const res = await fetch(`api/installments.php?amount=${Math.round(basePrice)}`);
      if (!res.ok) return;
      const data = await res.json();
      if (data.error || !data.installments || !data.installment_amount) return;
      badge.textContent = `${data.installments} cuotas de ${fmt(data.installment_amount)} (MercadoPago)`;
      badge.style.display = '';
    } catch (_) {}
  }

  const RELATED_CATEGORIES = {
    impresoras:   ['insumos', 'filamentos'],
    insumos:      ['impresoras', 'filamentos'],
    filamentos:   ['impresoras', 'insumos'],
    figuras:      ['decoracion', 'personalizado', 'llaveros'],
    decoracion:   ['figuras', 'personalizado', 'llaveros'],
    personalizado:['figuras', 'decoracion', 'llaveros'],
    mates:        ['jarras', 'personalizado', 'funcional'],
    jarras:       ['mates', 'personalizado', 'funcional'],
    llaveros:     ['figuras', 'decoracion', 'personalizado'],
    funcional:    ['personalizado', 'decoracion', 'figuras'],
  };

  async function loadRelatedProducts(currentProduct) {
    const section = document.getElementById('relatedProductsSection');
    const grid = document.getElementById('relatedProductsGrid');
    console.log('[related] guard:', { section: !!section, grid: !!grid, Products: !!window.Products });
    if (!section || !grid || !window.Products) return;

    try {
      const apiUrl = new URL('api/products.php', window.location.href);
      const res = await fetch(apiUrl);
      if (!res.ok) { console.error('[related] fetch error', res.status); return; }
      const all = await res.json();
      const products = Array.isArray(all) ? all : (all.products || []);
      console.log('[related] productos totales:', products.length, '| categoria actual:', currentProduct.category, '| id actual:', currentProduct.id);

      const cat = (currentProduct.category || '').toLowerCase();
      const relatedCats = RELATED_CATEGORIES[cat] || [];
      const currentId = Number(currentProduct.id);
      const isActive = p => p.active === undefined || p.active == 1 || p.active === true;

      // Prioridad: categorías relacionadas primero, luego misma categoría, luego cualquier producto
      let related = products.filter(p =>
        Number(p.id) !== currentId && isActive(p) && relatedCats.includes((p.category || '').toLowerCase())
      );
      if (related.length < 4) {
        const sameCat = products.filter(p =>
          Number(p.id) !== currentId && isActive(p) && (p.category || '').toLowerCase() === cat && !related.find(r => r.id === p.id)
        );
        related = [...related, ...sameCat];
      }
      if (related.length < 4) {
        const others = products.filter(p =>
          Number(p.id) !== currentId && isActive(p) && !related.find(r => r.id === p.id)
        );
        related = [...related, ...others];
      }
      related = related.slice(0, 4);

      console.log('[related] relacionados encontrados:', related.length);
      if (related.length === 0) return;

      grid.innerHTML = '';
      related.forEach((p, i) => {
        const delays = ['reveal-delay-1', 'reveal-delay-2', 'reveal-delay-3', 'reveal-delay-4'];
        const card = Products.renderCard(p, { delay: delays[i], enableGallery: false });
        card.classList.add('revealed');
        grid.appendChild(card);
      });
      section.style.display = '';
    } catch (e) { console.error('[related]', e); }
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
