// PRU-04 · Prueba técnica de los días 48 a 57 (Claude, 01/11/2026).
// Lo que Codex no pudo acreditar por pantalla, en un Chrome real contra una copia local de la base del sitio.
// Nunca contra el sitio: el servidor lo levanta quien corre esto, con DB_DATABASE apuntando a la copia.
// Uso: node navegador-dias-48-57.mjs <url> <clave-de-la-copia> <base-de-la-copia> <salida.json>
import { spawn, execFileSync } from 'child_process';
import fs from 'fs';
import os from 'os';
import path from 'path';

const [, , BASE, CLAVE, DB, SALIDA] = process.argv;
if (!/^http:\/\/127\.0\.0\.1:/.test(BASE) || !/^wings_t5_/.test(DB)) throw new Error('Solo una copia local wings_t5_*.');
const MYSQL = 'C:\\xampp\\mysql\\bin\\mysql.exe';
const sql = q => execFileSync(MYSQL, ['-uroot', DB, '--default-character-set=utf8mb4', '-N', '-e', q]).toString().trim();
const esperar = ms => new Promise(r => setTimeout(r, ms));
const resultado = [];
const anotar = (caso, ok, detalle) => { resultado.push({ caso, ok, detalle }); console.log((ok ? 'BIEN ' : 'MAL  ') + caso + ' :: ' + JSON.stringify(detalle)); };

const perfil = fs.mkdtempSync(path.join(os.tmpdir(), 'wings-t5-'));
const PUERTO = 9344;
const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', ['--headless=new', `--remote-debugging-port=${PUERTO}`, `--user-data-dir=${perfil}`, '--no-sandbox', '--disable-gpu', '--window-size=1280,900', 'about:blank']);
for (let i = 0; i < 30; i++) { try { await fetch(`http://127.0.0.1:${PUERTO}/json/version`); break; } catch { await esperar(300); } }
const version = await (await fetch(`http://127.0.0.1:${PUERTO}/json/version`)).json();

class Cdp {
    constructor(ws) { this.ws = ws; this.n = 1; this.pend = new Map(); this.oyentes = []; ws.onmessage = e => { const m = JSON.parse(e.data); if (m.id && this.pend.has(m.id)) { const [ok, mal] = this.pend.get(m.id); this.pend.delete(m.id); m.error ? mal(new Error(JSON.stringify(m.error))) : ok(m.result); } else if (m.method) this.oyentes.forEach(o => o(m)); }; }
    send(method, params = {}, sessionId) { return new Promise((ok, mal) => { const id = this.n++; this.pend.set(id, [ok, mal]); this.ws.send(JSON.stringify({ id, method, params, sessionId })); }); }
}
const ws = new WebSocket(version.webSocketDebuggerUrl);
await new Promise((ok, mal) => { ws.onopen = ok; ws.onerror = mal; });
const cdp = new Cdp(ws);

