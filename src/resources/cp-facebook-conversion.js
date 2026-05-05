(() => {
  function qs(sel) {
    return document.querySelector(sel);
  }

  function setModalTitle(text) {
    const dialog = qs('#fbce-event-modal');
    if (!dialog) return;
    const titleEl = dialog.querySelector('.fb-conversion-modal-header h2');
    if (titleEl) titleEl.textContent = text || '';
  }

  function clearModalBody() {
    const body = qs('#fbce-event-modal-body');
    if (body) body.textContent = '';
  }

  function renderPre(text) {
    const pre = document.createElement('pre');
    pre.className = 'fb-conversion-error-content';
    pre.textContent = text || '';
    return pre;
  }

  function renderCopyJsonBlock(payloadObj, testEventCode) {
    const wrapper = { data: [payloadObj] };
    if (testEventCode) {
      wrapper.test_event_code = testEventCode;
    }

    const json = JSON.stringify(wrapper);

    const container = document.createElement('div');
    container.className = 'fb-conversion-copy-json';

    const textarea = document.createElement('textarea');
    textarea.className = 'fb-conversion-copy-payload';
    textarea.setAttribute('aria-hidden', 'true');
    textarea.readOnly = true;
    textarea.value = json;
    container.appendChild(textarea);

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn small';
    btn.textContent = 'Copy as JSON';
    btn.addEventListener('click', async () => {
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(json);
          const original = btn.textContent;
          btn.textContent = 'Copied!';
          setTimeout(() => (btn.textContent = original), 2000);
        }
      } catch {
        // ignore
      }
    });
    container.appendChild(btn);

    return container;
  }

  function renderTable(data) {
    if (!data || typeof data !== 'object') return document.createTextNode('');

    const table = document.createElement('table');
    table.className = 'fb-conversion-event-data-table';
    const tbody = document.createElement('tbody');
    table.appendChild(tbody);

    const entries = Array.isArray(data)
      ? data.map((v, i) => [String(i), v])
      : Object.entries(data);

    for (const [key, value] of entries) {
      const tr = document.createElement('tr');

      const tdKey = document.createElement('td');
      tdKey.textContent = key;
      tr.appendChild(tdKey);

      const tdVal = document.createElement('td');
      if (value && typeof value === 'object') {
        tdVal.appendChild(renderTable(value));
      } else {
        tdVal.textContent = value == null ? '' : String(value);
      }
      tr.appendChild(tdVal);

      tbody.appendChild(tr);
    }

    return table;
  }

  async function fetchModalData(id, type) {
    if (!window.Craft || typeof Craft.getActionUrl !== 'function') {
      throw new Error('Craft JS API unavailable');
    }

    const url = Craft.getActionUrl('facebook-conversion/events/modal', { id, type });
    const res = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });

    if (!res.ok) {
      throw new Error(`Request failed (${res.status})`);
    }

    return await res.json();
  }

  async function openModalFor(id, type) {
    const dialog = qs('#fbce-event-modal');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    clearModalBody();
    setModalTitle('Loading…');
    dialog.showModal();

    try {
      const data = await fetchModalData(id, type);
      setModalTitle(data.title || '');

      const body = qs('#fbce-event-modal-body');
      if (!body) return;

      body.textContent = '';
      if (type === 'event-error') {
        body.appendChild(renderPre(data.payload));
      } else {
        if (type === 'event-data') {
          body.appendChild(renderCopyJsonBlock(data.payload, data.testEventCode));
        }
        body.appendChild(renderTable(data.payload));
      }
    } catch (err) {
      setModalTitle('Error');
      const body = qs('#fbce-event-modal-body');
      if (body) {
        body.textContent = '';
        body.appendChild(renderPre(err && err.message ? err.message : String(err)));
      }
    }
  }

  document.addEventListener('click', (e) => {
    const btn = e.target && e.target.closest ? e.target.closest('[data-fbce-modal-open]') : null;
    if (!btn) return;

    const type = btn.getAttribute('data-fbce-modal-open');
    const id = btn.getAttribute('data-event-id');
    if (!type || !id) return;

    openModalFor(id, type);
  });
})();

