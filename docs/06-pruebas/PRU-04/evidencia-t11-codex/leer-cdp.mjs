import fs from 'node:fs';
import path from 'node:path';
const raiz=path.resolve('docs/06-pruebas/PRU-04/evidencia-t11-codex');
const meta=JSON.parse(fs.readFileSync(path.join(raiz,'inspector-actual.json'),'utf8'));
const lista=await (await fetch(meta.base+'/json/list')).json();
const pagina=lista.find(p=>p.id===meta.targetId&&p.url.startsWith('https://test.gestionar-te.com.ar/'));
if(!pagina)throw new Error('No está vivo el destino esperado del inspector');
const ws=new WebSocket(pagina.webSocketDebuggerUrl);
await new Promise((r,j)=>{ws.addEventListener('open',r,{once:true});ws.addEventListener('error',j,{once:true});});
let id=0;
function leer(metodo){
    if(!['Page.getAppManifest','Page.getInstallabilityErrors'].includes(metodo))throw new Error('Solo consultas de lectura');
    return new Promise((resolve,reject)=>{
        const n=++id;const temporizador=setTimeout(()=>{ws.removeEventListener('message',respuesta);reject(new Error('Sin respuesta a '+metodo));},10000);
        function respuesta(e){const x=JSON.parse(e.data);if(x.id!==n)return;clearTimeout(temporizador);ws.removeEventListener('message',respuesta);if(x.error)reject(new Error(JSON.stringify(x.error)));else resolve(x.result);}
        ws.addEventListener('message',respuesta);ws.send(JSON.stringify({id:n,method:metodo,params:{}}));
    });
}
try{
    const manifest=await leer('Page.getAppManifest');
    const instalabilidad=await leer('Page.getInstallabilityErrors');
    const datos={chrome:meta.version,sitio:pagina.url,manifest,instalabilidad};
    fs.writeFileSync(path.join(raiz,'chrome-cdp-propio.json'),JSON.stringify(datos,null,2)+'\n');
    console.log(JSON.stringify({chrome:meta.version,manifest:manifest.url,erroresManifest:manifest.errors,instalabilidad},null,2));
}finally{ws.close();}