async function pestana() {
    const { browserContextId } = await cdp.send('Target.createBrowserContext');
    const { targetId } = await cdp.send('Target.createTarget', { url: 'about:blank', browserContextId });
    const { sessionId } = await cdp.send('Target.attachToTarget', { targetId, flatten: true });
    const s = (m, p) => cdp.send(m, p, sessionId);
    const dialogos = [];
    let responder = null;
    cdp.oyentes.push(m => {
        if (m.sessionId !== sessionId || m.method !== 'Page.javascriptDialogOpening') return;
        dialogos.push({ tipo: m.params.type, texto: m.params.message });
        if (responder !== null) s('Page.handleJavaScriptDialog', { accept: responder }).catch(() => {});
    });
    await s('Page.enable'); await s('Runtime.enable'); await s('Network.enable');
    const ev = async expr => { const r = await s('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true }); return r.result?.value; };
    const ir = async url => { await s('Page.navigate', { url: BASE + url }); await listo(); };
    const listo = async () => { for (let i = 0; i < 60; i++) { await esperar(150); if (await ev('document.readyState').catch(() => '') === 'complete') break; } await esperar(250); };
    const clic = async sel => {
        const r = await ev(`(()=>{const e=document.querySelector(${JSON.stringify(sel)}); if(!e) return null; e.scrollIntoView({block:'center'}); const b=e.getBoundingClientRect(); return {x:b.x+b.width/2,y:b.y+b.height/2,w:b.width};})()`);
        if (!r || !r.w) throw new Error('No se ve: ' + sel);
        for (const type of ['mousePressed', 'mouseReleased']) await s('Input.dispatchMouseEvent', { type, x: r.x, y: r.y, button: 'left', clickCount: 1 });
    };
    const escribir = async (sel, texto) => { await clic(sel); await s('Input.insertText', { text: texto }); };
    const salir = async email => {
        await ir('/login');
        await escribir('input[name="email"]', email); await escribir('input[name="password"]', CLAVE);
        await clic('button[type="submit"]'); await listo();
    };
    const sinSesion = async () => { const { cookies } = await s('Network.getCookies'); for (const c of cookies) if (/session/.test(c.name)) await s('Network.deleteCookies', { name: c.name, url: BASE }); return cookies.filter(c => /session/.test(c.name)).map(c => c.name); };
    return { s, ev, ir, listo, clic, escribir, entrar: salir, sinSesion, dialogos, responderCon: v => { responder = v; }, url: () => ev('location.pathname') };
}
const visible = `[...document.querySelectorAll('.ds-toast,[id^=toast],[role=alert],.text-red-600,.alert,[data-flash]')].map(e=>e.innerText.trim()).filter(Boolean).join(' | ')`;

try {
    // ALU-011 / 56.9: salir del alta con datos, por el menú y recargando.
    const rocio = await pestana();
    await rocio.entrar('rocio.paz@example.com');
    const antes = sql('SELECT COUNT(*) FROM alumnos');
    await rocio.ir('/alumnos/create');
    await rocio.escribir('#nombre', 'Prueba'); await rocio.escribir('#apellido', 'SinGuardar');
    const enlace = 'a.ds-nav-link[href$="/caja"], nav a[href$="/caja"]';
    rocio.responderCon(false);
    await rocio.clic(enlace); await esperar(700);
    const d1 = rocio.dialogos.at(-1);
    anotar('ALU-011 a: al tocar otra opción pregunta y Cancelar deja todo', d1?.tipo === 'confirm' && await rocio.url() === '/alumnos/create' && await rocio.ev(`document.querySelector('#apellido').value`) === 'SinGuardar', { dialogo: d1, url: await rocio.url() });
    rocio.responderCon(true);
    await rocio.clic(enlace); await rocio.listo();
    anotar('ALU-011 c: Aceptar sale y no guarda nada', await rocio.url() === '/caja' && sql('SELECT COUNT(*) FROM alumnos') === antes, { url: await rocio.url(), alumnos: [antes, sql('SELECT COUNT(*) FROM alumnos')] });
    rocio.dialogos.length = 0;
    await rocio.ir('/alumnos/create'); await rocio.clic(enlace); await rocio.listo();
    anotar('ALU-011 b: sin datos sale sin preguntar', rocio.dialogos.length === 0 && await rocio.url() === '/caja', { dialogos: rocio.dialogos.length, url: await rocio.url() });
    rocio.dialogos.length = 0;
    await rocio.ir('/alumnos/create'); await rocio.escribir('#nombre', 'Prueba'); rocio.responderCon(false);
    await rocio.s('Page.reload'); await esperar(900);
    anotar('ALU-011 a: recargar con datos también pregunta', rocio.dialogos.at(-1)?.tipo === 'beforeunload', { dialogo: rocio.dialogos.at(-1) });
    rocio.responderCon(true);
    await rocio.s('Page.handleJavaScriptDialog', { accept: true }).catch(() => {});

    // ACC-006 / 56.10: la sesión se vence con el Nuevo gasto a medio llenar (formulario común).
    const sandra = await pestana();
    await sandra.entrar('sandra.vidal@wings.test');
    await sandra.ir('/caja/apertura');
    await sandra.ev(`document.querySelector('input[name=confirmacion]').click()`);
    await sandra.clic('form[action$="/caja/apertura"] button[type=submit]'); await sandra.listo();
    const cajaSandra = sql(`SELECT COUNT(*) FROM cajas_operativas WHERE estado='ABIERTA'`);
    await sandra.ir('/caja/movimiento');
    await sandra.ev(`document.querySelector('#tipo-egreso').click()`);
    await sandra.ev(`(()=>{const t=document.querySelector('#tipo_caja_id'); t.value=[...t.options].find(o=>/Efectivo/.test(o.text)).value; t.dispatchEvent(new Event('change',{bubbles:true})); const r=document.querySelector('#rubro_id'); r.value=[...r.options].find(o=>o.value&&/Gastos/.test(o.text)).value; r.dispatchEvent(new Event('change',{bubbles:true})); const s=document.querySelector('#subrubro_id'); const o=[...s.options].find(o=>o.value&&!o.disabled&&!o.hidden&&o.dataset.rubro==r.value); s.value=o.value; s.dispatchEvent(new Event('change',{bubbles:true}));})()`);
    await sandra.escribir('#monto', '1500'); await sandra.escribir('#observaciones', 'Sesion vencida: no se guarda');
    const movAntes = sql('SELECT COUNT(*) FROM movimientos_operativos');
    const formulario = await sandra.ev(`(()=>{const f=document.querySelector('#mov-form'); return {valido:f.checkValidity(), invalidos:[...f.elements].filter(e=>!e.checkValidity()).map(e=>e.name), subrubro:document.querySelector('#subrubro_id').value, monto:document.querySelector('#monto').value, boton:!!document.querySelector('#mov-form button[type=submit]'), deshabilitado:document.querySelector('#mov-form button[type=submit]').disabled};})()`);
    await sandra.sinSesion();
    await sandra.clic('#mov-form button[type=submit]'); await esperar(600); await sandra.listo();
    const textoIngreso = await sandra.ev('document.body.innerText');
    anotar('ACC-006 a y b: Nuevo gasto con la sesión vencida va al ingreso con el aviso, sin guardar', await sandra.url() === '/login' && textoIngreso.includes('Tu sesión expiró. Iniciá sesión nuevamente.') && sql('SELECT COUNT(*) FROM movimientos_operativos') === movAntes, { url: await sandra.url(), aviso: textoIngreso.includes('Tu sesión expiró'), cajaAbierta: cajaSandra, formulario, pantalla: textoIngreso.slice(0, 200) });

    // La misma sesión vencida, en Cobrar: se envía por fetch.
    await sandra.entrar('sandra.vidal@wings.test');
    const pilar = sql(`SELECT id FROM alumnos WHERE nombre='Pilar' AND apellido='Ramos'`);
    await sandra.ir(`/caja/cobrar/${pilar}`);
    await sandra.ev(`(()=>{const m=document.querySelector('select[name=tipo_caja_id]'); if(m){m.value=[...m.options].find(o=>/Efectivo/.test(o.text)).value; m.dispatchEvent(new Event('change',{bubbles:true}));} [...document.querySelectorAll('input[type=checkbox]')].filter(c=>!c.checked&&c.offsetParent).slice(0,1).forEach(c=>c.click());})()`);
    await esperar(300);
    const pagosAntes = sql('SELECT COUNT(*) FROM pagos');
    await sandra.sinSesion();
    await sandra.ev(`document.querySelector('form[action*="/caja/cobrar/"]').requestSubmit()`); await esperar(1200);
    const textoCobro = await sandra.ev('document.body.innerText');
    const mensajeCobro = (textoCobro.match(/.*(CSRF|sesión expiró|No se pudo registrar).*/) || [''])[0];
    anotar('ACC-006: Cobrar con la sesión vencida dice en castellano qué pasó y lleva al ingreso', await sandra.url() === '/login' || /sesión/i.test(mensajeCobro), { url: await sandra.url(), mensaje: mensajeCobro, sinCobro: sql('SELECT COUNT(*) FROM pagos') === pagosAntes });

    // 56.3 (USU-008 b): el menú de Gonzalo después de pasar a operativa.
    const gonzalo = await pestana();
    await gonzalo.entrar('gonzalo.ibarra@example.com');
    const menu = await gonzalo.ev(`[...document.querySelectorAll('nav a')].map(a=>a.innerText.trim().split('\\n')[0]).filter(Boolean)`);
    anotar('USU-008 b: Gonzalo entra con el menú de operativa', menu.includes('Caja') && menu.includes('Alumnos') && !menu.includes('Cashflow'), { url: await gonzalo.url(), menu });

    // ASI-015 y ACC-006 / 50.6: la lista, con la sesión vencida y con la cuenta desactivada.
    const lucia = await pestana();
    await lucia.entrar('lucia.gaitan@wings.test');
    await lucia.ir('/clases/346');
    await lucia.ev(`[...document.querySelectorAll('.presente-checkbox')].forEach(c=>{ if(c.checked) c.click(); }); document.querySelector('.presente-checkbox').click();`);
    await lucia.sinSesion();
    await lucia.ev(`window.__t=[]; new MutationObserver(()=>{const t=document.getElementById('toast-asistencias'); if(t&&t.innerText.trim()) window.__t.push(t.innerText.trim());}).observe(document.body,{subtree:true,childList:true,characterData:true,attributes:true});`);
    await lucia.clic('#btn-guardar-asistencias'); await esperar(1500);
    const toast = await lucia.ev(`window.__t[window.__t.length-1]||''`);
    anotar('ACC-006 / 50.6: guardar la lista con la sesión vencida avisa en castellano y lleva al ingreso', await lucia.url() === '/login' || /sesi[oó]n/i.test(toast), { url: await lucia.url(), avisoDePantalla: toast, marcasGuardadas: sql('SELECT COUNT(*) FROM asistencias WHERE clase_id=346') });

    await lucia.entrar('lucia.gaitan@wings.test');
    await lucia.ir('/clases/346');
    await lucia.ev(`document.querySelector('.presente-checkbox').click();`);
    sql(`UPDATE users SET activo=0 WHERE email='lucia.gaitan@wings.test'`);
    await lucia.ev(`window.__t=[]; new MutationObserver(()=>{const t=document.getElementById('toast-asistencias'); if(t&&t.innerText.trim()) window.__t.push(t.innerText.trim());}).observe(document.body,{subtree:true,childList:true,characterData:true,attributes:true});`);
    await lucia.clic('#btn-guardar-asistencias'); await esperar(1500);
    const toast2 = await lucia.ev(`window.__t[window.__t.length-1]||''`);
    anotar('50.6: guardar con la cuenta desactivada no escribe', sql('SELECT COUNT(*) FROM asistencias WHERE clase_id=346') === '0', { url: await lucia.url(), avisoDePantalla: toast2, marcasGuardadas: sql('SELECT COUNT(*) FROM asistencias WHERE clase_id=346') });
    sql(`UPDATE users SET activo=1 WHERE email='lucia.gaitan@wings.test'`);

    // 51.1 (T95): profesora de un deporte por hora, sin valor hora.
    const duenio = await pestana();
    await duenio.entrar('admin@wings.test');
    if (await duenio.url() === '/login') throw new Error('El dueño no entró');
    await duenio.ir('/admin/profesores/create').catch(() => {});
    if (await duenio.ev(`!document.querySelector('#valor_hora')`)) await duenio.ir('/profesores/create');
    const hockey = sql(`SELECT id FROM deportes WHERE nombre='Hockey'`);
    await duenio.escribir('#nombre', 'Sin'); await duenio.escribir('#apellido', 'Tarifa'); await duenio.escribir('#dni', '30999888');
    await duenio.ev(`(()=>{const f=document.querySelector('#fecha_nacimiento'); f.value='1985-03-10'; f.dispatchEvent(new Event('input',{bubbles:true})); const d=document.querySelector('#deporte_id'); d.value='${hockey}'; d.dispatchEvent(new Event('change',{bubbles:true}));})()`);
    await duenio.escribir('#direccion', 'Av. Prueba 1'); await duenio.escribir('#localidad', 'CABA'); await duenio.escribir('#telefono', '1166554433');
    await esperar(200);
    const nativo = await duenio.ev(`(()=>{const v=document.querySelector('#valor_hora'); return {requerido:v.required, mensaje:v.validationMessage, visible:!!v.offsetParent};})()`);
    await duenio.clic('form.mobile-form button[type=submit]'); await duenio.listo();
    const cuerpo = await duenio.ev('document.body.innerText');
    anotar('T95 / 51.1: sin valor hora no se crea y lo dice', sql(`SELECT COUNT(*) FROM profesores WHERE apellido='Tarifa'`) === '0', { nativo, url: await duenio.url(), avisoServidor: cuerpo.includes('Cargá el valor por hora.') });
} catch (e) {
    anotar('el recorrido se cortó', false, { error: String(e).slice(0, 400) });
} finally {
    fs.writeFileSync(SALIDA, JSON.stringify({ fecha: new Date().toISOString(), base: DB, resultado }, null, 2));
    chrome.kill();
    await esperar(400);
    try { fs.rmSync(perfil, { recursive: true, force: true }); } catch {}
    process.exit(0);
}
