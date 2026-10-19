// T75: control de la interacción de Revisión en Chrome real, sobre wings_testing_claude (puerto 8738).
// Uso: node docs/06-pruebas/PRU-04/evidencia-t75-claude/recorrer-local.mjs
import fs from 'node:fs';
import path from 'node:path';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const base = 'http://127.0.0.1:8738';
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t75-claude');
const datos = JSON.parse(fs.readFileSync('storage/app/t75-local.json', 'utf8'));
const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t75-${Date.now()}`) });
const pausa = (ms = 900) => new Promise(r => setTimeout(r, ms));
const r = { fecha: new Date().toISOString(), base: 'wings_testing_claude', observaciones: [] };
let page;

const estado = (rev) => page.evaluate(`(() => {
    const f = document.querySelector('form[action$="/revision-cobranza/${rev}/resolver"]');
    if (!f) return null;
    return { interruptor: f.querySelector('[data-revision-estado] input').checked,
             oculto: f.elements.resolucion.value,
             aviso: f.querySelector('[data-revision-consecuencia]').textContent.trim() };
})()`);
async function captura(nombre) {
    const shot = await page.send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(salida, `${nombre}.png`), Buffer.from(shot.data, 'base64'));
}

try {
    await browser.start();
    page = await browser.newPage();
    await page.connect();
    await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 900, deviceScaleFactor: 1, mobile: false });
    await page.goto(`${base}/login`);
    await page.type('input[name=email]', datos.email);
    await page.type('input[name=password]', datos.password);
    await page.click('button[type=submit]');
    await pausa();
    if (await page.evaluate('location.pathname') === '/login') throw new Error('No pudo ingresar');

    const bruno = datos.ids.Bruno.revision;
    await page.goto(`${base}/revision-cobranza`);
    r.observaciones.push({ paso: 'al abrir', ...(await estado(bruno)) });

    // Mover el interruptor de Bruno con un clic real sobre su etiqueta.
    await page.evaluate(`document.querySelector('form[action$="/revision-cobranza/${bruno}/resolver"] [data-revision-estado]').click()`);
    await pausa(300);
    r.observaciones.push({ paso: 'interruptor apagado', ...(await estado(bruno)) });
    await captura('01-revision-interruptor-apagado-1366');

    // Ir a otra pantalla y volver con Atrás: ¿quedan iguales interruptor y resolución enviada?
    await page.goto(`${base}/alumnos`);
    const historia = await page.send('Page.getNavigationHistory');
    const cargada = new Promise(res => page.on('Page.loadEventFired', res));
    await page.send('Page.navigateToHistoryEntry', { entryId: historia.entries[historia.currentIndex - 1].id });
    await Promise.race([cargada, pausa(4000)]);
    await pausa(800);
    r.observaciones.push({ paso: 'despues de Atras', ...(await estado(bruno)) });
    await captura('02-revision-despues-de-atras-1366');

    fs.writeFileSync(path.join(salida, 'resultado-navegador.json'), JSON.stringify(r, null, 2));
    console.log(JSON.stringify(r, null, 2));
} finally {
    if (page) await page.close();
    await browser.close();
}
