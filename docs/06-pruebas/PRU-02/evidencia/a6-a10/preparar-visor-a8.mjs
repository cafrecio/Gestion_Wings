import {readFile,writeFile,access} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
const carpeta=path.dirname(fileURLToPath(import.meta.url));
const datos=JSON.parse(await readFile(path.join(carpeta,'comprobacion-a8-final.json'),'utf8'));
const controles=[];
for(const c of datos.capturas) {
 await access(path.join(carpeta,'capturas',c.nombre));
 if(c.anchoDocumento>c.ancho)throw new Error('Desborde '+c.nombre);
 if(c.ancho===375&&c.tarjetas.length) {
  for(const t of c.tarjetas) {
   if(t.acciones.some(a=>a.rect.width!==96||a.rect.height!==32))throw new Error('Tamaño '+c.nombre);
   if(t.acciones.length===2&&Math.abs(t.acciones[0].rect.y-t.acciones[1].rect.y)>0.1)throw new Error('Botones desalineados');
   if(t.acciones.at(-1)?.rect.right!==320)throw new Error('Borde derecho '+c.nombre);
   if(t.interruptor&&t.interruptor.right!==320)throw new Error('Interruptor '+c.nombre);
  }
 }
}
for(const caso of ['grupos-admin','grupos-operativo','niveles-admin','profesores-admin']) {
 const hash=async nombre=>createHash('sha256').update(await readFile(path.join(carpeta,'capturas',nombre))).digest('hex');
 const antes=await hash('a8-'+caso+'-desktop.png'),despues=await hash('a8-final-'+caso+'-desktop.png');
 if(antes!==despues)throw new Error('Escritorio cambió '+caso);
 controles.push({caso,antes,despues,identico:true});
}
const telefono=(nombre,titulo)=>`<figure><figcaption>${titulo}</figcaption><div class="telefono"><img src="capturas/${nombre}.png" alt="${titulo}"></div><a href="capturas/${nombre}.png">Imagen original</a></figure>`;
const paginas=[
 {nombre:'grupos',titulo:'Grupos',celulares:[['a8-final-grupos-admin-375-tarjeta-2','Activo: datos y controles completos'],['a8-final-grupos-admin-375-tarjeta-3','Inactivo: datos y controles completos']],extras:[['a8-final-grupos-admin-375-inicio','Inicio y Nuevo'],['a8-final-grupos-operativo-375-tarjeta-2','OPERATIVO: solo Ver']],desktop:'a8-final-grupos-admin-desktop',antes:'a8-grupos-admin-375-tarjeta-2',nota:'Verde: Activo. Gris: Inactivo. Editar y Ver comparten fila; el interruptor termina a derecha debajo.'},
 {nombre:'niveles',titulo:'Niveles',celulares:[['a8-final-niveles-admin-375-tarjeta-2','Con grupos: punto verde'],['a8-final-niveles-admin-375-tarjeta-5','Sin grupos: punto gris']],extras:[['a8-final-niveles-admin-375-inicio','Inicio y Nuevo']],desktop:'a8-final-niveles-admin-desktop',antes:'a8-niveles-admin-375-tarjeta-4',nota:'Verde: Con grupos. Gris: Sin grupos. No se agrega un interruptor. El nivel con un grupo inactivo sigue teniendo un grupo asociado.'},
 {nombre:'profesores',titulo:'Profesores',celulares:[['a8-final-profesores-admin-375-tarjeta-1','Activo e Inactivo con todos los controles']],extras:[['a8-final-profesores-admin-375-inicio','Inicio y Nuevo']],desktop:'a8-final-profesores-admin-desktop',antes:'a8-profesores-admin-375-tarjeta-1',nota:'Verde: Activo. Gris: Inactivo. Se conserva el distintivo coloreado. Ver y Editar comparten fila; el interruptor termina a derecha debajo.'}
];
for(const p of paginas) {
 const html=`<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>A8/A9 · ${p.titulo}</title><link rel="stylesheet" href="visor.css"></head><body><main><h1>${p.titulo} · resultado actual</h1><nav><a href="visor-a8-grupos.html">Grupos</a> · <a href="visor-a8-niveles.html">Niveles</a> · <a href="visor-a8-profesores.html">Profesores</a></nav><p class="nota">${p.nota}</p><div class="comparacion">${p.celulares.map(([n,t])=>telefono(n,t)).join('')}</div><h2>Escritorio</h2><p>Imagen idéntica al antes del ajuste móvil (SHA256).</p><img class="escritorio" src="capturas/${p.desktop}.png" alt="${p.titulo} escritorio"><details><summary>Inicio y otros casos</summary><div class="comparacion">${p.extras.map(([n,t])=>telefono(n,t)).join('')}</div></details><details><summary>Antes: controles a izquierda</summary>${telefono(p.antes,'Antes del ajuste móvil')}</details><p>08/10/2026 · Laravel real · datos ficticios · base wings_testing_codex · celular en marco 375×667. Sin cierre ni publicación.</p><p><a href="comprobacion-a8-final.json">Mediciones originales</a> · <a href="control-a8-final.json">Control de alineación y huellas</a> · <a href="capturas/a8-final-login-375.png">Login de control</a></p></main></body></html>`;
 await writeFile(path.join(carpeta,'visor-a8-'+p.nombre+'.html'),html+'\n');
}
await writeFile(path.join(carpeta,'control-a8-final.json'),JSON.stringify({fecha:new Date().toISOString(),capturas:datos.capturas.length,escritorios:controles,movil:{ancho:375,botones:'96×32; dos botones en una misma fila; borde derecho 320',interruptores:'borde derecho 320; componente original',desborde:false}},null,2)+'\n');
for(const nombre of ['visor-a8-grupos.html','visor-a8-niveles.html','visor-a8-profesores.html','visor-a9.html']) {
 const html=await readFile(path.join(carpeta,nombre),'utf8');
 for(const [,ref] of html.matchAll(/(?:src|href)="([^"]+)"/g)) {
  if(!/^https?:|^#/.test(ref))await access(path.resolve(carpeta,ref));
 }
}
console.log('22 imágenes; cuatro escritorios idénticos; controles móviles alineados; tres visores.');
