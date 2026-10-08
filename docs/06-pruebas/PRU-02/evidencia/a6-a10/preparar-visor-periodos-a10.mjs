import {readFile,writeFile,access} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
const dir=path.dirname(fileURLToPath(import.meta.url));
const root=path.resolve(dir,'../../../../..');
const control=JSON.parse(await readFile(path.join(dir,'comprobacion-a10-periodos.json'),'utf8'));
if(control.capturas.length!==20)throw new Error('Faltan capturas');
for(const c of control.capturas){
 await access(path.join(dir,'capturas',c.nombre));
 if(c.anchoDocumento>c.ancho)throw new Error('Desborde '+c.nombre);
 for(const f of c.filtros){if(f.control.x<0||f.control.right>c.anchoDocumento)throw new Error('Campo cortado '+c.nombre);}
}
const casos=[['dia','Día','Elegís una fecha.'],['semana','Semana','Elegís una fecha de esa semana. Se muestran lunes a domingo.'],['mes','Mes','Elegís año y mes.'],['anio','Año','Elegís el año completo.']];
const obtener=n=>control.capturas.find(c=>c.nombre==='a10-periodos-'+n+'.png');
const esperados={dia:'8 de octubre de 2026',semana:'Del 05/10/2026 al 11/10/2026',mes:'Octubre 2026',anio:'Año 2026 completo','semana-cruce-mes':'Del 28/09/2026 al 04/10/2026','semana-cruce-anio':'Del 28/12/2026 al 03/01/2027'};
for(const [caso,texto] of Object.entries(esperados)){
 const escritorio=obtener(caso+'-desktop'),movil=obtener(caso+'-375-inicio');
 if(!escritorio.periodo.includes(texto)||!movil.periodo.includes(texto))throw new Error('Intervalo incorrecto '+caso);
 if(JSON.stringify(escritorio.tablaFilas)!==JSON.stringify(movil.tablaFilas))throw new Error('Datos difieren por ancho '+caso);
 if(caso.startsWith('semana-cruce')&&escritorio.tablaFilas.length)throw new Error('Ingreso 08/10 entra fuera de su semana');
 if(!caso.startsWith('semana-cruce')&&escritorio.tablaFilas.length!==1)throw new Error('Movimiento de prueba ausente');
}
if(obtener('selector-interactivo-desktop').selectorPeriodo!=='semana')throw new Error('Selector sin ejercicio');
const huellas=JSON.parse(await readFile(path.join(dir,'periodos-huellas-originales.json'),'utf8'));
for(const [f,h] of Object.entries(huellas))if(createHash('sha256').update(await readFile(path.join(root,f))).digest('hex')!==h)throw new Error('Original cambió durante captura: '+f);
const telefono=(caso,toma,titulo)=>`<figure><figcaption>${titulo}</figcaption><div class="telefono"><img src="capturas/a10-periodos-${caso}-375-${toma}.png" alt="${titulo} de ${caso}" loading="lazy"></div></figure>`;
const escritorio=caso=>`<details><summary>Ver escritorio</summary><img class="escritorio" src="capturas/a10-periodos-${caso}-desktop.png" alt="Cashflow ${caso} en escritorio" loading="lazy"></details>`;
const secciones=casos.map(([id,nombre,descripcion])=>`<section id="${id}" data-modo><h2>${nombre}</h2><p>${descripcion}</p><div class="comparacion">${telefono(id,'inicio','Selección · celular')}${telefono(id,'resumen','Resumen y movimientos · celular')}</div>${escritorio(id)}</section>`).join('\n');
const html=`<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cashflow · propuestas de períodos</title><link rel="stylesheet" href="visor.css"><style>nav{display:flex;gap:8px;flex-wrap:wrap;margin:20px 0}nav a{padding:8px 18px;border:1px solid #d8e0ea;border-radius:6px;background:white;text-decoration:none}nav a[aria-current=true]{background:#4b6982;color:white}section{scroll-margin-top:12px}details{margin:16px 0}summary{cursor:pointer;font-weight:600}.nota{font-size:14px}.escritorio{margin-top:12px}</style></head><body><main>
<h1>Cashflow · selección de período</h1><p>Maquetas en celular y escritorio. Elegí un período para verlo.</p>
<nav aria-label="Períodos">${casos.map(([id,n])=>`<a href="#${id}" data-elegir="${id}">${n}</a>`).join('')}</nav>
${secciones}
<details><summary>Semana que cruza de mes o de año</summary><h3>Septiembre → octubre</h3><div class="comparacion">${telefono('semana-cruce-mes','inicio','Del 28/09 al 04/10')}${telefono('semana-cruce-anio','inicio','Del 28/12 al 03/01')}</div>${escritorio('semana-cruce-mes')}${escritorio('semana-cruce-anio')}</details>
<p class="nota">Diseño aprobado y aplicado localmente. <a href="visor-a10-aplicado.html">Ver resultado aplicado</a>. El resumen muestra ingresos − egresos como <strong>Resultado del período</strong>; el saldo acumulado queda pendiente de definición. Capturas de Laravel real con datos ficticios, 08/10/2026.</p>
<p><a href="visor-a10.html">Volver a la propuesta anterior de A10</a></p>
</main><script src="visor-periodos-a10.js"></script></body></html>`;
await writeFile(path.join(dir,'visor-a10-periodos.html'),html);
await writeFile(path.join(dir,'visor-periodos-a10.js'),`function mostrar(){const elegido=location.hash.slice(1)||'dia';const valido=['dia','semana','mes','anio'].includes(elegido)?elegido:'dia';document.querySelectorAll('[data-modo]').forEach(s=>s.hidden=s.id!==valido);document.querySelectorAll('[data-elegir]').forEach(a=>a.setAttribute('aria-current',String(a.dataset.elegir===valido)));}window.addEventListener('hashchange',mostrar);mostrar();\n`);
for(const match of html.matchAll(/(?:src|href)="([^"]+)"/g)){if(!match[1].startsWith('#'))await access(path.join(dir,match[1]));}
await writeFile(path.join(dir,'control-a10-periodos.json'),JSON.stringify({fecha:control.fecha,capturas:20,base:control.base,originalesSinCambios:huellas,intervalos:esperados,selectorDiaSemanaEjercitado:true,documentoSinDesborde:true,datosFicticios:true,estado:'Propuesta de diseño; no implementada en la aplicación',notaFinanciera:'Resultado = ingresos menos egresos del intervalo; no incorpora saldo inicial. Saldo acumulado no propuesto ni implementado.'},null,2)+'\n');
console.log('Visor listo; 20 capturas, intervalos y enlaces correctos; originales intactos.');
