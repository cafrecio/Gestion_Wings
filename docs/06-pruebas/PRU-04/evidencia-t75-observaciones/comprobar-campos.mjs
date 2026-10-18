import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
const root='docs/06-pruebas/PRU-04/evidencia-t75-observaciones';
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-t75-campos-${Date.now()}`)});
let page;const resultados=[];
try{
 await browser.start();page=await browser.newPage();await page.connect();
 await page.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1000,deviceScaleFactor:1,mobile:false});
 await page.goto('http://127.0.0.1:8735/__t75/marco');
 for(const ancho of [360,375])for(const slug of ['admin-clases-create','admin-clases-id-edit','admin-liquidaciones-id']){
  await page.evaluate(async(slug,ancho)=>{document.body.innerHTML='';const f=document.createElement('iframe');f.id='pantalla';f.style.cssText=`display:block;width:${ancho}px;height:1000px;border:0;margin:auto`;document.body.append(f);await new Promise(r=>{f.onload=r;f.src='/__t75/despues/'+slug+'.html';});await f.contentDocument.fonts.ready;},slug,ancho);
  const mediciones=await page.evaluate(slug=>{
   const w=document.querySelector('#pantalla').contentWindow,d=w.document;
   const campos=()=>[...d.querySelectorAll('input.w-full[type=date],input.w-full[type=time]')].filter(e=>e.getBoundingClientRect().width&&w.getComputedStyle(e).visibility!=='hidden').map(e=>{const s=w.getComputedStyle(e.parentElement);return {id:e.id,ancho:e.getBoundingClientRect().width,disponible:e.parentElement.clientWidth-parseFloat(s.paddingLeft)-parseFloat(s.paddingRight),max:w.getComputedStyle(e).maxWidth};});
   const antes=campos();const recurrente=d.querySelector('input[value=recurrente]');if(recurrente)recurrente.click();
   const despues=campos();const cifras=[...d.querySelectorAll('.ds-money-value')].map(e=>({fuente:w.getComputedStyle(e).font,color:w.getComputedStyle(e).color,tamano:parseFloat(w.getComputedStyle(e).fontSize)}));
   const conteos=[...d.querySelectorAll('.ds-count-value')].map(e=>parseFloat(w.getComputedStyle(e).fontSize));
   // Estrés de maquetación únicamente: textos sintéticos sobre el HTML real, sin guardar.
   for(const e of d.querySelectorAll('.ds-money-value'))e.textContent='$9,999,999,999.99';
   const desborde=d.documentElement.scrollWidth>w.innerWidth;
   const recortes=[...d.querySelectorAll('.ds-money-value')].filter(e=>e.getBoundingClientRect().right>w.innerWidth||e.getBoundingClientRect().left<0).length;
   return {antes,despues,cifras,conteos,desborde,recortes};
  },slug);
  for(const c of [...mediciones.antes,...mediciones.despues]){assert.equal(c.max,'none');assert(Math.abs(c.ancho-c.disponible)<2,`${slug} ${c.id}: ${c.ancho}/${c.disponible}`);}
  assert(new Set(mediciones.cifras.map(e=>e.fuente+'|'+e.color)).size<=1,'Importes uniformes');
  if(mediciones.cifras.length)assert(mediciones.conteos.every(n=>n<=mediciones.cifras[0].tamano),'Conteo secundario');
  assert(!mediciones.desborde&&mediciones.recortes===0,'Importes grandes completos');
  resultados.push({slug,ancho,...mediciones});
 }
 fs.writeFileSync(root+'/campos-cifras.json',JSON.stringify({resultados},null,2));console.log('Fechas únicas/recurrentes, horas, importes iguales y montos grandes: seis casos correctos.');
}finally{if(page)await page.close();await browser.close();}
