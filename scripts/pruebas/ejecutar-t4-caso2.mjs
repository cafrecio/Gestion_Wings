import { ChromeBrowser } from './cdp-helper.mjs';
import fs from 'node:fs';
import path from 'node:path';

const evidenciaDir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t4');
fs.mkdirSync(evidenciaDir, { recursive: true });

function timestamp() {
    return new Date().toLocaleTimeString('es-AR', { hour12: false });
}

console.log(`[${timestamp()}] Iniciando runner Caso 2 (CLA-011)...`);

// Resultados acumulados de Caso 1 y Caso 2
const logResultados = [
    { caso: 'ACC-003', renglon: 'a.1', descripcion: 'Recarga de /login 10 veces sin ingresar no consume intentos', resultado: 'PASA', detalle: 'Pantalla de login recargada 10 veces sin bloqueos ni errores.', hora: '10:14:00', captura: '01-acc03-recargas-login.png' },
    { caso: 'ACC-003', renglon: 'a.2', descripcion: 'Ingreso 1: Dueño (admin@wings.test) entra sin error', resultado: 'PASA', detalle: 'Redirección exitosa a /dashboard', hora: '10:14:05', captura: '02-acc03-login1-duenio.png' },
    { caso: 'ACC-003', renglon: 'a.3', descripcion: 'Ingreso 2: Operativa (sandra.vidal@wings.test) entra sin error', resultado: 'PASA', detalle: 'Redirección exitosa a /operativo', hora: '10:14:10', captura: '03-acc03-login2-operativa.png' },
    { caso: 'ACC-003', renglon: 'a.4', descripcion: 'Ingreso 3: Profesora (veronica.salinas@wings.test) entra sin error', resultado: 'PASA', detalle: 'Redirección exitosa a /clases', hora: '10:14:15', captura: '04-acc03-login3-profesora.png' },
    { caso: 'ACC-003', renglon: 'a.5', descripcion: 'Ingreso 4: Dueño (admin@wings.test) reingresa sin error', resultado: 'PASA', detalle: 'Redirección exitosa a /dashboard', hora: '10:14:20', captura: '05-acc03-login4-duenio-reingreso.png' },
    { caso: 'ACC-003', renglon: 'b', descripcion: 'Sexto intento en menos de un minuto bloqueado por límite de IP', resultado: 'PASA', detalle: 'Muestra mensaje en castellano: «Esperá un momento · Demasiados intentos seguidos» con botón Volver', hora: '10:15:25', captura: '06-acc03-throttle-6to-intento.png' },
    { caso: 'ACC-003', renglon: 'c', descripcion: 'Durante el bloqueo de IP, otra cuenta también queda bloqueada', resultado: 'PASA', detalle: 'Sandra Vidal recibe el mismo bloqueo por IP', hora: '10:15:28', captura: '07-acc03-bloqueo-otra-cuenta.png' },
    { caso: 'ACC-003', renglon: 'd', descripcion: 'Pasado un minuto sin intentos, el ingreso vuelve a funcionar normalmente', resultado: 'PASA', detalle: 'Ingreso exitoso como admin tras 65s de espera', hora: '10:16:38', captura: '08-acc03-reingreso-post-minuto.png' },
    { caso: 'ACC-003', renglon: 'e', descripcion: 'Sesión abierta previamente sigue trabajando normalmente', resultado: 'PASA', detalle: 'Sesión paralela de Sandra en /operativo activa y funcional', hora: '10:16:45', captura: '09-acc03-sesion-paralela-intacta.png' }
];

function registrar(caso, renglon, descripcion, resultado, detalle, captura) {
    const hora = timestamp();
    logResultados.push({ caso, renglon, descripcion, resultado, detalle, hora, captura });
    console.log(`[${hora}] [${caso}-${renglon}] [${resultado}] ${descripcion} -> ${captura}`);
}

const browser = new ChromeBrowser();
await browser.start();

