import { ChromeBrowser } from './cdp-helper.mjs';
import http from 'node:http';
import path from 'node:path';
import fs from 'node:fs';

const htmlRelative = 'docs/06-pruebas/PRU-04/evidencia-t64/01-configuracion-reglas.html';
const htmlPath = path.resolve(htmlRelative);
if (!fs.existsSync(htmlPath)) {
    throw new Error(`No existe ${htmlPath}`);
}

// Servidor HTTP simple para servir assets de /build y el HTML
const server = http.createServer((req, res) => {
    let filePath;
    const cleanUrl = req.url.split('?')[0];
    if (cleanUrl.startsWith('/build/')) {
        filePath = path.join(path.resolve('public'), cleanUrl);
    } else {
        filePath = path.join(path.resolve('.'), cleanUrl);
    }

    if (!fs.existsSync(filePath) || fs.statSync(filePath).isDirectory()) {
        res.statusCode = 404;
        res.end(`Not found: ${cleanUrl}`);
        return;
    }

    const ext = path.extname(filePath).toLowerCase();
    const mimeTypes = {
        '.html': 'text/html; charset=utf-8',
        '.js': 'application/javascript; charset=utf-8',
        '.css': 'text/css; charset=utf-8',
        '.json': 'application/json; charset=utf-8',
        '.png': 'image/png',
        '.svg': 'image/svg+xml'
    };
    res.setHeader('Content-Type', mimeTypes[ext] || 'application/octet-stream');
    fs.createReadStream(filePath).pipe(res);
});

await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const port = server.address().port;
console.log(`Servidor de prueba corriendo en http://127.0.0.1:${port}`);

const browser = new ChromeBrowser();
await browser.start();

try {
    const page = await browser.getFirstPage();
    await page.connect();

    page.on('Runtime.consoleAPICalled', (p) => {
        console.log('[BROWSER CONSOLE]', p.type, p.args?.map(a => a.value));
    });
    page.on('Runtime.exceptionThrown', (p) => {
        console.error('[BROWSER ERROR]', p.exceptionDetails?.text, p.exceptionDetails?.exception?.description);
    });

    console.log('Navegando a la pantalla de configuración...');
    await page.goto(`http://127.0.0.1:${port}/${htmlRelative}`);

    // Esperar a que el script de configuraciones.js se ejecute
    await new Promise(r => setTimeout(r, 1000));

    // Inyectar espía para capturar la petición fetch hacia /configuraciones/primer-pago
    await page.evaluate(() => {
        window.__ultimoPayloadEnviado = null;
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            if (url.includes('/configuraciones/primer-pago')) {
                window.__ultimoPayloadEnviado = JSON.parse(options.body || '{}');
                // Devolver respuesta mock válida con las reglas enviadas
                const reglasDevueltas = window.__ultimoPayloadEnviado.reglas.map(r => ({
                    ...r,
                    id: r.id || 99
                }));
                return {
                    ok: true,
                    status: 200,
                    json: async () => reglasDevueltas
                };
            }
            return originalFetch(url, options);
        };
    });

    console.log('1. Abriendo panel de edición de la regla 1...');
    const cards = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('#reglas-list .alumno-card[id^="regla-card-"]')).map(c => c.id.replace('regla-card-', ''));
    });
    const id1 = cards[0];
    const id2 = cards[1];
    console.log(`Regla 1 ID: ${id1}, Regla 2 ID: ${id2}`);

    await page.click(`#regla-card-${id1} .btn-editar-regla`);

    console.log('2. Cambiando porcentaje de regla 1 a 90%...');
    await page.type(`#edit-porcentaje-${id1}`, '90');

    const valorAntesCancelar = await page.evaluate((id) => document.getElementById(`edit-porcentaje-${id}`).value, id1);
    console.log(`Valor en input antes de cancelar: ${valorAntesCancelar}%`);

    console.log('3. Haciendo clic en Cancelar de la regla 1...');
    await page.click(`#regla-card-${id1} .btn-cancelar-edit`);

    const estadoDespuesCancelar = await page.evaluate((id) => {
        const panel = document.getElementById(`panel-edit-${id}`);
        const input = document.getElementById(`edit-porcentaje-${id}`);
        return {
            panelDisplay: panel ? panel.style.display : null,
            inputVal: input ? input.value : null,
        };
    }, id1);
    console.log('Estado después de cancelar:', estadoDespuesCancelar);

    if (estadoDespuesCancelar.inputVal !== '100') {
        throw new Error(`FALLÓ H1: El valor del input 1 tras cancelar debía ser 100% pero es ${estadoDespuesCancelar.inputVal}%`);
    }
    if (estadoDespuesCancelar.panelDisplay !== 'none') {
        throw new Error(`FALLÓ: El panel de edición de la regla 1 no se ocultó tras cancelar`);
    }

    console.log('4. Abriendo panel de edición de la regla 2...');
    await page.click(`#regla-card-${id2} .btn-editar-regla`);

    console.log('5. Cambiando porcentaje de regla 2 a 65%...');
    await page.type(`#edit-porcentaje-${id2}`, '65');

    console.log('6. Guardando la regla 2...');
    await page.click(`#regla-card-${id2} .btn-guardar-regla`);

    await new Promise(r => setTimeout(r, 800));

    const payload = await page.evaluate(() => window.__ultimoPayloadEnviado);
    console.log('Payload capturado en PUT /configuraciones/primer-pago:', JSON.stringify(payload, null, 2));

    if (!payload || !payload.reglas) {
        throw new Error('FALLÓ: No se capturó la petición de guardado en el servidor.');
    }

    const regla1Enviada = payload.reglas.find(r => r.id === parseInt(id1, 10));
    const regla2Enviada = payload.reglas.find(r => r.id === parseInt(id2, 10));

    console.log(`Porcentaje regla 1 enviado: ${regla1Enviada.porcentaje}% (Esperado: 100%)`);
    console.log(`Porcentaje regla 2 enviado: ${regla2Enviada.porcentaje}% (Esperado: 65%)`);

    if (regla1Enviada.porcentaje !== 100) {
        throw new Error(`FALLÓ H1: La regla 1 se envió con porcentaje ${regla1Enviada.porcentaje}% en lugar de conservar 100%`);
    }

    if (regla2Enviada.porcentaje !== 65) {
        throw new Error(`FALLÓ: La regla 2 debía enviarse con 65% pero fue ${regla2Enviada.porcentaje}%`);
    }

    console.log('✅ COMPROBACIÓN H1 EN NAVEGADOR REAL EXITOSA: La regla 1 no cambió tras cancelar la edición.');

} finally {
    await browser.close();
    server.close();
}
