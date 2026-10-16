import fs from 'node:fs';
import path from 'node:path';
import {spawnSync,execFileSync} from 'node:child_process';
import crypto from 'node:crypto';
const raiz=process.cwd(),copia=path.resolve('storage/app/verificacion-t3-codex-318ef2f');
const out=path.resolve('docs/06-pruebas/PRU-04/evidencia-t3-codex/segunda-vuelta');
if(!copia.startsWith(path.resolve('storage/app')+path.sep)||!fs.existsSync(path.join(copia,'.git/HEAD')))throw Error('No es la copia aislada');
const docs=['docs/00-estado/tareas.json','docs/00-estado/TABLERO.html','docs/00-estado/QUIEN-HACE-QUE.md','docs/06-pruebas/PRU-02/DEFECTOS.md','docs/06-pruebas/PRU-02/DEFECTOS.html'];
const codigo=['scripts/tablero/lib.php','scripts/tablero/tablero.php'];
const rutas=[...docs,...codigo];
const backups=Object.fromEntries(rutas.map(f=>[f,fs.readFileSync(path.join(copia,f))]));
const shared=Object.fromEntries(rutas.map(f=>[f,fs.readFileSync(path.join(raiz,f))]));
const env={...process.env,DB_DATABASE:'wings_testing_codex'};
const resultado={origen:'318ef2f',base:'wings_testing_codex',copia,casos:[],advertencias:[]};
function restore(){for(const [f,b] of Object.entries(backups))fs.writeFileSync(path.join(copia,f),b);}
function run(program,args,nombre){const p=spawnSync(program,args,{cwd:copia,env,encoding:'utf8',timeout:120000,windowsHide:true});if(p.error)throw p.error;const text=(p.stdout??'')+(p.stderr??'');fs.writeFileSync(path.join(out,nombre+'.txt'),text.trimEnd()+'\n');return {exitCode:p.status,text};}
function expect(nombre,pass,datos={}){resultado.casos.push({nombre,cumple:!!pass,...datos});if(!pass)throw Error('Control no cumplido '+nombre);}
function snap(id){const t=JSON.parse(fs.readFileSync(path.join(copia,docs[0]))).find(t=>t.id===id);const md=fs.readFileSync(path.join(copia,docs[3]),'utf8').split('\n').find(l=>l.startsWith('### '+id+'.'));const html=fs.readFileSync(path.join(copia,docs[4]),'utf8').split('\n').find(l=>l.includes('<input id="'+id+'"'));return {tarea:t,md,html};}
function cli(args,nombre){const p=run('php',['scripts/tablero/tablero.php',...args],nombre);expect(nombre,p.exitCode===0,{exitCode:p.exitCode});if(/Warning:/.test(p.text))resultado.advertencias.push({nombre,comando:['php','scripts/tablero/tablero.php',...args],exitCode:p.exitCode,texto:p.text.trim()});}
function test(nombre,nuevas=true){return run('php',[path.join(raiz,'vendor/phpunit/phpunit/phpunit'),'--no-configuration','--bootstrap',path.join(raiz,'vendor/autoload.php'),'--colors=never',...(nuevas?['--filter','test_cambiar_otro_dato_de_un_cerrado|test_al_cerrar_se_guarda_el_dia']:[]),path.join(copia,'tests/Feature/TableroAlDiaTest.php')],nombre);}
try{
 const base=test('cinco-actuales',false);expect('cinco-actuales',base.exitCode===0,{exitCode:base.exitCode});
 resultado.a6Antes=snap('A6');cli(['cambiar','A6','paso=prueba'],'A6-paso-actual');resultado.a6Despues=snap('A6');
 expect('A6-fecha-y-ambos-documentos-estables',resultado.a6Antes.tarea.cerrado_el===resultado.a6Despues.tarea.cerrado_el&&resultado.a6Antes.md===resultado.a6Despues.md&&resultado.a6Antes.html===resultado.a6Despues.html);
 restore();
 cli(['cambiar','B11','estado=cerrado','hizo=Claude','verifica=Codex'],'cerrar-B11');resultado.b11Cerrada=snap('B11');
 expect('cierre-tiene-fecha-de-hoy',resultado.b11Cerrada.tarea.cerrado_el===resultado.b11Cerrada.tarea.fecha&&resultado.b11Cerrada.md.includes('CERRADO')&&resultado.b11Cerrada.html.includes('type="checkbox" checked'));
 cli(['cambiar','B11','paso=Otro dato'],'B11-dato-despues');resultado.b11OtroDato=snap('B11');
 expect('otro-dato-no-mueve-fecha',resultado.b11OtroDato.tarea.cerrado_el===resultado.b11Cerrada.tarea.cerrado_el&&resultado.b11OtroDato.md===resultado.b11Cerrada.md&&resultado.b11OtroDato.html===resultado.b11Cerrada.html);
 const codigoFuturo="require 'scripts/tablero/lib.php'; $t=tablero_cambiar(tablero_leer(),'B11',['paso'=>'Otro dia simulado'],'2030-02-05'); if(tablero_validar($t))exit(2); tablero_guardar_y_generar($t);";
 const futuro=run('php',['-r',codigoFuturo],'B11-dia-distinto-helper');expect('helper-dia-distinto',futuro.exitCode===0);resultado.b11DiaDistinto=snap('B11');
 expect('fecha-cierre-independiente-del-ultimo-cambio',resultado.b11DiaDistinto.tarea.fecha==='2030-02-05'&&resultado.b11DiaDistinto.tarea.cerrado_el===resultado.b11Cerrada.tarea.cerrado_el&&resultado.b11DiaDistinto.md===resultado.b11Cerrada.md&&resultado.b11DiaDistinto.html===resultado.b11Cerrada.html);
 cli(['cambiar','B11','estado=devuelto','tiene=Claude','paso=Reabierta de prueba'],'reabrir-B11');resultado.b11Reabierta=snap('B11');
 expect('reabrir-borra-fecha-en-datos-y-cierre-en-documentos',resultado.b11Reabierta.tarea.cerrado_el===''&&!resultado.b11Reabierta.md.includes('CERRADO')&&!resultado.b11Reabierta.html.includes('type="checkbox" checked')&&!resultado.b11Reabierta.html.includes('tag done'));
 restore();
 const nuevas=test('dos-nuevas-actuales');expect('dos-nuevas-actuales',nuevas.exitCode===0,{exitCode:nuevas.exitCode});
 for(const f of codigo)fs.writeFileSync(path.join(copia,f),execFileSync('git',['show','f4e6436:'+f],{cwd:raiz,maxBuffer:8*1024*1024}));
 const viejas=test('dos-nuevas-con-codigo-f4e6436');expect('dos-nuevas-rojas-con-codigo-anterior',viejas.exitCode!==0&&viejas.text.includes('Errors: 2'),{exitCode:viejas.exitCode,motivo:'tablero_cambiar no existe en f4e6436; son errores de API ausente, no dos aserciones rojas de fecha'});
 resultado.a6AntesLegacy=snap('A6');cli(['cambiar','A6','paso=prueba'],'A6-paso-codigo-f4e6436');resultado.a6DespuesLegacy=snap('A6');
 const cierrePrevio=resultado.a6AntesLegacy.tarea.cerrado_el;
 const cambioLegacy=resultado.a6DespuesLegacy.tarea.fecha;
 const selloAnterior='CERRADO '+cierrePrevio.slice(8,10)+'/'+cierrePrevio.slice(5,7);
 const selloNuevo='CERRADO '+cambioLegacy.slice(8,10)+'/'+cambioLegacy.slice(5,7);
 expect('bug-original-reproducido-por-comando-real',selloAnterior!==selloNuevo&&resultado.a6AntesLegacy.md.includes(selloAnterior)&&resultado.a6DespuesLegacy.md.includes(selloNuevo)&&resultado.a6DespuesLegacy.html.includes(selloNuevo));
 restore();
 const final=test('cinco-actuales-restauradas',false);expect('cinco-actuales-restauradas',final.exitCode===0,{exitCode:final.exitCode});
 resultado.copiaRestaurada=rutas.every(f=>backups[f].equals(fs.readFileSync(path.join(copia,f))));
 resultado.compartidaIntacta=rutas.every(f=>shared[f].equals(fs.readFileSync(path.join(raiz,f))));
 resultado.hashesCompartida=Object.fromEntries(Object.entries(shared).map(([f,b])=>[f,crypto.createHash('sha256').update(b).digest('hex')]));
 expect('restauracion-y-compartida-intactas',resultado.copiaRestaurada&&resultado.compartidaIntacta);
}finally{restore();fs.writeFileSync(path.join(out,'pruebas-copia.json'),JSON.stringify(resultado,null,2)+'\n');}
console.log(JSON.stringify({casos:resultado.casos,copiaRestaurada:resultado.copiaRestaurada,compartidaIntacta:resultado.compartidaIntacta},null,2));
