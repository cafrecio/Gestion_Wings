import {readFile,writeFile,access} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
const dir=path.dirname(fileURLToPath(import.meta.url));
const root=path.resolve(dir,'../../../../..');
const tarea=JSON.parse(await readFile(path.join(root,'docs/00-estado/tareas.json'),'utf8')).find(t=>t.id==='A10');
const verificado=tarea?.estado==='cerrado' && tarea.verifica==='Claude';
const revision=verificado?'Intervalos/cálculos verificados por Claude; Limpiar comprobado con clics reales en los cuatro modos.':'Intervalos/cálculos pendientes de verificación independiente;';
const actual=JSON.parse(await readFile(path.join(dir,'comprobacion-a10-aplicado-periodos.json'),'utf8'));
const propuesta=JSON.parse(await readFile(path.join(dir,'comprobacion-a10-periodos.json'),'utf8'));
if(actual.capturas.length!==24)throw new Error('Inventario incompleto');
for(const c of actual.capturas){
 await access(path.join(dir,'capturas',c.nombre));
 if(c.anchoDocumento>c.ancho)throw new Error('Desborde '+c.nombre);
 if(c.nombre.includes('-375-')&&c.ancho!==375)throw new Error('Marco incorrecto');
 if(c.campos.length&&(c.campos.length!==6||c.campos.some(f=>!f.icono)))throw new Error('Campos sin íconos');
 if(c.nombre.includes('movimiento-')&&!c.titulo.includes('Nuevo movimiento'))throw new Error('Título antiguo');
 if(c.nombre.endsWith('movimiento-375-acciones.png')&&c.acciones.some(a=>a.rect.y<60||a.rect.bottom>667))throw new Error('Botones cortados');
}
const diferencias=[];
for(const antes of propuesta.capturas){
 const despues=actual.capturas.find(c=>c.nombre===antes.nombre.replace('a10-periodos-','a10-aplicado-periodos-'));
 if(!despues||antes.periodo!==despues.periodo||JSON.stringify(antes.tablaFilas)!==JSON.stringify(despues.tablaFilas))throw new Error('Datos distintos de la propuesta '+antes.nombre);
 if(JSON.stringify(antes.filtros)!==JSON.stringify(despues.filtros))diferencias.push(antes.nombre);
}
if(diferencias.length)throw new Error('Geometría distinta: '+diferencias.join(', '));
const casos=[['dia','Día'],['semana','Semana'],['mes','Mes'],['anio','Año']];
const img=(nombre,titulo)=>`<figure><figcaption>${titulo}</figcaption><div class="telefono"><img src="capturas/a10-aplicado-periodos-${nombre}.png" alt="${titulo}" loading="lazy"></div></figure>`;
const desktop=(nombre,titulo)=>`<details><summary>${titulo}</summary><img class="escritorio" src="capturas/a10-aplicado-periodos-${nombre}-desktop.png" alt="${titulo}" loading="lazy"></details>`;
const html=`<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>A10 aplicado · Cashflow</title><link rel="stylesheet" href="visor.css"></head><body><main><h1>A10 · resultado aplicado</h1><p>Diseño aprobado por Carlos: «Me gusta, A10 Aprobado». Aplicado localmente; sin desplegar.</p><nav>${casos.map(([id,n])=>`<a href="#${id}" data-elegir="${id}">${n}</a>`).join(' · ')}</nav>${casos.map(([id,n])=>`<section id="${id}" data-modo><h2>${n}</h2><div class="comparacion">${img(id+'-375-inicio','Selección en celular')}${img(id+'-375-resumen','Resumen y movimientos')}</div>${desktop(id,'Ver escritorio')}</section>`).join('')}<h2>Destino de Nuevo · formulario completo</h2><div class="comparacion">${img('movimiento-375-inicio','Parte superior')}${img('movimiento-375-acciones','Campos restantes y botones')}</div>${desktop('movimiento','Ver formulario en escritorio')}<p class="nota">24 capturas de Laravel real con variantes desactivadas, base de pruebas Codex. Misma geometría de filtros, rango y filas que las 20 capturas de la propuesta. Resultado del período sin saldo inicial. ${revision} Saldo acumulado de Reportes pendiente.</p><p><a href="control-a10-aplicado.json">Controles</a> · <a href="visor-a10-escritorio.html">Propuesta aprobada</a></p></main><script src="visor-periodos-a10.js"></script></body></html>`;
for(const [,ref] of html.matchAll(/(?:src|href)="([^"]+)"/g)){if(!ref.startsWith('#')&&ref!=='control-a10-aplicado.json')await access(path.join(dir,ref));}
await writeFile(path.join(dir,'visor-a10-aplicado.html'),html);
const huellas={};for(const f of ['app/Http/Controllers/CashflowWebController.php','resources/views/cashflow/index.blade.php','resources/views/cashflow/movimiento.blade.php'])huellas[f]=createHash('sha256').update(await readFile(path.join(root,f))).digest('hex');
await writeFile(path.join(dir,'control-a10-aplicado.json'),JSON.stringify({fecha:actual.fecha,capturas:24,base:actual.base,variantesDesactivadas:true,filtrosGeometriaIgualAPropuesta:true,periodosFilasIgualesAPropuesta:true,camposConIconos:6,accionesVisibles:true,huellas,disenoVerifica:'Carlos: Me gusta, A10 Aprobado',logica:verificado?'Verificado Claude (segunda revisión, 08/10/2026)':'Pendiente de verificación independiente',desplegado:false},null,2)+'\n');
console.log('24 capturas reales; filtros, fechas y filas coinciden con la propuesta; formulario completo.');
