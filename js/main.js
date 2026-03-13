/* ============================================
   PrintingBruno - Main JavaScript
   Interactivity, Animations & UI Logic
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {
  // ===== Mobile Menu Toggle =====
  const menuToggle = document.getElementById('menuToggle');
  const nav = document.getElementById('nav');
  const navOverlay = document.getElementById('navOverlay');

  if (menuToggle && nav) {
    menuToggle.addEventListener('click', () => {
      menuToggle.classList.toggle('active');
      nav.classList.toggle('open');
      if (navOverlay) navOverlay.classList.toggle('active');
      document.body.style.overflow = nav.classList.contains('open') ? 'hidden' : '';
    });

    if (navOverlay) {
      navOverlay.addEventListener('click', () => {
        menuToggle.classList.remove('active');
        nav.classList.remove('open');
        navOverlay.classList.remove('active');
        document.body.style.overflow = '';
      });
    }

    // Close menu on nav link click
    nav.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        menuToggle.classList.remove('active');
        nav.classList.remove('open');
        if (navOverlay) navOverlay.classList.remove('active');
        document.body.style.overflow = '';
      });
    });
  }

  // ===== Header Scroll Effect =====
  const header = document.getElementById('header');
  if (header) {
    let lastScroll = 0;
    window.addEventListener('scroll', () => {
      const currentScroll = window.pageYOffset;
      if (currentScroll > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
      lastScroll = currentScroll;
    }, { passive: true });
  }

  // ===== Scroll Reveal Animations =====
  const revealElements = document.querySelectorAll('.reveal');
  if (revealElements.length > 0) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach(el => revealObserver.observe(el));
  }

  // ===== Animated Counters =====
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length > 0) {
    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(counter => counterObserver.observe(counter));
  }

  function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-count'));
    const duration = 2000;
    const startTime = performance.now();
    const suffix = target < 10 ? '+' : '+';

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      // Ease out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = Math.floor(eased * target);
      element.textContent = current + suffix;

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        element.textContent = target + suffix;
      }
    }

    requestAnimationFrame(update);
  }

  // ===== Catalog Filter =====
  const catalogSidebar = document.querySelector('.catalog-sidebar');
  const catalogGrid = document.getElementById('catalogGrid');
  const productCount = document.getElementById('productCount');
  let currentFilter = 'all';
  let currentSearch = '';

  if (catalogSidebar && catalogGrid) {
    function applyFilterAndSearch() {
      const cards = catalogGrid.querySelectorAll('.product-card');
      let visibleCount = 0;
      const query = currentSearch.toLowerCase().trim();

      cards.forEach(card => {
        const category = card.getAttribute('data-category');
        const name = (card.querySelector('.product-name')?.textContent || '').toLowerCase();
        const desc = (card.querySelector('.product-description')?.textContent || '').toLowerCase();

        const matchesCategory = currentFilter === 'all' || category === currentFilter;
        const matchesSearch = !query || name.includes(query) || desc.includes(query);

        if (matchesCategory && matchesSearch) {
          card.style.display = '';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      if (productCount) {
        productCount.textContent = `Mostrando ${visibleCount} producto${visibleCount !== 1 ? 's' : ''}`;
      }
    }

    function applyFilter(filter) {
      currentFilter = filter;
      const filterOptions = document.querySelectorAll('.filter-option[data-filter]');

      filterOptions.forEach(opt => {
        const value = opt.getAttribute('data-filter');
        const input = opt.querySelector('input[name="category"]');
        const isActive = value === filter;
        opt.classList.toggle('active', isActive);
        if (input) input.checked = isActive;
      });

      applyFilterAndSearch();
    }

    catalogSidebar.addEventListener('click', (event) => {
      const option = event.target.closest('.filter-option[data-filter]');
      if (!option) return;
      applyFilter(option.getAttribute('data-filter'));
    });

    // ===== Catalog Search =====
    const searchInput = document.getElementById('catalogSearch');
    const searchClear = document.getElementById('catalogSearchClear');

    if (searchInput) {
      searchInput.addEventListener('input', () => {
        currentSearch = searchInput.value;
        if (searchClear) searchClear.style.display = currentSearch ? '' : 'none';
        applyFilterAndSearch();
      });
    }

    if (searchClear) {
      searchClear.addEventListener('click', () => {
        searchInput.value = '';
        currentSearch = '';
        searchClear.style.display = 'none';
        searchInput.focus();
        applyFilterAndSearch();
      });
    }

    // Activate filter from URL hash (e.g. catalogo.html#mates)
    function applyHashFilter() {
      const hash = window.location.hash.replace('#', '');
      if (!hash) return;
      const target = document.querySelector(`.filter-option[data-filter="${hash}"]`);
      if (target) applyFilter(hash);
    }

    document.addEventListener('catalogLoaded', () => {
      const hash = window.location.hash.replace('#', '');
      if (hash && document.querySelector(`.filter-option[data-filter="${hash}"]`)) {
        applyFilter(hash);
      } else {
        applyFilter('all');
      }
    });

    window.addEventListener('hashchange', applyHashFilter);
  }

  // ===== Sort Products =====
  const sortSelect = document.getElementById('sortSelect');
  if (sortSelect && catalogGrid) {
    sortSelect.addEventListener('change', () => {
      const cards = Array.from(catalogGrid.querySelectorAll('.product-card'));
      const sortValue = sortSelect.value;

      cards.sort((a, b) => {
        const nameA = a.querySelector('.product-name').textContent;
        const nameB = b.querySelector('.product-name').textContent;

        switch (sortValue) {
          case 'name-asc':
            return nameA.localeCompare(nameB);
          case 'name-desc':
            return nameB.localeCompare(nameA);
          case 'price-asc':
            return parseFloat(a.dataset.price || 0) - parseFloat(b.dataset.price || 0);
          case 'price-desc':
            return parseFloat(b.dataset.price || 0) - parseFloat(a.dataset.price || 0);
          default:
            return 0;
        }
      });

      cards.forEach(card => catalogGrid.appendChild(card));
    });
  }

  // ===== Contact Form =====
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const name = document.getElementById('name').value;
      const email = document.getElementById('email').value;
      const phone = document.getElementById('phone').value;
      const subject = document.getElementById('subject').value;
      const message = document.getElementById('message').value;

      // Build WhatsApp message
      let waMessage = `Hola! Soy ${name}.`;
      if (subject) waMessage += `\nConsulta: ${subject}`;
      waMessage += `\n\n${message}`;
      if (email) waMessage += `\n\nEmail: ${email}`;
      if (phone) waMessage += `\nTel: ${phone}`;

      const waUrl = `https://wa.me/5491125544248?text=${encodeURIComponent(waMessage)}`;
      window.open(waUrl, '_blank');

      // Show success message
      const btn = contactForm.querySelector('button[type="submit"]');
      const originalText = btn.textContent;
      btn.textContent = '✓ Redirigiendo a WhatsApp...';
      btn.style.background = '#25D366';
      setTimeout(() => {
        btn.textContent = originalText;
        btn.style.background = '';
      }, 3000);
    });
  }

  // ===== Smooth Scroll for Anchors =====
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href === '#') return;

      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        const headerHeight = document.querySelector('.header').offsetHeight;
        const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;
        window.scrollTo({ top: targetPosition, behavior: 'smooth' });
      }
    });
  });



  // ===== Preload hero image for LCP =====
  const heroImg = document.querySelector('.hero-image-wrapper img');
  if (heroImg) {
    heroImg.loading = 'eager';
    heroImg.fetchPriority = 'high';
  }
});
