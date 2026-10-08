// Capturas reales del laboratorio Laravel. Solo base descartable wings_testing_codex.
import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import os from 'node:os';

const carpeta = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(carpeta, '../../../../..');
const salida = path.join(carpeta, 'capturas');
const prefijo = process.env.A7_CAPTURE_PREFIX || 'a7-final';
if (!/^[a-z0-9-]+$/.test(prefijo)) throw new Error('Prefijo inválido');
const pausa = ms => new Promise(r => setTimeout(r, ms));
await mkdir(salida, { recursive: true });
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--remote-debugging-port=9237', '--remote-debugging-address=127.0.0.1',
  '--user-data-dir=' + path.join(os.tmpdir(), 'wings-chrome-a7-' + process.pid),
  '--no-first-run', '--no-default-browser-check', '--disable-background-networking',
  '--window-size=1280,900', 'about:blank'
], { windowsHide: true, stdio: 'ignore' });
let ws;
try {
  for (let i=0;i<60;i++) {
    try { await fetch('http://127.0.0.1:9237/json/version'); break; }
    catch { if (i===59) throw new Error('Chrome no responde'); await pausa(250); }
  }
  const target = await (await fetch('http://127.0.0.1:9237/json/new?about:blank', {method:'PUT'})).json();
  ws = new WebSocket(target.webSocketDebuggerUrl);
  await new Promise((r,j) => { ws.onopen=r; ws.onerror=j; });
  let id=0;
  const pendientes=new Map();
  ws.onmessage=e=>{
    const m=JSON.parse(e.data);
    if(m.id && pendientes.has(m.id)) {
      const p=pendientes.get(m.id); pendientes.delete(m.id); clearTimeout(p.timer);
      if(m.error) p.j(new Error(JSON.stringify(m.error))); else p.r(m.result);
    }
  };
  const cdp=(method,params={})=>new Promise((r,j)=>{
    const n=++id;
    const timer=setTimeout(()=>{pendientes.delete(n);j(new Error('Tiempo agotado '+method));},20000);
    pendientes.set(n,{r,j,timer}); ws.send(JSON.stringify({id:n,method,params}));
  });
  const evaluar=async expression=>{
    const r=await cdp('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});
    if(r.exceptionDetails) throw new Error(JSON.stringify(r.exceptionDetails));
    return r.result.value;
  };
  await cdp('Page.enable');
  await cdp('Emulation.setDeviceMetricsOverride',{width:1280,height:900,deviceScaleFactor:1,mobile:false});
  const pruebas=[];
  async function abrir(pagina,actor='admin',movil=false) {
    const url=movil
      ? 'http://127.0.0.1:8794/marco?alto=667&actor='+actor+'&pagina='+encodeURIComponent(pagina)
      : 'http://127.0.0.1:8794'+pagina+(pagina.includes('?')?'&':'?')+'actor='+actor;
    await cdp('Page.navigate',{url});
    await pausa(900);
    for(let i=0;i<40;i++) {
      const listo=await evaluar(`(()=>{const d=${movil ? "document.querySelector('iframe')?.contentDocument" : 'document'};return !!d && d.readyState==='complete' && !!d.querySelector('${pagina==='/login' ? 'form' : actor==='profesor' ? 'main' : '.alumnos-listado .alumno-card'}');})()`);
      if(listo) break;
      if(i===39) throw new Error('Pantalla no lista: '+url);
      await pausa(250);
    }
    await evaluar(`(async()=>{const d=${movil ? "document.querySelector('iframe').contentDocument" : 'document'}; await d.fonts.ready; return true;})()`);
    await pausa(400);
    return movil ? "document.querySelector('iframe').contentWindow" : 'window';
  }
  async function guardar(nombre,w,card=false) {
    nombre = nombre.replace(/^a7-final/, prefijo);
    const medicion=await evaluar(`(()=>{const w=${w},d=w.document;const r=e=>{const b=e.getBoundingClientRect();return {x:b.x,y:b.y,width:b.width,height:b.height,right:b.right,bottom:b.bottom};}; const c=d.querySelector('.alumnos-listado .alumno-card');return {ancho:w.innerWidth,alto:w.innerHeight,scroll:w.scrollY,anchoDocumento:d.documentElement.scrollWidth,tarjeta:c?r(c):null,nuevo:c?r(d.querySelector('.stats-bar > .ds-btn')):null,datos:c?[...c.querySelectorAll('.info-item')].map(e=>({texto:e.innerText,rect:r(e)})):[],acciones:c?[...c.querySelectorAll('.alumno-actions a,.alumno-actions button')].map(e=>({texto:e.innerText,rect:r(e)})):[],interruptor:c?r(c.querySelector('.ds-toggle')):null,alineacion:c?w.getComputedStyle(c.querySelector('.alumno-actions')).justifyContent:null,columnas:c?w.getComputedStyle(c.parentElement).gridTemplateColumns:null};})()`);
    if(w!=='window' && medicion.ancho!==375) throw new Error('Marco no mide 375');
    if(medicion.anchoDocumento>medicion.ancho) throw new Error('Desborde horizontal: '+nombre);
    if(card && !medicion.tarjeta) throw new Error('No hay tarjeta');
    const {data}=await cdp('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
    await writeFile(path.join(salida,nombre+'.png'),Buffer.from(data,'base64'));
    pruebas.push({nombre:nombre+'.png',...medicion});
    console.log(nombre,medicion.ancho,medicion.scroll);
  }
  let w=await abrir('/login','admin',true);
  await guardar('a7-final-login-375',w);
  for(const actor of ['admin','operativo']) {
    w=await abrir('/alumnos',actor,false);
    await guardar('a7-final-'+actor+'-desktop',w,true);
    w=await abrir('/alumnos',actor,true);
    await guardar('a7-final-'+actor+'-375-inicio',w,true);
    await evaluar(`(()=>{const w=${w},c=w.document.querySelector('.alumnos-listado .alumno-card');w.scrollTo(0,c.getBoundingClientRect().top+w.scrollY-12);})()`);
    await pausa(250); await guardar('a7-final-'+actor+'-375-tarjeta',w,true);
    await evaluar(`(()=>{const w=${w},c=w.document.querySelector('.alumnos-listado .alumno-card');w.scrollTo(0,c.getBoundingClientRect().bottom+w.scrollY-w.innerHeight+20);})()`);
    await pausa(250); await guardar('a7-final-'+actor+'-375-acciones',w,true);
  }
  w=await abrir('/alumnos?search=33444555','admin',false);
  await guardar('a7-final-largo-desktop',w,true);
  w=await abrir('/alumnos?search=33444555','admin',true);
  await evaluar(`(()=>{const w=${w},c=w.document.querySelector('.alumnos-listado .alumno-card');w.scrollTo(0,c.getBoundingClientRect().top+w.scrollY-12);})()`);
  await pausa(250); await guardar('a7-final-largo-375-tarjeta',w,true);
  await evaluar(`(()=>{const w=${w},c=w.document.querySelector('.alumnos-listado .alumno-card');w.scrollTo(0,c.getBoundingClientRect().bottom+w.scrollY-w.innerHeight+20);})()`);
  await pausa(250); await guardar('a7-final-largo-375-acciones',w,true);
  await writeFile(path.join(carpeta,'comprobacion-'+prefijo+'.json'),JSON.stringify({fecha:new Date().toISOString(),base:'wings_testing_codex',metodo:'Laravel HTTP real, CDP, marco iframe 375x667 dentro de 1280x900',capturas:pruebas},null,2)+'\n');
} finally {
  ws?.close(); chrome.kill();
}
