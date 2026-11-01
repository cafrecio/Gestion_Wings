import path from 'node:path';
import fs from 'node:fs';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const BASE = 'http://127.0.0.1:8124';
const evidenciaDir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t117-t118');
fs.mkdirSync(evidenciaDir, { recursive: true });

const esperar = ms => new Promise(r => setTimeout(r, ms));
const resultados = [];
function registrar(nombre, ok, detalle = '') {
    resultados.push({ nombre, ok, detalle });
    console.log(`${ok ? '✓ PASS' : '✗ FAIL'}: ${nombre} ${detalle ? '(' + detalle + ')' : ''}`);
}

const navegador = new ChromeBrowser({ userDataDir: path.resolve('storage/app/chrome-t117-test') });
await navegador.start();
const pagina = await navegador.newPage();
await pagina.connect();
await pagina.send('Network.enable');

async function borrarSesion() {
    const { cookies } = await pagina.send('Network.getCookies');
    for (const c of cookies) {
        if (/session/.test(c.name)) {
            await pagina.send('Network.deleteCookies', { name: c.name, url: BASE });
        }
    }
    await esperar(200);
}

async function login(email, password = 'password') {
    await borrarSesion();
    await pagina.goto(`${BASE}/login`);
    await esperar(500);
    const res = await pagina.send('Runtime.evaluate', {
        expression: `(() => {
            const emailInput = document.querySelector('[name=email]');
            const passInput = document.querySelector('[name=password]');
            const btn = document.querySelector('button[type=submit]');
            if (!emailInput || !passInput || !btn) {
                throw new Error('No se encontraron los inputs de login. HTML: ' + document.body.innerHTML.substring(0, 300));
            }
            emailInput.value = ${JSON.stringify(email)};
            passInput.value = ${JSON.stringify(password)};
            btn.click();
        })()`,
        awaitPromise: true,
        returnByValue: true,
    });
    if (res.exceptionDetails) {
        throw new Error(`Fallo en login: ${res.exceptionDetails.text} - ${JSON.stringify(res.exceptionDetails.exception || {})}`);
    }
    await esperar(1500);
}

