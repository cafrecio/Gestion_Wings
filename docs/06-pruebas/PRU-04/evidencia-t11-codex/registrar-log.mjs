import fs from 'node:fs';
import crypto from 'node:crypto';
const archivo='docs/00-estado/LOG-CODEX.md';
const original=fs.readFileSync(archivo,'utf8');
const titulo='## 2026-10-13 — Codex CyE — T11 devuelta a Gemini, verificación independiente';
if(original.includes(titulo))throw new Error('Entrada duplicada');
const secciones=[...original.matchAll(/^## /gm)];
let texto=original,archivada='';
if(secciones.length>=10){
    const corte=secciones.at(-1).index;
    archivada=original.slice(corte);texto=original.slice(0,corte);
    const destino='docs/99-archivo/bitacoras/2026-10-13/LOG-CODEX-ARCHIVO-T7-T11.md';
    const bloque='```markdown\n'+archivada+(archivada.endsWith('\n')?'':'\n')+'```\n';
    fs.appendFileSync(destino, bloque);
    texto+='\n[Archivo al verificar T7 y T11](../99-archivo/bitacoras/2026-10-13/LOG-CODEX-ARCHIVO-T7-T11.md).\n';
}
const entrada=titulo+'\n\n'+
 'Chrome 155 del sitio: dos avisos por screenshots de instalación ausentes; criterio sin avisos incumplido.\n'+
 'Motor sin errores de instalabilidad; manifest/PNG/MIME/CSP correctos. Apache comprobado, no Nginx.\n'+
 'Arranque real por tres roles correcto; cookie ADMIN vencida vuelve al login, con autorización expresa de Carlos.\n'+
 'Sin SW ni Cache storage en perfil nuevo; no garantiza ausencia de caché HTTP ni frescura universal.\n'+
 'Suite propia: 720 aprobadas, 2 omitidas, 6892 aserciones, 1089.50 s, wings_testing_codex.\n'+
 'Sin instalación física ni ventana desde ícono verificadas; esas pruebas siguen con Carlos.\n'+
 '[Informe y capturas](../06-pruebas/PRU-04/VERIFICACION-T11-CODEX.md). Devuelto a Gemini: avisos e informe.\n'+
 'Sin arreglos, datos del club, reloj ni despliegue. Chrome aislado retirado; T11 no cerrada.\n\n';
const primer=texto.indexOf('## ');
const nuevo=texto.slice(0,primer)+entrada+texto.slice(primer);
if((nuevo.match(/^## /gm)||[]).length>10||nuevo.split(/\r?\n/).length>150||nuevo.length>12000)throw new Error('Log excede los límites');
fs.writeFileSync(archivo,nuevo);
fs.writeFileSync('docs/06-pruebas/PRU-04/evidencia-t11-codex/archivo-log.json',JSON.stringify({entradaArchivadaSha256:crypto.createHash('sha256').update(archivada).digest('hex'),archivo:'docs/99-archivo/bitacoras/2026-10-13/LOG-CODEX-ARCHIVO-T7-T11.md',entradasActivas:(nuevo.match(/^## /gm)||[]).length},null,2)+'\n');
console.log('T11 registrada y entrada anterior conservada intacta.');
