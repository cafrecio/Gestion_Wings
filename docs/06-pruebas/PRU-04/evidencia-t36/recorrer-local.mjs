import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';
const fixture=JSON.parse(fs.readFileSync('storage/app/t36-runtime/app/t36-local.json','utf8'));
const base='http://127.0.0.1:8736';
const out='docs/06-pruebas/PRU-04/evidencia-t36';
const browser=new ChromeBrowser({userDataDir:path.resolve('storage/app/chrome-t36-'+Date.now())});
let page,frame;
const resultados=[];
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
async function visitar(ruta,ancho=360){
 await page.send('Page.bringToFront');
 await page.send('Emulation.setDeviceMetricsOverride',{width:ancho,height:1000,deviceScaleFactor:1,mobile:false});
 await page.goto(base+ruta);
}
async function esperar(fn){for(let i=0;i<50;i++){if(await page.evaluate(fn))return;await sleep(100);}throw Error('Condición no cumplida: '+fn);}
async function captura(nombre,ancho,derecha=false){
 await page.send('Page.bringToFront');
 let origen=page,opts={format:'png'};
 if(ancho<640){
  const html=await page.evaluate('document.documentElement.outerHTML');
  await frame.evaluate((html,ancho)=>{document.body.replaceChildren();document.body.style.margin='0';const f=document.createElement('iframe');f.style.cssText=`display:block;width:${ancho}px;height:1000px;border:0;margin:0 auto`;f.srcdoc=html;document.body.append(f);},html,ancho);
  await sleep(600);
  assert.equal(await frame.evaluate('document.querySelector("iframe").contentWindow.innerWidth'),ancho);
  if(derecha)assert(await frame.evaluate(()=>{const r=document.querySelector('iframe').contentDocument.querySelector('[role=region]');r.scrollLeft=r.scrollWidth;return r.scrollLeft>0;}));
  origen=frame;opts.clip={x:(1366-ancho)/2,y:0,width:ancho,height:1000,scale:1};
 }
 await origen.send('Page.bringToFront');
 const shot=await origen.send('Page.captureScreenshot',opts);
 fs.writeFileSync(`${out}/${nombre}-${ancho}.png`,Buffer.from(shot.data,'base64'));
}
async function login(rol){
 await visitar('/login');await page.type('input[name=email]',fixture.usuarios[rol]);await page.type('input[name=password]',fixture.password);await page.click('button[type=submit]');
 assert(!await page.evaluate('location.pathname === "/login"'));
}
async function salir(){await page.evaluate(()=>document.querySelector('form[action$="/logout"]').submit());await sleep(600);}
try{
 await browser.start();page=await browser.newPage();await page.connect();frame=await browser.newPage();await frame.connect();
 await frame.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1000,deviceScaleFactor:1,mobile:false});await frame.goto(base+'/login');
 await visitar('/login');assert(await page.evaluate('document.documentElement.scrollWidth <= innerWidth'));await captura('login-control',360);
 await login('ADMIN');
 const detalle=`/caja/${fixture.cerrada}/detalle`;
 await visitar(detalle);
 assert.equal(await page.evaluate('document.querySelectorAll(".mov-check").length'),2);
 await page.click('.mov-check');await esperar('document.querySelector(".mov-check").dataset.guardado === "1" && !document.querySelector(".mov-check").disabled');
 await visitar(detalle);assert(await page.evaluate('document.querySelector(".mov-check").checked'));resultados.push('Tilde guardado después de recargar');
 await page.click('#btn-validar');await esperar('document.body.textContent.includes("Falta 1 movimiento por verificar")');
 assert.equal(await page.evaluate('location.pathname'),detalle);resultados.push('Detalle rechaza validar con uno faltante');
 await captura('detalle-falta-uno',360);
 for(const ancho of [360,375,1366]){await visitar(detalle,ancho);assert(await page.evaluate('document.documentElement.scrollWidth <= innerWidth'));await captura('detalle-uno-verificado',ancho);if(ancho<640)await captura('detalle-tildes-derecha',ancho,true);}
 for(const ancho of [375,1366]){await visitar(`/caja/${fixture.cerrada}/resumen`,ancho);await captura('resumen',ancho);}
 await visitar(`/caja/${fixture.cerrada}/resumen`);
 await page.click('form[action$="/validar"] button');await esperar('document.body.textContent.includes("Falta 1 movimiento por verificar")');
 assert.equal(await page.evaluate('location.pathname'),detalle);resultados.push('Resumen lleva al Detalle con cantidad faltante');
 for(const ruta of [detalle,`/caja/${fixture.cerrada}/resumen`]){
  await visitar(ruta);await page.click('[data-abrir-rechazar]');assert.equal(await page.evaluate('document.querySelector("#modal-rechazar").style.display'),'flex');
  await captura(ruta.endsWith('resumen')?'rechazo-resumen':'rechazo-detalle',360);
  await page.click('[data-cerrar-rechazar]');assert.equal(await page.evaluate('document.querySelector("#modal-rechazar").style.display'),'none');
  await page.click('[data-abrir-rechazar]');await page.click('#modal-rechazar');assert.equal(await page.evaluate('document.querySelector("#modal-rechazar").style.display'),'none');
 }
 resultados.push('Rechazo abre y cierra con botón y fondo en ambas vistas');
 await visitar(detalle);
 await page.send('Network.enable');await page.send('Network.emulateNetworkConditions',{offline:true,latency:0,downloadThroughput:0,uploadThroughput:0});
 await page.click('.mov-check');await esperar('!document.querySelector("#verificacion-error").hidden');
 assert(await page.evaluate('document.querySelector(".mov-check").checked && !document.querySelector("#btn-validar").disabled'));
 assert(await page.evaluate('document.querySelector("#verificacion-error").textContent.includes("Revisá la conexión")'));
 await page.send('Network.emulateNetworkConditions',{offline:false,latency:0,downloadThroughput:-1,uploadThroughput:-1});
 resultados.push('Fallo de red revierte la casilla y muestra error en castellano');
 await visitar(detalle);
 // Los dos inputs son hijos de celdas distintas: marcar expresamente el pendiente.
 await page.evaluate(()=>{const c=[...document.querySelectorAll('.mov-check')].find(c=>!c.checked&&!c.disabled);if(c)c.click();});
 await esperar('[...document.querySelectorAll(".mov-check")].every(c=>!c.disabled && c.dataset.guardado === "1")');
 await page.click('#btn-validar');await esperar('location.pathname === "/caja"');resultados.push('Todos tildados permiten validar');
 await salir();await login('OPERATIVO');await visitar(`/caja/${fixture.abierta}/detalle`);
 assert.equal(await page.evaluate('document.querySelectorAll(".mov-check").length'),0);
 await page.click('[data-abrir-cancelar]');assert.equal(await page.evaluate('document.querySelector("#modal-cancelar").style.display'),'flex');
 assert(await page.evaluate('document.querySelector("#form-cancelar").action.includes("/cancelar")'));
 await captura('cancelacion-operativa',360);await page.click('[data-cerrar-cancelar]');
 assert.equal(await page.evaluate('document.querySelector("#modal-cancelar").style.display'),'none');
 await page.click('[data-abrir-cancelar]');await page.click('#modal-cancelar');assert.equal(await page.evaluate('document.querySelector("#modal-cancelar").style.display'),'none');
 resultados.push('Operativa sin casillas; cancelación abre y cierra desde archivo externo');
 let dialogos=0;page.on('Page.javascriptDialogOpening',()=>{dialogos++;page.send('Page.handleJavaScriptDialog',{accept:false});});
 const antes=await page.evaluate('document.querySelectorAll("form[data-confirmar-eliminacion]").length');
 await page.click('form[data-confirmar-eliminacion] button');assert.equal(dialogos,1);
 assert.equal(await page.evaluate('document.querySelectorAll("form[data-confirmar-eliminacion]").length'),antes);
 assert(!await page.evaluate('document.querySelector("form[data-confirmar-eliminacion]").dataset.submitted === "1"'));
 resultados.push('Eliminar pide confirmación y Cancelar permite seguir usando el formulario');
 const capturas=fs.readdirSync(out).filter(n=>n.endsWith('.png')).sort().map(nombre=>({nombre,sha256:createHash('sha256').update(fs.readFileSync(`${out}/${nombre}`)).digest('hex')}));
 fs.writeFileSync(`${out}/resultado.json`,JSON.stringify({fecha:new Date().toISOString(),base:'wings_testing_codex',resultados,capturas},null,2));console.log(JSON.stringify(resultados));
}finally{if(page)await page.close();if(frame)await frame.close();await browser.close();}
