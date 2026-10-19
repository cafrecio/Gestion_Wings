// T59 y T47: recorrido real en Chrome sobre wings_testing_claude (servidor local en 8738).
// Uso: node docs/06-pruebas/PRU-04/evidencia-t59-t47-claude/recorrer-local.mjs
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const base = 'http://127.0.0.1:8738';
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t59-t47-claude');
const datos = JSON.parse(fs.readFileSync('storage/app/t59t47-local.json', 'utf8'));
const pausa = (ms = 900) => new Promise(r => setTimeout(r, ms));
const resultado = { fecha: new Date().toISOString(), base: 'wings_testing_claude', pasos: [] };

async function sesion(usuario, recorrido) {
    const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t59t47-${Date.now()}`) });
    let page;
    try {
        await browser.start();
        page = await browser.newPage();
        await page.connect();
        const ancho = (w) => page.send('Emulation.setDeviceMetricsOverride', { width: w, height: 900, deviceScaleFactor: 1, mobile: w < 500 });
        const captura = async (nombre) => {
            await pausa(300);
            const shot = await page.send('Page.captureScreenshot', { format: 'png' });
            fs.writeFileSync(path.join(salida, `${nombre}.png`), Buffer.from(shot.data, 'base64'));
        };
        await ancho(1366);
        await page.goto(`${base}/login`);
        await page.type('input[name=email]', usuario.email);
        await page.type('input[name=password]', usuario.password);
        await page.click('button[type=submit]');
        await pausa();
        assert.notEqual(await page.evaluate('location.pathname'), '/login', 'No pudo ingresar');
        await recorrido({ page, ancho, captura, texto: () => page.evaluate('document.body.innerText') });
    } finally {
        if (page) await page.close();
        await browser.close();
    }
}

await sesion(datos.dueno, async ({ page, ancho, captura, texto }) => {
    await page.goto(`${base}/caja/movimiento`);
    await pausa();
    const destino = await page.evaluate('location.pathname');
    assert.equal(destino, '/cashflow/movimiento', 'T59: el dueño debía terminar en Cashflow');
    assert(!(await texto()).includes('Debés abrir la caja'));
    resultado.pasos.push({ paso: 'T59 dueño abre /caja/movimiento', destino });
    await captura('01-t59-duenio-va-a-cashflow-1366');

    await page.goto(`${base}/deudas/1/condonar`);
    const t = await texto();
    assert(t.includes('Esta dirección no se abre a mano') && !t.includes('Method Not Allowed'));
    resultado.pasos.push({ paso: 'T47 dueño abre /deudas/1/condonar', aviso: 'Esta dirección no se abre a mano' });
    await captura('02-t47-direccion-de-accion-1366');
    await ancho(375);
    await page.goto(`${base}/deudas/1/condonar`);
    assert(await page.evaluate('document.documentElement.scrollWidth <= window.innerWidth + 1'), 'Desborda a 375');
    await captura('03-t47-direccion-de-accion-375');
});

await sesion(datos.operativa, async ({ page, captura, texto }) => {
    await page.goto(`${base}/cajas/${datos.caja}/validar`);
    assert((await texto()).includes('Esta dirección no se abre a mano'));
    resultado.pasos.push({ paso: `T47 operativa abre /cajas/${datos.caja}/validar`, aviso: 'Esta dirección no se abre a mano' });
    await captura('04-t47-operativa-validar-1366');
    await page.click('a.ds-btn');
    await pausa();
    const volver = await page.evaluate('location.pathname');
    assert.equal(volver, '/operativo', 'Volver debía llevar al Inicio de la operativa');
    resultado.pasos.push({ paso: 'T47 Volver', destino: volver });
});

fs.writeFileSync(path.join(salida, 'resultado-navegador.json'), JSON.stringify(resultado, null, 2) + '\n');
console.log(JSON.stringify(resultado, null, 2));
