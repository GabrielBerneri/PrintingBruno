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
  <meta property="og:title" content="Tienda de Impresión 3D | PrintingBruno">
  <meta property="og:description"
    content="Transformamos tus ideas en objetos reales. Impresión 3D de alta calidad con atención personalizada.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://www.printingbruno.com/">
  <meta property="og:image" content="https://www.printingbruno.com/assets/logo/logo.png">
  <meta property="og:site_name" content="PrintingBruno">
  <title>Tienda de Impresión 3D | PrintingBruno</title>
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
        <div class="hero-3d-scene">
          <canvas id="heroCanvas"></canvas>
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
        <article class="entry-card reveal-left reveal-delay-1">
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
        <article class="entry-card reveal-right reveal-delay-3">
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
        <div class="feature-card reveal-scale reveal-delay-1">
          <div class="feature-icon">⚙️</div>
          <h3 class="feature-title">Criterio técnico</h3>
          <p class="feature-text">Elegimos materiales, orientación y terminación según el uso real de la pieza, no solo por estética.</p>
        </div>
        <div class="feature-card reveal-scale reveal-delay-2">
          <div class="feature-icon">🎨</div>
          <h3 class="feature-title">Personalización real</h3>
          <p class="feature-text">Podemos partir de una idea, una referencia o un archivo y llevarlo a una versión imprimible y prolija.</p>
        </div>
        <div class="feature-card reveal-scale reveal-delay-3">
          <div class="feature-icon">⚡</div>
          <h3 class="feature-title">Respuesta sin rodeos</h3>
          <p class="feature-text">Te decimos rápido si conviene hacerlo, cómo encararlo y qué información falta para avanzar.</p>
        </div>
        <div class="feature-card reveal-scale reveal-delay-4">
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

  <script>
  function initHero3D() {
    var THREE = window.THREE;
    if (!THREE) return;
    var canvas = document.getElementById('heroCanvas');
    if (!canvas) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var container = canvas.parentElement;
    var isMobile = window.innerWidth < 768;

    // Scene + camera: 3/4 view desde arriba-derecha para ver la cama y el cubo
    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
    camera.position.set(2.8, 2.2, 4.8);
    camera.lookAt(0, -0.3, 0);

    var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: !isMobile, alpha: true, powerPreference: 'high-performance' });
    renderer.setClearColor(0x000000, 0);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, isMobile ? 1 : 1.5));

    function setSize() {
      var w = container.offsetWidth, h = container.offsetHeight;
      if (!w || !h) return;
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
      renderer.setSize(w, h);
    }
    setSize();
    window.addEventListener('resize', setSize, { passive: true });

    // === PRINT BED ===
    var grid = new THREE.GridHelper(4.0, 16, 0x2a1208, 0x160a04);
    grid.position.y = -1.85;
    scene.add(grid);

    var bedPlane = new THREE.Mesh(
      new THREE.PlaneGeometry(4.0, 4.0),
      new THREE.MeshBasicMaterial({ color: 0x0d0600, transparent: true, opacity: 0.6, side: THREE.DoubleSide })
    );
    bedPlane.rotation.x = -Math.PI / 2;
    bedPlane.position.y = -1.85;
    scene.add(bedPlane);

    // === CUBO DE FILAMENTO: capas de tubos cuadrados ===
    var SQ    = 1.55;          // lado del cuadrado
    var h_sq  = SQ / 2;
    var LAYERS      = isMobile ? 10 : 15;
    var LAYER_H     = 0.145;
    var TUBE_R      = 0.026;
    var BASE_Y      = -1.82;   // justo sobre la cama
    var TOTAL_SIDES = LAYERS * 4;

    // Función: devuelve {p1, p2} para un lado/capa dados
    function getSide(layer, side) {
      var y = BASE_Y + (layer + 0.5) * LAYER_H;
      var corners = [
        [new THREE.Vector3(-h_sq, y, -h_sq), new THREE.Vector3( h_sq, y, -h_sq)], // frente: izq→der
        [new THREE.Vector3( h_sq, y, -h_sq), new THREE.Vector3( h_sq, y,  h_sq)], // derecha: frente→fondo
        [new THREE.Vector3( h_sq, y,  h_sq), new THREE.Vector3(-h_sq, y,  h_sq)], // fondo: der→izq
        [new THREE.Vector3(-h_sq, y,  h_sq), new THREE.Vector3(-h_sq, y, -h_sq)], // izquierda: fondo→frente
      ];
      return corners[side];
    }

    // Crear tubos (TubeGeometry) para cada lado de cada capa, inicialmente invisibles
    var objectGroup = new THREE.Group();
    scene.add(objectGroup);

    var sideData = [];
    for (var l = 0; l < LAYERS; l++) {
      var tl = l / Math.max(1, LAYERS - 1);
      for (var s = 0; s < 4; s++) {
        var pts   = getSide(l, s);
        var curve = new THREE.LineCurve3(pts[0], pts[1]);
        var tMat  = new THREE.MeshPhongMaterial({
          color: new THREE.Color(1.0, 0.26 + tl * 0.26, 0.03 + tl * 0.09),
          emissive: new THREE.Color(0.25, 0.04, 0),
          transparent: true, opacity: 0
        });
        var tMesh = new THREE.Mesh(new THREE.TubeGeometry(curve, 1, TUBE_R, 7, false), tMat);
        objectGroup.add(tMesh);
        sideData.push({ mesh: tMesh, mat: tMat, idx: l * 4 + s });
      }
    }

    // === NOZZLE ===
    var nozzleGroup = new THREE.Group();
    scene.add(nozzleGroup);

    // Cuerpo cilíndrico
    nozzleGroup.add(new THREE.Mesh(
      new THREE.CylinderGeometry(0.052, 0.042, 0.18, 8),
      new THREE.MeshPhongMaterial({ color: 0x909090, specular: 0x444444, shininess: 90 })
    ));
    // Punta cónica hacia abajo
    var nTip = new THREE.Mesh(
      new THREE.ConeGeometry(0.042, 0.10, 8),
      new THREE.MeshPhongMaterial({ color: 0x686868, shininess: 100 })
    );
    nTip.rotation.z = Math.PI;
    nTip.position.y = -0.14;
    nozzleGroup.add(nTip);

    // Hot-end incandescente
    var hotMat = new THREE.MeshBasicMaterial({ color: 0xff6b2b, transparent: true, opacity: 0.95 });
    var hotDot = new THREE.Mesh(new THREE.SphereGeometry(0.036, 8, 8), hotMat);
    hotDot.position.y = -0.19;
    nozzleGroup.add(hotDot);

    // Luz puntual en el hot-end
    var nozzleLight = new THREE.PointLight(0xff6b2b, 4, 1.8);
    nozzleLight.position.y = -0.19;
    nozzleGroup.add(nozzleLight);

    // === LUCES GLOBALES ===
    scene.add(new THREE.AmbientLight(0xffffff, 0.18));
    var keyLight  = new THREE.PointLight(0xffffff, 2.8, 16);
    keyLight.position.set(3.5, 4, 3);
    scene.add(keyLight);
    var fillLight = new THREE.PointLight(0xff8f5e, 1.6, 10);
    fillLight.position.set(-3, 1, 2);
    scene.add(fillLight);

    // === MOUSE PARALLAX ===
    var mx = 0, my = 0, txm = 0, tym = 0;
    window.addEventListener('mousemove', function(e) {
      txm = (e.clientX / window.innerWidth  - 0.5);
      tym = (e.clientY / window.innerHeight - 0.5);
    }, { passive: true });

    // === TIMING ===
    var t = 0, lastNow = performance.now(), paused = false;
    var SIDE_DUR    = isMobile ? 0.13 : 0.17; // segundos por lado
    var BUILD_DUR   = TOTAL_SIDES * SIDE_DUR;
    var RETRACT_DUR = 0.65;
    var rafId = null, nozzleRetracted = false;
    var retractStart = new THREE.Vector3();

    function animate() {
      if (paused) return;
      rafId = requestAnimationFrame(animate);
      var now = performance.now();
      t += (now - lastNow) / 1000;
      lastNow = now;

      // ── FASE BUILD ──
      var buildFrac  = Math.min(1, t / BUILD_DUR);
      var exactSide  = buildFrac * TOTAL_SIDES;
      var curSideIdx = Math.min(Math.floor(exactSide), TOTAL_SIDES - 1);
      var sideProg   = exactSide - Math.floor(exactSide); // 0-1 dentro del lado actual

      // Revelar tubos completados y el que se está depositando
      for (var si = 0; si < sideData.length; si++) {
        var sd = sideData[si];
        if (sd.idx < curSideIdx) {
          sd.mat.opacity = 0.85;
        } else if (sd.idx === curSideIdx) {
          // Aparece rápidamente al empezar el lado (como filamento caliente)
          sd.mat.opacity = Math.min(0.85, sideProg * 6);
        }
      }

      // Posicionar nozzle sobre el lado actual
      if (buildFrac < 1) {
        var curLayer = Math.floor(curSideIdx / 4);
        var curSide  = curSideIdx % 4;
        var pts      = getSide(curLayer, curSide);
        var p1 = pts[0], p2 = pts[1];

        nozzleGroup.position.x = p1.x + (p2.x - p1.x) * sideProg;
        nozzleGroup.position.y = p1.y + 0.16;
        nozzleGroup.position.z = p1.z + (p2.z - p1.z) * sideProg;
        // Orientar el nozzle en la dirección de avance
        nozzleGroup.rotation.y = -Math.atan2(p2.z - p1.z, p2.x - p1.x);

        hotMat.opacity = 0.88 + Math.sin(t * 14) * 0.10;
        retractStart.copy(nozzleGroup.position);
      }

      // ── FASE RETRACCIÓN ──
      if (buildFrac >= 1 && !nozzleRetracted) {
        var rt = Math.min(1, (t - BUILD_DUR) / RETRACT_DUR);
        nozzleGroup.position.x = retractStart.x * (1 - rt * 0.6);
        nozzleGroup.position.z = retractStart.z * (1 - rt * 0.6);
        nozzleGroup.position.y = retractStart.y + rt * 2.8;
        hotMat.opacity = 0.95 * (1 - rt);
        nozzleLight.intensity = 4 * (1 - rt);
        if (rt >= 1) { nozzleGroup.visible = false; nozzleRetracted = true; }
      }

      // ── FASE POST-BUILD: flota y rota ──
      var postT = Math.max(0, t - BUILD_DUR - RETRACT_DUR);
      if (postT > 0) {
        objectGroup.position.y = Math.min(0.32, postT * 0.38) + Math.sin(t * 0.72) * 0.07;
        objectGroup.rotation.y = postT * 0.34;
      }

      // Mouse parallax
      mx += (txm - mx) * 0.038;
      my += (tym - my) * 0.038;
      scene.rotation.y = mx * 0.18;
      scene.rotation.x = -my * 0.10;

      renderer.render(scene, camera);
    }

    animate();

    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        paused = true;
        if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
      } else {
        paused = false;
        lastNow = performance.now();
        animate();
      }
    });
  }
  </script>
  <script src="js/three.min.js"></script>
  <script>
    if (typeof THREE !== 'undefined') {
      requestAnimationFrame(function() { requestAnimationFrame(initHero3D); });
    }
  </script>
</body>

</html>
