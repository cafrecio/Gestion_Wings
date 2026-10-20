// T52: recorrido real en Chrome sobre wings_testing_claude (servidor local en 8738).
// Uso: node docs/06-pruebas/PRU-04/evidencia-t52-claude/recorrer-local.mjs
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const base = 'http://127.0.0.1:8738';
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t52-claude');
const datos = JSON.parse(fs.readFileSync('storage/app/t52-local.json', 'utf8'));
const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t52-${Date.now()}`) });
const pausa = (ms = 900) => new Promise(r => setTimeout(r, ms));
const resultado = { fecha: new Date().toISOString(), base: 'wings_testing_claude', usuario: 'OPERATIVO sintético', pasos: [] };
let page;

const ancho = (w) => page.send('Emulation.setDeviceMetricsOverride', { width: w, height: 900, deviceScaleFactor: 1, mobile: w < 500 });
async function captura(nombre, selector) {
    if (selector) await page.evaluate(`document.querySelector(${JSON.stringify(selector)})?.scrollIntoView({block: 'start'})`);
    await pausa(300);
    const shot = await page.send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(salida, `${nombre}.png`), Buffer.from(shot.data, 'base64'));
}
const texto = () => page.evaluate('document.body.innerText');

try {
    await browser.start();
    page = await browser.newPage();
    await page.connect();
    const consola = [];
    page.on('Runtime.consoleAPICalled', (p) => { if (['error', 'warning'].includes(p.type)) consola.push(p.args.map(a => a.value).join(' ')); });
    page.on('Runtime.exceptionThrown', (p) => consola.push(p.exceptionDetails.text));
    await page.send('Runtime.enable');

    await ancho(1366);
    await page.goto(`${base}/login`);
    await page.type('input[name=email]', datos.email);
    await page.type('input[name=password]', datos.password);
    await page.click('button[type=submit]');
    await pausa();
    assert.notEqual(await page.evaluate('location.pathname'), '/login', 'No pudo ingresar');

    await page.goto(`${base}/clases/${datos.clase}`);
    const t = await texto();
    for (const n of ['Cambio, Degrupo', 'Baja, Deayer', 'Ausente, Marcada', 'Sigue, Enelgrupo']) assert(t.includes(n), `Falta ${n}`);
    assert(t.includes('Cambió de grupo') && t.includes('De baja') && t.includes('ya no están'));
    const tarjetas = await page.evaluate('document.querySelectorAll(".alumno-asistencia-card").length');
    resultado.pasos.push({ paso: 'lista vieja', tarjetas, encabezado: t.split('\n').find(l => l.includes('alumnos en el grupo')) });
    await captura('01-clase-vieja-1366', '.stats-bar');

    await ancho(375);
    await page.goto(`${base}/clases/${datos.clase}`);
    assert(await page.evaluate('document.documentElement.scrollWidth <= window.innerWidth + 1'), 'Desborda a 375');
    await captura('02-clase-vieja-375', '.stats-bar');

    // Corrección con clics: la ausente estaba mal marcada.
    await ancho(1366);
    await page.goto(`${base}/clases/${datos.clase}`);
    await page.click(`#presente-${datos.ausente}`);
    await page.type('#input-motivo-asistencias', 'Se marcó mal a una alumna');
    await page.click('#btn-guardar-asistencias');
    await pausa(1500);
    const aviso = await page.evaluate('document.getElementById("toast-asistencias").innerText');
    assert(aviso.includes('Asistencias guardadas.'), `Aviso inesperado: ${aviso}`);
    await page.goto(`${base}/clases/${datos.clase}`);
    const marcadas = await page.evaluate('[...document.querySelectorAll(".presente-checkbox:checked")].length');
    assert.equal(marcadas, 4, 'Después de corregir debían quedar 4 presentes');
    resultado.pasos.push({ paso: 'corrección', aviso: aviso.trim(), presentes_despues: marcadas });
    await captura('03-clase-vieja-corregida-1366', '.stats-bar');

    resultado.consola = consola;
    assert.equal(consola.length, 0, `Consola con errores: ${consola.join(' | ')}`);
    fs.writeFileSync(path.join(salida, 'resultado-navegador.json'), JSON.stringify(resultado, null, 2) + '\n');
    console.log(JSON.stringify(resultado, null, 2));
} finally {
    if (page) await page.close();
    await browser.close();
}
