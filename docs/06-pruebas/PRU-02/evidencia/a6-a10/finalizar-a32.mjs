import {readFile,writeFile} from 'node:fs/promises';
import path from 'node:path';
const root=process.cwd();
const leer=p=>readFile(path.join(root,p),'utf8');
const guardar=(p,s)=>writeFile(path.join(root,p),s);
const mdp='docs/06-pruebas/PRU-02/DEFECTOS.md';
let md=await leer(mdp);
const re=/^### A32\.[\s\S]*?(?=^### A33\.)/m;
const bloque=md.match(re)?.[0];if(!bloque)throw Error('Falta A32');
const texto='Comprobación actual 08/10, Codex CyE: la cuenta propia tiene checked=true y disabled=true; etiqueta Activo visible, opacidad 0,45. Parece apagada por atenuación. El controlador impide desactivarse a sí mismo. Propuesta: Activo, Tu cuenta y candado; demás interruptores originales. En celular Editar coincide con Nuevo; escritorio conservado. Nueve capturas reales, originales intactos; falta elección de Carlos, no aplicado.';
md=md.replace(bloque,'### A32. El interruptor de usuario muestra apagado al usuario activo · Molesta · verificado\n\n'+texto+' [ANTES/propuesta](evidencia/a6-a10/visor-a32.html), [detalle y controles](PROPUESTA-A32.md).\n\n');
await guardar(mdp,md);
const hp='docs/06-pruebas/PRU-02/DEFECTOS.html';let html=await leer(hp);
const hre=/^.*<input id="A32".*$/m;if(!html.match(hre))throw Error('Falta A32 HTML');
html=html.replace(hre,'      <div class="item"><input id="A32" type="checkbox"><label for="A32"><span class="id">A32</span>El interruptor de usuario muestra apagado al usuario activo<span class="detail">'+texto+' <a href="evidencia/a6-a10/visor-a32.html">ANTES/propuesta</a> · <a href="PROPUESTA-A32.md">Detalle y controles</a>.</span></label><span class="tag media">Molesta</span></div>');
const total=[...md.matchAll(/^### [AB]\d+\./gm)].length,cerrados=[...md.matchAll(/^### [AB]\d+\..*CERRADO.*$/gm)].length;
html=html.replace(/Quedan \d+ abiertos;/,'Quedan '+(total-cerrados)+' abiertos;').replace('Cerrado quiere decir verificado por otro agente, no solo hecho.','Cerrado quiere decir verificado: por otro agente para lógica, o por Carlos para lo exclusivamente visual (AGENTS §6a).');
await guardar(hp,html);
const ep='docs/00-estado/ESTADO-ACTUAL.md';let estado=await leer(ep);
estado=estado.replace('## A6–A10 — Entrega 08/10/2026, Codex CyE','## A32 — Propuesta 08/10/2026, Codex CyE\n\n'+texto+' [Visor](../06-pruebas/PRU-02/evidencia/a6-a10/visor-a32.html). Base ficticia wings_testing_codex, login de calibración; no se modifica la cuenta real ni permisos.\n\n## A6–A10 — Entrega 08/10/2026, Codex CyE');
await guardar(ep,estado);
const rp='docs/00-estado/RESUMEN-ARRANQUE.md';let resumen=await leer(rp);
resumen=resumen.replace('## Trabajo que continúa\n','## Trabajo que continúa\n\n- **A32, propuesta 08/10, Codex CyE:** [ANTES/propuesta](../06-pruebas/PRU-02/evidencia/a6-a10/visor-a32.html), nueve capturas reales, escritorio/375, login y otros usuarios activos/inactivos. Cuenta propia con texto Activo, Tu cuenta y candado; resto conserva toggle. Solo celular Editar/Nuevo coinciden; escritorio conservado. Originales idénticos por SHA256. Tiene Carlos para elegir, no aplicado ni desplegado.\n');
await guardar(rp,resumen);
const ip='docs/06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md';let implementacion=await leer(ip);
implementacion=implementacion.replace('# A6–A10 — Implementación en preparación','# A6–A10 — Entrega consolidada');
implementacion=implementacion.replace('| Local; seis capturas después en propuesta |','| CERRADO 08/10, verificado Carlos; seis capturas reales |').replace('| Local; capturas finales aprobadas por Carlos 08/10 |','| CERRADO 08/10, verificado Carlos; capturas finales aprobadas |').replace('| Resultado visual aprobado: «A-8 APROBADO», 08/10 |','| CERRADO 08/10, verificado Carlos: «A-8 APROBADO» |').replace('| Aprobación condicionada a coincidir con Nuevo; condición medida y cumplida 08/10 |','| CERRADO 08/10, verificado Carlos; condición medida y aprobación explícita |');
await guardar(ip,implementacion);
const lp='docs/00-estado/LOG-CODEX.md';let log=await leer(lp);
const archivado=await leer('docs/99-archivo/bitacoras/2026-10-08/LOG-CODEX-CORTE.md');
const partes=[...log.matchAll(/^## \d{4}-\d{2}-\d{2}.*$/gm)];
if(partes.length!==10)throw Error('Revisar presupuesto del log: '+partes.length);
const inicio=partes.at(-1).index,fin=log.indexOf('\n[Corte integro',inicio);
const vieja=log.slice(inicio,fin).trim();
if(!archivado.replace(/\r\n/g,'\n').includes(vieja.replace(/\r\n/g,'\n')))throw Error('La entrada que se retira no está íntegra en archivo');
log=log.slice(0,inicio)+log.slice(fin);
const entrada=`## 2026-10-08 — Codex CyE — A6–A9 cerrados; A32 propuesta

A6/A7/A8/A9 cerrados con Carlos como verificador visual (§6a), decisiones citadas en DEFECTOS y entrega.
A10 asignado a Claude: [orden concreta](../06-pruebas/PRU-02/VERIFICAR-A10.md), revisión funcional todavía no ejecutada.
A32 comprobado: cuenta propia checked/disabled, opacidad 0,45; controlador impide desactivarla.
Variante documental propone Activo, Tu cuenta y candado; otros interruptores originales, celular alineado con Nuevo.
[Nueve capturas reales](../06-pruebas/PRU-02/evidencia/a6-a10/visor-a32.html): escritorio/375, login y cuentas activa/inactiva.
Originales de vista Usuarios, toggle, CSS y controlador idénticos por SHA256; geometría escritorio conservada.
Controles documentales 6/28 verdes; sintaxis PHP/Blade/Node y enlaces correctos; no suite funcional por propuesta documental.
Tablero A32 tiene Carlos para elegir; no aplicada ni desplegada. Laboratorio detenido.
Diez entradas; A6/A7 del 07/10 retirada solo tras comprobar copia íntegra en el corte 08/10.

`;
log=log.replace('# Wings — Bitácora activa de CODEX\n\n','# Wings — Bitácora activa de CODEX\n\n'+entrada);
if(log.split('\n').length>150||log.length>12000)throw Error('Log excede presupuesto');
await guardar(lp,log);
const visor=await leer('docs/06-pruebas/PRU-02/evidencia/a6-a10/visor-a32.html');
for(const m of visor.matchAll(/(?:src|href)="([^"]+)"/g)){if(m[1].startsWith('http')||m[1].startsWith('#'))continue;await readFile(path.resolve(root,'docs/06-pruebas/PRU-02/evidencia/a6-a10',m[1]));}
for(const p of ['docs/06-pruebas/PRU-02/PROPUESTA-A32.md','docs/06-pruebas/PRU-02/VERIFICAR-A10.md']){
 for(const m of (await leer(p)).matchAll(/\]\(([^)]+)\)/g))await readFile(path.resolve(path.dirname(path.join(root,p)),m[1]));
}
console.log('Estados, 9 enlaces de imágenes y log comprobados; avance',cerrados,'/',total);
