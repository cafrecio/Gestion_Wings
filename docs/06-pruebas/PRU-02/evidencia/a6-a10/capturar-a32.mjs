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
  const registro=await evaluar(`(()=>{const w=${w},d=w.document,r=e=>{if(!e)return null;const b=e.getBoundingClientRect();return {x:b.x,y:b.y,width:b.width,height:b.height,right:b.right,bottom:b.bottom};};return {ancho:w.innerWidth,alto:w.innerHeight,documento:d.documentElement.scrollWidth,scroll:w.scrollY,nuevo:r([...d.querySelectorAll('.stats-bar a')].find(e=>e.innerText==='Nuevo')),tarjetas:[...d.querySelectorAll('.alumno-card')].map(c=>{const toggle=c.querySelector('.ds-toggle'),i=toggle?.querySelector('input'),propia=c.querySelector('.usuarios-cuenta-propia'),on=toggle?.querySelector('.is-on'),off=toggle?.querySelector('.is-off');return {nombre:c.querySelector('h3').innerText,rect:r(c),editar:r(c.querySelector('.alumno-actions a')),propia:propia?{texto:propia.innerText,rect:r(propia),titulo:propia.title}:null,toggle:i?{checked:i.checked,disabled:i.disabled,opacity:w.getComputedStyle(toggle).opacity,rect:r(toggle),onVisible:w.getComputedStyle(on).display!=='none',offVisible:w.getComputedStyle(off).display!=='none'}:null};})};})()`);
  if(w!=='window'&&registro.ancho!==375)throw Error('Marco incorrecto');
  if(registro.documento>registro.ancho)throw Error('Desborde '+nombre);
  const {data}=await cdp('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
  await writeFile(path.join(carpeta,'capturas',nombre+'.png'),Buffer.from(data,'base64'));
  capturas.push({nombre:nombre+'.png',...registro});console.log(nombre,registro.ancho);
 }
 let w=await abrir(true,false,true);await foto('a32-login-375',w);
 for(const propuesta of [false,true]){
  const prefijo=propuesta?'a32-propuesta':'a32-actual';
  w=await abrir(false,propuesta);await foto(prefijo+'-desktop',w);
  w=await abrir(true,propuesta);await foto(prefijo+'-375-inicio',w);
  for(const [slug,index] of [['otro-activo',1],['inactivo',3]]){
   await evaluar(`(()=>{const w=${w},c=w.document.querySelectorAll('.alumno-card')[${index}];if(!c)throw Error('Falta usuario');w.scrollTo(0,c.getBoundingClientRect().top+w.scrollY-76);})()`);await pausa(180);await foto(prefijo+'-375-'+slug,w);
  }
 }
 const antes=capturas.find(c=>c.nombre==='a32-actual-desktop.png');
 const despues=capturas.find(c=>c.nombre==='a32-propuesta-desktop.png');
 const propiaAntes=antes.tarjetas.find(c=>c.nombre.includes('(vos)'));
 const propiaDespues=despues.tarjetas.find(c=>c.nombre.includes('(vos)'));
 if(!propiaAntes?.toggle?.checked||!propiaAntes.toggle.disabled||propiaAntes.toggle.opacity!=='0.45'||!propiaAntes.toggle.onVisible)throw Error('Estado actual difiere del diagnóstico');
 if(propiaDespues?.toggle||!propiaDespues?.propia?.texto.includes('Activo'))throw Error('Cuenta propia propuesta incorrecta');
 for(let i=0;i<antes.tarjetas.length;i++){
  const a=antes.tarjetas[i],b=despues.tarjetas[i];
  if(a.nombre!==b.nombre)throw Error('Filas distintas');
  if(JSON.stringify(a.editar)!==JSON.stringify(b.editar)||JSON.stringify(a.rect)!==JSON.stringify(b.rect))throw Error('Geometría escritorio cambió');
  if(!a.nombre.includes('(vos)')&&JSON.stringify(a.toggle)!==JSON.stringify(b.toggle))throw Error('Otro interruptor cambió');
 }
 const movil=capturas.find(c=>c.nombre==='a32-propuesta-375-inicio.png');
 for(const t of movil.tarjetas){
  if(Math.abs(t.editar.x-movil.nuevo.x)>0.1||Math.abs(t.editar.right-movil.nuevo.right)>0.1)throw Error('Editar no coincide con Nuevo');
  if(Math.abs((t.propia?.rect||t.toggle?.rect).right-movil.nuevo.right)>0.1)throw Error('Estado no alineado');
 }
 const originales=JSON.parse(await readFile(path.join(carpeta,'originales-a32.json'),'utf8'));
 for(const [p,sha]of Object.entries(originales.originales))if(createHash('sha256').update(await readFile(path.join(process.cwd(),p))).digest('hex')!==sha)throw Error('Original modificado '+p);
 await writeFile(path.join(carpeta,'control-a32.json'),JSON.stringify({fecha:new Date().toISOString(),base:'wings_testing_codex',metodo:'Laravel real, variante Blade del original, iframe 375x667 dentro de 1280x900',estado:'propuesta, no aplicada',diagnostico:'Cuenta propia checked=true, disabled=true, label Activo visible, opacity=0.45; parece apagado por atenuación',originales:originales.originales,comparacionEscritorio:true,alineacion375:{x:movil.nuevo.x,right:movil.nuevo.right},capturas},null,2)+'\n');
 console.log('9 capturas, originales idénticos, escritorio conservado, otros toggles intactos, móvil alineado con Nuevo');
}finally{ws?.close();chrome.kill();}
