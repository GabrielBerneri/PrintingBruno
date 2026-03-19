<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contactá a PrintingBruno para consultas sobre impresión 3D.">
    <title>Contacto | PrintingBruno</title>
    <link rel="stylesheet" href="css/styles.css?v=20260319-1">
    <link rel="icon" type="image/png" href="assets/logo/logo.png">
    <?php
    require_once __DIR__ . '/partials/site-chrome.php';
    pb_render_analytics_head();
    ?>
</head>

<body>
    <?php
    pb_render_header('contacto');
    ?>

    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><span class="accent-text">Contactanos</span></h1>
            <div class="page-breadcrumb"><a href="index.html">Inicio</a><span
                    class="separator">/</span><span>Contacto</span></div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-form reveal">
                    <div class="contact-form-head">
                        <div>
                            <span class="contact-form-eyebrow">Formulario de consulta</span>
                            <h2 class="contact-form-title">Envianos los datos del proyecto</h2>
                        </div>
                        <p class="contact-form-intro">Elegi el tipo de consulta y dejá todo lo importante en un solo mensaje.</p>
                    </div>

                    <form id="contactForm" novalidate>
                        <div class="contact-honeypot" aria-hidden="true">
                            <label for="website">Sitio web</label>
                            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <fieldset class="contact-topic-group">
                            <legend class="contact-topic-legend">Tipo de consulta</legend>
                            <div class="contact-topic-grid">
                                <label class="contact-topic-card">
                                    <input type="radio" name="subject" value="presupuesto" checked>
                                    <span>Presupuesto</span>
                                    <small>Para cotizar una pieza o pedido puntual.</small>
                                </label>
                                <label class="contact-topic-card">
                                    <input type="radio" name="subject" value="personalizado">
                                    <span>Diseño personalizado</span>
                                    <small>Si todavia no tenes archivo y hay que diseñarlo.</small>
                                </label>
                                <label class="contact-topic-card">
                                    <input type="radio" name="subject" value="archivo">
                                    <span>Tengo STL</span>
                                    <small>Si ya tenes el archivo y queres imprimirlo.</small>
                                </label>
                                <label class="contact-topic-card">
                                    <input type="radio" name="subject" value="mayorista">
                                    <span>Mayorista</span>
                                    <small>Para volumen, reventa o reposiciones.</small>
                                </label>
                                <label class="contact-topic-card">
                                    <input type="radio" name="subject" value="otro">
                                    <span>Otro</span>
                                    <small>Consultas generales o casos especiales.</small>
                                </label>
                            </div>
                        </fieldset>

                        <div class="contact-field-grid">
                            <div class="form-floating-group">
                                <input type="text" id="name" name="name" class="form-input" required placeholder=" " autocomplete="name">
                                <label class="form-floating-label" for="name">Nombre completo *</label>
                            </div>
                            <div class="form-floating-group">
                                <input type="email" id="email" name="email" class="form-input" required placeholder=" " autocomplete="email">
                                <label class="form-floating-label" for="email">Email *</label>
                            </div>
                            <div class="form-floating-group">
                                <input type="tel" id="phone" name="phone" class="form-input" placeholder=" " autocomplete="tel" inputmode="tel">
                                <label class="form-floating-label" for="phone">Telefono / WhatsApp</label>
                            </div>
                            <div class="form-floating-group">
                                <input type="text" id="quantity" name="quantity" class="form-input" placeholder=" ">
                                <label class="form-floating-label" for="quantity">Cantidad estimada</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="timeline">Urgencia o fecha estimada</label>
                            <select id="timeline" name="timeline" class="form-select">
                                <option value="">Flexible / sin apuro</option>
                                <option value="esta-semana">Lo necesito esta semana</option>
                                <option value="proxima-semana">Lo necesito la proxima semana</option>
                                <option value="este-mes">Lo necesito este mes</option>
                                <option value="cotizar">Solo quiero cotizar por ahora</option>
                            </select>
                        </div>

                        <div class="form-floating-group">
                            <textarea id="message" name="message" class="form-input contact-message-input" required placeholder=" " rows="5" maxlength="800" aria-describedby="contactDynamicHint"></textarea>
                            <label class="form-floating-label" for="message">Contanos medidas, material, colores, uso o link al STL *</label>
                        </div>

                        <div class="contact-form-meta">
                            <p class="contact-form-hint" id="contactDynamicHint">Tip: si tenes referencias visuales o STL, pegá el link en el mensaje.</p>
                            <div class="contact-message-counter" id="contactMessageCounter">0 / 800</div>
                        </div>

                        <div class="contact-form-actions">
                            <button type="submit" class="btn btn-primary btn-lg" id="contactSubmitBtn">Enviar consulta</button>
                            <a class="btn btn-secondary btn-lg" id="contactWhatsAppBtn" href="https://wa.me/5491137022937?text=Hola%20PrintingBruno,%20quiero%20hacer%20una%20consulta." target="_blank" rel="noopener">Prefiero WhatsApp</a>
                        </div>

                        <div id="contactFormMessage" class="contact-form-message" role="status" aria-live="polite"></div>
                    </form>
                </div>

                <div class="contact-side-stack reveal reveal-delay-1">
                    <div class="contact-info-card contact-info-card-accent">
                        <h3>Como trabajamos</h3>
                        <div class="contact-process-step">
                            <span>1</span>
                            <div>
                                <strong>Nos contás la idea</strong>
                                <p>Con medidas, referencias o archivo STL si ya lo tenes.</p>
                            </div>
                        </div>
                        <div class="contact-process-step">
                            <span>2</span>
                            <div>
                                <strong>Te respondemos con propuesta</strong>
                                <p>Te orientamos con materiales, viabilidad y un presupuesto base.</p>
                            </div>
                        </div>
                        <div class="contact-process-step">
                            <span>3</span>
                            <div>
                                <strong>Coordinamos produccion y entrega</strong>
                                <p>Definimos tiempos, ajustes finales y forma de retiro o envio.</p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-info-card">
                        <h3>Canales directos</h3>
                        <a class="contact-method-card" href="https://wa.me/5491137022937?text=Hola%20PrintingBruno,%20quiero%20hacer%20una%20consulta." target="_blank" rel="noopener">
                            <div class="contact-method-icon">WA</div>
                            <div>
                                <strong>WhatsApp</strong>
                                <span>Conversacion rapida y seguimiento directo</span>
                            </div>
                        </a>
                        <a class="contact-method-card" href="mailto:printingbruno.22@gmail.com">
                            <div class="contact-method-icon">EM</div>
                            <div>
                                <strong>Email</strong>
                                <span>printingbruno.22@gmail.com</span>
                            </div>
                        </a>
                        <a class="contact-method-card" href="https://www.instagram.com/printing.bruno/" target="_blank" rel="noopener">
                            <div class="contact-method-icon">IG</div>
                            <div>
                                <strong>Instagram</strong>
                                <span>@printing.bruno</span>
                            </div>
                        </a>
                    </div>

                    <div class="contact-info-card">
                        <h3>Datos utiles</h3>
                        <div class="contact-info-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <div>
                                <strong>Base operativa</strong>
                                <span>Argentina. Envio nacional y retiro coordinado.</span>
                            </div>
                        </div>
                        <div class="contact-info-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <div>
                                <strong>Horario de respuesta</strong>
                                <span>Lun - Sab, 9:00 a 20:00</span>
                            </div>
                        </div>
                        <div class="contact-info-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <div>
                                <strong>Archivos y referencias</strong>
                                <span>Podes enviarlos por link dentro del mensaje o seguir por WhatsApp.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php pb_render_footer(); ?>

    <script src="js/main.js?v=20260315-4"></script>
    <script src="js/contact.js?v=20260315-4"></script>
</body>

</html>
