import {readFile,writeFile,readdir,access} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const dir=path.dirname(fileURLToPath(import.meta.url)),root=process.cwd();
const json=async p=>JSON.parse(await readFile(path.join(dir,p),'utf8'));
const a10=await json('control-a10-aplicado.json');
for(const [p,sha]of Object.entries(a10.huellas))if(createHash('sha256').update(await readFile(path.join(root,p))).digest('hex')!==sha)throw Error('A10 cambió después de capturas: '+p);
const controles=['comprobacion-a7-columnas.json','comprobacion-a8-final.json','comprobacion-a9-alineacion.json','comprobacion-a10-aplicado-periodos.json'];
let imagenes=0;
for(const nombre of controles){
 const c=await json(nombre);if(c.base!=='wings_testing_codex')throw Error('Base no ficticia '+nombre);
 for(const f of c.capturas){
  const img=await readFile(path.join(dir,'capturas',f.nombre));
  if(img.readUInt32BE(16)!==1280||img.readUInt32BE(20)!==900)throw Error('PNG con tamaño inesperado '+f.nombre);
  if(f.ancho===375){
   if(f.alto!==667||(f.anchoDocumento??f.documento)>375)throw Error('Marco incorrecto '+f.nombre);
   if(nombre==='comprobacion-a9-alineacion.json'&&f.tarjetas.length){
    for(const t of f.tarjetas){const ultimo=t.acciones.at(-1).rect;if(Math.abs(ultimo.x-f.nuevo.x)>0.1||Math.abs(ultimo.right-f.nuevo.right)>0.1)throw Error('A9 no coincide con Nuevo');}
   }
  }
  imagenes++;
 }
}
let enlaces=0,visores=0;
for(const f of await readdir(dir)){
 if(!f.startsWith('visor-')||!f.endsWith('.html'))continue;
 const html=await readFile(path.join(dir,f),'utf8');
 for(const m of html.matchAll(/(?:src|href)="([^"]+)"/g)){
  const ref=m[1];if(ref.startsWith('#')||/^(https?:|data:)/.test(ref))continue;
  await access(path.resolve(dir,decodeURIComponent(ref.split('#')[0])));enlaces++;
 }
 visores++;
}
const suite=await readFile(path.join(dir,'suite-a10-limpiar-2026-10-08.txt'),'utf8');
if(!suite.includes('2 skipped, 508 passed (4077 assertions)')||!suite.includes('423.14s'))throw Error('Suite final no coincide');
const tablero=JSON.parse(await readFile(path.join(root,'docs/00-estado/tareas.json'),'utf8'));
for(const id of ['A6','A7','A8','A9']){const t=tablero.find(t=>t.id===id);if(t.estado!=='cerrado'||t.verifica!=='Carlos')throw Error('Cierre visual incorrecto '+id);}
const log=await readFile(path.join(root,'docs/00-estado/LOG-CODEX.md'),'utf8');
if(log.length>12000||log.split('\n').length>150||[...log.matchAll(/^## \d{4}-/gm)].length>10)throw Error('Bitácora excedida');
const salida={fecha:new Date().toISOString(),huellasA10Vigentes:true,capturasFinalesComprobadas:imagenes,visores,enlacesLocales:enlaces,suite:{aprobadas:508,omitidas:2,aserciones:4077,duracion:423.14},cierresVisuales:['A6','A7','A8','A9'],a10:tablero.find(t=>t.id==='A10').estado,a32:tablero.find(t=>t.id==='A32').estado};
await writeFile(path.join(dir,'control-entrega.json'),JSON.stringify(salida,null,2)+'\n');
console.log(JSON.stringify(salida));
