import { ChromeBrowser } from '../../../../../scripts/pruebas/cdp-helper.mjs';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const baseUrl = process.argv[2] || 'http://127.0.0.1:8740';
const modo = process.argv[3] || 'noviembre_simulado';

async function main() {
    console.log(`[CDP] Iniciando prueba de navegador para T50 en modo: ${modo}`);
    const browser = new ChromeBrowser();
    await browser.start();

    const resultado = {
        modo,
        baseUrl,
        pasos: {},
        consola: []
    };

    try {
        const page = await browser.getFirstPage();
        await page.connect();

        page.on('Console.messageAdded', (evt) => {
            resultado.consola.push({
                level: evt.message?.level,
                text: evt.message?.text
            });
        });

        // 1. Login
        await page.goto(`${baseUrl}/login`);
        await page.type('#email', 'admin@wings.test');
        await page.type('#password', 'password');
        await page.click('button[type="submit"]');
        await new Promise(r => setTimeout(r, 1200));

        // 2. Ir a /alumnos/create
        await page.goto(`${baseUrl}/alumnos/create`);
        await page.waitForSelector('#fecha_alta');

        // 3. Inspeccionar estado inicial del campo fecha_alta
        const estadoInicial = await page.evaluate(() => {
            const input = document.getElementById('fecha_alta');
            return {
                value: input.value,
                dataMax: input.dataset.max,
                max: input.max,
                required: input.required,
                validityValid: input.validity.valid,
                validationMessage: input.validationMessage
            };
        });
        resultado.pasos.estado_inicial = estadoInicial;
        console.log('[CDP] Estado inicial fecha_alta:', estadoInicial);

        if (modo === 'noviembre_simulado') {
            // Con noviembre simulado:
            // max debe ser 2026-12-31, valor por defecto 2026-11-01
            // Probar 2026-12-01 (fecha válida dentro del límite)
            await page.type('#fecha_alta', '2026-12-01');
            await new Promise(r => setTimeout(r, 600));

            const pasoValido = await page.evaluate(() => {
                const input = document.getElementById('fecha_alta');
                const cuotaMensaje = document.getElementById('cuota-alta-mensaje')?.textContent || '';
                return {
                    fechaProbada: input.value,
                    validityValid: input.validity.valid,
                    validationMessage: input.validationMessage,
                    cuotaMensaje
                };
            });
            resultado.pasos.fecha_valida = pasoValido;
            console.log('[CDP] Fecha válida 2026-12-01:', pasoValido);

            // Probar 2027-01-01 (fuera de límite)
            await page.type('#fecha_alta', '2027-01-01');
            await new Promise(r => setTimeout(r, 800));

            const pasoInvalido = await page.evaluate(() => {
                const input = document.getElementById('fecha_alta');
                const cuotaMensaje = document.getElementById('cuota-alta-mensaje')?.textContent || '';
                return {
                    fechaProbada: input.value,
                    validityValid: input.validity.valid,
                    validationMessage: input.validationMessage,
                    cuotaMensaje
                };
            });
            resultado.pasos.fecha_invalida = pasoInvalido;
            console.log('[CDP] Fecha inválida 2027-01-01:', pasoInvalido);

            // Captura de pantalla a 1366px
            await page.send('Emulation.setDeviceMetricsOverride', {
                width: 1366,
                height: 768,
                deviceScaleFactor: 1,
                mobile: false
            });
            await new Promise(r => setTimeout(r, 300));
            const path1366 = path.join(__dirname, '01-aviso-limite-1366.png');
            await page.screenshot(path1366);
            console.log(`[CDP] Captura 1366 guardada en: ${path1366}`);

            // Captura de pantalla a 375px
            await page.send('Emulation.setDeviceMetricsOverride', {
                width: 375,
                height: 667,
                deviceScaleFactor: 1,
                mobile: true
            });
            await new Promise(r => setTimeout(r, 300));
            const path375 = path.join(__dirname, '02-aviso-limite-375.png');
            await page.screenshot(path375);
            console.log(`[CDP] Captura 375 guardada en: ${path375}`);

        } else {
            // Reloj real (octubre):
            // max debe ser 2026-11-30, valor por defecto hoy
            // Probar fecha válida dentro del límite: 2026-11-15
            await page.type('#fecha_alta', '2026-11-15');
            await new Promise(r => setTimeout(r, 600));

            const pasoValido = await page.evaluate(() => {
                const input = document.getElementById('fecha_alta');
                const cuotaMensaje = document.getElementById('cuota-alta-mensaje')?.textContent || '';
                return {
                    fechaProbada: input.value,
                    validityValid: input.validity.valid,
                    validationMessage: input.validationMessage,
                    cuotaMensaje
                };
            });
            resultado.pasos.fecha_valida = pasoValido;
            console.log('[CDP] Fecha válida 2026-11-15:', pasoValido);

            // Probar fecha fuera de límite: 2026-12-01
            await page.type('#fecha_alta', '2026-12-01');
            await new Promise(r => setTimeout(r, 800));

            const pasoInvalido = await page.evaluate(() => {
                const input = document.getElementById('fecha_alta');
                const cuotaMensaje = document.getElementById('cuota-alta-mensaje')?.textContent || '';
                return {
                    fechaProbada: input.value,
                    validityValid: input.validity.valid,
                    validationMessage: input.validationMessage,
                    cuotaMensaje
                };
            });
            resultado.pasos.fecha_invalida = pasoInvalido;
            console.log('[CDP] Fecha inválida 2026-12-01:', pasoInvalido);
        }

        const jsonPath = path.join(__dirname, `recorrido-${modo}.json`);
        fs.writeFileSync(jsonPath, JSON.stringify(resultado, null, 2));
        console.log(`[CDP] Resultado guardado en ${jsonPath}`);

    } finally {
        await browser.close();
    }
}

main().catch(err => {
    console.error('[CDP] Error ejecutando prueba:', err);
    process.exit(1);
});
