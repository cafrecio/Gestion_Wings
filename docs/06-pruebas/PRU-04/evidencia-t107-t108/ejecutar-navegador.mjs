import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const BASE_URL = 'http://127.0.0.1:8112';
const EVIDENCIA_DIR = path.resolve('docs/06-pruebas/PRU-04/evidencia-t107-t108');

async function main() {
    const rawFixture = fs.readFileSync(path.join(EVIDENCIA_DIR, 'fixture.json'), 'utf8').replace(/^\uFEFF/, '');
    const fixtureData = JSON.parse(rawFixture);
    const { caja_id, thiago_dni, patin_deporte_id, patin_grupo_id, patin_plan_id, operativa_id } = fixtureData;

    console.log('Iniciando ChromeBrowser...');
    const browser = new ChromeBrowser();
    await browser.start();

    const page = await browser.getFirstPage();
    await page.connect();

    // 1. Iniciar sesión como operativa
    console.log('1. Login como operativa...');
    await page.goto(`${BASE_URL}/login`);
    await page.type('#email', 'operativa@wings.test');
    await page.type('#password', 'Password123!');
    await page.evaluate(`document.querySelector('form[action*="login"] button[type="submit"]').click();`);
    await new Promise(r => setTimeout(r, 1500));

    // 2. CASO 1: Cierre de caja con pantalla vieja
    console.log('2. Caso 1: Navegando a cierre de caja...');
    await page.goto(`${BASE_URL}/caja/${caja_id}/cerrar`);
    await new Promise(r => setTimeout(r, 1000));

    const antes = await page.evaluate(() => ({
        esperadoVisto: document.querySelector('input[name="esperado_visto"]')?.value,
        labels: [...document.querySelectorAll('.info-label')].map(e => e.textContent.trim()),
        values: [...document.querySelectorAll('.info-value')].map(e => e.textContent.trim()),
    }));
    console.log('Cierre antes del cobro intermedio:', antes);

    // Asentar en la base un cobro de $44.000 mientras la pantalla está abierta
    console.log('Insertando cobro de $44.000 en la caja mientras la pantalla sigue abierta...');
    execSync('php docs/06-pruebas/PRU-04/evidencia-t107-t108/insertar-movimiento.php', {
        env: { ...process.env, DB_DATABASE: 'wings_testing_gemini' }
    });

    // Enviar formulario de cierre con lo visto antes ($11.600)
    console.log('Enviando cierre con lo visto previamente ($11.600)...');
    await page.evaluate(() => {
        const c = document.getElementById('efectivo_contado');
        if (c) { c.value = '11600.00'; c.dispatchEvent(new Event('input', {bubbles:true})); c.dispatchEvent(new Event('change', {bubbles:true})); }
        const r = document.getElementById('cambio_retenido');
        if (r) { r.value = '5000.00'; r.dispatchEvent(new Event('input', {bubbles:true})); r.dispatchEvent(new Event('change', {bubbles:true})); }
        document.querySelector('form[data-caja-arqueo] button[type="submit"]').click();
    });
    await new Promise(r => setTimeout(r, 2000));

    // Evaluar la recarga con la pantalla al día
    const resultadoAviso = await page.evaluate(`({
        url: window.location.href,
        alerta: document.querySelector('[role="alert"]')?.textContent?.trim()?.replace(/\\s+/g, ' '),
        esperadoVisto: document.querySelector('input[name="esperado_visto"]')?.value,
        infoValues: [...document.querySelectorAll('.info-value')].map(e => e.textContent.trim()),
    })`);
    console.log('Resultado del intento de cierre con aviso al día:', resultadoAviso);

    await page.screenshot(path.join(EVIDENCIA_DIR, 'caso-1-aviso-pantalla-al-dia.png'));
    fs.writeFileSync(path.join(EVIDENCIA_DIR, 'caso-1-aviso-pantalla-al-dia.txt'), JSON.stringify(resultadoAviso, null, 2));

    // Cerrar de verdad con lo contado actualizado ($55.600)
    console.log('Cerrando con lo contado al día ($55.600)...');
    await page.evaluate(`
        const c = document.getElementById('efectivo_contado');
        if (c) { c.value = '55600.00'; c.dispatchEvent(new Event('input', {bubbles:true})); c.dispatchEvent(new Event('change', {bubbles:true})); }
        const r = document.getElementById('cambio_retenido');
        if (r) { r.value = '5000.00'; r.dispatchEvent(new Event('input', {bubbles:true})); r.dispatchEvent(new Event('change', {bubbles:true})); }
        document.querySelector('form[data-caja-arqueo] button[type="submit"]').click();
    `);
    await new Promise(r => setTimeout(r, 2000));

    await page.screenshot(path.join(EVIDENCIA_DIR, 'caso-1-cerrada-al-dia.png'));
    console.log('Caso 1 completado exitosamente.');

    // 3. CASO 9: Inscripción en segunda ficha por pantalla
    console.log('3. Caso 9: Navegando a alta de alumno...');
    await page.goto(`${BASE_URL}/alumnos/create`);
    await new Promise(r => setTimeout(r, 1500));

    console.log('Ingresando DNI de Thiago (45000001) y fecha de ingreso...');
    await page.evaluate(`
        const d = document.getElementById('dni');
        if (d) {
            d.value = '${thiago_dni}';
            d.dispatchEvent(new Event('input', {bubbles:true}));
            d.dispatchEvent(new Event('change', {bubbles:true}));
        }
        const f = document.getElementById('fecha_alta');
        if (f) {
            f.value = '2026-11-01';
            f.dispatchEvent(new Event('input', {bubbles:true}));
            f.dispatchEvent(new Event('change', {bubbles:true}));
        }
    `);

    await new Promise(r => setTimeout(r, 1500));

    const resultadoPreview = await page.evaluate(`({
        avisoInscripcion: document.getElementById('inscripcion-aviso')?.textContent?.trim(),
        importeVisto: document.getElementById('inscripcion-importe-visto')?.value,
    })`);
    console.log('Resultado del preview dinámico de inscripción:', resultadoPreview);

    await page.screenshot(path.join(EVIDENCIA_DIR, 'caso-9-aviso-previo-segunda-ficha.png'));
    fs.writeFileSync(path.join(EVIDENCIA_DIR, 'caso-9-aviso-previo-segunda-ficha.txt'), JSON.stringify(resultadoPreview, null, 2));

    // Completar el alta de la segunda ficha en Patín
    console.log('Completando alta en Patín...');
    await page.evaluate(`
        document.getElementById('apellido').value = 'Silva';
        document.getElementById('nombre').value = 'Thiago';
        document.getElementById('fecha_nacimiento').value = '2010-05-15';
        document.getElementById('celular').value = '1144445555';
        document.getElementById('nombre_tutor').value = 'Silva, Roberto';
        document.getElementById('telefono_tutor').value = '1144445555';

        const dep = document.getElementById('deporte_id');
        dep.value = '${patin_deporte_id}';
        dep.dispatchEvent(new Event('change', {bubbles:true}));
    `);

    await new Promise(r => setTimeout(r, 500));

    await page.evaluate(`
        const gr = document.getElementById('grupo_id');
        gr.value = '${patin_grupo_id}';
        gr.dispatchEvent(new Event('change', {bubbles:true}));
    `);

    await new Promise(r => setTimeout(r, 500));

    await page.evaluate(`
        const pl = document.getElementById('plan_id');
        pl.value = '${patin_plan_id}';
        pl.dispatchEvent(new Event('change', {bubbles:true}));
    `);

    // Esperar a que el AJAX de cuota-alta-preview termine y habilite los radios
    await new Promise(r => setTimeout(r, 1500));

    await page.evaluate(`
        const cuotaSi = document.getElementById('cuota-alta-si');
        if (cuotaSi) {
            cuotaSi.checked = true;
            cuotaSi.dispatchEvent(new Event('change', {bubbles:true}));
        }
        document.querySelector('form[data-alumno-form] button[type="submit"]').click();
    `);

    await new Promise(r => setTimeout(r, 2500));

    const fichaResult = await page.evaluate(`({
        url: window.location.href,
        titulo: document.title,
        bodyText: document.body.innerText.replace(/\\s+/g, ' ').slice(0, 600)
    })`);
    console.log('Ficha tras el alta:', fichaResult);

    await page.screenshot(path.join(EVIDENCIA_DIR, 'caso-9-ficha-segunda-ficha.png'));

    await page.close();
    await browser.close();
    console.log('Verificación por navegador ejecutada exitosamente.');
}

main().catch(err => {
    console.error('Error:', err);
    process.exit(1);
});
