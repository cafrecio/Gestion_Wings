'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const sandbox={location:{href:''}};vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(path.join(__dirname,'dashboard.js'),'utf8'),sandbox);
const {seleccionar,normalizar,montar}=sandbox.WingsCasos;
let comprobaciones=0;
function comprobar(f){f();comprobaciones++;}
const items=Array.from({length:42},(_,i)=>({n:i,texto:i===12?'COB-012 · Pago adelantado':'Caso '+i,estado:i%2?'Parcial':'Sin probar'}));
comprobar(()=>assert.equal(seleccionar(items,'','',1).items.length,10));
comprobar(()=>assert.equal(seleccionar(items,'','',5).items.length,2));
comprobar(()=>assert.equal(seleccionar(items,'','',99).pagina,5));
comprobar(()=>assert.equal(seleccionar(items,'','',-1).pagina,1));
comprobar(()=>assert.equal(seleccionar(items,'','Parcial',1).total,21));
comprobar(()=>assert.equal(seleccionar(items,'COB-012','',1).items[0].n,12));
comprobar(()=>assert.equal(seleccionar(items,'inexistente','',1).total,0));
comprobar(()=>assert.equal(normalizar('MATRÍCULA'),'matricula'));
// Verifica el montaje y los eventos del código entregado, sin afirmar render de navegador.
const filas=items.map(x=>({dataset:{texto:x.texto,estado:x.estado},hidden:false}));
const nodos={};for(const id of ['buscar','estado','anterior','siguiente','conteo','vacio','elegir-jornada']){
  nodos[id]={value:'',eventos:{},addEventListener(tipo,fn){this.eventos[tipo]=fn;}};
}
const doc={querySelector:()=>({querySelectorAll:()=>filas}),getElementById:id=>nodos[id]};
montar(doc);
comprobar(()=>assert.equal(filas.filter(f=>!f.hidden).length,10));
comprobar(()=>assert.equal(nodos.anterior.disabled,true));
nodos.siguiente.eventos.click();
comprobar(()=>assert.equal(nodos.conteo.textContent,'42 casos · página 2 de 5'));
nodos.buscar.value='COB-012';nodos.buscar.eventos.input();
comprobar(()=>assert.equal(filas.filter(f=>!f.hidden).length,1));
comprobar(()=>assert.equal(nodos.conteo.textContent,'1 casos · página 1 de 1'));
nodos.estado.value='Parcial';nodos.estado.eventos.change();
comprobar(()=>assert.equal(filas.filter(f=>!f.hidden).length,0));
comprobar(()=>assert.equal(nodos.vacio.hidden,false));
nodos['elegir-jornada'].value='jornadas/DIA-38.html';nodos['elegir-jornada'].eventos.change();
comprobar(()=>assert.equal(sandbox.location.href,'jornadas/DIA-38.html'));
console.log(JSON.stringify({comprobaciones,resultado:'OK',alcance:'Lógica real de filtros, paginación y eventos; sin render de navegador'}));
