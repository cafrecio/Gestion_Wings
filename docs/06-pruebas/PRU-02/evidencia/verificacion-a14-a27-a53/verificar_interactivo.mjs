import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createBrowserTarget } from './cdp.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..', '..', '..', '..', '..');
const capturasDir = path.join(__dirname, 'capturas');

if (!fs.existsSync(capturasDir)) {
    fs.mkdirSync(capturasDir, { recursive: true });
}

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chromeProfile = path.join(__dirname, 'chrome-profile');

const chrome = spawn(chromePath, [
    '--headless=new',
    '--remote-debugging-port=9222',
    `--user-data-dir=${chromeProfile}`,
    '--no-sandbox',
    '--disable-gpu',
    '--hide-scrollbars'
]);

// Esperar a que Chrome levante el puerto
await new Promise(r => setTimeout(r, 2000));

const mediciones = {};

try {
    const page = await createBrowserTarget(9222);

    // =========================================================================
    // CONTROL INICIAL: LOGIN A 375x667
    // =========================================================================
    console.log('--- Control inicial: Login a 375x667 ---');
    await page.setViewport(375, 667, true);
    await page.navigate('http://127.0.0.1:8088/login');
    const loginW = await page.evaluate('window.innerWidth');
    const loginH = await page.evaluate('window.innerHeight');
    mediciones['login_control'] = { innerWidth: loginW, innerHeight: loginH };
    console.log(`Login viewport verificado: ${loginW}x${loginH}`);
    await page.captureScreenshot(path.join(capturasDir, '00-login-control-375.png'));

    // Iniciar sesión como ADMIN
    console.log('--- Iniciando sesión como ADMIN ---');
    await page.evaluate(`
        (() => {
            document.getElementById('email').value = 'admin@wings.com';
            document.getElementById('password').value = 'password123';
            document.querySelector('button[type="submit"]').click();
        })()
    `);
    await new Promise(r => setTimeout(r, 2000));
    const urlPostLogin = await page.evaluate('window.location.href');
    console.log('Login exitoso, URL:', urlPostLogin);

    // =========================================================================
    // DEFECTO A14: FORMULARIOS LARGOS, BOTONES Y ERRORES A LA VISTA
    // =========================================================================
    console.log('--- A14: 1. Alta de Alumnos (/alumnos/create) ---');
    await page.navigate('http://127.0.0.1:8088/alumnos/create');
    await new Promise(r => setTimeout(r, 800));

    // A14 P1: Al abrir, sin desplazarse, posición de botones de acción
    const a14P1 = await page.evaluate(`
        (() => {
            const el = document.querySelector('.mobile-form-actions');
            const r = el ? el.getBoundingClientRect() : null;
            return r ? { top: r.top, bottom: r.bottom, height: r.height, left: r.left, right: r.right, within667: r.bottom <= 667 && r.top < 667 } : null;
        })()
    `);
    mediciones['a14_p1_alumnos_create_acciones_inicial'] = a14P1;
    console.log('A14 P1 Alta Alumnos acciones inicial:', a14P1);
    await page.captureScreenshot(path.join(capturasDir, 'a14-01-alumnos-create-inicial.png'));

    // A14 P2: Desplazarse al final y comprobar que siguen visibles
    await page.evaluate(`window.scrollTo(0, document.body.scrollHeight);`);
    await new Promise(r => setTimeout(r, 400));
    const a14P2 = await page.evaluate(`
        (() => {
            const el = document.querySelector('.mobile-form-actions');
            const r = el ? el.getBoundingClientRect() : null;
            return r ? { top: r.top, bottom: r.bottom, height: r.height, scrollY: window.scrollY, within667: r.bottom <= 667 } : null;
        })()
    `);
    mediciones['a14_p2_alumnos_create_acciones_fondo'] = a14P2;
    console.log('A14 P2 Alta Alumnos acciones fondo:', a14P2);
    await page.captureScreenshot(path.join(capturasDir, 'a14-02-alumnos-create-fondo.png'));

    // A14 P3: Barra fija no tapa el último campo
    const a14P3 = await page.evaluate(`
        (() => {
            window.scrollTo(0, document.body.scrollHeight);
            const fields = [...document.querySelectorAll('form.mobile-form input:not([type="hidden"]), form.mobile-form select, form.mobile-form textarea')];
            const last = fields[fields.length - 1];
            const lastRect = last ? last.getBoundingClientRect() : null;
            const bar = document.querySelector('.mobile-form-actions');
            const barRect = bar ? bar.getBoundingClientRect() : null;
            return {
                lastFieldId: last ? last.id : null,
                lastFieldBottom: lastRect ? lastRect.bottom : null,
                barTop: barRect ? barRect.top : null,
                libertadEspacio: barRect && lastRect ? (barRect.top - lastRect.bottom) : null
            };
        })()
    `);
    mediciones['a14_p3_alumnos_create_ultimo_campo'] = a14P3;
    console.log('A14 P3 Alta Alumnos distancia último campo:', a14P3);
    await page.captureScreenshot(path.join(capturasDir, 'a14-03-alumnos-create-ultimo-campo.png'));

    // A14 P4: Enviar formulario vacío -> contar errores del cartel dinámico
    await page.evaluate(`
        (() => {
            window.scrollTo(0, 0);
            document.querySelector('.mobile-form-actions button[type="submit"]').click();
        })()
    `);
    await new Promise(r => setTimeout(r, 500));
    const a14P4 = await page.evaluate(`
        (() => {
            const summary = document.getElementById('alumno-error-resumen');
            const items = [...summary.querySelectorAll('li')].map(li => li.textContent.trim());
            const rect = summary.getBoundingClientRect();
            return {
                hidden: summary.hidden,
                cantidad: items.length,
                mensajes: items,
                posicion: { top: rect.top, bottom: rect.bottom, height: rect.height }
            };
        })()
    `);
    mediciones['a14_p4_alumnos_create_errores_vacio'] = a14P4;
    console.log('A14 P4 Alta Alumnos errores al enviar vacío:', a14P4);
    await page.captureScreenshot(path.join(capturasDir, 'a14-04-alumnos-create-errores-vacio.png'));

    // A14 P5: Completar un campo con error (nombre) -> sale del cartel sin recargar
    await page.evaluate(`
        (() => {
            const inpNombre = document.getElementById('nombre');
            inpNombre.value = 'Camila Sofía';
            inpNombre.dispatchEvent(new Event('input', { bubbles: true }));
            inpNombre.dispatchEvent(new Event('change', { bubbles: true }));
        })()
    `);
    await new Promise(r => setTimeout(r, 300));
    const a14P5CompletaNombre = await page.evaluate(`
        (() => {
            const summary = document.getElementById('alumno-error-resumen');
            const items = [...summary.querySelectorAll('li')].map(li => li.textContent.trim());
            return {
                cantidad: items.length,
                mensajes: items,
                contieneNombre: items.some(m => m.toLowerCase().includes('nombre:'))
            };
        })()
    `);
    mediciones['a14_p5_alumnos_create_completa_nombre'] = a14P5CompletaNombre;
    console.log('A14 P5 Errores tras completar nombre:', a14P5CompletaNombre);
    await page.captureScreenshot(path.join(capturasDir, 'a14-05-alumnos-create-completa-nombre.png'));

    // Vaciarlo de nuevo -> vuelve a aparecer
    await page.evaluate(`
        (() => {
            const inpNombre = document.getElementById('nombre');
            inpNombre.value = '';
            inpNombre.dispatchEvent(new Event('input', { bubbles: true }));
            inpNombre.dispatchEvent(new Event('change', { bubbles: true }));
        })()
    `);
    await new Promise(r => setTimeout(r, 300));
    const a14P5VaciaNombre = await page.evaluate(`
        (() => {
            const summary = document.getElementById('alumno-error-resumen');
            const items = [...summary.querySelectorAll('li')].map(li => li.textContent.trim());
            return {
                cantidad: items.length,
                contieneNombre: items.some(m => m.toLowerCase().includes('nombre:'))
            };
        })()
    `);
    mediciones['a14_p5_alumnos_create_vacia_nombre'] = a14P5VaciaNombre;
    console.log('A14 P5 Vuelve tras vaciar nombre:', a14P5VaciaNombre);

    // Completar todos los obligatorios -> cartel desaparece
    await page.evaluate(`
        (() => {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.value = val;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            };
            setVal('nombre', 'Camila');
            setVal('apellido', 'Sosa');
            setVal('dni', '42999111');
            setVal('fecha_nacimiento', '2014-06-15');
            setVal('fecha_alta', '2026-10-01');
            const depSelect = document.getElementById('deporte_id');
            if (depSelect && depSelect.options.length > 1) {
                depSelect.selectedIndex = 1;
                depSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const grp = document.getElementById('grupo_id');
            if (grp && grp.options.length > 1) {
                grp.selectedIndex = 1;
                grp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        })()
    `);
    await new Promise(r => setTimeout(r, 400));
    const a14P5TodosCompletos = await page.evaluate(`
        (() => {
            const summary = document.getElementById('alumno-error-resumen');
            return {
                hidden: summary.hidden,
                display: window.getComputedStyle(summary).display
            };
        })()
    `);
    mediciones['a14_p5_alumnos_create_todos_completos'] = a14P5TodosCompletos;
    console.log('A14 P5 Cartel tras completar todos:', a14P5TodosCompletos);
    await page.captureScreenshot(path.join(capturasDir, 'a14-05-alumnos-create-todos-completos.png'));

    // A14 P6: DNI repetido (40111222 ya existe en Valentina Domínguez de la Sierra)
    console.log('A14 P6: Probando DNI repetido 40111222...');
    await page.evaluate(`
        (() => {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.value = val;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            };
            setVal('nombre', 'Camila');
            setVal('apellido', 'Sosa');
            setVal('dni', '40111222'); // DNI repetido en Alumno ID 3
            setVal('fecha_nacimiento', '2014-06-15');
            setVal('fecha_alta', '2026-10-01');
            const depSelect = document.getElementById('deporte_id');
            if (depSelect && depSelect.options.length > 1) {
                depSelect.selectedIndex = 1;
                depSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const grp = document.getElementById('grupo_id');
            if (grp && grp.options.length > 1) {
                grp.selectedIndex = 1;
                grp.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const plan = document.getElementById('plan_id');
            if (plan && plan.options.length > 1) {
                plan.selectedIndex = 1;
                plan.dispatchEvent(new Event('change', { bubbles: true }));
            }
        })()
    `);
    // Esperar a que alumnos-inscripcion.js verifique la cuota de alta y limpie setCustomValidity
    await new Promise(r => setTimeout(r, 1200));

    // Si aparece la opción de cuota de alta requerida, marcarla
    await page.evaluate(`
        (() => {
            const radioSi = document.getElementById('cuota-alta-si');
            if (radioSi && !radioSi.disabled) {
                radioSi.checked = true;
                radioSi.dispatchEvent(new Event('change', { bubbles: true }));
            }
            // Enviar formulario al backend
            document.querySelector('.mobile-form-actions button[type="submit"]').click();
        })()
    `);
    await new Promise(r => setTimeout(r, 2200));

    const a14P6DniRechazado = await page.evaluate(`
        (() => {
            const summary = document.getElementById('alumno-error-resumen');
            const items = summary ? [...summary.querySelectorAll('li')].map(li => li.textContent.trim()) : [];
            return {
                hidden: summary ? summary.hidden : null,
                items: items,
                headerStrong: summary ? summary.querySelector('strong')?.textContent.trim() : null,
                headerDiv: summary ? summary.querySelector('div')?.textContent.trim() : null
            };
        })()
    `);
    mediciones['a14_p6_dni_repetido_servidor'] = a14P6DniRechazado;
    console.log('A14 P6 Estado tras respuesta del servidor con DNI repetido:', a14P6DniRechazado);
    await page.captureScreenshot(path.join(capturasDir, 'a14-06-alumnos-create-dni-repetido-servidor.png'));

    // Modificar DNI a 40111223 -> cartel pasa a "pendiente de comprobar al guardar"
    await page.evaluate(`
        (() => {
            const dniInput = document.getElementById('dni');
            dniInput.value = '40111223';
            dniInput.dispatchEvent(new Event('input', { bubbles: true }));
            dniInput.dispatchEvent(new Event('change', { bubbles: true }));
        })()
    `);
    await new Promise(r => setTimeout(r, 400));
    const a14P6DniModificado = await page.evaluate(`
        (() => {
            const summary = document.getElementById('alumno-error-resumen');
            const items = summary ? [...summary.querySelectorAll('li')].map(li => li.textContent.trim()) : [];
            return {
                items: items,
                headerStrong: summary ? summary.querySelector('strong')?.textContent.trim() : null,
                headerDiv: summary ? summary.querySelector('div')?.textContent.trim() : null
            };
        })()
    `);
    mediciones['a14_p6_dni_modificado_pendiente'] = a14P6DniModificado;
    console.log('A14 P6 Estado tras modificar DNI (pendiente):', a14P6DniModificado);
    await page.captureScreenshot(path.join(capturasDir, 'a14-06-alumnos-create-dni-pendiente.png'));

    // A14 P7: El cartel no tapa el encabezado ni queda debajo de él
    const a14P7 = await page.evaluate(`
        (() => {
            window.scrollTo(0, 0);
            const header = document.querySelector('.module-header') || document.querySelector('.ds-topbar');
            const summary = document.getElementById('alumno-error-resumen');
            const hRect = header ? header.getBoundingClientRect() : null;
            const sRect = summary ? summary.getBoundingClientRect() : null;
            return {
                headerBottom: hRect ? hRect.bottom : null,
                summaryTop: sRect ? sRect.top : null,
                summaryHeight: sRect ? sRect.height : null,
                superposicion: (hRect && sRect) ? (sRect.top < hRect.bottom) : false,
                distanciaLibre: (hRect && sRect) ? (sRect.top - hRect.bottom) : null
            };
        })()
    `);
    mediciones['a14_p7_header_y_cartel'] = a14P7;
    console.log('A14 P7 Relación Header y Cartel:', a14P7);

    // -------------------------------------------------------------------------
    // A14 Pantalla 2: Edición de Alumnos (/alumnos/3/edit)
    // -------------------------------------------------------------------------
    console.log('--- A14: 2. Edición de Alumnos (/alumnos/3/edit) ---');
    await page.navigate('http://127.0.0.1:8088/alumnos/3/edit');
    await new Promise(r => setTimeout(r, 800));

    const a14EditP1 = await page.evaluate(`
        (() => {
            const el = document.querySelector('.mobile-form-actions');
            const r = el ? el.getBoundingClientRect() : null;
            return r ? { top: r.top, bottom: r.bottom, height: r.height, within667: r.bottom <= 667 } : null;
        })()
    `);
    mediciones['a14_edicion_alumnos_acciones_inicial'] = a14EditP1;
    console.log('A14 Edición Alumnos acciones inicial:', a14EditP1);
    await page.captureScreenshot(path.join(capturasDir, 'a14-07-alumnos-edit-inicial.png'));

    await page.evaluate(`window.scrollTo(0, document.body.scrollHeight);`);
    await new Promise(r => setTimeout(r, 400));
    const a14EditP2 = await page.evaluate(`
        (() => {
            const el = document.querySelector('.mobile-form-actions');
            const r = el ? el.getBoundingClientRect() : null;
            const fields = [...document.querySelectorAll('form.mobile-form input:not([type="hidden"]), form.mobile-form select, form.mobile-form textarea')];
            const last = fields[fields.length - 1];
            const lastRect = last ? last.getBoundingClientRect() : null;
            return {
                actions: r ? { top: r.top, bottom: r.bottom, height: r.height, within667: r.bottom <= 667 } : null,
                ultimoCampo: last ? last.id : null,
                libertadEspacio: r && lastRect ? (r.top - lastRect.bottom) : null
            };
        })()
    `);
    mediciones['a14_edicion_alumnos_acciones_fondo'] = a14EditP2;
    console.log('A14 Edición Alumnos acciones fondo y último campo:', a14EditP2);
    await page.captureScreenshot(path.join(capturasDir, 'a14-08-alumnos-edit-fondo.png'));

    // -------------------------------------------------------------------------
    // A14 Pantalla 3: Alta de Profesores (/profesores/create)
    // -------------------------------------------------------------------------
    console.log('--- A14: 3. Alta de Profesores (/profesores/create) ---');
    await page.navigate('http://127.0.0.1:8088/profesores/create');
    await new Promise(r => setTimeout(r, 800));

    const a14ProfCreateP1 = await page.evaluate(`
        (() => {
            const el = document.querySelector('.mobile-form-actions');
            const r = el ? el.getBoundingClientRect() : null;
            return r ? { top: r.top, bottom: r.bottom, height: r.height, within667: r.bottom <= 667 } : null;
        })()
    `);
    mediciones['a14_profesores_create_acciones_inicial'] = a14ProfCreateP1;
    console.log('A14 Alta Profesores acciones inicial:', a14ProfCreateP1);
    await page.captureScreenshot(path.join(capturasDir, 'a14-09-profesores-create-inicial.png'));

    // Submit vacío en profesores create
    await page.evaluate(`
        (() => {
            document.querySelector('.mobile-form-actions button[type="submit"]').click();
        })()
    `);
    await new Promise(r => setTimeout(r, 500));
    const a14ProfCreateErrores = await page.evaluate(`
        (() => {
            const summary = document.getElementById('profesor-error-resumen');
            const items = summary ? [...summary.querySelectorAll('li')].map(li => li.textContent.trim()) : [];
            return {
                hidden: summary ? summary.hidden : null,
                cantidad: items.length,
                mensajes: items
            };
        })()
    `);
    mediciones['a14_profesores_create_errores_vacio'] = a14ProfCreateErrores;
    console.log('A14 Alta Profesores errores al enviar vacío:', a14ProfCreateErrores);
    await page.captureScreenshot(path.join(capturasDir, 'a14-10-profesores-create-errores.png'));

    // -------------------------------------------------------------------------
    // A14 Pantalla 4: Edición de Profesores (/profesores/6/edit)
    // -------------------------------------------------------------------------
    console.log('--- A14: 4. Edición de Profesores (/profesores/6/edit) ---');
    await page.navigate('http://127.0.0.1:8088/profesores/6/edit');
    await new Promise(r => setTimeout(r, 800));

    const a14ProfEditP1 = await page.evaluate(`
        (() => {
            const el = document.querySelector('.mobile-form-actions');
            const r = el ? el.getBoundingClientRect() : null;
            return r ? { top: r.top, bottom: r.bottom, height: r.height, within667: r.bottom <= 667 } : null;
        })()
    `);
    mediciones['a14_profesores_edit_acciones_inicial'] = a14ProfEditP1;
    console.log('A14 Edición Profesores acciones inicial:', a14ProfEditP1);
    await page.captureScreenshot(path.join(capturasDir, 'a14-11-profesores-edit-inicial.png'));

    await page.evaluate(`window.scrollTo(0, document.body.scrollHeight);`);
    await new Promise(r => setTimeout(r, 400));
    const a14ProfEditP2 = await page.evaluate(`
        (() => {
            const el = document.querySelector('.mobile-form-actions');
            const r = el ? el.getBoundingClientRect() : null;
            const fields = [...document.querySelectorAll('form.mobile-form input:not([type="hidden"]), form.mobile-form select, form.mobile-form textarea')];
            const last = fields[fields.length - 1];
            const lastRect = last ? last.getBoundingClientRect() : null;
            return {
                actions: r ? { top: r.top, bottom: r.bottom, height: r.height, within667: r.bottom <= 667 } : null,
                ultimoCampo: last ? last.id : null,
                libertadEspacio: r && lastRect ? (r.top - lastRect.bottom) : null
            };
        })()
    `);
    mediciones['a14_profesores_edit_acciones_fondo'] = a14ProfEditP2;
    console.log('A14 Edición Profesores fondo y último campo:', a14ProfEditP2);
    await page.captureScreenshot(path.join(capturasDir, 'a14-12-profesores-edit-fondo.png'));


    // =========================================================================
    // DEFECTO A27: MOVIMIENTO DE CAJA EN CELULAR
    // =========================================================================
    console.log('--- A27: Movimiento de Caja con ADMIN ---');
    await page.navigate('http://127.0.0.1:8088/caja/movimiento');
    await new Promise(r => setTimeout(r, 800));

    // A27 Paso 1: Con ADMIN, Registrar y Cancelar dentro de 375 x 667
    const a27P1Admin = await page.evaluate(`
        (() => {
            const bar = document.querySelector('.mobile-form-actions');
            const r = bar ? bar.getBoundingClientRect() : null;
            return r ? { top: r.top, bottom: r.bottom, height: r.height, within667: r.bottom <= 667 } : null;
        })()
    `);
    mediciones['a27_p1_admin_posicion'] = a27P1Admin;
    console.log('A27 P1 Admin posición acciones:', a27P1Admin);
    await page.captureScreenshot(path.join(capturasDir, 'a27-01-movimiento-admin-posicion.png'));

    // A27 Paso 2: Campo Observaciones se alcanza y no queda tapado
    const a27P2 = await page.evaluate(`
        (() => {
            window.scrollTo(0, document.body.scrollHeight);
            const obs = document.getElementById('observaciones');
            const bar = document.querySelector('.mobile-form-actions');
            const oRect = obs ? obs.getBoundingClientRect() : null;
            const bRect = bar ? bar.getBoundingClientRect() : null;
            return {
                obsBottom: oRect ? oRect.bottom : null,
                barTop: bRect ? bRect.top : null,
                separacion: bRect && oRect ? (bRect.top - oRect.bottom) : null
            };
        })()
    `);
    mediciones['a27_p2_observaciones_campo'] = a27P2;
    console.log('A27 P2 Observaciones campo:', a27P2);
    await page.captureScreenshot(path.join(capturasDir, 'a27-02-movimiento-observaciones-campo.png'));

    // A27 Paso 3: Enviar con errores -> resumen aparece y se actualiza al corregir
    console.log('A27 P3: Enviando movimiento sin campos obligatorios...');
    await page.evaluate(`
        (() => {
            document.querySelector('.mobile-form-actions button[type="submit"]').click();
        })()
    `);
    await new Promise(r => setTimeout(r, 400));
    const a27P3 = await page.evaluate(`
        (() => {
            const summary = document.getElementById('movimiento-error-resumen');
            const items = summary ? [...summary.querySelectorAll('li')].map(li => li.textContent.trim()) : [];
            return {
                hidden: summary ? summary.hidden : null,
                cantidad: items.length,
                mensajes: items
            };
        })()
    `);
    mediciones['a27_p3_errores_movimiento'] = a27P3;
    console.log('A27 P3 Errores movimiento:', a27P3);
    await page.captureScreenshot(path.join(capturasDir, 'a27-03-movimiento-resumen-errores.png'));

    // Cambiar a rol OPERATIVO limpiando cookies
    console.log('--- A27: Cambiando a OPERATIVO ---');
    await page.send('Network.clearBrowserCookies');
    await page.navigate('http://127.0.0.1:8088/login');
    await new Promise(r => setTimeout(r, 600));

    await page.evaluate(`
        (() => {
            document.getElementById('email').value = 'operativo@wings.com';
            document.getElementById('password').value = 'password123';
            document.querySelector('button[type="submit"]').click();
        })()
    `);
    await new Promise(r => setTimeout(r, 2000));

    await page.navigate('http://127.0.0.1:8088/caja/movimiento');
    await new Promise(r => setTimeout(r, 800));

    // A27 Paso 1 con OPERATIVO
    const a27P1Operativo = await page.evaluate(`
        (() => {
            const bar = document.querySelector('.mobile-form-actions');
            const r = bar ? bar.getBoundingClientRect() : null;
            return r ? { top: r.top, bottom: r.bottom, height: r.height, within667: r.bottom <= 667 } : null;
        })()
    `);
    mediciones['a27_p1_operativo_posicion'] = a27P1Operativo;
    console.log('A27 P1 Operativo posición acciones:', a27P1Operativo);
    await page.captureScreenshot(path.join(capturasDir, 'a27-04-movimiento-operativo-posicion.png'));

    // A27 Paso 4: Registrar movimiento real de punta a punta tocando los botones
    console.log('A27 P4: Registrando movimiento real con OPERATIVO...');
    await page.evaluate(`
        (() => {
            // Seleccionar tipo Egreso
            const btnEgreso = document.getElementById('btn-egreso');
            if (btnEgreso) btnEgreso.click();

            // Seleccionar Medio de pago (tipo_caja_id)
            const tipoCajaSelect = document.getElementById('tipo_caja_id');
            if (tipoCajaSelect && tipoCajaSelect.options.length > 1) {
                tipoCajaSelect.selectedIndex = 1;
                tipoCajaSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Seleccionar Rubro
            const rubroSelect = document.getElementById('rubro_id');
            if (rubroSelect) {
                for (let i = 1; i < rubroSelect.options.length; i++) {
                    if (rubroSelect.options[i].style.display !== 'none') {
                        rubroSelect.selectedIndex = i;
                        rubroSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            }

            // Seleccionar Subrubro
            const subrubroSelect = document.getElementById('subrubro_id');
            if (subrubroSelect) {
                for (let i = 1; i < subrubroSelect.options.length; i++) {
                    if (subrubroSelect.options[i].style.display !== 'none') {
                        subrubroSelect.selectedIndex = i;
                        subrubroSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            }

            // Completar monto
            const montoInp = document.getElementById('monto');
            montoInp.value = '1500';
            montoInp.dispatchEvent(new Event('input', { bubbles: true }));

            // Observaciones
            const obsInp = document.getElementById('observaciones');
            if (obsInp) {
                obsInp.value = 'Verificación A27 caja operativo lavandina';
                obsInp.dispatchEvent(new Event('input', { bubbles: true }));
            }

            // Tocar Registrar
            document.querySelector('.mobile-form-actions button[type="submit"]').click();
        })()
    `);
    await new Promise(r => setTimeout(r, 2500));
    const urlTrasMov = await page.evaluate('window.location.href');
    mediciones['a27_p4_url_tras_registrar'] = urlTrasMov;
    console.log('A27 P4 URL tras registrar movimiento:', urlTrasMov);
    await page.captureScreenshot(path.join(capturasDir, 'a27-05-movimiento-registrado-exito.png'));


    // =========================================================================
    // DEFECTO A53: DATOS COMPLETOS EN GRUPOS Y EN SELECTOR DE COBRO
    // =========================================================================
    console.log('--- A53: Grupos en Celular (375x667) ---');
    await page.navigate('http://127.0.0.1:8088/grupos');
    await new Promise(r => setTimeout(r, 800));

    // A53 Paso 2: En Grupos a 375, nombre y tarifas se leen enteros y no desbordan
    const a53Grupos = await page.evaluate(`
        (() => {
            const cards = [...document.querySelectorAll('.alumno-card.mobile-readable-card')];
            const target = cards.find(c => c.textContent.includes('Entrenamiento Especial Federadas'));
            if (!target) return { encontrado: false };

            const nombreEl = target.querySelector('.alumno-nombre');
            const tarifasEl = target.querySelector('.info-value');
            const docScrollW = document.documentElement.scrollWidth;

            return {
                encontrado: true,
                nombreTexto: nombreEl ? nombreEl.textContent.trim() : '',
                nombreScrollW: nombreEl ? nombreEl.scrollWidth : null,
                nombreClientW: nombreEl ? nombreEl.clientWidth : null,
                nombreDesborda: nombreEl ? (nombreEl.scrollWidth > nombreEl.clientWidth) : null,
                tarifasTexto: tarifasEl ? tarifasEl.textContent.trim() : '',
                tarifasScrollW: tarifasEl ? tarifasEl.scrollWidth : null,
                tarifasClientW: tarifasEl ? tarifasEl.clientWidth : null,
                tarifasDesborda: tarifasEl ? (tarifasEl.scrollWidth > tarifasEl.clientWidth) : null,
                docScrollWidth: docScrollW,
                desbordaDoc: docScrollW > 375
            };
        })()
    `);
    mediciones['a53_p2_grupos_verificacion'] = a53Grupos;
    console.log('A53 P2 Grupos verificación:', a53Grupos);
    await page.captureScreenshot(path.join(capturasDir, 'a53-01-grupos-nombre-largo-tarifas.png'));

    // A53 Paso 3: Selector de cobro (/caja/cobrar)
    console.log('--- A53: Selector de Cobro (/caja/cobrar en 375x667) ---');
    await page.navigate('http://127.0.0.1:8088/caja/cobrar');
    await new Promise(r => setTimeout(r, 800));

    const a53Selector = await page.evaluate(`
        (() => {
            const cards = [...document.querySelectorAll('.alumno-card.mobile-readable-card')];
            const target = cards.find(c => c.textContent.includes('Domínguez de la Sierra'));
            if (!target) return { encontrado: false, totalCards: cards.length };

            const nombreEl = target.querySelector('.alumno-nombre');
            const grupoEl = target.querySelector('.info-item:nth-child(3) .info-value') || target.querySelector('.info-value');
            const saldoEl = target.querySelector('.info-item:nth-child(1) .info-value');
            const docScrollW = document.documentElement.scrollWidth;

            return {
                encontrado: true,
                nombreTexto: nombreEl ? nombreEl.textContent.trim() : '',
                nombreScrollW: nombreEl ? nombreEl.scrollWidth : null,
                nombreClientW: nombreEl ? nombreEl.clientWidth : null,
                nombreDesborda: nombreEl ? (nombreEl.scrollWidth > nombreEl.clientWidth) : null,
                grupoTexto: grupoEl ? grupoEl.textContent.trim() : '',
                grupoScrollW: grupoEl ? grupoEl.scrollWidth : null,
                grupoClientW: grupoEl ? grupoEl.clientWidth : null,
                grupoDesborda: grupoEl ? (grupoEl.scrollWidth > grupoEl.clientWidth) : null,
                saldoTexto: saldoEl ? saldoEl.textContent.trim() : '',
                docScrollWidth: docScrollW,
                desbordaDoc: docScrollW > 375
            };
        })()
    `);
    mediciones['a53_p3_selector_cobro'] = a53Selector;
    console.log('A53 P3 Selector de cobro verificación:', a53Selector);
    await page.captureScreenshot(path.join(capturasDir, 'a53-02-selector-cobro-alumno-largo.png'));

    // A53 Paso 5: Grupo corto en Grupos se ve bien sin renglones vacíos
    await page.navigate('http://127.0.0.1:8088/grupos');
    await new Promise(r => setTimeout(r, 600));
    await page.captureScreenshot(path.join(capturasDir, 'a53-03-grupos-datos-cortos.png'));


    // =========================================================================
    // REGRESIONES: A4 (SALIR SIN GUARDAR) Y ESCRITORIO 1280
    // =========================================================================
    console.log('--- Regresión A4: Salir sin guardar en alumnos-form.js ---');
    await page.navigate('http://127.0.0.1:8088/alumnos/create');
    await new Promise(r => setTimeout(r, 600));

    // Modificar un campo
    await page.evaluate(`
        (() => {
            const el = document.getElementById('nombre');
            el.value = 'Modificación de prueba A4';
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        })()
    `);

    // Intentar hacer click en Cancelar (que es un enlace <a href="...">)
    page.lastDialog = null;
    await page.evaluate(`
        (() => {
            const cancelBtn = document.querySelector('.mobile-form-actions a');
            if (cancelBtn) cancelBtn.click();
        })()
    `);
    await new Promise(r => setTimeout(r, 400));
    const a4DialogRecibido = page.lastDialog;
    mediciones['regresion_a4_dialog_recibido'] = a4DialogRecibido;
    console.log('Regresión A4 diálogo recibido:', a4DialogRecibido);


    // =========================================================================
    // ESCRITORIO A 1280x900: LAS 8 VISTAS TOCADAS
    // =========================================================================
    console.log('--- Regresión Escritorio 1280x900: Verificando las 8 vistas ---');
    await page.setViewport(1280, 900, false);
    
    // Reloguear como ADMIN para tener acceso pleno a todas las rutas
    await page.send('Network.clearBrowserCookies');
    await page.navigate('http://127.0.0.1:8088/login');
    await new Promise(r => setTimeout(r, 600));
    await page.evaluate(`
        (() => {
            document.getElementById('email').value = 'admin@wings.com';
            document.getElementById('password').value = 'password123';
            document.querySelector('button[type="submit"]').click();
        })()
    `);
    await new Promise(r => setTimeout(r, 1500));

    const rutasEscritorio = [
        { nombre: 'alumnos_create', url: 'http://127.0.0.1:8088/alumnos/create', archivo: 'desktop-01-alumnos-create-1280.png' },
        { nombre: 'alumnos_edit', url: 'http://127.0.0.1:8088/alumnos/3/edit', archivo: 'desktop-02-alumnos-edit-1280.png' },
        { nombre: 'profesores_create', url: 'http://127.0.0.1:8088/profesores/create', archivo: 'desktop-03-profesores-create-1280.png' },
        { nombre: 'profesores_edit', url: 'http://127.0.0.1:8088/profesores/6/edit', archivo: 'desktop-04-profesores-edit-1280.png' },
        { nombre: 'caja_movimiento', url: 'http://127.0.0.1:8088/caja/movimiento', archivo: 'desktop-05-caja-movimiento-1280.png' },
        { nombre: 'grupos_index', url: 'http://127.0.0.1:8088/grupos', archivo: 'desktop-06-grupos-index-1280.png' },
        { nombre: 'caja_cobrar', url: 'http://127.0.0.1:8088/caja/cobrar', archivo: 'desktop-07-caja-cobrar-1280.png' }
    ];

    mediciones['escritorio_1280'] = {};

    for (const item of rutasEscritorio) {
        await page.navigate(item.url);
        await new Promise(r => setTimeout(r, 600));

        const comp = await page.evaluate(`
            (() => {
                const bar = document.querySelector('.mobile-form-actions');
                const summary = document.querySelector('.mobile-error-summary');
                const barStyle = bar ? window.getComputedStyle(bar) : null;
                const sumStyle = summary ? window.getComputedStyle(summary) : null;
                return {
                    barPresente: !!bar,
                    barPosition: barStyle ? barStyle.position : null,
                    barEsFixed: barStyle ? barStyle.position === 'fixed' : false,
                    summaryPresente: !!summary,
                    summaryDisplay: sumStyle ? sumStyle.display : null,
                    summaryEsVisible: sumStyle ? sumStyle.display !== 'none' : false,
                    docScrollWidth: document.documentElement.scrollWidth
                };
            })()
        `);
        mediciones['escritorio_1280'][item.nombre] = comp;
        console.log(`Escritorio 1280 [${item.nombre}]:`, comp);
        await page.captureScreenshot(path.join(capturasDir, item.archivo));
    }

    // Guardar JSON completo de mediciones
    fs.writeFileSync(
        path.join(__dirname, 'mediciones-verificacion.json'),
        JSON.stringify(mediciones, null, 2),
        'utf8'
    );
    console.log('Mediciones guardadas exitosamente en mediciones-verificacion.json');

    await page.close();
} catch (e) {
    console.error('Error durante la verificación:', e);
} finally {
    chrome.kill();
    console.log('Chrome finalizado.');
}
