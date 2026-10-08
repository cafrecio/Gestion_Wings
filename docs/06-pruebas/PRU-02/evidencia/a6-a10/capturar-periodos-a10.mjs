// Capturas de Laravel real, rutas/controller originales y base de pruebas Codex.
import {spawn} from 'node:child_process';
import {mkdir,writeFile} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
import os from 'node:os';
const carpeta=path.dirname(fileURLToPath(import.meta.url));
const salida=path.join(carpeta,'capturas');
const prefijo=process.env.A10_CAPTURE_PREFIX||'a10-periodos';
if(!/^[a-z0-9-]+$/.test(prefijo))throw new Error('Prefijo inválido');
const pausa=ms=>new Promise(r=>setTimeout(r,ms));
await mkdir(salida,{recursive:true});
const chrome=spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',[
 '--headless=new','--remote-debugging-port=9239','--remote-debugging-address=127.0.0.1',
 '--user-data-dir='+path.join(os.tmpdir(),'wings-chrome-a10-'+process.pid),
 '--no-first-run','--no-default-browser-check','--disable-background-networking','--window-size=1280,900','about:blank'
],{windowsHide:true,stdio:'ignore'});
let ws;
try {
 for(let i=0;i<60;i++){try{await fetch('http://127.0.0.1:9239/json/version');break;}catch{if(i===59)throw new Error('Chrome no responde');await pausa(250);}}
 const t=await(await fetch('http://127.0.0.1:9239/json/new?about:blank',{method:'PUT'})).json();
 ws=new WebSocket(t.webSocketDebuggerUrl);await new Promise((r,j)=>{ws.onopen=r;ws.onerror=j;});
 let id=0;const pendientes=new Map();
 ws.onmessage=e=>{const m=JSON.parse(e.data);if(m.id&&pendientes.has(m.id)){const p=pendientes.get(m.id);pendientes.delete(m.id);clearTimeout(p.timer);m.error?p.j(new Error(JSON.stringify(m.error))):p.r(m.result);}};
 const cdp=(method,params={})=>new Promise((r,j)=>{const n=++id;const timer=setTimeout(()=>{pendientes.delete(n);j(new Error(method));},20000);pendientes.set(n,{r,j,timer});ws.send(JSON.stringify({id:n,method,params}));});
 const evaluar=async expression=>{const r=await cdp('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});if(r.exceptionDetails)throw new Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
 await cdp('Page.enable');await cdp('Emulation.setDeviceMetricsOverride',{width:1280,height:900,deviceScaleFactor:1,mobile:false});
 const pruebas=[];
 async function abrir(pagina,movil=false){
  const url=movil?'http://127.0.0.1:8794/marco?alto=667&actor=admin&pagina='+encodeURIComponent(pagina):'http://127.0.0.1:8794'+pagina+(pagina.includes('?')?'&':'?')+'actor=admin';
  await cdp('Page.navigate',{url});await pausa(500);
  const w=movil?"document.querySelector('iframe')?.contentWindow":'window';
  const selector=pagina==='/login'?'form':pagina.startsWith('/cashflow/movimiento')?'#mov-form':'#cashflow-periodo';
  for(let i=0;i<40;i++){if(await evaluar(`(()=>{const w=${w};return !!w&&w.document.readyState==='complete'&&!!w.document.querySelector('${selector}');})()`))break;if(i===39)throw new Error('Pantalla no lista '+url);await pausa(250);}
  await evaluar(`(async()=>{await (${w}).document.fonts.ready;return true;})()`);await pausa(250);return w;
 }
 async function guardar(nombre,w){
  const m=await evaluar(`(()=>{const w=${w},d=w.document;const r=e=>{if(!e)return null;const b=e.getBoundingClientRect();return {x:b.x,y:b.y,width:b.width,height:b.height,right:b.right,bottom:b.bottom};};const nuevo=[...d.querySelectorAll('a')].find(e=>e.textContent.trim()==='Nuevo');return {ancho:w.innerWidth,alto:w.innerHeight,anchoDocumento:d.documentElement.scrollWidth,scroll:w.scrollY,titulo:d.title,cabecera:d.querySelector('.ds-module-header')?.innerText,periodo:d.querySelector('#cashflow-periodo')?.innerText,nuevo:nuevo?{rect:r(nuevo),destino:nuevo.href,barra:nuevo.closest('.stats-bar')?.innerText}:null,barras:[...d.querySelectorAll('.stats-bar')].map(e=>({texto:e.innerText,rect:r(e)})),campos:[...d.querySelectorAll('#mov-form label[for]')].map(e=>({texto:e.innerText,icono:!!e.querySelector('svg'),rect:r(e)})),tipo:d.querySelector('#tipo-card > p')?.innerText,acciones:[...d.querySelectorAll('#mov-form .filtros-actions a,#mov-form .filtros-actions button')].map(e=>({texto:e.innerText,rect:r(e)})),formulario:r(d.querySelector('#mov-form')),selectorPeriodo:d.querySelector('[name=periodo]')?.value,filtros:[...d.querySelectorAll('#filtros-form label')].map(e=>({texto:e.innerText,rect:r(e),control:r(e.parentElement.querySelector('select,input'))})),tablaFilas:[...d.querySelectorAll('tbody tr')].map(e=>e.innerText)};})()`);
  if(w!=='window'&&m.ancho!==375)throw new Error('Marco incorrecto');
  if(m.anchoDocumento>m.ancho)throw new Error('Desborde horizontal '+nombre);
  const {data}=await cdp('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
  await writeFile(path.join(salida,prefijo+'-'+nombre+'.png'),Buffer.from(data,'base64'));
  pruebas.push({nombre:prefijo+'-'+nombre+'.png',...m});console.log(prefijo+'-'+nombre,m.ancho,m.scroll);
 }
 let w=await abrir('/login',true);await guardar('login-375',w);
 for(const [caso,ruta] of [
  ['dia','/cashflow?periodo=dia&fecha=2026-10-08'],
  ['semana','/cashflow?periodo=semana&fecha=2026-10-08'],
  ['mes','/cashflow?periodo=mes&anio=2026&mes=10'],
  ['anio','/cashflow?periodo=anio&anio=2026'],
  ['semana-cruce-mes','/cashflow?periodo=semana&fecha=2026-10-01'],
  ['semana-cruce-anio','/cashflow?periodo=semana&fecha=2027-01-01']
 ]) {
  w=await abrir(ruta);await guardar(caso+'-desktop',w);
  w=await abrir(ruta,true);await guardar(caso+'-375-inicio',w);
  await evaluar(`(()=>{const w=${w},e=w.document.querySelector('#cashflow-periodo');w.scrollTo(0,e.getBoundingClientRect().top+w.scrollY-76);})()`);
  await pausa(200);await guardar(caso+'-375-resumen',w);
 }
 // Ejercicio real del selector: Día → Semana, manteniendo la fecha.
 w=await abrir('/cashflow?periodo=dia&fecha=2026-10-08');
 await evaluar(`(()=>{const e=document.querySelector('[name=periodo]');e.value='semana';e.dispatchEvent(new Event('change',{bubbles:true}));})()`);
 for(let i=0;i<40;i++){if(await evaluar(`document.querySelector('[name=periodo]')?.value==='semana'&&document.querySelector('#cashflow-periodo')?.innerText.includes('11/10/2026')`))break;if(i===39)throw new Error('Selector no cambia el período');await pausa(250);}
 await guardar('selector-interactivo-desktop',w);
 if(prefijo==='a10-aplicado-periodos') {
  await evaluar(`(()=>{const a=[...document.querySelectorAll('a')].find(e=>e.textContent.trim()==='Nuevo');if(a.href!=='http://127.0.0.1:8794/cashflow/movimiento')throw new Error('Destino fuera del laboratorio');a.click();})()`);
  for(let i=0;i<40;i++){if(await evaluar(`!!document.querySelector('#mov-form')`))break;if(i===39)throw new Error('Nuevo no abrió formulario');await pausa(250);}
  await evaluar(`(async()=>{await document.fonts.ready;return true;})()`);await pausa(250);
  await guardar('movimiento-desktop','window');
  w=await abrir('/cashflow/movimiento',true);await guardar('movimiento-375-inicio',w);
  await evaluar(`(()=>{const w=${w},e=w.document.querySelector('[for="rubro_id"]');w.scrollTo(0,e.getBoundingClientRect().top+w.scrollY-76);})()`);
  await pausa(200);await guardar('movimiento-375-campos',w);
  await evaluar(`(()=>{const w=${w};w.scrollTo(0,w.document.documentElement.scrollHeight);})()`);
  await pausa(200);await guardar('movimiento-375-acciones',w);
 }
 await writeFile(path.join(carpeta,'comprobacion-'+prefijo+'.json'),JSON.stringify({fecha:new Date().toISOString(),base:'wings_testing_codex',metodo:'Laravel HTTP real; marco iframe 375x667; selector Día → Semana ejercitado en escritorio; móvil en srcdoc por X-Frame-Options DENY',capturas:pruebas},null,2)+'\n');
}finally{ws?.close();chrome.kill();}
