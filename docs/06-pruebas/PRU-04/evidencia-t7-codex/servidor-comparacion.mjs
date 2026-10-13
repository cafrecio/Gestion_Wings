import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import {fileURLToPath} from 'node:url';
const raiz=path.dirname(fileURLToPath(import.meta.url));
fs.mkdirSync(path.join(raiz,'img'),{recursive:true});
fs.copyFileSync('public/img/logo-wings.png',path.join(raiz,'img/logo-wings.png'));
const tipos={'.html':'text/html; charset=utf-8','.css':'text/css','.js':'application/javascript','.png':'image/png','.jpg':'image/jpeg','.woff2':'font/woff2','.json':'application/json'};
const etiquetas={};
for(const modo of ['antes','despues']){
 const manifest=JSON.parse(fs.readFileSync(path.join(raiz,'build-'+modo,'.vite/manifest.json'),'utf8'));
 etiquetas[modo]=['resources/css/app.css',...(modo==='antes'?['resources/css/sidebar.css']:[])]
   .map(p=>`<link rel="stylesheet" href="/build-${modo}/${manifest[p].file}">`).join('\n');
}
const servidor=http.createServer((req,res)=>{
 const url=new URL(req.url,'http://localhost');
 if(req.method!=='GET'){res.writeHead(405);res.end();return;}
 if(url.pathname==='/comparar'){
  const caso=url.searchParams.get('caso'),modo=url.searchParams.get('modo'),ancho=Number(url.searchParams.get('ancho'));
  if(!/^[a-z0-9-]+$/.test(caso)||!etiquetas[modo]||![360,1366].includes(ancho)){res.writeHead(400);res.end();return;}
  const archivo=path.join(raiz,caso+'-respuesta.html');
  if(!fs.existsSync(archivo)){res.writeHead(404);res.end();return;}
  const altura=ancho===360?900:1000;
  // Se conserva el HTML real de la aplicación; el marco solo fija su viewport.
  const marco=`<!doctype html><meta charset="utf-8"><title>T7 ${caso} ${modo}</title><style>body{margin:0}iframe{display:block;border:0;width:${ancho}px;height:${altura}px}</style><iframe src="/documento?caso=${caso}&modo=${modo}" title="Wings ${modo}"></iframe>`;
  res.writeHead(200,{'Content-Type':tipos['.html']});res.end(marco);return;
 }
 if(url.pathname==='/documento'){
  const caso=url.searchParams.get('caso'),modo=url.searchParams.get('modo');
  if(!/^[a-z0-9-]+$/.test(caso)||!etiquetas[modo]){res.writeHead(400);res.end();return;}
  const archivo=path.join(raiz,caso+'-respuesta.html');
  if(!fs.existsSync(archivo)){res.writeHead(404);res.end();return;}
  const html=fs.readFileSync(archivo,'utf8').replace(/<script\b[\s\S]*?<\/script>/gi,'')
    .replace(/<link\b[^>]*rel="stylesheet"[^>]*>/gi,'')
    .replace('</head>',etiquetas[modo]+'</head>')
    .replace(/https?:\/\/[^/" ]+\/img\/logo-wings.png/g,'/img/logo-wings.png')
    .replace(/<form\b[^>]*\baction="[^"]*"/gi,m=>m.replace(/action="[^"]*"/,'action="/solo-lectura"'));
  res.writeHead(200,{'Content-Type':tipos['.html']});res.end(html);return;
 }
 const archivo=path.resolve(raiz,'.'+url.pathname);
 if(!archivo.startsWith(raiz+path.sep)||!fs.existsSync(archivo)||!fs.statSync(archivo).isFile()){res.writeHead(404);res.end();return;}
 res.writeHead(200,{'Content-Type':tipos[path.extname(archivo)]||'application/octet-stream'});fs.createReadStream(archivo).pipe(res);
});
servidor.listen(0,'127.0.0.1',()=>{
 const url='http://127.0.0.1:'+servidor.address().port;
 fs.writeFileSync(path.join(raiz,'servidor-actual.json'),JSON.stringify({url,pid:process.pid},null,2));
 console.log(url);
});
