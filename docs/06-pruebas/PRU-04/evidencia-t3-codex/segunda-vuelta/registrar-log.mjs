import fs from 'node:fs';
import crypto from 'node:crypto';
const evidencia='docs/06-pruebas/PRU-04/evidencia-t3-codex/segunda-vuelta/';
const salida=JSON.parse(fs.readFileSync(evidencia+'suite-salida.json','utf8'));
const suite=fs.readFileSync(evidencia+'suite-completa.txt','utf8');
const total=suite.match(/Tests:\s*([^\r\n]+)/)?.[1]?.trim();
if(!total)throw Error('Suite sin resultado terminal');
const archivo='docs/00-estado/LOG-CODEX.md';
const previo=fs.readFileSync(archivo,'utf8');
const titulo='## 2026-10-16 — Codex CyE — T3 segunda vuelta: fechas correctas, aviso al cerrar';
if(previo.includes(titulo))throw Error('Entrada duplicada');
let activo=previo,archivada='';
const entradas=[...previo.matchAll(/^## /gm)];
if(entradas.length>=10){
 const corte=entradas.at(-1).index;archivada=previo.slice(corte);activo=previo.slice(0,corte);
 const destino='docs/99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3-SEGUNDA.md';
 fs.writeFileSync(destino,'# Entrada archivada al verificar T3 segunda vuelta — 16/10/2026\n\nTexto anterior conservado intacto.\n\n```markdown\n'+archivada+(archivada.endsWith('\n')?'':'\n')+'```\n');
 activo+='\n[Entrada archivada al verificar T3 segunda vuelta](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3-SEGUNDA.md).\n';
}
const entrada=titulo+'\n\n'+
 '318ef2f en copia: A6 conserva cierre; cerrar, editar otro día y reabrir B11 conserva/borra fecha correctamente.\n'+
 '67 encabezados y renglones cerrados restaurados; tres abiertos actuales. 66 fechas coinciden con encabezados.\n'+
 'B15 23/09 aceptable por SERVIDOR.md y registro contemporáneo 031bdec; no por el último cambio solo.\n'+
 'Devuelta a Claude: cerrar B11 emite Undefined array key 2, lib.php:660; guarda, pero no sale limpio.\n'+
 'Pruebas nuevas: 2/18 verdes; en código viejo dos errores por helper ausente, y CLI viejo reproduce fecha falsa.\n'+
 'Suite propia wings_testing_codex: '+total+'; salida '+salida.exitCode+'. Sin pruebas en otra base.\n'+
 '[Segunda vuelta y evidencia](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md#segunda-vuelta). Tiene Claude: grupo opcional y regresión HTML.\n'+
 'Sin arreglo de código, producto, datos del club, reloj ni despliegue; copia restaurada y retirada. T11 no empezada.\n\n';
const first=activo.indexOf('## ');const nuevo=activo.slice(0,first)+entrada+activo.slice(first);
if((nuevo.match(/^## /gm)||[]).length>10||nuevo.split(/\r?\n/).length>150||nuevo.length>12000)throw Error('Log excede límites');
fs.writeFileSync(archivo,nuevo);
fs.writeFileSync(evidencia+'archivo-log.json',JSON.stringify({shaArchivada:crypto.createHash('sha256').update(archivada).digest('hex'),entradas:(nuevo.match(/^## /gm)||[]).length,lineas:nuevo.split(/\r?\n/).length,caracteres:nuevo.length},null,2)+'\n');
console.log('Segunda vuelta registrada y entrada previa conservada.');
