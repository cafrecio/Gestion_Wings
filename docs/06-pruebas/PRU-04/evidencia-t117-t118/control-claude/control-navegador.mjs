// Control de T117 por Claude CAB: lo que el script de Gemini no ejecutó.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { ChromeBrowser } from './scripts/pruebas/cdp-helper.mjs';

const BASE = 'http://127.0.0.1:8127';
const DIR = path.resolve('_capturas');
fs.mkdirSync(DIR, { recursive: true });
const esperar = ms => new Promise(r => setTimeout(r, ms));
const sql = q => execFileSync('php', ['_q.php', q], { encoding: 'utf8' });
const salida = [];
const anotar = (caso, datos) => { salida.push({ caso, ...datos }); console.log(caso, JSON.stringify(datos)); };

const nav = new ChromeBrowser({ userDataDir: path.resolve('_chrome') });
await nav.start();
const p = await nav.newPage();
await p.connect();
await p.send('Network.enable');

let dialogoResponde = true; const dialogos = [];
p.on('Page.javascriptDialogOpening', async d => {
    dialogos.push({ tipo: d.type, mensaje: d.message });
    await p.send('Page.handleJavaScriptDialog', { accept: dialogoResponde });
});

const ev = e => p.evaluate(e);
const url = () => ev('location.pathname');
const texto = () => ev('document.body.innerText');
const sinCookie = async () => {
    const { cookies } = await p.send('Network.getCookies');
    for (const c of cookies) if (/session/.test(c.name)) await p.send('Network.deleteCookies', { name: c.name, url: BASE });
    await esperar(150);
};
const entrar = async email => {
    execFileSync('php', ['artisan', 'cache:clear'], { encoding: 'utf8' });
    await p.send('Network.clearBrowserCookies');
    await p.goto(`${BASE}/login`); await esperar(400);
    await ev(`(() => { document.querySelector('[name=email]').value=${JSON.stringify(email)}; document.querySelector('[name=password]').value='password'; document.querySelector('button[type=submit]').click(); })()`);
    for (let i = 0; i < 40; i++) {
        await esperar(300);
        const u = await ev('location.pathname').catch(() => '/login');
        if (u !== '/login') { await esperar(300); return; }
    }
    throw new Error('No entró: ' + email);
};
// Clic real del mouse: Chrome solo abre el aviso de salida si hubo gesto del usuario.
const clicReal = async selector => {
    await ev(`document.querySelector(${JSON.stringify(selector)}).scrollIntoView({block:'center', behavior:'instant'})`);
    await esperar(900);
    const r = JSON.parse(await ev(`(() => { const b=document.querySelector(${JSON.stringify(selector)}); const q=b.getBoundingClientRect(); const x=q.x+q.width/2, y=q.y+q.height/2; return JSON.stringify({x, y, acierta: document.elementFromPoint(x,y)===b}); })()`));
    console.log('clic', JSON.stringify(r));
    await p.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: r.x, y: r.y });
    for (const type of ['mousePressed', 'mouseReleased']) await p.send('Input.dispatchMouseEvent', { type, x: r.x, y: r.y, button: 'left', clickCount: 1 });
};
const foto = async n => { const s = await p.send('Page.captureScreenshot'); fs.writeFileSync(path.join(DIR, n + '.png'), Buffer.from(s.data, 'base64')); };
const aviso = t => ['Tu sesión expiró. Iniciá sesión nuevamente.', 'Tu cuenta fue desactivada.', 'CSRF token mismatch', 'Error de conexión', 'Unauthenticated'].filter(a => t.includes(a));