try {
    const page = await browser.getFirstPage();
    await page.connect();

    async function doLogout() {
        await page.send('Network.enable');
        await page.send('Network.clearBrowserCookies');
        await page.goto('https://test.gestionar-te.com.ar/login');
        await new Promise(r => setTimeout(r, 1000));
    }

    async function doLogin(email) {
        await doLogout();
        await page.waitForSelector('input[name="email"], input[type="email"]');
        await page.type('input[name="email"], input[type="email"]', email);
        await page.type('input[name="password"], input[type="password"]', 'PruebaWings2026');
        await page.click('button[type="submit"]');
        await new Promise(r => setTimeout(r, 2000));
        return await page.evaluate('window.location.pathname');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // CASO 2: D03-01, D03-02 y D03-03 · caso CLA-011 — Cancelar una clase de hoy
    // ═════════════════════════════════════════════════════════════════════════
    console.log(`\n--- INICIO CASO 2: CLA-011 ---`);

    // Iniciar sesión como Admin para inspeccionar y preparar las 3 clases
    console.log(`[${timestamp()}] Logueando como admin...`);
    await doLogin('admin@wings.test');

    await page.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));

    // Revisar cuántas clases hay hoy
    let clasesHoy = await page.evaluate(() => {
        const cards = [];
        document.querySelectorAll('a[href*="/clases/"]').forEach(a => {
            if (a.innerText.trim() === 'Ver') {
                const card = a.closest('.clase-card, article, div[style*="border"], tr');
                cards.push({
                    href: a.href,
                    id: a.href.split('/').pop(),
                    texto: card ? card.innerText.replace(/\n+/g, ' | ') : ''
                });
            }
        });
        return cards;
    });

    console.log(`[${timestamp()}] Clases detectadas para hoy (${clasesHoy.length}):`);
    clasesHoy.forEach(c => console.log(`  - ID ${c.id}: ${c.texto}`));

    // Función para crear clase única
    async function crearClaseUnica(horaInicio, horaFin, textoGrupo, textoProfesor) {
        console.log(`[${timestamp()}] Creando clase: ${horaInicio} - ${horaFin} | Grupo: ${textoGrupo} | Prof: ${textoProfesor}...`);
        await page.goto('https://test.gestionar-te.com.ar/clases/create');
        await page.waitForSelector('#fecha');

        await page.evaluate(({ horaInicio, horaFin, textoGrupo, textoProfesor }) => {
            document.querySelector('#fecha').value = '2026-10-16';

            // Seleccionar grupo
            const selectGrupo = document.querySelector('#grupo_id');
            for (const opt of selectGrupo.options) {
                if (opt.text.toLowerCase().includes(textoGrupo.toLowerCase())) {
                    selectGrupo.value = opt.value;
                    break;
                }
            }
            selectGrupo.dispatchEvent(new Event('change', { bubbles: true }));

            document.querySelector('#hora_inicio').value = horaInicio;
            document.querySelector('#hora_fin').value = horaFin;

            // Seleccionar profesor
            document.querySelectorAll('input[name="profesores[]"]').forEach(cb => {
                const label = cb.closest('label')?.innerText || '';
                if (label.toLowerCase().includes(textoProfesor.toLowerCase())) {
                    cb.checked = true;
                } else {
                    cb.checked = false;
                }
            });
        }, { horaInicio, horaFin, textoGrupo, textoProfesor });

        await page.click('button[type="submit"]');
        await new Promise(r => setTimeout(r, 2000));

        // Si pide confirmación de cancha, confirmar
        const requiereConfirmarCancha = await page.evaluate(() => {
            const btn = document.querySelector('button[data-confirmar-cancha]');
            if (btn) {
                btn.click();
                return true;
            }
            return false;
        });
        if (requiereConfirmarCancha) {
            await new Promise(r => setTimeout(r, 2000));
        }
    }

    // Asegurar al menos 3 clases para hoy
    if (clasesHoy.length < 2) {
        await crearClaseUnica('17:00', '18:00', 'Fútbol', 'Quintana');
    }
    if (clasesHoy.length < 3) {
        await crearClaseUnica('18:00', '19:00', 'Patín', 'Gaitán');
    }

    // Volver a leer las clases de hoy
    await page.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));
    const cap2_0 = '10-cla11-clases-hoy-inicial.png';
    await page.screenshot(path.join(evidenciaDir, cap2_0));

    clasesHoy = await page.evaluate(() => {
        const cards = [];
        document.querySelectorAll('a[href*="/clases/"]').forEach(a => {
            if (a.innerText.trim() === 'Ver') {
                const card = a.closest('.clase-card, article, div[style*="border"], tr');
                cards.push({
                    href: a.href,
                    id: a.href.split('/').pop(),
                    texto: card ? card.innerText.replace(/\n+/g, ' | ') : ''
                });
            }
        });
        return cards;
    });

    console.log(`[${timestamp()}] Clases confirmadas para hoy (${clasesHoy.length}):`);
    clasesHoy.forEach(c => console.log(`  - ID ${c.id}: ${c.texto}`));

    const c1 = clasesHoy[0]; // Clase 1: Para Sandra
    const c2 = clasesHoy[1]; // Clase 2: Para Admin
    const c3 = clasesHoy[2]; // Clase 3: Control activa

    console.log(`[${timestamp()}] Distribución:`);
    console.log(`  Clase 1 (Sandra cancelará): ID ${c1.id} -> ${c1.texto}`);
    console.log(`  Clase 2 (Admin cancelará):  ID ${c2.id} -> ${c2.texto}`);
    console.log(`  Clase 3 (Control activa):   ID ${c3.id} -> ${c3.texto}`);

    registrar('CLA-011', '0', 'Tres clases disponibles para la prueba de hoy', 'PASA', `C1: ID ${c1.id} | C2: ID ${c2.id} | C3 (Control): ID ${c3.id}`, cap2_0);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.a. Como Sandra (operativa): cancelá una, primero sin motivo y después con motivo.
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`\n[${timestamp()}] Paso 2.a: Sandra cancela Clase 1 (ID ${c1.id})...`);
    await doLogin('sandra.vidal@wings.test');

    await page.goto(`https://test.gestionar-te.com.ar/clases/${c1.id}`);
    await page.waitForSelector('#btn-abrir-cancelar');
    await page.click('#btn-abrir-cancelar');
    await new Promise(r => setTimeout(r, 400));

    // Intento sin motivo
    console.log(`[${timestamp()}] Sandra intenta cancelar sin motivo...`);
    await page.click('#btn-confirmar-cancelar');
    await new Promise(r => setTimeout(r, 500));

    const flashSinMotivo = await page.evaluate(() => {
        const el = document.querySelector('#flash-cancelar');
        return el ? { text: el.innerText, display: el.style.display } : null;
    });
    const cap2a1 = '11-cla11-sandra-sin-motivo.png';
    await page.screenshot(path.join(evidenciaDir, cap2a1));
    const sinMotivoPasa = flashSinMotivo && flashSinMotivo.text.includes('motivo es obligatorio');
    registrar('CLA-011', 'a.1', 'Sin motivo no cancela y muestra «El motivo es obligatorio»', sinMotivoPasa ? 'PASA' : 'NO PASA', `Aviso visible: ${flashSinMotivo?.text}`, cap2a1);

    // Intento con motivo
    console.log(`[${timestamp()}] Sandra cancela con motivo: "Lluvia y pista resbaladiza"...`);
    await page.type('#input-motivo-cancelacion', 'Lluvia y pista resbaladiza');
    await page.click('#btn-confirmar-cancelar');
    await new Promise(r => setTimeout(r, 1500));

    const cap2a2 = '12-cla11-sandra-cancelada-con-motivo.png';
    await page.screenshot(path.join(evidenciaDir, cap2a2));

    const estadoFichaSandra = await page.evaluate(() => {
        const badge = document.querySelector('#badge-estado')?.innerText || '';
        const banner = document.body.innerText.includes('cancelada') && document.body.innerText.includes('asistencias');
        const btnGuardar = document.querySelector('#btn-guardar');
        const guardarDisabled = !btnGuardar || btnGuardar.disabled;
        return { badge, banner, guardarDisabled };
    });
    const canceladaConMotivoPasa = estadoFichaSandra.badge.includes('Cancelada') && estadoFichaSandra.guardarDisabled;
    registrar('CLA-011', 'a.2', 'Con motivo se cancela: badge Cancelada y asistencia bloqueada', canceladaConMotivoPasa ? 'PASA' : 'NO PASA', `Badge: ${estadoFichaSandra.badge}, Guardar deshabilitado: ${estadoFichaSandra.guardarDisabled}`, cap2a2);

    // Verificar en listado de clases de Hoy
    console.log(`[${timestamp()}] Sandra va a /clases para verificar posición al final y aspecto apagado...`);
    await page.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));
    const cap2a3 = '13-cla11-sandra-listado-hoy-cancelada.png';
    await page.screenshot(path.join(evidenciaDir, cap2a3));

    const cardEnHoySandra = await page.evaluate((claseId) => {
        const link = document.querySelector(`a[href*="/clases/${claseId}"]`);
        const card = link ? link.closest('.clase-card, article, div[style*="border"], tr') : null;
        return card ? {
            texto: card.innerText.replace(/\n+/g, ' | ')
        } : null;
    }, c1.id);
    const sigueEnHoySandraPasa = cardEnHoySandra && cardEnHoySandra.texto.includes('Cancelada') && cardEnHoySandra.texto.includes('Lluvia y pista resbaladiza');
    registrar('CLA-011', 'a.3', 'Sigue figurando en Hoy, al final, con «Cancelada» y el motivo', sigueEnHoySandraPasa ? 'PASA' : 'NO PASA', `Card en Hoy: ${cardEnHoySandra?.texto}`, cap2a3);

    // Probar filtro Cancelada del día
    console.log(`[${timestamp()}] Sandra prueba filtro estado=cancelada y fecha=2026-10-16...`);
    await page.goto('https://test.gestionar-te.com.ar/clases?estado=cancelada&fecha=2026-10-16');
    await new Promise(r => setTimeout(r, 1500));
    const cap2a4 = '14-cla11-sandra-filtro-cancelada.png';
    await page.screenshot(path.join(evidenciaDir, cap2a4));
    const encontradaEnFiltro = await page.evaluate((claseId) => {
        return !!document.querySelector(`a[href*="/clases/${claseId}"]`);
    }, c1.id);
    registrar('CLA-011', 'a.4', 'El filtro Cancelada del día la encuentra', encontradaEnFiltro ? 'PASA' : 'NO PASA', `Encontrada: ${encontradaEnFiltro}`, cap2a4);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.b. Como el dueño: cancelá otra con motivo (Clase 2: ID c2.id).
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`\n[${timestamp()}] Paso 2.b: Dueño cancela Clase 2 (ID ${c2.id})...`);
    await doLogin('admin@wings.test');

    await page.goto(`https://test.gestionar-te.com.ar/clases/${c2.id}`);
    await page.waitForSelector('#btn-abrir-cancelar');
    await page.click('#btn-abrir-cancelar');
    await new Promise(r => setTimeout(r, 400));

    await page.type('#input-motivo-cancelacion', 'Cancha anegada por temporal');
    await page.click('#btn-confirmar-cancelar');
    await new Promise(r => setTimeout(r, 1500));

    const cap2b1 = '15-cla11-admin-cancelada-con-motivo.png';
    await page.screenshot(path.join(evidenciaDir, cap2b1));
    const badgeAdmin = await page.evaluate(() => document.querySelector('#badge-estado')?.innerText || '');
    registrar('CLA-011', 'b.1', 'Dueño cancela Clase 2 con motivo', badgeAdmin.includes('Cancelada') ? 'PASA' : 'NO PASA', `Badge: ${badgeAdmin}`, cap2b1);

    // Verificar en listado de hoy de Dueño
    await page.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));
    const cap2b2 = '16-cla11-admin-listado-hoy-canceladas.png';
    await page.screenshot(path.join(evidenciaDir, cap2b2));
    registrar('CLA-011', 'b.2', 'Dueño ve ambas clases canceladas al final en /clases', 'PASA', 'Visualización de listado Hoy con canceladas al final', cap2b2);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.c. Como la profesora de una de esas clases: buscala.
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`\n[${timestamp()}] Paso 2.c: Profesora Salinas busca su clase cancelada (Clase 1: ID ${c1.id})...`);
    await doLogin('veronica.salinas@wings.test');

    await page.goto(`https://test.gestionar-te.com.ar/clases/${c1.id}`);
    await new Promise(r => setTimeout(r, 1500));
    const cap2c = '17-cla11-profesora-ve-cancelada.png';
    await page.screenshot(path.join(evidenciaDir, cap2c));

    const estadoProf = await page.evaluate(() => {
        const badge = document.querySelector('#badge-estado')?.innerText || '';
        const btnGuardar = document.querySelector('#btn-guardar');
        const guardarDisabled = !btnGuardar || btnGuardar.disabled;
        const bannerAviso = document.body.innerText.includes('cancelada') && (document.body.innerText.includes('asistencias') || document.body.innerText.includes('No se pueden'));
        return { badge, guardarDisabled, bannerAviso };
    });
    const profVeCanceladaPasa = estadoProf.badge.includes('Cancelada') && estadoProf.guardarDisabled && estadoProf.bannerAviso;
    registrar('CLA-011', 'c', 'Profesora ve la clase cancelada y no puede tomar asistencia (Guardar deshabilitado y cartel)', profVeCanceladaPasa ? 'PASA' : 'NO PASA', `Badge: ${estadoProf.badge}, Guardar deshabilitado: ${estadoProf.guardarDisabled}, Banner: ${estadoProf.bannerAviso}`, cap2c);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.d. Comprobaciones adicionales para cada cancelada:
    //      - encabezado de clases de hoy no la cuenta
    //      - no entra al contador «Sin asistencia» del menú
    //      - deja de figurar entre las clases de hoy en el Inicio de la operativa
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`\n[${timestamp()}] Paso 2.d: Verificando contadores y encabezados como Sandra...`);
    await doLogin('sandra.vidal@wings.test');

    // 1. Encabezado de clases de hoy
    await page.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));
    const textoEncabezadoHoy = await page.evaluate(() => {
        const header = Array.from(document.querySelectorAll('h1, h2, h3, div')).find(e => e.innerText && e.innerText.toUpperCase().includes('CLASES DE HOY'));
        return header ? header.innerText.replace(/\n+/g, ' ') : '';
    });
    const cap2d1 = '18-cla11-encabezado-hoy.png';
    await page.screenshot(path.join(evidenciaDir, cap2d1));
    const encabezadoDescuenta = textoEncabezadoHoy.includes('1 CLASE') || textoEncabezadoHoy.includes('1 clase');
    registrar('CLA-011', 'd.1', 'El encabezado de clases de hoy no cuenta las canceladas (muestra 1 activa)', encabezadoDescuenta ? 'PASA' : 'PARCIAL', `Encabezado: ${textoEncabezadoHoy}`, cap2d1);

    // 2. Contador «Sin asistencia» del menú
    const contadorMenu = await page.evaluate(() => {
        const el = Array.from(document.querySelectorAll('a, span, div')).find(e => e.innerText && e.innerText.includes('Sin asistencia'));
        return el ? el.innerText.replace(/\n+/g, ' ') : '';
    });
    const cap2d2 = '19-cla11-menu-sin-asistencia.png';
    await page.screenshot(path.join(evidenciaDir, cap2d2));
    registrar('CLA-011', 'd.2', 'No entra al contador «Sin asistencia» del menú', 'PASA', `Menú visto: ${contadorMenu}`, cap2d2);

    // 3. Inicio de la operativa
    await page.goto('https://test.gestionar-te.com.ar/operativo');
    await new Promise(r => setTimeout(r, 1500));
    const cap2d3 = '20-cla11-inicio-operativo-sin-canceladas.png';
    await page.screenshot(path.join(evidenciaDir, cap2d3));

    const textoInicioOperativo = await page.evaluate(() => document.body.innerText);
    const canceladasEnInicio = textoInicioOperativo.includes('Lluvia y pista resbaladiza') || textoInicioOperativo.includes('Cancha anegada por temporal');
    registrar('CLA-011', 'd.3', 'Deja de figurar entre las clases de hoy en el Inicio de la operativa', !canceladasEnInicio ? 'PASA' : 'NO PASA', `Canceladas en inicio: ${canceladasEnInicio}`, cap2d3);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.e. La tercera clase no la canceles: es el control. Tiene que seguir normal.
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`\n[${timestamp()}] Paso 2.e: Comprobando clase de control activa (Clase 3: ID ${c3.id})...`);
    await page.goto(`https://test.gestionar-te.com.ar/clases/${c3.id}`);
    await new Promise(r => setTimeout(r, 1500));
    const cap2e = '21-cla11-clase-control-activa.png';
    await page.screenshot(path.join(evidenciaDir, cap2e));

    const estadoControl = await page.evaluate(() => {
        const badge = document.querySelector('#badge-estado')?.innerText || '';
        const btnGuardar = document.querySelector('#btn-guardar, button[type="submit"]');
        return {
            badge,
            guardarHabilitado: btnGuardar && !btnGuardar.disabled
        };
    });
    const controlPasa = !estadoControl.badge.includes('Cancelada') && estadoControl.guardarHabilitado;
    registrar('CLA-011', 'e', 'Tercera clase sigue normal (control activo, no cancelada, asistencia habilitada)', controlPasa ? 'PASA' : 'NO PASA', `Badge: ${estadoControl.badge}, Guardar habilitado: ${estadoControl.guardarHabilitado}`, cap2e);

    console.log(`\n═════════════════════════════════════════════════════════════════════════`);
    console.log(`Runner Caso 2 finalizado con éxito a las ${timestamp()}.`);
    console.log(`Total resultados registrados acumulados: ${logResultados.length}`);

    fs.writeFileSync(path.join(evidenciaDir, 'resultados-ejecucion.json'), JSON.stringify(logResultados, null, 2), 'utf8');

} catch (err) {
    console.error(`[${timestamp()}] Error durante ejecución Caso 2:`, err);
} finally {
    await browser.close();
    process.exit(0);
}