try {
    console.log('--- Iniciando comprobación en navegador real ---');

    // ─────────────────────────────────────────────────────────────
    // 1. Cobrar (cobrar.js:164)
    // ─────────────────────────────────────────────────────────────
    await login('operativo@wings.test');
    console.log('Login operativo OK, navegando a /caja/cobrar/1...');
    await pagina.goto(`${BASE}/caja/cobrar/1`);
    await esperar(800);

    // Seleccionar período y medio de pago
    await pagina.evaluate(`(() => {
        const chk = document.querySelector('input[name="periodos[]"]');
        if (chk && !chk.checked) chk.click();
        const selMedio = document.getElementById('tipo_caja_id');
        if (selMedio && selMedio.options.length > 1) {
            selMedio.selectedIndex = 1;
            selMedio.dispatchEvent(new Event('change', { bubbles: true }));
        }
    })()`);
    await esperar(300);

    // Borrar cookies de sesión para simular vencimiento
    await borrarSesion();

    // Disparar cobro haciendo clic en btn-cobrar
    await pagina.evaluate(`(() => {
        const btn = document.getElementById('btn-cobrar');
        if (btn) btn.click();
    })()`);
    await esperar(2000);

    const urlCobrar = await pagina.evaluate('location.pathname');
    const textoCobrar = await pagina.evaluate('document.body.innerText');
    const avisoSesionCobrar = textoCobrar.includes('Tu sesión expiró. Iniciá sesión nuevamente.');
    const okCobrar = urlCobrar === '/login' && avisoSesionCobrar;

    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '01-cobrar-sesion-vencida-login.png'), Buffer.from(s.data, 'base64'));
    });
    registrar('1. Cobrar con sesión vencida lleva a /login con aviso', okCobrar, `URL: ${urlCobrar}`);

    // Reingresar y verificar destino según rol (operativo -> /operativo)
    await login('operativo@wings.test');
    const urlReingresoOperativo = await pagina.evaluate('location.pathname');
    registrar('8a. Reingreso de operativa llega a inicio de su rol', urlReingresoOperativo === '/operativo', `URL: ${urlReingresoOperativo}`);

    // ─────────────────────────────────────────────────────────────
    // 2. Guardar lista (clases-show.js:104) en móvil a 375px
    // ─────────────────────────────────────────────────────────────
    await pagina.send('Emulation.setDeviceMetricsOverride', { width: 375, height: 812, deviceScaleFactor: 2, mobile: true });
    await pagina.goto(`${BASE}/clases/1`);
    await esperar(800);

    // Marcar presente a un alumno
    await pagina.evaluate(`(() => {
        const chk = document.querySelector('.presente-checkbox');
        if (chk) { chk.checked = true; chk.dispatchEvent(new Event('change', { bubbles: true })); }
    })()`);
    await esperar(300);

    await borrarSesion();

    // Guardar lista
    await pagina.evaluate(`(() => {
        const btn = document.getElementById('btn-guardar-asistencias');
        if (!btn) throw new Error('No se encontró #btn-guardar-asistencias');
        btn.click();
    })()`);
    await esperar(2000);

    const urlGuardarLista = await pagina.evaluate('location.pathname');
    const textoGuardarLista = await pagina.evaluate('document.body.innerText');
    const avisoGuardarLista = textoGuardarLista.includes('Tu sesión expiró. Iniciá sesión nuevamente.');
    const okGuardarLista = urlGuardarLista === '/login' && avisoGuardarLista;

    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '02-guardar-lista-sesion-vencida-375.png'), Buffer.from(s.data, 'base64'));
    });
    registrar('2. Guardar lista con sesión vencida a 375px lleva a /login con aviso', okGuardarLista, `URL: ${urlGuardarLista}`);

    // Restaurar viewport escritorio
    await pagina.send('Emulation.clearDeviceMetricsOverride');

    // ─────────────────────────────────────────────────────────────
    // 3. Cancelar clase (clases-show.js:213)
    // ─────────────────────────────────────────────────────────────
    await login('admin@wings.test');
    await pagina.goto(`${BASE}/clases/1`);
    await esperar(800);

    // Abrir subpanel cancelar y escribir motivo
    await pagina.evaluate(() => {
        const btnAbrir = document.getElementById('btn-abrir-cancelar');
        if (btnAbrir) btnAbrir.click();
        const inputMotivo = document.getElementById('input-motivo-cancelacion');
        if (inputMotivo) {
            inputMotivo.value = 'Lluvia torrencial';
            inputMotivo.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
    await esperar(400);

    await borrarSesion();

    // Confirmar cancelación
    await pagina.evaluate(() => {
        const btnConfirmar = document.getElementById('btn-confirmar-cancelar');
        if (!btnConfirmar) throw new Error('No se encontró #btn-confirmar-cancelar');
        btnConfirmar.click();
    });
    await esperar(1500);

    const urlCancelar = await pagina.evaluate(() => location.pathname);
    const textoCancelar = await pagina.evaluate(() => document.body.innerText);
    const avisoCancelar = textoCancelar.includes('Tu sesión expiró. Iniciá sesión nuevamente.');
    const okCancelar = urlCancelar === '/login' && avisoCancelar;

    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '03-cancelar-clase-sesion-vencida.png'), Buffer.from(s.data, 'base64'));
    });
    registrar('3. Cancelar clase con sesión vencida lleva a /login con aviso', okCancelar, `URL: ${urlCancelar}`);

    // Reingresar como admin y verificar destino (admin -> /admin/dashboard)
    await login('admin@wings.test');
    const urlReingresoAdmin = await pagina.evaluate(() => location.pathname);
    registrar('8b. Reingreso de admin llega a inicio de su rol', urlReingresoAdmin === '/admin/dashboard', `URL: ${urlReingresoAdmin}`);

    // ─────────────────────────────────────────────────────────────
    // T118: Comprobaciones visuales en pantalla
    // ─────────────────────────────────────────────────────────────

    // Revisión: REACTIVADO AUTOMÁTICAMENTE
    await pagina.goto(`${BASE}/revision-cobranza?estado=RESUELTO`);
    await esperar(600);
    const htmlRevision = await pagina.evaluate(() => document.body.innerHTML);
    const okRevision = htmlRevision.includes('REACTIVADO AUTOMÁTICAMENTE') && !htmlRevision.includes('REACTIVADO AUTOMáTICAMENTE');
    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '04-t118-revision-reactivado-automaticamente.png'), Buffer.from(s.data, 'base64'));
    });
    registrar('T118. Revisión muestra REACTIVADO AUTOMÁTICAMENTE con tilde', okRevision);

    // Caja Detalle: FÚT
    await pagina.goto(`${BASE}/caja/1/detalle`);
    await esperar(600);
    const htmlDetalle = await pagina.evaluate(() => document.body.innerHTML);
    const okFut = htmlDetalle.includes('FÚT');
    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '05-t118-caja-detalle-fut.png'), Buffer.from(s.data, 'base64'));
    });
    registrar('T118. Caja detalle muestra abreviatura FÚT', okFut);

    // Movimientos: Observación recortada con acentos
    await pagina.goto(`${BASE}/movimientos`);
    await esperar(600);
    const htmlMovimientos = await pagina.evaluate(() => document.body.innerHTML);
    const okMovimientos = htmlMovimientos.includes('12345678901234567890123456789012345á…') || htmlMovimientos.includes('12345678901234567890123456789012345á');
    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, '06-t118-movimientos-observaciones.png'), Buffer.from(s.data, 'base64'));
    });
    registrar('T118. Movimientos recorta observación sin romper caracteres', okMovimientos);

} catch (err) {
    console.error('Error durante la ejecución del navegador:', err);
    registrar('Ejecución sin excepciones', false, err.message);
} finally {
    await pagina.close();
    await navegador.close();
    fs.writeFileSync(path.join(evidenciaDir, 'resultados-navegador.json'), JSON.stringify(resultados, null, 2));
    console.log('--- Comprobación finalizada. Resultados guardados en evidencia-t117-t118/ ---');
}
