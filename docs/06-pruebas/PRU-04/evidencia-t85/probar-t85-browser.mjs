import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { ChromeBrowser } from './cdp-helper.mjs';

const PORT = 8785;
const base = `http://127.0.0.1:${PORT}`;
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t85');
fs.mkdirSync(salida, { recursive: true });

const pausa = (ms = 1000) => new Promise(r => setTimeout(r, ms));

async function main() {
    console.log('--- Iniciando prueba T85 en navegador real (Chrome) ---');

    // 1. Levantar servidor artisan serve con wings_testing_gemini
    const env = { ...process.env, DB_DATABASE: 'wings_testing_gemini' };
    const srv = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], {
        env,
        stdio: 'ignore'
    });

    await pausa(2000);

    const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t85-${Date.now()}`) });
    let page;

    const patchRequests = [];
    const patchResponses = [];

    try {
        await browser.start();
        page = await browser.newPage();
        await page.connect();

        // Monitoreo de Red vía CDP
        await page.send('Network.enable');
        page.on('Network.requestWillBeSent', (params) => {
            if (params.request.method === 'PATCH' && params.request.url.includes('/toggle-activo')) {
                patchRequests.push({
                    url: params.request.url,
                    timestamp: params.wallTime,
                    headers: params.request.headers
                });
                console.log(`[RED] PATCH enviado: ${params.request.url} (timestamp: ${params.wallTime})`);
            }
        });
        page.on('Network.responseReceived', (params) => {
            if (params.response.url.includes('/toggle-activo')) {
                patchResponses.push({
                    url: params.response.url,
                    status: params.response.status,
                    statusText: params.response.statusText
                });
                console.log(`[RED] Respuesta recibida: ${params.response.url} -> Status ${params.response.status}`);
            }
        });

        // 1366 px desktop
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 850, deviceScaleFactor: 1, mobile: false });

        // Login
        console.log('Iniciando sesión como admin...');
        await page.goto(`${base}/login`);
        await page.type('input[name=email]', 'admin@wings.test');
        await page.type('input[name=password]', 'PruebaWings2026');
        await page.click('button[type=submit]');
        await pausa(1500);

        // =====================================================================
        // PRUEBA 1: Listado de Alumnos (/alumnos) con doble clic rápido
        // =====================================================================
        console.log('\n=== PRUEBA 1: Doble clic rápido en listado de Alumnos ===');
        await page.goto(`${base}/alumnos`);
        await pausa(1500);

        patchRequests.length = 0;
        patchResponses.length = 0;

        // Disparar dos clics en el primer .ds-toggle en rápida sucesión (< 50ms)
        const clicsListado = await page.send('Runtime.evaluate', {
            expression: `
                (function() {
                    const label = document.querySelector('.alumno-card .ds-toggle[data-url]');
                    if (!label) return 'NO_LABEL';
                    const input = label.querySelector('.ds-toggle__input');
                    const estadoInicial = input.checked;

                    // Simular dos toques rápidos consecutivos
                    label.click();
                    label.click();

                    return {
                        estadoInicial,
                        estadoInmediato: input.checked,
                        disabledInmediato: input.disabled
                    };
                })()
            `,
            returnByValue: true
        });

        console.log('Resultado disparo doble clic listado:', clicsListado.result.value);
        await pausa(1500);

        console.log(`Total peticiones PATCH enviadas al servidor: ${patchRequests.length}`);
        if (patchRequests.length !== 1) {
            throw new Error(`FALLA: Se enviaron ${patchRequests.length} peticiones PATCH (se esperaba exactamente 1).`);
        }

        // Captura escritorio 1366 px
        let shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '01-listado-doble-clic-1366.png'), Buffer.from(shot.data, 'base64'));

        // Captura móvil 375 px
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '02-listado-doble-clic-375.png'), Buffer.from(shot.data, 'base64'));

        // Volver a 1366 px
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 850, deviceScaleFactor: 1, mobile: false });

        // =====================================================================
        // PRUEBA 2: Ficha del Alumno (/alumnos/{id}) con doble clic rápido
        // =====================================================================
        console.log('\n=== PRUEBA 2: Doble clic rápido en ficha del Alumno ===');
        await page.goto(`${base}/alumnos/3`);
        await pausa(1500);

        patchRequests.length = 0;
        patchResponses.length = 0;

        const clicsFicha = await page.send('Runtime.evaluate', {
            expression: `
                (function() {
                    const label = document.querySelector('.ds-toggle[data-url]');
                    if (!label) return 'NO_LABEL';
                    const input = label.querySelector('.ds-toggle__input');
                    const estadoInicial = input.checked;

                    label.click();
                    label.click();

                    return {
                        estadoInicial,
                        estadoInmediato: input.checked,
                        disabledInmediato: input.disabled
                    };
                })()
            `,
            returnByValue: true
        });

        console.log('Resultado disparo doble clic ficha:', clicsFicha.result.value);
        await pausa(2000); // Esperar reload

        console.log(`Total peticiones PATCH enviadas al servidor en ficha: ${patchRequests.length}`);
        if (patchRequests.length !== 1) {
            throw new Error(`FALLA: Se enviaron ${patchRequests.length} peticiones PATCH en ficha (se esperaba exactamente 1).`);
        }

        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '03-ficha-doble-clic-1366.png'), Buffer.from(shot.data, 'base64'));

        // Móvil 375 px
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '04-ficha-doble-clic-375.png'), Buffer.from(shot.data, 'base64'));

        // Volver a 1366 px
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 850, deviceScaleFactor: 1, mobile: false });

        // =====================================================================
        // PRUEBA 3: Error del servidor revertido y destrabado
        // =====================================================================
        console.log('\n=== PRUEBA 3: Error forzado (alumno sin plan), reversión y destrabe ===');
        await page.goto(`${base}/alumnos/63`);
        await pausa(1500);

        patchRequests.length = 0;
        patchResponses.length = 0;

        // Click en toggle de alumno inactivo sin plan -> debe dar 422
        await page.send('Runtime.evaluate', {
            expression: `document.querySelector('.ds-toggle[data-url]')?.click();`
        });

        await pausa(1200);

        const estadoTrasError = await page.send('Runtime.evaluate', {
            expression: `
                (function() {
                    const label = document.querySelector('.ds-toggle[data-url]');
                    const input = label.querySelector('.ds-toggle__input');
                    const flash = document.querySelector('.ds-flash--error');
                    return {
                        checked: input.checked,
                        ariaChecked: input.getAttribute('aria-checked'),
                        disabled: input.disabled,
                        errorTexto: flash ? flash.textContent.trim() : null
                    };
                })()
            `,
            returnByValue: true
        });

        console.log('Estado del toggle tras error 422:', estadoTrasError.result.value);

        const errVal = estadoTrasError.result.value;
        if (errVal.checked !== false || errVal.ariaChecked !== 'false' || errVal.disabled !== false) {
            throw new Error(`FALLA: El toggle no se revirtió o no se destrabó correctamente: ${JSON.stringify(errVal)}`);
        }
        if (!errVal.errorTexto || !errVal.errorTexto.includes('Asigná un plan antes de reactivarlo')) {
            throw new Error(`FALLA: No se mostró el mensaje de error esperado: ${errVal.errorTexto}`);
        }

        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '05-error-forzado-revertido-1366.png'), Buffer.from(shot.data, 'base64'));

        // Móvil 375 px
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '06-error-forzado-revertido-375.png'), Buffer.from(shot.data, 'base64'));

        console.log('\n--- TODAS LAS PRUEBAS EN NAVEGADOR PASARON CON ÉXITO ---');

    } finally {
        if (page) await page.close();
        await browser.close();
        srv.kill('SIGTERM');
        console.log('Servidor de prueba detenido.');
    }
}

main().catch(err => {
    console.error('ERROR EN PRUEBA DE NAVEGADOR:', err);
    process.exit(1);
});
