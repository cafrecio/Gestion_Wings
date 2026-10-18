import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
const fase=process.argv[2]||'antes';assert(['antes','despues'].includes(fase));
const carpeta='docs/06-pruebas/PRU-04/evidencia-t75';
const manifiesto=JSON.parse(fs.readFileSync(`${carpeta}/respuestas-${fase}.json`,'utf8'));
const salida=path.resolve(`storage/app/t75-runtime/app/capturas/${fase}`);fs.mkdirSync(salida,{recursive:true});
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-t75-${Date.now()}`)});
const base='http://127.0.0.1:8735';
const filtro=process.env.T75_SOLO?process.env.T75_SOLO.split(',').filter(Boolean):null;
const checkpoint=path.resolve(`storage/app/t75-runtime/app/mediciones-${fase}.json`);
const continuar=process.env.T75_CONTINUAR==='1';
let page;const resultados=continuar?JSON.parse(fs.readFileSync(fs.existsSync(checkpoint)?checkpoint:`${carpeta}/mediciones-${fase}.json`,'utf8')).casos.filter(c=>!filtro?.includes(c.slug)):[];const variantesVistas=new Set();
const completados=new Set(resultados.filter(c=>resultados.filter(r=>r.slug===c.slug).length===3).map(c=>c.slug));
for(const c of resultados)for(const v of c.variantes){const p=c.paneles.find(p=>p.id===v.panel);if(p){const vista=c.vistas.find(v=>!v.includes('/layouts/')&&!v.includes('/components/'))||c.slug;variantesVistas.add(`${vista}:${p.id}:${p.etiquetas.join('|')}:${c.ancho}`);}}
const dormir=ms=>new Promise(r=>setTimeout(r,ms));
async function guardar(destino,texto){for(let i=0;i<6;i++){try{await fs.promises.writeFile(destino,texto);return;}catch(e){if(i===5)throw e;await dormir(150*(i+1));}}}

function medir(contexto){
 const win=document.querySelector('#pantalla').contentWindow,d=win.document;
 const visible=e=>{const r=e.getBoundingClientRect(),s=win.getComputedStyle(e);return r.width>0&&r.height>0&&s.display!=='none'&&s.visibility!=='hidden'&&!e.closest('[hidden]');};
 const red=n=>Math.round(n*100)/100;
 const caja=e=>{const r=e.getBoundingClientRect();return {x:red(r.x),y:red(r.y+win.scrollY),w:red(r.width),h:red(r.height),derecha:red(r.right)};};
 const ruta=e=>{const r=[];while(e&&e!==d.body){let n=1;for(let p=e.previousElementSibling;p;p=p.previousElementSibling)if(p.tagName===e.tagName)n++;r.unshift(e.tagName.toLowerCase()+':nth-of-type('+n+')');e=e.parentElement;}return r.join('>');};
 const raiz=d.querySelector('.ds-content')||d.querySelector('main')||d.body;
 const controles=[...raiz.querySelectorAll('button,a,input[type=submit],.ds-toggle,[role=switch]')].filter(visible).filter(e=>!e.parentElement.closest('.ds-btn,.ds-toggle'));
 const grupos=new Map();
 for(const e of controles){
  let zona=e.closest('.alumno-actions,.mobile-form-actions,.form-actions,.filtros-actions');
  if(!zona)zona=e.closest('.stats-bar');
  if(!zona){for(let p=e.parentElement;p&&p!==raiz.parentElement;p=p.parentElement){if(['flex','inline-flex','grid','inline-grid'].includes(win.getComputedStyle(p).display)){zona=p;break;}}}
  zona||=e.parentElement;
  const clave=ruta(zona);if(!grupos.has(clave))grupos.set(clave,{elemento:zona,controles:[]});
  let categoria='barra';
  if(contexto.slug==='login')categoria='ingreso';
  else if(e.closest('td,th'))categoria='fila-tabla';
  else if(e.closest('nav'))categoria='navegacion';
  else {let modal=false;for(let p=e.parentElement;p&&p!==d.body;p=p.parentElement){const s=win.getComputedStyle(p);if(p.getAttribute('role')==='dialog'||(s.position==='fixed'&&parseInt(s.zIndex)>=100)){modal=true;break;}}if(modal)categoria='modal';else if(e.closest('.alumno-actions'))categoria='pie-tarjeta';else if(!e.closest('form,.stats-bar')&&!['flex','grid','inline-flex','inline-grid'].includes(win.getComputedStyle(zona).display))categoria='otro';}
  const etiqueta=(e.textContent||e.value||e.getAttribute('aria-label')||e.title||'').trim().replace(/\s+/g,' ').slice(0,110);
  grupos.get(clave).controles.push({ruta:ruta(e),id:e.id,etiqueta,categoria,tag:e.tagName.toLowerCase(),rect:caja(e),disabled:!!e.disabled});
 }
 const zonas=[];
 for(const [clave,g]of grupos){
  const s=win.getComputedStyle(g.elemento),r=g.elemento.getBoundingClientRect();
  const izquierda=r.left+parseFloat(s.borderLeftWidth||0)+parseFloat(s.paddingLeft||0),derecha=r.right-parseFloat(s.borderRightWidth||0)-parseFloat(s.paddingRight||0);
  const filas=[];for(const e of g.controles){const cy=e.rect.y+e.rect.h/2;let fila=filas.find(f=>Math.abs(f.cy-cy)<5);if(!fila){fila={cy,controles:[]};filas.push(fila);}fila.controles.push(e);}
  for(const f of filas){f.controles.sort((a,b)=>a.rect.x-b.rect.x);const inicio=f.controles[0].rect.x,fin=Math.max(...f.controles.map(e=>e.rect.derecha));const hueco=parseFloat(s.columnGap)||parseFloat(s.gap)||0;const separada=f.controles.some((e,i)=>i>0&&e.rect.x-f.controles[i-1].rect.derecha>hueco+12);
   f.alineacion=separada?'distribuidos':Math.abs(fin-derecha)<=4?'derecha':Math.abs(inicio-izquierda)<=4?'izquierda':Math.abs((inicio+fin)-(izquierda+derecha))<=8?'centrados':'intermedia';
   f.cy=red(f.cy);
  }
  zonas.push({ruta:clave,clases:g.elemento.className,rect:caja(g.elemento),display:s.display,justify:s.justifyContent,direccion:s.flexDirection,izquierda:red(izquierda),derecha:red(derecha),filas});
 }
 const paneles=[...d.querySelectorAll('[id^="modal"],[id^="panel-"]')].filter(e=>e.querySelector('button,a')&&!visible(e)&&!e.parentElement.closest('[id^="modal"],[id^="panel-"]')).map(e=>({id:e.id,modal:e.id.startsWith('modal'),etiquetas:[...e.querySelectorAll('button')].map(b=>b.textContent.trim().replace(/\s+/g,' ')).filter(Boolean)}));
 const regiones=[...d.querySelectorAll('div[style*="overflow"],[role=region]')].filter(e=>visible(e)&&e.scrollWidth>e.clientWidth+20&&e.querySelector('table button,table a,table input'));
 return {ancho:win.innerWidth,cliente:d.documentElement.clientWidth,anchoDocumento:d.documentElement.scrollWidth,altoDocumento:d.documentElement.scrollHeight,desborde:d.documentElement.scrollWidth>win.innerWidth+1,zonas,paneles,tablasConAccionesDesplazables:regiones.length,fuentes:!!d.fonts&&d.fonts.status};
}
async function cargar(caso,ancho){
 await page.send('Page.bringToFront');
 await page.evaluate(async(base,fase,caso,ancho)=>{
  let f=document.querySelector('#pantalla');if(!f){f=document.createElement('iframe');f.id='pantalla';document.body.append(f);}
  f.style.cssText=`display:block;width:${ancho}px;height:1000px;border:0;margin:0 auto`;
  await new Promise((resolve,reject)=>{const t=setTimeout(()=>reject(Error('Timeout iframe '+caso.slug)),15000);f.onload=()=>{clearTimeout(t);resolve();};f.src=`${base}/__t75/${fase}/${caso.slug}.html`;});
  await f.contentDocument.fonts.ready;
  await new Promise(r=>setTimeout(r,450));
 },base,fase,caso,ancho);
 assert.equal(await page.evaluate('document.querySelector("#pantalla").contentWindow.innerWidth'),ancho);
}
async function foto(caso,ancho,variante='principal'){
 const nombre=`${caso.slug}--${ancho}--${variante}.png`;
 const r=await page.send('Page.captureScreenshot',{format:'png',clip:{x:(1366-ancho)/2,y:0,width:ancho,height:1000,scale:1}});
 const datos=Buffer.from(r.data,'base64');fs.writeFileSync(path.join(salida,nombre),datos);
 return {nombre,sha256:createHash('sha256').update(datos).digest('hex')};
}
try{
 await browser.start();page=await browser.newPage();await page.connect();
 await page.send('Network.enable');await page.send('Network.setBypassServiceWorker',{bypass:true});
 await page.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1000,deviceScaleFactor:1,mobile:false});
 await page.send('Page.bringToFront');await page.goto(base+'/__t75/marco');
 for(const caso of manifiesto.casos){
  if(filtro&&!filtro.includes(caso.slug))continue;
  if(continuar&&!filtro&&completados.has(caso.slug))continue;
  if(caso.status!==200)continue;
  for(const ancho of [360,375,1366]){
   await cargar(caso,ancho);
   const medicion=await page.evaluate(medir,caso);const fotos=[await foto(caso,ancho)];
   const controles=medicion.zonas.flatMap(z=>z.filas.flatMap(f=>f.controles));
   const ultimo=controles.reduce((a,b)=>b.rect.y+b.rect.h>(a?.rect.y||0)+(a?.rect.h||0)?b:a,null);
   if(ultimo&&ultimo.rect.y+ultimo.rect.h>980){await page.evaluate(y=>{const w=document.querySelector('#pantalla').contentWindow;w.scrollTo(0,Math.max(0,y-850));},ultimo.rect.y);await dormir(80);fotos.push(await foto(caso,ancho,'pie'));await page.evaluate(()=>document.querySelector('#pantalla').contentWindow.scrollTo(0,0));}
   if(ancho<640&&medicion.tablasConAccionesDesplazables){await page.evaluate(()=>{const d=document.querySelector('#pantalla').contentDocument;for(const e of d.querySelectorAll('div[style*="overflow"],[role=region]'))if(e.querySelector('table button,table a,table input'))e.scrollLeft=e.scrollWidth;});fotos.push(await foto(caso,ancho,'tabla-derecha'));}
   const variantes=[];
   for(const panel of medicion.paneles){
    const vista=caso.vistas.find(v=>!v.includes('/layouts/')&&!v.includes('/components/'))||caso.slug;
    const clave=`${vista}:${panel.id}:${panel.etiquetas.join('|')}:${ancho}`;
    if(variantesVistas.has(clave))continue;variantesVistas.add(clave);
    await cargar(caso,ancho);
    await page.evaluate(id=>{const d=document.querySelector('#pantalla').contentDocument,e=d.getElementById(id);e.hidden=false;e.classList.remove('hidden');e.style.display=id.startsWith('modal')?'flex':'block';e.scrollIntoView({block:'center'});},panel.id);
    await dormir(100);
    variantes.push({panel:panel.id,medicion:await page.evaluate(medir,caso),foto:await foto(caso,ancho,'panel-'+panel.id)});
   }
   resultados.push({slug:caso.slug,rol:caso.rol,ruta:caso.ruta||caso.vista_directa,vistas:caso.vistas,...medicion,fotos,variantes});
  }
  console.log(`${fase}: ${caso.slug}`);
  await guardar(checkpoint,JSON.stringify({fase,fecha:new Date().toISOString(),metodo:'Respuestas reales Laravel + iframe 360/375/1366 en Chrome 1366',casos:resultados},null,2));
 }
 await guardar(`${carpeta}/mediciones-${fase}.json`,JSON.stringify({fase,fecha:new Date().toISOString(),metodo:'Respuestas reales Laravel + iframe 360/375/1366 en Chrome 1366',casos:resultados},null,2));
 console.log(JSON.stringify({fase,pantallas:manifiesto.casos.length,mediciones:resultados.length,desbordes:resultados.filter(r=>r.desborde).map(r=>`${r.slug}:${r.ancho}`)}));
}finally{if(page)await page.close();await browser.close();}
