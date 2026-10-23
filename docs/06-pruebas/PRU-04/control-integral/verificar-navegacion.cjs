// Aceptación de navegación/paginación: ejecuta la lógica real sin navegador ni red.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const sandbox = { module: { exports: {} } };
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'informe-humano.js'),'utf8'),sandbox);
const logic = Object.fromEntries(['destinoDia','seleccionar'].map(name=>[name,(...args)=>JSON.parse(JSON.stringify(sandbox.module.exports[name](...args)))]));
const model = JSON.parse(fs.readFileSync(path.join(__dirname,'devolucion-humana.json'),'utf8'));
const data = JSON.parse(fs.readFileSync(path.join(__dirname,'control-integral.json'),'utf8'));
const manifest = JSON.parse(fs.readFileSync(path.join(__dirname,'jornadas.json'),'utf8'));
const routes = Object.fromEntries(manifest.jornadas.map(j=>[String(j.dia),j.archivo]));
let checks = 0;
for(let day=1;day<=60;day++) {
  const result=logic.destinoDia(String(day),routes);
  if(routes[day]) assert.equal(result.url,routes[day]);
  else assert.deepEqual(result,{sinInforme:day});
  checks++;
}
assert.deepEqual(logic.destinoDia('38',routes),{url:'jornadas/DIA-38.html'});
assert.match(fs.readFileSync(path.join(__dirname,'jornadas/DIA-38.html'),'utf8'),/Sin resultados consolidados por caso/); checks++;
for(const input of ['0','61','3.5','','texto']) { assert.ok(logic.destinoDia(input,routes).error); checks++; }
const states=Object.fromEntries(data.ejecuciones.map(e=>[e.id,e.resultado]));
const items=model.jornadas[0].situaciones.map(s=>({id:s.numero,text:Object.values(s).join(' '),state:states[s.referencia]}));
const visited=[];
for(let p=1;p<=4;p++) {
  const result=logic.seleccionar(items,'','',p,4);
  assert.equal(result.ids.length,4); assert.equal(result.pages,4); visited.push(...result.ids); checks++;
}
assert.deepEqual(visited,[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16]); checks++;
assert.deepEqual(logic.seleccionar(items,'','Parcial',1,4).ids,[2,6]); checks++;
assert.deepEqual(logic.seleccionar(items,'HERNAN','',1,4).ids,[12]); checks++;
assert.equal(logic.seleccionar(items,'no hay coincidencia 123','',1,4).total,0); checks++;
assert.equal(logic.seleccionar(items,'','',999,4).current,4); checks++;
assert.equal(logic.seleccionar(items,'','',0,4).current,1); checks++;
console.log(JSON.stringify({controles:checks,dias:60,dia38:'Acceso directo a página sin resultados consolidados; fecha del calendario ficticio identificada',paginacion:'Anexo de 16 situaciones, cuatro por página, sin pérdida ni duplicación',filtros:'Parciales, búsqueda sin acentos, vacío y extremos aprobados',alcance:'Lógica real de navegación; no render ni prueba en navegador'}));
