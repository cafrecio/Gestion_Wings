import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
const raiz='docs/06-pruebas/PRU-04/evidencia-t75';
const archivo=`${raiz}/mediciones-despues.json`,mediciones=JSON.parse(fs.readFileSync(archivo,'utf8'));
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-t75-recaudacion-${Date.now()}`)});
let page;
try {
 await browser.start();page=await browser.newPage();await page.connect();
 await page.send('Network.enable');await page.send('Network.setBypassServiceWorker',{bypass:true});
 await page.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1000,deviceScaleFactor:1,mobile:false});
 await page.goto('http://127.0.0.1:8735/__t75/marco');
 for(const slug of ['operativo-0','operativo-turno-antiguo','operativo-ultimo-cierre'])for(const ancho of [360,375]){
  const datos=await page.evaluate(async(slug,ancho)=>{
   let f=document.querySelector('iframe');if(!f){f=document.createElement('iframe');document.body.append(f);}
   f.style.cssText=`display:block;width:${ancho}px;height:1000px;border:0;margin:0 auto`;
   await new Promise(resolve=>{f.onload=resolve;f.src=`http://127.0.0.1:8735/__t75/despues/${slug}.html`;});
   const d=f.contentDocument,w=f.contentWindow;await d.fonts.ready;
   const g=d.querySelector('.resumen-operativo-grid');if(!g)throw Error('Sin recaudación real');
   w.scrollTo(0,d.documentElement.scrollHeight);await new Promise(resolve=>setTimeout(resolve,300));
   return {ancho:w.innerWidth,columnas:w.getComputedStyle(g).gridTemplateColumns,desborde:d.documentElement.scrollWidth>w.innerWidth+1};
  },slug,ancho);
  assert.equal(datos.ancho,ancho);assert(!datos.desborde);assert(!datos.columnas.includes(' '));
  const nombre=`${slug}--${ancho}--recaudacion.png`;
  const foto=await page.send('Page.captureScreenshot',{format:'png',clip:{x:(1366-ancho)/2,y:0,width:ancho,height:1000,scale:1}});
  const png=Buffer.from(foto.data,'base64');fs.writeFileSync(`storage/app/t75-runtime/app/capturas/despues/${nombre}`,png);
  const c=mediciones.casos.find(c=>c.slug===slug&&c.ancho===ancho);assert(c);
  c.fotos=c.fotos.filter(f=>f.nombre!==nombre);c.fotos.push({nombre,sha256:createHash('sha256').update(png).digest('hex')});
 }
 for(const slug of ['admin-revision-cobranza','operativo-9'])for(const ancho of [360,375,1366]){
  const datos=await page.evaluate(async(slug,ancho)=>{
   const f=document.querySelector('iframe');f.style.width=ancho+'px';
   await new Promise(resolve=>{f.onload=resolve;f.src=`http://127.0.0.1:8735/__t75/despues/${slug}.html`;});
   const d=f.contentDocument,w=f.contentWindow;await d.fonts.ready;
   const id=d.querySelector('[data-abrir-revision]').dataset.abrirRevision,p=d.getElementById('form-'+id);
   p.style.display='block';d.getElementById('botones-'+id).style.display='none';
   p.scrollIntoView({block:'center'});await new Promise(resolve=>setTimeout(resolve,300));
   const fila=p.querySelector('button').parentElement,b=[...fila.querySelectorAll('button')].map(e=>{const r=e.getBoundingClientRect();return {texto:e.textContent.trim(),x:r.x,y:r.y,derecha:r.right};});
   return {panel:p.id,ancho:w.innerWidth,desborde:d.documentElement.scrollWidth>w.innerWidth+1,borde:fila.getBoundingClientRect().right,botones:b};
  },slug,ancho);
  assert.equal(datos.ancho,ancho);assert(!datos.desborde);
  if(ancho<640)assert(Math.abs(datos.botones.at(-1).derecha-datos.borde)<1);
  const nombre=`${slug}--${ancho}--panel-${datos.panel}.png`;
  const foto=await page.send('Page.captureScreenshot',{format:'png',clip:{x:(1366-ancho)/2,y:0,width:ancho,height:1000,scale:1}});
  const png=Buffer.from(foto.data,'base64');fs.writeFileSync(`storage/app/t75-runtime/app/capturas/despues/${nombre}`,png);
  const c=mediciones.casos.find(c=>c.slug===slug&&c.ancho===ancho);c.variantes=c.variantes.filter(v=>v.panel!==datos.panel);
  c.variantes.push({panel:datos.panel,medicion:datos,foto:{nombre,sha256:createHash('sha256').update(png).digest('hex')}});
 }
 fs.writeFileSync(archivo,JSON.stringify(mediciones,null,2));
 console.log('12 capturas adicionales: recaudación y resolución de Revisión, sin desborde.');
} finally {if(page)await page.close();await browser.close();}
