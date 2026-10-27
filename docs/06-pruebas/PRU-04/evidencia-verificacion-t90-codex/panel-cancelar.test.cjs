// Ejecuta el JS real de la ficha con un DOM mínimo y la respuesta real guardada por PHP.
// No usa navegador ni red; fetch está sustituido por una respuesta local de prueba.
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const crypto = require('node:crypto');

const root = path.resolve(__dirname, '../../../..');
const source = fs.readFileSync(path.join(root, 'resources/js/clases-show.js'), 'utf8');
const response = JSON.parse(fs.readFileSync(path.join(__dirname, 'respuesta-rechazo.json'), 'utf8'));
assert.equal(response.success, false);

function element(extra = {}) {
  return Object.assign({
    style: { display: 'none' }, textContent: '', innerHTML: '', disabled: false,
    listeners: {}, addEventListener(name, callback) { this.listeners[name] = callback; },
    focus() { this.focused = true; },
  }, extra);
}

async function test(button, serie) {
  const calls = [];
  const nodes = new Map([
    ['clase-show-datos', element({ dataset: { claseId: '90', infoSemana: '{}', correccionAsistencia: '0', correccionProfesores: '0' } })],
    ['contador-presentes', element()],
    ['btn-abrir-cancelar', element()], ['panel-cancelar', element()],
    ['btn-confirmar-cancelar', element()], ['btn-cancelar-serie', element()],
    ['btn-cerrar-cancelar', element()],
    ['input-motivo-cancelacion', element({ value: 'Prueba independiente T90' })],
    ['flash-cancelar', element()],
  ]);
  const document = {
    getElementById(id) { return nodes.get(id) ?? null; },
    querySelector(selector) {
      assert.equal(selector, 'meta[name="csrf-token"]');
      return { content: 'valor-ficticio-sin-credenciales' };
    },
    querySelectorAll(selector) {
      assert.ok(['.presente-checkbox', '.presente-checkbox:checked', '.alumno-asistencia-card'].includes(selector));
      return [];
    },
  };
  const context = vm.createContext({ document, setTimeout, clearTimeout,
    fetch: async (url, options) => {
      calls.push({ url, options });
      return { status: 422, ok: false, json: async () => response };
    },
    location: { reload() { throw new Error('No debe recargar al rechazar'); } },
  });
  vm.runInContext(source, context, { filename: 'resources/js/clases-show.js' });
  nodes.get('btn-abrir-cancelar').listeners.click();
  assert.equal(nodes.get('panel-cancelar').style.display, 'block');
  nodes.get(button).listeners.click();
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(calls.length, 1);
  assert.equal(calls[0].url, '/clases/90/cancelar');
  assert.equal(calls[0].options.method, 'PATCH');
  assert.equal(calls[0].options.headers.Accept, 'application/json');
  assert.deepEqual(JSON.parse(calls[0].options.body), { motivo_cancelacion: 'Prueba independiente T90', cancelar_serie: serie });
  const flash = nodes.get('flash-cancelar');
  assert.equal(flash.style.display, 'block');
  assert.equal(flash.textContent, '✗ ' + response.message);
  assert.equal(flash.style.color, 'var(--color-danger)');
  assert.equal(nodes.get('panel-cancelar').style.display, 'block');
  assert.equal(nodes.get('btn-confirmar-cancelar').disabled, false);
  assert.equal(nodes.get('btn-cancelar-serie').disabled, false);
  return { boton: button, cancelar_serie: serie, status_simulado: 422, texto_panel: flash.textContent, panel_permanece_abierto: true, botones_restaurados: true };
}

(async () => {
  const results = [await test('btn-confirmar-cancelar', false), await test('btn-cancelar-serie', true)];
  const data = { metodo: 'JS real ejecutado con DOM mínimo; sin navegador ni red',
    js_sha256: crypto.createHash('sha256').update(source).digest('hex'), casos: results };
  fs.writeFileSync(path.join(__dirname, 'resultado-panel.json'), JSON.stringify(data, null, 2) + '\n');
  console.log('2/2: rechazo real visible en el panel, para cancelar esta y cancelar serie; sin recargar ni ocultar el error.');
})().catch(error => { console.error(error); process.exitCode = 1; });
