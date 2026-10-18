import {medir} from './medicion.mjs';
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
const fase=process.argv[2]||'antes';assert(['antes','despues'].includes(fase));
const carpeta='docs/06-pruebas/PRU-04/evidencia-t75-observaciones';
const archivoFase=process.env.T75_MANIFIESTO||fase;
const manifiesto=JSON.parse(fs.readFileSync(`${carpeta}/respuestas-${archivoFase}.json`,'utf8'));
const salida=path.resolve(`storage/app/t75-observaciones-runtime/app/capturas/${fase}`);fs.mkdirSync(salida,{recursive:true});
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-t75-${Date.now()}`)});
const base='http://127.0.0.1:8735';
const filtro=process.env.T75_SOLO?process.env.T75_SOLO.split(',').filter(Boolean):null;
const checkpoint=path.resolve(`storage/app/t75-observaciones-runtime/app/mediciones-${archivoFase}.json`);
const continuar=process.env.T75_CONTINUAR==='1';
let page;const resultados=continuar?JSON.parse(fs.readFileSync(fs.existsSync(checkpoint)?checkpoint:`${carpeta}/mediciones-${archivoFase}.json`,'utf8')).casos.filter(c=>!filtro?.includes(c.slug)):[];const variantesVistas=new Set();
const completados=new Set(resultados.filter(c=>resultados.filter(r=>r.slug===c.slug).length===3).map(c=>c.slug));
for(const c of resultados)for(const v of c.variantes){const p=c.paneles.find(p=>p.id===v.panel);if(p){const vista=c.vistas.find(v=>!v.includes('/layouts/')&&!v.includes('/components/'))||c.slug;variantesVistas.add(`${vista}:${p.id}:${p.etiquetas.join('|')}:${c.ancho}`);}}
const dormir=ms=>new Promise(r=>setTimeout(r,ms));
async function guardar(destino,texto){for(let i=0;i<6;i++){try{await fs.promises.writeFile(destino,texto);return;}catch(e){if(i===5)throw e;await dormir(150*(i+1));}}}


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
 assert.equal(await page.evaluate('getComputedStyle(document.querySelector("#pantalla").contentDocument.documentElement).getPropertyValue("--space-card-pad").trim()'),'16px','La respuesta debe cargar el CSS real de Wings');
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
   let tablaDerecha=null;
   if(ancho<640&&medicion.tablasConAccionesDesplazables){await page.evaluate(()=>{const d=document.querySelector('#pantalla').contentDocument;for(const e of d.querySelectorAll('div[style*="overflow"],[role=region]'))if(e.querySelector('table button,table a,table input')){e.scrollLeft=e.scrollWidth;e.scrollIntoView({block:'center'});}});tablaDerecha=await page.evaluate(medir,caso);fotos.push(await foto(caso,ancho,'tabla-derecha'));}
   if(medicion.altoDocumento>1001){await page.evaluate(()=>{const w=document.querySelector('#pantalla').contentWindow;w.scrollTo(0,w.document.documentElement.scrollHeight);});await dormir(80);fotos.push(await foto(caso,ancho,'final'));}
   const variantes=[];
   for(const panel of medicion.paneles){
    const vista=caso.vistas.find(v=>!v.includes('/layouts/')&&!v.includes('/components/'))||caso.slug;
    const clave=`${vista}:${panel.id}:${panel.etiquetas.join('|')}:${ancho}`;
    if(variantesVistas.has(clave))continue;variantesVistas.add(clave);
    await cargar(caso,ancho);
    await page.evaluate(id=>{const d=document.querySelector('#pantalla').contentDocument,e=d.getElementById(id);e.hidden=false;e.classList.remove('hidden');e.style.display=id.startsWith('modal')?'flex':'block';if(id.startsWith('form-')){const b=d.getElementById(id.replace('form-','botones-'));if(b)b.style.display='none';}e.scrollIntoView({block:'center'});},panel.id);
    await dormir(100);
    variantes.push({panel:panel.id,medicion:await page.evaluate(medir,caso),foto:await foto(caso,ancho,'panel-'+panel.id)});
   }
   resultados.push({slug:caso.slug,rol:caso.rol,ruta:caso.ruta||caso.vista_directa,vistas:caso.vistas,...medicion,tablaDerecha,fotos,variantes});
  }
  console.log(`${fase}: ${caso.slug}`);
  await guardar(checkpoint,JSON.stringify({fase,fecha:new Date().toISOString(),metodo:'Respuestas reales Laravel + iframe 360/375/1366 en Chrome 1366',casos:resultados},null,2));
 }
 await guardar(`${carpeta}/mediciones-${archivoFase}.json`,JSON.stringify({fase,fecha:new Date().toISOString(),metodo:'Respuestas reales Laravel + iframe 360/375/1366 en Chrome 1366',casos:resultados},null,2));
 console.log(JSON.stringify({fase,pantallas:manifiesto.casos.length,mediciones:resultados.length,desbordes:resultados.filter(r=>r.desborde).map(r=>`${r.slug}:${r.ancho}`)}));
}finally{if(page)await page.close();await browser.close();}
