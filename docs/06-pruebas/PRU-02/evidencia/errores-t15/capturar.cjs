// Chrome real. El marco solo aloja Wings: no reconstruye ninguna pantalla.
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),cp=require('node:child_process');
const {chromium}=require('playwright');
const base=path.resolve(__dirname,'../../../../..'),origin='http://127.0.0.1:8011',caida='http://127.0.0.1:8012';
const fixturePath=path.join(base,'storage/app/t15-fixture.json');
const salida=path.join(__dirname,'resultado.json');
const results={fecha:new Date().toISOString(),database:'wings_testing_codex',completo:false,errores:[],menu:[],caida:[],controles:[],avisos_js:[]};
if(process.argv.includes('--continuar')){Object.assign(results,JSON.parse(fs.readFileSync(salida)));delete results.fallo;results.completo=false;}
const save=()=>fs.writeFileSync(salida,JSON.stringify(results,null,2)+'\n');
const archivo=s=>s.replace(/[^a-zA-Z0-9-]/g,'-');
const cli=(args,env={})=>cp.execFileSync('php',args[0]==='artisan'?[path.join(__dirname,'artisan.php'),...args.slice(1)]:args,{cwd:base,encoding:'utf8',env:{...process.env,APP_ENV:'testing',DB_DATABASE:'wings_testing_codex',APP_MAINTENANCE_DRIVER:'file',...env}});
let browser;
(async()=>{
 browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
 async function capture(page,url,name,width,server=origin){
  let response,frame;
  if(width===1366){response=await page.goto(server+url);frame=page.mainFrame();}
  else{
   const expected=page.waitForResponse(r=>new URL(r.url()).origin===server&&new URL(r.url()).pathname===new URL(url,server).pathname);
   await page.goto(server+'/__t15/marco?url='+encodeURIComponent(url));
   response=await expected;frame=page.frames().find(f=>f!==page.mainFrame());
  }
  await frame.waitForLoadState('networkidle');
  const titulo=await frame.locator('h1').first().textContent().catch(()=>null);
  const medidas=await frame.evaluate(()=>({ancho:innerWidth,contenido:document.documentElement.scrollWidth}));
  if(width===360&&name.startsWith('menu-')){
   await frame.locator('#ds-menu-toggle').click();
   await frame.locator('.ds-sidebar a').filter({hasText:/^\s*Inicio\s*$/}).waitFor({state:'visible'});
   await page.waitForTimeout(250);
  }
  const file=archivo(name)+'-'+width+'.png';
  if(width===360)await page.locator('iframe').screenshot({path:path.join(__dirname,file)});
  else await page.screenshot({path:path.join(__dirname,file),fullPage:true});
  return {url,status:response.status(),titulo:titulo?.trim(),...medidas,captura:file,frame};
 }
 if(process.argv.includes('--caida')){
  const context=await browser.newContext({viewport:{width:1366,height:900},locale:'es-AR'});
  results.entorno_caida=await (await context.request.get(caida+'/__t15/entorno')).json();
  assert.equal(String(results.entorno_caida.db_port),'1');assert.equal(results.entorno_caida.session_driver,'database');assert.equal(results.entorno_caida.database,'wings_testing_codex');
  const p=await context.newPage();
  for(const width of [1366,360]){
   const r=await capture(p,'/login','base-caida-500',width,caida);const frame=r.frame;delete r.frame;
   assert.equal(r.status,500);assert.equal(r.titulo,'Algo falló');assert.equal(r.ancho,width);
   assert(!(await frame.locator('body').innerText()).includes('SQLSTATE'));
   results.caida.push(r);save();
  }
  cli(['artisan','down','--retry=120'],{DB_PORT:'1',SESSION_DRIVER:'database',CACHE_STORE:'file',LARAVEL_STORAGE_PATH:path.join(base,'storage/app/errores-t15-caida')});
  try{
   for(const width of [1366,360]){
    const r=await capture(p,'/login','base-caida-mantenimiento-503',width,caida);delete r.frame;
    assert.equal(r.status,503);assert.equal(r.titulo,'Estamos actualizando Wings');assert.equal(r.ancho,width);results.caida.push(r);save();
   }
  }finally{
   cli(['artisan','up'],{DB_PORT:'1',SESSION_DRIVER:'database',CACHE_STORE:'file',LARAVEL_STORAGE_PATH:path.join(base,'storage/app/errores-t15-caida')});
  }
  const log=path.join(base,'storage/app/errores-t15-caida/logs/laravel.log');
  results.fallo_de_base_registrado=fs.existsSync(log)&&fs.readFileSync(log,'utf8').includes('SQLSTATE');
  assert(results.fallo_de_base_registrado);results.completo=true;save();return;
 }
 const fixture=JSON.parse(fs.readFileSync(fixturePath));assert.equal(fixture.database,'wings_testing_codex');
 const titulos={404:'Página no disponible',429:'Esperá un momento',500:'Algo falló',503:'Estamos actualizando Wings'};
 for(const rol of ['ADMIN','OPERATIVO','PROFESOR','ANONIMO']){
  const completo=results.errores.filter(r=>r.rol===rol).length===8&&results.controles.some(r=>r.rol===rol&&r.caso==='alumno-inexistente');
  if(completo&&(rol!=='ADMIN'||results.controles.filter(r=>r.caso==='primera-carga-pendiente').length>=2))continue;
  const context=await browser.newContext({viewport:{width:1366,height:900},locale:'es-AR'});const p=await context.newPage();
  // Un marco genera avisos de frame-ancestors. No se envían esos avisos artificiales.
  await context.route('**/csp-reporte',r=>r.abort());
  p.on('pageerror',e=>results.avisos_js.push({rol,error:e.message}));
  if(rol!=='ANONIMO'){
   await p.goto(origin+'/login');await p.locator('#email').fill(fixture.usuarios[rol].email);await p.locator('#password').fill(fixture.usuarios[rol].password);
   await Promise.all([p.waitForURL(u=>!u.pathname.endsWith('/login')),p.locator('button[type=submit]').click()]);
  }else{
   const r=await capture(p,'/login','control-login',360);delete r.frame;
   assert.equal(r.status,200);assert.equal(r.ancho,360);assert.equal(r.contenido,360);results.controles.push(r);
  }
  for(const status of [404,429,500,503]){
   if(results.errores.filter(r=>r.rol===rol&&r.status===status).length===2)continue;
   const url=status===404?'/direccion-inventada-t15':'/__t15/error/'+status;
   for(const width of [1366,360]){
    const r=await capture(p,url,rol.toLowerCase()+'-'+status,width);const frame=r.frame;delete r.frame;
    assert.equal(r.status,status);assert.equal(r.titulo,titulos[status]);assert.equal(r.ancho,width);assert.equal(r.contenido,width);
    const link=frame.locator('article a').filter({hasText:'Volver'});assert.equal(await link.count(),1);
    const href=await link.getAttribute('href');r.volver=href.replace(origin,'');
    const destino={ADMIN:'/admin/dashboard',OPERATIVO:'/operativo',PROFESOR:'/clases',ANONIMO:'/login'}[rol];
    await Promise.all([frame.waitForURL(u=>u.pathname===destino),link.click()]);
    await frame.waitForLoadState('networkidle');r.destino_final=new URL(frame.url()).pathname;assert.equal(r.destino_final,destino);
    results.errores.push({rol,...r});save();
   }
   const json=await context.request.get(origin+url,{headers:{Accept:'application/json'}});
   assert.equal(json.status(),status);assert(json.headers()['content-type'].includes('application/json'));
  }
  const missing=await context.request.get(origin+'/alumnos/999999',{maxRedirects:0});
  assert.equal(missing.status(),{ADMIN:404,OPERATIVO:404,PROFESOR:403,ANONIMO:302}[rol]);
  results.controles.push({rol,caso:'alumno-inexistente',status:missing.status(),location:missing.headers().location??null});save();
  if(rol==='ADMIN'){
   let n=0;
   for(const ruta of fixture.rutas){
    if(results.menu.some(r=>r.nombre===ruta.nombre&&r.url===ruta.url))continue;
    const response=await context.request.get(origin+ruta.url);const html=response.headers()['content-type']?.includes('text/html');
    const texto=html?await response.text():'';
    if(!html||!texto.includes('ds-nav-link')){results.menu.push({...ruta,status:response.status(),capturas:[],motivo:'Respuesta sin menú compartido'});continue;}
    const capturas=[];
    for(const width of [1366,360]){
     const r=await capture(p,ruta.url,'menu-'+String(++n).padStart(3,'0')+'-'+ruta.nombre,width);const frame=r.frame;delete r.frame;
     const inicio=frame.locator('.ds-sidebar a').filter({hasText:/^\s*Inicio\s*$/});assert.equal(await inicio.count(),1);assert.equal((await frame.locator('.ds-sidebar').innerText()).includes('Dashboard'),false);
     capturas.push(r);
    }
    results.menu.push({...ruta,status:response.status(),capturas});save();
   }
   cli([path.join(__dirname,'fixture.php'),'pendiente']);
   try{
    for(const width of [1366,360]){
     const r=await capture(p,'/admin/dashboard','primera-carga-pendiente',width);const frame=r.frame;delete r.frame;
     assert((await frame.locator('body').innerText()).includes('Inicio, Alumnos y Reportes'));
     results.controles.push({caso:'primera-carga-pendiente',...r});save();
    }
   }finally{
    // Restaura la carga terminada sin ejecutar migraciones ni generar usuarios.
    cli([path.join(__dirname,'fixture.php'),'terminada']);
   }
  }
  await context.close();
 }
 const runtime=path.join(base,'storage/app/errores-t15-runtime/logs/laravel.log');
 results.log_500_registrado=fs.existsSync(runtime)&&fs.readFileSync(runtime,'utf8').includes('FICTICIO-T15: fallo de prueba registrado');
 assert(results.log_500_registrado);results.completo=true;save();
 const fotos=results.errores.map(r=>r.captura);
 const caidas=JSON.parse(fs.readFileSync(path.join(__dirname,'caida.json'))).caida;
 const cards=[...results.errores.filter(r=>r.rol==='ADMIN'),...caidas];
 const menu=results.menu.flatMap(r=>r.capturas);
 fs.writeFileSync(path.join(__dirname,'visor.html'),'<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>T15 — pantallas reales</title><h1>Pantallas de error</h1><p>Wings real, escritorio1366 y marco360. Sin base: puerto cerrado y sesión en base.</p>'+cards.map(r=>'<p><a href="'+r.captura+'">'+r.titulo+' · '+r.ancho+'</a></p><img width="'+(r.ancho===360?360:680)+'" src="'+r.captura+'" alt="'+r.titulo+'">').join('')+'<h2>Menú compartido</h2><p>Todas las rutas ADMIN que devuelven el menú, con datos ficticios.</p><ul>'+menu.map(r=>'<li><a href="'+r.captura+'">'+r.url+' · '+r.ancho+'</a></li>').join('')+'</ul></html>');
 console.log(JSON.stringify({errores:results.errores.length,menu:results.menu.length,capturas_menu:menu.length,base_caida:caidas.length,log_500:results.log_500_registrado,completo:true}));
})().catch(e=>{results.fallo=e.message;save();console.error(e.stack);process.exitCode=1;}).finally(async()=>{await browser?.close();});
