import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {ChromeBrowser} from '../../../../scripts/pruebas/cdp-helper.mjs';

const destino='docs/06-pruebas/PRU-04/evidencia-t75-observaciones';
const browser=new ChromeBrowser({userDataDir:path.resolve(`storage/app/chrome-t75-revision-${Date.now()}`)});
let page;
const resultados=[];
try {
    await browser.start(); page=await browser.newPage(); await page.connect();
    await page.send('Emulation.setDeviceMetricsOverride',{width:1366,height:1000,deviceScaleFactor:1,mobile:false});
    await page.goto('http://127.0.0.1:8735/__t75/marco');
    for (const ancho of [360,375,1366]) {
        await page.evaluate(async ancho=>{
            document.body.innerHTML='';
            const f=document.createElement('iframe'); f.id='pantalla';
            f.style.cssText=`display:block;width:${ancho}px;height:1000px;border:0;margin:auto`;
            document.body.append(f);
            await new Promise(resolve=>{f.onload=resolve;f.src='/__t75/despues/admin-revision-cobranza.html';});
            await f.contentDocument.fonts.ready;
        },ancho);
        const comprobar=await page.evaluate(()=>{
            const d=document.querySelector('#pantalla').contentDocument;
            const form=d.querySelector('.revision-resolucion');
            const input=form.querySelector('[role=switch]');
            let envios=[];
            form.addEventListener('submit',e=>{e.preventDefault();envios.push(Object.fromEntries(new FormData(form)));});
            const estados=[];
            for(const activo of [true,false,true,false]) {
                if(input.checked!==activo) input.click();
                estados.push({activo:input.checked,tipo:form.elements.resolucion.value,aria:input.getAttribute('aria-checked'),aviso:form.querySelector('[data-revision-consecuencia]').textContent,envios:envios.length});
            }
            form.requestSubmit();
            const sinNota=envios.length;
            form.elements.nota_resolucion.value='Contacto confirmado para la prueba de interfaz.';
            form.requestSubmit();
            input.click(); form.requestSubmit();
            form.scrollIntoView({block:'center'});
            return {estados,sinNota,envios:envios.map(e=>({resolucion:e.resolucion,nota:e.nota_resolucion})),ancho:d.defaultView.innerWidth};
        });
        assert.equal(comprobar.ancho,ancho);
        assert.equal(comprobar.sinNota,0,'La nota sigue siendo obligatoria');
        for(const e of comprobar.estados) {
            assert.equal(e.tipo,e.activo?'CONTINUA':'INACTIVO');
            assert.equal(e.aria,String(e.activo));
            assert.equal(e.envios,0,'Cambiar el interruptor no envía');
            assert.match(e.aviso,e.activo?/Se generará la cuota/:/deudas anteriores se conservan/);
        }
        assert.deepEqual(comprobar.envios.map(e=>e.resolucion),['INACTIVO','CONTINUA']);
        resultados.push(comprobar);
        const foto=await page.send('Page.captureScreenshot',{format:'png',clip:{x:(1366-ancho)/2,y:0,width:ancho,height:1000,scale:1}});
        fs.writeFileSync(`${destino}/revision-interruptor-${ancho}.png`,Buffer.from(foto.data,'base64'));
    }
    fs.writeFileSync(`${destino}/interruptor.json`,JSON.stringify({metodo:'HTML real Laravel; eventos nativos; submit interceptado sin escrituras',resultados},null,2));
    console.log('Interruptor: 3 anchos; ambos valores, aviso, aria, nota y confirmación correctos.');
} finally {if(page)await page.close();await browser.close();}
