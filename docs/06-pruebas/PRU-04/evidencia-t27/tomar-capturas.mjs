import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import {createRequire} from 'node:module';
const {chromium} = createRequire(import.meta.url)('playwright');
const raiz = path.resolve('.');
const evidencia = path.join(raiz, 'docs/06-pruebas/PRU-04/evidencia-t27');
const capturas = path.join(evidencia, 'capturas'); fs.mkdirSync(capturas, {recursive:true});
const mime = {'.html':'text/html; charset=utf-8','.css':'text/css','.js':'application/javascript','.png':'image/png','.svg':'image/svg+xml','.woff2':'font/woff2','.json':'application/json'};
const servidor = http.createServer((req,res) => {
    const url = new URL(req.url,'http://localhost');
    const prefijo = url.pathname.startsWith('/t27/html/') ? '/t27/html/' : url.pathname.startsWith('/t27/') ? '/t27/' : url.pathname.startsWith('/build/') ? '/build/' : '/';
    const dir = prefijo === '/t27/html/' ? path.join(evidencia,'html') : prefijo === '/t27/' ? evidencia : prefijo === '/build/' ? path.join(evidencia,'build') : path.join(raiz,'public');
    const archivo = path.resolve(dir, url.pathname.slice(prefijo.length));
    if (req.method !== 'GET' || !archivo.startsWith(dir+path.sep) || !fs.existsSync(archivo) || !fs.statSync(archivo).isFile()) {res.writeHead(404); res.end(); return;}
    res.writeHead(200,{'Content-Type':mime[path.extname(archivo)] ?? 'application/octet-stream'}); fs.createReadStream(archivo).pipe(res);
});
await new Promise(resolve => servidor.listen(0,'127.0.0.1',resolve));
const base = `http://127.0.0.1:${servidor.address().port}`;
const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
const casos = JSON.parse(fs.readFileSync(path.join(evidencia,'casos.json'),'utf8'));
const resultados = [], fallos = [];
const pantallas = ['propuesta','cashflow-actual','movimientos-actual','historial-actual','ficha-alumno','caja-validada','liquidacion-pagada','caja-abierta','editar-en-caja','login'];
try {
    for (const modo of ['escritorio','marco-360']) for (const id of pantallas) {
        const page = await browser.newPage({viewport:modo === 'escritorio' ? {width:1366,height:1000} : {width:700,height:860}});
        const errores = []; page.on('pageerror',e=>errores.push(e.message));
        await page.route('**/*',route => route.request().method() === 'GET' && route.request().url().startsWith(base+'/') ? route.continue() : route.abort());
        try {
            await page.goto(`${base}/t27/html/${id}${modo === 'marco-360' ? '-marco-360' : ''}.html`,{waitUntil:'load'});
            const frame = modo === 'escritorio' ? page.mainFrame() : page.frames().find(f=>f !== page.mainFrame());
            await frame.evaluate(()=>document.fonts.ready.then(()=>undefined));
            const medidas = await frame.evaluate(()=>({ancho:innerWidth,pagina:document.documentElement.scrollWidth}));
            if (modo === 'marco-360' && medidas.ancho !== 360) throw new Error('El marco no mide 360');
            if (id === 'login' && medidas.pagina > medidas.ancho) throw new Error('Login de control recortado');
            if (errores.length) throw new Error(errores.join('; '));
            await page.screenshot({path:path.join(capturas,`${id}-${modo}.png`)});
            resultados.push({id,modo,...medidas,errores});
            if (id === 'propuesta') {
                const visible = modo === 'escritorio' ? '.cashflow-desktop-table' : '.cashflow-mobile-cards';
                await frame.locator(visible).evaluate(el => window.scrollTo({top:scrollY + el.getBoundingClientRect().top - 100, behavior:'instant'}));
                await page.screenshot({path:path.join(capturas,`propuesta-listado-${modo}.png`)});
                await frame.locator('#t27-cajas').scrollIntoViewIfNeeded();
                await page.screenshot({path:path.join(capturas,`propuesta-cajas-${modo}.png`)});
                for (const [movId,caso] of Object.entries(casos)) {
                    if (!['Directo','Cobro del dueño','Caja validada','Liquidación pagada'].includes(caso.origen)) continue;
                    if (caso.origen === 'Directo' && movId !== Object.keys(casos)[0]) continue;
                    const enlace = frame.locator(`${visible} a[href="#t27-${movId}"]`).first();
                    await page.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));
                    await enlace.evaluate(el=>window.scrollTo({top:scrollY + el.getBoundingClientRect().top - innerHeight / 2,behavior:'instant'}));
                    await frame.waitForFunction(selector => {
                        const rect = document.querySelector(selector).getBoundingClientRect();
                        return rect.top > 75 && rect.bottom < innerHeight;
                    }, `${visible} a[href="#t27-${movId}"]`);
                    const accesible = await enlace.evaluate(el => {
                        const rect = el.getBoundingClientRect();
                        return el.contains(document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2));
                    });
                    if (!accesible) throw new Error('Otro elemento tapa el ícono');
                    const posicion = await enlace.boundingBox();
                    await page.mouse.click(posicion.x + posicion.width / 2, posicion.y + posicion.height / 2);
                    await frame.waitForFunction(id => location.hash === `#t27-${id}`, movId);
                    const panel = frame.locator(`#t27-${movId}`); await panel.scrollIntoViewIfNeeded();
                    if (!await panel.isVisible()) throw new Error('El panel no abre');
                    const slug = caso.origen.toLowerCase().replaceAll(' ','-').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
                    await page.screenshot({path:path.join(capturas,`propuesta-${slug}-${modo}.png`)});
                }
            }
        } catch(e) {fallos.push({id,modo,error:e.message});}
        finally {await page.close();}
    }
} finally {
    await browser.close(); await new Promise(resolve=>servidor.close(resolve));
    fs.writeFileSync(path.join(evidencia,'resultado-navegador.json'),JSON.stringify({resultados,fallos},null,2));
}
console.log(JSON.stringify({pantallas:resultados.length,fallos},null,2));
if(fallos.length) process.exitCode=1;
