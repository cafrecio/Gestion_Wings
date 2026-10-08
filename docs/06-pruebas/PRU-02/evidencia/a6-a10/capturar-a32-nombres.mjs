import {spawn} from 'node:child_process';
import {readFile,writeFile} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
import os from 'node:os';
const carpeta=path.dirname(fileURLToPath(import.meta.url));
const pausa=ms=>new Promise(r=>setTimeout(r,ms));
const chrome=spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',[
 '--headless=new','--remote-debugging-port=9241','--remote-debugging-address=127.0.0.1',
 '--user-data-dir='+path.join(os.tmpdir(),'wings-chrome-a32-'+process.pid),
 '--no-first-run','--no-default-browser-check','--disable-background-networking','--window-size=1280,900','about:blank'
],{windowsHide:true,stdio:'ignore'});
let ws;
const capturas=[];
try{
 for(let i=0;i<60;i++){try{await fetch('http://127.0.0.1:9241/json/version');break;}catch{if(i===59)throw Error('Chrome no responde');await pausa(250);}}
 const tab=await(await fetch('http://127.0.0.1:9241/json/new?about:blank',{method:'PUT'})).json();
 ws=new WebSocket(tab.webSocketDebuggerUrl);await new Promise((r,j)=>{ws.onopen=r;ws.onerror=j;});
 let id=0;const pendientes=new Map();
 ws.onmessage=e=>{const m=JSON.parse(e.data);if(m.id&&pendientes.has(m.id)){const p=pendientes.get(m.id);pendientes.delete(m.id);clearTimeout(p.timer);m.error?p.j(Error(JSON.stringify(m.error))):p.r(m.result);}};
 const cdp=(method,params={})=>new Promise((r,j)=>{const n=++id,timer=setTimeout(()=>{pendientes.delete(n);j(Error('Tiempo agotado '+method));},20000);pendientes.set(n,{r,j,timer});ws.send(JSON.stringify({id:n,method,params}));});
 const evaluar=async expression=>{const r=await cdp('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});if(r.exceptionDetails)throw Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
 await cdp('Page.enable');await cdp('Emulation.setDeviceMetricsOverride',{width:1280,height:900,deviceScaleFactor:1,mobile:false});
 async function abrir(movil,propuesta,login=false){
  const pagina=login?'/login':'/usuarios';
  const url=movil?'http://127.0.0.1:8794/marco?alto=667&actor=admin&a32='+(propuesta?'propuesta':'actual')+'&pagina='+encodeURIComponent(pagina):'http://127.0.0.1:8794'+pagina+'?actor=admin&a32='+(propuesta?'propuesta':'actual');
  await cdp('Page.navigate',{url});await pausa(500);
  const w=movil?"document.querySelector('iframe')?.contentWindow":'window';
  for(let i=0;i<40;i++){if(await evaluar(`(()=>{const w=${w};return w?.document.readyState==='complete'&&!!w.document.querySelector('${login?'form':'.alumno-card'}');})()`))break;if(i===39)throw Error('Pantalla no lista '+url);await pausa(250);}
  await evaluar(`(async()=>{await (${w}).document.fonts.ready;return true;})()`);await pausa(200);return w;
 }
 async function foto(nombre,w){
  const registro=await evaluar(`(()=>{const w=${w},d=w.document,r=e=>{if(!e)return null;const b=e.getBoundingClientRect();return {x:b.x,y:b.y,width:b.width,height:b.height,right:b.right,bottom:b.bottom};};return {ancho:w.innerWidth,alto:w.innerHeight,documento:d.documentElement.scrollWidth,scroll:w.scrollY,nuevo:r([...d.querySelectorAll('.stats-bar a')].find(e=>e.innerText==='Nuevo')),tarjetas:[...d.querySelectorAll('.alumno-card')].map(c=>{const toggle=c.querySelector('.ds-toggle'),i=toggle?.querySelector('input'),propia=c.querySelector('.usuarios-cuenta-propia'),on=toggle?.querySelector('.is-on'),off=toggle?.querySelector('.is-off');return {nombre:c.querySelector('h3').innerText,datos:[...c.querySelectorAll('.info-item')].map(e=>({etiqueta:e.querySelector('.info-label').innerText,valor:e.querySelector('.info-value').innerText,rect:r(e)})),rect:r(c),editar:r(c.querySelector('.alumno-actions a')),propia:propia?{texto:propia.innerText,rect:r(propia),titulo:propia.title}:null,toggle:i?{checked:i.checked,disabled:i.disabled,opacity:w.getComputedStyle(toggle).opacity,rect:r(toggle),onVisible:w.getComputedStyle(on).display!=='none',offVisible:w.getComputedStyle(off).display!=='none'}:null};})};})()`);
  if(w!=='window'&&registro.ancho!==375)throw Error('Marco incorrecto');
  if(registro.documento>registro.ancho)throw Error('Desborde '+nombre);
  const {data}=await cdp('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
  await writeFile(path.join(carpeta,'capturas',nombre+'.png'),Buffer.from(data,'base64'));
  capturas.push({nombre:nombre+'.png',...registro});console.log(nombre,registro.ancho);
 }
 let w=await abrir(true,false,true);await foto('a32-nombres-login-375',w);
 w=await abrir(false,false);await foto('a32-nombres-desktop',w);
 w=await abrir(true,false);await foto('a32-nombres-375-inicio',w);
 for(const [slug,nombre] of [['profesor','Gómez de prueba, Mariana'],['operativo','Sandra'],['inactivo','Victoria']]){
  await evaluar(`(()=>{const w=${w},c=[...w.document.querySelectorAll('.alumno-card')].find(e=>e.querySelector('h3').innerText.includes(${JSON.stringify(nombre)}));if(!c)throw Error('Falta persona');w.scrollTo(0,c.getBoundingClientRect().top+w.scrollY-76);})()`);await pausa(180);await foto('a32-nombres-375-'+slug,w);
 }
 const desktop=capturas.find(c=>c.nombre==='a32-nombres-desktop.png'),movil=capturas.find(c=>c.nombre==='a32-nombres-375-inicio.png');
 for(const t of desktop.tarjetas){
  if(JSON.stringify(t.datos.map(d=>d.etiqueta))!==JSON.stringify(['Email:','Rol:']))throw Error('Datos distintos entre tarjetas');
  if(Math.abs(t.datos[1].rect.x-desktop.tarjetas[0].datos[1].rect.x)>0.1)throw Error('Rol desalineado');
 }
 if(!desktop.tarjetas.some(t=>t.nombre==='Gómez de prueba, Mariana'&&t.datos[1].valor==='Profesor'))throw Error('Nombre de profesora incorrecto');
 if(!desktop.tarjetas.some(t=>t.nombre==='Sandra'&&t.datos[1].valor==='Operativo'))throw Error('Nombre de operativo incorrecto');
 for(const t of movil.tarjetas){
  if(Math.abs(t.editar.x-movil.nuevo.x)>0.1||Math.abs(t.editar.right-movil.nuevo.right)>0.1)throw Error('Editar no coincide con Nuevo');
  if(Math.abs((t.propia?.rect||t.toggle?.rect).right-movil.nuevo.right)>0.1)throw Error('Estado desalineado');
 }
 const propia=desktop.tarjetas.find(t=>t.nombre.includes('(vos)'));if(propia.toggle||!propia.propia.texto.includes('Activo'))throw Error('A32 original alterado');
 const original=JSON.parse(await readFile(path.join(carpeta,'control-a32.json'),'utf8'));
 for(const [p,sha]of Object.entries(original.originales)){if(p==='resources/views/usuarios/index.blade.php')continue;if(createHash('sha256').update(await readFile(path.join(process.cwd(),p))).digest('hex')!==sha)throw Error('Compartido modificado '+p);}
 const vista=await readFile(path.join(process.cwd(),'resources/views/usuarios/index.blade.php'));
 await writeFile(path.join(carpeta,'control-a32-nombres.json'),JSON.stringify({fecha:new Date().toISOString(),base:'wings_testing_codex',metodo:'Laravel real, vista aplicada, iframe 375x667 dentro de 1280x900',estado:'correccion pedida por Carlos, pendiente de revision visual',vistaSha256:createHash('sha256').update(vista).digest('hex'),rolesAlineados:true,nombresPersonas:true,compartidosIntactos:true,alineacion375:{x:movil.nuevo.x,right:movil.nuevo.right},capturas},null,2)+'\n');
 console.log('6 capturas: roles alineados, personas en títulos, acciones móviles alineadas y compartidos intactos');
}finally{ws?.close();chrome.kill();}
