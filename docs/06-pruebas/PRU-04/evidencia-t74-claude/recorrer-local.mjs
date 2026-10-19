// T74: recorrido real en Chrome sobre wings_testing_claude (servidor local en 8738, fecha simulada 05/10).
// Uso: node docs/06-pruebas/PRU-04/evidencia-t74-claude/recorrer-local.mjs
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const base = 'http://127.0.0.1:8738';
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t74-claude');
const datos = JSON.parse(fs.readFileSync('storage/app/t74-local.json', 'utf8'));
const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t74-${Date.now()}`) });
const pausa = (ms = 900) => new Promise(r => setTimeout(r, ms));
const resultado = { fecha: new Date().toISOString(), base: 'wings_testing_claude', usuario: 'OPERATIVO sintético', fecha_simulada: '2026-10-05', pasos: [] };
let page;

async function ancho(w) {
    await page.send('Emulation.setDeviceMetricsOverride', { width: w, height: 900, deviceScaleFactor: 1, mobile: w < 500 });
}
async function captura(nombre, selector) {
    if (selector) await page.evaluate(`document.querySelector(${JSON.stringify(selector)})?.scrollIntoView({block: 'center'})`);
    await pausa(300);
    const shot = await page.send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(salida, `${nombre}.png`), Buffer.from(shot.data, 'base64'));
}
const texto = () => page.evaluate('document.body.innerText');
// Lee el número grande de la tarjeta cuyo rótulo es `rotulo`.
const tarjeta = (rotulo) => page.evaluate(`(() => {
    const a = [...document.querySelectorAll('a.filtros-card')].find(x => x.querySelector('p')?.innerText.trim().toUpperCase() === ${JSON.stringify(rotulo.toUpperCase())});
    return a ? { numero: Number(a.querySelectorAll('p')[1].innerText.trim()), href: a.getAttribute('href') } : null;
})()`);

try {
    await browser.start();
    page = await browser.newPage();
    await page.connect();

    await ancho(1366);
    await page.goto(`${base}/login`);
    await page.type('input[name=email]', datos.email);
    await page.type('input[name=password]', datos.password);
    await page.click('button[type=submit]');
    await pausa();
    assert.notEqual(await page.evaluate('location.pathname'), '/login', 'No pudo ingresar: el servidor no está en la base descartable');

    await page.goto(`${base}/operativo`);
    const vencidos = await tarjeta('Vencidos');
    const enPlazo = await tarjeta('En plazo');
    assert(vencidos && enPlazo, 'Faltan las tarjetas Vencidos / En plazo');
    assert(!(await texto()).includes('CON DEUDA'), 'Quedó la tarjeta vieja «Con deuda»');
    resultado.pasos.push({ paso: 'inicio', vencidos, enPlazo });
    await captura('01-inicio-1366', 'a.filtros-card');

    await ancho(375);
    await page.goto(`${base}/operativo`);
    assert(await page.evaluate('document.documentElement.scrollWidth <= window.innerWidth + 1'), 'El Inicio desborda a 375');
    await captura('02-inicio-375', 'a.filtros-card');

    await ancho(1366);
    await page.goto(`${base}${vencidos.href.replace(base, '')}`);
    await captura('03-cobranza-vencidos-1366');
    await page.goto(`${base}${enPlazo.href.replace(base, '')}`);
    await captura('04-cobranza-en-plazo-1366');

    await page.goto(`${base}/caja/cobrar`);
    const encabezado = (await texto()).split('\n').find(l => l.includes('con deuda pendiente')) ?? '';
    const anunciados = Number((encabezado.match(/(\d+)/) ?? [])[1]);
    resultado.pasos.push({ paso: 'cobrar', encabezado, anunciados });
    assert.equal(anunciados, vencidos.numero + enPlazo.numero, 'Vencidos + en plazo no da lo del buscador de Cobrar');
    await captura('05-cobrar-buscador-1366');

    fs.writeFileSync(path.join(salida, 'resultado-navegador.json'), JSON.stringify(resultado, null, 2));
    console.log(JSON.stringify(resultado, null, 2));
} finally {
    if (page) await page.close();
    await browser.close();
}
