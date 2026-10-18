import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
const raiz='resources/views';
const fase=process.argv[2]||'antes';
if(!['antes','despues'].includes(fase))throw Error('Fase inválida');
const archivos=[];
function recorrer(carpeta){for(const e of fs.readdirSync(carpeta,{withFileTypes:true})){const p=path.join(carpeta,e.name);if(e.isDirectory())recorrer(p);else if(e.name.endsWith('.blade.php'))archivos.push(p.replaceAll('\\','/'));}}
recorrer(raiz);
const vistas=archivos.sort().map(archivo=>{const fuente=fs.readFileSync(archivo,'utf8');const lineas=fuente.split(/\r?\n/);return {archivo,sha256:createHash('sha256').update(fuente).digest('hex'),lineas:lineas.length,marcas_interactivas:lineas.flatMap((texto,i)=>/<(?:button|x-ds\.button|x-ds\.toggle)\b|\bds-btn\b|type=["']submit["']|role=["']button["']/.test(texto)?[{linea:i+1,fuente:texto.trim()}]:[]),incluye:[...fuente.matchAll(/@(?:include|extends)\(['"]([^'"]+)/g)].map(m=>m[1]),javascript_incrustado:(fuente.match(/<script(?![^>]*\bsrc\s*=)[\s>]/gi)||[]).length,manejadores:(fuente.match(/\son[a-z]+\s*=/gi)||[]).length};});
const css=fs.readFileSync('resources/css/app.css');
fs.writeFileSync(`docs/06-pruebas/PRU-04/evidencia-t75-consistencia/inventario-fuente-${fase}.json`,JSON.stringify({css_sha256:createHash('sha256').update(css).digest('hex'),vistas},null,2));
console.log(`${vistas.length} vistas leídas; ${vistas.filter(v=>v.marcas_interactivas.length).length} con marcas de controles en la fuente.`);
