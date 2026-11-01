import path from 'node:path';
import fs from 'node:fs';
import { execSync } from 'node:child_process';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const BASE = 'http://127.0.0.1:8128';
const evidenciaDir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t113');
fs.mkdirSync(evidenciaDir, { recursive: true });

// Resembrar base de datos antes de iniciar
execSync('php docs/06-pruebas/PRU-04/evidencia-t113/seed-t113.php', {
    env: { ...process.env, DB_DATABASE: 'wings_testing_gemini' },
    stdio: 'inherit'
});

const esperar = ms => new Promise(r => setTimeout(r, ms));
const resultados = [];
function registrar(nombre, ok, detalle = '') {
    resultados.push({ nombre, ok, detalle });
    console.log(`${ok ? '✓ PASS' : '✗ FAIL'}: ${nombre} ${detalle ? '(' + detalle + ')' : ''}`);
}

const navegador = new ChromeBrowser({ userDataDir: path.resolve('storage/app/chrome-t113-test') });
await navegador.start();
const pagina = await navegador.newPage();
await pagina.connect();
await pagina.send('Network.enable');

async function login(email, password = 'password') {
    await pagina.goto(`${BASE}/login`);
    await esperar(500);
    const res = await pagina.send('Runtime.evaluate', {
        expression: `(() => {
            const emailInput = document.querySelector('[name=email]');
            const passInput = document.querySelector('[name=password]');
            const btn = document.querySelector('button[type=submit]');
            if (!emailInput || !passInput || !btn) {
                throw new Error('No se encontraron los inputs de login.');
            }
            emailInput.value = ${JSON.stringify(email)};
            passInput.value = ${JSON.stringify(password)};
            btn.click();
        })()`,
        awaitPromise: true,
        returnByValue: true,
    });
    if (res.exceptionDetails) {
        throw new Error(`Fallo en login: ${res.exceptionDetails.text}`);
    }
    await esperar(1500);
}

