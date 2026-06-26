<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description"
    content="PrintingBruno - Impresión 3D a medida. Figuras, organizadores, lámparas, piezas personalizadas y más. Calidad premium con atención personalizada.">
  <meta name="keywords"
    content="impresión 3D, printing bruno, figuras 3D, impresión personalizada, 3D printing Argentina">
  <meta name="author" content="PrintingBruno">
  <meta property="og:title" content="Impresión 3D a Medida | PrintingBruno">
  <meta property="og:description"
    content="Transformamos tus ideas en objetos reales. Impresión 3D de alta calidad con atención personalizada.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://www.printingbruno.com/">
  <meta property="og:image" content="https://www.printingbruno.com/assets/logo/logo.png">
  <meta property="og:site_name" content="PrintingBruno">
  <title>Impresión 3D a Medida | PrintingBruno</title>
  <link rel="canonical" href="https://www.printingbruno.com/">
  <link rel="stylesheet" href="css/styles.css?v=20260331-1">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/logo/logo.png">
  <link rel="apple-touch-icon" href="https://www.printingbruno.com/assets/logo/logo.png">
  <?php
  require_once __DIR__ . '/partials/site-chrome.php';
  pb_render_analytics_head();
  ?>
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "PrintingBruno",
      "url": "https://www.printingbruno.com/",
      "logo": "https://www.printingbruno.com/assets/logo/logo.png",
      "image": "https://www.printingbruno.com/assets/logo/logo.png",
      "sameAs": [
        "https://www.instagram.com/printing.bruno/",
        "https://www.tiktok.com/@printing.bruno"
      ]
    }
  </script>
</head>

