export function medir(contexto){
 const win=document.querySelector('#pantalla').contentWindow,d=win.document;
 const visible=e=>{const r=e.getBoundingClientRect(),s=win.getComputedStyle(e);return r.width>0&&r.height>0&&s.display!=='none'&&s.visibility!=='hidden'&&!e.closest('[hidden]');};
 const red=n=>Math.round(n*100)/100;
 const caja=e=>{const r=e.getBoundingClientRect();return {x:red(r.x),y:red(r.y+win.scrollY),w:red(r.width),h:red(r.height),derecha:red(r.right)};};
 const ruta=e=>{const r=[];while(e&&e!==d.body){let n=1;for(let p=e.previousElementSibling;p;p=p.previousElementSibling)if(p.tagName===e.tagName)n++;r.unshift(e.tagName.toLowerCase()+':nth-of-type('+n+')');e=e.parentElement;}return r.join('>');};
 const raiz=d.querySelector('.ds-content')||d.querySelector('main')||d.body;
 const controles=[...raiz.querySelectorAll('button,a,input[type=submit],.ds-toggle,[role=switch]')].filter(visible).filter(e=>!e.parentElement.closest('.ds-btn,.ds-toggle'));
 const grupos=new Map();
 for(const e of controles){
  let zona=e.closest('.alumno-actions,.mobile-form-actions,.form-actions,.filtros-actions');
  if(!zona)zona=e.closest('.stats-bar');
  if(!zona){for(let p=e.parentElement;p&&p!==raiz.parentElement;p=p.parentElement){if(['flex','inline-flex','grid','inline-grid'].includes(win.getComputedStyle(p).display)){zona=p;break;}}}
  zona||=e.parentElement;
  const clave=ruta(zona);if(!grupos.has(clave))grupos.set(clave,{elemento:zona,controles:[]});
  let categoria='barra';
  if(contexto.slug==='login')categoria='ingreso';
  else if(e.closest('td,th'))categoria='fila-tabla';
  else if(e.closest('nav'))categoria='navegacion';
  else {let modal=false;for(let p=e.parentElement;p&&p!==d.body;p=p.parentElement){const s=win.getComputedStyle(p);if(p.getAttribute('role')==='dialog'||(s.position==='fixed'&&parseInt(s.zIndex)>=100)){modal=true;break;}}if(modal)categoria='modal';else if(e.closest('.alumno-actions'))categoria='pie-tarjeta';else if(!e.closest('form,.stats-bar')&&!['flex','grid','inline-flex','inline-grid'].includes(win.getComputedStyle(zona).display))categoria='otro';}
  const etiqueta=(e.textContent||e.value||e.getAttribute('aria-label')||e.title||'').trim().replace(/\s+/g,' ').slice(0,110);
  grupos.get(clave).controles.push({ruta:ruta(e),id:e.id,etiqueta,categoria,tag:e.tagName.toLowerCase(),rect:caja(e),disabled:!!e.disabled,
 clases:e.className,textoVisible:e.textContent.trim().replace(/\s+/g,' '),href:e.getAttribute('href'),tipo:e.getAttribute('type'),
 estilo:{padding:win.getComputedStyle(e).padding,fontSize:win.getComputedStyle(e).fontSize,fontWeight:win.getComputedStyle(e).fontWeight,lineHeight:win.getComputedStyle(e).lineHeight,boxSizing:win.getComputedStyle(e).boxSizing,background:win.getComputedStyle(e).backgroundColor},
 padre:{tag:e.parentElement.tagName,clases:e.parentElement.className,estilo:e.parentElement.getAttribute('style')},
 esAccion:((e.tagName==='BUTTON'&&!!e.textContent.trim())||(e.tagName==='A'&&(e.classList.contains('ds-btn')||e.classList.contains('ds-btn-row')||e.getAttribute('role')==='button'||/inline-flex|height:\s*(?:3[0268]|4[024])px/.test(e.getAttribute('style')||''))))&&!e.classList.contains('ds-btn--icon')&&!e.classList.contains('ds-toggle')&&!e.classList.contains('btn-toggle-password')});
 }
 const zonas=[];
 for(const [clave,g]of grupos){
  const s=win.getComputedStyle(g.elemento),r=g.elemento.getBoundingClientRect();
  const izquierda=r.left+parseFloat(s.borderLeftWidth||0)+parseFloat(s.paddingLeft||0),derecha=r.right-parseFloat(s.borderRightWidth||0)-parseFloat(s.paddingRight||0);
  const filas=[];for(const e of g.controles){const cy=e.rect.y+e.rect.h/2;let fila=filas.find(f=>Math.abs(f.cy-cy)<5);if(!fila){fila={cy,controles:[]};filas.push(fila);}fila.controles.push(e);}
  for(const f of filas){f.controles.sort((a,b)=>a.rect.x-b.rect.x);const inicio=f.controles[0].rect.x,fin=Math.max(...f.controles.map(e=>e.rect.derecha));const hueco=parseFloat(s.columnGap)||parseFloat(s.gap)||0;const separada=f.controles.some((e,i)=>i>0&&e.rect.x-f.controles[i-1].rect.derecha>hueco+12);
   f.alineacion=separada?'distribuidos':Math.abs(fin-derecha)<=4?'derecha':Math.abs(inicio-izquierda)<=4?'izquierda':Math.abs((inicio+fin)-(izquierda+derecha))<=8?'centrados':'intermedia';
   f.cy=red(f.cy);
  }
  zonas.push({ruta:clave,clases:g.elemento.className,rect:caja(g.elemento),display:s.display,justify:s.justifyContent,direccion:s.flexDirection,izquierda:red(izquierda),derecha:red(derecha),filas});
 }
 const paneles=[...d.querySelectorAll('[id^="modal"],[id^="panel-"],[id^="form-"]')].filter(e=>e.querySelector('button,a')&&!visible(e)&&!e.parentElement.closest('[id^="modal"],[id^="panel-"],[id^="form-"]')).map(e=>({id:e.id,modal:e.id.startsWith('modal'),etiquetas:[...e.querySelectorAll('button')].map(b=>b.textContent.trim().replace(/\s+/g,' ')).filter(Boolean)}));
 const regiones=[...d.querySelectorAll('div[style*="overflow"],[role=region]')].filter(e=>visible(e)&&e.scrollWidth>e.clientWidth+20&&e.querySelector('table button,table a,table input'));
 const navegacion=[...d.querySelectorAll('.ds-topbar button,.ds-topbar a.ds-btn')].filter(visible).map(e=>({etiqueta:(e.textContent.trim()||e.getAttribute('aria-label')||''),icono:!e.textContent.trim(),rect:caja(e)}));
 const avisosAcciones=[...d.querySelectorAll('.liquidacion-acciones-movil > span')].filter(visible).map(e=>({texto:e.textContent.trim(),rect:caja(e)}));
 return {ancho:win.innerWidth,cliente:d.documentElement.clientWidth,referenciaDerecha:red(raiz.getBoundingClientRect().right-parseFloat(win.getComputedStyle(raiz).paddingRight||0)-24),anchoDocumento:d.documentElement.scrollWidth,altoDocumento:d.documentElement.scrollHeight,desborde:d.documentElement.scrollWidth>win.innerWidth+1,zonas,paneles,navegacion,avisosAcciones,tablasConAccionesDesplazables:regiones.length,fuentes:!!d.fonts&&d.fonts.status};
}
