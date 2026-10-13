import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {gunzipSync,gzipSync} from 'node:zlib';
import {fileURLToPath} from 'node:url';
import {createRequire} from 'node:module';
const raiz=path.dirname(fileURLToPath(import.meta.url));
const postcss=createRequire(import.meta.url)('postcss');
const reglasSitio=[];
postcss.parse(fs.readFileSync(path.join(raiz,'app-sitio.css'),'utf8')).walkRules(r=>{
    if(!/\.ds-(?:sidebar|nav-|brand)/.test(r.selector))return;
    const contexto=[];for(let p=r.parent;p?.type!=='root';p=p.parent)if(p.type==='atrule')contexto.unshift('@'+p.name+' '+p.params);
    reglasSitio.push({contexto,selector:r.selector,declaraciones:r.nodes.filter(n=>n.type==='decl').map(d=>[d.prop,d.value,!!d.important])});
});
const fuente=JSON.parse(fs.readFileSync(path.join(raiz,'resultado-css.json'),'utf8'));
if(JSON.stringify(reglasSitio)!==JSON.stringify(fuente.compiladoActual))throw new Error('El CSS servido del menu difiere del compilado verificado');
fs.writeFileSync(path.join(raiz,'css-sitio-confirmado.json'),JSON.stringify({reglasMenu:reglasSitio.length,igualesAlCompiladoActual:true},null,2));
const casos=['admin-sin-asistencia-1366','admin-sin-asistencia-360',
    'operativo-sin-asistencia-1366','operativo-sin-asistencia-360',
    'profesor-sin-asistencia-1366','profesor-sin-asistencia-360',
    'error-sin-contexto-1366','error-sin-contexto-360','login-control-360'];
const filas=JSON.parse(fs.readFileSync(path.join(raiz,'comparaciones-estilos.json'),'utf8')).filter(f=>casos.includes(f.caso));
const resumen=[];
for(const caso of casos){
    const fila=filas.find(f=>f.caso===caso);if(!fila)throw new Error('Falta caso '+caso);
    const ancho=caso.endsWith('1366')?1366:360;
    const pares={};
    for(const modo of ['antes','despues']){
        pares[modo]=JSON.parse(gunzipSync(fs.readFileSync(path.join(raiz,`${caso}-${modo}-estilos.json.gz`))));
        if(pares[modo].ancho!==ancho||pares[modo].alto!==(ancho===360?900:1000)
            ||pares[modo].fonts!=='loaded')throw new Error('Marco o fuentes incorrectos '+caso);
    }
    const diferencias=[];
    if(pares.antes.menu.length!==pares.despues.menu.length)throw new Error('Cambio el menu '+caso);
    pares.antes.menu.forEach((a,i)=>{
        const b=pares.despues.menu[i];
        if(a.tag!==b.tag||a.clase!==b.clase)throw new Error('Cambio el elemento '+caso+'/'+i);
        for(const grupo of ['estilos','antes','despues'])for(const k of new Set([...Object.keys(a[grupo]),...Object.keys(b[grupo])])){
            if(a[grupo][k]!==b[grupo][k])diferencias.push({elemento:i,clase:a.clase,grupo,propiedad:k,antes:a[grupo][k],despues:b[grupo][k]});
        }
    });
    if(diferencias.length!==1||diferencias[0].clase!=='ds-brand__logo'
        ||diferencias[0].propiedad!=='background-position'||diferencias[0].antes!=='0% 0%'
        ||diferencias[0].despues!=='0px 0px')throw new Error('Diferencia no declarada '+caso);
    if(pares.antes.menu[3].estilos['background-image']!=='none'
        ||pares.despues.menu[3].estilos['background-image']!=='none')throw new Error('El logo tiene imagen de fondo '+caso);
    const hash=modo=>crypto.createHash('sha256').update(fs.readFileSync(path.join(raiz,'capturas',`${caso}-${modo}-marco.jpg`))).digest('hex');
    const iguales=hash('antes')===hash('despues');
    // El login es control del marco; su cursor de entrada puede parpadear.
    if(!caso.startsWith('login-')&&!iguales)throw new Error('Cambio la captura '+caso);
    resumen.push({caso,ancho,elementos:pares.antes.menu.length,diferencias,
        logoSinImagenDeFondo:true,capturasIdenticas:iguales,shaAntes:hash('antes'),shaDespues:hash('despues')});
}
fs.writeFileSync(path.join(raiz,'comparaciones-estilos.json'),JSON.stringify(filas,null,2));
fs.writeFileSync(path.join(raiz,'resumen-final.json'),JSON.stringify(resumen,null,2));
// Separar el documento obtenido del sitio a 700 del control que se remaquetó a 360.
const observaciones=JSON.parse(fs.readFileSync(path.join(raiz,'observaciones-sitio.json'),'utf8'));
for(const f of observaciones)if(f.nombre==='login-control-360'&&f.ancho===700)f.nombre='login-origen-700';
fs.writeFileSync(path.join(raiz,'observaciones-sitio.json'),JSON.stringify(observaciones,null,2));
for(const [viejo,nuevo] of [['capturas/login-control-360.jpg','capturas/login-origen-700.jpg'],
    ['login-control-360-estilos.json.gz','login-origen-700-estilos.json.gz']]){
    if(fs.existsSync(path.join(raiz,viejo)))fs.renameSync(path.join(raiz,viejo),path.join(raiz,nuevo));
}
const actual=path.join(raiz,'admin-1366-estilos.json');
if(fs.existsSync(actual)){
    fs.writeFileSync(actual+'.gz',gzipSync(fs.readFileSync(actual)));fs.unlinkSync(actual);
}
for(const salida of ['suite-completa.txt','build.txt','comparacion-compilada.txt']){
    const archivo=path.join(raiz,salida);
    fs.writeFileSync(archivo,fs.readFileSync(archivo,'utf8').split(/\r?\n/).map(l=>l.trimEnd()).join('\n').trimEnd()+'\n');
}
console.log(JSON.stringify({comparaciones:resumen.length,capturasMenuYErrorIdenticas:resumen.filter(r=>!r.caso.startsWith('login-')&&r.capturasIdenticas).length,fallos:0},null,2));