try {
    // A. Cuenta desactivada, Guardar lista. Gemini afirma que se ve el aviso; no lo ejecutó.
    await entrar('operativo@wings.test');
    await p.goto(`${BASE}/clases/1`); await esperar(600);
    await ev(`document.querySelector('.presente-checkbox').click()`);
    sql(`UPDATE users SET activo=0 WHERE email='operativo@wings.test'`);
    await ev(`document.getElementById('btn-guardar-asistencias').click()`);
    await esperar(2500);
    anotar('A cuenta desactivada + Guardar lista', { url: await url(), avisos: aviso(await texto()), asistencias: sql('SELECT COUNT(*) n FROM asistencias') });
    await foto('A-desactivada-guardar-lista');
    sql(`UPDATE users SET activo=1 WHERE email='operativo@wings.test'`);

    // B. Cuenta desactivada, Cobrar.
    await entrar('operativo@wings.test');
    await p.goto(`${BASE}/caja/cobrar/1`); await esperar(700);
    await ev(`(() => { const c=document.querySelector('input[name="periodos[]"]'); if(c&&!c.checked) c.click(); const s=document.getElementById('tipo_caja_id'); if(s&&s.options.length>1){s.selectedIndex=1; s.dispatchEvent(new Event('change',{bubbles:true}));} })()`);
    await esperar(300);
    sql(`UPDATE users SET activo=0 WHERE email='operativo@wings.test'`);
    await ev(`document.getElementById('btn-cobrar').click()`);
    await esperar(2500);
    anotar('B cuenta desactivada + Cobrar', { url: await url(), avisos: aviso(await texto()), pagos: sql('SELECT COUNT(*) n FROM pagos') });
    await foto('B-desactivada-cobrar');
    sql(`UPDATE users SET activo=1 WHERE email='operativo@wings.test'`);

    // B2. Desactivada y va por un formulario común: referencia de cómo se ve el aviso.
    await entrar('operativo@wings.test');
    await p.goto(`${BASE}/operativo`); await esperar(500);
    sql(`UPDATE users SET activo=0 WHERE email='operativo@wings.test'`);
    await p.goto(`${BASE}/alumnos`); await esperar(800);
    anotar('B2 cuenta desactivada + navegar', { url: await url(), avisos: aviso(await texto()) });
    sql(`UPDATE users SET activo=1 WHERE email='operativo@wings.test'`);

    // C. Vencimiento de verdad: la cookie sigue y la fila de sesión ya no está.
    await entrar('operativo@wings.test');
    await p.goto(`${BASE}/caja/cobrar/1`); await esperar(700);
    await ev(`(() => { const c=document.querySelector('input[name="periodos[]"]'); if(c&&!c.checked) c.click(); const s=document.getElementById('tipo_caja_id'); if(s&&s.options.length>1){s.selectedIndex=1; s.dispatchEvent(new Event('change',{bubbles:true}));} })()`);
    await esperar(300);
    const movAntes = sql('SELECT COUNT(*) n FROM movimientos_operativos');
    sql('DELETE FROM sessions');
    await ev(`document.getElementById('btn-cobrar').click()`);
    await esperar(2500);
    anotar('C vencida con cookie + Cobrar', { url: await url(), avisos: aviso(await texto()), pagos: sql('SELECT COUNT(*) n FROM pagos'), movAntes, movDespues: sql('SELECT COUNT(*) n FROM movimientos_operativos') });

    // D. Guardar lista en escritorio.
    await entrar('operativo@wings.test');
    await p.goto(`${BASE}/clases/1`); await esperar(600);
    await ev(`document.querySelector('.presente-checkbox').click()`);
    await sinCookie();
    await ev(`document.getElementById('btn-guardar-asistencias').click()`);
    await esperar(2000);
    anotar('D vencida + Guardar lista escritorio', { url: await url(), avisos: aviso(await texto()), asistencias: sql('SELECT COUNT(*) n FROM asistencias') });

    // E. Cambiar profesoras.
    await entrar('admin@wings.test');
    await p.goto(`${BASE}/clases/1`); await esperar(600);
    const hayProfs = await ev(`!!document.getElementById('btn-guardar-profesores')`);
    if (hayProfs) {
        const antes = sql('SELECT COUNT(*) n FROM clase_profesor');
        await ev(`document.getElementById('btn-modificar-profesores')?.click()`);
        await sinCookie();
        await ev(`document.getElementById('btn-guardar-profesores').click()`);
        await esperar(2000);
        anotar('E vencida + Cambiar profesoras', { url: await url(), avisos: aviso(await texto()), antes, despues: sql('SELECT COUNT(*) n FROM clase_profesor') });
    } else anotar('E vencida + Cambiar profesoras', { sinBoton: true });

    // F. Estado de profesora.
    await entrar('admin@wings.test');
    await p.goto(`${BASE}/profesores`); await esperar(600);
    const profesorActivo = sql('SELECT activo FROM profesores LIMIT 1');
    await sinCookie();
    await ev(`document.querySelector('[data-profesor-estado-url] input').click()`);
    await esperar(2000);
    anotar('F vencida + Estado de profesora', { url: await url(), avisos: aviso(await texto()), profesorActivo, despues: sql('SELECT activo FROM profesores LIMIT 1') });

    // G. Configuración, con el aviso de salida porque el campo tiene un cambio.
    for (const responde of [false, true]) {
        dialogoResponde = responde; dialogos.length = 0;
        await entrar('admin@wings.test');
        await p.goto(`${BASE}/configuraciones`); await esperar(800);
        const r = JSON.parse(await ev(`(() => { const f=document.querySelector('.configuracion-form'); const c=f.elements.valor; c.focus(); c.value=(c.value==='1'?'2':'1'); c.dispatchEvent(new Event('input',{bubbles:true})); f.dataset.marca='1'; return JSON.stringify({clave:f.dataset.clave, valor:c.value}); })()`));
        await sinCookie();
        await clicReal('.configuracion-form[data-marca] button[type=submit]');
        await esperar(2500);
        anotar(`G vencida + Configuración, aviso de salida ${responde ? 'acepta' : 'cancela'}`, {
            campo: r.clave, url: await url(), dialogos: [...dialogos], avisos: aviso(await texto()),
            trabado: await ev(`(() => { const f=document.querySelector('.configuracion-form[data-marca]'); return f ? JSON.stringify({boton:f.querySelector('button[type=submit]').disabled, campo:f.elements.valor.disabled}) : 'sin formulario'; })()`),
            resumen: await ev(`document.getElementById('configuracion-error-resumen')?.hidden ?? 'sin resumen'`),
        });
        await foto(`G-config-${responde ? 'acepta' : 'cancela'}`);
    }
    dialogoResponde = true;

    // H. Búsqueda de alumnos (GET sin encabezados) con la sesión vencida.
    await entrar('operativo@wings.test');
    await p.goto(`${BASE}/alumnos`); await esperar(700);
    await sinCookie();
    const tipo = await ev(`(async () => { const r = await fetch('/alumnos/autocomplete?q=lu').catch(e => ({err:String(e)})); return JSON.stringify({status:r.status, redirected:r.redirected, err:r.err}); })()`).catch(e => 'navegó: ' + e.message);
    await esperar(1500);
    anotar('H vencida + GET sin encabezados', { tipo, url: await url(), avisos: aviso(await texto()) });

    // I. Lo que pide con Accept JSON por GET: responde 401, el interceptor no lo mira.
    await entrar('admin@wings.test');
    await p.goto(`${BASE}/alumnos`); await esperar(700);
    await sinCookie();
    const g401 = await ev(`(async () => { const r = await fetch('/alumnos/inscripcion-preview?dni=1',{headers:{Accept:'application/json'}}); return JSON.stringify({status:r.status, cuerpo:(await r.text()).slice(0,120)}); })()`);
    anotar('I vencida + GET con Accept JSON', { g401, url: await url() });

    // J. Reingreso de profesora con su inicio.
    await entrar('profesor@wings.test');
    anotar('J reingreso de profesora', { url: await url() });
} catch (e) {
    anotar('ERROR', { mensaje: e.message, stack: e.stack });
} finally {
    fs.writeFileSync('_control.json', JSON.stringify(salida, null, 2));
    await p.close();
    await nav.close();
}
