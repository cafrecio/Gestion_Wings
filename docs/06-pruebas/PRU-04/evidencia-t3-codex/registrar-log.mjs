import fs from 'node:fs';
import crypto from 'node:crypto';
const root='docs/06-pruebas/PRU-04/evidencia-t3-codex/';
const salida=JSON.parse(fs.readFileSync(root+'suite-salida.json','utf8'));
const suite=fs.readFileSync(root+'suite-completa.txt','utf8');
const total=suite.match(/Tests:\s*([^\r\n]+)/)?.[1]?.trim();
if(!total)throw Error('No hay resultado terminal de la suite');
const archivo='docs/00-estado/LOG-CODEX.md';
const previo=fs.readFileSync(archivo,'utf8');
const titulo='## 2026-10-16 — Codex CyE — T3 devuelta a Claude, fecha de cierre alterada';
if(previo.includes(titulo))throw Error('Entrada duplicada');
let activo=previo,archivada='';
const entradas=[...previo.matchAll(/^## /gm)];
if(entradas.length>=10){
 const corte=entradas.at(-1).index;
 archivada=previo.slice(corte);activo=previo.slice(0,corte);
 const destino='docs/99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3.md';
 fs.mkdirSync('docs/99-archivo/bitacoras/2026-10-16',{recursive:true});
 fs.writeFileSync(destino,'# Entrada archivada al verificar T3 — Codex CyE, 16/10/2026\n\nTexto anterior conservado intacto.\n\n```markdown\n'+archivada+(archivada.endsWith('\n')?'':'\n')+'```\n');
 activo+='\n[Entrada archivada al verificar T3](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3.md).\n';
}
const entrada=titulo+'\n\n'+
 'Verificado f4e6436: 70 defectos, cero cambios abierto/cerrado y cuerpos intactos; 56 encabezados distintos.\n'+
 'Copia aislada: cinco archivos escritos; tres corrupciones detectadas y CRLF verde. Guardianes 9/31 verdes.\n'+
 'Solo cambiar paso de A6 altera CERRADO 08/10 a 16/10; el guardián sigue verde. Devolver, sin arreglar.\n'+
 'Se pierden en encabezados aprobaciones, doble verificador y motivos de cierre; antecedentes siguen en cuerpos.\n'+
 'Suite propia wings_testing_codex: '+total+'; salida '+salida.exitCode+'. T33 concurrente pasa sus diez casos en la suite.\n'+
 '[Informe y evidencia](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md). Devuelta, tiene Claude: conservar fecha real y agregar regresión.\n'+
 'Sin cambios de producto, reloj, datos del club ni despliegue; ensayos documentales solo en copia restaurada.\n\n';
const primero=activo.indexOf('## ');
const nuevo=activo.slice(0,primero)+entrada+activo.slice(primero);
if((nuevo.match(/^## /gm)||[]).length>10||nuevo.split(/\r?\n/).length>150||nuevo.length>12000)throw Error('Log excede límites');
fs.writeFileSync(archivo,nuevo);
fs.writeFileSync(root+'archivo-log.json',JSON.stringify({entradaArchivadaSha256:crypto.createHash('sha256').update(archivada).digest('hex'),entradasActivas:(nuevo.match(/^## /gm)||[]).length,lineas:nuevo.split(/\r?\n/).length,caracteres:nuevo.length},null,2)+'\n');
console.log('T3 registrada; entrada anterior archivada sin cambios.');
