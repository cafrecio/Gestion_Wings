"""Genera el visor desde control-integral.json. Solo escribe CONTROL-INTEGRAL.html."""
import html
import json
import os
from collections import Counter
from pathlib import Path

HERE=Path(__file__).resolve().parent
REPO=HERE.parents[3]
d=json.loads((HERE/'control-integral.json').read_text(encoding='utf-8'))
s=d['resumen']
esc=lambda v:html.escape(str(v))
def link(path,label=None):
    target=os.path.relpath(REPO/path,HERE).replace('\\','/')
    assert (REPO/path).is_file(),path
    return f'<a href="{esc(target)}">{esc(label or Path(path).name)}</a>'
def badge(state):
    color='ok' if state in ['Coincide','Aprobado'] else 'warn' if state in ['Parcial','Observación abierta'] else 'pending'
    return f'<span class="badge {color}">{esc(state)}</span>'
def refs(ids):
    return ' · '.join(f'<a href="#{esc(i)}">{esc(i)}</a>' for i in ids) or 'Sin ejecución'
def row(cells,search,state,ident=''):
    ident_attr=f' id="{esc(ident)}"' if ident else ''
    return f'<tr{ident_attr} data-search="{esc(search.lower())}" data-state="{esc(state)}">'+''.join('<td>'+c+'</td>' for c in cells)+'</tr>'
def table(heads,rows):
    return '<div class="wrap"><table><thead><tr>'+''.join('<th scope="col">'+h+'</th>' for h in heads)+'</tr></thead><tbody>'+''.join(rows)+'</tbody></table></div>'
out=['''<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Wings · Seguimiento integral de pruebas</title>
<style>
body{font:16px/1.5 system-ui,sans-serif;color:#172635;background:#f3f5f7;margin:0}
main{max-width:1400px;margin:auto;padding:24px}h1{font-size:27px;margin:0}h2{font-size:22px;margin-top:32px}
a{color:#174f94}header,.note{padding:20px;background:white;border:1px solid #ccd4dd;border-radius:10px}
.note{margin:16px 0}.stats,nav{display:flex;gap:12px;flex-wrap:wrap;margin:16px 0}
.stats span{background:#e6edf5;border-radius:6px;padding:8px 12px}nav a{padding:4px}
.filters{position:sticky;top:0;z-index:2;background:#f3f5f7;padding:10px 0;display:flex;gap:12px;flex-wrap:wrap}
label{font-weight:650}input,select,button{font:inherit;padding:8px;border:1px solid #718398;border-radius:5px}
input{min-width:260px}.wrap{overflow:auto;background:white;border:1px solid #ccd4dd;border-radius:8px}
table{border-collapse:collapse;width:100%;font-size:14px}th,td{text-align:left;vertical-align:top;padding:12px;border-bottom:1px solid #d9e0e7}
th{background:#e8eef4}td:first-child{min-width:100px;font-weight:650}td{min-width:130px;max-width:620px;overflow-wrap:anywhere}
summary{cursor:pointer;font-weight:650}details{margin:7px 0}p{margin:8px 0}.small{font-size:13px;color:#41566b}
.badge{display:inline-block;padding:3px 8px;border-radius:5px;background:#e5e9ed;white-space:nowrap}
.ok{background:#def0e3;color:#17522a}.warn{background:#fff0cc;color:#6a4900}.pending{background:#e5e9ed;color:#354556}
[hidden]{display:none!important}tr:target{outline:3px solid #b77b12;outline-offset:-3px}li{margin:8px 0}
@media(max-width:650px){main{padding:12px}header,.note{padding:14px}.wrap table{min-width:760px}input{min-width:0;width:100%}.filters{position:static}}
@media print{.filters,nav{display:none}main{padding:0;max-width:none}.wrap{overflow:visible}body{background:white}details{break-inside:avoid}}
</style></head><body><main><header><h1>Seguimiento integral de pruebas Wings</h1>
<p>Actualizado 23/10/2026 · Codex CyE · T14 en curso.</p>
<p><b>Día 4: recorrido humano contrastado. Seguridad: corrida posterior en base local aislada.</b></p>
''']
out.append('<div class="stats">'+''.join('<span>'+esc(t)+'</span>' for t in [
    '16 tareas: 14 coinciden / 2 parciales',
    f"{s['automatizadas_aprobadas']}/{s['automatizadas']} automatizadas aprobadas · {s['aserciones']} aserciones",
    f"{s['casos_con_evidencia_parcial']}/331 casos con evidencia parcial",
    f"{s['casos_sin_evidencia']} casos sin evidencia incorporada",
    '0 casos fuente cerrados integralmente'])+'</div>')
