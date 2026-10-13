import fs from 'node:fs';
import crypto from 'node:crypto';
const archivo='docs/00-estado/LOG-CODEX.md';
const original=fs.readFileSync(archivo,'utf8');
const titulo='## 2026-10-13 — Codex CyE — T7 verificada, queda el OK visual de Carlos';
if(original.includes(titulo))throw new Error('T7 ya registrada; no duplicar la entrada');
const secciones=[...original.matchAll(/^## /gm)];
let texto=original,archivada='';
if(secciones.length>=10){
    const corte=secciones[secciones.length-1].index;
    archivada=original.slice(corte);texto=original.slice(0,corte);
    const destino='docs/99-archivo/bitacoras/2026-10-13/LOG-CODEX-ARCHIVO-T7-T11.md';
    fs.mkdirSync('docs/99-archivo/bitacoras/2026-10-13',{recursive:true});
    const cabecera='# Entradas archivadas por Codex CyE — 13/10/2026\n\nTexto de `docs/00-estado/LOG-CODEX.md` preservado intacto dentro de cada bloque.\n\n';
    const bloque='```markdown\n'+archivada+(archivada.endsWith('\n')?'':'\n')+'```\n';
    fs.writeFileSync(destino,(fs.existsSync(destino)?fs.readFileSync(destino,'utf8'):cabecera)+bloque);
    texto+='\n[Entrada archivada al verificar T7](../99-archivo/bitacoras/2026-10-13/LOG-CODEX-ARCHIVO-T7-T11.md).\n';
}
const entrada=titulo+'\n\n'+
 'Verificación independiente de d4f02d4 (Claude): fuente y cascada del menú conservadas.\n'+
 'Chrome del sitio: ADMIN/OPERATIVO/PROFESOR, 1366 y 360, activo Sin asistencia y contador 33.\n'+
 'HTML real y CSS anterior/actual: ocho parejas menú/error idénticas; estilos iguales salvo fondo inexistente del logo.\n'+
 'Renderer 500 del sitio comprobado con conexión a base bloqueada y cero consultas; sin provocar un fallo HTTP.\n'+
 'Suite propia: 722 aprobadas, 2 omitidas, 6910 aserciones, 1106.41 s; carpeta compartida con cambios ajenos de T2.\n'+
 'APROBADO en Chrome medido; navegador sin color-mix y teléfono físico no verificados. Sin arreglo, datos, reloj ni despliegue.\n'+
 '[Informe y capturas](../06-pruebas/PRU-04/VERIFICACION-T7-CODEX.md). Tablero: tiene Carlos, espera OK visual; no cerrado.\n\n';
const primer=texto.indexOf('## ');
const nuevo=texto.slice(0,primer)+entrada+texto.slice(primer);
if((nuevo.match(/^## /gm)||[]).length>10||nuevo.split(/\r?\n/).length>150||nuevo.length>12000)throw new Error('Log excede los limites');
fs.writeFileSync(archivo,nuevo);
fs.writeFileSync('docs/06-pruebas/PRU-04/evidencia-t7-codex/archivo-log.json',JSON.stringify({entradaArchivadaSha256:crypto.createHash('sha256').update(archivada).digest('hex'),archivo:'docs/99-archivo/bitacoras/2026-10-13/LOG-CODEX-ARCHIVO-T7-T11.md',entradasActivas:(nuevo.match(/^## /gm)||[]).length},null,2));
console.log('T7 registrada; entrada antigua preservada intacta.');
