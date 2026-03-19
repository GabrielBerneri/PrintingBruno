document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contactForm');
  if (!form) return;

  const submitBtn = document.getElementById('contactSubmitBtn');
  const messageBox = document.getElementById('contactFormMessage');
  const whatsappBtn = document.getElementById('contactWhatsAppBtn');
  const messageInput = document.getElementById('message');
  const counter = document.getElementById('contactMessageCounter');
  const dynamicHint = document.getElementById('contactDynamicHint');
  const maxMessageLength = 800;
  const defaultSubject = form.querySelector('input[name="subject"][value="presupuesto"]');

  const hints = {
    presupuesto: 'Tip: incluye medidas, cantidad y material esperado para cotizar mejor.',
    personalizado: 'Tip: describi el uso de la pieza, referencias visuales y si necesitas modelado desde cero.',
    archivo: 'Tip: pega el link al STL, OBJ o referencia del archivo dentro del mensaje.',
    mayorista: 'Tip: indica cantidades aproximadas, frecuencia de reposicion y que tipo de producto necesitas.',
    otro: 'Tip: si no entra en ninguna categoria, dejanos el contexto y te orientamos.'
  };

  function getSelectedSubject() {
    return form.querySelector('input[name="subject"]:checked')?.value || 'presupuesto';
  }

  function getFormData() {
    return {
      name: form.querySelector('#name')?.value.trim() || '',
      email: form.querySelector('#email')?.value.trim() || '',
      phone: form.querySelector('#phone')?.value.trim() || '',
      quantity: form.querySelector('#quantity')?.value.trim() || '',
      timeline: form.querySelector('#timeline')?.value || '',
      subject: getSelectedSubject(),
      message: messageInput?.value.trim() || '',
      website: form.querySelector('#website')?.value.trim() || ''
    };
  }

  function buildWhatsAppUrl(data) {
    const subjectLabels = {
      presupuesto: 'Presupuesto',
      personalizado: 'Diseno personalizado',
      archivo: 'Tengo STL',
      mayorista: 'Consulta mayorista',
      otro: 'Consulta general'
    };

    let text = 'Hola PrintingBruno, quiero hacer una consulta desde la web.\n\n';
    text += `Nombre: ${data.name || '-'}\n`;
    text += `Email: ${data.email || '-'}\n`;
    if (data.phone) text += `Telefono: ${data.phone}\n`;
    if (data.quantity) text += `Cantidad estimada: ${data.quantity}\n`;
    if (data.timeline) text += `Urgencia: ${data.timeline}\n`;
    text += `Tipo: ${subjectLabels[data.subject] || data.subject}\n`;
    if (data.message) text += `Mensaje: ${data.message}`;

    return `https://wa.me/5491137022937?text=${encodeURIComponent(text)}`;
  }

  function updateWhatsAppButton() {
    if (!whatsappBtn) return;
    whatsappBtn.href = buildWhatsAppUrl(getFormData());
  }

  function updateCounter() {
    if (!counter || !messageInput) return;
    const current = messageInput.value.length;
    counter.textContent = `${current} / ${maxMessageLength}`;
  }

  function updateHint() {
    if (!dynamicHint) return;
    dynamicHint.textContent = hints[getSelectedSubject()] || hints.presupuesto;
  }

  function updateTopicCards() {
    form.querySelectorAll('.contact-topic-card').forEach((card) => {
      const input = card.querySelector('input[name="subject"]');
      card.classList.toggle('is-selected', Boolean(input?.checked));
    });
  }

  function setStatus(type, text, whatsappUrl = '') {
    if (!messageBox) return;

    if (!text) {
      messageBox.className = 'contact-form-message';
      messageBox.innerHTML = '';
      return;
    }

    messageBox.className = `contact-form-message is-${type}`;
    messageBox.innerHTML = `<span>${text}</span>`;

    if (type === 'success' && whatsappUrl) {
      const action = document.createElement('a');
      action.className = 'contact-form-message-link';
      action.href = whatsappUrl;
      action.target = '_blank';
      action.rel = 'noopener';
      action.textContent = 'Seguir por WhatsApp';
      messageBox.appendChild(action);
    }
  }

  function resetFormState() {
    form.reset();
    if (defaultSubject) defaultSubject.checked = true;
    updateHint();
    updateTopicCards();
    updateCounter();
    updateWhatsAppButton();
  }

  form.addEventListener('input', () => {
    updateCounter();
    updateWhatsAppButton();
  });

  form.addEventListener('change', () => {
    updateHint();
    updateTopicCards();
    updateWhatsAppButton();
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const data = getFormData();
    const whatsappUrl = buildWhatsAppUrl(data);

    if (data.message.length < 10) {
      setStatus('error', 'Contanos un poco mas para poder ayudarte mejor.');
      messageInput?.focus();
      return;
    }

    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando...';
    setStatus('', '');

    try {
      const response = await fetch('api/contact.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        throw new Error(payload.error || 'No pudimos enviar tu consulta en este momento.');
      }

      setStatus('success', payload.message || 'Consulta enviada correctamente.', whatsappUrl);
      resetFormState();
    } catch (error) {
      setStatus('error', error.message || 'No pudimos enviar tu consulta. Intenta de nuevo.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  });

  if (messageInput) {
    messageInput.maxLength = maxMessageLength;
  }

  updateHint();
  updateTopicCards();
  updateCounter();
  updateWhatsAppButton();
});
