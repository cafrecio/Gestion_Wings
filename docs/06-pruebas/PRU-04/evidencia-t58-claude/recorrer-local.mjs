// T58: recorrido real en Chrome sobre wings_testing_claude (servidor local en 8738).
// Uso: node docs/06-pruebas/PRU-04/evidencia-t58-claude/recorrer-local.mjs
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const base = 'http://127.0.0.1:8738';
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t58-claude');
const datos = JSON.parse(fs.readFileSync('storage/app/t58-local.json', 'utf8'));
const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t58-${Date.now()}`) });
const pausa = (ms = 900) => new Promise(r => setTimeout(r, ms));
const resultado = { fecha: new Date().toISOString(), base: 'wings_testing_claude', usuario: 'ADMIN sintético', pasos: [] };
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

try {
    await browser.start();
    page = await browser.newPage();
    await page.connect();
    const dialogos = [];
    page.on('Page.javascriptDialogOpening', (p) => {
        dialogos.push(p.message);
        page.send('Page.handleJavaScriptDialog', { accept: true });
    });

    await ancho(1366);
    await page.goto(`${base}/login`);
    await page.type('input[name=email]', datos.email);
    await page.type('input[name=password]', datos.password);
    await page.click('button[type=submit]');
    await pausa();
    assert.notEqual(await page.evaluate('location.pathname'), '/login', 'No pudo ingresar: el servidor no está en la base descartable');

    await page.goto(`${base}/alumnos/${datos.alumno}`);
    assert((await texto()).includes('Meses sin cuota generada'));
    await captura('01-ficha-mes-faltante-1366', '#cuotas-pasadas');

    await ancho(375);
    await page.goto(`${base}/alumnos/${datos.alumno}`);
    assert(await page.evaluate('document.documentElement.scrollWidth <= window.innerWidth + 1'), 'La ficha desborda a 375');
    await captura('02-ficha-mes-faltante-375', '#cuotas-pasadas');

    await ancho(1366);
    await page.goto(`${base}/alumnos/${datos.alumno}`);
    await page.click('#cuotas-pasadas button[type=submit]');
    await pausa(1500);
    const ficha = await texto();
    assert.equal(dialogos.length, 1, 'Crear debía pedir confirmación');
    assert(/Se creó la cuota de \w+ \d{4}: \$12,000\.00\./.test(ficha), 'Falta el aviso de la cuota creada');
    assert(!ficha.includes('Meses sin cuota generada'), 'El bloque debía desaparecer al no quedar meses');
    resultado.pasos.push({ paso: 'crear', confirmacion: dialogos[0], aviso: ficha.split('\n').find(l => l.includes('Se creó la cuota')) });
    await captura('03-ficha-cuota-creada-1366');

    fs.writeFileSync(path.join(salida, 'resultado-navegador.json'), JSON.stringify(resultado, null, 2));
    console.log(JSON.stringify(resultado, null, 2));
} finally {
    if (page) await page.close();
    await browser.close();
}
