import path from 'node:path';
import fs from 'node:fs';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const BASE = 'http://127.0.0.1:8126';
const evidenciaDir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t12-t13');
fs.mkdirSync(evidenciaDir, { recursive: true });

const esperar = ms => new Promise(r => setTimeout(r, ms));
const resultados = [];

function registrar(nombre, ok, detalle = '') {
    resultados.push({ nombre, ok, detalle });
    console.log(`${ok ? '✓ PASS' : '✗ FAIL'}: ${nombre} ${detalle ? '(' + detalle + ')' : ''}`);
}

const navegador = new ChromeBrowser({ userDataDir: path.resolve('storage/app/chrome-t12-test') });
await navegador.start();
const pagina = await navegador.newPage();
await pagina.connect();
await pagina.send('Network.enable');

async function borrarSesion() {
    await pagina.send('Network.clearBrowserCookies');
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
            if (!emailInput || !passInput || !btn) throw new Error('Inputs de login no encontrados');
            emailInput.value = ${JSON.stringify(email)};
            passInput.value = ${JSON.stringify(password)};
            btn.click();
        })()`,
        awaitPromise: true,
        returnByValue: true,
    });
    if (res.exceptionDetails) {
        throw new Error(`Fallo login: ${res.exceptionDetails.text}`);
    }
    await esperar(1200);
}

async function verificarBotonAyuda(pantallaRuta, esperadoSlug, capturaNombre, descripcion) {
    await pagina.goto(`${BASE}${pantallaRuta}`);
    await esperar(800);

    const hayBoton = await pagina.evaluate(() => {
        const btn = document.querySelector('a[aria-label="Ayuda sobre esta pantalla"]');
        if (!btn) return false;
        btn.click();
        return true;
    });

    if (!hayBoton) {
        registrar(descripcion, false, 'No se encontró botón ? en la topbar');
        return;
    }

    await esperar(1000);
    const urlDestino = await pagina.evaluate(() => location.pathname);
    const titulo = await pagina.evaluate(() => document.title);
    const ok = urlDestino === `/ayuda/${esperadoSlug}`;

    await pagina.send('Page.captureScreenshot').then(s => {
        fs.writeFileSync(path.join(evidenciaDir, capturaNombre), Buffer.from(s.data, 'base64'));
    });

    registrar(descripcion, ok, `Destino: ${urlDestino} | Título: ${titulo}`);
}

try {
    console.log('--- Iniciando comprobación del botón ? en navegador real ---');

    // ─────────────────────────────────────────────────────────────
    // 1. ADMIN (T96, T97, T102)
    // ─────────────────────────────────────────────────────────────
    console.log('Ingresando como ADMIN...');
    await login('admin@wings.test');

    // T96: /tipos-caja
    await verificarBotonAyuda(
        '/tipos-caja',
        'administrar-tipos-caja',
        'signo-pregunta-sv-admin-tipos-caja.png',
        'Admin T96: /tipos-caja abre administrar-tipos-caja'
    );

    // T96: /caja/configuracion (Cargar saldo inicial de Efectivo)
    await verificarBotonAyuda(
        '/caja/configuracion',
        'configurar-mostrador',
        'signo-pregunta-sv-admin-configurar-mostrador.png',
        'Admin T96: /caja/configuracion abre configurar-mostrador'
    );

    // T96: /configuraciones (Configuración general de parámetros)
    await verificarBotonAyuda(
        '/configuraciones',
        'configurar-parametros',
        'signo-pregunta-sv-admin-configuraciones.png',
        'Admin: /configuraciones abre configurar-parametros'
    );

    // T97: /cashflow/transferencia
    await verificarBotonAyuda(
        '/cashflow/transferencia',
        'transferir-entre-cuentas',
        'signo-pregunta-sv-admin-transferencia.png',
        'Admin T97: /cashflow/transferencia abre transferir-entre-cuentas'
    );

    // T97: /cashflow (En index de cashflow el slug configurado es corregir-movimiento-caja-validada)
    await verificarBotonAyuda(
        '/cashflow',
        'corregir-movimiento-caja-validada',
        'signo-pregunta-sv-admin-cashflow.png',
        'Admin T97: /cashflow abre corregir-movimiento-caja-validada'
    );

    // T102: /niveles
    await verificarBotonAyuda(
        '/niveles',
        'administrar-niveles',
        'signo-pregunta-sv-admin-niveles.png',
        'Admin T102: /niveles abre administrar-niveles'
    );

    // T102: /grupos
    await verificarBotonAyuda(
        '/grupos',
        'administrar-grupos',
        'signo-pregunta-sv-admin-grupos.png',
        'Admin T102: /grupos abre administrar-grupos'
    );

    // ─────────────────────────────────────────────────────────────
    // 2. OPERATIVO (T96)
    // ─────────────────────────────────────────────────────────────
    console.log('Ingresando como OPERATIVO...');
    await login('operativo@wings.test');

    // T96: /caja/apertura (Apertura de turno)
    await verificarBotonAyuda(
        '/caja/apertura',
        'abrir-caja',
        'signo-pregunta-sv-operativo-abrir-caja.png',
        'Operativo T96: /caja/apertura abre abrir-caja'
    );

    // ─────────────────────────────────────────────────────────────
    // 3. PROFESOR
    // ─────────────────────────────────────────────────────────────
    console.log('Ingresando como PROFESOR...');
    await login('profesor@wings.test');

    // /clases
    await verificarBotonAyuda(
        '/clases',
        'consultar-mis-clases',
        'signo-pregunta-sv-profesor-clases.png',
        'Profesor: /clases abre consultar-mis-clases'
    );

} catch (err) {
    console.error('Error durante la ejecución del navegador:', err);
    registrar('Ejecución sin excepciones', false, err.message);
} finally {
    await pagina.close();
    await navegador.close();
    fs.writeFileSync(path.join(evidenciaDir, 'resultados-signo-pregunta.json'), JSON.stringify(resultados, null, 2));
    console.log('--- Comprobación finalizada. Resultados guardados en evidencia-t12-t13/ ---');
}
