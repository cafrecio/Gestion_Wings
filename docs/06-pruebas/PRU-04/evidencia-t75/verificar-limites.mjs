import fs from 'node:fs';
import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {createHash} from 'node:crypto';
const archivos=execFileSync('git',['diff','--name-only','4a23634','--','resources/views','resources/css'],{encoding:'utf8'}).trim().split(/\r?\n/).filter(Boolean);
const sinClases=s=>s.replace(/\s+class\s*=\s*(?:"[^"]*"|'[^']*')/g,'');
const resultados=[];
for(const archivo of archivos){const antes=execFileSync('git',['show',`4a23634:${archivo}`],{encoding:'utf8'}).replaceAll('\r\n','\n'),despues=fs.readFileSync(archivo,'utf8').replaceAll('\r\n','\n');let prueba;
 if(archivo.endsWith('.blade.php')){assert.equal(sinClases(antes),sinClases(despues),`Cambio fuera de clases: ${archivo}`);prueba='Solo atributos class; lógica, etiquetas, textos, estilos inline y JavaScript idénticos';}
 else {const bloque=/\/\* T75: la alineación de acciones cambia solo en celular\. \*\/\n@media \(max-width: 640px\) \{\n(?:[^\n]*\n)*?\}\n\n/;assert(bloque.test(despues));assert.equal(despues.replace(bloque,''),antes);prueba='Solo bloque nuevo @media max-width 640px; resto del CSS idéntico';}
 resultados.push({archivo,prueba,antes_sha256:createHash('sha256').update(antes).digest('hex'),despues_sha256:createHash('sha256').update(despues).digest('hex')});}
assert.equal(archivos.length,7);
fs.writeFileSync('docs/06-pruebas/PRU-04/evidencia-t75/limites-cambio.json',JSON.stringify(resultados,null,2));
console.log(`${resultados.length} archivos: solo CSS móvil y clases, sin alterar código de negocio ni JavaScript.`);
