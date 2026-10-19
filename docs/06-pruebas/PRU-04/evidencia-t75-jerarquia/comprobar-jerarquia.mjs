import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
const negativos=process.argv.includes('--negativos');
const root='docs/06-pruebas/PRU-04/evidencia-t75-jerarquia';
const casos=JSON.parse(fs.readFileSync(root+'/respuestas-despues.json','utf8')).casos.filter(c=>c.vistas.some(v=>/views\/(caja\/(detalle|resumen)|cobranza\/index|admin\/dashboard|reportes\/(index|sueldos)|cashflow\/index|liquidaciones\/show)\.blade/.test(v)));
const extras=JSON.parse(fs.readFileSync(root+'/respuestas-comision.json','utf8')).casos;
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-jerarquia-${Date.now()}`)});
let page;const resultados=[];
try{
 await browser.start();page=await browser.newPage();await page.connect();
 await page.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1000,deviceScaleFactor:1,mobile:false});await page.goto('http://127.0.0.1:8735/__t75/marco');
 for(const c of [...casos,...extras])for(const ancho of [360,375,1366]){
  await page.evaluate(async(slug,ancho)=>{let f=document.querySelector('#pantalla');if(!f){f=document.createElement('iframe');f.id='pantalla';document.body.append(f);}f.style.cssText=`display:block;width:${ancho}px;height:1000px;border:0;margin:auto`;await new Promise(r=>{f.onload=r;f.src='/__t75/despues/'+slug+'.html';});await f.contentDocument.fonts.ready;},c.slug,ancho);
  const datos=await page.evaluate(negativo=>{
   const w=document.querySelector('#pantalla').contentWindow,d=w.document;
   for(const details of d.querySelectorAll('details'))details.open=true;
   const cifras=[...d.querySelectorAll('.ds-money')].filter(e=>e.getBoundingClientRect().width);
   const estilos=cifras.map(e=>({nivel:e.className.match(/ds-money--(\w+)/)[1],tamano:parseFloat(w.getComputedStyle(e).fontSize),peso:w.getComputedStyle(e).fontWeight,color:w.getComputedStyle(e).color,texto:e.textContent}));
   const antes=d.documentElement.scrollWidth>w.innerWidth;
   for(const e of cifras)e.textContent=(negativo?'−':'')+'$9,999,999,999.99';
   const fuera=[];
   for(const e of cifras){const range=d.createRange();range.selectNodeContents(e);const r=range.getBoundingClientRect();for(let p=e.parentElement;p&&p!==d.body;p=p.parentElement){const s=w.getComputedStyle(p),pr=p.getBoundingClientRect();if(!pr.width||s.display==='inline'||s.display==='contents')continue;if(s.overflowX==='auto'||s.overflowX==='scroll')break;if(r.right>pr.right+1||r.left<pr.left-1){fuera.push({nivel:e.className,padre:p.tagName+'.'+p.className,ancho:pr.width,derecha:r.right,limite:pr.right});break;}}}
   const main=d.querySelector('.ds-content'),ms=w.getComputedStyle(main);
   const referencia=main.getBoundingClientRect().right-parseFloat(ms.paddingRight)-24;
   const bordes=w.innerWidth<641?[...d.querySelectorAll('.cashflow-mobile-cards .alumno-actions')].map(e=>e.getBoundingClientRect().right-referencia):[];
   return {estilos,antes,despues:d.documentElement.scrollWidth>w.innerWidth,fuera,bordes};
  },negativos);
  for(const e of datos.estilos){assert(Math.abs(e.tamano-({total:17.6,summary:14.4,row:12.8}[e.nivel]))<.1);assert.equal(e.peso,({total:'800',summary:'700',row:'600'}[e.nivel]));}
  assert(datos.bordes.every(b=>Math.abs(b)<1),'Borde comun de acciones Cashflow');assert(!datos.antes&&!datos.despues,`${c.slug}:${ancho} sin desborde`);assert.deepEqual(datos.fuera,[],`${c.slug}:${ancho} dentro de contenedores`);
  resultados.push({slug:c.slug,ancho,...datos});
 }
 fs.writeFileSync(root+(negativos?'/jerarquia-negativos.json':'/jerarquia.json'),JSON.stringify({negativos,metodo:'Respuestas reales y detalles abiertos; estres DOM sintetico sin guardar',resultados},null,2));
 console.log(JSON.stringify({casos:resultados.length,desbordes:resultados.filter(c=>c.antes||c.despues).map(c=>[c.slug,c.ancho]),contenedores:resultados.filter(c=>c.fuera.length).map(c=>[c.slug,c.ancho,c.fuera])}));
}finally{if(page)await page.close();await browser.close();}
