import { chromium } from 'playwright';
import { readdir, readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath, pathToFileURL } from 'node:url';
import path from 'node:path';
const dir = path.dirname(fileURLToPath(import.meta.url));
const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe', headless:true, args:['--allow-file-access-from-files']});
const resultados = [];
for (const file of (await readdir(dir)).filter(x=>x.endsWith('.html') && !x.startsWith('marco-'))) {
  let html = await readFile(path.join(dir,file),'utf8');
  // Resolver las imagenes originales locales al abrir respuestas guardadas fuera de HTTP.
  const imagenes = pathToFileURL(path.resolve(dir,'../../../../public/img')).href + '/';
  const conImagenes = html.replace(/https?:\/\/[^/"\s]+\/img\//g, imagenes);
  if (conImagenes !== html) { html = conImagenes; await writeFile(path.join(dir,file),html); }
  const divs = [...html.matchAll(/<\/?div\b[^>]*>/gi)];
  let balance=0, minimo=0;
  for(const m of divs) { balance += /^<\//.test(m[0]) ? -1:1; minimo=Math.min(minimo,balance); }
  for (const ancho of [375,1366]) {
    const page = await browser.newPage({viewport:{width:1500,height:1100}});
    const marco = `<!doctype html><html><body style="margin:0"><iframe style="border:0;width:${ancho}px;height:1000px" src="${pathToFileURL(path.join(dir,file)).href}"></iframe></body></html>`;
    const marcoPath = path.join(dir,`marco-${ancho}-${file}`);
    await writeFile(marcoPath,marco);
    await page.goto(pathToFileURL(marcoPath).href);
    await page.waitForTimeout(800);
    const frame = page.frames()[1];
    const medidas = await frame.evaluate(()=>({
      viewport:innerWidth, client:document.documentElement.clientWidth, scroll:document.documentElement.scrollWidth,
      main:!!document.querySelector('main'), hojas:document.styleSheets.length,
      fuera:[...document.querySelectorAll('main li, main .filtros-card, main a')].map(el=>({texto:el.textContent.trim().slice(0,70),x:el.getBoundingClientRect().x,right:el.getBoundingClientRect().right})).filter(x=>x.x < -1 || x.right > innerWidth+1)
    }));
    resultados.push({file,ancho,divBalance:balance,divMinimo:minimo,...medidas});
    const alto = await frame.evaluate(()=>Math.max(1000,document.documentElement.scrollHeight));
    await page.locator('iframe').evaluate((el,h)=>el.style.height=h+'px', alto);
    await page.locator('iframe').screenshot({path:path.join(dir,`${file.replace('.html','')}-${ancho}.png`)});
    await page.close();
  }
}
await writeFile(path.join(dir,'mediciones.json'),JSON.stringify(resultados,null,2));
await browser.close();
console.log(JSON.stringify(resultados));
