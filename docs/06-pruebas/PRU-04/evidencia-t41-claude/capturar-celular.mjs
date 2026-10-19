// T41: capturas de la ficha de liquidación en celular, sobre el código actual (puerto 8738).
// Uso: node docs/06-pruebas/PRU-04/evidencia-t41-claude/capturar-celular.mjs <sufijo> <liquidacion:...>
import fs from 'node:fs';
import path from 'node:path';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const base = 'http://127.0.0.1:8738';
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t41-claude');
const datos = JSON.parse(fs.readFileSync('storage/app/t41-local.json', 'utf8'));
const [, , sufijo, ...pedidas] = process.argv;
const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t41-${Date.now()}`) });
const pausa = (ms = 900) => new Promise(r => setTimeout(r, ms));
let page;

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

    for (const pedido of pedidas) {
        const [mes, ancho] = pedido.split(':');
        await page.send('Emulation.setDeviceMetricsOverride', { width: Number(ancho), height: 820, deviceScaleFactor: 2, mobile: true });
        await page.goto(`${base}/liquidaciones/${datos[mes]}`);
        const desborda = await page.evaluate('document.documentElement.scrollWidth > window.innerWidth + 1');
        const shot = await page.send('Page.captureScreenshot', { format: 'png' });
        const nombre = `liquidacion-${mes}-${sufijo}-${ancho}.png`;
        fs.writeFileSync(path.join(salida, nombre), Buffer.from(shot.data, 'base64'));
        console.log(nombre, desborda ? 'DESBORDA' : 'sin desborde');
    }
} finally {
    if (page) await page.close();
    await browser.close();
}
