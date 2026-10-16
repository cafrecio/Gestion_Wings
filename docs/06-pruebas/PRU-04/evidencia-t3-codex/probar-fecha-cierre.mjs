import fs from 'node:fs';
import path from 'node:path';
import {spawnSync} from 'node:child_process';
const raiz=process.cwd(),copia=path.resolve('storage/app/verificacion-t3-codex-f4e6436');
const out=path.resolve('docs/06-pruebas/PRU-04/evidencia-t3-codex');
if(!copia.startsWith(path.resolve('storage/app')+path.sep)||!fs.existsSync(path.join(copia,'.git/HEAD')))throw Error('Copia no válida');
const rutas=['docs/00-estado/tareas.json','docs/00-estado/TABLERO.html','docs/00-estado/QUIEN-HACE-QUE.md','docs/06-pruebas/PRU-02/DEFECTOS.md','docs/06-pruebas/PRU-02/DEFECTOS.html'];
const backup=Object.fromEntries(rutas.map(f=>[f,fs.readFileSync(path.join(copia,f))]));
const shared=Object.fromEntries(rutas.map(f=>[f,fs.readFileSync(path.join(raiz,f))]));
function estado(){const tareas=JSON.parse(fs.readFileSync(path.join(copia,rutas[0])));return {tarea:tareas.find(t=>t.id==='A6'),encabezado:fs.readFileSync(path.join(copia,rutas[3]),'utf8').split('\n').find(l=>l.startsWith('### A6.')),html:fs.readFileSync(path.join(copia,rutas[4]),'utf8').split('\n').find(l=>l.includes('<input id="A6"'))};}
let antes,despues;
try{
 antes=estado();
 const p=spawnSync('php',['scripts/tablero/tablero.php','cambiar','A6','paso=prueba'],{cwd:copia,env:{...process.env,DB_DATABASE:'wings_testing_codex'},encoding:'utf8',windowsHide:true});
 if(p.error||p.status!==0)throw p.error??Error(p.stderr);
 fs.writeFileSync(path.join(out,'cambiar-A6-paso.txt'),p.stdout+p.stderr);
 despues=estado();
 const diff=spawnSync('git',['diff','--',rutas[0],rutas[3],rutas[4]],{cwd:copia,encoding:'utf8'});
 fs.writeFileSync(path.join(out,'fecha-cierre-diff.txt'),diff.stdout);
 const guardian=spawnSync('php',[path.join(raiz,'vendor/phpunit/phpunit/phpunit'),'--no-configuration','--bootstrap',path.join(raiz,'vendor/autoload.php'),'--colors=never',path.join(copia,'tests/Feature/TableroAlDiaTest.php')],{cwd:copia,env:{...process.env,DB_DATABASE:'wings_testing_codex'},encoding:'utf8'});
 fs.writeFileSync(path.join(out,'fecha-cierre-guardian.txt'),guardian.stdout+guardian.stderr);
 const resultado={comando:'php scripts/tablero/tablero.php cambiar A6 paso="prueba"',antes,despues,estadoSigueCerrado:antes.tarea.estado==='cerrado'&&despues.tarea.estado==='cerrado',fechaCierreTextoCambio:antes.encabezado!==despues.encabezado,guardianExitCode:guardian.status,compartidaIntacta:rutas.every(f=>shared[f].equals(fs.readFileSync(path.join(raiz,f))))};
 fs.writeFileSync(path.join(out,'fecha-cierre.json'),JSON.stringify(resultado,null,2)+'\n');
 console.log(JSON.stringify({antes:antes.encabezado,despues:despues.encabezado,estadoSigueCerrado:resultado.estadoSigueCerrado,guardianExitCode:guardian.status,compartidaIntacta:resultado.compartidaIntacta},null,2));
}finally{for(const [f,b] of Object.entries(backup))fs.writeFileSync(path.join(copia,f),b);}
