import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createBrowserTarget } from '../verificacion-a14-a27-a53/cdp.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const evidenciaDir = __dirname;
const chromeProfile = path.join(__dirname, 'chrome-profile');
const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';

if (!fs.existsSync(chromeProfile)) {
    fs.mkdirSync(chromeProfile, { recursive: true });
}

console.log('Iniciando Chrome Headless...');
const chrome = spawn(chromePath, [
    '--headless=new',
    '--remote-debugging-port=9222',
    `--user-data-dir=${chromeProfile}`,
    '--no-sandbox',
    '--disable-gpu',
    '--window-size=1280,800',
    '--hide-scrollbars'
]);

await new Promise(r => setTimeout(r, 2000));

const resultadosNavegador = {};

try {
    const page = await createBrowserTarget(9222);

    // 1. Login como Sandra
    console.log('1. Navegando a login...');
    await page.navigate('http://127.0.0.1:8099/login');
    await page.evaluate(`
        (() => {
            document.getElementById('email').value = 'sandra@wings.com';
            document.getElementById('password').value = 'password';
            document.querySelector('button[type="submit"]').click();
        })()
    `);
    await new Promise(r => setTimeout(r, 2000));

    // =========================================================================
    // A26: Asterisco dinámico en /alumnos/create
    // =========================================================================
    console.log('2. Navegando a /alumnos/create (A26)...');
    await page.navigate('http://127.0.0.1:8099/alumnos/create');
    await new Promise(r => setTimeout(r, 800));

    // Estado inicial: sin fecha o mayor -> asterisco debe ser visible (hidden === false)
    const estadoInicial = await page.evaluate(`
        (() => {
            const marca = document.getElementById('celular-obligatorio');
            const fechaInput = document.getElementById('fecha_nacimiento');
            return {
                marcaExiste: !!marca,
                marcaHidden: marca ? marca.hidden : null,
                marcaOffsetParent: marca ? !!marca.offsetParent : false,
                fechaVal: fechaInput ? fechaInput.value : null
            };
        })()
    `);
    resultadosNavegador['a26_inicial'] = estadoInicial;
    console.log('A26 Estado inicial:', estadoInicial);

    // Cambiar a MENOR (10 años: ej 2016-05-10) y disparar eventos
    console.log('A26: Cambiando fecha a menor (2016-05-10)...');
    const estadoMenor = await page.evaluate(`
        (() => {
            const fechaInput = document.getElementById('fecha_nacimiento');
            fechaInput.value = '2016-05-10';
            fechaInput.dispatchEvent(new Event('input', { bubbles: true }));
            fechaInput.dispatchEvent(new Event('change', { bubbles: true }));
            const marca = document.getElementById('celular-obligatorio');
            return {
                fechaVal: fechaInput.value,
                marcaHidden: marca ? marca.hidden : null,
                marcaDisplay: marca ? window.getComputedStyle(marca).display : null
            };
        })()
    `);
    resultadosNavegador['a26_menor'] = estadoMenor;
    console.log('A26 Estado menor:', estadoMenor);
    await page.captureScreenshot(path.join(evidenciaDir, 'a26-asterisco-menor.png'));

    // Cambiar a MAYOR (25 años: ej 2001-05-10) y disparar eventos
    console.log('A26: Cambiando fecha a mayor (2001-05-10)...');
    const estadoMayor = await page.evaluate(`
        (() => {
            const fechaInput = document.getElementById('fecha_nacimiento');
            fechaInput.value = '2001-05-10';
            fechaInput.dispatchEvent(new Event('input', { bubbles: true }));
            fechaInput.dispatchEvent(new Event('change', { bubbles: true }));
            const marca = document.getElementById('celular-obligatorio');
            return {
                fechaVal: fechaInput.value,
                marcaHidden: marca ? marca.hidden : null,
                marcaDisplay: marca ? window.getComputedStyle(marca).display : null
            };
        })()
    `);
    resultadosNavegador['a26_mayor'] = estadoMayor;
    console.log('A26 Estado mayor:', estadoMayor);
    await page.captureScreenshot(path.join(evidenciaDir, 'a26-asterisco-mayor.png'));

    // Probar tilde "Mismo que el teléfono del tutor"
    console.log('A26: Probando tilde "Mismo que el teléfono del tutor"...');
    const pruebaTildeTutor = await page.evaluate(`
        (() => {
            const telTutor = document.getElementById('telefono_tutor');
            const cel = document.getElementById('celular');
            const check = document.getElementById('celular-mismo-tutor');
            if (!telTutor || !cel || !check) return { error: 'Campos no encontrados' };
            telTutor.value = '11-8888-9999';
            telTutor.dispatchEvent(new Event('input', { bubbles: true }));
            check.checked = true;
            check.dispatchEvent(new Event('change', { bubbles: true }));
            return {
                telTutor: telTutor.value,
                celularSincronizado: cel.value,
                coinciden: cel.value === '11-8888-9999'
            };
        })()
    `);
    resultadosNavegador['a26_tilde_tutor'] = pruebaTildeTutor;
    console.log('A26 Tilde tutor:', pruebaTildeTutor);

    // =========================================================================
    // A39: Vista de Movimientos para Sandra
    // =========================================================================
    console.log('3. Navegando a /movimientos como Sandra (A39)...');
    await page.navigate('http://127.0.0.1:8099/movimientos');
    await new Promise(r => setTimeout(r, 800));

    // Captura general de la pantalla de Movimientos vista por Sandra
    await page.captureScreenshot(path.join(evidenciaDir, 'a39-sandra-movimientos.png'));

    // Inspeccionar filas visibles, montos, totales y opciones de filtros
    const inspeccionA39 = await page.evaluate(`
        (() => {
            // Filas de tabla
            const filas = Array.from(document.querySelectorAll('table tbody tr')).map(tr => {
                return Array.from(tr.querySelectorAll('td')).map(td => td.innerText.trim());
            });

            // Resumen de totales
            const statsInfo = document.querySelector('.stats-info')?.innerText.trim() || null;
            const tarjetasTotales = Array.from(document.querySelectorAll('.ds-kpi, .kpi-card, [class*="total"], [class*="kpi"]')).map(el => el.innerText.trim());

            // Desplegable de Rubros
            const rubroSelect = document.querySelector('select[name="rubro_id"]');
            const opcionesRubros = rubroSelect ? Array.from(rubroSelect.options).map(o => o.text.trim()) : [];

            // Desplegable de Subrubros
            const subrubroSelect = document.querySelector('select[name="subrubro_id"]');
            const opcionesSubrubros = subrubroSelect ? Array.from(subrubroSelect.options).map(o => o.text.trim()) : [];

            // Enlace en el menú
            const linkMovimientosMenu = Array.from(document.querySelectorAll('nav a, aside a, .ds-sidebar a')).find(a => a.href.includes('/movimientos'));

            return {
                statsInfo,
                filas,
                tarjetasTotales,
                opcionesRubros,
                opcionesSubrubros,
                menuMovimientosTexto: linkMovimientosMenu ? linkMovimientosMenu.innerText.trim() : null
            };
        })()
    `);
    resultadosNavegador['a39_sandra'] = inspeccionA39;
    console.log('A39 Opciones Rubros:', inspeccionA39.opcionesRubros);
    console.log('A39 Opciones Subrubros:', inspeccionA39.opcionesSubrubros);
    console.log('A39 Filas visibles:', inspeccionA39.filas.length);

    fs.writeFileSync(
        path.join(evidenciaDir, 'resultados-navegador.json'),
        JSON.stringify(resultadosNavegador, null, 2)
    );

    console.log('Verificación interactiva completada con éxito.');
} finally {
    chrome.kill();
    console.log('Chrome finalizado.');
}
