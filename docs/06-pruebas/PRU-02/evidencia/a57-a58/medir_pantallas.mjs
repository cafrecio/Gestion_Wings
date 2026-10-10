import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createBrowserTarget } from './cdp.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..', '..', '..', '..', '..');
const capturasDir = path.join(__dirname, 'capturas-antes');

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

const baseUrl = 'http://127.0.0.1:8088';

const ids = JSON.parse(fs.readFileSync(path.join(__dirname, 'get_ids.php.json'), 'utf8').replace(/^\uFEFF/, '') || '{}');

const pantallasPorRol = {
    ADMIN: [
        { nombre: 'Login (Público)', ruta: '/login' },
        { nombre: 'Dashboard Admin', ruta: '/admin/dashboard' },
        { nombre: 'Cashflow', ruta: '/cashflow' },
        { nombre: 'Cashflow Movimiento', ruta: '/cashflow/movimiento' },
        { nombre: 'Caja Índice', ruta: '/caja' },
        { nombre: 'Caja Configuración Mostrador', ruta: '/caja/configuracion' },
        { nombre: 'Caja Apertura', ruta: '/caja/apertura' },
        { nombre: 'Caja Historial', ruta: '/caja/historial' },
        { nombre: 'Caja Movimiento', ruta: '/caja/movimiento' },
        { nombre: 'Caja Cobrar Selector', ruta: '/caja/cobrar' },
        { nombre: 'Caja Cobrar Alumno', ruta: `/caja/cobrar/${ids.alumnoId || 908}` },
        { nombre: 'Caja Resumen', ruta: `/caja/${ids.cajaId || 42}/resumen` },
        { nombre: 'Caja Detalle', ruta: `/caja/${ids.cajaId || 42}/detalle` },
        { nombre: 'Caja Editar Turno', ruta: `/caja/${ids.cajaId || 42}/editar` },
        { nombre: 'Caja Cierre', ruta: `/caja/${ids.cajaId || 42}/cerrar` },
        { nombre: 'Alumnos Índice', ruta: '/alumnos' },
        { nombre: 'Alumnos Alta', ruta: '/alumnos/create' },
        { nombre: 'Alumnos Edición', ruta: `/alumnos/${ids.alumnoId || 908}/edit` },
        { nombre: 'Alumnos Ficha', ruta: `/alumnos/${ids.alumnoId || 908}` },
        { nombre: 'Cobranza', ruta: '/cobranza' },
        { nombre: 'Revisión Cobranza', ruta: '/revision-cobranza' },
        { nombre: 'Movimientos', ruta: '/movimientos' },
        { nombre: 'Clases Índice', ruta: '/clases' },
        { nombre: 'Clases Alta', ruta: '/clases/create' },
        { nombre: 'Clases Edición', ruta: `/clases/${ids.claseId || 120}/edit` },
        { nombre: 'Clases Asistencia / Detalle', ruta: `/clases/${ids.claseId || 120}` },
        { nombre: 'Deportes Índice', ruta: '/deportes' },
        { nombre: 'Deportes Alta', ruta: '/deportes/create' },
        { nombre: 'Deportes Edición', ruta: `/deportes/${ids.deporteId || 226}/edit` },
        { nombre: 'Niveles Índice', ruta: '/niveles' },
        { nombre: 'Niveles Alta', ruta: '/niveles/create' },
        { nombre: 'Niveles Edición', ruta: `/niveles/${ids.nivelId || 291}/edit` },
        { nombre: 'Grupos Índice', ruta: '/grupos' },
        { nombre: 'Grupos Alta', ruta: '/grupos/create' },
        { nombre: 'Grupos Ficha', ruta: `/grupos/${ids.grupoId || 272}` },
        { nombre: 'Grupos Edición', ruta: `/grupos/${ids.grupoId || 272}/edit` },
        { nombre: 'Profesores Índice', ruta: '/profesores' },
        { nombre: 'Profesores Alta', ruta: '/profesores/create' },
        { nombre: 'Profesores Ficha', ruta: `/profesores/${ids.profesorId || 111}` },
        { nombre: 'Profesores Edición', ruta: `/profesores/${ids.profesorId || 111}/edit` },
        { nombre: 'Rubros Índice', ruta: '/rubros' },
        { nombre: 'Rubros Alta', ruta: '/rubros/create' },
        { nombre: 'Rubros Edición', ruta: `/rubros/${ids.rubroId || 1}/edit` },
        { nombre: 'Subrubros Alta', ruta: `/rubros/${ids.rubroId || 1}/subrubros/create` },
        { nombre: 'Subrubros Edición', ruta: `/rubros/${ids.rubroId || 1}/subrubros/${ids.subrubroId || 1}/edit` },
        { nombre: 'Tipos de Caja Índice', ruta: '/tipos-caja' },
        { nombre: 'Tipos de Caja Alta', ruta: '/tipos-caja/create' },
        { nombre: 'Tipos de Caja Edición', ruta: `/tipos-caja/${ids.tipoCajaId || 323}/edit` },
        { nombre: 'Usuarios Índice', ruta: '/usuarios' },
        { nombre: 'Usuarios Alta', ruta: '/usuarios/create' },
        { nombre: 'Usuarios Edición', ruta: `/usuarios/${ids.usuarioId || 315}/edit` },
        { nombre: 'Configuraciones', ruta: '/configuraciones' },
        { nombre: 'Liquidaciones Índice', ruta: '/liquidaciones' },
        { nombre: 'Liquidaciones Alta', ruta: '/liquidaciones/crear' },
        { nombre: 'Liquidaciones Detalle', ruta: `/liquidaciones/${ids.liquidacionId || 27}` },
        { nombre: 'Primera Carga', ruta: '/sistema/primera-carga' },
        { nombre: 'Reportes Índice', ruta: '/reportes' },
        { nombre: 'Reportes Alumnos', ruta: '/reportes/alumnos' },
        { nombre: 'Reportes Sueldos', ruta: '/reportes/sueldos' }
    ],
    OPERATIVO: [
        { nombre: 'Inicio Operativo', ruta: '/operativo' },
        { nombre: 'Caja Índice', ruta: '/caja' },
        { nombre: 'Caja Apertura', ruta: '/caja/apertura' },
        { nombre: 'Caja Historial', ruta: '/caja/historial' },
        { nombre: 'Caja Movimiento', ruta: '/caja/movimiento' },
        { nombre: 'Caja Cobrar Selector', ruta: '/caja/cobrar' },
        { nombre: 'Caja Cobrar Alumno', ruta: `/caja/cobrar/${ids.alumnoId || 908}` },
        { nombre: 'Caja Resumen', ruta: `/caja/${ids.cajaId || 42}/resumen` },
        { nombre: 'Caja Detalle', ruta: `/caja/${ids.cajaId || 42}/detalle` },
        { nombre: 'Caja Cierre', ruta: `/caja/${ids.cajaId || 42}/cerrar` },
        { nombre: 'Alumnos Índice', ruta: '/alumnos' },
        { nombre: 'Alumnos Alta', ruta: '/alumnos/create' },
        { nombre: 'Alumnos Edición', ruta: `/alumnos/${ids.alumnoId || 908}/edit` },
        { nombre: 'Alumnos Ficha', ruta: `/alumnos/${ids.alumnoId || 908}` },
        { nombre: 'Cobranza', ruta: '/cobranza' },
        { nombre: 'Revisión Cobranza', ruta: '/revision-cobranza' },
        { nombre: 'Movimientos', ruta: '/movimientos' },
        { nombre: 'Clases Índice', ruta: '/clases' },
        { nombre: 'Clases Asistencia / Detalle', ruta: `/clases/${ids.claseId || 120}` },
        { nombre: 'Grupos Índice', ruta: '/grupos' },
        { nombre: 'Grupos Ficha', ruta: `/grupos/${ids.grupoId || 272}` }
    ],
    PROFESOR: [
        { nombre: 'Clases Índice', ruta: '/clases' },
        { nombre: 'Clases Asistencia / Ficha', ruta: `/clases/${ids.claseId || 120}` }
    ]
};

