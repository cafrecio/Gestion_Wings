import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
const dir=path.resolve('docs/06-pruebas/PRU-04/evidencia-t66-codex');
const origen=path.resolve('public/build'),destino=path.join(dir,'build'),vistos=new Set(),archivos=[];
function copiar(rel) {
 rel=rel.split('?')[0];const src=path.resolve(origen,rel),dst=path.resolve(destino,rel);
 if(!src.startsWith(origen+path.sep)||!dst.startsWith(destino+path.sep))throw Error('Ruta fuera de recursos: '+rel);
 if(vistos.has(src))return;vistos.add(src);
 const bytes=fs.readFileSync(src);fs.mkdirSync(path.dirname(dst),{recursive:true});fs.writeFileSync(dst,bytes);
 archivos.push({ruta:path.relative(dir,dst).replaceAll('\\','/'),sha256:createHash('sha256').update(bytes).digest('hex')});
 if(!/\.(css|js)$/.test(src))return;
 const texto=bytes.toString('utf8');
 for(const m of texto.matchAll(/(?:["'](\.\.?\/[^"']+\.(?:js|css))["']|url\(["']?([^\s)"']+)["']?\))/g)) {
  const dep=m[1]||m[2];if(dep.startsWith('/build/')){copiar(dep.slice(7));continue;}if(/^(data:|https?:|\/)/.test(dep))continue;
  copiar(path.join(path.dirname(rel),dep));
 }
}
for(const nombre of fs.readdirSync(path.join(dir,'html'))) {
 const html=fs.readFileSync(path.join(dir,'html',nombre),'utf8');
 for(const m of html.matchAll(/\/build\/([^"'\s<>]+)/g))copiar(m[1]);
}
fs.writeFileSync(path.join(dir,'recursos.json'),JSON.stringify(archivos,null,2));
console.log(`${archivos.length} recursos usados, con SHA-256`);
