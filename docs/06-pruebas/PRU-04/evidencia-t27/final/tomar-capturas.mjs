import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import {createRequire} from 'node:module';
const {chromium} = createRequire(import.meta.url)('playwright');
const raiz = path.resolve('.');
const evidencia = path.join(raiz, 'docs/06-pruebas/PRU-04/evidencia-t27/final');
const capturas = path.join(evidencia, 'capturas'); fs.mkdirSync(capturas, {recursive:true});
const rutas = JSON.parse(fs.readFileSync(path.join(evidencia,'rutas.json'),'utf8'));
const casos = JSON.parse(fs.readFileSync(path.join(evidencia,'casos.json'),'utf8'));
const pantallas = ['cashflow','crear-directo','editar-directo','eliminar-directo','detalle-editado','eliminar-editado',
    'detalle-anulado','cashflow-anulado','historial-anulado','inicio-anulado','reportes-anulado',
    'liquidacion-sin-pagar','liquidacion-pagada','ficha-alumno','caja-validada','caja-abierta','editar-en-caja','login'];
const seleccion = process.env.T27_CAPTURAS?.split(',').filter(Boolean);
const recorrer = seleccion ? pantallas.filter(id=>seleccion.includes(id)) : pantallas;
const mime = {'.html':'text/html; charset=utf-8','.css':'text/css','.js':'application/javascript','.png':'image/png','.svg':'image/svg+xml','.woff2':'font/woff2','.woff':'font/woff','.json':'application/json'};
function archivoPara(url) {
    const prefijo = url.pathname.startsWith('/t27/html/') ? '/t27/html/' : url.pathname.startsWith('/build/') ? '/build/' : '/';
    const dir = prefijo === '/t27/html/' ? path.join(evidencia,'html') : prefijo === '/build/' ? path.join(evidencia,'build') : path.join(raiz,'public');
    const archivo = path.resolve(dir,url.pathname.slice(prefijo.length));
    return archivo.startsWith(dir+path.sep) && fs.existsSync(archivo) && fs.statSync(archivo).isFile() ? archivo : null;
}
const servidor = http.createServer((req,res) => {
    const archivo = archivoPara(new URL(req.url,'http://localhost'));
    if (req.method !== 'GET' || !archivo) {res.writeHead(404);res.end();return;}
    res.writeHead(200,{'Content-Type':mime[path.extname(archivo)] ?? 'application/octet-stream'});fs.createReadStream(archivo).pipe(res);
});
await new Promise(resolve=>servidor.listen(0,'127.0.0.1',resolve));
const base = `http://127.0.0.1:${servidor.address().port}`;
const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
const resultados = [], controles = [], fallos = [];
async function foto(page,id,modo) {await page.screenshot({path:path.join(capturas,`${id}-${modo}.png`)});}
async function centrar(frame,locator) {await locator.evaluate(el=>window.scrollTo({top:scrollY+el.getBoundingClientRect().top-innerHeight/2,behavior:'instant'}));}
try {
 for (const modo of ['escritorio','marco-360']) for (const id of recorrer) {
    const page = await browser.newPage({viewport:modo === 'escritorio' ? {width:1366,height:1000} : {width:700,height:860}});
    const errores = [];page.on('pageerror',e=>errores.push(e.message));
    await page.route('**/*',async route=>{
        const req=route.request(), url=new URL(req.url());
        if(req.method()!=='GET') return route.abort();
        if(req.url().startsWith(base+'/')) return route.continue();
        // Los enlaces mantienen sus URL reales; el ensayo responde con el HTML ya pedido a Laravel.
        const destino=rutas[url.pathname+url.search];
        if(destino) return route.fulfill({path:path.join(evidencia,'html',destino+'.html'),contentType:'text/html; charset=utf-8'});
        const archivo=archivoPara(url);
        if(archivo && ['/build/','/img/','/favicon','/apple-touch','/site.webmanifest'].some(p=>url.pathname.startsWith(p))) {
            return route.fulfill({path:archivo,contentType:mime[path.extname(archivo)] ?? 'application/octet-stream'});
        }
        return route.abort();
    });
    try {
        await page.goto(`${base}/t27/html/${id}${modo === 'marco-360' ? '-marco-360' : ''}.html`,{waitUntil:'load'});
        const frame=modo==='escritorio'?page.mainFrame():page.frames().find(f=>f!==page.mainFrame());
        await frame.evaluate(()=>document.fonts.ready.then(()=>undefined));
        const medidas=await frame.evaluate(()=>({ancho:innerWidth,pagina:document.documentElement.scrollWidth}));
        if(modo==='marco-360' && (medidas.ancho!==360 || medidas.pagina>360)) throw new Error('El marco se desborda');
        if(errores.length) throw new Error(errores.join('; '));
        await foto(page,id,modo);resultados.push({id,modo,...medidas,errores});
        if(id==='cashflow' || id==='cashflow-anulado') {
            const visible=modo==='escritorio'?'.cashflow-desktop-table':'.cashflow-mobile-cards';
            await frame.locator(visible).evaluate(el=>window.scrollTo({top:scrollY+el.getBoundingClientRect().top-100,behavior:'instant'}));
            await foto(page,id+'-listado',modo);
            if(id==='cashflow') {
                for(const [selector,nombre] of [['#cashflow-cajas-editables','cashflow-cajas'],['#cashflow-liquidaciones-pendientes','cashflow-liquidaciones']]) {
                    await frame.locator(selector).scrollIntoViewIfNeeded();await foto(page,nombre,modo);
                }
                const iconos=frame.locator(`${visible} a[aria-label]`);
                const dimensiones=await iconos.evaluateAll(els=>els.map(el=>({ancho:el.getBoundingClientRect().width,alto:el.getBoundingClientRect().height})));
                if(!dimensiones.length || dimensiones.some(d=>d.ancho!==28 || d.alto!==28)) throw new Error('Los íconos no miden 28');
                controles.push({id,modo,iconos:dimensiones.length,dimensiones});
                const enlace=frame.locator(`${visible} a[href$="/cashflow/movimientos/${casos.directo}/editar"]`);
                await page.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));await centrar(frame,enlace);
                const visibleParaMouse=await enlace.evaluate(el=>{const r=el.getBoundingClientRect();return el.contains(document.elementFromPoint(r.x+r.width/2,r.y+r.height/2));});
                if(!visibleParaMouse) throw new Error('El ícono de editar está tapado');
                const r=await enlace.boundingBox();await page.mouse.click(r.x+r.width/2,r.y+r.height/2);
                await frame.waitForURL(url=>url.pathname.endsWith(`/cashflow/movimientos/${casos.directo}/editar`));
                if(!await frame.locator('#monto').isVisible()) throw new Error('Editar no lleva al formulario');
                controles.push({id,modo,editarLlevaAFormulario:true});
            }
        }
        if(id==='editar-directo') {
            await frame.waitForFunction(()=>Boolean(window.WingsMoney));
            const seleccionConservada=await frame.evaluate(()=>{
                const form=document.getElementById('mov-form');
                return document.getElementById('rubro_id').value===form.dataset.rubroInicial
                    && document.getElementById('subrubro_id').value===form.dataset.subrubroInicial;
            });
            if(!seleccionConservada) throw new Error('El formulario perdió el rubro o subrubro guardado');
            await frame.locator('#mov-form button[type="submit"]').evaluate(el=>window.scrollTo({top:scrollY+el.getBoundingClientRect().top-innerHeight+150,behavior:'instant'}));
            await foto(page,'editar-directo-botones',modo);
            const input=frame.locator('#monto');await centrar(frame,input);await input.fill('48.50');
            if(await input.getAttribute('aria-invalid')==='true') throw new Error('Rechazó dos decimales');
            await input.fill('48.505');
            if(await input.inputValue()!=='48.50' || !await input.evaluate(el=>el.classList.contains('money-input--warning'))) throw new Error('Tres decimales no avisan y conservan dos');
            if(!await frame.locator('.money-notice').isVisible()) throw new Error('Falta aviso de decimales');
            await foto(page,'editar-directo-aviso',modo);controles.push({id,modo,dosDecimales:true,advertenciaTres:true});
        }
        if(id==='liquidacion-sin-pagar') {
            // hasAttribute distingue el atributo booleano open="" de su ausencia.
            if(!await frame.locator('#editar-liquidacion').evaluate(el=>el.hasAttribute('open'))) throw new Error('No abrió la edición existente');
            await frame.locator('#editar-liquidacion').evaluate(el=>window.scrollTo({top:scrollY+el.getBoundingClientRect().top-100,behavior:'instant'}));
            await foto(page,'liquidacion-editor',modo);
        }
        if(id==='liquidacion-pagada' && await frame.locator('#editar-liquidacion').count()) throw new Error('Pagada ofrece edición');
        if(id==='historial-anulado') {
            const tabla=frame.locator('table');
            if(modo==='marco-360') {
                await tabla.evaluate(el=>{window.scrollTo({top:scrollY+el.getBoundingClientRect().top-100,behavior:'instant'});el.parentElement.scrollLeft=el.parentElement.scrollWidth;});
                await foto(page,'historial-importes',modo);
            }
            const importes=await tabla.locator('tbody tr td:last-child').evaluateAll(celdas=>celdas.map(el=>{
                const rango=document.createRange();rango.selectNodeContents(el);
                const texto=rango.getBoundingClientRect(), celda=el.getBoundingClientRect();
                return {texto:el.textContent.trim(),derecha:texto.right,derechaEsperada:celda.right-parseFloat(getComputedStyle(el).paddingRight)};
            }));
            controles.push({id,modo,importes});
            if(!importes.some(i=>i.texto.includes('1,250,000.50'))) throw new Error('Falta el importe grande de control');
            if(importes.some(i=>Math.abs(i.derecha-i.derechaEsperada)>1)) throw new Error('Los importes no se alinean con el borde derecho de la columna');
        }
        if(id==='detalle-anulado') {
            await frame.locator('#cashflow-historial-correcciones').evaluate(el=>window.scrollTo({top:scrollY+el.getBoundingClientRect().top-100,behavior:'instant'}));
            await foto(page,'historial-correcciones',modo);
        }
    } catch(e) {fallos.push({id,modo,error:e.message});}
    finally {await page.close();}
 }
} finally {
 await browser.close();await new Promise(resolve=>servidor.close(resolve));
 fs.writeFileSync(path.join(evidencia,seleccion?'resultado-navegador-historial.json':'resultado-navegador.json'),JSON.stringify({resultados,controles,fallos},null,2));
}
console.log(JSON.stringify({pantallas:resultados.length,controles:controles.length,fallos},null,2));
if(fallos.length) process.exitCode=1;