async function login(page, email, password) {
    await page.navigate(`${baseUrl}/login`);
    await page.evaluate(`
        (() => {
            const em = document.getElementById('email') || document.querySelector('input[type="email"]');
            const pw = document.getElementById('password') || document.querySelector('input[type="password"]');
            if (em) em.value = '${email}';
            if (pw) pw.value = '${password}';
            const btn = document.querySelector('button[type="submit"]') || document.querySelector('input[type="submit"]');
            if (btn) btn.click();
        })()
    `);
    await new Promise(r => setTimeout(r, 1500));
}

async function logout(page) {
    await page.navigate(`${baseUrl}/logout`);
    await new Promise(r => setTimeout(r, 500));
}

const resultadosMedicion = [];

try {
    const page = await createBrowserTarget(9222);

    for (const [rol, pantallas] of Object.entries(pantallasPorRol)) {
        console.log(`\n================== ROL: ${rol} ==================`);
        
        let creds = { email: 'admin@wings.com', pass: 'password123' };
        if (rol === 'OPERATIVO') creds = { email: 'operativo@wings.com', pass: 'password123' };
        if (rol === 'PROFESOR') creds = { email: 'profesor@wings.com', pass: 'password123' };

        await login(page, creds.email, creds.pass);

        for (const p of pantallas) {
            const url = `${baseUrl}${p.ruta}`;
            console.log(`Midiendo: [${rol}] ${p.nombre} (${p.ruta})...`);
            
            // Medir en 360 x 740 (ancho de control principal de Carlos)
            await page.setViewport(360, 740, true);
            await page.navigate(url);
            await new Promise(r => setTimeout(r, 600));

            const med360 = await page.evaluate(`
                (() => {
                    const w = window.innerWidth;
                    const docEl = document.documentElement;
                    const body = document.body;
                    const scrollW = Math.max(docEl.scrollWidth, body ? body.scrollWidth : 0);
                    const seDesliza = scrollW > (w + 1);

                    let elementosSalidos = [];
                    if (seDesliza) {
                        const all = document.querySelectorAll('*');
                        for (const el of all) {
                            if (el === docEl || el === body) continue;
                            const s = window.getComputedStyle(el);
                            if (s.display === 'none' || s.visibility === 'hidden' || s.opacity === '0') continue;
                            
                            // Verificar si está dentro de un contenedor con scroll propio (tablas Rubros, Movimientos, Historial)
                            let enScrollPropio = false;
                            let cur = el.parentElement;
                            while (cur && cur !== body && cur !== docEl) {
                                const cs = window.getComputedStyle(cur);
                                if (cs.overflowX === 'auto' || cs.overflowX === 'scroll') {
                                    enScrollPropio = true;
                                    break;
                                }
                                cur = cur.parentElement;
                            }
                            if (enScrollPropio) continue;

                            const r = el.getBoundingClientRect();
                            if (r.right > (w + 1) && r.width > 0 && r.height > 0) {
                                let sel = el.tagName.toLowerCase();
                                if (el.id) sel += '#' + el.id;
                                else if (el.className && typeof el.className === 'string') {
                                    const c = el.className.trim().split(/\\s+/).filter(x => !x.includes(':')).slice(0, 2).join('.');
                                    if (c) sel += '.' + c;
                                }
                                elementosSalidos.push({
                                    selector: sel,
                                    right: Math.round(r.right),
                                    width: Math.round(r.width),
                                    tag: el.tagName
                                });
                            }
                        }
                    }

                    // Filtros
                    const rows = document.querySelectorAll('.filtros-row');
                    const filtros = [];
                    rows.forEach((row, i) => {
                        const controls = Array.from(row.querySelectorAll('.filtros-control, .filtros-select, select, input:not([type="hidden"]):not([type="submit"]), .search-input-group'));
                        const campos = Array.from(new Set(controls)).filter(c => !c.closest('.filtros-actions') && !c.matches('button, input[type="submit"], a'));
                        if (campos.length > 0) {
                            const anchos = campos.map(c => {
                                const r = c.getBoundingClientRect();
                                return {
                                    name: c.name || c.id || c.className || c.tagName,
                                    type: c.type || c.tagName.toLowerCase(),
                                    width: Math.round(r.width * 10) / 10
                                };
                            });
                            const min = Math.min(...anchos.map(a => a.width));
                            const max = Math.max(...anchos.map(a => a.width));
                            filtros.push({
                                index: i,
                                parejos: (max - min) <= 2,
                                diferencia: Math.round((max - min) * 10) / 10,
                                campos: anchos
                            });
                        }
                    });

                    return {
                        scrollWidth: scrollW,
                        innerWidth: w,
                        seDesliza,
                        elementosSalidos: elementosSalidos.slice(0, 5),
                        filtros
                    };
                })()
            `);

            // Medir también a 375 y 320
            await page.setViewport(375, 667, true);
            await new Promise(r => setTimeout(r, 200));
            const med375 = await page.evaluate(`
                (() => {
                    const w = window.innerWidth;
                    const docEl = document.documentElement;
                    const body = document.body;
                    const scrollW = Math.max(docEl.scrollWidth, body ? body.scrollWidth : 0);
                    return { scrollWidth: scrollW, innerWidth: w, seDesliza: scrollW > (w + 1) };
                })()
            `);

            await page.setViewport(320, 568, true);
            await new Promise(r => setTimeout(r, 200));
            const med320 = await page.evaluate(`
                (() => {
                    const w = window.innerWidth;
                    const docEl = document.documentElement;
                    const body = document.body;
                    const scrollW = Math.max(docEl.scrollWidth, body ? body.scrollWidth : 0);
                    return { scrollWidth: scrollW, innerWidth: w, seDesliza: scrollW > (w + 1) };
                })()
            `);

            // Captura de pantalla a 360 si hay deslizamiento o filtros dispares, o en Caja y Clases
            let capPath = null;
            if (med360.seDesliza || med360.filtros.some(f => !f.parejos) || p.ruta === '/caja' || p.ruta === '/clases') {
                await page.setViewport(360, 740, true);
                const safeName = `${rol.toLowerCase()}-${p.nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-')}-360.png`;
                capPath = path.join(capturasDir, safeName);
                await page.captureScreenshot(capPath);
            }

            resultadosMedicion.push({
                rol,
                nombre: p.nombre,
                ruta: p.ruta,
                med360,
                med375,
                med320,
                captura: capPath ? path.basename(capPath) : null
            });
        }

        await logout(page);
    }

    fs.writeFileSync(
        path.join(__dirname, 'mediciones-paso1.json'),
        JSON.stringify(resultadosMedicion, null, 2),
        'utf8'
    );
    console.log('\nMediciones guardadas en docs/06-pruebas/PRU-02/evidencia/a57-a58/mediciones-paso1.json');

} catch (err) {
    console.error('Error durante la medición:', err);
} finally {
    try { chrome.kill(); } catch (e) {}
}
