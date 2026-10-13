import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import {createRequire} from 'node:module';
const {chromium} = createRequire(import.meta.url)('playwright');
const raiz = path.resolve('.');
const evidencia = path.join(raiz, 'docs/06-pruebas/PRU-04/evidencia-t33');
fs.cpSync(path.join(raiz, 'public/build'), path.join(evidencia, 'build'), {recursive:true});
fs.mkdirSync(path.join(evidencia, 'capturas'), {recursive:true});
fs.mkdirSync(path.join(evidencia, 'img'), {recursive:true});
fs.copyFileSync(path.join(raiz,'public/img/logo-wings.png'), path.join(evidencia,'img/logo-wings.png'));
for(const caso of ['inscripcion-pendiente','inscripcion-condonada','cuota-condonada','login']) {
    const archivo=path.join(evidencia,caso+'.html');
    const html=fs.readFileSync(archivo,'utf8')
        .replace(/(name="(?:csrf-token|_token)"[^>]*?(?:content|value)=")[^"]*/g,'$1prueba-descartable-t33')
        .replace(/https?:\/\/[^/\"]+\/img\/logo-wings.png/g,'./img/logo-wings.png');
    fs.writeFileSync(archivo,html);
}
const tipos = {'.html':'text/html; charset=utf-8','.css':'text/css','.js':'application/javascript','.png':'image/png',
    '.svg':'image/svg+xml','.woff2':'font/woff2','.woff':'font/woff','.json':'application/json'};
const servidor = http.createServer((req,res) => {
    const url = new URL(req.url,'http://localhost');
    const archivo = path.resolve(evidencia, '.'+url.pathname);
    if(req.method !== 'GET' || !archivo.startsWith(evidencia+path.sep) || !fs.existsSync(archivo) || !fs.statSync(archivo).isFile()) {
        res.writeHead(404);res.end();return;
    }
    res.writeHead(200, {'Content-Type':tipos[path.extname(archivo)] || 'application/octet-stream'});
    fs.createReadStream(archivo).pipe(res);
});
await new Promise(resolve => servidor.listen(0, '127.0.0.1', resolve));
const base = `http://127.0.0.1:${servidor.address().port}`;
const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
const resultados = [], fallos = [];
const referenciaFila = {};
try {
    for(const modo of ['escritorio','marco-360']) for(const caso of ['inscripcion-pendiente','inscripcion-condonada','cuota-condonada','login']) {
        const page = await browser.newPage({viewport:modo === 'escritorio' ? {width:1366,height:1000} : {width:700,height:1000}});
        const errores=[];page.on('pageerror',e=>errores.push(e.message));
        await page.route('**/*',route=>route.request().url().startsWith(base+'/') && route.request().method()==='GET' ? route.continue() : route.abort());
        try {
            if(modo === 'marco-360') {
                // El iframe fija el ancho REAL; la ventana exterior permanece grande.
                const marco = `<!doctype html><meta charset="utf-8"><title>Wings — marco 360</title><iframe src="${caso}.html" style="width:360px;height:940px;border:0;display:block;margin:16px auto"></iframe>`;
                fs.writeFileSync(path.join(evidencia,caso+'-marco-360.html'),marco);
            }
            await page.goto(`${base}/${caso}${modo==='marco-360'?'-marco-360':''}.html`,{waitUntil:'networkidle'});
            const frame = modo === 'escritorio' ? page.mainFrame() : page.frames().find(f=>f!==page.mainFrame());
            await frame.evaluate(()=>document.fonts.ready);
            const imagenesCargadas=await frame.locator('img').evaluateAll(els=>els.every(el=>el.complete && el.naturalWidth>0));
            if(!imagenesCargadas) throw new Error('Falta cargar una imagen de Wings');
            const medidas=await frame.evaluate(()=>({ancho:innerWidth,pagina:document.documentElement.scrollWidth}));
            if(medidas.ancho !== (modo==='escritorio'?1366:360) || medidas.pagina > medidas.ancho) throw new Error('Ancho incorrecto o desborde');
            let medidasBoton;
            if(caso !== 'login') {
                const zona = frame.locator(caso==='cuota-condonada'?'#cuotas-condonadas':'.alumno-card').first();
                await zona.screenshot({path:path.join(evidencia,'capturas',`${caso}-${modo}.png`)});
                const selector = caso==='inscripcion-pendiente'?'[data-abrir-condonar]':'[data-abrir-reactivar]';
                const boton=zona.locator(selector).first();
                medidasBoton=await boton.evaluate(el=>({ancho:el.getBoundingClientRect().width,alto:el.getBoundingClientRect().height,
                    alineado:Math.abs(el.getBoundingClientRect().right-el.parentElement.getBoundingClientRect().right)<1,
                    separacionLinea:el.getBoundingClientRect().top-el.parentElement.getBoundingClientRect().top
                        -parseFloat(getComputedStyle(el.parentElement).borderTopWidth)}));
                if(caso==='inscripcion-pendiente') referenciaFila[modo]=await frame.locator('a.ds-btn-row').first().evaluate(el=>({
                    ancho:el.getBoundingClientRect().width,alto:el.getBoundingClientRect().height}));
                const referencia=referenciaFila[modo];
                if(Math.abs(medidasBoton.ancho-referencia.ancho)>1 || Math.abs(medidasBoton.alto-referencia.alto)>1
                    || !medidasBoton.alineado) throw new Error('Botón distinto del Cobrar de fila existente o no alineado a la derecha: '+JSON.stringify(medidasBoton));
                if(caso.startsWith('inscripcion-') && Math.abs(medidasBoton.separacionLinea-12)>0.5)
                    throw new Error('La separación entre línea y botón no es 12 px: '+JSON.stringify(medidasBoton));
                const action=await boton.getAttribute(caso==='inscripcion-pendiente'?'data-abrir-condonar':'data-abrir-reactivar');
                await boton.click();
                const modal=frame.locator('#modal-condonar');
                if(!await modal.isVisible()) throw new Error('No abrió el modal');
                const etiqueta=caso==='inscripcion-pendiente'?'Condonar':'Reactivar';
                if(await frame.locator('#perdon-confirmar').textContent()!==etiqueta) throw new Error('Acción incorrecta en modal');
                if(await frame.locator('#form-condonar').getAttribute('action')!==action) throw new Error('Destino incorrecto');
                await frame.locator('#form-condonar textarea').fill('abc');
                if(await frame.locator('#form-condonar textarea').evaluate(el=>el.checkValidity())) throw new Error('Acepta motivo corto');
                await frame.locator('#form-condonar textarea').fill('Motivo documentado en navegador');
                if(!await frame.locator('#form-condonar textarea').evaluate(el=>el.checkValidity())) throw new Error('Rechaza motivo válido');
                await modal.locator('div').first().screenshot({path:path.join(evidencia,'capturas',`${caso}-modal-${modo}.png`)});
                await frame.locator('[data-cerrar-condonar]').click();
                if(await modal.isVisible()) throw new Error('No cerró el modal');
                await boton.click();
                if(await frame.locator('#form-condonar textarea').inputValue()!=='') throw new Error('Conserva un motivo de otra decisión');
                await frame.locator('[data-cerrar-condonar]').click();
                const otro=frame.locator('[data-abrir-condonar]').first();
                if(await otro.count()) {
                    await otro.click();
                    if(await frame.locator('#perdon-confirmar').textContent()!=='Condonar') throw new Error('No volvió al modo Condonar');
                    await frame.locator('[data-cerrar-condonar]').click();
                }
            } else {
                const contenido=modo==='escritorio'?page:page.locator('iframe');
                await contenido.screenshot({path:path.join(evidencia,'capturas',`login-${modo}.png`)});
            }
            if(errores.length) throw new Error(errores.join('; '));
            resultados.push({caso,modo,...medidas,imagenesCargadas,errores,modal:caso!=='login',medidasBoton});
        } catch(e) {fallos.push({caso,modo,error:e.message});}
        finally {await page.close();}
    }
} finally {
    await browser.close();await new Promise(resolve=>servidor.close(resolve));
    fs.writeFileSync(path.join(evidencia,'resultado-navegador.json'),JSON.stringify({resultados,fallos},null,2));
}
console.log(JSON.stringify({visitas:resultados.length,fallos},null,2));
if(fallos.length) process.exitCode=1;
