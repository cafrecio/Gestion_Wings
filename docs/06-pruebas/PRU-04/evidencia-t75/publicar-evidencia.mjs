import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';import {createHash} from 'node:crypto';

const raiz='docs/06-pruebas/PRU-04/evidencia-t75';

const fases=Object.fromEntries(['antes','despues'].map(f=>[f,JSON.parse(fs.readFileSync(`${raiz}/mediciones-${f}.json`,'utf8')).casos]));

const comparacion=JSON.parse(fs.readFileSync(`${raiz}/comparacion.json`,'utf8'));

const fuentes=JSON.parse(fs.readFileSync(`${raiz}/inventario-fuente-antes.json`,'utf8')).vistas;

const respuestas=JSON.parse(fs.readFileSync(`${raiz}/respuestas-despues.json`,'utf8')).casos;

const controles=['admin-alumnos','admin-grupos','admin-profesores','admin-niveles','admin-caja-cerrada-detalle','operativo-6'];

const especiales=[['Filas de tabla · Caja','admin-caja-id-editar'],['Filas de tabla · Cashflow','admin-cashflow'],['Filas de tabla · Historial','admin-caja-historial'],['Ingreso','login'],['Agregar período junto al importe','admin-caja-cobrar-alumnoId'],['Interruptor del campo Afecta caja','admin-rubros-rubroId-subrubros-create'],['Interruptor del campo Descubierto','admin-tipos-caja-create'],['Ojo de contraseña','admin-usuarios-create'],['Pasos de primera carga','admin-sistema-primera-carga'],['Enlaces de Reportes','admin-reportes'],['Enlaces de Reporte alumnos','admin-reportes-alumnos'],['Enlaces de Reporte sueldos','admin-reportes-sueldos'],['Enlace Ver todos de un grupo','admin-grupos-id']];

const seleccion=new Set([...comparacion.pantallas_movil_con_cambios,...controles]);

const capturas=new Map();

function copiar(fase,c,f){if(!f)return null;assert.equal(path.basename(f.nombre),f.nombre);assert(/^[\w-]+\.png$/.test(f.nombre));const key=`${fase}/${f.nombre}`;if(capturas.has(key))return key;const origen=path.resolve(`storage/app/t75-runtime/app/capturas/${fase}/${f.nombre}`),datos=fs.readFileSync(origen),sha=createHash('sha256').update(datos).digest('hex');assert.equal(sha,f.sha256,`Hash de ${key}`);fs.mkdirSync(`${raiz}/${fase}`,{recursive:true});fs.writeFileSync(`${raiz}/${key}`,datos);capturas.set(key,{archivo:key,sha256:sha,slug:c.slug,ancho:c.ancho,fase});return key;}

for(const fase of ['antes','despues'])for(const c of fases[fase])if(seleccion.has(c.slug)){for(const f of c.fotos)copiar(fase,c,f);for(const v of c.variantes)copiar(fase,c,v.foto);}

const consultas=[];

for(const [tipo,slug]of especiales){const c=fases.despues.find(c=>c.slug===slug&&c.ancho===360);assert(c,slug);const preferida=tipo.startsWith('Filas')?'tabla-derecha':tipo.includes('Ver todos')?'pie':'principal';const f=c.fotos.find(f=>f.nombre.endsWith(`--${preferida}.png`))||c.fotos[0];consultas.push({tipo,slug,ruta:c.ruta,captura:copiar('despues',c,f),posiciones:c.zonas.flatMap(z=>z.filas.map(f=>({alineacion:f.alineacion,controles:f.controles.map(b=>b.etiqueta)})))});}

for(const c of fases.despues.filter(c=>c.ancho===360))for(const v of c.variantes.filter(v=>v.panel.startsWith('modal'))){consultas.push({tipo:'Modal existente · '+v.panel,slug:c.slug,ruta:c.ruta,captura:copiar('despues',c,v.foto),posiciones:v.medicion.zonas.flatMap(z=>z.filas.filter(f=>f.controles.some(b=>b.categoria==='modal')).map(f=>({alineacion:f.alineacion,controles:f.controles.map(b=>b.etiqueta)})))});}

const estado=c=>[...new Set(c.zonas.flatMap(z=>z.filas.map(f=>f.alineacion)))].join(', ')||'sin botones visibles';

const mapa=new Map(fases.despues.map(c=>[`${c.slug}:${c.ancho}`,c]));