out.append('''<p class="small">No se suman tareas, métodos y casos como si fueran lo mismo. Aprobar una variante no cierra todas las letras, roles y condiciones del caso fuente. «Pendiente» significa sin evidencia incorporada a este seguimiento; no afirma que nadie lo haya probado antes.</p>
<p>Esta consolidación y la corrida del 23/10 esperan revisión independiente. Sin aceptación integral ni despliegue.</p></header>
<nav><a href="#dia4">Día 4</a><a href="#medidas">Mediciones y hallazgos</a><a href="#seguridad">Pruebas técnicas</a><a href="#pendientes">Qué falta</a><a href="#catalogo">331 casos / letras</a><a href="#controles">14 controles</a><a href="#recorridos">39 recorridos</a><a href="LEEME.md">Uso y límites</a></nav>
<div class="filters"><label for="busca">Buscar <input id="busca" type="search" placeholder="D4-T06, COB-012, permiso…"></label>
<label for="estado">Estado <select id="estado"><option value="">Todos</option><option>Pendiente</option><option>Parcial</option><option>Coincide</option><option>Aprobado</option><option>Observación abierta</option><option>Medido</option></select></label><button id="limpiar">Limpiar</button><span id="cuenta" aria-live="polite"></span></div>
''')
def execution(e):
    expected=e.get('esperado_fijado_antes',{})
    obs=e['observado']
    scope=esc(obs.get('resultado',obs.get('medicion',obs.get('hecho',obs.get('prueba','')))))
    limit=esc(obs.get('limites',obs.get('siguiente','')))
    checks=e.get('criterios',[])
    coverage=' · '.join(f'<a href="#caso-{q["caso"]}">{q["caso"]}.{q["letra"]}</a>' for q in checks) or 'Control propio / sin caso fuente completo'
    evidence='<details><summary>Evidencia y contexto</summary><p>'+ '<br>'.join(link(p) for p in e['evidencia'])+'</p><p>'+esc(e.get('entorno',''))+' / '+esc(e.get('base',''))+'</p><p>Versión: '+esc(e.get('commit',''))+'</p><p>Wings: '+esc(e.get('fecha_hora_wings',''))+'</p><p>Real: '+esc(e.get('fecha_hora_real',''))+'</p><p>Hizo: '+esc(e.get('hizo',''))+'<br>Verifica: '+esc(e.get('verifica',''))+'</p></details>'
    if checks:
        evidence+='<details><summary>Alcance por letra</summary>'+''.join('<p><b>'+esc(q['caso']+'.'+q['letra'])+'</b>: '+esc(q['alcance'])+'</p>' for q in checks)+'</details>'
    if e['id'].startswith('SEG-'):
        scope+='<p>'+esc(str(obs.get('aserciones',0))+' aserciones; '+str(obs.get('duracion_s',0))+' s de prueba, no tiempo de pantalla.')+'</p>'
    dims='<p>Funcional: '+esc(e.get('funcional','Pendiente'))+'<br>Datos: '+esc(e.get('datos_y_calculos','Pendiente'))+'<br>Seguridad: '+esc(e.get('permisos_y_seguridad','Pendiente'))+'<br>Visual 1366 / 375: '+esc(e.get('visual_1366','Pendiente'))+' / '+esc(e.get('visual_375','Pendiente'))+'<br>Performance: '+esc(e.get('performance','Pendiente'))+'</p>'
    return row([esc(e['id'])+'<p>'+badge(e['resultado'])+'</p>',esc(e.get('titulo',''))+'<p class="small">'+esc(e.get('rol',''))+'</p><p>'+coverage+'</p>',scope+'<p class="small">'+limit+'</p>',evidence+'<details><summary>Dimensiones medidas / pendientes</summary>'+dims+'</details>'],json.dumps(e,ensure_ascii=False),e['resultado'],e['id'])
