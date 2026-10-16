import fs from 'node:fs';
import path from 'node:path';
import {execFileSync} from 'node:child_process';
const out=path.resolve('docs/06-pruebas/PRU-04/evidencia-t3-codex/segunda-vuelta');
const read=(rev,file)=>execFileSync('git',['show',rev+':'+file],{encoding:'utf8',maxBuffer:8*1024*1024}).replaceAll('\r','');
const md='docs/06-pruebas/PRU-02/DEFECTOS.md',html='docs/06-pruebas/PRU-02/DEFECTOS.html';
function headers(s){return Object.fromEntries([...s.matchAll(/^### ([AB]\d+)\. (.*)$/gm)].map(m=>[m[1],m[0]]));}
function rows(s){return Object.fromEntries(s.split('\n').map(l=>[l.match(/<input id="([AB]\d+)"/)?.[1],l]).filter(x=>x[0]));}
const prev={md:headers(read('f4e6436^',md)),html:rows(read('f4e6436^',html))};
const now={md:headers(read('318ef2f',md)),html:rows(read('318ef2f',html))};
const tareas=JSON.parse(read('318ef2f','docs/00-estado/tareas.json')).filter(t=>/^[AB]\d+$/.test(t.id));
const servidor=read('f4e6436^','docs/04-tecnico/SERVIDOR.md');
const bloqueB15=servidor.match(/^### El SPF[^\n]*23\/09\/2026[\s\S]*?(?=^###? |$(?![\s\S]))/m)?.[0];
const entradaB15=read('031bdec','docs/06-pruebas/PRU-02/DEFECTOS.md').match(/^### B15\.[^\n]*\n[\s\S]*?(?=^###? |$(?![\s\S]))/m)?.[0];
if(!bloqueB15?.includes('Se corrigió')||!entradaB15?.includes('Verificado y corregido'))throw Error('No se confirma respaldo histórico B15');
const respaldoB15={id:'B15',fecha:'2026-09-23',fuenteTecnica:'f4e6436^:docs/04-tecnico/SERVIDOR.md:99-112',registroContemporaneo:'031bdec5a65d1df235acc2441925117a0cb9a8f0',criterio:'Aceptable en este caso por evidencia complementaria; el último cambio solo no prueba la fecha del cierre.'};
const previoListado=JSON.parse(fs.readFileSync('docs/06-pruebas/PRU-04/evidencia-t3-codex/migracion.json')).filas.filter(x=>x.mdAntes!==x.mdDespues||x.htmlAntes!==x.htmlDespues).map(x=>x.id);
const filas=tareas.map(t=>{
 if(!prev.md[t.id]||!prev.html[t.id]||!now.md[t.id]||!now.html[t.id])throw Error('Falta defecto '+t.id);
 const fecha=prev.md[t.id].match(/\b(\d{2})\/(\d{2})\b/);
 const fechaHistorica=fecha?'2026-'+fecha[2]+'-'+fecha[1]:null;
 return {id:t.id,estado:t.estado,cerrado_el:t.cerrado_el,fechaUltimoCambio:t.fecha,encabezadoAnterior:prev.md[t.id],encabezadoActual:now.md[t.id],fechaHistorica,
 fechaCoincide:fechaHistorica===null?null:t.cerrado_el===fechaHistorica,encabezadoRestaurado:prev.md[t.id]===now.md[t.id],renglonHtmlRestaurado:prev.html[t.id]===now.html[t.id],enListaAnterior56:previoListado.includes(t.id)};
});
const cerrados=filas.filter(x=>x.estado==='cerrado'),abiertos=filas.filter(x=>x.estado!=='cerrado');
const estados={sin_empezar:'Sin empezar',en_curso:'En curso',a_verificar:'Hecho, espera verificación',devuelto:'Devuelto'};
const open=abiertos.map(x=>{const t=tareas.find(t=>t.id===x.id);const esperado=estados[t.estado]+(t.tiene!=='nadie'?', la tiene '+t.tiene:'');return {...x,estadoEsperado:esperado,mdEstadoActual:now.md[x.id].endsWith(esperado),htmlEstadoActual:now.html[x.id].includes(esperado),casillaSinMarcar:/type="checkbox">/.test(now.html[x.id]),sinFechaCierre:t.cerrado_el===''};});
const result={codigo:'318ef2f',historico:'f4e6436^',defectos:filas.length,cerrados:cerrados.length,fechasCoincidentes:cerrados.filter(x=>x.fechaCoincide===true).length,fechasDiferentes:cerrados.filter(x=>x.fechaCoincide===false),fechasSinRespaldoEnEncabezado:cerrados.filter(x=>x.fechaCoincide===null),respaldoComplementarioB15:respaldoB15,encabezadosCerradosDiferentes:cerrados.filter(x=>!x.encabezadoRestaurado),renglonesHtmlCerradosDiferentes:cerrados.filter(x=>!x.renglonHtmlRestaurado),abiertos:open,filas};
fs.writeFileSync(path.join(out,'historia.json'),JSON.stringify(result,null,2)+'\n');
fs.writeFileSync(path.join(out,'FECHAS-CIERRE.md'),'# Las 67 fechas de cierre — contraste propio\n\nEl año 2026 corresponde al corte histórico del proyecto. «Sin fecha» significa que el encabezado anterior no acredita el día; no se completa con el último cambio.\n\n| Defecto | Encabezado anterior a T3 | cerrado_el | Resultado |\n|---|---|---|---|\n'+cerrados.map(x=>'| '+[x.id,x.encabezadoAnterior.replace(/^### /,'').replaceAll('|','/'),x.cerrado_el,x.fechaCoincide===true?'Coincide':x.fechaCoincide===null?'SIN RESPALDO EN ENCABEZADO':'DIFIERE'].join(' | ')+' |').join('\n')+'\n');
fs.appendFileSync(path.join(out,'FECHAS-CIERRE.md'),'\nB15: el encabezado anterior no tenía fecha. El 23/09 se acepta por [evidencia complementaria comprobada](B15-RESPALDO.md), no por igualar automáticamente el último cambio.\n');
fs.writeFileSync(path.join(out,'B15-RESPALDO.md'),'# B15 — respaldo de la fecha histórica\n\nLectura propia de Git, 16/10/2026. No se consultó ni cambió DNS.\n\n- `f4e6436^:docs/04-tecnico/SERVIDOR.md:99–112`: corrección documentada el 23/09/2026.\n- `031bdec5a65d1df235acc2441925117a0cb9a8f0`, 23/09/2026: B15 ya figura «Verificado y corregido».\n- El encabezado inmediato anterior a T3 no incluía fecha; no se atribuye a ese encabezado una prueba que no contiene.\n\n## Sección técnica preservada\n\n```text\n'+bloqueB15.trim()+'\n```\n\n## Entrada contemporánea preservada\n\n```text\n'+entradaB15.trim()+'\n```\n');
console.log(JSON.stringify({defectos:result.defectos,cerrados:result.cerrados,fechasCoincidentes:result.fechasCoincidentes,fechasDiferentes:result.fechasDiferentes.map(x=>x.id),fechasSinRespaldo:result.fechasSinRespaldoEnEncabezado.map(x=>({id:x.id,cerrado_el:x.cerrado_el,fecha:x.fechaUltimoCambio})),encabezadosNoRestaurados:result.encabezadosCerradosDiferentes.map(x=>x.id),renglonesNoRestaurados:result.renglonesHtmlCerradosDiferentes.map(x=>x.id),abiertos:open.map(x=>({id:x.id,estadoActualMd:x.mdEstadoActual,estadoActualHtml:x.htmlEstadoActual,sinFechaCierre:x.sinFechaCierre}))},null,2));
