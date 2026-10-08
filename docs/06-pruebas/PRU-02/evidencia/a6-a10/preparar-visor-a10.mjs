import {readFile,writeFile,access} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
const carpeta=path.dirname(fileURLToPath(import.meta.url));
const root=path.resolve(carpeta,'../../../../..');
const actual=JSON.parse(await readFile(path.join(carpeta,'comprobacion-a10-actual.json'),'utf8'));
const propuesta=JSON.parse(await readFile(path.join(carpeta,'comprobacion-a10-propuesta.json'),'utf8'));
if(actual.capturas.length!==14||propuesta.capturas.length!==14)throw new Error('Inventario incompleto');
for(const c of [...actual.capturas,...propuesta.capturas]){
 await access(path.join(carpeta,'capturas',c.nombre));
 if(c.anchoDocumento>c.ancho)throw new Error('Desborde '+c.nombre);
 if(c.nombre.includes('-375-')&&c.ancho!==375)throw new Error('Marco incorrecto');
 if(c.campos.length&&(!c.campos.every(e=>e.icono)||c.campos.length!==6))throw new Error('Íconos incompletos');
 if(c.nombre.endsWith('movimiento-375-acciones.png')&&c.acciones.some(e=>e.rect.y<60||e.rect.bottom>667))throw new Error('Acciones fuera de pantalla');
}
for(const caso of ['anual','octubre','septiembre-vacio']){
 const antes=actual.capturas.find(c=>c.nombre===`a10-actual-${caso}-desktop.png`);
 const despues=propuesta.capturas.find(c=>c.nombre===`a10-propuesta-${caso}-desktop.png`);
 if(antes.periodo!==despues.periodo||JSON.stringify(antes.tablaFilas)!==JSON.stringify(despues.tablaFilas))throw new Error('Datos/período cambiaron');
 if(despues.barras.length!==2||despues.nuevo.rect.width!==96||despues.nuevo.rect.height!==32||despues.nuevo.barra.includes('$'))throw new Error('Nuevo no tiene su barra propia');
}
for(const c of propuesta.capturas.filter(c=>c.nombre.includes('movimiento-')))if(!c.titulo.includes('Nuevo movimiento'))throw new Error('Título incorrecto');
const img=(prefijo,caso,titulo)=>`<figure><figcaption>${titulo}</figcaption><div class="telefono"><img src="capturas/${prefijo}-${caso}.png" alt="${titulo}"></div><a href="capturas/${prefijo}-${caso}.png">Imagen original</a></figure>`;
const desktop=(prefijo,caso,titulo)=>`<h3>${titulo}</h3><img class="escritorio" src="capturas/${prefijo}-${caso}-desktop.png" alt="${titulo}">`;
const escritorioPeriodos=`<section id="periodos-escritorio"><h2>Propuesta actualizada · escritorio</h2><p>El selector <strong>Período</strong> ofrece <strong>Día, Semana, Mes y Año</strong>. Día y Semana muestran un calendario; Mes muestra año y mes; Año muestra el año.</p><p><a href="visor-a10-periodos.html">Ver las cuatro maquetas también en celular</a></p>${desktop('a10-periodos','dia','Día · elegís una fecha')}${desktop('a10-periodos','semana','Semana · elegís una fecha, se muestra lunes a domingo')}<details><summary>Ver Mes y Año en escritorio</summary>${desktop('a10-periodos','mes','Mes')}${desktop('a10-periodos','anio','Año')}</details><p class="nota">Diseño aprobado y aplicado localmente. Resultado del período = ingresos − egresos; saldo acumulado pendiente de definición.</p></section>`;
const base=(titulo,cuerpo)=>`<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>A10 · ${titulo}</title><link rel="stylesheet" href="visor.css"></head><body><main><h1>A10 · ${titulo}</h1><nav><a href="visor-a10.html">Cashflow</a> · <a href="visor-a10-movimiento.html">Pantalla de Nuevo</a></nav>${cuerpo}<p>Diseño aprobado por Carlos 08/10; aplicado localmente. <a href="visor-a10-aplicado.html">Ver resultado aplicado</a>. Capturas de Laravel real con datos ficticios; escritorio 1280×900 y marco 375×667. 08/10/2026. Estas capturas conservan la etapa de propuesta.</p><p><a href="comprobacion-a10-actual.json">Actual: mediciones</a> · <a href="comprobacion-a10-propuesta.json">Propuesta: mediciones</a> · <a href="control-a10.json">Control de datos e inventario</a></p></main></body></html>`;
await writeFile(path.join(carpeta,'visor-a10.html'),base('Cashflow',`<p class="nota">Propuesta: Nuevo junto al contador, en una barra propia. Totales y período arriba. Limpiar junto a los filtros. Nuevo conserva el verbo y usa el mismo botón de los otros listados.</p><div class="comparacion">${img('a10-actual','anual-375-resumen','Actual: Nuevo dentro de los totales')}${img('a10-propuesta','anual-375-resumen','Propuesta: contador y Nuevo en su barra')}</div><h2>Escritorio</h2>${desktop('a10-actual','anual','Actual')}${desktop('a10-propuesta','anual','Propuesta')}<details><summary>Con filtro de mes y sin movimientos</summary><div class="comparacion">${img('a10-propuesta','octubre-375-resumen','Octubre 2026')}${img('a10-propuesta','septiembre-vacio-375-resumen','Septiembre 2026: cero movimientos')}</div></details><p><a href="visor-a10-movimiento.html">Ver qué pantalla abre Nuevo, completa</a></p>`)+'\n');
await writeFile(path.join(carpeta,'visor-a10-movimiento.html'),base('Pantalla de Nuevo',`<p class="nota">Propuesta de título: Nuevo movimiento. Se conservan campos, íconos y botones existentes.</p><div class="comparacion">${img('a10-actual','movimiento-375-inicio','Título actual: Movimiento directo')}${img('a10-propuesta','movimiento-375-inicio','Título propuesto: Nuevo movimiento')}</div><h2>Formulario móvil completo</h2><p>Dos capturas complementarias; sin achicar texto ni teléfono.</p><div class="comparacion">${img('a10-propuesta','movimiento-375-inicio','Parte superior')}${img('a10-propuesta','movimiento-375-acciones','Campos restantes y Cancelar/Registrar')}</div><h2>Escritorio</h2>${desktop('a10-actual','movimiento','Actual')}${desktop('a10-propuesta','movimiento','Propuesta')}`)+'\n');
// Mantener el enlace original de Carlos al día; lo anterior queda como antecedente.
const paginaCashflow=path.join(carpeta,'visor-a10.html');
let visorCashflow=await readFile(paginaCashflow,'utf8');
const navFin=visorCashflow.indexOf('</nav>')+'</nav>'.length;
const footerInicio=visorCashflow.indexOf('<p>Diseño aprobado por Carlos 08/10; aplicado localmente. <a href="visor-a10-aplicado.html">Ver resultado aplicado</a>.');
if(navFin<6||footerInicio<0)throw new Error('Estructura del visor cambió');
visorCashflow=visorCashflow.slice(0,navFin)+escritorioPeriodos+'<details><summary>Antecedente: ubicación de Nuevo, antes y primera propuesta</summary>'+visorCashflow.slice(navFin,footerInicio)+'</details>'+visorCashflow.slice(footerInicio);
await writeFile(paginaCashflow,visorCashflow);
await writeFile(path.join(carpeta,'visor-a10-escritorio.html'),visorCashflow);
const sha=async nombre=>createHash('sha256').update(await readFile(nombre)).digest('hex');
await writeFile(path.join(carpeta,'control-a10.json'),JSON.stringify({fecha:new Date().toISOString(),base:'wings_testing_codex',capturas:28,datosPeriodoFilasIdenticos:true,camposConIconos:6,accionesMovilesVisibles:true,nuevoPropuesto:'96×32 en barra propia junto al contador',aplicacionModificada:false,originales:{index:await sha(path.join(root,'resources/views/cashflow/index.blade.php')),movimiento:await sha(path.join(root,'resources/views/cashflow/movimiento.blade.php'))}},null,2)+'\n');
for(const nombre of ['visor-a10.html','visor-a10-escritorio.html','visor-a10-movimiento.html'])for(const [,ref] of (await readFile(path.join(carpeta,nombre),'utf8')).matchAll(/(?:src|href)="([^"]+)"/g))await access(path.resolve(carpeta,ref));
console.log('28 capturas; datos y período idénticos; seis íconos; botones móviles visibles; dos visores comprobados.');