out.append('<h2 id="dia4">Día 4 · las 16 situaciones</h2><p>Se conserva el informe humano y su esperado original. No se volvieron a cargar operaciones para completar esta tabla.</p>')
out.append(table(['Tarea / resultado','Qué se probó / caso y letra','Qué ocurrió / qué no acredita','Dónde comprobarlo'],[execution(e) for e in d['ejecuciones'] if e['id'].startswith('D4-T')]))
out.append('<h2 id="medidas">Mediciones y hallazgos</h2>')
out.append(table(['ID / estado','Control','Resultado / implicación','Evidencia'],[execution(e) for e in d['ejecuciones'] if e['id']=='D4-OBS-01' or e['id'].startswith('MED-D4-')]))
out.append('<h2 id="seguridad">Pruebas técnicas dirigidas · 23/10</h2><p>41 métodos existentes en seis archivos, 331 aserciones, salida 0, 43,623 s. Incluyen permisos, validaciones e integridad; no son 41 ataques diferentes. Conexión comprobada: testing / localhost / wings_testing_codex. HTTP interno de Laravel; no navegador ni servidor remoto.</p>')
out.append(table(['Método / resultado','Caso / control','Aserciones / límites','Evidencia'],[execution(e) for e in d['ejecuciones'] if e['id'].startswith('SEG-')]))
out.append('<h2 id="pendientes">Qué falta y cómo seguir</h2><div class="note"><ul>'+''.join('<li>'+esc(p)+'</li>' for p in d['pendientes_prioritarios'])+'</ul></div>')
out.append(f'<h2 id="catalogo">Catálogo · 331 casos, {s["criterios_catalogo"]} criterios</h2><p>{s["criterios_con_evidencia_parcial"]} letras tienen evidencia parcial. Las demás siguen pendientes. Copia fuente inalterada; esta tabla registra su seguimiento por separado.</p>')
rows=[]
for c in d['casos']:
    checks={q['letra']:q for q in c['criterios_seguimiento']}
    body=''.join('<p><b>'+esc(q['l']+' · '+q['donde'])+'</b> '+badge(checks[q['l']]['estado'])+'<br>'+esc(q['texto'])+'<br>'+refs(checks[q['l']]['ejecuciones'])+'</p>' for q in c['esperado_fuente'])
    detail='<details><summary>Condición, acción y cada letra</summary><p><b>Requiere:</b> '+esc(c['requiere_fuente'])+'</p><p><b>Acción:</b> '+esc(c['accion_fuente'])+'</p>'+body+'</details>'
    rows.append(row([esc(c['codigo']),esc(c['titulo'])+detail,badge(c['revision_actual'])+'<p>'+refs(c['ejecuciones'])+'</p>',esc(', '.join(c['roles']))+'<p>'+link(c['fuente'],'Fuente solo lectura')+'</p><p>Fuente: '+esc(c['estado_fuente'])+'</p>'],json.dumps(c,ensure_ascii=False),c['revision_actual'],'caso-'+c['codigo']))
out.append(table(['Código','Caso y criterios originales','Seguimiento actual','Roles / fuente'],rows))
out.append('<h2 id="controles">14 controles transversales</h2>')
out.append(table(['Control','Qué exige','Estado / ejecuciones'],[row([esc(c['id'])+'<p>'+esc(c['dimension'])+'</p>',esc(c['criterio']),badge(c['estado'])+'<p>'+refs(c['ejecuciones'])+'</p>'],json.dumps(c,ensure_ascii=False),c['estado']) for c in d['controles']]))
out.append('<h2 id="recorridos">39 recorridos · plan, no resultado integral</h2>')
out.append(table(['ID','Recorrido / efectos a comprobar','Estado / ejecuciones'],[row([esc(r['id']),'<b>'+esc(r['titulo'])+'</b><p>'+esc(r['accion_y_esperado'])+'</p><details><summary>Preparación e impactos</summary>'+''.join('<p><b>'+esc(k)+':</b> '+esc(r.get(k,''))+'</p>' for k in ['roles','previo','pantallas','tablas_a_controlar','codigo_orientativo','atencion'])+'</details>',badge(r['estado'])+'<p>'+refs(r['ejecuciones'])+'</p>'],json.dumps(r,ensure_ascii=False),r['estado']) for r in d['recorridos']]))
out.append('''<footer><p>Fuente de seguimiento: <a href="control-integral.json">JSON</a> · Generación reproducible: <a href="generar.py">generar.py</a>. La tabla de Carlos/Claude se mantiene solo lectura.</p></footer></main>
<script>
const rows=[...document.querySelectorAll('tr[data-search]')];
const input=document.querySelector('#busca'), select=document.querySelector('#estado');
function filter(){let n=0;const q=input.value.toLocaleLowerCase('es').trim();for(const r of rows){r.hidden=!(r.dataset.search.includes(q)&&(!select.value||r.dataset.state===select.value));if(!r.hidden)n++;}document.querySelector('#cuenta').textContent=n+' / '+rows.length+' filas visibles';}
input.addEventListener('input',filter);select.addEventListener('change',filter);document.querySelector('#limpiar').addEventListener('click',()=>{input.value='';select.value='';filter();});
function showTarget(){const target=document.getElementById(location.hash.slice(1));if(target?.matches('tr[data-search]')){input.value='';select.value='';filter();target.scrollIntoView();}}
window.addEventListener('hashchange',showTarget);filter();showTarget();
</script></body></html>
''')
(HERE/'CONTROL-INTEGRAL.html').write_text('\n'.join(out),encoding='utf-8')
print('Generado:',HERE/'CONTROL-INTEGRAL.html')
