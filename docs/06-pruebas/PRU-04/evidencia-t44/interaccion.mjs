import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
const dir='docs/06-pruebas/PRU-04/evidencia-t44';
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-t44-interaccion-${Date.now()}`)});
let page;const resultados=[];
try{
 await browser.start();page=await browser.newPage();await page.connect();
 await page.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1100,deviceScaleFactor:1,mobile:false});
 await page.goto('http://127.0.0.1:8744/__t44/marco');
 for(const ancho of [360,375,1366]){
  await page.evaluate(async(ancho)=>{
   let f=document.querySelector('iframe');if(!f){f=document.createElement('iframe');document.body.append(f);}
   f.style.cssText=`display:block;width:${ancho}px;height:1100px;border:0;margin:0 auto`;
   await new Promise((resolve,reject)=>{const t=setTimeout(()=>reject(Error('iframe')),15000);f.onload=()=>{clearTimeout(t);resolve();};f.src='/__t44/cobrar-parcial.html';});
   await f.contentDocument.fonts.ready;await new Promise(r=>setTimeout(r,500));
  },ancho);
  const datos=await page.evaluate(()=>{
   const d=document.querySelector('iframe').contentDocument,w=d.defaultView;
   const medio=d.querySelector('#tipo_caja_id');medio.value=[...medio.options].find(o=>o.value!=='').value;medio.dispatchEvent(new w.Event('change',{bubbles:true}));
   const radio=[...d.querySelectorAll('input[name="nuevo_plan_id"]')].find(r=>Number(r.dataset.precio)===60000);
   const chk=d.querySelector('input[type="checkbox"][data-periodo="2026-10"]');
   if(!radio||!chk)throw Error('Falta escenario de subida y cuota parcial');
   if(!chk.checked)chk.click();
   const input=d.querySelector('.monto-cuota[data-periodo="2026-10"]');
   const antes=Number(input.value.replaceAll(',',''));
   radio.click();
   return {antes,despues:Number(input.value.replaceAll(',','')),saldo:Number(chk.dataset.saldo),pagado:Number(chk.dataset.pagado),precio:Number(radio.dataset.precioMes),recargo:Number(chk.dataset.recargo||0),totalVisible:d.querySelector('#total-label').textContent,botonHabilitado:!d.querySelector('#btn-cobrar').disabled};
  });
  assert.equal(datos.antes,30000);assert.equal(datos.despues,50000);assert.equal(datos.saldo,50000);assert.equal(datos.pagado,10000);assert.equal(datos.precio,60000);assert(datos.botonHabilitado);assert.equal(Number(datos.totalVisible.replace(/[^0-9.]/g,'')),50000+datos.recargo);
  await page.evaluate(()=>{const w=document.querySelector('iframe').contentWindow,b=w.document.querySelector('#btn-cobrar');w.scrollTo(0,Math.max(0,b.getBoundingClientRect().bottom+w.scrollY-1080));});
  await new Promise(r=>setTimeout(r,100));
  const foto=await page.send('Page.captureScreenshot',{format:'png',clip:{x:(1366-ancho)/2,y:0,width:ancho,height:1100,scale:1}});
  const bytes=Buffer.from(foto.data,'base64'),archivo=`capturas/subida-parcial-seleccionada-${ancho}.png`;fs.writeFileSync(`${dir}/${archivo}`,bytes);
  resultados.push({ancho,...datos,archivo,sha256:createHash('sha256').update(bytes).digest('hex')});
 }
 fs.writeFileSync(`${dir}/interaccion.json`,JSON.stringify({alcance:'Seleccion real de radio en Chrome; no confirma pago. Persistencia cubierta por PrecioPlanVigenciaT44Test.',resultados},null,2));
 console.log(JSON.stringify(resultados));
}finally{if(page)await page.close();await browser.close();}
