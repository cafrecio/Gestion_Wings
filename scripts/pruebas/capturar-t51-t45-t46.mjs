import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import { ChromeBrowser } from './cdp-helper.mjs';

const tarea = process.argv[2];
if (!['t51', 't45', 't46'].includes(tarea)) throw new Error('Indicar t51, t45 o t46');
const root = path.resolve('.');
const dir = path.resolve(`docs/06-pruebas/PRU-04/evidencia-${tarea}`);
const perfil = path.resolve(`storage/app/chrome-${tarea}`);
if (![dir, perfil].every(p => p.startsWith(root + path.sep))) throw new Error('Destino fuera del repositorio');
const paginas = fs.readdirSync(dir).filter(f => /^\d\d-.*\.html$/.test(f) && !f.includes('-marco-')).map(f => f.slice(0, -5));
if (!paginas.includes('00-login')) throw new Error('Falta login para calibrar');
const tipos = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.woff2': 'font/woff2', '.png': 'image/png', '.svg': 'image/svg+xml', '.ico': 'image/x-icon', '.json': 'application/json' };
const server = http.createServer((req, res) => {
    const archivo = path.resolve(root, '.' + decodeURIComponent(new URL(req.url, 'http://localhost').pathname));
    if (req.method !== 'GET' || ![dir, path.join(root, 'public')].some(p => archivo.startsWith(p + path.sep)) || !fs.existsSync(archivo) || !fs.statSync(archivo).isFile()) { res.writeHead(404); res.end(); return; }
    res.setHeader('Content-Type', tipos[path.extname(archivo)] ?? 'application/octet-stream');
    fs.createReadStream(archivo).pipe(res);
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origen = `http://127.0.0.1:${server.address().port}/docs/06-pruebas/PRU-04/evidencia-${tarea}/`;
const browser = new ChromeBrowser({ userDataDir: perfil });
const medidas = [], errores = [];
try {
    await browser.start();
    const page = await browser.newPage();
    await page.connect();
    page.on('Runtime.exceptionThrown', e => errores.push(e.exceptionDetails?.text));
    for (const nombre of paginas) {
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 950, deviceScaleFactor: 1, mobile: false });
        await page.goto(origen + nombre + '.html');
        await page.evaluate(async () => {
            await document.fonts.ready;
            document.querySelector('#historia-correcciones')?.scrollIntoView({ block: 'start' });
            if (document.querySelector('#historia-correcciones')) window.scrollBy(0, -80);
        });
        await page.screenshot(path.join(dir, `${nombre}-1366.png`));
        const marco = `<!doctype html><html lang="es"><meta charset="utf-8"><title>Wings a 375 px</title><body style="margin:0;background:#ddd;font-family:Arial;text-align:center"><p>Wings · contenido real a 375 px</p><iframe title="Wings a 375 px" src="${nombre}.html" style="width:375px;height:950px;border:6px solid #333;border-radius:16px;background:white"></iframe></body></html>`;
        fs.writeFileSync(path.join(dir, `${nombre}-marco-375.html`), marco);
        await page.send('Emulation.setDeviceMetricsOverride', { width: 800, height: 1050, deviceScaleFactor: 1, mobile: false });
        await page.goto(origen + nombre + '-marco-375.html');
        const medida = await page.evaluate(async () => {
            const f = document.querySelector('iframe'), d = f.contentDocument;
            await d.fonts.ready;
            if (d.querySelector('#historia-correcciones')) {
                d.querySelector('#historia-correcciones').scrollIntoView({ block: 'start' });
                f.contentWindow.scrollBy(0, -80);
            }
            return { marco: f.clientWidth, ancho: d.documentElement.clientWidth, scroll: d.documentElement.scrollWidth };
        });
        if (medida.marco !== 375 || medida.scroll > 375) throw new Error(`${nombre}: ${JSON.stringify(medida)}`);
        await page.screenshot(path.join(dir, `${nombre}-375.png`));
        medidas.push({ pagina: nombre, ...medida });
        console.log(`Capturada ${nombre}: 1366 y marco 375`);
    }
    fs.writeFileSync(path.join(dir, 'medidas-chrome.json'), JSON.stringify({ medidas, errores }, null, 2) + '\n');
    if (errores.length) throw new Error(errores.join(', '));
    await page.close();
} finally {
    await browser.close();
    await new Promise(resolve => server.close(resolve));
}
