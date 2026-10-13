// Comparación acotada con dd62c64. Se sirve su HTML real y SU build en el escenario aislado.
// La matriz final permanece en tests/Browser/importes-t24.mjs; aquí se registran las regresiones previas.
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import {createRequire} from 'node:module';
const {chromium} = createRequire(import.meta.url)('playwright');
const raiz = path.resolve('.');
const salida = path.join(raiz, 'docs/06-pruebas/PRU-04/evidencia-t24/antes');
const publico = path.join(raiz, 'public');
const datos = JSON.parse(fs.readFileSync(path.join(salida, 'pedidos.json'), 'utf8'));
const tipos = {'.html':'text/html; charset=utf-8','.js':'application/javascript','.css':'text/css','.png':'image/png','.woff2':'font/woff2','.svg':'image/svg+xml'};
const server = http.createServer((req,res) => {
    const url = new URL(req.url,'http://localhost');
    const carpeta = url.pathname.startsWith('/antes/') ? path.join(salida,'html')
        : url.pathname.startsWith('/build/') ? path.join(salida,'build') : publico;
    const archivo = path.resolve(carpeta, url.pathname.replace(/^\/(?:antes\/|build\/)?/,''));
    if (req.method !== 'GET' || !archivo.startsWith(carpeta+path.sep) || !fs.existsSync(archivo) || !fs.statSync(archivo).isFile()) {res.writeHead(404);res.end();return;}
    res.writeHead(200,{'Content-Type':tipos[path.extname(archivo)]??'application/octet-stream'}); fs.createReadStream(archivo).pipe(res);
});
await new Promise(resolve => server.listen(0,'127.0.0.1',resolve));
const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
const page = await browser.newPage({viewport:{width:1366,height:1000}});
const resultados=[];
try {
    for (const caso of datos.casos.filter(caso=>caso.ruta)) {
        await page.goto(`http://127.0.0.1:${server.address().port}/antes/${caso.caso}.html`,{waitUntil:'load'});
        if (caso.caso==='grupo-alta') await page.locator('#btn-add-plan').click();
        if (caso.caso==='profesor-alta') await page.locator('#deporte_id').selectOption({label:'Deporte T24'});
        if (caso.caso==='liquidacion') await page.getByText('Editar monto final',{exact:true}).click();
        if (caso.caso==='cuota') await page.locator('.cuota-check[data-periodo="2026-10"]').check();
        if (caso.caso==='adelantado') await page.locator('#adelanto-periodo').selectOption('2026-11');
        const input = page.locator(caso.selector);
        await input.focus(); await input.fill('48.50');
        const dosDecimales = await input.inputValue();
        await input.fill('48.505');
        const tresDecimales = await input.inputValue();
        const rojo = await input.evaluate(el=>el.classList.contains('money-input--warning'));
        const aviso = await page.locator('.money-notice').count()>0;
        resultados.push({caso:caso.caso,dosDecimales,tresDecimales,rojo,aviso,
            detectaRegresion:dosDecimales!=='48.50'||tresDecimales!=='48.50'||!rojo||!aviso});
    }
    fs.writeFileSync(path.join(salida,'navegador.json'),JSON.stringify({revision:'dd62c64',resultados},null,2));
    if (resultados.length!==18 || resultados.some(r=>!r.detectaRegresion)) throw Error('Falta evidencia de un campo anterior');
    console.log(JSON.stringify({campos:resultados.length,regresiones:resultados.filter(r=>r.detectaRegresion).length}));
} finally {await browser.close();await new Promise(resolve=>server.close(resolve));}
