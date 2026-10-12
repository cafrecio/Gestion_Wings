/* Prueba permanente del navegador sobre HTML emitido por Laravel en la base propia.
 * Preparar: DB_DATABASE=wings_testing_codex WINGS_CAPTURAS_T24=1 php artisan test --filter=ImportesPesosT24Test
 * Ejecutar con Playwright disponible en NODE_PATH: node tests/Browser/importes-t24.mjs
 * Para reproducir esta entrega visual: WINGS_T24_BUILD_DIR=docs/06-pruebas/PRU-04/evidencia-t24/build
 * No se envía ningún formulario ni se consulta el sitio de prueba.
 */
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import {createRequire} from 'node:module';
import assert from 'node:assert/strict';

const require = createRequire(import.meta.url);
const {chromium} = require('playwright');
const raiz = path.resolve('.');
const evidencia = path.resolve(process.env.WINGS_T24_EVIDENCIA_DIR ?? 'docs/06-pruebas/PRU-04/evidencia-t24');
const html = path.join(evidencia, 'html');
const build = process.env.WINGS_T24_BUILD_DIR ? path.resolve(process.env.WINGS_T24_BUILD_DIR) : path.join(raiz, 'public/build');
const capturas = path.join(evidencia, 'capturas');
fs.mkdirSync(capturas, {recursive: true});
const casos = JSON.parse(fs.readFileSync(path.join(evidencia, 'pantallas.json'), 'utf8'));
const mime = {'.html':'text/html; charset=utf-8', '.js':'application/javascript', '.css':'text/css',
    '.png':'image/png', '.svg':'image/svg+xml', '.woff2':'font/woff2', '.ico':'image/x-icon'};
