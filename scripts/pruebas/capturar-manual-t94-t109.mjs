// Capturas del manual que T94 a T109 dejaron viejas, tomadas del Wings real en un navegador, sin agregar texto.
// Solo se marca con un contorno el botón del paso. Corre contra una copia descartable servida en el puerto 8123:
// restaurar simulacion/tanda-5/fotos/d54 en wings_testing_guion, correr la rutina de la mañana del día 55, darles esta clave
// a Sandra y al dueño, y servir public/ con DB_DATABASE=wings_testing_guion. Uso: node <este archivo> <caja abierta> <id de Liam>
import path from 'node:path';
import { ChromeBrowser } from './cdp-helper.mjs';

const base = 'http://127.0.0.1:8123';
const clave = 'Copia-descartable-2026!';
const img = (r) => path.resolve('resources/manual/img', r);
const esperar = (ms) => new Promise((r) => setTimeout(r, ms));

const navegador = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-manual-${Date.now()}`) });
await navegador.start();
const pagina = await navegador.newPage();
await pagina.connect();

async function ancho(alto = 950) {
    await pagina.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: alto, deviceScaleFactor: 1, mobile: false });
}

async function entrar(email) {
    await pagina.send('Network.enable');
    await pagina.send('Network.clearBrowserCookies');
    await pagina.goto(`${base}/login`);
    await pagina.evaluate(`(() => { document.querySelector('[name=email]').value = ${JSON.stringify(email)}; document.querySelector('[name=password]').value = ${JSON.stringify(clave)}; document.querySelector('button[type=submit]').click(); })()`);
    await esperar(1500);
    const url = await pagina.evaluate('location.pathname');
    if (url === '/login') throw new Error(`No entró ${email}`);
}

async function ir(uri) {
    await pagina.goto(base + uri);
    await esperar(600);
    const titulo = await pagina.evaluate('document.title');
    if (/Página no disponible|Sin permiso|Error/.test(titulo)) throw new Error(`${uri}: ${titulo}`);
}

const marcar = (selector, color = 'var(--color-danger)') => pagina.evaluate(`(() => { const b = document.querySelector(${JSON.stringify(selector)}); if (!b) throw new Error('sin ' + ${JSON.stringify(selector)}); b.style.outline = '3px solid ${color}'; b.style.outlineOffset = '2px'; b.style.borderRadius = '4px'; b.scrollIntoView({ block: 'center' }); })()`);
const tocar = (selector) => pagina.evaluate(`document.querySelector(${JSON.stringify(selector)}).click()`);

async function poner(selector, valor) {
    await pagina.evaluate(`(() => { const c = document.querySelector(${JSON.stringify(selector)}); c.value = ${JSON.stringify(valor)}; c.dispatchEvent(new Event('input', { bubbles: true })); c.dispatchEvent(new Event('change', { bubbles: true })); c.dispatchEvent(new Event('blur', { bubbles: true })); })()`);
    await esperar(250);
}

const cajaAbierta = process.argv[2];
const liam = process.argv[3];
await ancho();

// Sandra: cancelar un cobro de cuota, con la caja abierta de verdad.
await entrar('sandra.vidal@wings.test');
await ir(`/caja/${cajaAbierta}/detalle`);
await marcar('[data-abrir-cancelar]');
await pagina.screenshot(img('operativo/cancelar-movimiento-caja-paso2.png'));
await tocar('[data-abrir-cancelar]');
await esperar(300);
await pagina.evaluate(`document.querySelector('[data-abrir-cancelar]').style.outline = ''`);
await pagina.screenshot(img('operativo/cancelar-movimiento-caja-paso3.png'));

// Sandra: el alta con grupo y frecuencia elegidos, y el aviso de inscripción que responde Wings.
await ir('/alumnos/create');
await poner('#nombre', 'Benicio');
await poner('#apellido', 'Luna');
await poner('#dni', '58123456');
await poner('#fecha_nacimiento', '2014-06-02');
await poner('#fecha_alta', '2026-12-03');
await poner('#nombre_tutor', 'Marta Luna');
await poner('#telefono_tutor', '1166112233');
await pagina.evaluate(`(() => { const d = document.querySelector('#deporte_id'); d.value = [...d.options].find((o) => o.text.includes('Patín')).value; d.dispatchEvent(new Event('change', { bubbles: true })); })()`);
await esperar(400);
await pagina.evaluate(`(() => { const g = document.querySelector('#grupo_id'); g.value = [...g.options].find((o) => o.text.includes('Patín') && o.text.includes('Principiantes')).value; g.dispatchEvent(new Event('change', { bubbles: true })); })()`);
await esperar(400);
await pagina.evaluate(`(() => { const p = document.querySelector('#plan_id'); p.value = [...p.options].find((o) => o.value).value; p.dispatchEvent(new Event('change', { bubbles: true })); })()`);
await esperar(1500);
const aviso = await pagina.evaluate(`document.body.innerText.includes('No se pudo consultar')`);
if (aviso) throw new Error('El alta sigue mostrando el error de consulta');
await pagina.evaluate(`[...document.querySelectorAll('button[type=submit]')].pop().scrollIntoView({ block: 'end' })`);
await esperar(300);
await pagina.screenshot(img('operativo/alta-alumno-paso3.png'));

// El dueño: el panel de condonar, un grupo nuevo y Cashflow con el catálogo real.
await entrar('admin@wings.test');
await ir(`/alumnos/${liam}`);
await tocar('[data-abrir-condonar]');
await esperar(400);
await pagina.screenshot(img('admin/condonar-deuda-alumno-paso1.png'));
await ir('/grupos/create');
await pagina.screenshot(img('admin/administrar-grupos-paso1.png'));
await ir('/cashflow');
await pagina.screenshot(img('admin/consultar-cashflow-paso1.png'));
await pagina.evaluate(`[...document.querySelectorAll('span, p, div')].find((e) => e.children.length === 0 && /movimientos$/.test(e.textContent.trim()) || e.children.length === 0 && e.textContent.trim() === 'RESULTADO DEL PERÍODO' || e.children.length === 0 && /Resultado del período/i.test(e.textContent)).scrollIntoView({ block: 'start' }); window.scrollBy(0, -120);`);
await esperar(200);
await pagina.screenshot(img('admin/consultar-cashflow-paso2.png'));

await pagina.close();
await navegador.close();
console.log('capturas listas');
