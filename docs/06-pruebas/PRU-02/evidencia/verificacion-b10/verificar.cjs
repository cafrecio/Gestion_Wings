// B10: navegador y pedidos HTTP reales. JavaScript externo; no crea HTML de imitación.
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),cp=require('node:child_process');
const {chromium}=require('playwright');
const base=path.resolve(__dirname,'../../../../..'),origin=process.env.B10_BASE_URL||'http://127.0.0.1:8010';
assert(['localhost','127.0.0.1'].includes(new URL(origin).hostname));
const fixture=JSON.parse(fs.readFileSync(path.join(base,'storage/app/b10-fixture.json')));
assert.equal(fixture.database,'wings_testing_codex');
const results={fecha:new Date().toISOString(),commit:cp.execFileSync('git',['rev-parse','HEAD'],{cwd:base,encoding:'utf8'}).trim(),database:fixture.database,completo:false,casos:[],rutas:[],json:[],errores_js:[]};
const save=()=>fs.writeFileSync(path.join(__dirname,'resultado.json'),JSON.stringify(results,null,2)+'\n');
const record=(punto,caso,ok,datos)=>{results.casos.push({punto,caso,ok,...datos});save();};
const php=(...args)=>JSON.parse(cp.execFileSync('php',[path.join(__dirname,'escenarios.php'),...args],{cwd:base,encoding:'utf8',env:{...process.env,APP_ENV:'testing',DB_CONNECTION:'mysql',DB_DATABASE:'wings_testing_codex'}}));
const fila=(kind,key)=>php('fila',kind,String(key));
let browser;
(async()=>{
 browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_BIN||'C:/Program Files/Google/Chrome/Application/chrome.exe'});
 const contexts={};const pages={};
 async function login(rol){
  const c=await browser.newContext({viewport:{width:1440,height:1000},locale:'es-AR'});const p=await c.newPage();
  p.on('pageerror',e=>results.errores_js.push({rol,error:e.message}));
  p.on('dialog',d=>d.accept());
  await p.goto(origin+'/login');await p.locator('#email').fill(fixture.usuarios[rol].email);await p.locator('#password').fill(fixture.usuarios[rol].password);
  await Promise.all([p.waitForURL(u=>!u.pathname.endsWith('/login')),p.locator('button[type=submit]').click()]);
  contexts[rol]=c;pages[rol]=p;return p;
 }
 const page=await login('ADMIN');
 const csrf=async()=>page.locator('meta[name="csrf-token"]').getAttribute('content');
 async function request(route,form,method='POST',referer){
  const response=await page.request.fetch(origin+route,{method,maxRedirects:0,headers:{Referer:origin+(referer||new URL(page.url()).pathname),'Accept':'text/html'},form:{_token:await csrf(),...form}});
  return {status:response.status(),location:response.headers().location||'',body:await response.text()};
 }
 async function capture(name,locator){await page.waitForLoadState('networkidle');await locator.screenshot({path:path.join(__dirname,name+'.png')});}
 function validProfessor(n,extra={}){return {nombre:'FICTICIO B10 '+n,apellido:'Profesor',dni:String(99110000+n),fecha_nacimiento:'1990-01-01',direccion:'Domicilio ficticio '+n,localidad:'Localidad ficticia',telefono:'1100000000',email:'prof'+n+'@b10.ficticio.test',deporte_id:String(fixture.deporte_id),valor_hora:'5000',...extra};}
 let n=0;let editable;
 for(const [nombre,value,esperado]of [['alias','prof.ficticio.b10','prof.ficticio.b10'],['cbu','0170099220000067797912','0170099220000067797912'],['cbu-espacios',' 0170 0992 2000 0067 7979 12 ','0170099220000067797912'],['sin-dato','',null]]){
  await page.goto(origin+'/profesores/create');const data=validProfessor(++n,{cbu_alias:value});const antes=fila('profesor',data.dni);
  const http=await request('/profesores',data);const despues=fila('profesor',data.dni);const row=despues.filas[0];
  const ok=http.status===302&&row?.cbu_alias===esperado&&antes.filas.length===0;
  record(1,'alta-'+nombre,ok,{enviado:value,http:{status:http.status,location:http.location},antes,despues,esperado});assert(row,'Se requiere profesor creado para seguir');if(nombre==='alias')editable={id:row.id,data};
 }
 for(const [nombre,value,esperado]of [['borrar','',null],['cambiar','nuevo.alias.b10','nuevo.alias.b10']]){
  await page.goto(origin+'/profesores/'+editable.id+'/edit');const antes=fila('profesor',editable.data.dni);
  const http=await request('/profesores/'+editable.id,{...editable.data,cbu_alias:value},'PUT');const despues=fila('profesor',editable.data.dni);
  record(1,'editar-'+nombre,http.status===302&&despues.filas[0]?.cbu_alias===esperado,{enviado:value,http:{status:http.status,location:http.location},antes,despues,esperado});
 }
 const malos=[['alias-5','abcde'],['alias-21','a'.repeat(21)],['numeros-21','1'.repeat(21)],['numeros-23','1'.repeat(23)],['22-con-letra','1'.repeat(10)+'a'+'1'.repeat(11)],['alias-espacio','alias con espacio'],['alias-enie','alias.ñuevo'],['alias-arroba','alias@prueba'],['alias-guion-bajo','alias_prueba'],['numerico-corto','12345678'],['texto-200','a'.repeat(200)],['script','<script>window.__b10xss=true</script>'],['alias-tab','alias\tprueba'],['alias-salto','alias\nprueba']];
 for(const [nombre,value]of malos){
  await page.goto(origin+'/profesores/create');const data=validProfessor(++n,{cbu_alias:value});const antes=fila('profesor',data.dni);const http=await request('/profesores',data);const despues=fila('profesor',data.dni);
  let mensaje='',conservados={},ejecuto_script=false;
  if(http.status===302&&new URL(http.location,origin).pathname==='/profesores/create'){
   await page.goto(new URL(http.location,origin).href);await page.waitForLoadState('networkidle');
   mensaje=(await page.locator('#cbu_alias').locator('..').innerText()).trim();
   for(const field of ['nombre','apellido','dni','fecha_nacimiento','direccion','localidad','telefono','email','deporte_id','cbu_alias'])conservados[field]=await page.locator('[name="'+field+'"]').inputValue();
   ejecuto_script=await page.evaluate(()=>window.__b10xss===true);
   if(['alias-5','texto-200','script'].includes(nombre))await capture('rechazo-'+nombre,page.locator('#cbu_alias').locator('..'));
  }
  const conservacion=Object.keys(conservados).length===10&&Object.entries(conservados).every(([k,v])=>v===data[k]);
  const rechazado=despues.filas.length===0;
  const mensaje_castellano=!!mensaje&&!/\bThe\b|\bfield must\b/.test(mensaje);
  record(2,nombre,rechazado&&conservacion&&!ejecuto_script&&mensaje_castellano,{enviado:value,http:{status:http.status,location:http.location},antes,despues,rechazado,mensaje,mensaje_castellano,otros_campos_conservados:conservacion,conservados,ejecuto_script});
  if(!rechazado){await page.goto(origin+'/profesores/'+despues.filas[0].id);await capture('aceptado-indebido-'+nombre,page.locator('main'));}
 }
 // Bordes admitidos por la especificación: seis/veinte caracteres, mayúsculas, puntos y guiones.
 for(const [nombre,value]of [['alias-6','abc123'],['alias-20','ABCDEFGHIJKLMNOPQRST'],['mayusculas','PROFESOR.B10'],['guiones','profesor-b10']]){
  await page.goto(origin+'/profesores/create');const data=validProfessor(++n,{cbu_alias:value});const http=await request('/profesores',data);const despues=fila('profesor',data.dni);
  record(2,nombre,http.status===302&&despues.filas[0]?.cbu_alias===value,{enviado:value,despues,http:{status:http.status,location:http.location},longitud:value.length});
 }
 // Usuarios: el banco se guarda según rol, incluso ante POST directo con campo oculto.
 let creadoOp;
 for(const rol of ['OPERATIVO','ADMIN','PROFESOR']){
  await page.goto(origin+'/usuarios/create');
  let profesorId;
  if(rol==='PROFESOR'){const options=await page.locator('#profesor_id option').evaluateAll(os=>os.filter(o=>o.value).map(o=>o.value));assert(options.length);profesorId=options[0];}
  const email='alta-'+rol.toLowerCase()+'@b10.ficticio.test';const pass=require('node:crypto').randomBytes(12).toString('hex');
  const data={name:rol+' FICTICIO ALTA B10',email,rol,password:pass,password_confirmation:pass,cbu_alias:'usuario.ficticio.b10',...(profesorId?{profesor_id:profesorId}:{})};
  const antes=fila('usuario',email);const http=await request('/usuarios',data);const despues=fila('usuario',email);const esperado=rol==='OPERATIVO'?data.cbu_alias:null;
  record(4,'alta-'+rol,http.status===302&&despues.filas[0]?.cbu_alias===esperado,{antes,despues,esperado,http:{status:http.status,location:http.location}});if(rol==='OPERATIVO'){assert(despues.filas[0]);creadoOp=despues.filas[0];}
 }
 for(const rol of ['ADMIN','OPERATIVO']){
  await page.goto(origin+'/usuarios/'+creadoOp.id+'/edit');const antes=fila('usuario',creadoOp.email);
  const http=await request('/usuarios/'+creadoOp.id,{name:creadoOp.name,email:creadoOp.email,rol,cbu_alias:rol==='ADMIN'?'usuario.ficticio.b10':''},'PUT');const despues=fila('usuario',creadoOp.email);
  record(4,'cambio-rol-a-'+rol,http.status===302&&despues.filas[0]?.rol===rol&&despues.filas[0]?.cbu_alias===null,{antes,despues,http:{status:http.status,location:http.location}});
 }
 await page.goto(origin+'/usuarios/create');
 for(const rol of ['ADMIN','OPERATIVO','PROFESOR','OPERATIVO','ADMIN']){
  await page.locator('input[name=rol][value="'+rol+'"]').check();await page.waitForLoadState('networkidle');const visible=await page.locator('#panel-operativo').isVisible();
  record(4,'panel-en-vivo-'+rol,visible===(rol==='OPERATIVO'),{rol,visible});if(['OPERATIVO','PROFESOR'].includes(rol))await capture('panel-'+rol,page.locator('main'));
 }
 await page.goto(origin+'/usuarios/'+fixture.usuarios.OPERATIVO.id+'/edit');const panel=await page.locator('#panel-operativo').isVisible();const valor=await page.locator('#cbu_alias').inputValue();
 record(4,'abrir-operativo-existente',panel&&valor==='FICTICIO.OP.B10',{visible:panel,valor});await capture('edicion-operativo-inicial',page.locator('#panel-operativo'));
 await page.goto(origin+'/usuarios/'+fixture.usuarios.ADMIN.id+'/edit');const antesSelf=fila('usuario',fixture.usuarios.ADMIN.email);
 const self=await request('/usuarios/'+fixture.usuarios.ADMIN.id,{name:'ADMIN FICTICIO B10 editado',email:fixture.usuarios.ADMIN.email,rol:'ADMIN',cbu_alias:'ignorado.admin'},'PUT');const despuesSelf=fila('usuario',fixture.usuarios.ADMIN.email);
 record(4,'admin-edita-su-cuenta',self.status===302&&despuesSelf.filas[0]?.rol==='ADMIN'&&despuesSelf.filas[0]?.name==='ADMIN FICTICIO B10 editado'&&despuesSelf.filas[0]?.cbu_alias===null,{antes:antesSelf,despues:despuesSelf,http:{status:self.status,location:self.location}});
 // Generar, cerrar y pagar por las rutas reales, con una clase ficticia de 90 minutos.
 await page.goto(origin+'/liquidaciones/crear');const generar=await request('/liquidaciones',{profesor_id:String(fixture.profesor_id),mes:String(fixture.mes),anio:String(fixture.anio)});
 const match=new URL(generar.location,origin).pathname.match(/^\/liquidaciones\/(\d+)$/);assert(match,'La liquidación debe generarse por el sistema');const liqId=Number(match[1]);
 await page.goto(origin+'/liquidaciones/'+liqId);let raw=await page.content();let estado=fila('pago',liqId);
 record(3,'abierta',estado.liquidacion.estado==='ABIERTA',{estado,alias_visible:raw.includes('FICTICIO.PROF.B10'),formulario_pago:await page.locator('form[action$="/pagar"]').count()});
 await request('/liquidaciones/'+liqId+'/cerrar',{});await page.goto(origin+'/liquidaciones/'+liqId);raw=await page.content();estado=fila('pago',liqId);
 const payForm=page.locator('form[action$="/pagar"]');const contenedor=payForm.locator('..');
 const ubicacion=await contenedor.evaluate(el=>{const s=el.querySelector('strong'),f=el.querySelector('form');return !!(s&&f&&(s.compareDocumentPosition(f)&Node.DOCUMENT_POSITION_FOLLOWING));});
 record(3,'cerrada-con-dato',estado.liquidacion.estado==='CERRADA'&&raw.includes('FICTICIO.PROF.B10')&&ubicacion,{estado,alias_visible:true,dato_antes_formulario:ubicacion});await capture('liquidacion-cerrada-dato',contenedor);
 // El borrado del dato también se hace por la aplicación.
 await page.goto(origin+'/profesores/'+fixture.profesor_id+'/edit');const datosProfe=await page.locator('main form').first().evaluate(f=>Object.fromEntries(new FormData(f).entries()));delete datosProfe._token;delete datosProfe._method;
 await request('/profesores/'+fixture.profesor_id,{...datosProfe,cbu_alias:''},'PUT');await page.goto(origin+'/liquidaciones/'+liqId);
 const aviso=page.getByText('no tiene CBU ni alias cargado.',{exact:false});const href=await aviso.locator('a').getAttribute('href');
 const okLink=new URL(href,origin).pathname==='/profesores/'+fixture.profesor_id+'/edit';await aviso.locator('a').click();
 record(3,'cerrada-sin-dato',okLink&&new URL(page.url()).pathname==='/profesores/'+fixture.profesor_id+'/edit',{mensaje:'no tiene CBU ni alias cargado',href,edicion_al_abrir:new URL(page.url()).pathname,fila:fila('profesor','99101000')});
 await request('/profesores/'+fixture.profesor_id,{...datosProfe,cbu_alias:'FICTICIO.PROF.B10'},'PUT');await page.goto(origin+'/liquidaciones/'+liqId);
 const comision=php('comision');await page.goto(origin+'/liquidaciones/'+comision.id);await page.getByText('Editar monto final',{exact:true}).click();
 const labels=await page.locator('label[for=monto_final],label[for=motivo_ajuste],label[for=tipo_caja_id],label[for=fecha_pago],label[for=observaciones]').evaluateAll(ns=>ns.map(n=>({for:n.htmlFor,text:n.textContent.trim(),iconos:n.querySelectorAll('svg').length})));
 record(3,'convivencia-con-580f380',labels.some(n=>n.text==='Monto ajustado')&&labels.every(n=>n.iconos===1)&&(await page.content()).includes('FICTICIO.COM.B10'),{labels,ayuda:await page.locator('#monto_final_ayuda').textContent(),liquidacion_comision:comision.id});
 await capture('convivencia-monto-ajustado-banco',page.locator('main'));await page.goto(origin+'/liquidaciones/'+liqId);
 await page.locator('#tipo_caja_id').selectOption(String(fixture.tipo_caja_id));await page.locator('#fecha_pago').fill(fixture.hoy);await page.locator('#observaciones').fill('Pago FICTICIO B10 de punta a punta');
 await Promise.all([page.waitForResponse(r=>r.url()===origin+'/liquidaciones/'+liqId+'/pagar'&&r.request().method()==='POST'),page.locator('form[action$="/pagar"] button[type=submit]').click()]);
 await page.goto(origin+'/liquidaciones/'+liqId);await page.waitForLoadState('networkidle');estado=fila('pago',liqId);raw=await page.content();
 record(3,'pagada',estado.liquidacion.estado_pago==='PAGADA'&&estado.movimientos.length===1&&Number(estado.movimientos[0].monto)===-7500,{estado,alias_visible:raw.includes('FICTICIO.PROF.B10'),formulario_pago:await page.locator('form[action$="/pagar"]').count()});await capture('liquidacion-pagada',page.locator('main'));
 const pdf=await page.request.get(origin+'/recibos/liquidacion/'+liqId+'?regenerar=1',{maxRedirects:0});assert.equal(pdf.status(),200);assert(pdf.headers()['content-type'].includes('pdf'));fs.writeFileSync(path.join(__dirname,'recibo-liquidacion.pdf'),await pdf.body());
 record(5,'recibo-admin',true,{url:'/recibos/liquidacion/'+liqId,status:pdf.status(),content_type:pdf.headers()['content-type'],archivo:'recibo-liquidacion.pdf'});
 // Buscar ambos valores exactos en todas las rutas GET de la aplicación, con los dos roles.
 const priv=php('privacidad');record(5,'datos-cargados-para-privacidad',true,{filas:priv});
 const actual=JSON.parse(fs.readFileSync(path.join(base,'storage/app/b10-fixture.json')));
 const routes=JSON.parse(fs.readFileSync(path.join(base,'storage/app/b10-rutas.json'),'utf8').replace(/^\uFEFF/,''));
 const reemplazar=uri=>uri.replace(/\{([^}]+)\}/g,(_,name)=>String(name==='alumnoId'?actual.alumno_id:name==='liquidacionId'?liqId:name==='rubroId'?1:name==='planId'?fixture.plan_id:name==='cajaId'?actual.caja_id:name==='movId'?actual.mov_id:uri.startsWith('profesores')?fixture.profesor_id:uri.startsWith('usuarios')?fixture.usuarios.OPERATIVO.id:uri.startsWith('clases')?actual.clase_privacidad_id:uri.startsWith('alumnos')?actual.alumno_id:uri.startsWith('grupos')?fixture.grupo_id:uri.startsWith('liquidaciones')?liqId:uri.startsWith('caja')?actual.caja_id:uri.startsWith('tipos-caja')?fixture.tipo_caja_id:1));
 const paths=[...new Set(routes.filter(r=>r.method.includes('GET')&&!r.uri.startsWith('storage/')&&!r.uri.startsWith('sanctum/')&&!['login','logout','up','/'].includes(r.uri)).map(r=>'/'+reemplazar(r.uri)))];
 for(const rol of ['OPERATIVO','PROFESOR']){
  const p=await login(rol);const c=contexts[rol];
  for(const url of paths){
   const r=await c.request.get(origin+url,{maxRedirects:0});const tipo=r.headers()['content-type']||'';const body=await r.text();const filtraciones=['FICTICIO.PROF.B10','FICTICIO.OP.B10'].filter(v=>body.includes(v));
   const item={rol,url,status:r.status(),content_type:tipo,location:r.headers().location||'',valores_encontrados:filtraciones};results.rutas.push(item);assert.equal(filtraciones.length,0,'Filtración de dato bancario '+rol+' '+url);
   if(tipo.includes('json')){const j=JSON.parse(body);results.json.push({...item,body:j,tiene_cbu_alias:JSON.stringify(j).includes('cbu_alias')});}
  }
  const sensibles=['/profesores/'+fixture.profesor_id,'/profesores/'+fixture.profesor_id+'/edit','/profesores','/usuarios','/usuarios/'+fixture.usuarios.OPERATIVO.id+'/edit','/liquidaciones/'+liqId,'/recibos/liquidacion/'+liqId];
  for(const url of sensibles){const r=results.rutas.find(r=>r.rol===rol&&r.url===url);assert(r);record(5,rol+' restringido '+url,r.status===403&&r.valores_encontrados.length===0,r);}
  record(5,rol+' HTML accesible',true,{pantallas:results.rutas.filter(r=>r.rol===rol&&r.status===200&&r.content_type.includes('html')).map(r=>r.url),valores_encontrados:[]});
 }
 // JSON de funciones reales: búsqueda, comprobaciones y actualización de profesores.
 await page.goto(origin+'/clases/'+actual.clase_privacidad_id);const token=await csrf();
 for(const rol of ['ADMIN','OPERATIVO','PROFESOR']){
  const c=contexts[rol];
  for(const url of ['/alumnos/autocomplete?q=FICTICIO','/grupos/check-disponible?deporte_id='+fixture.deporte_id+'&nivel_id=1','/usuarios/check-email?email='+encodeURIComponent(fixture.usuarios.OPERATIVO.email),'/alumnos/inscripcion-preview?dni=99101099&fecha_alta='+fixture.hoy]){
   const r=await c.request.get(origin+url,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},maxRedirects:0});const text=await r.text();const item={rol,url,status:r.status(),body:JSON.parse(text),tiene_cbu_alias:text.includes('cbu_alias'),valores_encontrados:['FICTICIO.PROF.B10','FICTICIO.OP.B10'].filter(v=>text.includes(v))};results.json.push(item);record(5,'JSON '+rol+' '+url,!item.tiene_cbu_alias&&item.valores_encontrados.length===0,item);
  }
 }
 const reasignar=await page.request.patch(origin+'/clases/'+actual.clase_privacidad_id+'/profesores',{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},form:{_token:token,'profesores[]':String(fixture.profesor_id)}});
 const reasignarTexto=await reasignar.text();results.json.push({rol:'ADMIN',url:'/clases/'+actual.clase_privacidad_id+'/profesores',status:reasignar.status(),body:JSON.parse(reasignarTexto),tiene_cbu_alias:reasignarTexto.includes('cbu_alias')});
 record(5,'JSON profesores de clase',reasignar.status()===200&&!reasignarTexto.includes('cbu_alias')&&!reasignarTexto.includes('FICTICIO.PROF.B10'),results.json.at(-1));
 // Primera carga por Excel con columnas bancarias ya migradas y cuentas existentes.
 const excel=php('excel');assert.deepEqual(excel.errores,[]);await page.goto(origin+'/sistema/primera-carga');let t=await csrf();
 const revisar=await page.request.post(origin+'/sistema/primera-carga/revisar',{maxRedirects:0,headers:{Referer:origin+'/sistema/primera-carga'},multipart:{_token:t,archivo:{name:'b10-carga.xlsx',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',buffer:fs.readFileSync(path.join(base,'storage/app/b10-carga.xlsx'))}}});
 assert.equal(revisar.status(),302);await page.goto(origin+'/sistema/primera-carga');t=await csrf();const cargar=await page.request.post(origin+'/sistema/primera-carga/cargar',{maxRedirects:0,headers:{Referer:origin+'/sistema/primera-carga'},form:{_token:t,confirmar:'1'}});
 const datosExcel=php('excel-comprobar');record(6,'primera-carga-excel',cargar.status()===302&&datosExcel.estado==='TERMINADA'&&datosExcel.alumno?.dni==='99101098',{revision_sin_errores:true,http_revisar:revisar.status(),http_cargar:cargar.status(),despues:datosExcel});
 results.completo=true;save();console.log('B10: '+results.casos.length+' casos; '+results.rutas.length+' rutas; fallas de criterio: '+results.casos.filter(c=>!c.ok).map(c=>c.caso).join(', '));
})().catch(e=>{results.error_programa=e.message;save();console.error(e.message);process.exitCode=1;}).finally(async()=>{if(browser)await browser.close();});
