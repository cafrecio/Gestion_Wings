// T80: con la política de contenido en modo bloqueo, recorre en Chrome real todas las pantallas de cada rol
// y junta cada bloqueo del navegador, error de JavaScript y respuesta de error.
// Uso: node docs/06-pruebas/PRU-04/evidencia-t80-claude/recorrer-local.mjs   (servidor local en 8738)
import fs from 'node:fs';
import path from 'node:path';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const base = 'http://127.0.0.1:8738';
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t80-claude');
const d = JSON.parse(fs.readFileSync('storage/app/t80-local.json', 'utf8'));
const i = d.ids;
const pausa = (ms = 700) => new Promise(r => setTimeout(r, ms));
const rutas = {
    ADMIN: ['/admin/dashboard', '/alumnos', `/alumnos/${i.alumno}`, `/alumnos/${i.alumno}/edit`, '/alumnos/create', '/cobranza', '/caja',
        `/caja/${i.caja}/detalle`, '/cashflow', '/cashflow/movimiento', '/cashflow/transferencia', '/reportes', '/reportes/alumnos', '/reportes/sueldos',
        '/clases', `/clases/${i.clase}`, '/clases/create', `/clases/${i.clase}/edit`, '/liquidaciones', '/liquidaciones/crear', '/movimientos',
        '/configuraciones', '/rubros', '/rubros/create', `/rubros/${i.rubro}/edit`, '/grupos', `/grupos/${i.grupo}`, `/grupos/${i.grupo}/edit`, '/grupos/create',
        '/profesores', `/profesores/${i.profesor}`, `/profesores/${i.profesor}/edit`, '/profesores/create', '/deportes', `/deportes/${i.deporte}/edit`,
        '/niveles', '/usuarios', '/usuarios/create', `/usuarios/${i.usuario}/edit`, '/tipos-caja', `/tipos-caja/${i.medio}/edit`, '/revision-cobranza'],  // el recibo es un PDF: no es una página
    OPERATIVO: ['/operativo', '/caja', '/caja/cobrar', `/caja/cobrar/${i.alumno}`, '/caja/movimiento', '/alumnos', `/alumnos/${i.alumno}`, '/alumnos/create',
        '/cobranza', '/clases', `/clases/${i.clase}`, '/movimientos', '/revision-cobranza', '/grupos'],
    PROFESOR: ['/clases', `/clases/${i.clase}`],
};
// Se inyecta antes de cada página (CDP la ejecuta aunque la política bloquee el resto).
const espia = `window.__bloqueos=[];document.addEventListener('securitypolicyviolation',e=>window.__bloqueos.push({directiva:e.violatedDirective,bloqueado:e.blockedURI,linea:e.lineNumber,muestra:e.sample}));`;
const resultado = { fecha: new Date().toISOString(), base: 'wings_testing_claude', politica: null, paginas: [] };

async function recorrer(rol) {
    const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t80-${rol}-${Date.now()}`) });
    let page;
    try {
        await browser.start();
        page = await browser.newPage();
        await page.connect();
        await page.send('Page.enable');
        await page.send('Runtime.enable');
        await page.send('Page.addScriptToEvaluateOnNewDocument', { source: espia });
        let errores = [];
        page.on('Runtime.exceptionThrown', p => errores.push(p.exceptionDetails.exception?.description || p.exceptionDetails.text));
        page.on('Runtime.consoleAPICalled', p => { if (p.type === 'error') errores.push(p.args.map(a => a.value ?? a.description).join(' ')); });
        await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 900, deviceScaleFactor: 1, mobile: false });
        await page.goto(`${base}/login`);
        const loginBloqueos = await page.evaluate('window.__bloqueos');
        if (rol === 'ADMIN') resultado.paginas.push({ rol: 'SIN SESION', url: '/login', bloqueos: loginBloqueos, errores: [...errores] });
        await page.type('input[name=email]', d[rol].email);
        await page.type('input[name=password]', d[rol].password);
        await page.click('button[type=submit]');
        await pausa(1200);
        for (const url of rutas[rol]) {
            errores = [];
            await page.goto(base + url);
            await pausa();
            const info = await page.evaluate(`({final: location.pathname, titulo: document.title, bloqueos: window.__bloqueos || [], scriptsExternos: document.querySelectorAll('script[src]').length})`);
            resultado.paginas.push({ rol, url, final: info.final, titulo: info.titulo, bloqueos: info.bloqueos, errores });
        }
        if (!resultado.politica) {
            const r = await fetch(`${base}/login`); resultado.politica = r.headers.get('content-security-policy');
        }
    } finally {
        if (page) await page.close();
        await browser.close();
    }
}

for (const rol of ['ADMIN', 'OPERATIVO', 'PROFESOR']) await recorrer(rol);
const conBloqueo = resultado.paginas.filter(p => p.bloqueos.length);
const conError = resultado.paginas.filter(p => p.errores.length);
resultado.resumen = { paginas: resultado.paginas.length, con_bloqueo: conBloqueo.length, con_error_js: conError.length };
fs.writeFileSync(path.join(salida, 'recorrido.json'), JSON.stringify(resultado, null, 2) + '\n');
console.log(JSON.stringify(resultado.resumen));
for (const p of [...conBloqueo, ...conError]) console.log(p.rol, p.url, JSON.stringify(p.bloqueos).slice(0, 300), JSON.stringify(p.errores).slice(0, 300));
