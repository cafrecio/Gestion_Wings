import path from 'node:path';
import fs from 'node:fs';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const BASE = 'http://127.0.0.1:8128';
const evidenciaDir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t117-t118');
fs.mkdirSync(evidenciaDir, { recursive: true });

const esperar = ms => new Promise(r => setTimeout(r, ms));
const nav = new ChromeBrowser({ userDataDir: path.resolve('storage/app/chrome-t117-config-test') });
await nav.start();
const p = await nav.newPage();
await p.connect();
await p.send('Network.enable');

const dialogos = [];
p.on('Page.javascriptDialogOpening', async d => {
    dialogos.push({ type: d.type, message: d.message });
    console.log(`[DIALOGO ABIERTO] tipo=${d.type}`);
    await p.send('Page.handleJavaScriptDialog', { accept: true });
});

const ev = e => p.evaluate(e);

const clic = async sel => {
    await ev(`document.querySelector(${JSON.stringify(sel)}).scrollIntoView({block:'center', behavior:'instant'})`);
    await esperar(300);
    const r = JSON.parse(await ev(`(() => { const q=document.querySelector(${JSON.stringify(sel)}).getBoundingClientRect(); return JSON.stringify({x:q.x+q.width/2,y:q.y+q.height/2}); })()`));
    await p.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: r.x, y: r.y });
    for (const type of ['mousePressed', 'mouseReleased']) {
        await p.send('Input.dispatchMouseEvent', { type, x: r.x, y: r.y, button: 'left', clickCount: 1 });
    }
};

async function capturar(nombre) {
    const { data } = await p.send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(evidenciaDir, nombre), Buffer.from(data, 'base64'));
}

async function loginAdmin() {
    await p.send('Network.clearBrowserCookies');
    await p.goto(`${BASE}/login`);
    await esperar(400);
    await ev(`(() => {
        document.querySelector('[name=email]').value = 'admin@wings.test';
        document.querySelector('[name=password]').value = 'password';
        document.querySelector('button[type=submit]').click();
    })()`);
    for (let i = 0; i < 40 && await ev('location.pathname').catch(() => '/login') === '/login'; i++) {
        await esperar(200);
    }
}

async function borrarCookiesSesion() {
    const { cookies } = await p.send('Network.getCookies');
    for (const c of cookies) {
        if (/session/.test(c.name)) {
            await p.send('Network.deleteCookies', { name: c.name, url: BASE });
        }
    }
    await esperar(200);
}

const resultados = [];

// =========================================================================
// Caso 1: Configuración con campo cambiado y sesión vencida -> directo a login
// =========================================================================
console.log('--- Probando Caso 1: Guardar configuración con sesión vencida ---');
await loginAdmin();
await p.goto(`${BASE}/configuraciones`);
await esperar(600);
dialogos.length = 0;

// Escribir un valor nuevo con teclado real en el primer formulario
await ev(`document.querySelector('.configuracion-form').dataset.marca = '1'`);
await clic('.configuracion-form[data-marca] [name=valor]');
await esperar(200);
await ev(`(() => { const c = document.querySelector('.configuracion-form[data-marca]').elements.valor; c.select(); })()`);
await p.send('Input.insertText', { text: '99999' });
await esperar(200);

// Borrar sesión (simulando vencimiento)
await borrarCookiesSesion();

// Clic de mouse real sobre Guardar
await clic('.configuracion-form[data-marca] button[type=submit]');
await esperar(2500);

const url1 = await ev('location.pathname');
const texto1 = await ev('document.body.innerText');
const tieneAviso1 = texto1.includes('Tu sesión expiró. Iniciá sesión nuevamente.');
const huboDialogo1 = dialogos.some(d => d.type === 'beforeunload');

console.log('URL final:', url1);
console.log('Hubo diálogo beforeunload:', huboDialogo1);
console.log('Tiene aviso flash:', tieneAviso1);

await capturar('07-t117-configuracion-sesion-vencida-login.png');

const ok1 = url1 === '/login' && tieneAviso1 && !huboDialogo1;
resultados.push({
    prueba: 'Guardar configuración con sesión vencida va directo a login sin diálogo de salida',
    ok: ok1,
    url: url1,
    huboDialogoBeforeUnload: huboDialogo1,
    tieneAviso: tieneAviso1
});

// =========================================================================
// Caso 2: Guardar reglas con sesión vencida -> directo a login
// =========================================================================
console.log('--- Probando Caso 2: Guardar reglas con sesión vencida ---');
await loginAdmin();
await p.goto(`${BASE}/configuraciones`);
await esperar(600);
dialogos.length = 0;

// Abrir panel para agregar regla
await clic('#btn-agregar-regla');
await esperar(300);

// Llenar campos de la regla
await clic('#add-nombre');
await p.send('Input.insertText', { text: 'Regla Test 419' });
await clic('#add-dia-desde');
await p.send('Input.insertText', { text: '1' });
await clic('#add-dia-hasta');
await p.send('Input.insertText', { text: '10' });
await clic('#add-porcentaje');
await p.send('Input.insertText', { text: '100' });
await esperar(200);

// Borrar sesión
await borrarCookiesSesion();

// Clic sobre Guardar regla
await clic('#btn-guardar-add');
await esperar(2500);

const url2 = await ev('location.pathname');
const texto2 = await ev('document.body.innerText');
const tieneAviso2 = texto2.includes('Tu sesión expiró. Iniciá sesión nuevamente.');
const huboDialogo2 = dialogos.some(d => d.type === 'beforeunload');

console.log('URL final:', url2);
console.log('Hubo diálogo beforeunload:', huboDialogo2);
console.log('Tiene aviso flash:', tieneAviso2);

await capturar('08-t117-reglas-sesion-vencida-login.png');

const ok2 = url2 === '/login' && tieneAviso2 && !huboDialogo2;
resultados.push({
    prueba: 'Guardar reglas con sesión vencida va directo a login sin diálogo de salida',
    ok: ok2,
    url: url2,
    huboDialogoBeforeUnload: huboDialogo2,
    tieneAviso: tieneAviso2
});

// =========================================================================
// Caso 3: Sesión VIVA, campo cambiado, intentar salir -> Sí dispara beforeunload
// =========================================================================
console.log('--- Probando Caso 3: Sesión viva con cambios sin guardar sí pregunta al salir ---');
await loginAdmin();
await p.goto(`${BASE}/configuraciones`);
await esperar(600);
dialogos.length = 0;

await ev(`document.querySelector('.configuracion-form').dataset.marca = '2'`);
await clic('.configuracion-form[data-marca] [name=valor]');
await esperar(200);
await ev(`(() => { const c = document.querySelector('.configuracion-form[data-marca]').elements.valor; c.select(); })()`);
await p.send('Input.insertText', { text: '88888' });
await esperar(200);

// Intentar navegar con sesión viva (clic en menú Alumnos)
await clic('a.ds-nav-link[href*="alumnos"]');
await esperar(1000);

const huboDialogo3 = dialogos.some(d => d.type === 'beforeunload');
console.log('Hubo diálogo beforeunload con sesión viva:', huboDialogo3);

const ok3 = huboDialogo3;
resultados.push({
    prueba: 'Salir sin guardar con sesión viva sigue preguntando',
    ok: ok3,
    huboDialogoBeforeUnload: huboDialogo3
});

fs.writeFileSync(
    path.join(evidenciaDir, 'resultados-segunda-vuelta.json'),
    JSON.stringify(resultados, null, 2),
    'utf8'
);

console.log('Resultados guardados:', resultados);
await p.close();
await nav.close();
