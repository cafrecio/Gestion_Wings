// Navegación local del informe. Sin red, cambios de datos ni dependencias.
(function (root, factory) {
  if (typeof module === 'object' && module.exports) module.exports = factory();
  else root.InformeWings = factory();
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  const clean = value => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  function destinoDia(value, routes) {
    const day = Number(value);
    if (!Number.isInteger(day) || day < 1 || day > 60) return { error: 'Elegí un día entre 1 y 60.' };
    return routes[String(day)] ? { url: routes[String(day)] } : { sinInforme: day };
  }
  function seleccionar(items, query, state, page, size) {
    const q = clean(query);
    const filtered = items.filter(x => clean(x.text).includes(q) && (!state || x.state === state));
    const pages = Math.max(1, Math.ceil(filtered.length / size));
    const current = Math.min(Math.max(1, page), pages);
    return { ids: filtered.slice((current - 1) * size, current * size).map(x => x.id), total: filtered.length, pages, current };
  }
  function montar(doc, win) {
    const picker = doc.querySelector('#elegir-dia');
    const panel = doc.querySelector('#contenido-informe');
    const empty = doc.querySelector('#sin-informe');
    const routes = JSON.parse(picker.dataset.rutas);
    picker.addEventListener('submit', event => {
      event.preventDefault();
      const result = destinoDia(doc.querySelector('#numero-dia').value, routes);
      const message = doc.querySelector('#aviso-dia');
      if (result.error) { message.textContent = result.error; return; }
      message.textContent = '';
      if (result.url) { win.location.href = result.url; return; }
      panel.hidden = true; empty.hidden = false;
      doc.querySelector('#titulo-sin-informe').textContent = 'Día ' + result.sinInforme + ' · sin informe publicado';
      empty.focus();
    });
    doc.querySelector('#volver-informe').addEventListener('click', () => { empty.hidden = true; panel.hidden = false; doc.querySelector('#numero-dia').value = picker.dataset.dia; });
    const cards = [...doc.querySelectorAll('[data-situacion]')];
    if (!cards.length) return;
    const search = doc.querySelector('#buscar-situacion');
    const state = doc.querySelector('#estado-situacion');
    const tabs = [...doc.querySelectorAll('[data-vista]')];
    let current = 1;
    function vista(name) {
      for (const t of tabs) { const active = t.dataset.vista === name; t.setAttribute('aria-selected', String(active)); }
      doc.querySelector('#resumen').hidden = name !== 'resumen';
      doc.querySelector('#situaciones').hidden = name !== 'situaciones';
    }
    function pintar() {
      const result = seleccionar(cards.map(c => ({ id: c.id, text: c.dataset.busqueda, state: c.dataset.estado })), search.value, state.value, current, 4);
      current = result.current;
      for (const c of cards) c.hidden = !result.ids.includes(c.id);
      doc.querySelector('#pagina-situaciones').textContent = result.total ? 'Página ' + current + ' de ' + result.pages + ' · ' + result.total + ' situaciones' : 'No hay situaciones con ese filtro.';
      doc.querySelector('#anterior').disabled = current === 1;
      doc.querySelector('#siguiente').disabled = current === result.pages;
    }
    for (const t of tabs) t.addEventListener('click', () => vista(t.dataset.vista));
    for (const input of [search, state]) input.addEventListener(input === search ? 'input' : 'change', () => { current = 1; pintar(); });
    for (const [id,step] of [['#anterior',-1],['#siguiente',1]]) doc.querySelector(id).addEventListener('click', () => { current += step; pintar(); doc.querySelector('#situaciones').scrollIntoView({ block: 'start' }); });
    function abrirSituacion() {
      const raw = win.location.hash.slice(1);
      const id = raw.startsWith('situacion-') ? raw : raw.startsWith('D4-T') ? 'situacion-' + Number(raw.slice(4)) : '';
      const index = cards.findIndex(c => c.id === id);
      if (index < 0) return;
      search.value = ''; state.value = ''; current = Math.floor(index / 4) + 1;
      vista('situaciones'); pintar();
      cards[index].querySelector('details').open = true;
      cards[index].scrollIntoView({ block: 'start' });
    }
    win.addEventListener('hashchange', abrirSituacion);
    for (const link of doc.querySelectorAll('a[href^="#situacion-"]')) link.addEventListener('click', event => {
      event.preventDefault(); win.location.hash = link.getAttribute('href'); abrirSituacion();
    });
    pintar(); abrirSituacion();
  }
  return { destinoDia, seleccionar, montar };
});
if (typeof document !== 'undefined') document.addEventListener('DOMContentLoaded', () => InformeWings.montar(document, window));
