(function(root){
  'use strict';
  const normalizar=s=>String(s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
  function seleccionar(items,q,estado,pagina,tamano=10){
    const filtrados=items.filter(x=>normalizar(x.texto).includes(normalizar(q))&&(!estado||x.estado===estado));
    const paginas=Math.max(1,Math.ceil(filtrados.length/tamano));
    const actual=Math.max(1,Math.min(Number(pagina)||1,paginas));
    return {items:filtrados.slice((actual-1)*tamano,actual*tamano),total:filtrados.length,pagina:actual,paginas};
  }
  function montar(doc){
    const dia=doc.getElementById('elegir-jornada');
    if(dia)dia.addEventListener('change',()=>{if(dia.value)root.location.href=dia.value;});
    const cuerpo=doc.querySelector('[data-casos]');
    if(!cuerpo)return;
    const filas=[...cuerpo.querySelectorAll('[data-caso]')];
    const items=filas.map((r,n)=>({n,texto:r.dataset.texto,estado:r.dataset.estado}));
    const buscar=doc.getElementById('buscar'),estado=doc.getElementById('estado');
    let pagina=1;
    function pintar(){
      const r=seleccionar(items,buscar.value,estado.value,pagina);pagina=r.pagina;
      const visibles=new Set(r.items.map(x=>x.n));
      filas.forEach((f,n)=>{f.hidden=!visibles.has(n);});
      doc.getElementById('conteo').textContent=r.total+' casos · página '+r.pagina+' de '+r.paginas;
      doc.getElementById('anterior').disabled=pagina===1;
      doc.getElementById('siguiente').disabled=pagina===r.paginas;
      doc.getElementById('vacio').hidden=r.total!==0;
    }
    buscar.addEventListener('input',()=>{pagina=1;pintar();});
    estado.addEventListener('change',()=>{pagina=1;pintar();});
    doc.getElementById('anterior').addEventListener('click',()=>{pagina--;pintar();});
    doc.getElementById('siguiente').addEventListener('click',()=>{pagina++;pintar();});
    pintar();
  }
  root.WingsCasos={normalizar,seleccionar,montar};
  if(typeof document!=='undefined')montar(document);
})(globalThis);
