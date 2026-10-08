import {readFile,writeFile,mkdir,access} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import path from 'node:path';
const raiz=process.cwd();
const leer=p=>readFile(p,'utf8');
const guardar=(p,s)=>writeFile(p,s);
const tablero=JSON.parse(await leer('docs/00-estado/tareas.json'));
if(!['A6','A7','A8','A9','A10'].every(id=>tablero.find(t=>t.id===id)?.estado==='cerrado')||tablero.find(t=>t.id==='A10').verifica!=='Claude')throw Error('No están verificados los cinco');
const suite=await leer('docs/06-pruebas/PRU-02/evidencia/a6-a10/suite-a10-limpiar-2026-10-08.txt');
if(!suite.includes('2 skipped, 508 passed (4077 assertions)')||!suite.includes('423.14s'))throw Error('Suite final no comprobada');
const navegador=JSON.parse(await leer('docs/06-pruebas/PRU-02/evidencia/a10-verificacion-claude/resultado-navegador.json'));
if(navegador.fallos!==0||navegador.hechos.length!==45||navegador.hechos.some(h=>!h.ok))throw Error('Revisión independiente incompleta');
const archivo='docs/99-archivo/pruebas/2026-10-08';await mkdir(archivo,{recursive:true});
for(const nombre of ['IMPLEMENTACION-A6-A10','IMPLEMENTACION-A10-PERIODOS']){
 const origen='docs/06-pruebas/PRU-02/'+nombre+'.md';
 const bytes=await readFile(origen);await writeFile(archivo+'/'+nombre+'-ANTES-CIERRE.md.txt',bytes);
}
await guardar('docs/06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md',`# A6–A10 — Entrega verificada

08/10/2026 · Codex CyE. **Los cinco cerrados.** A6–A9 verificados visualmente por Carlos (§6a); A10 también verificado funcionalmente por Claude.

| Tarea | Resultado y aprobación | Evidencia |
|---|---|---|
| A6 | Modificar → Editar, formato original. Carlos: «Dejalo con el mismo formato que tiene originalmente y cambia solo el texto» | [Seis capturas y alcance](PROPUESTA-A6-A10.md#A6--Ficha-de-Clase-texto-elegido-por-Carlos) |
| A7 | Una tarjeta por fila; plan/celular. Cobrar/Editar alineados, Ver con Nuevo, solo celular. Carlos: «Muy buen trabajo, me gusta» | [Capturas finales](evidencia/a6-a10/visor-a7-columnas.html) |
| A8 | Estados útiles en Grupos, Niveles y Profesores; distintivo de Profesores conservado. Carlos: «A-8 APROBADO» | [Tres pantallas](evidencia/a6-a10/visor-a8-grupos.html) |
| A9 | Acciones a derecha solo celular; último botón/Nuevo x=224–320, interruptor termina en x=320. Carlos: «Perfecto, APROBADO A-9 Entonces» | [Capturas](evidencia/a6-a10/visor-a9.html) |
| A10 | Día/Semana/Mes/Año, resultado sin saldo inicial, Nuevo y su destino. Carlos: «Me gusta, A10 Aprobado»; lógica verifica Claude | [Aplicado](evidencia/a6-a10/visor-a10-aplicado.html) · [Verificación](VERIFICACION-A10.md) |

## Verificación final

- Suite Codex: **508 aprobadas / 2 omitidas**, 4077 aserciones, 423,14 s; 510 pruebas. [Salida](evidencia/a6-a10/suite-a10-limpiar-2026-10-08.txt).
- A10: diez pruebas, 110 aserciones. Nueva regresión del href de Limpiar: roja antes, verde después.
- Claude: 432 combinaciones/442 pedidos con cálculo propio; segunda corrida 19 pruebas/6819 aserciones y Chrome 45 comprobaciones sin fallos, 18 capturas propias. Primera devolución conservada intacta.
- Sintaxis y compilación Blade correctas; build final 38,28 s. Escenario ficticio Codex 4/15 verde, 8,72 s.
- 65 capturas finales A7/A8/A9/A10 renovadas tras el build; A6 conserva las seis de su cambio de una palabra. Escritorio/375, roles y estados; login de calibración. [Control de integridad](evidencia/a6-a10/control-entrega.json).

## Alcance y entrega

A6 solo texto; A7 listado de Alumnos y carga de su plan, CSS exclusivo. A8/A9 tres listados y sus pies móviles; componentes compartidos conservados. A10 solo consulta de Cashflow y títulos/acciones: no modifica registro, permisos ni saldo disponible. [Detalle A10](IMPLEMENTACION-A10-PERIODOS.md).

El selector, fechas y resultados están verificados. El apartado de saldo acumulado de Reportes continúa fuera de A10 (POS-01).

**A32 sigue separado y sin aplicar:** [propuesta](evidencia/a6-a10/visor-a32.html), pendiente de elección de Carlos. No se modifica su cuenta ni el toggle compartido.

Código listo para versionar; sin despliegue. [Antecedente íntegro de esta entrega](../../99-archivo/pruebas/2026-10-08/IMPLEMENTACION-A6-A10-ANTES-CIERRE.md.txt); conserva los cortes anteriores como texto, con sus rutas relativas originales.
`);
await guardar('docs/06-pruebas/PRU-02/IMPLEMENTACION-A10-PERIODOS.md',`# A10 — Períodos y acciones de Cashflow

08/10/2026 · Codex CyE. **CERRADO, verificado Claude.** Diseño aprobado por Carlos: «Me gusta, A10 Aprobado».

## Aplicado y comprobado

- Día por fecha; Semana lunes–domingo que la contiene; Mes/Año completos. Fechas inclusivas, cruces de mes/año sin recorte.
- Filas y totales usan el mismo intervalo y caja; Tipo afecta las filas y conserva ambos totales.
- Enlaces anteriores Año/Mes compatibles; sin parámetros, año actual completo.
- Fecha conservada al alternar modos; día limitado al último válido del mes/año.
- Resultado del período = ingresos − egresos, sin saldo inicial. Saldo disponible de Caja/Liquidaciones intacto; saldo acumulado de Reportes fuera de esta tarea.
- Nuevo junto al contador; destino Nuevo movimiento. Limpiar elimina Caja/Tipo conservando período y fecha.

## Corrección tras revisión independiente

Claude detectó que el enlace de Limpiar se escapaba dos veces y volvía a hoy. Se corrigió el parámetro ligado :href="route(...)", sin cambio visual. La nueva regresión DOM lee el href como Chrome: falló antes con amp;anio/amp;fecha/amp;mes y pasó después en los cuatro modos. Las pruebas HTTP solas ocultaban el defecto al decodificar otra vez.

Claude volvió a pulsar Limpiar en los cuatro modos con fechas lejanas y caja/tipo: período, fecha, filas y totales conservados; filtros vacíos. **45 comprobaciones, 0 fallos**. [Verificación independiente y antecedente negativo](VERIFICACION-A10.md).

## Evidencia final

Diez A10: **10 aprobadas / 110 aserciones**, 7,78 s. Suite propia completa: **508 aprobadas / 2 omitidas**, 4077 aserciones, 423,14 s; 510 pruebas. [Salida](evidencia/a6-a10/suite-a10-limpiar-2026-10-08.txt).

Claude: matriz propia de 432 combinaciones/442 pedidos; segunda corrida 19 aprobadas/6819 aserciones, 46,74 s, y 18 capturas del navegador. Fuente del controlador sin cambios entre ambas revisiones; negativo archivado con huellas.

24 capturas actuales de Laravel real después de corregir: cuatro modos, cruces de mes/año, selección Día → Semana y Nuevo hasta el formulario completo. Mismos filtros/rangos/filas que la propuesta; seis íconos y acciones visibles; login dentro de marco375. [Visor aplicado](evidencia/a6-a10/visor-a10-aplicado.html) · [Huellas](evidencia/a6-a10/control-a10-aplicado.json).

Sintaxis y Blade correctos; build final 38,28 s. Escenario ficticio Codex 4/15 verde, 8,72 s. Controles documentales 6/28 verdes. Servidores y Chrome detenidos al entregar; base del club no usada. Sin despliegue.

[Entrega del paquete](IMPLEMENTACION-A6-A10.md) · [Antecedente íntegro](../../99-archivo/pruebas/2026-10-08/IMPLEMENTACION-A10-PERIODOS-ANTES-CIERRE.md.txt).
`);
const reemplazar=async(p,fn)=>guardar(p,fn(await leer(p)));
const corte='**510 pruebas**: nueva regresión de Limpiar; suite completa en ejecución. Corte anterior 509: 507 aprobadas/2 omitidas, 4037 aserciones, 379,46 s; suite Codex CyE 08/10';
const nuevo='**510 pruebas**: 508 aprobadas/2 omitidas, 4077 aserciones, 423,14 s; suite final Codex CyE 08/10 posterior a corregir Limpiar. Antecedente 509: 507 aprobadas/2 omitidas, 4037 aserciones, 379,46 s; suite Codex CyE 08/10';
await reemplazar('docs/00-estado/ESTADO-ACTUAL.md',s=>s.replace(corte,nuevo).replace('## A10 — Corrección tras revisión independiente, 08/10/2026','## A6–A10 — CERRADOS 08/10/2026\n\nA6–A9 verifica Carlos; A10 verifica Claude en segunda revisión, con 432 combinaciones/442 pedidos, 19 pruebas propias del corte final y 45 comprobaciones de navegador sin fallos. Limpiar corregido y pulsado en los cuatro modos. Suite final Codex 508 aprobadas/2 omitidas, 4077 aserciones, 423,14 s; build 38,28 s y capturas finales renovadas. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md), [verificación independiente](../06-pruebas/PRU-02/VERIFICACION-A10.md). A32 continúa como propuesta, pendiente de Carlos. Sin despliegue.\n\n## Antecedentes A6–A10, antes del cierre (07/08 de octubre)\n\n### Corrección tras primera revisión independiente, 08/10/2026'));
await reemplazar('docs/00-estado/PLAN-PRODUCCION.md',s=>s.replace(corte,nuevo));
await reemplazar('docs/00-estado/CHECKLIST-CARLOS.md',s=>s.replace('# 510 pruebas; suite posterior a Limpiar en ejecución (corte anterior 509: 507 aprobadas/2 omitidas)','# 510 pruebas: 508 aprobadas/2 omitidas, 4077 aserciones, 423,14 s; A10 cerrado, verifica Claude').replace('aplicado localmente; Claude verificó la lógica y devolvió Limpiar. Enlace corregido, segunda revisión en ejecución.','aplicado y cerrado, verifica Claude en segunda revisión; Limpiar conserva el período con clic real en los cuatro modos.'));
await reemplazar('docs/02-contratos/Wings-Contrato-Reportes-V1.md',s=>s.replace('Implementada localmente, lógica pendiente de verificación independiente:','Implementada y verificada por Claude el 08/10 (segunda revisión):').replace('Esta consulta no implementa POS-01','Limpiar quita Caja/Tipo conservando período y fecha; el enlace se comprobó con clic real en los cuatro modos. Esta consulta no implementa POS-01'));
await reemplazar('docs/06-pruebas/PRU-02/PROPUESTA-A6-A10.md',s=>s.replace(/^#.*\n/,'# A6–A10 — Propuestas y decisiones históricas\n').replace(/\*\*Corte vigente 08\/10:[^\n]*\n/,'**Corte final 08/10:** A6–A10 cerrados; Carlos verifica aspecto, Claude verifica lógica A10. [Entrega vigente](IMPLEMENTACION-A6-A10.md), suite final 508/2, 24 capturas A10 renovadas tras corregir Limpiar. Sin despliegue.\n\n## Antecedentes de propuesta, anteriores al cierre\n'));
await reemplazar('docs/00-estado/RESUMEN-ARRANQUE.md',s=>s.replace(/^A10 aprobado\/aplicado localmente 08\/10:[^\n]*$/m,'A6–A10 cerrados 08/10: Carlos verifica aspecto A6–A9/A10; Claude verifica lógica A10 en segunda revisión. Limpiar corregido y pulsado en los cuatro modos. Suite final Codex 508/2, 4077 aserciones, 423,14 s; 510 pruebas. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md) · [Verificación independiente](../06-pruebas/PRU-02/VERIFICACION-A10.md). A32 espera elección de Carlos, no aplicado. Sin despliegue.').replace(/^\*\*A10, corrección posterior 08\/10:[^\n]*\n/m,'').replace(/^- \*\*A6–A10, 08\/10, Codex CyE:[^\n]*$/m,'- **A6–A10, CERRADOS 08/10:** aspecto verifica Carlos; lógica A10 verifica Claude con 432 combinaciones/442 pedidos y 45 comprobaciones de navegador sin fallos. Regresión nueva de Limpiar, diez A10/110 aserciones; suite completa 508/2, 4077 aserciones, 423,14 s; build 38,28 s. Capturas finales renovadas; [entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md). A32 separado, espera elección de Carlos. Sin despliegue.'));
const lp='docs/00-estado/LOG-CODEX.md',log=await leer(lp),corteLog='docs/99-archivo/bitacoras/2026-10-08/LOG-CODEX-REVISION-A10.md';
await guardar(corteLog,log);
const sha=createHash('sha256').update(log).digest('hex');
const marcas=[...log.matchAll(/^## \d{4}-\d{2}-\d{2}.*$/gm)],ultima=marcas.at(-1).index,fin=log.indexOf('\n[Corte integro',ultima);
if(marcas.length!==10||fin<0)throw Error('Revisar presupuesto de bitácora');
const vieja=log.slice(ultima,fin).trim();if(!(await leer(corteLog)).includes(vieja))throw Error('Entrada sin copia íntegra');
const entrada=`## 2026-10-08 — Codex CyE — A10 cerrado tras revisión y corrección de Limpiar

Claude ejecutó revisión real: devolvió Limpiar por doble escape; resto contrastado con 432 combinaciones/442 pedidos.
Corregido solo el href; regresión DOM roja antes/verde después, diez A10/110 aserciones.
Segunda revisión Claude: 19 pruebas/6819 aserciones, 45 comprobaciones de navegador sin fallos, 18 capturas; A10 cerrado.
Suite final Codex 508 aprobadas/2 omitidas, 4077 aserciones, 423,14 s; 510 pruebas. Build 38,28 s, sintaxis/Blade correctos.
65 capturas finales del paquete renovadas después del build; escenario ficticio 4/15 verde, 8,72 s; controles documentales 6/28.
A6–A9 cerrados por Carlos; [entrega verificada](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md), estados/contrato y contadores actualizados.
A32 sigue sin aplicar: propuesta lista, pregunta visual pendiente de Carlos según AGENTS §1. Sin despliegue.
Servidores de ensayo detenidos; pruebas siempre en bases propias, base del club intacta.
Diez entradas; copia íntegra previa en LOG-CODEX-REVISION-A10.md comprobada antes de retirar la más antigua.

`;
const nuevoLog=log.slice(0,ultima).replace('# Wings — Bitácora activa de CODEX\n\n','# Wings — Bitácora activa de CODEX\n\n'+entrada)+log.slice(fin);
if(nuevoLog.split('\n').length>150||nuevoLog.length>12000)throw Error('Bitácora fuera de presupuesto');await guardar(lp,nuevoLog);
await reemplazar('docs/99-archivo/bitacoras/2026-10-08/INDICE-CODEX.md',s=>s+'\nSegundo corte íntegro, antes de registrar el cierre A10: [LOG-CODEX-REVISION-A10.md](LOG-CODEX-REVISION-A10.md). SHA256 `'+sha+'`.\n');
for(const p of ['docs/06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md','docs/06-pruebas/PRU-02/IMPLEMENTACION-A10-PERIODOS.md']){
 for(const m of (await leer(p)).matchAll(/\]\(([^)]+)\)/g))await access(path.resolve(path.dirname(p),m[1].split('#')[0]));
}
console.log('Entrega actual corta; antecedentes íntegros, estados y contadores actualizados; A32 permanece pendiente');
