import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
import {medir} from './medicion.mjs';
const raiz='docs/06-pruebas/PRU-04/evidencia-t75-consistencia';
const casos=JSON.parse(fs.readFileSync(`${raiz}/respuestas-despues.json`,'utf8')).casos;
if(fs.existsSync(`${raiz}/respuestas-complementos.json`))casos.push(...JSON.parse(fs.readFileSync(`${raiz}/respuestas-complementos.json`,'utf8')).casos);
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-t75-sondeo-${Date.now()}`)});
const resultados=[],variantes=[];let page;
try {
 await browser.start();page=await browser.newPage();await page.connect();
 await page.send('Network.enable');await page.send('Network.setBypassServiceWorker',{bypass:true});
 await page.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1000,deviceScaleFactor:1,mobile:false});
 await page.goto('http://127.0.0.1:8735/__t75/marco');
 for(const caso of casos)for(const ancho of [360,375,1366]){
  await page.evaluate(async(slug,ancho)=>{
   let f=document.querySelector('iframe');if(!f){f=document.createElement('iframe');f.id='pantalla';document.body.append(f);}
   f.style.cssText=`display:block;width:${ancho}px;height:1000px;border:0;margin:0 auto`;
   await new Promise(resolve=>{f.onload=resolve;f.src=`http://127.0.0.1:8735/__t75/despues/${slug}.html`;});
   await f.contentDocument.fonts.ready;
  },caso.slug,ancho);
  assert.equal(await page.evaluate('getComputedStyle(document.querySelector("#pantalla").contentDocument.documentElement).getPropertyValue("--space-card-pad").trim()'),'16px');
  const principal=await page.evaluate(medir,caso);
  resultados.push({slug:caso.slug,...principal});
  if(ancho<640&&principal.tablasConAccionesDesplazables){
   await page.evaluate(()=>{const d=document.querySelector('#pantalla').contentDocument;for(const e of d.querySelectorAll('div[style*="overflow"],[role=region]'))if(e.querySelector('table button,table a,table input'))e.scrollLeft=e.scrollWidth;});
   variantes.push({slug:caso.slug+' :: tabla-derecha',tipo:'tabla',...await page.evaluate(medir,caso)});
  }
  for(const panel of principal.paneles){
   const previo=await page.evaluate(id=>{const d=document.querySelector('#pantalla').contentDocument,e=d.getElementById(id),b=d.getElementById(id.replace('form-','botones-'));const previo={style:e.getAttribute('style'),clase:e.className,hidden:e.hidden,botones:b?.getAttribute('style')};e.hidden=false;e.classList.remove('hidden');e.style.display=id.startsWith('modal')?'flex':'block';if(id.startsWith('form-')&&b)b.style.display='none';return previo;},panel.id);
   variantes.push({slug:caso.slug+' :: '+panel.id,tipo:'panel',...await page.evaluate(medir,caso)});
   await page.evaluate((id,p)=>{const d=document.querySelector('#pantalla').contentDocument,e=d.getElementById(id),b=d.getElementById(id.replace('form-','botones-'));if(p.style===null)e.removeAttribute('style');else e.setAttribute('style',p.style);e.className=p.clase;e.hidden=p.hidden;if(id.startsWith('form-')&&b){if(p.botones===null)b.removeAttribute('style');else b.setAttribute('style',p.botones);}},panel.id,previo);
  }
 }
 fs.writeFileSync(`${raiz}/sondeo-despues.json`,JSON.stringify({casos:resultados,variantes},null,2));
 const tamanos=[],bordes=[];
 for(const c of [...resultados,...variantes].filter(c=>c.ancho<640))for(const z of c.zonas)for(const f of z.filas){
  const bs=f.controles.filter(b=>b.esAccion&&b.textoVisible&&!/^[✕×]$/.test(b.textoVisible));
  for(const b of bs)if(Math.abs(b.rect.w-96)>.6||Math.abs(b.rect.h-32)>.6)tamanos.push([c.slug,c.ancho,b.etiqueta,b.rect]);
  if(bs.length&&(!bs.some(b=>b.categoria==='fila-tabla')||c.tipo==='tabla')){
   const delta=Math.max(...f.controles.map(b=>b.rect.derecha))-c.referenciaDerecha;
   if(Math.abs(delta)>1)bordes.push([c.slug,c.ancho,bs.map(b=>b.etiqueta).join('/'),Math.round(delta*100)/100,z.clases]);
  }
 }
 console.log(JSON.stringify({mediciones:resultados.length,variantes:variantes.length,tamanos,bordes,desbordes:[...resultados,...variantes].filter(c=>c.desborde).map(c=>[c.slug,c.ancho])}));
}finally{if(page)await page.close();await browser.close();}
