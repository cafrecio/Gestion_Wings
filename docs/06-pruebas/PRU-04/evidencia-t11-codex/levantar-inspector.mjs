import fs from 'node:fs';
import path from 'node:path';
import net from 'node:net';
import crypto from 'node:crypto';
import {spawn} from 'node:child_process';

// Solo prepara un Chrome aislado como destino del inspector. Toda interacción
// con la interfaz del DevTools auténtico se hace después mediante cua_repl.
const evidencia=path.resolve('docs/06-pruebas/PRU-04/evidencia-t11-codex');
const almacenamiento=path.resolve('storage/app');
const perfil=path.join(almacenamiento,'t11-codex-chrome-'+crypto.randomUUID());
if(!perfil.startsWith(almacenamiento+path.sep)||fs.existsSync(perfil))throw new Error('Perfil no seguro');
fs.mkdirSync(perfil,{recursive:true});
const reserva=net.createServer();await new Promise(r=>reserva.listen(0,'127.0.0.1',r));
const puerto=reserva.address().port;await new Promise(r=>reserva.close(r));
const base='http://127.0.0.1:'+puerto;
const chrome=spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',[
    '--headless=new','--no-first-run','--no-default-browser-check',
    '--remote-debugging-address=127.0.0.1','--remote-debugging-port='+puerto,
    '--remote-allow-origins='+base,'--user-data-dir='+perfil,
    'https://test.gestionar-te.com.ar/login'],{windowsHide:true,stdio:['ignore','ignore','pipe']});
chrome.stderr.on('data',()=>{});
chrome.on('error',e=>{console.error(e.message);process.exitCode=1;});
let destino;
const limite=Date.now()+30000;
while(!destino&&Date.now()<limite){
    if(chrome.exitCode!==null)throw new Error('Chrome finalizó antes de iniciar el inspector');
    try{const lista=await (await fetch(base+'/json/list')).json();destino=lista.find(p=>p.type==='page'&&p.url.startsWith('https://test.gestionar-te.com.ar/'));}catch{}
    if(!destino)await new Promise(r=>setTimeout(r,200));
}
if(!destino){chrome.kill();throw new Error('No inició la página del sitio de prueba');}
const version=await (await fetch(base+'/json/version')).json();
const inspector=base+'/devtools/inspector.html?panel=resources&ws='+destino.webSocketDebuggerUrl.replace(/^ws:\/\//,'');
const prueba=await fetch(inspector);
if(!prueba.ok){chrome.kill();throw new Error('No se sirve el frontend auténtico del inspector: '+prueba.status);}
const datos={inspector,base,chromePid:chrome.pid,nodePid:process.pid,perfil,
    sitio:destino.url,version:version.Browser,targetId:destino.id};
fs.writeFileSync(path.join(evidencia,'inspector-actual.json'),JSON.stringify(datos,null,2));
console.log(JSON.stringify(datos,null,2));
chrome.on('exit',(codigo)=>{
    console.log('Chrome aislado terminó: '+codigo);
    process.exitCode=0;
});