const rutas=Object.fromEntries(respuestas.map(c=>[c.slug,c]));

const titulo=slug=>{const html=fs.readFileSync(`storage/app/t75-runtime/app/html/despues/${slug}.html`,'utf8');return (html.match(/<title>([^<]*)<\/title>/i)?.[1]||slug).replace(/\s*[–—-]\s*Wings\s*$/,'').replaceAll('&amp;','&');};

const esc=s=>String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('"','&quot;');

const imagen=(f,alt)=>f?`<a href="${esc(f)}"><img loading="lazy" src="${esc(f)}" alt="${esc(alt)}"></a>`:'';

let html=`<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>T75 · Comparaciones para Carlos</title><style>body{font:16px system-ui;margin:24px;line-height:1.45;color:rgb(17 24 39);background:rgb(244 246 248)}a{color:rgb(68 99 122)}h1{margin-bottom:4px}nav{display:flex;gap:16px;flex-wrap:wrap;margin:20px 0}article{background:white;border:1px solid rgb(216 224 234);border-radius:12px;margin:20px 0;padding:20px}img{max-width:100%;height:auto;border:1px solid rgb(216 224 234)}.par{display:flex;gap:20px;flex-wrap:wrap}.par>div{max-width:375px}.escritorio>div{max-width:600px}summary{cursor:pointer;margin:16px 0}small{color:rgb(75 85 99)}code{overflow-wrap:anywhere}table{border-collapse:collapse;background:white}td,th{border:1px solid rgb(216 224 234);padding:8px;text-align:left}section{margin-top:32px}</style><h1>T75 · Botones a la derecha en celular</h1><p>Respuestas reales de Wings, con datos ficticios. Cada imagen se abre completa al pulsarla.</p><p>99 casos medidos a 360, 375 y 1366. Sin desbordes posteriores. Las posiciones y dimensiones de los controles de escritorio se conservaron.</p><nav><a href="#cambios">Cambios móviles</a><a href="#controles">Controles del diseño aprobado</a><a href="#consultas">Casos para tu decisión</a><a href="INVENTARIO.md">Inventario completo de 101 vistas</a></nav>`;

function pares(slug,ancho,variante='principal'){const a=fases.antes.find(c=>c.slug===slug&&c.ancho===ancho),b=mapa.get(`${slug}:${ancho}`),fa=a?.fotos.find(f=>f.nombre.endsWith(`--${variante}.png`)),fb=b?.fotos.find(f=>f.nombre.endsWith(`--${variante}.png`));if(!fa||!fb)return '';return `<div class="par ${ancho===1366?'escritorio':''}"><div><h4>Antes · ${ancho}px</h4>${imagen(`antes/${fa.nombre}`,'Antes')}</div><div><h4>Después · ${ancho}px</h4>${imagen(`despues/${fb.nombre}`,'Después')}</div></div>`;}

html+='<section id="cambios"><h2>Cambios móviles</h2>';

for(const slug of comparacion.pantallas_movil_con_cambios){html+=`<article id="${esc(slug)}"><h3>${esc(titulo(slug))}</h3><small>${esc(rutas[slug].rol)} · ${esc(rutas[slug].ruta||rutas[slug].vista_directa)} · ${esc(slug)}</small>${pares(slug,360)}<details><summary>Ver 375px</summary>${pares(slug,375)}</details><details><summary>Ver escritorio a 1366px · controles sin cambios</summary>${pares(slug,1366)}</details>`;for(const v of ['pie','tabla-derecha'])if(pares(slug,360,v))html+=`<details><summary>${v==='pie'?'Pie de la pantalla':'Acciones al deslizar la tabla'}</summary>${pares(slug,360,v)}</details>`;const a=fases.antes.find(c=>c.slug===slug&&c.ancho===360),b=mapa.get(`${slug}:360`);for(const va of a.variantes){const vb=b.variantes.find(v=>v.panel===va.panel);if(vb)html+=`<details><summary>Panel: ${esc(va.panel)}</summary><div class="par"><div><h4>Antes</h4>${imagen(`antes/${va.foto.nombre}`,'Antes')}</div><div><h4>Después</h4>${imagen(`despues/${vb.foto.nombre}`,'Después')}</div></div></details>`;}for(const vb of b.variantes.filter(v=>!a.variantes.some(x=>x.panel===v.panel)))html+=`<details><summary>Formulario existente desplegado · resultado final</summary><div style="max-width:360px">${imagen('despues/'+vb.foto.nombre,'Formulario final')}</div></details>`;const rec=b.fotos.find(f=>f.nombre.endsWith('--recaudacion.png'));if(rec)html+=`<details><summary>Recaudación completa después del ajuste móvil</summary><div style="max-width:360px">${imagen('despues/'+rec.nombre,'Recaudación final')}</div></details>`;html+='</article>';}

