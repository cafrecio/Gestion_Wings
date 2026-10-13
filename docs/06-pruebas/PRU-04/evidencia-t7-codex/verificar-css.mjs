import fs from 'node:fs';
import path from 'node:path';
import {execFileSync} from 'node:child_process';
import {createRequire} from 'node:module';
import {build} from 'vite';

const require = createRequire(import.meta.url);
const postcss = require('postcss');
const raiz = path.resolve('.');
const evidencia = path.dirname(new URL(import.meta.url).pathname.replace(/^\/([A-Za-z]:)/,'$1'));
const git = (...args) => execFileSync('git',args,{cwd:raiz,encoding:'utf8'});
const antiguoApp = git('show','d4f02d4^:resources/css/app.css');
const antiguoMenu = git('show','d4f02d4^:resources/css/sidebar.css');
const nuevoApp = fs.readFileSync(path.join(raiz,'resources/css/app.css'),'utf8');
const sinComentarios = css => css.replace(/\/\*[\s\S]*?\*\//g,'').replace(/\r\n/g,'\n').trim();
const nodos = css => {
    const root=postcss.parse(css); root.walkComments(c=>c.remove());
    return root.nodes.map(n=>n.toString().replace(/\s+/g,' ').trim());
};
const resultado = {
    anterior:git('rev-parse','d4f02d4^').trim(),
    actual:git('rev-parse','HEAD').trim(),
    mismoContenidoSinComentarios:JSON.stringify(nodos(antiguoApp+'\n'+antiguoMenu))===JSON.stringify(nodos(nuevoApp)),
    mismoBloqueLiteral:sinComentarios(nuevoApp).endsWith(sinComentarios(antiguoMenu)),
    vistasConClasesMenu:git('grep','-l','-E','ds-sidebar|ds-nav-|ds-brand','--','resources/views').trim().split('\n'),
    welcomeSinClases:!/(?:ds-sidebar|ds-nav-|ds-brand)/.test(fs.readFileSync('resources/views/welcome.blade.php','utf8')),
    menuFuenteAnterior:[], menuFuenteActual:[], compiladoAnterior:[], compiladoActual:[],
};
function reglasMenu(css) {
    const rows=[];const root=postcss.parse(css);
    root.walkRules(r=>{
        if(!/\.ds-(?:sidebar|nav-|brand)/.test(r.selector)) return;
        const contexto=[];for(let p=r.parent;p?.type!=='root';p=p.parent) if(p.type==='atrule')contexto.unshift('@'+p.name+' '+p.params);
        rows.push({contexto,selector:r.selector,declaraciones:r.nodes.filter(n=>n.type==='decl').map(d=>[d.prop,d.value,!!d.important])});
    });return rows;
}
resultado.menuFuenteAnterior=reglasMenu(antiguoApp+'\n'+antiguoMenu);
resultado.menuFuenteActual=reglasMenu(nuevoApp);
resultado.mismoOrdenReglasFuente=JSON.stringify(resultado.menuFuenteAnterior)===JSON.stringify(resultado.menuFuenteActual);
fs.writeFileSync(path.join(evidencia,'app-antes-fuente.css'),antiguoApp);
fs.writeFileSync(path.join(evidencia,'sidebar-antes-fuente.css'),antiguoMenu);

for(const modo of ['antes','despues']){
    // Instancias nuevas: Tailwind conserva estado interno entre compilaciones.
    const config=(await import('../../../../vite.config.js?verificacion-t7='+modo)).default;
    const salida=path.join(evidencia,'build-'+modo);
    const entradas=modo==='antes'?['resources/css/app.css','resources/css/sidebar.css']:['resources/css/app.css'];
    const historico={name:'t7-fuente-historica-solo-lectura',enforce:'pre',
        resolveId(id){if(modo==='antes'&&id.replaceAll('\\','/').endsWith('/resources/css/sidebar.css'))return path.join(raiz,'resources/css/sidebar.css');},
        load(id){const archivo=id.replaceAll('\\','/');
            if(modo==='antes'&&archivo.endsWith('/resources/css/app.css'))return antiguoApp;
            if(modo==='antes'&&archivo.endsWith('/resources/css/sidebar.css'))return antiguoMenu;
        }};
    await build({...config,configFile:false,plugins:[historico,...config.plugins],
        build:{outDir:salida,emptyOutDir:true,manifest:true,rollupOptions:{input:entradas.map(p=>path.join(raiz,p))}}});
    const manifest=JSON.parse(fs.readFileSync(path.join(salida,'.vite/manifest.json'),'utf8'));
    const css=entradas.map(p=>fs.readFileSync(path.join(salida,manifest[p].file),'utf8')).join('\n');
    fs.writeFileSync(path.join(evidencia,'compilado-'+modo+'.css'),css);
    resultado[modo==='antes'?'compiladoAnterior':'compiladoActual']=reglasMenu(css);
}
fs.writeFileSync(path.join(evidencia,'resultado-css.json'),JSON.stringify(resultado,null,2));
console.log(JSON.stringify({mismoContenidoSinComentarios:resultado.mismoContenidoSinComentarios,
    mismoBloqueLiteral:resultado.mismoBloqueLiteral,mismoOrdenReglasFuente:resultado.mismoOrdenReglasFuente,
    welcomeSinClases:resultado.welcomeSinClases,vistasConClasesMenu:resultado.vistasConClasesMenu,
    reglasCompiladasAntes:resultado.compiladoAnterior.length,reglasCompiladasDespues:resultado.compiladoActual.length},null,2));
if(!resultado.mismoContenidoSinComentarios||!resultado.mismoBloqueLiteral||!resultado.mismoOrdenReglasFuente||!resultado.welcomeSinClases)process.exitCode=1;
