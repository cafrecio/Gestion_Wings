import {readFile,writeFile} from 'node:fs/promises';
import path from 'node:path';
const root=process.cwd();
const leer=p=>readFile(path.join(root,p),'utf8');
const guardar=(p,s)=>writeFile(path.join(root,p),s);
const cierres={
 A6:'Carlos: «Dejalo con el mismo formato que tiene originalmente y cambia solo el texto». Modificar → Editar, una sola palabra; seis capturas reales, formato original conservado.',
 A7:'Carlos: «Muy buen trabajo, me gusta». Una tarjeta por fila, plan/celular y columnas móviles Cobrar/Editar, Ver/Nuevo alineadas; escritorio conservado.',
 A8:'Carlos: «A-8 APROBADO». Estados útiles en Grupos, Niveles y Profesores; distintivo original de Profesores conservado.',
 A9:'Carlos: «Perfecto, APROBADO A-9 Entonces». Acciones móviles a derecha; último botón y Nuevo x=224–320 en las tres pantallas; interruptor termina en x=320.'
};
const mdPath='docs/06-pruebas/PRU-02/DEFECTOS.md';
let md=await leer(mdPath);
const htmlPath='docs/06-pruebas/PRU-02/DEFECTOS.html';
let html=await leer(htmlPath);
for(const [id,detalle] of Object.entries(cierres)) {
 const re=new RegExp('^### '+id+'\\. (.*?)(?=^### |$(?![\\s\\S]))','ms');
 const bloque=md.match(re)?.[0];if(!bloque)throw Error('No existe '+id);
 const titulo=bloque.split('\n')[0].replace(/ · CERRADO.*$/,'');
 md=md.replace(bloque,titulo+' · CERRADO 08/10 · verificado Carlos\n\n**Cierre visual, AGENTS §6a:** '+detalle+' [Entrega y capturas](IMPLEMENTACION-A6-A10.md). Suite final del paquete 507 aprobadas/2 omitidas; sin deploy.\n\n');
 const hre=new RegExp('^.*<input id="'+id+'".*$','m');
 const linea=html.match(hre)?.[0];if(!linea)throw Error('Falta HTML '+id);
 const tituloHtml=linea.match(new RegExp('<span class="id">'+id+'</span>(.*?)<span class="detail">'))?.[1];
 html=html.replace(linea,'      <div class="item"><input id="'+id+'" type="checkbox" checked><label for="'+id+'"><span class="id">'+id+'</span>'+tituloHtml+'<span class="detail"><b>CERRADO 08/10 · verificado Carlos, AGENTS §6a:</b> '+detalle+' <a href="IMPLEMENTACION-A6-A10.md">Entrega y capturas</a>. Suite final del paquete 507 aprobadas/2 omitidas; sin deploy.</span></label><span class="tag done">Cerrado</span></div>');
}
const cerrados=[...md.matchAll(/^### [AB]\d+\..*CERRADO.*$/gm)].length;
const total=[...md.matchAll(/^### [AB]\d+\./gm)].length;
for(const [p,s] of [[mdPath,md],[htmlPath,html]])await guardar(p,s.replace(/Avance al \d{2}\/\d{2}\/\d{4}: \d+ cerrados de \d+/g,`Avance al 08/10/2026: ${cerrados} cerrados de ${total}`));
const cabecera='**Corte vigente 08/10:** A6–A9 cerrados con Carlos como verificador visual (AGENTS §6a), citando sus decisiones. A10 aplicado, aspecto aprobado; revisión funcional asignada a Claude: [orden concreta](VERIFICAR-A10.md). Suite final 507 aprobadas/2 omitidas; no desplegado. Las entradas anteriores conservan sus fechas y pendientes de aquel momento.\n\n';
for(const p of ['docs/06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md','docs/06-pruebas/PRU-02/PROPUESTA-A6-A10.md']){
 const s=await leer(p),pos=s.indexOf('\n\n');await guardar(p,s.slice(0,pos+2)+cabecera+s.slice(pos+2));
}
let entrega=await leer('docs/06-pruebas/PRU-02/IMPLEMENTACION-A10-PERIODOS.md');
entrega=entrega.replace('sin dueño de verificación asignado: otro agente debe comprobar intervalos, validaciones y cálculo del resultado','asignado a Claude: debe comprobar intervalos, validaciones y cálculo del resultado; [orden preparada](VERIFICAR-A10.md), todavía no ejecutada');
await guardar('docs/06-pruebas/PRU-02/IMPLEMENTACION-A10-PERIODOS.md',entrega);
let estado=await leer('docs/00-estado/ESTADO-ACTUAL.md');
estado=estado.replace('## A6–A10 — Retoma 08/10/2026, Codex CyE','## A6–A10 — Entrega 08/10/2026, Codex CyE\n\nA6–A9 cerrados con aprobación visual de Carlos (§6a); decisiones y capturas en [entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md). A10 asignado a Claude para verificar lógica; [orden](../06-pruebas/PRU-02/VERIFICAR-A10.md) preparada, revisión todavía no ejecutada. Los cortes anteriores de este apartado son antecedentes.');
await guardar('docs/00-estado/ESTADO-ACTUAL.md',estado);
let resumen=await leer('docs/00-estado/RESUMEN-ARRANQUE.md');
resumen=resumen.replace('**A6–A10, 08/10, Codex CyE:** A6 solo texto;', '**A6–A10, 08/10, Codex CyE:** A6–A9 cerrados, verifica Carlos (§6a). A6 solo texto;').replace('lógica A10 a_verificar, sin deploy','lógica A10 a_verificar, asignada a Claude con [orden preparada](../06-pruebas/PRU-02/VERIFICAR-A10.md); revisión no ejecutada, sin deploy');
await guardar('docs/00-estado/RESUMEN-ARRANQUE.md',resumen);
console.log('Cierres visuales consolidados:',cerrados,'de',total,'; A10 asignado, sin afirmar revisión ejecutada');