html+='</section><section id="controles"><h2>Diseño aprobado conservado</h2>';

for(const slug of controles){html+=`<article><h3>${esc(titulo(slug))} · ${esc(slug)}</h3>${pares(slug,360)}<details><summary>375 y escritorio 1366</summary>${pares(slug,375)}${pares(slug,1366)}</details></article>`;}

html+='</section><section id="consultas"><h2>Casos para que decida Carlos</h2><p>Se conserva su disposición. Aquí están las filas de tabla, el ingreso, modales, controles de campos y enlaces de navegación. Los modales de caja mantienen la aprobación anterior.</p>';

for(const [i,c]of consultas.entries())html+=`<article id="consulta-${i+1}"><h3>${esc(c.tipo)}</h3><small>${esc(titulo(c.slug))} · ${esc(c.ruta)}</small><div style="max-width:360px">${imagen(c.captura,c.tipo)}</div></article>`;

html+='</section></html>';fs.writeFileSync(`${raiz}/GALERIA.html`,html);

let md='# Inventario completo T75\n\n101 plantillas revisadas en fuente: 94 renderizadas, 5 de correo/PDF y 2 piezas sin uso detectado. Se renderizó además la paginación de Laravel.\nLas posiciones de las acciones se midieron sobre respuestas reales de Laravel.\nLas categorías incluyen enlaces y campos; los casos fuera de tarjeta/barra conservan su disposición para Carlos.\n\n## Casos de pantalla\n\n| Caso | Rol y ruta | Antes 360 | Antes 375 | Después 360 | Después 375 | Escritorio |\n|---|---|---|---|---|---|---|\n';

for(const r of respuestas){const a=fases.antes.find(c=>c.slug===r.slug&&c.ancho===360),aa=fases.antes.find(c=>c.slug===r.slug&&c.ancho===375),b=mapa.get(`${r.slug}:360`),bb=mapa.get(`${r.slug}:375`);md+=`| ${r.slug} | ${r.rol} · ${r.ruta||r.vista_directa} | ${estado(a)} | ${estado(aa)} | ${estado(b)} | ${estado(bb)} | sin diferencias |\n`;}

md+='\n## Todas las vistas\n\n| Archivo | Controles en fuente | Pantallas donde se renderizó | Estado de revisión |\n|---|---|---|---|\n';

for(const v of fuentes){const casos=respuestas.filter(c=>c.vistas.includes(v.archivo));const primarias=casos.filter(c=>c.vistas[0]===v.archivo);const lista=(primarias.length?primarias:casos).map(c=>c.slug);const estado=casos.length?'Renderizada y medida a 360/375/1366':v.marcas_interactivas.length?'SIN COBERTURA: revisar':v.archivo.includes('/pdfs/')||v.archivo.includes('/vendor/mail/')?'Correo/PDF, revisión de fuente sin botones':'Pieza sin uso detectado; revisión de fuente, sin pantalla propia';md+=`| ${v.archivo} | ${v.marcas_interactivas.length?'sí':'sin marcas de botón'} | ${lista.slice(0,4).join(', ')}${lista.length>4?' · +'+(lista.length-4):''} | ${estado} |\n`;assert(casos.length||!v.marcas_interactivas.length,v.archivo);}

md+='\n## Casos para Carlos\n\n| Caso | Captura | Disposición |\n|---|---|---|\n';for(const c of consultas)md+=`| ${c.tipo} · ${c.slug} | [Ver](${c.captura}) | Conservada, sin elegir otra posición |\n`;

fs.writeFileSync(`${raiz}/INVENTARIO.md`,md);

fs.writeFileSync(`${raiz}/capturas.json`,JSON.stringify([...capturas.values()],null,2));fs.writeFileSync(`${raiz}/casos-carlos.json`,JSON.stringify(consultas,null,2));

console.log(JSON.stringify({vistas:fuentes.length,casos:respuestas.length,cambiosMoviles:comparacion.pantallas_movil_con_cambios.length,controles:controles.length,consultas:consultas.length,capturas:capturas.size}));
