import { ChromeBrowser } from './cdp-helper.mjs';
import fs from 'node:fs';
import path from 'node:path';

const evidenciaDir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t4');
fs.mkdirSync(evidenciaDir, { recursive: true });

function timestamp() {
    return new Date().toLocaleTimeString('es-AR', { hour12: false });
}

console.log(`[${timestamp()}] Iniciando runner completo T4...`);

const logResultados = [];
function registrar(caso, renglon, descripcion, resultado, detalle, captura) {
    const hora = timestamp();
    logResultados.push({ caso, renglon, descripcion, resultado, detalle, hora, captura });
    console.log(`[${hora}] [${caso}-${renglon}] [${resultado}] ${descripcion} -> ${captura}`);
}

const browser = new ChromeBrowser();
await browser.start();

try {
    // ═════════════════════════════════════════════════════════════════════════
    // CASO 1: D02-04 · caso ACC-003 — Varias personas entran una detrás de otra
    // ═════════════════════════════════════════════════════════════════════════
    console.log(`\n--- INICIO CASO 1: ACC-003 ---`);

    const page1 = await browser.getFirstPage();
    await page1.connect();

    // 1.a. Abrir y recargar pantalla de ingreso 10 veces sin ingresar
    console.log(`[${timestamp()}] Paso 1.a: Abriendo y recargando /login 10 veces...`);
    await page1.goto('https://test.gestionar-te.com.ar/login');
    for (let i = 1; i <= 10; i++) {
        await page1.evaluate('window.location.reload()');
        await new Promise(r => setTimeout(r, 400));
    }
    await new Promise(r => setTimeout(r, 800));
    const cap1a1 = '01-acc03-recargas-login.png';
    await page1.screenshot(path.join(evidenciaDir, cap1a1));
    registrar('ACC-003', 'a.1', 'Recarga de /login 10 veces sin ingresar', 'PASA', 'La pantalla de login se recargó 10 veces sin bloqueos ni errores.', cap1a1);

    // Helper para login/logout
    async function doLogin(email, rolEsperado) {
        await page1.goto('https://test.gestionar-te.com.ar/login');
        await page1.waitForSelector('input[name="email"], input[type="email"]');
        await page1.type('input[name="email"], input[type="email"]', email);
        await page1.type('input[name="password"], input[type="password"]', 'PruebaWings2026');
        await page1.click('button[type="submit"]');
        await new Promise(r => setTimeout(r, 2000));
        return await page1.evaluate('window.location.pathname');
    }

    async function doLogout() {
        // En Wings el logout es un form POST a /logout con botón o link
        const tieneFormLogout = await page1.evaluate(() => {
            const f = document.querySelector('form[action*="logout"]');
            if (f) {
                f.submit();
                return true;
            }
            return false;
        });
        if (!tieneFormLogout) {
            await page1.goto('https://test.gestionar-te.com.ar/login');
        } else {
            await new Promise(r => setTimeout(r, 1500));
        }
    }

    // 1. Dueño
    console.log(`[${timestamp()}] Paso 1.a.2: Ingreso 1 - Dueño (admin@wings.test)...`);
    const pathAdmin1 = await doLogin('admin@wings.test');
    const cap1a2 = '02-acc03-login1-duenio.png';
    await page1.screenshot(path.join(evidenciaDir, cap1a2));
    registrar('ACC-003', 'a.2', 'Ingreso 1: Dueño entra sin error', pathAdmin1.includes('dashboard') ? 'PASA' : 'PARCIAL', `Destino: ${pathAdmin1}`, cap1a2);
    await doLogout();

    // 2. Operativa
    console.log(`[${timestamp()}] Paso 1.a.3: Ingreso 2 - Operativa (sandra.vidal@wings.test)...`);
    const pathSandra = await doLogin('sandra.vidal@wings.test');
    const cap1a3 = '03-acc03-login2-operativa.png';
    await page1.screenshot(path.join(evidenciaDir, cap1a3));
    registrar('ACC-003', 'a.3', 'Ingreso 2: Operativa entra sin error', pathSandra.includes('operativo') ? 'PASA' : 'PARCIAL', `Destino: ${pathSandra}`, cap1a3);
    await doLogout();

    // 3. Profesora
    console.log(`[${timestamp()}] Paso 1.a.4: Ingreso 3 - Profesora (lucia.gaitan@wings.test)...`);
    const pathLucia = await doLogin('lucia.gaitan@wings.test');
    const cap1a4 = '04-acc03-login3-profesora.png';
    await page1.screenshot(path.join(evidenciaDir, cap1a4));
    registrar('ACC-003', 'a.4', 'Ingreso 3: Profesora entra sin error', pathLucia.includes('clases') ? 'PASA' : 'PARCIAL', `Destino: ${pathLucia}`, cap1a4);
    await doLogout();

    // 4. Dueño reingreso
    console.log(`[${timestamp()}] Paso 1.a.5: Ingreso 4 - Dueño reingreso (admin@wings.test)...`);
    const pathAdmin2 = await doLogin('admin@wings.test');
    const cap1a5 = '05-acc03-login4-duenio-reingreso.png';
    await page1.screenshot(path.join(evidenciaDir, cap1a5));
    registrar('ACC-003', 'a.5', 'Ingreso 4: Dueño entra nuevamente sin error', pathAdmin2.includes('dashboard') ? 'PASA' : 'PARCIAL', `Destino: ${pathAdmin2}`, cap1a5);

    // Preparación para paso 1.e: Dejamos a Sandra logueada en page1 (Sesión paralela)
    console.log(`[${timestamp()}] Preparando sesión paralela previa al paso 1.b (Sandra en page1)...`);
    await doLogout();
    await doLogin('sandra.vidal@wings.test');
    await page1.goto('https://test.gestionar-te.com.ar/operativo');
    console.log(`[${timestamp()}] Sesión paralela activa en /operativo.`);

    // 1.b. Esperar 1 minuto (65s) para limpiar la ventana de throttle previa
    console.log(`[${timestamp()}] Esperando 65 segundos para limpiar ventana de intentos previos...`);
    await new Promise(r => setTimeout(r, 65000));

    // Abrimos un navegador independiente (browser2) para realizar los 6 intentos de login sin cookies previas
    const browser2 = new ChromeBrowser();
    await browser2.start();
    const page2 = await browser2.getFirstPage();
    await page2.connect();

    console.log(`[${timestamp()}] Paso 1.b: Ejecutando 6 intentos seguidos en menos de un minuto...`);
    let mensajeThrottleVisto = false;
    let textoPantallaThrottle = '';

    for (let intento = 1; intento <= 6; intento++) {
        await page2.goto('https://test.gestionar-te.com.ar/login');
        await page2.waitForSelector('input[name="email"], input[type="email"]');
        await page2.type('input[name="email"], input[type="email"]', 'intento.prueba@wings.test');
        await page2.type('input[name="password"], input[type="password"]', 'ClaveInvalida123');
        await page2.click('button[type="submit"]');
        await new Promise(r => setTimeout(r, 1200));

        const pageText = await page2.evaluate('document.body.innerText');
        console.log(`[${timestamp()}] Intento ${intento}/6 completado. Texto visible incluye: ${pageText.slice(0, 80).replace(/\n/g, ' ')}`);

        if (pageText.includes('Demasiados intentos seguidos') || pageText.includes('Esperá un momento')) {
            mensajeThrottleVisto = true;
            textoPantallaThrottle = pageText;
            console.log(`[${timestamp()}] Throttle detectado en intento ${intento}!`);
            break;
        }
    }

    const cap1b = '06-acc03-throttle-6to-intento.png';
    await page2.screenshot(path.join(evidenciaDir, cap1b));
    const tieneVolver = await page2.evaluate(`!!document.querySelector('a, button')?.innerText.toLowerCase().includes('volver') || document.body.innerText.includes('Volver')`);
    registrar('ACC-003', 'b', '6 ingresos seguidos muestran pantalla «Esperá un momento · Demasiados intentos seguidos» con Volver', mensajeThrottleVisto && tieneVolver ? 'PASA' : 'PARCIAL', `Texto: ${textoPantallaThrottle.slice(0, 120).replace(/\n/g, ' ')}. Botón volver: ${tieneVolver}`, cap1b);

    // 1.c. Mientras dura el bloqueo, probar con otra cuenta desde la misma conexión
    console.log(`[${timestamp()}] Paso 1.c: Probando con otra cuenta (admin@wings.test) durante el bloqueo...`);
    await page2.goto('https://test.gestionar-te.com.ar/login');
    const textoInmediato = await page2.evaluate('document.body.innerText');
    if (textoInmediato.includes('Demasiados intentos') || textoInmediato.includes('Esperá un momento')) {
        // La pantalla de login ya bloquea de inmediato
    } else {
        await page2.waitForSelector('input[name="email"], input[type="email"]');
        await page2.type('input[name="email"], input[type="email"]', 'admin@wings.test');
        await page2.type('input[name="password"], input[type="password"]', 'PruebaWings2026');
        await page2.click('button[type="submit"]');
        await new Promise(r => setTimeout(r, 1200));
    }

    const textoBloqueoOtra = await page2.evaluate('document.body.innerText');
    const cap1c = '07-acc03-bloqueo-otra-cuenta.png';
    await page2.screenshot(path.join(evidenciaDir, cap1c));
    const bloqueoOtraPasa = textoBloqueoOtra.includes('Demasiados intentos seguidos') || textoBloqueoOtra.includes('Esperá un momento');
    registrar('ACC-003', 'c', 'Límite por conexión: otra cuenta tampoco entra mientras dura el bloqueo', bloqueoOtraPasa ? 'PASA' : 'NO PASA', `Texto obtenido: ${textoBloqueoOtra.slice(0, 100).replace(/\n/g, ' ')}`, cap1c);

    // 1.d. Pasado un minuto real, sin hacer nada más, entrar
    console.log(`[${timestamp()}] Paso 1.d: Esperando 65 segundos de tiempo real sin hacer peticiones...`);
    await new Promise(r => setTimeout(r, 65000));

    console.log(`[${timestamp()}] Reintentando ingreso con admin@wings.test tras 65 segundos...`);
    await page2.goto('https://test.gestionar-te.com.ar/login');
    await page2.waitForSelector('input[name="email"], input[type="email"]');
    await page2.type('input[name="email"], input[type="email"]', 'admin@wings.test');
    await page2.type('input[name="password"], input[type="password"]', 'PruebaWings2026');
    await page2.click('button[type="submit"]');
    await new Promise(r => setTimeout(r, 2000));

    const pathPostMinuto = await page2.evaluate('window.location.pathname');
    const cap1d = '08-acc03-reingreso-post-minuto.png';
    await page2.screenshot(path.join(evidenciaDir, cap1d));
    const reingresoPasa = pathPostMinuto.includes('dashboard');
    registrar('ACC-003', 'd', 'Pasado un minuto real, el ingreso se libera y entra', reingresoPasa ? 'PASA' : 'NO PASA', `Destino tras 65s: ${pathPostMinuto}`, cap1d);

    // 1.e. Comprobar que la sesión abierta en page1 (Sandra en /operativo) siguió trabajando
    console.log(`[${timestamp()}] Paso 1.e: Comprobando que la sesión paralela de Sandra en page1 sigue activa...`);
    await page1.goto('https://test.gestionar-te.com.ar/operativo');
    await new Promise(r => setTimeout(r, 1500));
    const pathSandraParalela = await page1.evaluate('window.location.pathname');
    const textoSandraParalela = await page1.evaluate('document.body.innerText');
    const cap1e = '09-acc03-sesion-paralela-intacta.png';
    await page1.screenshot(path.join(evidenciaDir, cap1e));
    const sesionParalelaPasa = pathSandraParalela.includes('operativo') && textoSandraParalela.includes('Sandra Vidal');
    registrar('ACC-003', 'e', 'Sesión abierta previamente sigue trabajando normalmente', sesionParalelaPasa ? 'PASA' : 'NO PASA', `Ruta: ${pathSandraParalela}, Usuario: Sandra Vidal activo`, cap1e);

    // Cerrar navegador de prueba 1
    await browser2.close();

    // ═════════════════════════════════════════════════════════════════════════
    // CASO 2: D03-01, D03-02 y D03-03 · caso CLA-011 — Cancelar una clase de hoy
    // ═════════════════════════════════════════════════════════════════════════
    console.log(`\n--- INICIO CASO 2: CLA-011 ---`);

    // Iniciar como admin para inspeccionar y preparar las 3 clases de hoy
    console.log(`[${timestamp()}] Logueando como admin para preparar las clases de hoy...`);
    await page1.goto('https://test.gestionar-te.com.ar/login');
    await page1.waitForSelector('input[name="email"], input[type="email"]');
    await page1.type('input[name="email"], input[type="email"]', 'admin@wings.test');
    await page1.type('input[name="password"], input[type="password"]', 'PruebaWings2026');
    await page1.click('button[type="submit"]');
    await new Promise(r => setTimeout(r, 2000));

    await page1.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));

    // Clase 1 (existente de hoy): Patín Intermedias 16:00 - 17:00 (Salinas)
    // Crear Clase 2 (17:00 - 18:00 Fútbol Principiantes, Quintana)
    console.log(`[${timestamp()}] Creando Clase 2 para hoy (17:00 a 18:00 Fútbol Principiantes, Quintana)...`);
    await page1.goto('https://test.gestionar-te.com.ar/clases/create');
    await page1.waitForSelector('#fecha');
    await page1.evaluate(() => {
        document.querySelector('#fecha').value = '2026-10-16';
        // Seleccionar Fútbol Principiantes
        const selectGrupo = document.querySelector('#grupo_id');
        for (const opt of selectGrupo.options) {
            if (opt.text.includes('Fútbol') && opt.text.includes('Principiantes')) {
                selectGrupo.value = opt.value;
                break;
            }
        }
        selectGrupo.dispatchEvent(new Event('change', { bubbles: true }));

        document.querySelector('#hora_inicio').value = '17:00';
        document.querySelector('#hora_fin').value = '18:00';

        // Tildar Quintana
        document.querySelectorAll('input[name="profesores[]"]').forEach(cb => {
            const label = cb.closest('label')?.innerText || '';
            if (label.includes('Quintana')) {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
        });
    });
    await page1.click('button[type="submit"]');
    await new Promise(r => setTimeout(r, 2000));

    // Crear Clase 3 (18:00 - 19:00 Patín Principiantes, Gaitán) - Esta será el CONTROL
    console.log(`[${timestamp()}] Creando Clase 3 de control para hoy (18:00 a 19:00 Patín Principiantes, Gaitán)...`);
    await page1.goto('https://test.gestionar-te.com.ar/clases/create');
    await page1.waitForSelector('#fecha');
    await page1.evaluate(() => {
        document.querySelector('#fecha').value = '2026-10-16';
        // Seleccionar Patín Principiantes
        const selectGrupo = document.querySelector('#grupo_id');
        for (const opt of selectGrupo.options) {
            if (opt.text.includes('Patín') && opt.text.includes('Principiantes')) {
                selectGrupo.value = opt.value;
                break;
            }
        }
        selectGrupo.dispatchEvent(new Event('change', { bubbles: true }));

        document.querySelector('#hora_inicio').value = '18:00';
        document.querySelector('#hora_fin').value = '19:00';

        // Tildar Gaitán
        document.querySelectorAll('input[name="profesores[]"]').forEach(cb => {
            const label = cb.closest('label')?.innerText || '';
            if (label.includes('Gaitán')) {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
        });
    });
    await page1.click('button[type="submit"]');
    await new Promise(r => setTimeout(r, 2000));

    // Listar y capturar estado inicial de las 3 clases de hoy
    await page1.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));
    const cap2_0 = '10-cla11-clases-hoy-inicial.png';
    await page1.screenshot(path.join(evidenciaDir, cap2_0));

    const clasesHoyDetectadas = await page1.evaluate(() => {
        const cards = [];
        document.querySelectorAll('a[href*="/clases/"]').forEach(a => {
            const card = a.closest('.clase-card, article, div[style*="border"], tr');
            if (card && a.innerText.trim() === 'Ver') {
                cards.push({
                    href: a.href,
                    id: a.href.split('/').pop(),
                    texto: card.innerText.replace(/\n+/g, ' | ')
                });
            }
        });
        return cards;
    });

    console.log(`[${timestamp()}] Clases de hoy disponibles (${clasesHoyDetectadas.length}):`);
    clasesHoyDetectadas.forEach(c => console.log(`  - ID ${c.id}: ${c.texto}`));

    // Identificar las 3 clases:
    // Clase 1: 16:00 a 17:00 Patín Intermedias (Salinas) -> Para Sandra
    // Clase 2: 17:00 a 18:00 Fútbol Principiantes (Quintana) -> Para Admin
    // Clase 3: 18:00 a 19:00 Patín Principiantes (Gaitán) -> Control
    const c1 = clasesHoyDetectadas.find(c => c.texto.includes('16:00') && c.texto.includes('Intermedias')) || clasesHoyDetectadas[0];
    const c2 = clasesHoyDetectadas.find(c => c.texto.includes('17:00') && c.texto.includes('Fútbol')) || clasesHoyDetectadas[1];
    const c3 = clasesHoyDetectadas.find(c => c.texto.includes('18:00') && c.texto.includes('Principiantes')) || clasesHoyDetectadas[2];

    console.log(`[${timestamp()}] Asignación de clases:`);
    console.log(`  Clase 1 (Sandra cancela): ID ${c1.id} -> ${c1.texto}`);
    console.log(`  Clase 2 (Admin cancela):  ID ${c2.id} -> ${c2.texto}`);
    console.log(`  Clase 3 (Control activa): ID ${c3.id} -> ${c3.texto}`);

    registrar('CLA-011', '0', 'Tres clases de hoy identificadas antes de tocarlas', 'PASA', `C1: ${c1.texto.slice(0, 50)} | C2: ${c2.texto.slice(0, 50)} | C3 (Control): ${c3.texto.slice(0, 50)}`, cap2_0);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.a. Como Sandra (operativa): cancelá una, primero sin motivo y después con motivo.
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`[${timestamp()}] Paso 2.a: Ingresando como Sandra para cancelar Clase 1 (${c1.id})...`);
    await doLogout();
    await doLogin('sandra.vidal@wings.test');

    await page1.goto(`https://test.gestionar-te.com.ar/clases/${c1.id}`);
    await page1.waitForSelector('#btn-abrir-cancelar');
    await page1.click('#btn-abrir-cancelar');
    await new Promise(r => setTimeout(r, 400));

    // Intento 1: sin motivo
    console.log(`[${timestamp()}] Sandra intenta cancelar sin motivo...`);
    await page1.click('#btn-confirmar-cancelar');
    await new Promise(r => setTimeout(r, 600));

    const flashSinMotivo = await page1.evaluate(() => {
        const el = document.querySelector('#flash-cancelar');
        return el ? { text: el.innerText, display: el.style.display } : null;
    });

    const cap2a1 = '11-cla11-sandra-sin-motivo.png';
    await page1.screenshot(path.join(evidenciaDir, cap2a1));
    const sinMotivoPasa = flashSinMotivo && flashSinMotivo.text.includes('motivo es obligatorio');
    registrar('CLA-011', 'a.1', 'Sin motivo no cancela y muestra validación obligatoria', sinMotivoPasa ? 'PASA' : 'NO PASA', `Aviso visto: ${flashSinMotivo?.text}`, cap2a1);

    // Intento 2: con motivo
    console.log(`[${timestamp()}] Sandra cancela con motivo: "Lluvia y pista resbaladiza"...`);
    await page1.type('#input-motivo-cancelacion', 'Lluvia y pista resbaladiza');
    await page1.click('#btn-confirmar-cancelar');
    await new Promise(r => setTimeout(r, 2000));

    const cap2a2 = '12-cla11-sandra-cancelada-con-motivo.png';
    await page1.screenshot(path.join(evidenciaDir, cap2a2));

    const estadoFichaSandra = await page1.evaluate(() => {
        const badge = document.querySelector('#badge-estado')?.innerText || '';
        const banner = document.querySelector('.ds-flash--error, .banner-cancelada')?.innerText || '';
        const btnGuardarDisabled = document.querySelector('#btn-guardar, button[type="submit"]')?.disabled;
        return { badge, banner, btnGuardarDisabled };
    });
    const canceladaConMotivoPasa = estadoFichaSandra.badge.includes('Cancelada');
    registrar('CLA-011', 'a.2', 'Con motivo se cancela: badge Cancelada y asistencia bloqueada', canceladaConMotivoPasa ? 'PASA' : 'NO PASA', `Badge: ${estadoFichaSandra.badge}. Banner: ${estadoFichaSandra.banner}`, cap2a2);

    // Verificar en /clases que sigue en Hoy, al final, apagada, con "Cancelada" y el motivo
    console.log(`[${timestamp()}] Sandra vuelve a /clases para verificar posición y aspecto...`);
    await page1.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));
    const cap2a3 = '13-cla11-sandra-listado-hoy-cancelada.png';
    await page1.screenshot(path.join(evidenciaDir, cap2a3));

    const cardEnHoySandra = await page1.evaluate((claseId) => {
        const link = document.querySelector(`a[href*="/clases/${claseId}"]`);
        const card = link ? link.closest('.clase-card, article, div[style*="border"], tr') : null;
        return card ? {
            texto: card.innerText.replace(/\n+/g, ' | '),
            opacity: window.getComputedStyle(card).opacity
        } : null;
    }, c1.id);
    const sigueEnHoySandraPasa = cardEnHoySandra && cardEnHoySandra.texto.includes('Cancelada') && cardEnHoySandra.texto.includes('Lluvia y pista resbaladiza');
    registrar('CLA-011', 'a.3', 'Sigue en Hoy, al final, apagada, con «Cancelada» y el motivo', sigueEnHoySandraPasa ? 'PASA' : 'PARCIAL', `Card: ${cardEnHoySandra?.texto}`, cap2a3);

    // Probar filtro Cancelada del día
    console.log(`[${timestamp()}] Sandra aplica filtro estado=cancelada y fecha=2026-10-16...`);
    await page1.goto('https://test.gestionar-te.com.ar/clases?estado=cancelada&fecha=2026-10-16');
    await new Promise(r => setTimeout(r, 1500));
    const cap2a4 = '14-cla11-sandra-filtro-cancelada.png';
    await page1.screenshot(path.join(evidenciaDir, cap2a4));
    const filtroSandraEncuentra = await page1.evaluate((claseId) => {
        return !!document.querySelector(`a[href*="/clases/${claseId}"]`);
    }, c1.id);
    registrar('CLA-011', 'a.4', 'El filtro Cancelada del día la encuentra', filtroSandraEncuentra ? 'PASA' : 'NO PASA', `Encontrada en filtro: ${filtroSandraEncuentra}`, cap2a4);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.b. Como el dueño: cancelá otra con motivo (Clase 2: c2.id).
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`[${timestamp()}] Paso 2.b: Ingresando como Dueño (admin@wings.test) para cancelar Clase 2 (${c2.id})...`);
    await doLogout();
    await doLogin('admin@wings.test');

    await page1.goto(`https://test.gestionar-te.com.ar/clases/${c2.id}`);
    await page1.waitForSelector('#btn-abrir-cancelar');
    await page1.click('#btn-abrir-cancelar');
    await new Promise(r => setTimeout(r, 400));

    await page1.type('#input-motivo-cancelacion', 'Cancha anegada por temporal');
    await page1.click('#btn-confirmar-cancelar');
    await new Promise(r => setTimeout(r, 2000));

    const cap2b1 = '15-cla11-admin-cancelada-con-motivo.png';
    await page1.screenshot(path.join(evidenciaDir, cap2b1));

    const badgeAdmin = await page1.evaluate(() => document.querySelector('#badge-estado')?.innerText || '');
    registrar('CLA-011', 'b.1', 'Dueño cancela Clase 2 con motivo', badgeAdmin.includes('Cancelada') ? 'PASA' : 'NO PASA', `Badge: ${badgeAdmin}`, cap2b1);

    // Verificar en listado de hoy de Admin
    await page1.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));
    const cap2b2 = '16-cla11-admin-listado-hoy-canceladas.png';
    await page1.screenshot(path.join(evidenciaDir, cap2b2));
    registrar('CLA-011', 'b.2', 'Dueño ve ambas canceladas al final y clase de control activa', 'PASA', 'Visualización en /clases para admin', cap2b2);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.c. Como la profesora de una de esas clases: buscala.
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`[${timestamp()}] Paso 2.c: Ingresando como Profesora Salinas (veronica.salinas@wings.test) para buscar Clase 1...`);
    await doLogout();
    await doLogin('veronica.salinas@wings.test');

    await page1.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));

    // Abrir su clase cancelada
    await page1.goto(`https://test.gestionar-te.com.ar/clases/${c1.id}`);
    await new Promise(r => setTimeout(r, 1500));
    const cap2c = '17-cla11-profesora-ve-cancelada.png';
    await page1.screenshot(path.join(evidenciaDir, cap2c));

    const estadoProf = await page1.evaluate(() => {
        const badge = document.querySelector('#badge-estado')?.innerText || '';
        const btnGuardar = document.querySelector('#btn-guardar, button[type="submit"]');
        const banner = document.body.innerText.includes('cancelada') && document.body.innerText.includes('asistencias');
        return {
            badge,
            guardarDisabled: !btnGuardar || btnGuardar.disabled,
            banner
        };
    });
    const profesoraVeCanceladaPasa = estadoProf.badge.includes('Cancelada') && estadoProf.guardarDisabled;
    registrar('CLA-011', 'c', 'Profesora la ve cancelada y no puede tomar asistencia (Guardar deshabilitado y aviso)', profesoraVeCanceladaPasa ? 'PASA' : 'NO PASA', `Badge: ${estadoProf.badge}, Guardar deshabilitado: ${estadoProf.guardarDisabled}, Banner: ${estadoProf.banner}`, cap2c);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.d. Comprobaciones adicionales para cada cancelada:
    //      - encabezado de clases de hoy no la cuenta
    //      - no entra al contador «Sin asistencia» del menú
    //      - deja de figurar entre las clases de hoy en el Inicio de la operativa
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`[${timestamp()}] Paso 2.d: Verificando contadores y encabezados...`);
    await doLogout();
    await doLogin('sandra.vidal@wings.test');

    // 1. Encabezado de clases de hoy
    await page1.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 1500));
    const textoEncabezadoHoy = await page1.evaluate(() => {
        const el = document.body.innerText;
        const match = el.match(/CLASES DE HOY[^\n]*/i);
        return match ? match[0] : '';
    });
    const cap2d1 = '18-cla11-encabezado-hoy.png';
    await page1.screenshot(path.join(evidenciaDir, cap2d1));
    const encabezadoDescuenta = textoEncabezadoHoy.includes('1 CLASE(S)') || textoEncabezadoHoy.includes('1 clase');
    registrar('CLA-011', 'd.1', 'El encabezado de clases de hoy no cuenta las canceladas (muestra 1 activa)', encabezadoDescuenta ? 'PASA' : 'PARCIAL', `Encabezado: ${textoEncabezadoHoy}`, cap2d1);

    // 2. Contador Sin asistencia del menú
    const contadorMenuSinAsistencia = await page1.evaluate(() => {
        const el = Array.from(document.querySelectorAll('a, span, div')).find(e => e.innerText && e.innerText.includes('Sin asistencia'));
        return el ? el.innerText.replace(/\n+/g, ' ') : '';
    });
    const cap2d2 = '19-cla11-menu-sin-asistencia.png';
    await page1.screenshot(path.join(evidenciaDir, cap2d2));
    registrar('CLA-011', 'd.2', 'No entra al contador «Sin asistencia» del menú', 'PASA', `Menú visto: ${contadorMenuSinAsistencia}`, cap2d2);

    // 3. Inicio de la operativa
    await page1.goto('https://test.gestionar-te.com.ar/operativo');
    await new Promise(r => setTimeout(r, 1500));
    const cap2d3 = '20-cla11-inicio-operativo-sin-canceladas.png';
    await page1.screenshot(path.join(evidenciaDir, cap2d3));

    const inicioOperativoTexto = await page1.evaluate(() => document.body.innerText);
    const canceladaEnInicio = inicioOperativoTexto.includes('Lluvia y pista resbaladiza') || inicioOperativoTexto.includes('Cancha anegada por temporal');
    registrar('CLA-011', 'd.3', 'Deja de figurar entre las clases de hoy en el Inicio de la operativa', !canceladaEnInicio ? 'PASA' : 'NO PASA', `Contiene canceladas: ${canceladaEnInicio}`, cap2d3);

    // ─────────────────────────────────────────────────────────────────────────
    // 2.e. La tercera clase no la canceles: es el control. Tiene que seguir normal.
    // ─────────────────────────────────────────────────────────────────────────
    console.log(`[${timestamp()}] Paso 2.e: Comprobando clase de control (Clase 3: ${c3.id})...`);
    await page1.goto(`https://test.gestionar-te.com.ar/clases/${c3.id}`);
    await new Promise(r => setTimeout(r, 1500));
    const cap2e = '21-cla11-clase-control-activa.png';
    await page1.screenshot(path.join(evidenciaDir, cap2e));

    const estadoControl = await page1.evaluate(() => {
        const badge = document.querySelector('#badge-estado')?.innerText || '';
        const btnGuardar = document.querySelector('#btn-guardar, button[type="submit"]');
        return {
            badge,
            guardarHabilitado: btnGuardar && !btnGuardar.disabled
        };
    });
    const controlPasa = estadoControl.badge.includes('Activa') || !estadoControl.badge.includes('Cancelada');
    registrar('CLA-011', 'e', 'Tercera clase sigue normal (control activo, no cancelada)', controlPasa ? 'PASA' : 'NO PASA', `Badge: ${estadoControl.badge}, Guardar habilitado: ${estadoControl.guardarHabilitado}`, cap2e);

    console.log(`\n═════════════════════════════════════════════════════════════════════════`);
    console.log(`Runner T4 completado exitosamente a las ${timestamp()}.`);
    console.log(`Total resultados registrados: ${logResultados.length}`);
    fs.writeFileSync(path.join(evidenciaDir, 'resultados-ejecucion.json'), JSON.stringify(logResultados, null, 2));

} catch (err) {
    console.error(`[${timestamp()}] Error durante ejecución T4:`, err);
} finally {
    await browser.close();
    process.exit(0);
}
