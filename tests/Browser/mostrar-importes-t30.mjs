/* Chrome sobre respuestas reales de Laravel; solo GET locales. Nunca envía formularios. */
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import {createRequire} from 'node:module';
const require = createRequire(import.meta.url);
const {chromium} = require('playwright');
const raiz = path.resolve('.');
const fase = process.env.WINGS_T30_FASE ?? 'despues';
const evidencia = path.join(raiz, process.env.WINGS_T30_EVIDENCIA_DIR ?? 'docs/06-pruebas/PRU-04/evidencia-t30', fase === 'antes' ? 'antes' : '');
const carpetaHtml = path.join(evidencia, 'html');
const build = path.join(evidencia, 'build');
const capturas = path.join(evidencia, 'capturas');
fs.mkdirSync(capturas, {recursive:true});
const seleccion = process.env.WINGS_T30_CASOS?.split(',');
const pantallas = JSON.parse(fs.readFileSync(path.join(evidencia, 'pantallas.json'), 'utf8'))
    .filter(p => !seleccion || seleccion.includes(p.caso));
const tipos = {'.html':'text/html; charset=utf-8','.js':'application/javascript','.css':'text/css',
    '.json':'application/json','.png':'image/png','.svg':'image/svg+xml','.woff2':'font/woff2','.ico':'image/x-icon'};