try {
    console.log('--- Iniciando comprobación en navegador real T113 ---');

    await login('admin@wings.test');

    // ─────────────────────────────────────────────────────────────
    // CASO 1: Clase pasada validada sin liquidación
    // ─────────────────────────────────────────────────────────────
    await pagina.goto(`${BASE}/clases/1`);
    await esperar(800);

    const html1Antes = await pagina.evaluate(() => document.body.innerHTML);
    const tieneBotonDesvalidar1 = html1Antes.includes('Desvalidar') && !html1Antes.includes('>Validar<');
    const tieneBadgeValidada1 = html1Antes.includes('Validada para liquidación');
    registrar('Caso 1: Botón Desvalidar y badge Validada visibles inicialmente', tieneBotonDesvalidar1 && tieneBadgeValidada1);

    // Captura escritorio
    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '01-t113-caso1-desvalidar-visible-desktop.png'), Buffer.from(s.data, 'base64'));
    });

    // Captura móvil 375px
    await pagina.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 812, deviceScaleFactor: 2, mobile: true });
    await esperar(300);
    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '01-t113-caso1-desvalidar-visible-375.png'), Buffer.from(s.data, 'base64'));
    });
    await pagina.send('Emulation.clearDeviceMetricsOverride');
    await esperar(200);

    await pagina.evaluate(() => {
        const btn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.trim() === 'Desvalidar');
        if (!btn) throw new Error('No se encontró botón Desvalidar');
        btn.closest('form').submit();
    });
    await esperar(2000);

    const html1Despues = await pagina.evaluate(() => document.body.innerHTML);
    const flashDesmarcada = html1Despues.includes('Clase desmarcada de validación.');
    const ofreceValidar = await pagina.evaluate(() => Array.from(document.querySelectorAll('button')).some(b => b.innerText.trim() === 'Validar'));
    const sinDesvalidar = await pagina.evaluate(() => !Array.from(document.querySelectorAll('button')).some(b => b.innerText.trim() === 'Desvalidar'));
    const sinBadge = !html1Despues.includes('Validada para liquidación');
    registrar('Caso 1: Clic en Desvalidar muestra flash de éxito, badge desaparece y ofrece Validar', flashDesmarcada && ofreceValidar && sinDesvalidar && sinBadge);

    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '02-t113-caso1-desvalidada-exito-desktop.png'), Buffer.from(s.data, 'base64'));
    });

    await pagina.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 812, deviceScaleFactor: 2, mobile: true });
    await esperar(300);
    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '02-t113-caso1-desvalidada-exito-375.png'), Buffer.from(s.data, 'base64'));
    });
    await pagina.send('Emulation.clearDeviceMetricsOverride');
    await esperar(200);

    // ─────────────────────────────────────────────────────────────
    // CASO 2: Clase futura validada (19/12)
    // ─────────────────────────────────────────────────────────────
    await pagina.goto(`${BASE}/clases/2`);
    await esperar(800);

    const html2Antes = await pagina.evaluate(() => document.body.innerHTML);
    const tieneDesvalidar2 = html2Antes.includes('Desvalidar');
    registrar('Caso 2: Clase futura validada ofrece botón Desvalidar', tieneDesvalidar2);

    await pagina.evaluate(() => {
        const btn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.trim() === 'Desvalidar');
        btn.closest('form').submit();
    });
    await esperar(1500);

    const html2Despues = await pagina.evaluate(() => document.body.innerHTML);
    const flash2 = html2Despues.includes('Clase desmarcada de validación.');
    const ofreceValidar2 = await pagina.evaluate(() => Array.from(document.querySelectorAll('button')).some(b => b.innerText.trim() === 'Validar'));
    registrar('Caso 2: Clase futura se desvalida correctamente', flash2 && ofreceValidar2);

    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '03-t113-caso2-clase-futura-desvalidada.png'), Buffer.from(s.data, 'base64'));
    });

    // ─────────────────────────────────────────────────────────────
    // CASO 6: Clase en liquidación por hora ABIERTA (no ofrece Desvalidar)
    // ─────────────────────────────────────────────────────────────
    await pagina.goto(`${BASE}/clases/3`);
    await esperar(800);

    const html3 = await pagina.evaluate(() => document.body.innerHTML);
    const noOfreceDesvalidar = !html3.includes('>Desvalidar<');
    registrar('Caso 6: Clase en liquidación abierta no ofrece botón Desvalidar', noOfreceDesvalidar);

    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '04-t113-caso6-sin-boton-desvalidar.png'), Buffer.from(s.data, 'base64'));
    });

    // Forzado: enviar formulario directo con accion=desvalidar
    await pagina.evaluate(() => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/clases/3/validar';
        form.innerHTML = `
            <input type="hidden" name="_token" value="${token}">
            <input type="hidden" name="_method" value="PATCH">
            <input type="hidden" name="accion" value="desvalidar">
        `;
        document.body.appendChild(form);
        form.submit();
    });
    await esperar(1500);

    const html3Forzado = await pagina.evaluate(() => document.body.innerHTML);
    const mensajeRechazo6 = 'No se puede desvalidar: la clase está en una liquidación abierta. Eliminá esa liquidación, desvalidá y generala de nuevo.';
    const okRechazo6 = html3Forzado.includes(mensajeRechazo6);
    registrar('Caso 6: Forzar desvalidación en liquidación abierta es rechazado con mensaje exacto', okRechazo6);

    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '05-t113-caso6-rechazo-forzado.png'), Buffer.from(s.data, 'base64'));
    });

    // ─────────────────────────────────────────────────────────────
    // CASO 9: Doble clic y pantalla vieja
    // ─────────────────────────────────────────────────────────────
    await pagina.goto(`${BASE}/clases/4`);
    await esperar(800);

    // Primera vez: desvalidar normal
    await pagina.evaluate(() => {
        const btn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.trim() === 'Desvalidar');
        btn.closest('form').submit();
    });
    await esperar(1500);

    // Segunda vez: simular envío de 'desvalidar' desde pantalla vieja
    await pagina.evaluate(() => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/clases/4/validar';
        form.innerHTML = `
            <input type="hidden" name="_token" value="${token}">
            <input type="hidden" name="_method" value="PATCH">
            <input type="hidden" name="accion" value="desvalidar">
        `;
        document.body.appendChild(form);
        form.submit();
    });
    await esperar(1500);

    const html4Vieja = await pagina.evaluate(() => document.body.innerHTML);
    const mensajeRechazo9 = 'La clase cambió mientras la mirabas. Recargá la pantalla.';
    const okRechazo9 = html4Vieja.includes(mensajeRechazo9);
    registrar('Caso 9: Desvalidar con pantalla vieja rechaza con «La clase cambió mientras la mirabas. Recargá la pantalla.»', okRechazo9);

    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '06-t113-caso9-pantalla-vieja-error.png'), Buffer.from(s.data, 'base64'));
    });

} catch (err) {
    console.error('Error durante la ejecución del navegador:', err);
    registrar('Ejecución sin excepciones', false, err.message);
} finally {
    await pagina.close();
    await navegador.close();
    fs.writeFileSync(path.join(evidenciaDir, 'resultados-navegador.json'), JSON.stringify(resultados, null, 2));
    console.log('--- Comprobación de navegador T113 finalizada. ---');
}
