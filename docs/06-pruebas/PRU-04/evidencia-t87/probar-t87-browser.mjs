import { spawn, execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const PORT = 8787;
const base = `http://127.0.0.1:${PORT}`;
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t87');
fs.mkdirSync(salida, { recursive: true });

const pausa = (ms = 1000) => new Promise(r => setTimeout(r, ms));

async function main() {
    console.log('--- Iniciando prueba T87 en navegador real (Chrome) ---');

    // 1. Preparar la base wings_testing_gemini con el escenario de Lucía
    console.log('Inicializando datos del escenario en wings_testing_gemini...');
    execSync('php artisan migrate:fresh --seed', {
        env: { ...process.env, DB_DATABASE: 'wings_testing_gemini' },
        stdio: 'inherit'
    });

    execSync('php docs/06-pruebas/PRU-04/evidencia-t87/seeder-t87.php', {
        env: { ...process.env, DB_DATABASE: 'wings_testing_gemini' },
        stdio: 'inherit'
    });

    // 2. Levantar servidor local en puerto dedicado
    const env = { ...process.env, DB_DATABASE: 'wings_testing_gemini' };
    const srv = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], {
        env,
        stdio: 'ignore'
    });

    await pausa(2000);

    const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t87-${Date.now()}`) });
    let page;

    try {
        await browser.start();
        page = await browser.newPage();
        await page.connect();

        // 1366 px desktop
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 850, deviceScaleFactor: 1, mobile: false });

        // Login como admin
        console.log('Iniciando sesión como admin...');
        await page.goto(`${base}/login`);
        await page.type('input[name=email]', 'admin@wings.test');
        await page.type('input[name=password]', 'PruebaWings2026');
        await page.click('button[type=submit]');
        await pausa(1500);

        // =====================================================================
        // PASO 1: Abrir /liquidaciones/crear en Octubre 2026 y elegir a Lucía
        // =====================================================================
        console.log('\n=== PASO 1: Octubre 2026 - Seleccionar Lucía Gaitán ===');
        await page.goto(`${base}/liquidaciones/crear?mes=10&anio=2026`);
        await pausa(1000);

        // Seleccionar a Lucía
        const selLucia = await page.evaluate(() => {
            const rows = Array.from(document.querySelectorAll('.profesor-radio-row'));
            const rowLucia = rows.find(r => r.textContent.includes('Gaitán, Lucía'));
            if (!rowLucia) return { error: 'No se encontró a Lucía' };
            const radio = rowLucia.querySelector('input[type=radio]');
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));

            const countEl = rowLucia.querySelector('.profesor-clases-count');
            const sinAsisEl = rowLucia.querySelector('.profesor-sin-asistencia-aviso');
            const avisoGeneral = document.getElementById('aviso-sin-asistencia');
            const textoAviso = document.getElementById('aviso-sin-asistencia-texto');

            return {
                radioChecked: radio.checked,
                textoCount: countEl ? countEl.textContent.trim() : null,
                textoSinAsis: sinAsisEl ? sinAsisEl.textContent.trim() : null,
                sinAsisDisplay: sinAsisEl ? sinAsisEl.style.display : null,
                avisoGeneralDisplay: avisoGeneral ? avisoGeneral.style.display : null,
                textoAvisoGeneral: textoAviso ? textoAviso.textContent.trim() : null
            };
        });

        console.log('Estado Octubre:', selLucia);
        if (!selLucia.textoCount.includes('17 clases liquidables')) {
            throw new Error(`FALLA Octubre: Se esperaba "17 clases liquidables", obtenido "${selLucia.textoCount}"`);
        }
        if (!selLucia.textoSinAsis.includes('2 clases sin asistencia')) {
            throw new Error(`FALLA Octubre: Se esperaba "2 clases sin asistencia", obtenido "${selLucia.textoSinAsis}"`);
        }
        if (selLucia.avisoGeneralDisplay === 'none' || !selLucia.textoAvisoGeneral.includes('2 clases')) {
            throw new Error(`FALLA Octubre: El banner de aviso general debía mostrar 2 clases, obtenido "${selLucia.textoAvisoGeneral}"`);
        }

        // Captura escritorio 1366 px
        let shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '01-octubre-lucia-1366.png'), Buffer.from(shot.data, 'base64'));

        // Captura móvil 375 px
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '02-octubre-lucia-375.png'), Buffer.from(shot.data, 'base64'));

        // =====================================================================
        // PASO 2: Cambiar mes a Septiembre (sin recargar la página)
        // =====================================================================
        console.log('\n=== PASO 2: Cambiar mes a Septiembre (reactivo en cliente) ===');
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 850, deviceScaleFactor: 1, mobile: false });
        await pausa(500);

        const cambioSeptiembre = await page.evaluate(() => {
            const selMes = document.getElementById('mes');
            selMes.value = '9';
            selMes.dispatchEvent(new Event('change'));

            const rows = Array.from(document.querySelectorAll('.profesor-radio-row'));
            const rowLucia = rows.find(r => r.textContent.includes('Gaitán, Lucía'));
            const countEl = rowLucia.querySelector('.profesor-clases-count');
            const sinAsisEl = rowLucia.querySelector('.profesor-sin-asistencia-aviso');
            const avisoGeneral = document.getElementById('aviso-sin-asistencia');
            const textoAviso = document.getElementById('aviso-sin-asistencia-texto');

            return {
                mesVal: selMes.value,
                textoCount: countEl ? countEl.textContent.trim() : null,
                textoSinAsis: sinAsisEl ? sinAsisEl.textContent.trim() : null,
                avisoGeneralDisplay: avisoGeneral ? avisoGeneral.style.display : null,
                textoAvisoGeneral: textoAviso ? textoAviso.textContent.trim() : null
            };
        });

        console.log('Estado Septiembre:', cambioSeptiembre);
        if (!cambioSeptiembre.textoCount.includes('3 clases liquidables')) {
            throw new Error(`FALLA Septiembre: Se esperaba "3 clases liquidables", obtenido "${cambioSeptiembre.textoCount}"`);
        }
        if (!cambioSeptiembre.textoSinAsis.includes('1 clase sin asistencia')) {
            throw new Error(`FALLA Septiembre: Se esperaba "1 clase sin asistencia", obtenido "${cambioSeptiembre.textoSinAsis}"`);
        }
        if (!cambioSeptiembre.textoAvisoGeneral.includes('1 clase')) {
            throw new Error(`FALLA Septiembre: El aviso general debía actualizarse a 1 clase, obtenido "${cambioSeptiembre.textoAvisoGeneral}"`);
        }

        // Captura escritorio 1366 px
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '03-septiembre-lucia-1366.png'), Buffer.from(shot.data, 'base64'));

        // Captura móvil 375 px
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '04-septiembre-lucia-375.png'), Buffer.from(shot.data, 'base64'));

        // =====================================================================
        // PASO 3: Volver a Octubre (reactivo en cliente)
        // =====================================================================
        console.log('\n=== PASO 3: Volver a Octubre ===');
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 850, deviceScaleFactor: 1, mobile: false });
        await pausa(500);

        const vueltaOctubre = await page.evaluate(() => {
            const selMes = document.getElementById('mes');
            selMes.value = '10';
            selMes.dispatchEvent(new Event('change'));

            const rows = Array.from(document.querySelectorAll('.profesor-radio-row'));
            const rowLucia = rows.find(r => r.textContent.includes('Gaitán, Lucía'));
            const countEl = rowLucia.querySelector('.profesor-clases-count');
            const sinAsisEl = rowLucia.querySelector('.profesor-sin-asistencia-aviso');
            const avisoGeneral = document.getElementById('aviso-sin-asistencia');
            const textoAviso = document.getElementById('aviso-sin-asistencia-texto');

            return {
                mesVal: selMes.value,
                textoCount: countEl ? countEl.textContent.trim() : null,
                textoSinAsis: sinAsisEl ? sinAsisEl.textContent.trim() : null,
                avisoGeneralDisplay: avisoGeneral ? avisoGeneral.style.display : null,
                textoAvisoGeneral: textoAviso ? textoAviso.textContent.trim() : null
            };
        });

        console.log('Estado Vuelta a Octubre:', vueltaOctubre);
        if (!vueltaOctubre.textoCount.includes('17 clases liquidables')) {
            throw new Error(`FALLA Vuelta a Octubre: Se esperaba "17 clases liquidables", obtenido "${vueltaOctubre.textoCount}"`);
        }
        if (!vueltaOctubre.textoSinAsis.includes('2 clases sin asistencia')) {
            throw new Error(`FALLA Vuelta a Octubre: Se esperaba "2 clases sin asistencia", obtenido "${vueltaOctubre.textoSinAsis}"`);
        }
        if (!vueltaOctubre.textoAvisoGeneral.includes('2 clases')) {
            throw new Error(`FALLA Vuelta a Octubre: El aviso general debía volver a 2 clases, obtenido "${vueltaOctubre.textoAvisoGeneral}"`);
        }

        // Captura escritorio 1366 px
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '05-vuelta-octubre-1366.png'), Buffer.from(shot.data, 'base64'));

        // Captura móvil 375 px
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '06-vuelta-octubre-375.png'), Buffer.from(shot.data, 'base64'));

        console.log('\n--- PRUEBA T87 EN NAVEGADOR COMPLETADA CON ÉXITO ---');
    } finally {
        if (browser) await browser.close().catch(() => {});
        srv.kill('SIGKILL');
    }
}

main().catch(err => {
    console.error('ERROR en prueba:', err);
    process.exit(1);
});
