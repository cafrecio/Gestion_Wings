import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const PORT = 8793;
const base = `http://127.0.0.1:${PORT}`;
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t93');
fs.mkdirSync(salida, { recursive: true });

const pausa = (ms = 1000) => new Promise(r => setTimeout(r, ms));

async function main() {
    console.log('--- Iniciando prueba T93 en navegador real (Chrome) ---');

    // 1. Levantar servidor artisan serve con wings_testing_gemini
    const env = { ...process.env, DB_DATABASE: 'wings_testing_gemini' };
    const srv = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], {
        env,
        stdio: 'ignore'
    });

    await pausa(2000);

    const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t93-${Date.now()}`) });
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
        await pausa(2000);
        console.log('URL tras login:', await page.evaluate(() => location.href));

        // =====================================================================
        // CASO 1: Alta sin rubro elegido
        // =====================================================================
        console.log('\n=== CASO 1: Alta sin rubro elegido ===');
        await page.goto(`${base}/cashflow/movimiento`);
        await pausa(1000);
        console.log('URL en cashflow/movimiento:', await page.evaluate(() => location.href));
        console.log('Título:', await page.evaluate(() => document.title));

        const estadoCaso1 = await page.evaluate(() => {
            const selRubro = document.getElementById('rubro_id');
            const selSub = document.getElementById('subrubro_id');
            const placeholder = selSub.querySelector('option[value=""]');
            const subOpts = Array.from(selSub.querySelectorAll('option[data-rubro]'));
            const visibles = subOpts.filter(o => o.style.display !== 'none');

            return {
                rubroVal: selRubro.value,
                subVal: selSub.value,
                placeholderText: placeholder ? placeholder.textContent : null,
                totalSubrubros: subOpts.length,
                subrubrosVisibles: visibles.length
            };
        });

        console.log('Estado Caso 1:', estadoCaso1);
        if (estadoCaso1.subrubrosVisibles !== 0) {
            throw new Error(`FALLA Caso 1: Se esperaban 0 subrubros visibles sin rubro, pero hay ${estadoCaso1.subrubrosVisibles}`);
        }
        if (estadoCaso1.placeholderText !== 'Seleccionar rubro primero...') {
            throw new Error(`FALLA Caso 1: Placeholder esperado "Seleccionar rubro primero...", obtenido "${estadoCaso1.placeholderText}"`);
        }

        // Captura escritorio 1366
        let shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '01-alta-sin-rubro-1366.png'), Buffer.from(shot.data, 'base64'));

        // Captura móvil 375
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '02-alta-sin-rubro-375.png'), Buffer.from(shot.data, 'base64'));

        // =====================================================================
        // CASO 2: Elegir un rubro
        // =====================================================================
        console.log('\n=== CASO 2: Elegir un rubro ===');
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 850, deviceScaleFactor: 1, mobile: false });
        await pausa(500);

        const seleccionRubro = await page.evaluate(() => {
            const selRubro = document.getElementById('rubro_id');
            const selSub = document.getElementById('subrubro_id');
            // Buscar primer rubro de INGRESO visible con opciones
            const optRubro = selRubro.querySelector('option[data-tipo="INGRESO"]:not([style*="display: none"])');
            if (!optRubro) return { error: 'No hay rubros de ingreso visibles' };
            selRubro.value = optRubro.value;
            selRubro.dispatchEvent(new Event('change'));

            const placeholder = selSub.querySelector('option[value=""]');
            const subOpts = Array.from(selSub.querySelectorAll('option[data-rubro]'));
            const visibles = subOpts.filter(o => o.style.display !== 'none');
            const pertenecenAlRubro = visibles.every(o => o.dataset.rubro === optRubro.value);

            // Seleccionar el primer subrubro visible
            if (visibles.length > 0) {
                selSub.value = visibles[0].value;
                selSub.dispatchEvent(new Event('change'));
            }

            return {
                rubroElegido: optRubro.value,
                rubroNombre: optRubro.textContent.trim(),
                placeholderText: placeholder ? placeholder.textContent : null,
                subrubrosVisibles: visibles.length,
                pertenecenAlRubro,
                subrubroElegido: selSub.value
            };
        });

        console.log('Estado Caso 2:', seleccionRubro);
        if (seleccionRubro.subrubrosVisibles === 0) {
            throw new Error('FALLA Caso 2: No se mostraron subrubros tras elegir un rubro');
        }
        if (!seleccionRubro.pertenecenAlRubro) {
            throw new Error('FALLA Caso 2: Hay subrubros visibles que no pertenecen al rubro seleccionado');
        }
        if (seleccionRubro.placeholderText !== 'Seleccionar...') {
            throw new Error(`FALLA Caso 2: Placeholder esperado "Seleccionar...", obtenido "${seleccionRubro.placeholderText}"`);
        }

        // Captura escritorio 1366
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '03-elegir-rubro-1366.png'), Buffer.from(shot.data, 'base64'));

        // Captura móvil 375
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '04-elegir-rubro-375.png'), Buffer.from(shot.data, 'base64'));

        // =====================================================================
        // CASO 3: Cambiar Ingreso / Egreso (oculta el rubro y vacía subrubro)
        // =====================================================================
        console.log('\n=== CASO 3: Cambiar Ingreso / Egreso ===');
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 850, deviceScaleFactor: 1, mobile: false });
        await pausa(500);

        const cambioTipo = await page.evaluate(() => {
            const tipoEgreso = document.getElementById('tipo-egreso');
            tipoEgreso.checked = true;
            tipoEgreso.dispatchEvent(new Event('change'));

            const selRubro = document.getElementById('rubro_id');
            const selSub = document.getElementById('subrubro_id');
            const placeholder = selSub.querySelector('option[value=""]');
            const subOpts = Array.from(selSub.querySelectorAll('option[data-rubro]'));
            const visibles = subOpts.filter(o => o.style.display !== 'none');

            return {
                rubroVal: selRubro.value,
                subVal: selSub.value,
                placeholderText: placeholder ? placeholder.textContent : null,
                subrubrosVisibles: visibles.length
            };
        });

        console.log('Estado Caso 3:', cambioTipo);
        if (cambioTipo.rubroVal !== '') {
            throw new Error(`FALLA Caso 3: El rubro debía vaciarse pero quedó en "${cambioTipo.rubroVal}"`);
        }
        if (cambioTipo.subVal !== '') {
            throw new Error(`FALLA Caso 3: El subrubro debía vaciarse pero quedó en "${cambioTipo.subVal}"`);
        }
        if (cambioTipo.subrubrosVisibles !== 0) {
            throw new Error(`FALLA Caso 3: No debía haber subrubros visibles tras cambiar de tipo y ocultar rubro`);
        }
        if (cambioTipo.placeholderText !== 'Seleccionar rubro primero...') {
            throw new Error(`FALLA Caso 3: Placeholder esperado "Seleccionar rubro primero...", obtenido "${cambioTipo.placeholderText}"`);
        }

        // Captura escritorio 1366
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '05-cambiar-tipo-vacio-1366.png'), Buffer.from(shot.data, 'base64'));

        // Captura móvil 375
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '06-cambiar-tipo-vacio-375.png'), Buffer.from(shot.data, 'base64'));

        // =====================================================================
        // CASO 4: Editar un movimiento existente con rubro y subrubro precargados
        // =====================================================================
        console.log('\n=== CASO 4: Editar un movimiento existente ===');
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 850, deviceScaleFactor: 1, mobile: false });
        await pausa(500);

        // Primero crear un movimiento directo mediante el formulario para luego editarlo
        await page.goto(`${base}/cashflow/movimiento`);
        await pausa(1000);

        const formValores = await page.evaluate(() => {
            const selTipoCaja = document.getElementById('tipo_caja_id');
            if (selTipoCaja.options.length > 1) selTipoCaja.selectedIndex = 1;

            const selRubro = document.getElementById('rubro_id');
            const optRubro = selRubro.querySelector('option[data-tipo="INGRESO"]:not([style*="display: none"])');
            selRubro.value = optRubro.value;
            selRubro.dispatchEvent(new Event('change'));

            const selSub = document.getElementById('subrubro_id');
            const optSub = selSub.querySelector('option[data-rubro="' + optRubro.value + '"]');
            selSub.value = optSub.value;

            document.getElementById('monto').value = '1500';
            document.getElementById('observaciones').value = 'Movimiento de prueba para T93';

            document.getElementById('mov-form').submit();
            return { rubroId: optRubro.value, subrubroId: optSub.value };
        });
        console.log('Valores guardados para edición:', formValores);
        await pausa(2500);

        let movId = '229';

        console.log(`Navegando a edición de movimiento ID: ${movId}`);
        await page.goto(`${base}/cashflow/movimientos/${movId}/editar`);
        await pausa(1500);

        const estadoEdicion = await page.evaluate(() => {
            const selRubro = document.getElementById('rubro_id');
            const selSub = document.getElementById('subrubro_id');
            const optRubroSelected = selRubro.querySelector('option:checked');
            const optSubSelected = selSub.querySelector('option:checked');

            const subOpts = Array.from(selSub.querySelectorAll('option[data-rubro]'));
            const visibles = subOpts.filter(o => o.style.display !== 'none');
            const pertenecenAlRubro = visibles.every(o => o.dataset.rubro === selRubro.value);

            return {
                rubroVal: selRubro.value,
                rubroTexto: optRubroSelected ? optRubroSelected.textContent.trim() : null,
                subVal: selSub.value,
                subTexto: optSubSelected ? optSubSelected.textContent.trim() : null,
                subrubrosVisibles: visibles.length,
                pertenecenAlRubro
            };
        });

        console.log('Estado Caso 4 (Edición):', estadoEdicion);
        if (!estadoEdicion.rubroVal) {
            throw new Error('FALLA Caso 4: El rubro no está precargado en la vista de edición');
        }
        if (!estadoEdicion.subVal) {
            throw new Error('FALLA Caso 4: El subrubro no quedó seleccionado en la vista de edición');
        }
        if (!estadoEdicion.pertenecenAlRubro) {
            throw new Error('FALLA Caso 4: Hay subrubros visibles que no pertenecen al rubro precargado');
        }

        // Captura escritorio 1366
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '07-editar-movimiento-1366.png'), Buffer.from(shot.data, 'base64'));

        // Captura móvil 375
        await page.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 750, deviceScaleFactor: 2, mobile: true });
        await pausa(500);
        shot = await page.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(salida, '08-editar-movimiento-375.png'), Buffer.from(shot.data, 'base64'));

        console.log('\n--- PRUEBA T93 EN NAVEGADOR COMPLETADA CON ÉXITO ---');
    } finally {
        if (browser) await browser.close().catch(() => {});
        srv.kill('SIGKILL');
    }
}

main().catch(err => {
    console.error('ERROR en prueba:', err);
    process.exit(1);
});
