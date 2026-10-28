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
        if (nombre === '05-profesora-nueva') await page.evaluate(() => {
            const select = document.getElementById('deporte_id');
            select.selectedIndex = 1; select.dispatchEvent(new Event('change', { bubbles: true }));
        });
        await page.evaluate(async () => {
            await document.fonts.ready;
            const destino = document.querySelector('#historia-correcciones') ?? document.querySelector('#profesor-vigencia');
            destino?.scrollIntoView({ block: 'start' });
            if (destino) window.scrollBy(0, -100);
        });
        await page.screenshot(path.join(dir, `${nombre}-1366.png`));
        const marco = `<!doctype html><html lang="es"><meta charset="utf-8"><title>Wings a 375 px</title><body style="margin:0;background:#ddd;font-family:Arial;text-align:center"><p>Wings · contenido real a 375 px</p><iframe title="Wings a 375 px" src="${nombre}.html" style="width:375px;height:950px;border:6px solid #333;border-radius:16px;background:white"></iframe></body></html>`;
        fs.writeFileSync(path.join(dir, `${nombre}-marco-375.html`), marco);
        await page.send('Emulation.setDeviceMetricsOverride', { width: 800, height: 1050, deviceScaleFactor: 1, mobile: false });
        await page.goto(origen + nombre + '-marco-375.html');
        const medida = await page.evaluate(async seleccionar => {
            const f = document.querySelector('iframe'), d = f.contentDocument;
            await d.fonts.ready;
            if (seleccionar) {
                const select = d.getElementById('deporte_id');
                select.selectedIndex = 1; select.dispatchEvent(new f.contentWindow.Event('change', { bubbles: true }));
            }
            const destino = d.querySelector('#historia-correcciones') ?? d.querySelector('#profesor-vigencia');
            if (destino) {
                destino.scrollIntoView({ block: 'start' });
                f.contentWindow.scrollBy(0, -100);
            }
            return { marco: f.clientWidth, ancho: d.documentElement.clientWidth, scroll: d.documentElement.scrollWidth };
        }, nombre === '05-profesora-nueva');
        if (medida.marco !== 375 || medida.scroll > 375) throw new Error(`${nombre}: ${JSON.stringify(medida)}`);
        await page.screenshot(path.join(dir, `${nombre}-375.png`));
        medidas.push({ pagina: nombre, ...medida });
        console.log(`Capturada ${nombre}: 1366 y marco 375`);
    }
    if (tarea === 't45') {
        await page.goto(origen + '03-liquidacion-porcentaje.html');
        const periodos = await page.evaluate(() => {
            const mes = document.getElementById('mes'), fila = document.querySelector('.profesor-radio-row');
            const leer = () => ({ tipo: fila.querySelector('.profesor-tipo').textContent.trim(), aviso: !fila.querySelector('.profesor-sin-porcentaje').hidden });
            mes.value = '10'; mes.dispatchEvent(new Event('change', { bubbles: true })); const octubre = leer();
            mes.value = '11'; mes.dispatchEvent(new Event('change', { bubbles: true }));
            return { octubre, noviembre: leer() };
        });
        await page.goto(origen + '04-profesora-editar.html');
        const ficha = await page.evaluate(() => {
            const hora = document.getElementById('valor_hora'), porcentaje = document.getElementById('porcentaje_comision');
            const anterior = hora.value;
            porcentaje.value = '25'; document.getElementById('deporte_id').dispatchEvent(new Event('change', { bubbles: true }));
            return { horaConservada: hora.value === anterior && anterior !== '', porcentajeConservado: porcentaje.value === '25',
                horaVisible: getComputedStyle(document.getElementById('field-valor-hora')).display !== 'none',
                porcentajeVisible: getComputedStyle(document.getElementById('field-porcentaje-comision')).display !== 'none' };
        });
        fs.writeFileSync(path.join(dir, 'interaccion-cliente.json'), JSON.stringify({ periodos, ficha }, null, 2) + '\n');
        if (periodos.octubre.tipo !== 'Por hora' || periodos.octubre.aviso || periodos.noviembre.tipo !== 'Por comisión'
            || !periodos.noviembre.aviso || Object.values(ficha).some(v => !v)) throw new Error('Falló la interacción T45');
    }
    if (tarea === 't46') {
        await page.goto(origen + '01-profesora-futuras.html');
        // Las rutas reales están cubiertas por PHPUnit; aquí se comprueba solo el cliente con respuestas simuladas.
        const cliente = await page.evaluate(async () => {
            const esperar = ms => new Promise(resolve => setTimeout(resolve, ms));
            const label = document.querySelector('[data-profesor-estado-url]'), card = label.closest('[data-profesor-card]');
            const input = label.querySelector('input'), aviso = card.querySelector('[data-profesor-estado-aviso]');
            const badge = card.querySelector('[data-profesor-badge]');
            document.documentElement.dataset.pruebaT46 = 'sin-navegacion';
            let pedidos = 0;
            window.fetch = async () => { pedidos++; return { ok: false, json: async () => ({ error: 'Tiene 1 clase futura. Reasignala antes de desactivar.' }) }; };
            input.click(); await esperar(150);
            const rechazo = input.checked && badge.textContent.trim() === 'Activo' && !aviso.hidden && aviso.textContent.includes('Reasignala');
            window.fetch = async () => { pedidos++; await esperar(200); return { ok: true, json: async () => ({ activo: false, mensaje: 'Profesora inactiva. Usuario desactivado y sesiones cerradas.' }) }; };
            input.click(); input.click(); await esperar(400);
            return { rechazo, pedidos, inactivo: !input.checked, badge: badge.textContent.trim(), aria: input.getAttribute('aria-checked'),
                sinNavegacion: document.documentElement.dataset.pruebaT46 === 'sin-navegacion', habilitado: !input.disabled,
                aviso: aviso.textContent };
        });
        fs.writeFileSync(path.join(dir, 'interaccion-cliente.json'), JSON.stringify(cliente, null, 2) + '\n');
        if (!cliente.rechazo || cliente.pedidos !== 2 || !cliente.inactivo || cliente.badge !== 'Inactivo' || cliente.aria !== 'false'
            || !cliente.sinNavegacion || !cliente.habilitado) throw new Error('Falló la interacción T46');
    }
    fs.writeFileSync(path.join(dir, 'medidas-chrome.json'), JSON.stringify({ medidas, errores }, null, 2) + '\n');
    if (errores.length) throw new Error(errores.join(', '));
    await page.close();
} finally {
    await browser.close();
    await new Promise(resolve => server.close(resolve));
}
