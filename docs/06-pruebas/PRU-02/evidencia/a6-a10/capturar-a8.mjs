// Laravel real; laboratorio exclusivo wings_testing_codex. Marco móvil real de 375.
import {spawn} from 'node:child_process';
import {mkdir,writeFile} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
import os from 'node:os';
const carpeta=path.dirname(fileURLToPath(import.meta.url));
const salida=path.join(carpeta,'capturas');
const prefijo=process.env.A8_CAPTURE_PREFIX||'a8-final';
const comprobarNuevo=process.env.A9_CHECK_ONLY==='1';
if(!/^[a-z0-9-]+$/.test(prefijo))throw new Error('Prefijo inválido');
const pausa=ms=>new Promise(r=>setTimeout(r,ms));
await mkdir(salida,{recursive:true});
const chrome=spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',[
 '--headless=new','--remote-debugging-port=9238','--remote-debugging-address=127.0.0.1',
 '--user-data-dir='+path.join(os.tmpdir(),'wings-chrome-a8-'+process.pid),
 '--no-first-run','--no-default-browser-check','--disable-background-networking','--window-size=1280,900','about:blank'
],{windowsHide:true,stdio:'ignore'});
let ws;
try {
 for(let i=0;i<60;i++) {try {await fetch('http://127.0.0.1:9238/json/version');break;} catch {if(i===59) throw new Error('Chrome no responde');await pausa(250);}}
 const target=await(await fetch('http://127.0.0.1:9238/json/new?about:blank',{method:'PUT'})).json();
 ws=new WebSocket(target.webSocketDebuggerUrl);
 await new Promise((r,j)=>{ws.onopen=r;ws.onerror=j;});
 let id=0;const pendientes=new Map();
 ws.onmessage=e=>{const m=JSON.parse(e.data);if(m.id&&pendientes.has(m.id)){const p=pendientes.get(m.id);pendientes.delete(m.id);clearTimeout(p.timer);m.error?p.j(new Error(JSON.stringify(m.error))):p.r(m.result);}};
 const cdp=(method,params={})=>new Promise((r,j)=>{const n=++id;const timer=setTimeout(()=>{pendientes.delete(n);j(new Error('Tiempo agotado '+method));},20000);pendientes.set(n,{r,j,timer});ws.send(JSON.stringify({id:n,method,params}));});
 const evaluar=async expression=>{const r=await cdp('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});if(r.exceptionDetails)throw new Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
 await cdp('Page.enable');
 await cdp('Emulation.setDeviceMetricsOverride',{width:1280,height:900,deviceScaleFactor:1,mobile:false});
 const pruebas=[];
 async function abrir(pagina,actor,movil) {
  const url=movil?'http://127.0.0.1:8794/marco?alto=667&actor='+actor+'&pagina='+encodeURIComponent(pagina):'http://127.0.0.1:8794'+pagina+'?actor='+actor;
  await cdp('Page.navigate',{url});await pausa(600);
  const w=movil?"document.querySelector('iframe')?.contentWindow":'window';
  for(let i=0;i<40;i++) {if(await evaluar(`(()=>{const w=${w};return !!w&&w.document.readyState==='complete'&&!!w.document.querySelector('${pagina==='/login'?'form':'.alumno-card'}');})()`))break;if(i===39)throw new Error('Pantalla no lista '+url);await pausa(250);}
  await evaluar(`(async()=>{await (${w}).document.fonts.ready;return true;})()`);await pausa(250);return w;
 }
 async function guardar(nombre,w) {
  nombre=nombre.replace(/^a8/,prefijo);
  const medicion=await evaluar(`(()=>{const w=${w},d=w.document;const r=e=>{if(!e)return null;const b=e.getBoundingClientRect();return {x:b.x,y:b.y,width:b.width,height:b.height,right:b.right,bottom:b.bottom};};return {ancho:w.innerWidth,alto:w.innerHeight,scroll:w.scrollY,anchoDocumento:d.documentElement.scrollWidth,nuevo:r([...d.querySelectorAll('.stats-bar a')].find(e=>e.textContent.trim()==='Nuevo')),tarjetas:[...d.querySelectorAll('.alumno-card')].map(c=>{const dot=c.querySelector('.alumno-dot'),pie=c.querySelector('.alumno-actions');return {texto:c.innerText,rect:r(c),punto:dot?{estado:dot.getAttribute('aria-label'),titulo:dot.title,color:w.getComputedStyle(dot).backgroundColor}:null,acciones:[...c.querySelectorAll('.alumno-actions a,.alumno-actions button')].map(e=>({texto:e.innerText,rect:r(e)})),interruptor:r(c.querySelector('.ds-toggle')),alineacion:pie?w.getComputedStyle(pie).justifyContent:null};})};})()`);
  if(w!=='window'&&medicion.ancho!==375)throw new Error('Marco incorrecto');
  if(medicion.anchoDocumento>medicion.ancho)throw new Error('Desborde horizontal '+nombre);
  const {data}=await cdp('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
  await writeFile(path.join(salida,nombre+'.png'),Buffer.from(data,'base64'));
  pruebas.push({nombre:nombre+'.png',...medicion});console.log(nombre,medicion.ancho,medicion.scroll);
  if(comprobarNuevo&&medicion.tarjetas.length) {
   if(!medicion.nuevo)throw new Error('Nuevo no encontrado '+nombre);
   for(const t of medicion.tarjetas) {
    const ultimo=t.acciones.at(-1)?.rect;
    if(!ultimo||Math.abs(ultimo.x-medicion.nuevo.x)>0.1||Math.abs(ultimo.right-medicion.nuevo.right)>0.1)throw new Error('Botón no coincide con Nuevo '+nombre);
    if(t.interruptor&&Math.abs(t.interruptor.right-medicion.nuevo.right)>0.1)throw new Error('Interruptor no coincide con Nuevo '+nombre);
   }
   console.log('Nuevo y ultimo boton: x='+medicion.nuevo.x+', derecha='+medicion.nuevo.right+'; interruptores alineados');
  }
 }
 let w=await abrir('/login','admin',true);await guardar('a8-login-375',w);
 const casos=comprobarNuevo?[['grupos','admin'],['niveles','admin'],['profesores','admin']]:[['grupos','admin'],['grupos','operativo'],['niveles','admin'],['profesores','admin']];
 for(const [pantalla,actor] of casos) {
  if(!comprobarNuevo){w=await abrir('/'+pantalla,actor,false);await guardar('a8-'+pantalla+'-'+actor+'-desktop',w);}
  w=await abrir('/'+pantalla,actor,true);await guardar('a8-'+pantalla+'-'+actor+'-375-inicio',w);
  const cantidad=comprobarNuevo?1:await evaluar(`(${w}).document.querySelectorAll('.alumno-card').length`);
  for(let i=0;i<cantidad;i++) {
   await evaluar(`(()=>{const w=${w},c=w.document.querySelectorAll('.alumno-card')[${i}];w.scrollTo(0,c.getBoundingClientRect().top+w.scrollY-76);})()`);
   await pausa(200);await guardar('a8-'+pantalla+'-'+actor+'-375-tarjeta-'+(i+1),w);
   const alto=await evaluar(`(${w}).document.querySelectorAll('.alumno-card')[${i}].getBoundingClientRect().height`);
   if(alto>570) {
    await evaluar(`(()=>{const w=${w},c=w.document.querySelectorAll('.alumno-card')[${i}];w.scrollTo(0,c.getBoundingClientRect().bottom+w.scrollY-w.innerHeight+20);})()`);
    await pausa(200);await guardar('a8-'+pantalla+'-'+actor+'-375-acciones-'+(i+1),w);
   }
  }
 }
 await writeFile(path.join(carpeta,'comprobacion-'+prefijo+'.json'),JSON.stringify({fecha:new Date().toISOString(),base:'wings_testing_codex',metodo:'Laravel HTTP real; iframe 375x667 en 1280x900',capturas:pruebas},null,2)+'\n');
} finally {ws?.close();chrome.kill();}
