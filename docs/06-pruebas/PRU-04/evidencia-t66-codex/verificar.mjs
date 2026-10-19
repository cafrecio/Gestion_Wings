import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
const dir='docs/06-pruebas/PRU-04/evidencia-t66-codex';
const leer=p=>JSON.parse(fs.readFileSync(p,'utf8').replace(/^\uFEFF/,''));
const hash=p=>createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const fuentes=leer(`${dir}/fuentes-suite.json`);
for(const f of fuentes){const lf=fs.readFileSync(f.ruta,'utf8').replaceAll('\r\n','\n');assert.equal(createHash('sha256').update(lf).digest('hex'),f.sha256_lf.toLowerCase(),`Fuente cambió: ${f.ruta}`);}
const medidas=leer(`${dir}/mediciones.json`);assert.equal(medidas.length,12);
let fotos=0;
for(const m of medidas){assert(m.contenido<=m.ancho);assert([360,375,1366].includes(m.ancho));for(const f of m.fotos){const b=fs.readFileSync(`${dir}/${f}`);assert.equal(b.subarray(1,4).toString(),'PNG');fotos++;}}
assert.equal(fotos,19);
for(const f of leer(`${dir}/recursos.json`))assert.equal(hash(`${dir}/${f.ruta}`),f.sha256);
for(const archivo of [`${dir}/GALERIA.html`,'docs/06-pruebas/PRU-04/IMPLEMENTACION-T66.md']) {
 const texto=fs.readFileSync(archivo,'utf8');
 const refs=archivo.endsWith('.html')?[...texto.matchAll(/(?:src|href)="([^"]+)"/g)].map(m=>m[1]):[...texto.matchAll(/\]\(([^)]+)\)/g)].map(m=>m[1]);
 for(const ref of refs){if(/^(https?:|#)/.test(ref))continue;assert(fs.existsSync(path.resolve(path.dirname(archivo),ref.split('#')[0])),`Enlace roto: ${ref}`);}
}
const suite=leer(`${dir}/suite-resultado.json`);assert.equal(suite.exit_code,0);
const xml=fs.readFileSync(`${dir}/suite.xml`,'utf8'),raiz=xml.match(/<testsuite\s+[^>]+>/)?.[0];assert(raiz);
for(const [campo,valor] of Object.entries({tests:924,assertions:8155,errors:0,failures:0,skipped:4}))assert(raiz.includes(`${campo}="${valor}"`),`JUnit: ${campo}`);
const tablero=leer('docs/00-estado/tareas.json');
const buscar=(obj)=>{if(Array.isArray(obj))for(const el of obj){const r=buscar(el);if(r)return r;}else if(obj&&typeof obj==='object'){if(obj.id==='T66')return obj;for(const el of Object.values(obj)){const r=buscar(el);if(r)return r;}}};
const t=buscar(tablero);assert.equal(t.estado,'a_verificar');assert.equal(t.hizo,'Codex');assert.equal(t.tiene,'Claude');assert.equal(t.verifica,'Claude');
console.log(JSON.stringify({fuentes:fuentes.length,mediciones:medidas.length,fotos,recursos:leer(`${dir}/recursos.json`).length,suite:'salida 0, sin errores ni fallos',tablero:'T66 a verificar por Claude'},null,2));
