// Recorrido de A10 en Chrome (CDP) contra la aplicación real, base wings_testing_claude, puerto 8811.
// Entra por el formulario de login, elige períodos con el teclado, pulsa Limpiar/Nuevo/paginación con el mouse.
import {spawn} from 'node:child_process';
import {writeFile, mkdir} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
import os from 'node:os';

const carpeta = path.dirname(fileURLToPath(import.meta.url));
const salida = path.join(carpeta, 'capturas');
const BASE = 'http://127.0.0.1:8811';
const PUERTO = 9247;
const clave = process.env.A10_CLAVE;
if (!clave) throw new Error('Falta A10_CLAVE');
const pausa = ms => new Promise(r => setTimeout(r, ms));
await mkdir(salida, {recursive: true});

const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--remote-debugging-port=' + PUERTO, '--remote-debugging-address=127.0.0.1',
  '--user-data-dir=' + path.join(os.tmpdir(), 'wings-chrome-a10-claude-' + process.pid),
  '--no-first-run', '--no-default-browser-check', '--disable-background-networking', '--window-size=1280,900', 'about:blank',
], {windowsHide: true, stdio: 'ignore'});

const hechos = [];
let fallos = 0;
const anotar = (paso, ok, datos = {}) => { hechos.push({paso, ok, ...datos}); if (!ok) fallos++; console.log(ok ? 'OK  ' : 'MAL ', paso, JSON.stringify(datos)); };
let ws;
try {
  for (let i = 0; i < 60; i++) { try { await fetch(`http://127.0.0.1:${PUERTO}/json/version`); break; } catch { if (i === 59) throw new Error('Chrome no responde'); await pausa(250); } }
  const t = await (await fetch(`http://127.0.0.1:${PUERTO}/json/new?about:blank`, {method: 'PUT'})).json();
  ws = new WebSocket(t.webSocketDebuggerUrl); await new Promise((r, j) => { ws.onopen = r; ws.onerror = j; });
  let id = 0; const pendientes = new Map();
  ws.onmessage = e => { const m = JSON.parse(e.data); if (m.id && pendientes.has(m.id)) { const p = pendientes.get(m.id); pendientes.delete(m.id); clearTimeout(p.timer); m.error ? p.j(new Error(JSON.stringify(m.error))) : p.r(m.result); } };
  const cdp = (method, params = {}) => new Promise((r, j) => { const n = ++id; const timer = setTimeout(() => { pendientes.delete(n); j(new Error('Sin respuesta: ' + method)); }, 20000); pendientes.set(n, {r, j, timer}); ws.send(JSON.stringify({id: n, method, params})); });
  const evaluar = async expression => { const r = await cdp('Runtime.evaluate', {expression, awaitPromise: true, returnByValue: true}); if (r.exceptionDetails) throw new Error(JSON.stringify(r.exceptionDetails)); return r.result.value; };
  const esperar = async (condicion, que) => { for (let i = 0; i < 60; i++) { try { if (await evaluar(condicion)) return true; } catch {} await pausa(200); } throw new Error('No ocurrió: ' + que); };
  await cdp('Page.enable');
  await cdp('Emulation.setDeviceMetricsOverride', {width: 1280, height: 900, deviceScaleFactor: 1, mobile: false});

  const W = marco => marco ? "document.querySelector('iframe').contentWindow" : 'window';
  const ir = async (ruta, listo = '#cashflow-periodo') => { await cdp('Page.navigate', {url: BASE + ruta}); await esperar(`document.readyState==='complete'&&!!document.querySelector('${listo}')`, ruta); };
  const irMarco = async (ruta, listo = '#cashflow-periodo') => { await cdp('Page.navigate', {url: BASE + '/__marco375?pagina=' + encodeURIComponent(ruta)}); await esperar(`(()=>{const w=${W(true)};return w&&w.document.readyState==='complete'&&!!w.document.querySelector('${listo}')})()`, 'marco ' + ruta); };
  // Lo que una persona lee: período, los tres totales, contador, campos del formulario y filas.
  const leer = (marco = false) => evaluar(`(()=>{const w=${W(marco)},d=w.document;const f=d.querySelector('#filtros-form');const campos={};
    for(const e of f.querySelectorAll('[name]'))campos[e.name]=e.value;
    const barras=[...d.querySelectorAll('.stats-bar')].map(e=>e.innerText.replace(/\\s+/g,' ').trim());
    const a=t=>[...d.querySelectorAll('a')].find(e=>e.textContent.trim()===t);
    return {url:w.location.pathname+w.location.search,periodo:d.querySelector('#cashflow-periodo strong').innerText.trim(),totales:barras[0],contador:barras[1],campos,
      visibles:[...f.querySelectorAll('select,input:not([type=hidden])')].map(e=>e.name),
      filas:d.querySelectorAll('tbody tr').length,limpiar:a('Limpiar')?{atributo:a('Limpiar').getAttribute('href'),destino:a('Limpiar').href}:null,nuevo:a('Nuevo')?.href??null,
      ancho:w.innerWidth,anchoDocumento:d.documentElement.scrollWidth};})()`);
  const captura = async (nombre, marco = false) => {
    const params = {format: 'png', captureBeyondViewport: false};
    if (marco) { const r = await evaluar(`(()=>{const b=document.querySelector('iframe').getBoundingClientRect();return {x:b.x,y:b.y,width:b.width,height:b.height,scale:1};})()`); params.clip = r; }
    const {data} = await cdp('Page.captureScreenshot', params);
    await writeFile(path.join(salida, nombre + '.png'), Buffer.from(data, 'base64'));
  };
  const centro = selectorJs => evaluar(`(()=>{const e=${selectorJs};e.scrollIntoView({block:'center'});const b=e.getBoundingClientRect();return {x:b.x+b.width/2,y:b.y+b.height/2};})()`);
  const pulsar = async selectorJs => { const {x, y} = await centro(selectorJs); await pausa(100);
    await cdp('Input.dispatchMouseEvent', {type: 'mouseMoved', x, y});
    await cdp('Input.dispatchMouseEvent', {type: 'mousePressed', x, y, button: 'left', clickCount: 1});
    await cdp('Input.dispatchMouseEvent', {type: 'mouseReleased', x, y, button: 'left', clickCount: 1}); };
  const tecla = async key => { const vk = {ArrowDown: 40, ArrowUp: 38}[key];
    await cdp('Input.dispatchKeyEvent', {type: 'rawKeyDown', key, code: key, windowsVirtualKeyCode: vk});
    await cdp('Input.dispatchKeyEvent', {type: 'keyUp', key, code: key, windowsVirtualKeyCode: vk}); };
  // Elegir una opción vecina con el teclado, como una persona; si no es vecina, se asigna y se emite un único change.
  const elegir = async (nombre, valor) => {
    const delta = await evaluar(`(()=>{const s=document.querySelector('#filtros-form [name=${nombre}]');const i=[...s.options].findIndex(o=>o.value==='${valor}');if(i<0)throw new Error('Sin opción ${valor}');s.focus();return i-s.selectedIndex;})()`);
    const marca = await evaluar(`(()=>{window.__vieja=true;return 1})()`);
    let metodo = 'teclado';
    if (Math.abs(delta) === 1) { await tecla(delta > 0 ? 'ArrowDown' : 'ArrowUp'); }
    else { metodo = 'evento change'; await evaluar(`(()=>{const s=document.querySelector('#filtros-form [name=${nombre}]');s.value='${valor}';s.dispatchEvent(new Event('change',{bubbles:true}));})()`); }
    try { await esperar(`!window.__vieja&&document.readyState==='complete'&&!!document.querySelector('#cashflow-periodo')`, 'recarga tras elegir ' + nombre); }
    catch (e) { if (metodo !== 'teclado') throw e; metodo = 'evento change (el teclado no cambió la opción)';
      await evaluar(`(()=>{const s=document.querySelector('#filtros-form [name=${nombre}]');s.value='${valor}';s.dispatchEvent(new Event('change',{bubbles:true}));})()`);
      await esperar(`!window.__vieja&&document.readyState==='complete'&&!!document.querySelector('#cashflow-periodo')`, 'recarga tras elegir ' + nombre); }
    return metodo;
  };
  const paso = async (nombre, campo, valor, esperado) => {
    const metodo = await elegir(campo, valor); const v = await leer();
    const ok = Object.entries(esperado).every(([k, x]) => k === 'fecha' ? v.campos.fecha === x : k === 'visibles' ? v.visibles.join() === x : String(v[k]).includes(x));
    anotar(nombre, ok, {elegido: `${campo}=${valor}`, metodo, url: v.url, periodo: v.periodo, totales: v.totales, contador: v.contador, fecha: v.campos.fecha, visibles: v.visibles.join()});
    return v;
  };

  // 0. Calibración del método de celular: el login dentro del marco de 375.
  await irMarco('/login', 'form');
  let m = await evaluar(`(()=>{const w=${W(true)};return {ancho:w.innerWidth,anchoDocumento:w.document.documentElement.scrollWidth,titulo:w.document.title};})()`);
  anotar('calibración: login en marco de 375 sin desborde', m.ancho === 375 && m.anchoDocumento <= 375, m);
  await captura('00-login-375', true);

  // 1. Sin sesión, /cashflow manda al login. Entrada real por el formulario.
  await cdp('Page.navigate', {url: BASE + '/cashflow'}); await esperar(`document.readyState==='complete'&&location.pathname==='/login'`, 'redirección al login');
  anotar('anónimo: /cashflow redirige a /login', true, {url: await evaluar('location.pathname')});
  await evaluar(`document.querySelector('[name=email]').focus()`); await cdp('Input.insertText', {text: 'admin.a10@ensayo.test'});
  await evaluar(`document.querySelector('[name=password]').focus()`); await cdp('Input.insertText', {text: clave});
  await pulsar(`document.querySelector('form [type=submit]')`);
  await esperar(`document.readyState==='complete'&&location.pathname!=='/login'`, 'login');
  anotar('login por formulario con usuario que solo existe en wings_testing_claude', true, {url: await evaluar('location.pathname')});

  // 2. Importes en pantalla, cuatro modos y cruces (datos de sembrar-navegador.php).
  for (const [nombre, ruta, periodo, totales, contador] of [
    ['día 08/10/2026', '/cashflow?periodo=dia&fecha=2026-10-08', '8 de octubre de 2026', '$2.200 INGRESOS $750 EGRESOS $1.450 RESULTADO DEL PERÍODO', '4 movimientos'],
    ['semana del 08/10/2026', '/cashflow?periodo=semana&fecha=2026-10-08', 'Del 05/10/2026 al 11/10/2026', '$3.700 INGRESOS $1.080 EGRESOS $2.620 RESULTADO DEL PERÍODO', '6 movimientos'],
    ['semana que cruza de mes', '/cashflow?periodo=semana&fecha=2026-10-01', 'Del 28/09/2026 al 04/10/2026', '$730 INGRESOS $210 EGRESOS $520 RESULTADO DEL PERÍODO', '2 movimientos'],
    ['semana que cruza de año', '/cashflow?periodo=semana&fecha=2027-01-01', 'Del 28/12/2026 al 03/01/2027', '$1.200 INGRESOS $200 EGRESOS $1.000 RESULTADO DEL PERÍODO', '2 movimientos'],
    ['mes octubre 2026', '/cashflow?periodo=mes&anio=2026&mes=10', 'Octubre 2026', '$12.500 INGRESOS $1.290 EGRESOS $11.210 RESULTADO DEL PERÍODO', '8 movimientos'],
    ['febrero bisiesto', '/cashflow?anio=2024&mes=2', 'Febrero 2024', '$1.900 INGRESOS $350 EGRESOS $1.550 RESULTADO DEL PERÍODO', '2 movimientos'],
    ['año 2026', '/cashflow?anio=2026', 'Año 2026 completo', '$23.870 INGRESOS $1.290 EGRESOS $22.580 RESULTADO DEL PERÍODO', '45 movimientos'],
    ['sin parámetros', '/cashflow', 'Año 2026 completo', '$23.870 INGRESOS $1.290 EGRESOS $22.580 RESULTADO DEL PERÍODO', '45 movimientos'],
    ['año 2026 solo Caja Alfa', '/cashflow?anio=2026&tipo_caja_id=1', 'Año 2026 completo', '$23.870 INGRESOS $0 EGRESOS $23.870 RESULTADO DEL PERÍODO', '41 movimientos'],
  ]) {
    await ir(ruta); const v = await leer();
    const sinSaldo = !(await evaluar(`document.body.innerText.includes('777.000')||/saldo inicial/i.test(document.body.innerText)`));
    anotar('pantalla: ' + nombre, v.periodo === periodo && v.totales.toUpperCase() === totales && v.contador.startsWith(contador) && sinSaldo, {ruta, periodo: v.periodo, totales: v.totales, contador: v.contador, saldo_inicial_ausente: sinSaldo});
  }
  await ir('/cashflow?periodo=dia&fecha=2026-10-08'); await captura('01-dia-escritorio');
  await ir('/cashflow?periodo=semana&fecha=2027-01-01'); await captura('02-semana-cruce-anio-escritorio');

  // 3. Selección real y fecha conservada: Día 31/01/2024 → Semana → Mes → Febrero → Día.
  await ir('/cashflow?periodo=dia&fecha=2024-01-31');
  await paso('Día → Semana conserva la fecha', 'periodo', 'semana', {periodo: 'Del 29/01/2024 al 04/02/2024', fecha: '2024-01-31', visibles: 'periodo,fecha,tipo_caja_id,tipo'});
  await paso('Semana → Mes muestra Año y Mes', 'periodo', 'mes', {periodo: 'Enero 2024', fecha: '2024-01-31', visibles: 'periodo,anio,mes,tipo_caja_id,tipo'});
  await paso('Mes Enero → Febrero limita el 31 al 29', 'mes', '2', {periodo: 'Febrero 2024', fecha: '2024-02-29'});
  await captura('03-mes-febrero-2024-escritorio');
  await paso('Mes → Año', 'periodo', 'anio', {periodo: 'Año 2024 completo', fecha: '2024-02-29', visibles: 'periodo,anio,tipo_caja_id,tipo'});
  await paso('Año → Día vuelve al 29 de febrero', 'periodo', 'dia', {periodo: '29 de febrero de 2024', fecha: '2024-02-29', totales: '$1.550'});
  // Cambiar la fecha en el campo de fecha.
  await evaluar(`(()=>{window.__vieja=true;const e=document.querySelector('#fecha');e.value='2026-10-11';e.dispatchEvent(new Event('change',{bubbles:true}));})()`);
  await esperar(`!window.__vieja&&document.readyState==='complete'&&!!document.querySelector('#cashflow-periodo')`, 'recarga por fecha');
  let v = await leer(); anotar('campo Fecha envía al cambiar', v.periodo === '11 de octubre de 2026', {periodo: v.periodo, url: v.url});

  // 4. Segunda revisión: Caja y Tipo elegidos con el teclado sobre una fecha lejana, y Limpiar pulsado con el mouse en los cuatro modos.
  for (const [modo, archivo, ruta] of [['día', 'dia', '/cashflow?periodo=dia&fecha=2024-02-29'], ['semana', 'semana', '/cashflow?periodo=semana&fecha=2024-02-29'], ['mes', 'mes', '/cashflow?periodo=mes&anio=2024&mes=2'], ['año', 'anio', '/cashflow?periodo=anio&anio=2024']]) {
    await ir(ruta); const antes = await leer();
    anotar(`Limpiar (${modo}): sin filtros no aparece`, antes.limpiar === null, {periodo: antes.periodo, fecha: antes.campos.fecha});
    const m1 = await elegir('tipo_caja_id', '1'); const m2 = await elegir('tipo', 'INGRESO'); const conFiltro = await leer();
    anotar(`Filtros Caja y Tipo (${modo}): conservan período y fecha, recortan filas`, conFiltro.periodo === antes.periodo && conFiltro.campos.fecha === antes.campos.fecha && conFiltro.campos.tipo_caja_id === '1' && conFiltro.campos.tipo === 'INGRESO' && conFiltro.filas < antes.filas && !!conFiltro.limpiar, {metodo: [m1, m2], periodo: conFiltro.periodo, fecha: conFiltro.campos.fecha, filasAntes: antes.filas, filasConFiltro: conFiltro.filas, totalesAntes: antes.totales, totalesConFiltro: conFiltro.totales, url: conFiltro.url});
    // El enlace tal como lo entiende el navegador: atributo decodificado una vez y parámetros según URL().
    const enlace = await evaluar(`(()=>{const a=[...document.querySelectorAll('a')].find(e=>e.textContent.trim()==='Limpiar');const u=new URL(a.href);return {atributo:a.getAttribute('href'),claves:[...u.searchParams.keys()],parametros:Object.fromEntries(u.searchParams)};})()`);
    anotar(`Limpiar (${modo}): enlace sin «amp;» y con los cuatro parámetros`, !enlace.atributo.includes('amp;') && enlace.claves.join() === 'periodo,anio,mes,fecha' && enlace.parametros.fecha === antes.campos.fecha, enlace);
    await captura(`04-${archivo}-con-filtros-antes-de-limpiar-escritorio`);
    await evaluar('window.__vieja=true'); await pulsar(`[...document.querySelectorAll('a')].find(e=>e.textContent.trim()==='Limpiar')`);
    await esperar(`!window.__vieja&&document.readyState==='complete'&&!!document.querySelector('#cashflow-periodo')`, 'Limpiar');
    const despues = await leer();
    anotar(`Limpiar (${modo}): quita Caja y Tipo y CONSERVA período y fecha`, despues.periodo === antes.periodo && despues.campos.fecha === antes.campos.fecha && despues.campos.periodo === antes.campos.periodo && despues.campos.tipo === '' && despues.campos.tipo_caja_id === '' && despues.totales === antes.totales && despues.filas === antes.filas && despues.limpiar === null && !despues.url.includes('amp;'), {periodoAntes: antes.periodo, periodoDespues: despues.periodo, fechaAntes: antes.campos.fecha, fechaDespues: despues.campos.fecha, caja: despues.campos.tipo_caja_id, tipo: despues.campos.tipo, filas: despues.filas, totales: despues.totales, url_despues: despues.url});
    await captura(`05-${archivo}-despues-de-limpiar-escritorio`);
  }

  // 5. Paginación: el enlace real a la página 2 conserva período, filtro y totales.
  await ir('/cashflow?periodo=anio&anio=2026&tipo_caja_id=1'); const p1 = await leer();
  await evaluar('window.__vieja=true'); await pulsar(`[...document.querySelectorAll('nav a')].find(e=>/[?&]page=2/.test(e.href)&&e.getBoundingClientRect().width>0)`);
  await esperar(`!window.__vieja&&document.readyState==='complete'&&!!document.querySelector('#cashflow-periodo')`, 'página 2');
  const p2 = await leer();
  anotar('paginación: página 2 conserva período, caja y totales', p2.url.includes('page=2') && p2.url.includes('periodo=anio') && p2.url.includes('tipo_caja_id=1') && p2.periodo === p1.periodo && p2.totales === p1.totales && p1.filas === 30 && p2.filas === 11, {url: p2.url, filasPagina1: p1.filas, filasPagina2: p2.filas, totales: p2.totales});

  // 6. Nuevo, pulsado con el mouse.
  await ir('/cashflow?periodo=semana&fecha=2026-10-08');
  await pulsar(`[...document.querySelectorAll('a')].find(e=>e.textContent.trim()==='Nuevo')`);
  await esperar(`document.readyState==='complete'&&location.pathname==='/cashflow/movimiento'&&!!document.querySelector('#mov-form')`, 'Nuevo');
  anotar('Nuevo abre el formulario de alta', true, {url: await evaluar('location.pathname'), titulo: await evaluar('document.title'), cabecera: await evaluar(`document.querySelector('.ds-module-header')?.innerText.trim().split('\\n')[0]`)});
  await captura('06-nuevo-movimiento-escritorio');

  // 7. Parámetro inválido escrito en la barra de direcciones: no hay error 500.
  for (const ruta of ['/cashflow?fecha=2026-02-30', '/cashflow?periodo=otro', '/cashflow?mes=13', '/cashflow?anio=1800']) {
    await cdp('Page.navigate', {url: BASE + ruta}); await esperar(`document.readyState==='complete'`, ruta); await pausa(300);
    const d = await evaluar(`({url:location.pathname+location.search,titulo:document.title,error500:/500|Server Error|Whoops/i.test(document.title)})`);
    anotar('inválido en navegador sin 500: ' + ruta, !d.error500, d);
  }

  // 8. Celular: marco de 375, cuatro modos sin desborde y selección dentro del marco.
  for (const [nombre, ruta] of [['dia', '/cashflow?periodo=dia&fecha=2026-10-08'], ['semana', '/cashflow?periodo=semana&fecha=2027-01-01'], ['mes', '/cashflow?periodo=mes&anio=2024&mes=2'], ['anio', '/cashflow?periodo=anio&anio=2026']]) {
    await irMarco(ruta); const c = await leer(true);
    anotar(`celular 375 (${nombre}): entra sin desborde`, c.ancho === 375 && c.anchoDocumento <= 375, {ancho: c.ancho, anchoDocumento: c.anchoDocumento, periodo: c.periodo, totales: c.totales});
    await captura('07-' + nombre + '-375', true);
  }
  await irMarco('/cashflow?periodo=dia&fecha=2026-10-08');
  await evaluar(`(()=>{const w=${W(true)};w.__vieja=true;const s=w.document.querySelector('[name=periodo]');s.value='semana';s.dispatchEvent(new w.Event('change',{bubbles:true}));})()`);
  await esperar(`(()=>{const w=${W(true)};return !w.__vieja&&w.document.readyState==='complete'&&!!w.document.querySelector('#cashflow-periodo')})()`, 'selección en el marco');
  const c = await leer(true);
  anotar('celular 375: Día → Semana dentro del marco', c.periodo === 'Del 05/10/2026 al 11/10/2026' && c.campos.fecha === '2026-10-08' && c.anchoDocumento <= 375, {periodo: c.periodo, fecha: c.campos.fecha, totales: c.totales});
  await evaluar(`(()=>{const w=${W(true)};w.scrollTo(0,w.document.querySelector('#cashflow-periodo').getBoundingClientRect().top+w.scrollY-70);})()`); await pausa(200);
  await captura('08-semana-elegida-375-resumen', true);
} catch (e) {
  anotar('ERROR DEL RECORRIDO', false, {mensaje: String(e.message || e)});
} finally {
  await writeFile(path.join(carpeta, 'resultado-navegador.json'), JSON.stringify({fecha: new Date().toISOString(), revision: 'segunda, tras la corrección del href de Limpiar', base: 'wings_testing_claude', servidor: BASE, metodo: 'Chrome headless por CDP; login por formulario; período vecino elegido con flechas del teclado; Limpiar, Nuevo y paginación pulsados con el mouse; celular en <iframe> de 375 (se quita solo X-Frame-Options en el servidor de ensayo)', fallos, hechos}, null, 2) + '\n');
  ws?.close(); chrome.kill();
  console.log('Fallos:', fallos);
}
