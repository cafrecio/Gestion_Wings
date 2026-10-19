import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
const dir='docs/06-pruebas/PRU-04/evidencia-t44';
const casos=JSON.parse(fs.readFileSync(`${dir}/respuestas.json`,'utf8'));
fs.mkdirSync(`${dir}/capturas`,{recursive:true});
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-t44-${Date.now()}`)});
let page;const resultados=[];
try {
 await browser.start();page=await browser.newPage();await page.connect();
 await page.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1100,deviceScaleFactor:1,mobile:false});
 await page.goto('http://127.0.0.1:8744/__t44/marco');
 for(const caso of casos)for(const ancho of [360,375,1366]) {
  await page.evaluate(async(slug,ancho)=>{
   let f=document.querySelector('iframe');if(!f){f=document.createElement('iframe');document.body.append(f);}
   f.style.cssText=`display:block;width:${ancho}px;height:1100px;border:0;margin:0 auto`;
   await new Promise((resolve,reject)=>{const t=setTimeout(()=>reject(Error('iframe '+slug)),15000);f.onload=()=>{clearTimeout(t);resolve();};f.src=`/__t44/${slug}.html`;});
   await f.contentDocument.fonts.ready;await new Promise(r=>setTimeout(r,400));
  },caso.slug,ancho);
  const medida=await page.evaluate(()=>{
   const f=document.querySelector('iframe'),w=f.contentWindow,d=f.contentDocument;
   return {ancho:w.innerWidth,contenido:d.documentElement.scrollWidth,alto:d.documentElement.scrollHeight,css:getComputedStyle(d.documentElement).getPropertyValue('--space-card-pad').trim(),texto:d.body.innerText,
    botones:[...d.querySelectorAll('.ds-content button,.ds-content a.ds-btn')].filter(e=>e.getBoundingClientRect().width>0).map(e=>({texto:e.textContent.trim(),x:e.getBoundingClientRect().x,derecha:e.getBoundingClientRect().right,ancho:e.getBoundingClientRect().width,alto:e.getBoundingClientRect().height}))};
  });
  assert.equal(medida.ancho,ancho);assert.equal(medida.css,'16px');assert(medida.contenido<=ancho,caso.slug+': desborde a '+ancho);
  const fotos=[];
  for(const zona of medida.alto>1100?['inicio','pie']:['inicio']){
   if(zona==='pie')await page.evaluate(()=>{const w=document.querySelector('iframe').contentWindow;w.scrollTo(0,w.document.documentElement.scrollHeight);});
   await new Promise(r=>setTimeout(r,80));
   const result=await page.send('Page.captureScreenshot',{format:'png',clip:{x:(1366-ancho)/2,y:0,width:ancho,height:1100,scale:1}});
   const bytes=Buffer.from(result.data,'base64'),archivo=`capturas/${caso.slug}-${ancho}-${zona}.png`;fs.writeFileSync(`${dir}/${archivo}`,bytes);
   fotos.push({archivo,sha256:createHash('sha256').update(bytes).digest('hex')});
  }
  resultados.push({slug:caso.slug,...medida,fotos});console.log(caso.slug+': '+ancho);
 }
 fs.writeFileSync(`${dir}/mediciones.json`,JSON.stringify({metodo:'Respuestas de Laravel en iframe de ancho real',resultados},null,2));
 console.log(JSON.stringify({casos:resultados.length,desbordes:[]}));
} finally {if(page)await page.close();await browser.close();}
