import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {spawnSync} from 'node:child_process';

const raiz=process.cwd();
const copia=path.resolve(raiz,'storage/app/verificacion-t3-codex-f4e6436');
const salida=path.resolve(raiz,'docs/06-pruebas/PRU-04/evidencia-t3-codex');
if(!copia.startsWith(path.resolve(raiz,'storage/app')+path.sep)||!fs.existsSync(path.join(copia,'.git/HEAD')))throw Error('Falta copia aislada');
const archivos=['docs/00-estado/tareas.json','docs/00-estado/TABLERO.html','docs/00-estado/QUIEN-HACE-QUE.md','docs/06-pruebas/PRU-02/DEFECTOS.md','docs/06-pruebas/PRU-02/DEFECTOS.html'];
const originales=Object.fromEntries(archivos.map(f=>[f,fs.readFileSync(path.join(copia,f))]));
const compartidos=Object.fromEntries(archivos.map(f=>[f,fs.readFileSync(path.join(raiz,f))]));
const sha=b=>crypto.createHash('sha256').update(b).digest('hex');
const env={...process.env,DB_DATABASE:'wings_testing_codex'};
function run(programa,args,nombre){
 const x=spawnSync(programa,args,{cwd:copia,env,encoding:'utf8',timeout:120000,windowsHide:true});
 if(x.error)throw x.error;
 const texto=(x.stdout??'')+(x.stderr??'');
 fs.writeFileSync(path.join(salida,nombre+'.txt'),texto.trimEnd()+'\n');
 return {exitCode:x.status,texto};
}
function restore(){for(const [f,b] of Object.entries(originales))fs.writeFileSync(path.join(copia,f),b);}
function test(nombre,extras=[]){return run('php',[path.join(raiz,'vendor/phpunit/phpunit/phpunit'),'--no-configuration','--bootstrap',path.join(raiz,'vendor/autoload.php'),'--colors=never',...extras,path.join(copia,'tests/Feature/TableroAlDiaTest.php')],nombre);}
const resumen={copia,base:'wings_testing_codex',sinConexionBdEnEstasPruebas:true,casos:[]};
function check(nombre,res,expected,archivo){
 const cumple=res.exitCode===expected&&(!archivo||res.texto.includes(archivo));
 resumen.casos.push({nombre,exitCode:res.exitCode,esperado:expected,archivoNombrado:archivo??null,cumple});
 if(!cumple)throw Error('Control falló: '+nombre+'\n'+res.texto);
}
try{
 check('base-sana',test('base-sana'),0);
 const tiempos=Object.fromEntries(archivos.map(f=>[f,fs.statSync(path.join(copia,f)).mtimeMs]));
 check('cambiar-B11-paso',run('php',['scripts/tablero/tablero.php','cambiar','B11','paso=prueba'],'cambiar-B11-paso'),0);
 resumen.escrituraB11=archivos.map(f=>({archivo:f,contenidoCambio:!originales[f].equals(fs.readFileSync(path.join(copia,f))),mtimeCambio:fs.statSync(path.join(copia,f)).mtimeMs!==tiempos[f]}));
 run('git',['diff','--stat'],'diff-B11-paso-stat');run('git',['diff','--',...archivos],'diff-B11-paso');
 restore();
 check('cambiar-B11-estado',run('php',['scripts/tablero/tablero.php','cambiar','B11','estado=en_curso','tiene=Codex','paso=prueba'],'cambiar-B11-estado'),0);
 resumen.escrituraEstado=archivos.map(f=>({archivo:f,contenidoCambio:!originales[f].equals(fs.readFileSync(path.join(copia,f)))}));
 run('git',['diff','--stat'],'diff-B11-estado-stat');
 restore();
 const html=archivos[4],md=archivos[3],tablero=archivos[1];
 let original=originales[html].toString('utf8');
 if(!original.includes('<input id="A1" type="checkbox" checked>'))throw Error('Casilla A1 inesperada');
 fs.writeFileSync(path.join(copia,html),original.replace('<input id="A1" type="checkbox" checked>','<input id="A1" type="checkbox">'));
 check('casilla-a-mano',test('casilla-a-mano'),1,html);
 restore();
 original=originales[md].toString('utf8');
 const editado=original.replace(/(^### A1\.[^\r\n]*?)CERRADO[^\r\n]*/m,'$1Sin empezar');
 if(editado===original)throw Error('No cambió encabezado A1');
 fs.writeFileSync(path.join(copia,md),editado);
 check('encabezado-a-mano',test('encabezado-a-mano'),1,md);
 restore();
 original=originales[tablero].toString('utf8');
 const numero=original.replace(/(<span class="n">)\d+(<\/span>)/,'$1999$2');
 if(numero===original)throw Error('No cambió número');
 fs.writeFileSync(path.join(copia,tablero),numero);
 check('numero-a-mano',test('numero-a-mano'),1,tablero);
 restore();
 for(const [f,b] of Object.entries(originales))fs.writeFileSync(path.join(copia,f),b.toString('utf8').replace(/\r?\n/g,'\r\n'));
 check('cinco-archivos-CRLF',test('cinco-archivos-CRLF'),0);
 restore();
 // Prueba de solapamiento: el guardián nuevo no inventa una ficha HTML ausente.
 fs.writeFileSync(path.join(copia,html),originales[html].toString('utf8').split('\n').filter(l=>!l.includes('<input id="A1"')).join('\n'));
 check('ficha-html-ausente-guardian-nuevo',test('ficha-html-ausente-guardian-nuevo'),0);
 const viejo=run('php',[path.join(raiz,'vendor/phpunit/phpunit/phpunit'),'--no-configuration','--bootstrap',path.join(raiz,'vendor/autoload.php'),'--colors=never',path.join(copia,'tests/Feature/DefectosNoDivergenTest.php')],'ficha-html-ausente-guardian-anterior');
 check('ficha-html-ausente-guardian-anterior',viejo,1,'A1');
 restore();
 check('restaurada-sana',test('restaurada-sana'),0);
 resumen.restaurada=archivos.every(f=>originales[f].equals(fs.readFileSync(path.join(copia,f))));
 resumen.compartidaIntacta=archivos.every(f=>compartidos[f].equals(fs.readFileSync(path.join(raiz,f))));
 resumen.shaCompartidos=Object.fromEntries(Object.entries(compartidos).map(([f,b])=>[f,sha(b)]));
 if(!resumen.restaurada||!resumen.compartidaIntacta)throw Error('Carpeta no preservada');
}finally{
 restore();fs.writeFileSync(path.join(salida,'pruebas-copia.json'),JSON.stringify(resumen,null,2)+'\n');
}
console.log(JSON.stringify(resumen,null,2));