<body>
  <?php
  pb_render_header('home', ['show_cart' => true]);
  ?>

  <!-- ========== HERO ========== -->
  <section class="hero" id="hero">
    <div class="hero-bg">
      <div class="hero-grid"></div>
      <div class="hero-orb hero-orb-1"></div>
      <div class="hero-orb hero-orb-2"></div>
      <div class="hero-orb hero-orb-3"></div>
    </div>

    <div class="container">
      <div class="hero-content">
        <h1 class="hero-title">
          Tienda de impresión <span class="hero-3d">3D</span>
        </h1>
        <p class="hero-description">
          Venimos con las mejores ofertas del mercado y la confianza que tu proyecto necesita.
        </p>
        <div class="hero-buttons">
          <a href="contacto.html" class="btn btn-primary btn-lg">
            Pedir presupuesto
          </a>
          <a href="catalogo.html" class="btn btn-secondary btn-lg">
            Ver catálogo
          </a>
        </div>
      </div>

      <div class="hero-visual">
        <div class="hero-image-glow"></div>
        <div class="hero-image-wrapper">
          <img src="assets/logo/heroimg.jpg" alt="Impresión 3D PrintingBruno" loading="eager">
        </div>
      </div>
    </div>
  </section>

  <section class="section home-entry-points" id="como-empezar">
    <div class="container">
      <div class="section-head reveal">
        <div>
          <span class="section-kicker">Puntos de entrada</span>
          <h2 class="section-title">Empezá por el camino que mejor encaje con tu <span class="accent-text">pedido</span></h2>
        </div>
      </div>

      <div class="entry-points-grid">
        <article class="entry-card reveal reveal-delay-1">
          <div class="entry-icon">STL</div>
          <h3>Ya tenés archivo</h3>
          <p>Subinos el contexto, material buscado y cantidad. Lo evaluamos y te orientamos rápido.</p>
          <a href="contacto.html" class="entry-link">Cargar consulta técnica</a>
        </article>
        <article class="entry-card reveal reveal-delay-2">
          <div class="entry-icon">3D</div>
          <h3>Necesitás diseño desde cero</h3>
          <p>Si tenés una idea, boceto o referencia, te ayudamos a traducirla a una pieza imprimible.</p>
          <a href="contacto.html" class="entry-link">Contar la idea</a>
        </article>
        <article class="entry-card reveal reveal-delay-3">
          <div class="entry-icon">B2B</div>
          <h3>Querés una tanda o reposición</h3>
          <p>Ideal para eventos, souvenirs, series cortas o clientes que necesitan repetir producción.</p>
          <a href="contacto.html" class="entry-link">Consultar volumen</a>
        </article>
      </div>
    </div>
  </section>

  <!-- ========== CATEGORÍAS ========== -->
  <section class="section categories" id="categorias">
    <div class="container">
      <div class="section-head reveal">
        <div>
          <span class="section-kicker">Lo que más piden</span>
          <h2 class="section-title">Categorias que mueven el <span class="accent-text">catálogo</span></h2>
        </div>
      </div>

      <div class="categories-grid">
        <a href="catalogo.html#mates" class="category-card reveal reveal-delay-1">
          <div class="category-icon">🧉</div>
          <span class="category-eyebrow">Uso diario</span>
          <h3 class="category-name">Mates y piezas con identidad</h3>
          <p class="category-count">Modelos listos para regalar, usar o personalizar.</p>
          <span class="category-link">Explorar mates</span>
        </a>
        <a href="catalogo.html#personalizado" class="category-card reveal reveal-delay-2">
          <div class="category-icon">🎉</div>
          <span class="category-eyebrow">Eventos y regalos</span>
          <h3 class="category-name">Personalizados</h3>
          <p class="category-count">Souvenirs, regalos, cumpleaños y piezas con nombre propio.</p>
          <span class="category-link">Ver personalizados</span>
        </a>
        <a href="catalogo.html#filamentos" class="category-card reveal reveal-delay-3">
          <div class="category-icon">🧵</div>
          <span class="category-eyebrow">Producción</span>
          <h3 class="category-name">Filamentos e insumos</h3>
          <p class="category-count">Materiales y consumibles para quienes ya imprimen o quieren escalar.</p>
          <span class="category-link">Ver insumos</span>
        </a>
        <a href="catalogo.html#funcional" class="category-card reveal reveal-delay-4">
          <div class="category-icon">⚙️</div>
          <span class="category-eyebrow">Soluciones</span>
          <h3 class="category-name">Piezas funcionales</h3>
          <p class="category-count">Soportes, organizadores y piezas pensadas para resolver problemas concretos.</p>
          <span class="category-link">Ver funcionales</span>
        </a>
      </div>
    </div>
  </section>

  <!-- ========== PRODUCTOS DESTACADOS ========== -->
  <section class="section products" id="productos">
    <div class="container">
      <div class="section-head reveal">
        <div>
          <span class="section-kicker">Selección actual</span>
          <h2 class="section-title">Productos <span class="accent-text">destacados</span></h2>
        </div>
      </div>

      <div class="products-grid" id="featuredGrid">
        <!-- Products loaded dynamically from API -->
      </div>

      <div style="text-align: center; margin-top: var(--space-3xl);">
        <a href="catalogo.html" class="btn btn-secondary btn-lg reveal">Ver Todo el Catálogo →</a>
      </div>
    </div>
  </section>

  <!-- ========== POR QUÉ ELEGIRNOS ========== -->
  <section class="section features" id="features">
    <div class="container">
      <div class="section-head reveal">
        <div>
          <span class="section-kicker">Forma de trabajo</span>
          <h2 class="section-title">Qué recibís cuando trabajás con <span class="accent-text">PrintingBruno</span></h2>
        </div>
      </div>

      <div class="features-grid">
        <div class="feature-card reveal reveal-delay-1">
          <div class="feature-icon">💎</div>
          <h3 class="feature-title">Criterio técnico</h3>
          <p class="feature-text">Elegimos materiales, orientación y terminación según el uso real de la pieza, no solo por estética.</p>
        </div>
        <div class="feature-card reveal reveal-delay-2">
          <div class="feature-icon">🎨</div>
          <h3 class="feature-title">Personalización real</h3>
          <p class="feature-text">Podemos partir de una idea, una referencia o un archivo y llevarlo a una versión imprimible y prolija.</p>
        </div>
        <div class="feature-card reveal reveal-delay-3">
          <div class="feature-icon">🚀</div>
          <h3 class="feature-title">Respuesta sin rodeos</h3>
          <p class="feature-text">Te decimos rápido si conviene hacerlo, cómo encararlo y qué información falta para avanzar.</p>
        </div>
        <div class="feature-card reveal reveal-delay-4">
          <div class="feature-icon">💬</div>
          <h3 class="feature-title">Seguimiento directo</h3>
          <p class="feature-text">Hablás con quien produce y define el pedido. Menos vueltas, mejores decisiones y mejor resultado.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== TESTIMONIOS ========== -->
  <section class="section testimonials" id="testimonios">
    <div class="container">
      <div class="section-head reveal">
        <div>
          <span class="section-kicker">Prueba social</span>
          <h2 class="section-title">Lo que cuentan quienes ya pidieron sus <span class="accent-text">piezas</span></h2>
        </div>
      </div>

      <div class="testimonials-wrapper reveal">
        <div class="testimonial-card">
          <div class="testimonial-tag">Detalle fino</div>
          <div class="testimonial-stars">★★★★★</div>
          <p class="testimonial-text">"Increíble la calidad de la figura que me hicieron. El detalle es impresionante y
            la atención fue excelente de principio a fin."</p>
          <div class="testimonial-author">
            <div class="testimonial-avatar">ML</div>
            <div>
              <div class="testimonial-name">Martín L.</div>
              <div class="testimonial-role">Figura personalizada</div>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="testimonial-tag">Eventos</div>
          <div class="testimonial-stars">★★★★★</div>
          <p class="testimonial-text">"Pedí llaveros personalizados para mi evento y quedaron espectaculares. Todos los
            invitados quedaron encantados. ¡Súper recomendable!"</p>
          <div class="testimonial-author">
            <div class="testimonial-avatar">CP</div>
            <div>
              <div class="testimonial-name">Carolina P.</div>
              <div class="testimonial-role">Llaveros personalizados</div>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="testimonial-tag">Solución funcional</div>
          <div class="testimonial-stars">★★★★★</div>
          <p class="testimonial-text">"Necesitaba una pieza funcional para un proyecto y Bruno me asesoró perfecto. La
            pieza encajó a la perfección. Volveré seguro."</p>
          <div class="testimonial-author">
            <div class="testimonial-avatar">FS</div>
            <div>
              <div class="testimonial-name">Federico S.</div>
              <div class="testimonial-role">Pieza funcional</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== CTA ========== -->
  <section class="cta-section" id="cta">
    <div class="cta-bg"></div>
    <div class="container">
      <div class="cta-content reveal">
        <h2 class="cta-title">¿Tenés una idea, un STL o una necesidad puntual? <span class="accent-text">Lo aterrizamos juntos.</span></h2>
        <p class="cta-text">Contanos el uso, la cantidad y cualquier referencia que tengas. Te orientamos con el camino más lógico para producirlo.</p>
        <div class="cta-buttons">
          <a href="https://wa.me/5491137022937?text=Hola!%20Tengo%20una%20idea%20para%20imprimir%20en%203D"
            target="_blank" class="btn btn-whatsapp btn-lg">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
            Escribinos por WhatsApp
          </a>
          <a href="contacto.html" class="btn btn-secondary btn-lg">
            Formulario de Contacto
          </a>
        </div>
      </div>
    </div>
  </section>

  <?php pb_render_footer(); ?>
  <?php pb_render_cart_drawer(); ?>

  <script src="js/cart.js?v=20260331-1"></script>
  <script src="js/products.js?v=20260331-1"></script>
  <script src="js/main.js?v=20260331-1"></script>
  <script>Products.loadFeatured('featuredGrid');</script>
</body>

</html>
