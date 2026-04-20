<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conocé a PrintingBruno - Impresión 3D de alta calidad.">
    <title>Nosotros | PrintingBruno</title>
    <link rel="stylesheet" href="css/styles.css?v=20260331-1">
    <link rel="icon" type="image/jpeg" href="assets/logo/logo.png">
    <?php
    require_once __DIR__ . '/partials/site-chrome.php';
    pb_render_analytics_head();
    ?>
</head>

<body>
    <?php
    pb_render_header('nosotros');
    ?>

    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Sobre <span class="accent-text">Nosotros</span></h1>
            <div class="page-breadcrumb"><a href="index.html">Inicio</a><span
                    class="separator">/</span><span>Nosotros</span></div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="about-hero">
                <div class="about-text reveal">
                    <h2>Hola, somos <span class="accent-text">PrintingBruno</span></h2>
                    <p>Estamos en General Pacheco y nos apasiona la tecnología 3D. Creamos piezas funcionales,
                        prototipos y objetos personalizados con la máxima dedicación.</p>
                    <p>Además de imprimir tus ideas, somos tu punto de referencia en Zona Norte para conseguir insumos
                        y filamentos. Queremos que tu impresora nunca se detenga, por eso te asesoramos de forma
                        personalizada para que te lleves siempre lo mejor.</p>
                    <p>¡Escribinos y materialicemos ese proyecto!</p>
                    <div style="margin-top: var(--space-xl); display: flex; gap: var(--space-md); flex-wrap: wrap;">
                        <a href="catalogo.html" class="btn btn-primary">Ver Catálogo</a>
                        <a href="contacto.html" class="btn btn-secondary">Contactanos</a>
                    </div>
                </div>
                <div class="reveal"
                    style="border-radius: var(--border-radius-lg); overflow: hidden; border: 1px solid var(--border-subtle); background: var(--bg-card);">
                    <img src="esteesbruno.jpeg" alt="Bruno de PrintingBruno"
                        style="width:100%;height:100%;object-fit:cover;display:block;">
                </div>
            </div>
        </div>
    </section>

    <section class="section"
        style="background: var(--bg-secondary); border-top: 1px solid var(--border-subtle); border-bottom: 1px solid var(--border-subtle);">
        <div class="container">
            <h2 class="section-title reveal">Nuestro <span class="accent-text">Proceso</span></h2>
            <p class="section-subtitle reveal">Así trabajamos para que tu idea se convierta en realidad.</p>
            <div class="features-grid">
                <div class="feature-card reveal reveal-delay-1">
                    <div class="feature-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Consultá</h3>
                    <p class="feature-text">Escribinos por WhatsApp o Instagram con tu idea, fotos o archivos 3D.</p>
                </div>
                <div class="feature-card reveal reveal-delay-2">
                    <div class="feature-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                            <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                            <path d="M2 2l7.586 7.586"></path>
                            <circle cx="11" cy="11" r="2"></circle>
                        </svg>
                    </div>
                    <h3 class="feature-title">Diseñamos</h3>
                    <p class="feature-text">Preparamos el diseño 3D y te enviamos una vista previa para aprobación.</p>
                </div>
                <div class="feature-card reveal reveal-delay-3">
                    <div class="feature-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                            <path d="M6 8h.01M9 8h.01M6 11h.01M9 11h.01M12 8h.01M12 11h.01"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Imprimimos</h3>
                    <p class="feature-text">Configuramos la impresora con los mejores parámetros para máxima calidad.
                    </p>
                </div>
                <div class="feature-card reveal reveal-delay-4">
                    <div class="feature-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                    <h3 class="feature-title">Entregamos</h3>
                    <p class="feature-text">Limpiamos, terminamos y empaquetamos. Envíos a todo el país o retiro.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="section-title reveal">Nuestros <span class="accent-text">Números</span></h2>
            <div class="features-grid reveal">
                <div class="feature-card" style="text-align:center">
                    <div style="font-size:2.5rem;font-weight:900;font-family:var(--font-heading);color:var(--accent);margin-bottom:var(--space-sm)"
                        data-count="500" class="counter">0+</div>
                    <p class="feature-text">Piezas impresas</p>
                </div>
                <div class="feature-card" style="text-align:center">
                    <div style="font-size:2.5rem;font-weight:900;font-family:var(--font-heading);color:var(--accent);margin-bottom:var(--space-sm)"
                        data-count="150" class="counter">0+</div>
                    <p class="feature-text">Clientes satisfechos</p>
                </div>
                <div class="feature-card" style="text-align:center">
                    <div style="font-size:2.5rem;font-weight:900;font-family:var(--font-heading);color:var(--accent);margin-bottom:var(--space-sm)"
                        data-count="50" class="counter">0+</div>
                    <p class="feature-text">Diseños únicos</p>
                </div>
                <div class="feature-card" style="text-align:center">
                    <div style="font-size:2.5rem;font-weight:900;font-family:var(--font-heading);color:var(--accent);margin-bottom:var(--space-sm)"
                        data-count="3" class="counter">0+</div>
                    <p class="feature-text">Años de experiencia</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-bg"></div>
        <div class="container">
            <div class="cta-content reveal">
                <h2 class="cta-title">¿Querés trabajar con nosotros?</h2>
                <p class="cta-text">Contactanos y contanos tu idea. Te asesoramos sin compromiso.</p>
                <div class="cta-buttons"><a href="https://wa.me/5491137022937?text=Hola!" target="_blank"
                        class="btn btn-whatsapp btn-lg">Escribinos por WhatsApp</a></div>
            </div>
        </div>
    </section>
    <?php pb_render_footer(); ?>


    <script src="js/main.js?v=20260331-1"></script>
</body>

</html>
