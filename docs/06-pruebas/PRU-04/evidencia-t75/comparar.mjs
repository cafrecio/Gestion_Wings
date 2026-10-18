import fs from 'node:fs';
import assert from 'node:assert/strict';
const base='docs/06-pruebas/PRU-04/evidencia-t75';
const antes=JSON.parse(fs.readFileSync(`${base}/mediciones-antes.json`,'utf8')).casos;
const despues=JSON.parse(fs.readFileSync(`${base}/mediciones-despues.json`,'utf8')).casos;
const mapa=new Map(antes.map(c=>[`${c.slug}:${c.ancho}`,c]));
const controles=c=>new Map(c.zonas.flatMap(z=>z.filas.flatMap(f=>f.controles)).map(b=>[b.ruta,b]));
function comparar(a,b){const aa=controles(a),bb=controles(b),d=[];for(const [ruta,x]of aa){const y=bb.get(ruta);if(!y){d.push({ruta,problema:'control ausente',antes:x.etiqueta});continue;}if(x.etiqueta!==y.etiqueta)d.push({ruta,problema:'texto distinto',antes:x.etiqueta,despues:y.etiqueta});const cambios={};for(const k of ['x','y','w','h'])if(Math.abs(x.rect[k]-y.rect[k])>.51)cambios[k]=[x.rect[k],y.rect[k]];if(Object.keys(cambios).length)d.push({ruta,etiqueta:x.etiqueta,cambios});}for(const [ruta,y]of bb)if(!aa.has(ruta))d.push({ruta,problema:'control agregado',despues:y.etiqueta});return d;}
assert.equal(antes.length,297);assert.equal(despues.length,297);
const comparaciones=despues.map(b=>{const a=mapa.get(`${b.slug}:${b.ancho}`);assert(a);return {slug:b.slug,ancho:b.ancho,diferencias:comparar(a,b),alto_antes:a.altoDocumento,alto_despues:b.altoDocumento,ancho_antes:a.anchoDocumento,ancho_despues:b.anchoDocumento};});
const escritorio=comparaciones.filter(c=>c.ancho===1366&&(c.diferencias.length||Math.abs(c.alto_antes-c.alto_despues)>1));
const desbordes=despues.filter(c=>c.desborde).map(c=>[c.slug,c.ancho,c.anchoDocumento]);
const panelesDesbordados=despues.flatMap(c=>c.variantes.filter(v=>v.medicion.desborde).map(v=>[c.slug,c.ancho,v.panel]));
const regresiones=[];
for(const slug of ['admin-alumnos','admin-grupos','admin-profesores','admin-niveles','admin-caja-cerrada-detalle','operativo-6'])for(const ancho of [360,375,1366]){const a=mapa.get(`${slug}:${ancho}`),b=despues.find(c=>c.slug===slug&&c.ancho===ancho);assert(a&&b,slug);const diferencias=comparar(a,b);regresiones.push({slug,ancho,diferencias});}
const grid=[];
for(const ancho of [360,375]){const c=despues.find(c=>c.slug==='admin-caja-cerrada-detalle'&&c.ancho===ancho),cc=[...controles(c).values()];const buscar=t=>cc.find(b=>b.etiqueta===t).rect;const v=buscar('Validar'),r=buscar('Rechazar'),s=buscar('Resumen'),b=buscar('Volver');assert.equal(v.y,r.y);assert.equal(s.y,b.y);assert(s.y>v.y);assert.equal(v.x,s.x);assert.equal(r.derecha,b.derecha);grid.push({ancho,filas:'2x2',derecha:true});}
const seleccion=[...new Set(comparaciones.filter(c=>c.ancho<640&&c.diferencias.length).map(c=>c.slug))];
const informe={escritorio_diferencias:escritorio,desbordes,paneles_desbordados:panelesDesbordados,regresiones,detalle_caja:grid,pantallas_movil_con_cambios:seleccion,comparaciones};
fs.writeFileSync(`${base}/comparacion.json`,JSON.stringify(informe,null,2));
console.log(JSON.stringify({pantallas:despues.length/3,cambiosMoviles:seleccion.length,escritorioDiferente:escritorio.map(c=>c.slug),desbordes,panelesDesbordados,regresionesConCambios:regresiones.filter(c=>c.diferencias.length).map(c=>`${c.slug}:${c.ancho}`),detalleCaja:grid}));
assert.deepEqual(escritorio,[],'Cambió el escritorio');assert.deepEqual(desbordes,[]);assert.deepEqual(panelesDesbordados,[]);assert(regresiones.every(c=>c.diferencias.length===0),'Cambió una grilla aprobada o Detalle');
