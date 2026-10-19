import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
const dir='docs/06-pruebas/PRU-04/evidencia-t66-codex';
const casos=JSON.parse(fs.readFileSync(`${dir}/respuestas.json`,'utf8')).casos;
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-t66-${Date.now()}`)});
const mediciones=[];let page;
try {
 await browser.start();page=await browser.newPage();await page.connect();
 await page.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1100,deviceScaleFactor:1,mobile:false});
 await page.goto('http://127.0.0.1:8766/__t66/marco');
 fs.mkdirSync(`${dir}/capturas`,{recursive:true});
 for(const slug of casos)for(const ancho of [360,375,1366]) {
  await page.evaluate(async(slug,ancho)=>{
   let f=document.querySelector('iframe');if(!f){f=document.createElement('iframe');document.body.append(f);}
   f.style.cssText=`display:block;width:${ancho}px;height:1100px;border:0;margin:0 auto`;
   await new Promise((resolve,reject)=>{const t=setTimeout(()=>reject(Error('iframe '+slug)),15000);f.onload=()=>{clearTimeout(t);resolve();};f.src=`/__t66/${slug}.html`;});
   await f.contentDocument.fonts.ready;await new Promise(r=>setTimeout(r,500));
   if(slug==='cambiar-profesoras')f.contentDocument.querySelector('#btn-modificar-profesores').click();
  },slug,ancho);
  const m=await page.evaluate(()=>{
   const f=document.querySelector('iframe'),d=f.contentDocument,w=f.contentWindow;
   return {ancho:w.innerWidth,contenido:d.documentElement.scrollWidth,alto:d.documentElement.scrollHeight,
    css:getComputedStyle(d.documentElement).getPropertyValue('--space-card-pad').trim(),
    texto:d.body.innerText,panelVisible:d.querySelector('#panel-profesores')?.style.display,
    botones:[...d.querySelectorAll('.ds-content button,.ds-content a.ds-btn')].filter(e=>e.getBoundingClientRect().width>0).map(e=>({texto:e.textContent.trim(),ancho:e.getBoundingClientRect().width,derecha:e.getBoundingClientRect().right}))};
  });
  assert.equal(m.ancho,ancho);assert.equal(m.css,'16px');assert(m.contenido<=ancho,`${slug} desborda ${ancho}`);
  if(slug.endsWith('error'))assert(m.texto.includes('Seleccioná al menos una profesora activa del deporte de la clase.'));
  if(slug==='cambiar-profesoras')assert.equal(m.panelVisible,'block');
  m.fotos=[];
  for(const zona of m.alto>1100?['inicio','pie']:['inicio']) {
   if(zona==='pie')await page.evaluate(()=>{const w=document.querySelector('iframe').contentWindow;w.scrollTo(0,w.document.documentElement.scrollHeight);});
   const res=await page.send('Page.captureScreenshot',{format:'png',clip:{x:(1366-ancho)/2,y:0,width:ancho,height:1100,scale:1}});
   const archivo=`capturas/${slug}-${ancho}-${zona}.png`;fs.writeFileSync(`${dir}/${archivo}`,Buffer.from(res.data,'base64'));m.fotos.push(archivo);
  }
  mediciones.push({slug,...m});console.log(slug+': '+ancho);
 }
 fs.writeFileSync(`${dir}/mediciones.json`,JSON.stringify(mediciones,null,2));
} finally {if(page)await page.close();await browser.close();}
