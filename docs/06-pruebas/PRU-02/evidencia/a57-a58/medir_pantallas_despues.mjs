import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createBrowserTarget } from './cdp.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chromeProfile = path.join(__dirname, 'chrome-profile-despues');

const chrome = spawn(chromePath, [
    '--headless=new',
    '--remote-debugging-port=9222',
    `--user-data-dir=${chromeProfile}`,
    '--no-sandbox',
    '--disable-gpu',
    '--hide-scrollbars'
]);
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

const cssInyectar = `
    @media (max-width: 768px) {
        .filtros-row {
            flex-wrap: wrap !important;
        }
        .filtros-row > .filtros-select,
        .filtros-row > .filtros-control,
        .filtros-row > select,
        .filtros-row > input,
        .filtros-row > .search-input-group,
        .filtros-row > label,
        .filtros-row > div:not(.filtros-actions) {
            flex: 1 1 100% !important;
            min-width: 100% !important;
            width: 100% !important;
        }
        .filtros-row > label .filtros-control,
        .filtros-row > div:not(.filtros-actions) .filtros-control {
            width: 100% !important;
        }
    }
    @media (max-width: 640px) {
        .alumno-card .alumno-info {
            grid-template-columns: 1fr !important;
        }
        #clases-hoy-container {
            overflow-x: hidden !important;
        }
    }
`;

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

const resultados = [];

try {
    const page = await createBrowserTarget(9222);

    for (const [rol, pantallas] of Object.entries(pantallasPorRol)) {
        let creds = { email: 'admin@wings.com', pass: 'password123' };
        if (rol === 'OPERATIVO') creds = { email: 'operativo@wings.com', pass: 'password123' };
        if (rol === 'PROFESOR') creds = { email: 'profesor@wings.com', pass: 'password123' };

        await login(page, creds.email, creds.pass);

        for (const p of pantallas) {
            const url = `${baseUrl}${p.ruta}`;
            await page.setViewport(360, 740, true);
            await page.navigate(url);
            await new Promise(r => setTimeout(r, 300));

            // Inyectar el CSS de la solución
            await page.evaluate(`
                (() => {
                    const style = document.createElement('style');
                    style.id = 'propuesta-css';
                    style.textContent = \`${cssInyectar}\`;
                    document.head.appendChild(style);
                })()
            `);
            await new Promise(r => setTimeout(r, 200));

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
                                elementosSalidos.push({
                                    tag: el.tagName,
                                    right: Math.round(r.right),
                                    width: Math.round(r.width)
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
                        elementosSalidos,
                        filtros
                    };
                })()
            `);

            resultados.push({
                rol,
                nombre: p.nombre,
                ruta: p.ruta,
                med360
            });
        }
        await logout(page);
    }

    fs.writeFileSync(
        path.join(__dirname, 'mediciones-paso3.json'),
        JSON.stringify(resultados, null, 2),
        'utf8'
    );
    console.log('Medición paso 3 completada con éxito.');

} catch (e) {
    console.error(e);
} finally {
    try { chrome.kill(); } catch (e) {}
}
