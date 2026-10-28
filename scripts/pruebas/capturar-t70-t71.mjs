import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import { ChromeBrowser } from './cdp-helper.mjs';

const root = path.resolve('.');
const dir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t70-t71');
const perfil = path.resolve('storage/app/chrome-t70-t71');
if (!dir.startsWith(root + path.sep) || !perfil.startsWith(root + path.sep)) throw new Error('Destino fuera del repositorio');
const paginas = ['00-login', '01-reporte-alumnos', '02-cobranza', '03-reportes', '04-ficha-perdida', '05-cobranza-operativo'];
if (fs.readFileSync(path.join(dir, '00-login.html'), 'utf8').includes('Redirecting')) throw new Error('La calibración debe ser el login real, no una redirección');
const tipos = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.woff2': 'font/woff2', '.png': 'image/png', '.svg': 'image/svg+xml', '.ico': 'image/x-icon', '.json': 'application/json' };
const server = http.createServer((req, res) => {
    const archivo = path.resolve(root, '.' + decodeURIComponent(new URL(req.url, 'http://localhost').pathname));
    const permitido = [dir, path.join(root, 'public')].some(base => archivo.startsWith(base + path.sep));
    if (req.method !== 'GET' || !permitido || !fs.existsSync(archivo) || !fs.statSync(archivo).isFile()) { res.writeHead(404); res.end(); return; }
    res.setHeader('Content-Type', tipos[path.extname(archivo)] ?? 'application/octet-stream');
    fs.createReadStream(archivo).pipe(res);
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const browser = new ChromeBrowser({ userDataDir: perfil });
const evidencia = [];
try {
    await browser.start();
    const page = await browser.newPage();
    await page.connect();
    const errores = [];
    page.on('Runtime.exceptionThrown', e => errores.push(e.exceptionDetails?.text));
    for (const nombre of paginas) {
        const selector = /cobranza|^03-reportes/.test(nombre) ? '[data-deuda-inactivos]' : null;
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 950, deviceScaleFactor: 1, mobile: false });
        await page.goto(`${origin}/docs/06-pruebas/PRU-04/evidencia-t70-t71/${nombre}.html`);
        await page.evaluate(async () => { await document.fonts.ready; });
        if (selector) await page.evaluate(s => document.querySelector(s).scrollIntoView({ block: 'start' }), selector);
        await page.screenshot(path.join(dir, `${nombre}-1366.png`));
        const marco = `<!doctype html><html lang="es"><meta charset="utf-8"><title>Wings · marco real 375 px</title><body style="margin:0;background:#ddd;font-family:Arial;text-align:center"><p>Wings · contenido real a 375 px</p><iframe title="Wings a 375 px" src="${nombre}.html" style="width:375px;height:950px;border:6px solid #333;border-radius:16px;background:white"></iframe></body></html>`;
        fs.writeFileSync(path.join(dir, `${nombre}-marco-375.html`), marco);
        await page.send('Emulation.setDeviceMetricsOverride', { width: 800, height: 1050, deviceScaleFactor: 1, mobile: false });
        await page.goto(`${origin}/docs/06-pruebas/PRU-04/evidencia-t70-t71/${nombre}-marco-375.html`);
        await page.evaluate(async () => { await document.querySelector('iframe').contentDocument.fonts.ready; });
        const selectorMovil = nombre === '01-reporte-alumnos' ? '.rv-pair' : selector;
        if (selectorMovil) await page.evaluate(s => {
            const marco = document.querySelector('iframe');
            marco.contentDocument.querySelector(s).scrollIntoView({ block: 'start' });
            if (s === '.rv-pair') marco.contentWindow.scrollBy(0, -80);
        }, selectorMovil);
        const medidas = await page.evaluate(() => {
            const f = document.querySelector('iframe'); const d = f.contentDocument;
            return { marco: f.clientWidth, ancho: d.documentElement.clientWidth, scroll: d.documentElement.scrollWidth,
                botones: [...d.querySelectorAll('[data-deuda-inactivos] .ds-btn')].map(b => ({ texto: b.textContent.trim(), ancho: b.getBoundingClientRect().width, alto: b.getBoundingClientRect().height })) };
        });
        if (medidas.marco !== 375 || medidas.scroll > 375) throw new Error(`${nombre}: ancho inválido ${JSON.stringify(medidas)}`);
        await page.screenshot(path.join(dir, `${nombre}-375.png`));
        evidencia.push({ pagina: nombre, ...medidas });
        console.log(`Capturada ${nombre}: 1366 y marco375`);
    }
    // Prueba del cliente, posterior a todas las capturas y sin tocar ninguna base.
    // El servidor real se ejercita en InactivosT70T71Test; aquí se simulan sus respuestas.
    await page.goto(`${origin}/docs/06-pruebas/PRU-04/evidencia-t70-t71/02-cobranza.html`);
    const cliente = await page.evaluate(async () => {
        const esperar = ms => new Promise(resolve => setTimeout(resolve, ms));
        const seccion = document.querySelector('[data-deuda-inactivos]');
        const form = seccion.querySelector('[data-perder-deuda]');
        const fila = form.closest('[data-inactivo-fila]');
        const boton = form.querySelector('button');
        const original = seccion.querySelector('[data-inactivos-total]').textContent;
        const cantidad = seccion.querySelectorAll('[data-inactivo-fila]').length;
        document.documentElement.dataset.testT71 = 'sin-navegacion';
        window.confirm = () => true;
        let pedidos = 0;
        window.fetch = async () => { pedidos++; return { ok: false, json: async () => ({ errors: { deuda: ['La deuda cambió. Actualizá la lista.'] } }) }; };
        boton.click();
        await esperar(100);
        const rechazo = { filaIntacta: fila.isConnected, totalIntacto: seccion.querySelector('[data-inactivos-total]').textContent === original,
            aviso: fila.querySelector('[data-inactivo-error]').textContent, habilitado: !boton.disabled };
        window.fetch = async () => { pedidos++; await esperar(200); return { ok: true, json: async () => ({ mensaje: 'Deuda dada por perdida.' }) }; };
        boton.click(); boton.click();
        await esperar(350);
        return { rechazo, pedidos, filasAntes: cantidad, filasDespues: seccion.querySelectorAll('[data-inactivo-fila]').length,
            totalDespues: seccion.querySelector('[data-inactivos-total]').textContent,
            sinNavegacion: document.documentElement.dataset.testT71 === 'sin-navegacion' };
    });
    fs.writeFileSync(path.join(dir, 'interaccion-cliente.json'), JSON.stringify(cliente, null, 2) + '\n');
    if (!cliente.rechazo.filaIntacta || !cliente.rechazo.totalIntacto || !cliente.rechazo.habilitado || !cliente.sinNavegacion
        || cliente.pedidos !== 2 || cliente.filasDespues !== cliente.filasAntes - 1) throw new Error('Falló la prueba de cliente: ' + JSON.stringify(cliente));
    fs.writeFileSync(path.join(dir, 'medidas-chrome.json'), JSON.stringify({ evidencia, errores }, null, 2) + '\n');
    if (errores.length) throw new Error(`Errores de JavaScript: ${errores.join(', ')}`);
    await page.close();
} finally {
    await browser.close();
    await new Promise(resolve => server.close(resolve));
}
