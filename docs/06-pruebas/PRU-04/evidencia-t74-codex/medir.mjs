import { chromium } from 'playwright';
import { readdir, readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath, pathToFileURL } from 'node:url';
import path from 'node:path';

const fase=process.argv[2];
if(!['antes','despues'].includes(fase))throw new Error('Indicar antes o despues');
const dir=path.join(path.dirname(fileURLToPath(import.meta.url)),fase);
const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--allow-file-access-from-files']});
const resultados=[];
try {
 for(const file of (await readdir(dir)).filter(f=>f.endsWith('.html')&&!f.startsWith('marco-'))) {
  const archivo=path.join(dir,file);
  let html=await readFile(archivo,'utf8');
  html=html.replace(/https?:\/\/[^/"\s]+\/img\//g,pathToFileURL(path.resolve(dir,'../../../../../public/img')).href+'/');
  await writeFile(archivo,html);
  for(const ancho of [1366,375]) {
   const page=await browser.newPage({viewport:{width:1500,height:1000}});
   const marco=path.join(dir,`marco-${ancho}-${file}`);
   await writeFile(marco,`<!doctype html><html><body style="margin:0"><iframe style="display:block;border:0;width:${ancho}px;height:900px" src="${pathToFileURL(archivo).href}"></iframe></body></html>`);
   await page.goto(pathToFileURL(marco).href);
   await page.waitForTimeout(500);
   const frame=page.frames()[1];
   const datos=await frame.evaluate(()=>{
    const root=document.querySelector('.ds-content > .container')??document.querySelector('main');
    const rect=el=>el?{x:el.getBoundingClientRect().x,right:el.getBoundingClientRect().right,width:el.getBoundingClientRect().width,height:el.getBoundingClientRect().height}:null;
    const links=[...(root?.querySelectorAll('a')??[])];
    const alumnos=links.find(a=>a.textContent.trim()==='Alumnos');
    const cajon=document.getElementById('tarjeta-cajon');
    const accion=cajon?[...cajon.querySelectorAll('a')].at(-1):null;
    const a=rect(alumnos),b=rect(accion);
    return {viewport:innerWidth,client:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth,
     hojas:document.styleSheets.length,alumnos:a,accionCajon:rect(accion),accionTexto:accion?.textContent.trim(),delta:a&&b?a.right-b.right:null,
     botones:links.filter(a=>['Caja','Cobranza','Alumnos','Cobrar','Registrar','Resumen','Cerrar','Abrir'].includes(a.textContent.trim())).map(a=>({texto:a.textContent.trim(),...rect(a)})),
     fuera:[...(root?.querySelectorAll('a, .filtros-card')??[])].map(a=>({texto:a.textContent.trim().slice(0,70),...rect(a)})).filter(a=>a.x < -1||a.right>innerWidth+1)};
   });
   const alto=await frame.evaluate(()=>Math.max(900,document.documentElement.scrollHeight));
   await page.locator('iframe').evaluate((el,h)=>el.style.height=h+'px',alto);
   await page.locator('iframe').screenshot({path:path.join(dir,`${file.slice(0,-5)}-${ancho}.png`)});
   if(ancho===1366 && file==='propia.html') {
    await page.screenshot({path:path.join(dir,'propia-1366-botones.png'),clip:{x:950,y:170,width:416,height:430}});
   }
   const tramos=[];let altoTramos=null;
   if(ancho===375) {
    await page.locator('iframe').evaluate(el=>el.style.height='720px');
    const total=await frame.evaluate(()=>document.documentElement.scrollHeight);altoTramos=total;
    for(let y=0,i=1;y<total;y+=600,i++) {
     await frame.evaluate(y=>scrollTo(0,y),y);await page.waitForTimeout(100);
     const nombre=`${file.slice(0,-5)}-375-tramo-${String(i).padStart(2,'0')}.png`;
     const scrollY=await frame.evaluate(()=>window.scrollY);
     await page.locator('iframe').screenshot({path:path.join(dir,nombre)});
     tramos.push({archivo:nombre,scrollY});
     if(scrollY+720>=total)break;
    }
   }
   resultados.push({file,ancho,...datos,tramos,altoTramos});
   await page.close();
  }
 }
 await writeFile(path.join(dir,'mediciones.json'),JSON.stringify(resultados,null,2));
 console.log(JSON.stringify(resultados.map(r=>({file:r.file,ancho:r.ancho,delta:r.delta,fuera:r.fuera.length,tramos:r.tramos.length}))));
} finally {await browser.close();}