const server = http.createServer((request, response) => {
    const url = new URL(request.url, 'http://localhost');
    const carpeta = url.pathname.startsWith('/t24/') ? html : url.pathname.startsWith('/build/')
        ? build : path.join(raiz, 'public');
    const relativa = url.pathname.startsWith('/t24/') ? url.pathname.slice(5)
        : url.pathname.startsWith('/build/') ? url.pathname.slice(7) : url.pathname.slice(1);
    const archivo = path.resolve(carpeta, relativa);
    if (request.method !== 'GET' || !archivo.startsWith(carpeta + path.sep) || !fs.existsSync(archivo) || !fs.statSync(archivo).isFile()) {
        response.writeHead(404); response.end(); return;
    }
    response.writeHead(200, {'Content-Type':mime[path.extname(archivo)] ?? 'application/octet-stream'});
    fs.createReadStream(archivo).pipe(response);
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const base = `http://127.0.0.1:${server.address().port}/t24/`;
const browser = await chromium.launch({executablePath:process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
const resultados = [];
const fallos = [];
let verificaciones = 0;
const verificar = (condicion, texto) => { assert.ok(condicion, texto); verificaciones++; };

async function preparar(frame, caso) {
    if (caso === 'grupo-alta') await frame.locator('#btn-add-plan').click();
    if (caso === 'profesor-alta') await frame.locator('#deporte_id').selectOption({label:'Deporte T24'});
    if (caso === 'liquidacion') await frame.getByText('Editar monto final', {exact:true}).click();
    if (caso === 'cuota') await frame.locator('.cuota-check[data-periodo="2026-10"]').check();
    if (caso === 'adelantado') await frame.locator('#adelanto-periodo').selectOption('2026-11');
}

async function comprobarCampo(frame, input, etiqueta, pagina, modo) {
    await input.scrollIntoViewIfNeeded();
    await input.focus();
    await input.fill('48.50');
    verificar(await input.inputValue() === '48.50', `${etiqueta}: dos decimales alterados: ${await input.inputValue()}`);
    verificar(await input.evaluate(el => !el.classList.contains('money-input--invalid')), `${etiqueta}: válido marcado inválido`);
    await input.blur();
    verificar(await input.inputValue() === '48.50', `${etiqueta}: centavos perdidos al salir`);
    await input.focus(); await input.fill('48000.50'); await input.blur();
    verificar(await input.inputValue() === '48,000.50', `${etiqueta}: formato guardado`);
    const medida = await input.evaluate(el => {
        const estilo = getComputedStyle(el);
        const canvas = document.createElement('canvas');
        const contexto = canvas.getContext('2d'); contexto.font = estilo.font;
        return {texto:contexto.measureText(el.value).width, disponible:el.clientWidth-parseFloat(estilo.paddingLeft)-parseFloat(estilo.paddingRight)};
    });
    verificar(medida.texto <= medida.disponible, `${etiqueta}: importe recortado ${JSON.stringify(medida)}`);
    await input.focus();
    verificar(await input.inputValue() === '48000.50', `${etiqueta}: foco confunde miles/decimales`);
    await input.fill('48,50');
    verificar(await input.inputValue() === '48.50', `${etiqueta}: coma decimal`);
    await input.fill('48.50');
    await input.blur();
    await input.focus(); await input.fill('48000.50'); await input.blur();
    await frame.locator('.money-notice').waitFor({state:'hidden', timeout:5000});
    await pagina.screenshot({path:path.join(capturas, `${etiqueta}-${modo}-valido.png`)});

    for (const texto of ['48.505', '48.000', '48.0000']) {
        await input.focus();
        await input.fill(texto);
        verificar(await input.inputValue() === texto.slice(0, 5), `${etiqueta}: no conserva dos decimales de ${texto}`);
        verificar(await input.evaluate(el => el.classList.contains('money-input--warning')), `${etiqueta}: falta rojo`);
        verificar(await input.evaluate(el => el.checkValidity()), `${etiqueta}: valor corregido bloqueado`);
        verificar(await frame.locator('.money-notice').isVisible(), `${etiqueta}: falta cartel`);
    }
    await input.fill('48.505');
    await pagina.screenshot({path:path.join(capturas, `${etiqueta}-${modo}-aviso.png`)});
    await input.fill('');
    await input.pressSequentially('48.505');
    verificar(await input.inputValue() === '48.50', `${etiqueta}: tercer decimal por teclado`);
    verificar(await input.evaluate(el => el.classList.contains('money-input--warning') && el.checkValidity()), `${etiqueta}: teclado no deja continuar`);
    for (const texto of ['-1', 'letras', '1e2']) {
        await input.fill(texto);
        verificar(await input.evaluate(el => el.getAttribute('aria-invalid') === 'true'), `${etiqueta}: acepta ${texto}`);
        verificar(await input.evaluate(el => el.classList.contains('money-input--invalid')), `${etiqueta}: error sin rojo`);
    }
    await input.fill('0');
    const permiteCero = await input.getAttribute('min') === '0';
    verificar(await input.evaluate(el => el.getAttribute('aria-invalid') === 'true') === !permiteCero, `${etiqueta}: cero cambia la regla`);
    await input.fill('48.50'); await input.blur();
    verificar(await input.evaluate(el => !el.classList.contains('money-input--warning') && !el.classList.contains('money-input--invalid')), `${etiqueta}: no limpia rojo`);
    // FormData debe conservar 48.50 aunque el campo visible lleve coma de miles.
    if (await input.getAttribute('name')) {
        await input.fill('48000.50'); await input.blur();
        const enviado = await input.evaluate(el => new FormData(el.form).get(el.name));
        verificar(enviado === '48000.50', `${etiqueta}: FormData envía ${enviado}`);
    }
}

async function comprobarRevisionVisual(frame, caso, pagina, modo) {
    if (['caja-agregar', 'caja-editar'].includes(caso)) {
        for (const id of ['fecha', 'tipo_caja_id', 'rubro_id', 'subrubro_id', 'monto', 'observaciones']) {
            verificar(await frame.locator(`label[for="${id}"] svg`).count() === 1, `${caso}: falta icono ${id}`);
        }
    }
    if (caso === 'entregado') {
        await frame.locator('.cuota-check[data-periodo="2026-10"]').check();
        const input = frame.locator('#monto-entregado');
        await input.focus(); await input.fill('48000.50'); await input.blur();
        const inscripcion = frame.locator('[data-distribucion-inscripcion]');
        const cuotas = frame.locator('[data-distribucion-cuotas]');
        verificar(await inscripcion.textContent() === 'Inscripción $5.000', 'Inscripción: desglose incorrecto');
        verificar(await cuotas.textContent() === 'Cuotas $43.000,50', 'Cuotas: faltan los dos decimales');
        const a = await inscripcion.boundingBox(); const b = await cuotas.boundingBox();
        verificar(b.y >= a.y + a.height - 1, 'El desglose no ocupa dos líneas');
        verificar(await frame.locator('label[for="monto-entregado"] svg').count() === 1, 'Importe entregado: falta icono');
        await frame.locator('#inscripcion-distribucion').scrollIntoViewIfNeeded();
        await frame.locator('.money-notice').waitFor({state:'hidden', timeout:5000});
        await pagina.screenshot({path:path.join(capturas, `inscripcion-desglose-${modo}.png`)});
    }
    if (['configuracion', 'liquidacion'].includes(caso)) {
        const acciones = frame.locator(caso === 'configuracion' ? '.configuracion-form .acciones-celular-derecha' : 'details .acciones-celular-derecha').first();
        if (modo === 'marco-360') {
            verificar(await acciones.evaluate(el => getComputedStyle(el).justifyContent === 'flex-end'), `${caso}: Guardar no está a la derecha`);
        } else {
            verificar(await acciones.evaluate(el => getComputedStyle(el).justifyContent === 'flex-start'), `${caso}: cambió la posición de escritorio`);
        }
        await acciones.scrollIntoViewIfNeeded();
        await frame.locator('.money-notice').waitFor({state:'hidden', timeout:5000});
        await pagina.screenshot({path:path.join(capturas, `${caso}-acciones-${modo}.png`)});
        if (caso === 'configuracion') {
            for (const grupo of await frame.locator('.acciones-celular-derecha:visible').all()) {
                verificar(await grupo.evaluate(el => getComputedStyle(el).justifyContent === (innerWidth <= 640 ? 'flex-end' : 'flex-start')), 'Configuración: acciones visibles desalineadas');
            }
            await frame.locator('.btn-editar-regla').first().click();
            const editar = frame.locator('[id^="panel-edit-"]:visible .acciones-celular-derecha');
            verificar(await editar.evaluate(el => getComputedStyle(el).justifyContent === (innerWidth <= 640 ? 'flex-end' : 'normal')), 'Configuración: acciones al editar regla desalineadas');
            await editar.scrollIntoViewIfNeeded();
            await pagina.screenshot({path:path.join(capturas, `configuracion-editar-regla-${modo}.png`)});
            await frame.locator('.btn-cancelar-edit:visible').click();
            await frame.locator('#btn-agregar-regla').click();
            const agregar = frame.locator('#panel-add-regla .acciones-celular-derecha');
            verificar(await agregar.evaluate(el => getComputedStyle(el).justifyContent === (innerWidth <= 640 ? 'flex-end' : 'flex-start')), 'Configuración: acciones de nueva regla desalineadas');
            await agregar.scrollIntoViewIfNeeded();
            await pagina.screenshot({path:path.join(capturas, `configuracion-nueva-regla-${modo}.png`)});
        }
    }
    if (caso === 'adelantado-fila-nueva') {
        const fila = frame.locator('.cuota-row--adelantado');
        await fila.scrollIntoViewIfNeeded();
        const limites = await fila.boundingBox();
        for (const selector of ['[data-money="true"]', '.adelanto-marca', '.adelanto-mes']) {
            const caja = await fila.locator(selector).boundingBox();
            verificar(caja.x >= limites.x && caja.x + caja.width <= limites.x + limites.width + 1, `Adelantado: ${selector} sale de la fila`);
        }
        if (modo === 'marco-360') {
            const marca = await fila.locator('.adelanto-marca').boundingBox();
            const mes = await fila.locator('.adelanto-mes').boundingBox();
            verificar(marca.y + marca.height <= mes.y + 1, 'Adelantado no está arriba del mes');
        }
    }
}

try {
    for (const modo of ['escritorio', 'marco-360']) {
        const pagina = await browser.newPage({viewport:modo === 'escritorio' ? {width:1366,height:1000} : {width:700,height:860},deviceScaleFactor:1});
        const erroresJS = [];
        pagina.on('pageerror', error => erroresJS.push(error.message));
        // Se impiden todos los guardados. El servidor auxiliar solo sirve archivos.
        await pagina.route('**/*', async ruta => {
            if (ruta.request().method() !== 'GET') return ruta.abort();
            return ruta.continue();
        });
        for (const caso of casos) {
            try {
                await pagina.goto(base + caso.caso + (modo === 'marco-360' ? '-marco-360.html' : '.html'), {waitUntil:'load'});
                const frame = modo === 'marco-360' ? pagina.frames().find(frame => frame !== pagina.mainFrame()) : pagina.mainFrame();
                await frame.waitForFunction(() => Boolean(window.WingsMoney));
                await preparar(frame, caso.caso);
                const input = frame.locator(caso.selector);
                if (caso.soloLectura) {
                    verificar(await input.evaluate(el => el.readOnly || el.type === 'hidden'), `${caso.caso}: se puede editar`);
                    const crudo = await input.inputValue();
                    verificar(crudo === '48,000.50' || crudo === '48000.50', `${caso.caso}: centavos cambiados`);
                    if (caso.caso === 'cierre-con-conteo') verificar((await frame.locator('[data-diferencia]').textContent()).includes('Sin diferencia'), 'Conteo preservado mal interpretado');
                    await pagina.screenshot({path:path.join(capturas, `${caso.caso}-${modo}-valido.png`)});
                    resultados.push({caso:caso.caso, modo, soloLectura:true});
                    continue;
                }
                await comprobarCampo(frame, input, caso.caso, pagina, modo);
                await comprobarRevisionVisual(frame, caso.caso, pagina, modo);
                if (modo === 'marco-360') {
                    const ancho = await frame.evaluate(() => ({viewport:innerWidth, pagina:document.documentElement.scrollWidth}));
                    verificar(ancho.viewport === 360 && ancho.pagina <= 360, `${caso.caso}: marco/desborde ${JSON.stringify(ancho)}`);
                    resultados.push({caso:caso.caso, modo, ...ancho});
                } else resultados.push({caso:caso.caso, modo});
                if (caso.caso === 'adelantado') {
                    await input.fill('48.50');
                    await frame.locator('#adelanto-agregar').click();
                    await comprobarCampo(frame, frame.locator('[name="montos_cuota[2026-11]"]'), 'adelantado-fila-nueva', pagina, modo);
                    await comprobarRevisionVisual(frame, 'adelantado-fila-nueva', pagina, modo);
                }
                if (caso.caso === 'grupo-editar') {
                    await frame.locator('#btn-add-plan').click();
                    await comprobarCampo(frame, frame.locator('[name="planes[1][precio_mensual]"]'), 'grupo-fila-nueva', pagina, modo);
                }
            } catch (error) {
                fallos.push({caso:caso.caso, modo, error:error.message});
            }
        }
        await pagina.goto(base + 'login' + (modo === 'marco-360' ? '-marco-360.html' : '.html'), {waitUntil:'load'});
        if (modo === 'marco-360') {
            const frame = pagina.frames().find(frame => frame !== pagina.mainFrame());
            const medidas = await frame.evaluate(() => ({viewport:innerWidth, pagina:document.documentElement.scrollWidth}));
            verificar(medidas.viewport === 360 && medidas.pagina <= 360, 'El login de control aparece recortado');
            resultados.push({caso:'login', modo, ...medidas});
        }
        await pagina.screenshot({path:path.join(capturas, `login-${modo}.png`)});
        verificar(erroresJS.length === 0, 'Errores JavaScript: ' + erroresJS.join('; '));
        // Verificación real de duración: desde el aviso hasta su ocultación.
        await pagina.goto(base + 'cashflow.html', {waitUntil:'load'});
        const duracion = await pagina.evaluate(() => new Promise(resolve => {
            const input = document.getElementById('monto');
            const comienzo = performance.now();
            input.value = '48.505'; input.dispatchEvent(new Event('input', {bubbles:true}));
            const aviso = document.querySelector('.money-notice');
            const observer = new MutationObserver(() => {
                if (aviso.hidden) { observer.disconnect(); resolve(performance.now() - comienzo); }
            });
            observer.observe(aviso, {attributes:true, attributeFilter:['hidden']});
        }));
        verificar(duracion >= 3700 && duracion <= 4400, `Duración del aviso: ${duracion}ms`);
        await pagina.locator('#monto').fill('48.505');
        verificar(await pagina.locator('.money-notice').isVisible(), 'El cartel no reaparece después de sus cuatro segundos');
        resultados.push({caso:'duracion-aviso', modo, duracion});
        await pagina.close();
    }
} catch (error) {
    fallos.push({caso:'control-general', error:error.message});
} finally {
    fs.writeFileSync(path.join(evidencia, 'resultado-navegador.json'), JSON.stringify({verificaciones,resultados,fallos}, null, 2));
    await browser.close();
    await new Promise(resolve => server.close(resolve));
}
console.log(JSON.stringify({verificaciones, pantallas:resultados.length, fallos}, null, 2));
if (fallos.length) process.exitCode = 1;
