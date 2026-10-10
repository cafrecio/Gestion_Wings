// A42: navegador real y endpoints reales; todo el JS está en este archivo externo.
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const {chromium} = require('playwright');
const base = path.resolve(__dirname, '../../../../..');
const origin = process.env.A42_BASE_URL || 'http://127.0.0.1:8042';
assert(['127.0.0.1', 'localhost'].includes(new URL(origin).hostname), 'Solo sitio local de prueba');
const fixture = JSON.parse(fs.readFileSync(path.join(base, 'storage/app/a42-fixture.json'), 'utf8'));
assert.equal(fixture.database, 'wings_testing_codex');
const results = []; const jsChecks = [];
const prompt = 'Ingrese el DNI y la fecha real de ingreso para consultar la inscripción.';
const paidMessage = 'Esta persona ya tiene una inscripción registrada. Saldo: $0,00';
const pendingMessage = 'Esta persona ya tiene una inscripción registrada. Saldo: $5.000,00';
let browser;
(async () => {
  browser = await chromium.launch({headless:true, executablePath:process.env.CHROME_BIN || 'C:/Program Files/Google/Chrome/Application/chrome.exe'});
  const context = await browser.newContext({viewport:{width:1440,height:1000}, locale:'es-AR'});
  const page = await context.newPage();
  const errors = [];
  const requests = [];
  page.on('pageerror', e => errors.push(e.message));
  page.on('dialog', d => d.accept());
  page.on('response', async r => {
    if (r.url().includes('/alumnos/inscripcion-preview') || r.url().includes('/alumnos/cuota-alta-preview')) requests.push({url:r.url(), status:r.status()});
  });
  const waitPreview = () => page.waitForResponse(r => r.url().includes('/alumnos/inscripcion-preview') && r.status()===200);
  async function settle() { await page.waitForLoadState('networkidle'); await page.evaluate(() => document.fonts.ready); }
  async function assertNoInline(name) {
    const inline=await page.locator('script:not([src])').evaluateAll(nodes=>nodes.filter(n=>n.textContent.trim() && n.type!=='application/json').length);
    assert.equal(inline,0,'JavaScript debe ser externo en '+name);
    jsChecks.push({pantalla:name,inline});
  }
  async function capture(name, selector='#inscripcion-aviso') {
    await settle();
    await assertNoInline(name); const notice=page.locator(selector);
    await notice.screenshot({path:path.join(__dirname, name+'.png')});
    return (await notice.textContent()).trim();
  }
  async function verifyNotice(name, expected, navigate) {
    const before=requests.length;
    if(navigate) { const response=waitPreview(); await page.goto(origin+navigate); await response; }
    await page.waitForFunction(text => document.querySelector('#inscripcion-aviso')?.textContent.trim()===text, expected);
    const text=await capture(name);
    assert.equal(text,expected);
    results.push({case:name, aviso:text, consultas:requests.slice(before), sin_tocar_datos:!!navigate});
    return text;
  }
  await page.goto(origin+'/login');
  await page.locator('#email').fill(fixture.email);
  await page.locator('#password').fill(fixture.password);
  await Promise.all([page.waitForURL(u=>!u.pathname.endsWith('/login')), page.locator('button[type=submit]').click()]);
  const expected={pendiente:pendingMessage,pagada:paidMessage,sin_cargo:'Editar el ingreso no genera inscripción retroactiva.'};
  for(const [caseName, alumno] of Object.entries(fixture.alumnos)) {
    await verifyNotice('edicion-'+caseName,expected[caseName],'/alumnos/'+alumno.id+'/edit');
    assert.equal(await page.locator('#dni').inputValue(), alumno.dni);
    assert.equal(await page.locator('#fecha_alta').inputValue(), alumno.fecha_alta);
  }
  const emptyBefore=requests.length;
  await page.goto(origin+'/alumnos/create'); await settle();
  assert.equal(await page.locator('#dni').inputValue(),'');
  assert.equal((await page.locator('#inscripcion-aviso').textContent()).trim(),prompt);
  assert.equal(requests.slice(emptyBefore).filter(r=>r.url.includes('inscripcion-preview')).length,0);
  assert.equal(await page.locator('#cuota-alta-aviso').isVisible(),false);
  results.push({case:'alta-vacia',aviso:await capture('alta-vacia'),sin_consulta_inscripcion:true,cuota_cerrada_visible:false});
  // Error real del servidor: POST incompleto con CSRF y sesión del navegador, sin seguir el 302.
  const csrf=await page.locator('[data-alumno-form] input[name="_token"]').getAttribute('value');
  const altaToken=await page.locator('input[name="alta_token"]').getAttribute('value');
  const response=await page.request.post(origin+'/alumnos',{maxRedirects:0,headers:{Referer:origin+'/alumnos/create'},form:{
    _token:csrf,alta_token:altaToken,nombre:'',apellido:'FICTICIO A42 ERROR',dni:'99042004',fecha_nacimiento:'2000-01-01',celular:'1100000000',fecha_alta:fixture.anterior,
    deporte_id:String(fixture.deporte_id),grupo_id:String(fixture.grupo_id),plan_id:String(fixture.plan_id),generar_cuota_actual:'1',inscripcion_importe_visto:'5000',cuota_periodo_visto:fixture.periodo,cuota_importe_visto:'30000'
  }});
  assert.equal(response.status(),302);
  const location=new URL(response.headers().location,origin);
  assert.equal(location.pathname,'/alumnos/create');
  const errorBefore=requests.length; const initial=waitPreview(); await page.goto(location.href); await initial;
  await verifyNotice('alta-con-error','Corresponde inscripción por única vez: $5.000,00');
  results[results.length-1].sin_tocar_datos=true; results[results.length-1].consultas=requests.slice(errorBefore);
  assert.equal(await page.locator('[data-alumno-form]').getAttribute('data-con-errores'),'1');
  assert.equal(await page.locator('#dni').inputValue(),'99042004');
  assert.equal(await page.locator('#fecha_alta').inputValue(),fixture.anterior);
  assert.equal(await page.locator('#plan_id').inputValue(),String(fixture.plan_id));
  await page.waitForFunction(()=>!document.querySelector('#cuota-alta-aviso').hidden && document.querySelector('#cuota-importe-visto').value==='30000');
  const choice=page.locator('input[name="generar_cuota_actual"][value="1"]');
  assert.equal(await choice.isChecked(),true);
  assert.equal(await choice.isEnabled(),true);
  assert.equal(await choice.getAttribute('required'),'');
  assert.equal(await page.locator('#cuota-periodo-visto').inputValue(),fixture.periodo);
  results.push({case:'cuota-tras-error',aviso:await capture('alta-error-cuota','#cuota-alta-aviso'),importe:30000,periodo:fixture.periodo,decision_si_restaurada:true,radio_obligatorio:true});
  // Ambos cambios existentes continúan funcionando en edición, sin guardar.
  const pendiente=fixture.alumnos.pendiente;
  await verifyNotice('edicion-antes-cambios',pendingMessage,'/alumnos/'+pendiente.id+'/edit');
  let changed=waitPreview(); await page.locator('#dni').fill(fixture.alumnos.pagada.dni); await page.locator('#dni').press('Tab'); await changed;
  await verifyNotice('cambio-dni',paidMessage);
  changed=waitPreview(); await page.locator('#dni').fill(pendiente.dni); await page.locator('#dni').press('Tab'); await changed;
  changed=waitPreview(); await page.locator('#fecha_alta').fill(fixture.anterior); await page.locator('#fecha_alta').press('Tab'); await changed;
  await verifyNotice('cambio-fecha','Se conservará una única inscripción de $5.000,00');
  // Alta y decisión de cuota cerrada usando controles reales.
  await page.goto(origin+'/alumnos/create'); await settle();
  await page.locator('#deporte_id').selectOption(String(fixture.deporte_id));
  await page.locator('#grupo_id').selectOption(String(fixture.grupo_id));
  await page.locator('#plan_id').selectOption(String(fixture.plan_id));
  await page.locator('#fecha_alta').fill(fixture.anterior); await page.locator('#fecha_alta').press('Tab');
  await page.waitForFunction(()=>!document.querySelector('#cuota-alta-aviso').hidden && document.querySelector('#cuota-importe-visto').value==='30000');
  const no=page.locator('input[name="generar_cuota_actual"][value="0"]');
  assert.equal(await no.isEnabled(),true); await no.check(); assert.equal(await no.isChecked(),true);
  results.push({case:'alta-mes-cerrado',aviso:await capture('alta-mes-cerrado-cuota','#cuota-alta-aviso'),importe:30000,periodo:await page.locator('#cuota-periodo-visto').inputValue(),decision_no_seleccionable:true});
  await page.locator('#fecha_alta').fill(fixture.hoy); await page.locator('#fecha_alta').press('Tab');
  await page.waitForFunction(()=>document.querySelector('#cuota-alta-aviso').hidden && document.querySelector('#fecha_alta').validationMessage==='');
  assert.equal(await no.isChecked(),false);
  results.push({case:'alta-mes-corriente',cuota_cerrada_visible:false,decision_borrada_al_cambiar_fecha:true});
  await assertNoInline('alta-mes-corriente');
  results.push({case:'javascript',controles:jsChecks,archivos_externos:true,errores:errors}); assert.deepEqual(errors,[]);
  // Control con dientes: quitar SOLO la llamada inicial del recurso JS externo en memoria.
  const negative=await context.newPage(); let mutated=false,negativeRequests=0;
  negative.on('response',r=>{if(r.url().includes('inscripcion-preview'))negativeRequests++;});
  await negative.route('**/build/assets/alumnos-inscripcion-*.js',async route=>{
    const resource=await route.fetch(); const original=await resource.text();
    const pattern=/(\.addEventListener\("change",(\w+)\),\w+\.addEventListener\("change",\2\)),\2\(\)(?=})/;
    assert(pattern.test(original),'Ubicar una sola llamada inicial del bundle');
    const modified=original.replace(pattern,'$1'); assert.notEqual(modified,original); mutated=true;
    await route.fulfill({response:resource,body:modified});
  });
  await negative.goto(origin+'/alumnos/'+pendiente.id+'/edit'); await negative.waitForLoadState('networkidle');
  assert.equal(mutated,true); assert.equal(negativeRequests,0);
  assert.equal((await negative.locator('#inscripcion-aviso').textContent()).trim(),prompt);
  await negative.locator('#inscripcion-aviso').screenshot({path:path.join(__dirname,'control-sin-llamada-inicial.png')});
  const retry=negative.waitForResponse(r=>r.url().includes('inscripcion-preview')&&r.status()===200);
  await negative.locator('#dni').fill(' '+pendiente.dni+' '); await negative.locator('#dni').press('Tab'); await retry;
  await negative.waitForFunction(text=>document.querySelector('#inscripcion-aviso').textContent.trim()===text,pendingMessage);
  results.push({case:'control-negativo',recurso_externo_mutado_solo_en_memoria:true,sin_llamada_inicial_no_consulta:true,al_cambiar_dni_consulta:true,ningun_archivo_aplicacion_modificado:true});
  const dbCheck=require('node:child_process').spawnSync('php',[path.join(__dirname,'preparar.php'),'--comprobar'],{cwd:base,stdio:'inherit',env:{...process.env,APP_ENV:'testing',DB_DATABASE:'wings_testing_codex',DB_CONNECTION:'mysql'}});
  assert.equal(dbCheck.status,0,'Comprobar filas concretas y ausencia de alta inválida');
  fs.writeFileSync(path.join(__dirname,'resultado-navegador.json'),JSON.stringify({fecha:new Date().toISOString(),database:fixture.database,base_commit:require('node:child_process').execFileSync('git',['rev-parse','HEAD'],{cwd:base,encoding:'utf8'}).trim(),cases:results},null,2)+'\n');
  console.log('A42: '+results.length+' casos aprobados; capturas reales y resultado guardados.');
})().catch(error=>{console.error(error.message);process.exitCode=1;}).finally(async()=>{if(browser)await browser.close();});