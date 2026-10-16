import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {execFileSync} from 'node:child_process';

const out=path.dirname(new URL(import.meta.url).pathname.replace(/^\/([A-Z]:)/i,'$1'));
const sha=s=>crypto.createHash('sha256').update(s).digest('hex');
const read=(rev,file)=>execFileSync('git',['show',rev+':'+file],{encoding:'utf8',maxBuffer:12*1024*1024}).replaceAll('\r','');
const plain=s=>s.replace(/<[^>]*>/g,'').replaceAll('&amp;','&').trim();
function md(text){
 const result={};
 for(const m of text.matchAll(/^### ([AB]\d+)\. (.*)\n([\s\S]*?)(?=^#{2,3} |$(?![\s\S]))/gm)){
  result[m[1]]={encabezado:m[2],cerrado:/CERRADO/i.test(m[2]),cuerpoSha:sha(m[3]),cuerpo:m[3]};
 }
 return result;
}
function html(text){
 const result={};
 for(const line of text.split('\n')){
  const input=line.match(/<input id="([AB]\d+)" type="checkbox"( checked)?>/);
  if(!input)continue;
  const label=line.match(/<label[^>]*>([\s\S]*?)<span class="detail">/);
  const detail=line.match(/<span class="detail">([\s\S]*)<\/span><\/label>/);
  result[input[1]]={cerrado:!!input[2],tagCerrado:/class="tag done"/.test(line),encabezado:plain(label?.[1]??''),cuerpoSha:sha(detail?.[1]??''),cuerpo:detail?.[1]??''};
 }
 return result;
}
const files={md:'docs/06-pruebas/PRU-02/DEFECTOS.md',html:'docs/06-pruebas/PRU-02/DEFECTOS.html'};
const before={md:md(read('f4e6436^',files.md)),html:html(read('f4e6436^',files.html))};
const after={md:md(read('f4e6436',files.md)),html:html(read('f4e6436',files.html))};
const tareas=JSON.parse(read('f4e6436','docs/00-estado/tareas.json'));
const ids=[...new Set([...Object.keys(before.md),...Object.keys(after.md),...Object.keys(before.html),...Object.keys(after.html)])].sort((a,b)=>a.localeCompare(b,undefined,{numeric:true}));
const filas=ids.map(id=>{
 const previo=before.md[id],actual=after.md[id],htmlAntes=before.html[id],htmlDespues=after.html[id];
 if(!previo||!actual||!htmlAntes||!htmlDespues)throw Error('ID ausente '+id);
 const task=tareas.find(t=>t.id===id);
 return {id,mdAntes:previo.encabezado,mdDespues:actual.encabezado,htmlAntes:htmlAntes.encabezado,htmlDespues:htmlDespues.encabezado,
 cerradoMdAntes:previo.cerrado,cerradoMdDespues:actual.cerrado,cerradoHtmlAntes:htmlAntes.cerrado,cerradoHtmlDespues:htmlDespues.cerrado,
 tagAntes:htmlAntes.tagCerrado,tagDespues:htmlDespues.tagCerrado,cuerpoMdIgual:previo.cuerpo===actual.cuerpo,cuerpoHtmlIgual:htmlAntes.cuerpo===htmlDespues.cuerpo,
 fechaCierreAntes:(previo.encabezado.match(/CERRADO (\d{2}\/\d{2})/i)||[])[1]??null,
 fechaCierreDespues:(actual.encabezado.match(/CERRADO (\d{2}\/\d{2})/i)||[])[1]??null,
 verificaTablero:task?.verifica,fechaTablero:task?.fecha};
});
const changedState=filas.filter(x=>x.cerradoMdAntes!==x.cerradoMdDespues||x.cerradoHtmlAntes!==x.cerradoHtmlDespues||x.tagAntes!==x.tagDespues);
const headers=filas.filter(x=>x.mdAntes!==x.mdDespues||x.htmlAntes!==x.htmlDespues);
const dates=filas.filter(x=>x.fechaCierreAntes!==x.fechaCierreDespues);
const resultado={commit:'f4e6436',defectos:filas.length,cambiosDeEstado:changedState,encabezadosDistintos:headers.length,fechasDeCierreDistintas:dates,
 cuerposMdDistintos:filas.filter(x=>!x.cuerpoMdIgual).map(x=>x.id),cuerposHtmlDistintos:filas.filter(x=>!x.cuerpoHtmlIgual).map(x=>x.id),filas};
fs.writeFileSync(path.join(out,'migracion.json'),JSON.stringify(resultado,null,2)+'\n');
const escaped=s=>s.replaceAll('|','/');
const tabla='# Encabezados distintos por la migración T3\n\nComparación independiente de f4e6436^ → f4e6436. Se incluyen diferencias de texto, fecha y atribución; no todas son errores.\n\n| Defecto | Markdown anterior | Markdown nuevo | HTML anterior | HTML nuevo |\n|---|---|---|---|---|\n'+headers.map(x=>'| '+[x.id,x.mdAntes,x.mdDespues,x.htmlAntes,x.htmlDespues].map(escaped).join(' | ')+' |').join('\n')+'\n';
fs.writeFileSync(path.join(out,'ENCABEZADOS.md'),tabla);
console.log(JSON.stringify({defectos:filas.length,cambiosDeEstado:changedState,encabezadosDistintos:headers.length,fechasDeCierreDistintas:dates.map(x=>({id:x.id,antes:x.fechaCierreAntes,despues:x.fechaCierreDespues})),cuerposMdDistintos:resultado.cuerposMdDistintos,cuerposHtmlDistintos:resultado.cuerposHtmlDistintos},null,2));