const server = http.createServer((req, res) => {
    const url = new URL(req.url, 'http://localhost');
    const carpeta = url.pathname.startsWith('/t30/') ? carpetaHtml : url.pathname.startsWith('/build/') ? build : path.join(raiz, 'public');
    const relativa = url.pathname.startsWith('/t30/') ? url.pathname.slice(5) : url.pathname.startsWith('/build/') ? url.pathname.slice(7) : url.pathname.slice(1);
    const archivo = path.resolve(carpeta, relativa);
    if (req.method !== 'GET' || !archivo.startsWith(carpeta + path.sep) || !fs.existsSync(archivo) || !fs.statSync(archivo).isFile()) {
        res.writeHead(404); res.end(); return;
    }
    res.writeHead(200, {'Content-Type':tipos[path.extname(archivo)] ?? 'application/octet-stream'});
    fs.createReadStream(archivo).pipe(res);
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const base = `http://127.0.0.1:${server.address().port}`;
const browser = await chromium.launch({executablePath:process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
const resultados = []; const fallos = []; let controles = 0;
function verificar(condicion, detalle) { controles++; if (!condicion) throw new Error(detalle); }

async function preparar(frame, caso) {
    if (['alumno-alta', 'alumno-editar'].includes(caso)) {
        for (const selector of ['#deporte_id', '#grupo_id', '#plan_id']) {
            const select = frame.locator(selector);
            if (await select.count() && await select.evaluate(el => el.tagName === 'SELECT' && !el.disabled)) {
                await select.selectOption({index:1});
            }
        }
        await frame.locator('#plan_id').scrollIntoViewIfNeeded();
    }
    if (caso === 'profesor-alta') await frame.locator('#deporte_id').selectOption({index:1});
    if (caso === 'liquidacion-comision') await frame.getByText('Editar monto final', {exact:true}).click();
    if (caso === 'primera-carga') await frame.locator('#paso-4').scrollIntoViewIfNeeded();
    if (caso === 'reportes') await frame.locator('.rv-breakdown').first().scrollIntoViewIfNeeded();
}

async function medir(frame) {
    return frame.evaluate(() => {
        const problemas = [];
        for (const input of document.querySelectorAll('[data-money="true"]')) {
            if (!input.getClientRects().length || !input.value) continue;
            const estilo = getComputedStyle(input); const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d'); ctx.font = estilo.font;
            const disponible = input.clientWidth - parseFloat(estilo.paddingLeft) - parseFloat(estilo.paddingRight);
            const ancho = ctx.measureText(input.value).width;
            if (ancho > disponible + 1) problemas.push({tipo:'campo',selector:input.id ? '#'+input.id : input.name,valor:input.value,ancho,disponible});
        }
        for (const el of document.querySelectorAll('td, .info-value, .rv-amount, strong, .text-right')) {
            if (!el.getClientRects().length || !/\$[\s-]*[0-9]/.test(el.textContent)) continue;
            if (el.children.length) continue;
            const estilo = getComputedStyle(el);
            if (el.scrollWidth > el.clientWidth + 1 && ['hidden','clip'].includes(estilo.overflowX)) {
                problemas.push({tipo:'texto',texto:el.textContent.trim(),ancho:el.scrollWidth,disponible:el.clientWidth});
            }
        }
        for (const el of document.querySelectorAll('.ds-money-summary, .rv-kpi .rv-amount, .rv-current-item strong')) {
            if (!el.getClientRects().length || !el.textContent.includes('$')) continue;
            const range = document.createRange(); range.selectNodeContents(el);
            if (range.getClientRects().length > 1) problemas.push({tipo:'centavos-en-otra-linea',texto:el.textContent.trim()});
            const rect = range.getBoundingClientRect();
            const contenedor = el.parentElement.getBoundingClientRect();
            if (el.closest('.rv-current-item') && rect.right > contenedor.right + 1) problemas.push({tipo:'importe-fuera-de-tarjeta',texto:el.textContent.trim()});
            for (let padre = el.parentElement; padre; padre = padre.parentElement) {
                const estilo = getComputedStyle(padre); const borde = padre.getBoundingClientRect();
                if (['hidden','clip'].includes(estilo.overflowX) && (rect.right > borde.right + 1 || rect.left < borde.left - 1)) {
                    problemas.push({tipo:'recorte-en-resumen',texto:el.textContent.trim()}); break;
                }
            }
        }
        for (const fila of document.querySelectorAll('.rv-breakdown-row')) {
            const limite = fila.getBoundingClientRect();
            if (innerWidth <= 700) {
                const titulo = fila.querySelector('.rv-breakdown-title');
                const porcentaje = fila.querySelector('.rv-row-share');
                const importe = fila.querySelector('strong');
                if (titulo && porcentaje && importe) {
                    const t = titulo.getBoundingClientRect(), p = porcentaje.getBoundingClientRect(), m = importe.getBoundingClientRect();
                    if (p.top >= t.bottom || p.right > limite.right + 1 || m.top < t.bottom - 1) {
                        problemas.push({tipo:'porcentaje-no-esta-junto-al-titulo',texto:titulo.textContent.trim()});
                    }
                }
            }
            for (const el of fila.querySelectorAll('strong, .rv-row-share, .rv-breakdown-title')) {
                const range = document.createRange(); range.selectNodeContents(el);
                const rect = range.getBoundingClientRect();
                if (rect.right > limite.right + 1) problemas.push({tipo:'leyenda-fuera-de-tarjeta',texto:el.textContent.trim(),ancho:rect.width,disponible:limite.right-rect.left});
            }
        }
        return {viewport:innerWidth,pagina:document.documentElement.scrollWidth,problemas};
    });
}

try {
    for (const modo of ['escritorio', 'marco-360']) {
        for (const pantalla of pantallas) {
            const page = await browser.newPage({viewport:modo === 'escritorio' ? {width:1366,height:1000} : {width:700,height:860},deviceScaleFactor:1});
            const erroresJS = []; page.on('pageerror', e => erroresJS.push(e.message));
            await page.route('**/*', async route => {
                const req = route.request(); const url = new URL(req.url());
                if (req.method() !== 'GET') return route.abort();
                const api = url.pathname === '/alumnos/inscripcion-preview' ? 'inscripcion'
                    : url.pathname === '/alumnos/cuota-alta-preview' ? 'cuota' : null;
                if (api) {
                    const archivo = path.join(carpetaHtml, `api-${api}-${pantalla.perfil}.json`);
                    if (fs.existsSync(archivo)) return route.fulfill({status:200,contentType:'application/json',body:fs.readFileSync(archivo)});
                }
                if (req.url().startsWith(base + '/')) return route.continue();
                return route.abort();
            });
            try {
                await page.goto(`${base}/t30/${pantalla.id}${modo === 'marco-360' ? '-marco-360' : ''}.html`, {waitUntil:'load'});
                const frame = modo === 'marco-360' ? page.frames().find(f => f !== page.mainFrame()) : page.mainFrame();
                await frame.waitForFunction(() => Boolean(window.WingsMoney));
                await preparar(frame, pantalla.caso);
                await frame.evaluate(() => document.fonts.ready.then(() => undefined));
                // En el teléfono, el importe probado debe estar dentro de la captura,
                // también cuando el resumen aparece debajo de gráficos o formularios largos.
                await frame.evaluate(esperado => {
                    const el = [...document.querySelectorAll('body *')].find(el =>
                        !el.children.length && el.getClientRects().length &&
                        (el.value === esperado || el.textContent.trim() === esperado));
                    if (el) {
                        const rect = el.getBoundingClientRect();
                        if (rect.bottom > innerHeight || rect.top < 0) el.scrollIntoView({block:'center'});
                    }
                }, pantalla.esperado);
                let visible = (await frame.locator('body').innerText()).includes(pantalla.esperado);
                if (pantalla.campo) {
                    visible ||= await frame.locator('[data-money="true"]').evaluateAll((inputs, esperado) => inputs.some(el => el.value === esperado), pantalla.esperado);
                }
                if (['alumno-alta','alumno-editar'].includes(pantalla.caso)) {
                    visible = (await frame.locator('#plan_id option:checked').textContent()).includes(pantalla.esperado);
                }
                verificar(visible, `${pantalla.id}: falta ${pantalla.esperado}`);
                verificar(erroresJS.length === 0, `${pantalla.id}: errores JS ${erroresJS.join(';')}`);
                const medidas = await medir(frame);
                if (modo === 'marco-360') verificar(medidas.viewport === 360, `${pantalla.id}: ancho del marco incorrecto`);
                verificar(medidas.problemas.length === 0, `${pantalla.id}: importe recortado ${JSON.stringify(medidas.problemas)}`);
                resultados.push({...pantalla,modo,...medidas});
                if (pantalla.caso === 'cobrar') {
                    await frame.locator('.cuota-check[data-periodo="2026-10"]').check();
                    await frame.locator('#tipo_caja_id').selectOption({index:1});
                    await frame.locator('#adelanto-periodo').selectOption({index:1});
                    const adelanto = frame.locator('#adelanto-monto');
                    await adelanto.focus(); await adelanto.fill(pantalla.perfil === 'millones' ? '1250000.50' : '1250.50');
                    await adelanto.blur(); await frame.locator('#adelanto-agregar').click();
                    const fila = frame.locator('.cuota-row--adelantado'); await fila.scrollIntoViewIfNeeded();
                    const dato = await fila.locator('[data-money="true"]').inputValue();
                    const esperado = pantalla.perfil === 'millones' ? '1,250,000.50' : '1,250.50';
                    verificar(dato === esperado, 'Adelantado no conserva los centavos');
                    const rect = await fila.boundingBox(); const input = await fila.locator('[data-money="true"]').boundingBox();
                    verificar(input.x + input.width <= rect.x + rect.width + 1, 'Adelantado sale de la fila');
                    const total = await frame.locator('#total-label').textContent();
                    verificar(total === (pantalla.perfil === 'millones' ? '$2,500,001.00' : '$2,501.00'), 'El total seleccionado no coincide con cuota + adelanto');
                    await page.screenshot({path:path.join(capturas,`cobrar-adelantado-${pantalla.perfil}-${modo}.png`)});
                    const medidasAdelanto = await medir(frame);
                    verificar(medidasAdelanto.problemas.length === 0, 'Adelantado recorta un importe');
                    resultados.push({id:`cobrar-adelantado-${pantalla.perfil}`,modo,...medidasAdelanto});
                }
            } catch (error) {
                fallos.push({id:pantalla.id,modo,error:error.message});
            } finally {
                await page.screenshot({path:path.join(capturas,`${pantalla.id}-${modo}.png`)});
                await page.close();
            }
        }
        const page = await browser.newPage({viewport:modo === 'escritorio' ? {width:1366,height:1000} : {width:700,height:860}});
        await page.goto(`${base}/t30/login${modo === 'marco-360' ? '-marco-360' : ''}.html`, {waitUntil:'load'});
        if (modo === 'marco-360') {
            const frame = page.frames().find(f => f !== page.mainFrame());
            const ancho = await frame.evaluate(() => ({viewport:innerWidth,pagina:document.documentElement.scrollWidth}));
            verificar(ancho.viewport === 360 && ancho.pagina <= 360, 'El login de control aparece recortado');
        }
        await page.screenshot({path:path.join(capturas,`login-${modo}.png`)}); await page.close();
    }
} catch (error) {
    fallos.push({id:'control-general',error:error.message});
} finally {
    const archivoResultado = seleccion ? 'resultado-navegador-seleccion.json' : 'resultado-navegador.json';
    fs.writeFileSync(path.join(evidencia,archivoResultado), JSON.stringify({fase,controles,resultados,fallos},null,2));
    await browser.close(); await new Promise(resolve => server.close(resolve));
}
console.log(JSON.stringify({fase,controles,capturas:resultados.length,fallos},null,2));
if (fallos.length && fase !== 'antes') process.exitCode = 1;
