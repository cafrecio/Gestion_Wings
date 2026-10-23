"""Resumen liviano y dos detalles opcionales. Funciona también con file://."""
import html
import json
import os
import sys
sys.dont_write_bytecode=True
from pathlib import Path
from salida import actualizar

HERE=Path(__file__).resolve().parent
REPO=HERE.parents[3]
PATH=HERE/'control-integral.json'
d=actualizar(json.loads(PATH.read_text(encoding='utf-8')))
PATH.write_text(json.dumps(d,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
s=d['resumen']; prod=d['produccion']
MAIN='CONTROL-INTEGRAL.html'; CAT='CATALOGO.html'; TECH='DETALLE-TECNICO.html'
esc=lambda x:html.escape(str(x))
def link(path,label=None):
    assert (REPO/path).is_file(),path
    target=os.path.relpath(REPO/path,HERE).replace('\\','/')
    return f'<a href="{esc(target)}">{esc(label or Path(path).name)}</a>'
def badge(state):
    tone='ok' if state in ['Coincide','Aprobado','Verificada'] else 'warn' if state in ['Parcial','Observación abierta','Dependencia pendiente'] else ''
    return f'<span class="badge {tone}">{esc(state)}</span>'
def refs(ids):
    return ' · '.join(f'<a href="{MAIN if i.startswith("D4-T") else TECH}#{esc(i)}">{esc(i)}</a>' for i in ids) or 'Sin ejecución incorporada'
def case_link(code):return f'<a href="{CAT}#caso-{esc(code)}">{esc(code)}</a>'
def row(cells,search,state,ident='',scope=''):
    ident=f' id="{esc(ident)}"' if ident else ''
    return f'<tr{ident} data-search="{esc(search.lower())}" data-state="{esc(state)}" data-scope="{esc(scope)}">'+''.join('<td>'+cell+'</td>' for cell in cells)+'</tr>'
def table(heads,rows):
    return '<div class="wrap"><table><thead><tr>'+''.join('<th scope="col">'+h+'</th>' for h in heads)+'</tr></thead><tbody>'+''.join(rows)+'</tbody></table></div>'
CSS='''body{font:16px/1.5 system-ui,sans-serif;color:#172635;background:#f3f5f7;margin:0}main{max-width:1380px;margin:auto;padding:24px}h1{font-size:27px;margin:0}h2{font-size:22px;margin:28px 0 10px}h3{font-size:18px;margin:0 0 8px}a{color:#174f94}header,.note,.card{padding:18px;background:white;border:1px solid #ccd4dd;border-radius:9px}.columns{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:14px 0}.card ul{padding-left:19px;margin:0}.card li{margin:7px 0;font-size:14px}.stats,nav{display:flex;flex-wrap:wrap;gap:10px;margin:14px 0}.stats span{background:#e6edf5;padding:6px 10px;border-radius:5px}.small{font-size:13px;color:#41566b}.filters{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:16px 0}input,select,button{font:inherit;border:1px solid #718398;border-radius:5px;padding:7px}input{min-width:220px}.wrap{overflow:auto;background:white;border:1px solid #ccd4dd;border-radius:8px}table{border-collapse:collapse;width:100%;font-size:14px}td,th{text-align:left;vertical-align:top;padding:10px;border-bottom:1px solid #d9e0e7}th{background:#e8eef4}td{overflow-wrap:anywhere;min-width:110px}p{margin:7px 0}details{margin:7px 0}summary{cursor:pointer;font-weight:650}.badge{display:inline-block;padding:2px 7px;border-radius:4px;background:#e5e9ed;color:#354556}.ok{background:#def0e3;color:#17522a}.warn{background:#fff0cc;color:#6a4900}[hidden]{display:none!important}tr:target{outline:3px solid #b77b12;outline-offset:-3px}footer{margin-top:25px}@media(max-width:650px){main{padding:12px}.columns{grid-template-columns:1fr}h1{font-size:23px}header,.card{padding:13px}.wrap table{min-width:700px}.filters{display:grid}input{min-width:0;width:100%;box-sizing:border-box}}@media print{nav,.filters{display:none}main{max-width:none;padding:0}body{background:white}.wrap{overflow:visible}}'''
JS='''const rows=[...document.querySelectorAll('tr[data-search]')];const input=document.querySelector('#busca'),state=document.querySelector('#estado'),scope=document.querySelector('#alcance');function filter(){let n=0;const q=input.value.toLocaleLowerCase('es').trim();for(const r of rows){r.hidden=!(r.dataset.search.includes(q)&&(!state.value||r.dataset.state===state.value)&&(!scope.value||r.dataset.scope===scope.value));if(!r.hidden)n++;}document.querySelector('#cuenta').textContent=n+' / '+rows.length+' filas visibles';}for(const c of [input,state,scope])c.addEventListener(c===input?'input':'change',filter);document.querySelector('#limpiar').addEventListener('click',()=>{input.value='';state.value='';scope.value='';filter();});function showTarget(){const target=document.getElementById(location.hash.slice(1));if(target?.matches('tr[data-search]')){input.value='';state.value='';scope.value='';filter();target.scrollIntoView();}}window.addEventListener('hashchange',showTarget);filter();showTarget();'''
def filters():
    states=['Pendiente','Parcial','Coincide','Aprobado','Verificada','Dependencia pendiente','Observación abierta','Medido']
    return '<div class="filters"><label for="busca">Buscar</label><input id="busca" type="search" placeholder="Caso, tarea, rol o tema"><label for="estado">Estado</label><select id="estado"><option value="">Todos</option>'+''.join('<option>'+x+'</option>' for x in states)+'</select><label for="alcance">Alcance</label><select id="alcance"><option value="">Todos</option><option value="salida">Salida</option><option value="ampliacion">Ampliación</option></select><button id="limpiar">Limpiar</button><span id="cuenta" aria-live="polite"></span></div>'
def page(name,title,parts):
    text='<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'+esc(title)+'</title><style>'+CSS+'</style></head><body><main>'+''.join(parts)+'<footer><p>'+link('docs/06-pruebas/PRU-04/control-integral/LEEME.md','Uso y límites')+' · '+link('docs/06-pruebas/PRU-04/control-integral/control-integral.json','Datos únicos')+'</p></footer></main><script>'+JS+'</script></body></html>'
    (HERE/name).write_text(text,encoding='utf-8')
    return len(text.encode('utf-8'))
NAV=f'<nav><a href="{MAIN}">Resumen y salida</a><a href="{CAT}">Catálogo completo</a><a href="{TECH}">Detalle técnico</a></nav>'
def execution(e):
    obs=e['observado']; checks=e.get('criterios',[])
    coverage=' · '.join(case_link(c) for c in dict.fromkeys(q['caso'] for q in checks)) or 'Control propio'
    text=esc(obs.get('resultado',obs.get('medicion',obs.get('hecho',obs.get('prueba','')))))
    if 'aserciones' in obs:text+='<p>'+esc(str(obs['aserciones'])+' aserciones; '+str(obs['duracion_s'])+' s de prueba, no tiempo de pantalla.')+'</p>'
    text+='<p class="small">'+esc(obs.get('limites',obs.get('siguiente','')))+'</p>'
    context='<details><summary>Evidencia y revisión</summary><p>'+'<br>'.join(link(p) for p in e['evidencia'])+'</p><p>Ejecutó: '+esc(e.get('hizo',''))+'</p>'
    if e.get('contraste_tecnico_por'):context+='<p>Contraste técnico: '+esc(e['contraste_tecnico_por'])+' (mismo agente; no es revisión independiente).</p>'
    rev=e['revision_independiente']
    context+='<p><b>Revisión independiente: '+esc(rev['estado'])+'</b>'+(' · '+esc(rev['identidad']) if rev.get('identidad') else ' · sin revisor asignado')+'</p>'
    context+='<p>'+esc(e.get('entorno',''))+' / '+esc(e.get('base',''))+'<br>Versión '+esc(e.get('commit',''))+'<br>Wings: '+esc(e.get('fecha_hora_wings',''))+'<br>Real: '+esc(e.get('fecha_hora_real',''))+'</p></details>'
    context+='<details><summary>Dimensiones / alcance por letra</summary><p>'+ '<br>'.join(esc(k)+': '+esc(e.get(k,'Pendiente')) for k in ['funcional','datos_y_calculos','permisos_y_seguridad','visual_1366','visual_375','performance'])+'</p>'+''.join('<p>'+case_link(q['caso'])+'.'+esc(q['letra'])+': '+esc(q['alcance'])+'</p>' for q in checks)+'</details>'
    search=' '.join([e['id'],e.get('titulo',''),e.get('rol',''),e.get('caso',''),str(obs)])
    return row([esc(e['id'])+'<p>'+badge(e['resultado'])+'</p>',esc(e.get('titulo',''))+'<p>'+esc(e.get('rol',''))+'</p><p>'+coverage+'</p>',text,context],search,e['resultado'],e['id'],'salida')

main=['<header><h1>Wings · ¿Podemos salir a producción?</h1><p><b>'+esc(prod['estado'])+'</b> · corte documental 23/10/2026</p><p class="small">'+esc(prod['alcance'])+'</p><div class="columns">']
for title,key in [('Listo','listo'),('Frena','frena'),('Falta para producción','falta')]:
    main.append('<section class="card"><h3>'+title+'</h3><ul>'+''.join('<li>'+esc(x)+'</li>' for x in prod[key])+'</ul></section>')
main.append('</div><p class="small">Listo = ensayos entregados, con revisión independiente pendiente. No implica autorización para publicar. Los requisitos abiertos salen del tablero; no se presentan como bugs reproducidos hoy.</p></header>')
main.append('<div class="stats">'+''.join('<span>'+esc(x)+'</span>' for x in [f"Salida: {prod['casos_necesarios']} casos seleccionados",f"{prod['con_evidencia_parcial']} con evidencia parcial",f"{prod['con_firma_independiente']} con firma independiente"] )+'</div>')
main.append(NAV+filters())
main.append('<h2 id="salida">Casos necesarios para la salida</h2><p>Camino feliz, protecciones mínimas y requisitos explícitos de Carlos. Los grupos pueden superponerse; cada código se cuenta una sola vez. La selección espera revisión y no recorta criterios fuente.</p>')
rows=[]
for c in d['casos']:
    p=c['produccion']
    if not p['necesario']:continue
    state='Dependencia pendiente' if p['dependencias_abiertas'] else 'Parcial' if c['ejecuciones'] else 'Pendiente'
    rows.append(row([case_link(c['codigo']),esc(c['titulo'])+'<p class="small">'+esc(' · '.join(p['grupos']))+'</p>',badge(state)+'<p>'+refs(c['ejecuciones'])+'</p>',esc(', '.join(p['dependencias_abiertas']))+'<p>Revisión independiente: '+esc(p['firma_independiente'])+'</p>'],' '.join([c['codigo'],c['titulo'],*c['roles'],*p['grupos'],*p['tareas']]),state,'salida-'+c['codigo'],'salida'))
main.append(table(['Caso','Objetivo / por qué entra','Evidencia actual','Dependencia / firma'],rows))
main.append('<h2 id="requisitos">Requisitos previos abiertos del tablero</h2><p>Fuente: '+link('docs/00-estado/tareas.json','tareas.json')+'. Se conserva la prioridad decidida por Carlos; se reevalúa al generar el visor.</p>')
main.append(table(['Tarea','Qué falta','Estado / responsable'],[row([esc(t['id']),esc(t['titulo']),esc(t['estado'])+' / '+esc(t['responsable'])],t['id']+' '+t['titulo'],'Dependencia pendiente',scope='salida') for t in prod['tareas_previas_abiertas']]))
main.append('<h2 id="dia4">Día 4 · resultados entregados</h2><p>16 situaciones: 14 coinciden, T2/T6 parciales. El contraste técnico de Codex queda conservado; la firma de otro agente sigue pendiente.</p>')
main.append(table(['Tarea / resultado','Qué se probó','Qué ocurrió / límites','Evidencia / revisión'],[execution(e) for e in d['ejecuciones'] if e['id'].startswith('D4-T')]))
main.append(f'<h2 id="seguridad">Seguridad, mediciones y pendientes</h2><p>41 métodos técnicos aprobados / 331 aserciones, en base local aislada. Una medición CLI de reporte: 35,83 ms y 29 SQL; no demuestra carga. <a href="{TECH}#seguridad">Abrir evidencia por método</a>.</p><p id="medidas"><a href="{TECH}#medidas">Medición y D4-OBS-01</a></p><p id="pendientes">Faltan otras acciones por rol, CSRF, sesiones, concurrencia, volumen y procesos del cambio de mes.</p><p id="catalogo"><a href="{CAT}">Catálogo completo: 331 referencias</a>. La ampliación queda separada del avance de salida; sin evidencia no significa roto.</p><p id="controles"><a href="{TECH}#controles">14 controles transversales</a></p><p id="recorridos"><a href="{TECH}#recorridos">39 recorridos propuestos</a></p>')

cat=['<header><h1>Wings · Catálogo completo</h1><p>Referencia de solo lectura: 331 casos, 1.438 letras. Las marcas de salida y revisión viven en el seguimiento; no cambian la tabla Carlos/Claude.</p></header>',NAV,filters()]
rows=[]
for c in d['casos']:
    checks={q['letra']:q for q in c['criterios_seguimiento']}
    detail='<details><summary>Condición, acción y cada letra</summary><p>Requiere: '+esc(c['requiere_fuente'])+'</p><p>Acción: '+esc(c['accion_fuente'])+'</p>'+''.join('<p><b>'+esc(q['l']+' · '+q['donde'])+'</b> '+badge(checks[q['l']]['estado'])+'<br>'+esc(q['texto'])+'<br>'+refs(checks[q['l']]['ejecuciones'])+'</p>' for q in c['esperado_fuente'])+'</details>'
    mark='Salida' if c['produccion']['necesario'] else 'Ampliación'
    rows.append(row([esc(c['codigo']),esc(c['titulo'])+detail,badge(c['revision_actual'])+'<p>'+refs(c['ejecuciones'])+'</p>',esc(mark)+'<p>'+esc(', '.join(c['roles']))+'</p>'+link(c['fuente'],'Fuente solo lectura')],c['codigo']+' '+c['titulo']+' '+c['modulo']+' '+', '.join(c['roles'])+' '+c['requiere_fuente']+' '+c['accion_fuente'],c['revision_actual'],'caso-'+c['codigo'],'salida' if mark=='Salida' else 'ampliacion'))
cat.append(table(['Código','Caso original','Seguimiento','Alcance / fuente'],rows))
tech=['<header><h1>Wings · Detalle técnico y evidencia</h1><p>Resultados declarados por Codex; revisión independiente pendiente.</p></header>',NAV,filters()]
tech.append('<h2 id="medidas">Mediciones y hallazgos</h2>')
tech.append(table(['ID','Control','Resultado / límites','Evidencia'],[execution(e) for e in d['ejecuciones'] if e['id']=='D4-OBS-01' or e['id'].startswith('MED-D4-')]))
tech.append('<h2 id="seguridad">Corrida dirigida del 23/10</h2><p>41 métodos en seis archivos, 331 aserciones, salida 0, 43,623 s. Incluyen permisos, validaciones e integridad; no son 41 ataques distintos. HTTP interno de Laravel y servicios, sin certificar CSRF real ni configuración remota.</p>')
tech.append(table(['Método / resultado','Caso / control','Aserciones / límites','Evidencia / revisión'],[execution(e) for e in d['ejecuciones'] if e['id'].startswith('SEG-')]))
tech.append('<h2 id="controles">14 controles transversales</h2>')
tech.append(table(['Control','Qué exige','Estado / ejecuciones'],[row([esc(c['id'])+'<p>'+esc(c['dimension'])+'</p>',esc(c['criterio']),badge(c['estado'])+'<p>'+refs(c['ejecuciones'])+'</p>'],c['id']+' '+c['dimension']+' '+c['criterio'],c['estado'],scope='salida') for c in d['controles']]))
tech.append('<h2 id="recorridos">39 recorridos · plan de trabajo</h2>')
tech.append(table(['ID','Recorrido / impactos','Estado / ejecuciones'],[row([esc(r['id']),esc(r['titulo'])+'<p>'+esc(r['accion_y_esperado'])+'</p><details><summary>Preparación e impactos</summary>'+''.join('<p>'+esc(k)+': '+esc(r.get(k,''))+'</p>' for k in ['roles','previo','pantallas','tablas_a_controlar','codigo_orientativo','atencion'])+'</details>',badge(r['estado'])+'<p>'+refs(r['ejecuciones'])+'</p>'],r['id']+' '+r['titulo']+' '+r['roles']+' '+r['tablas_a_controlar'],r['estado'],scope='salida') for r in d['recorridos']]))
sizes={name:page(name,title,parts) for name,title,parts in [(MAIN,'Wings · Resumen de salida',main),(CAT,'Wings · Catálogo completo',cat),(TECH,'Wings · Detalle técnico',tech)]}
print(json.dumps({'bytes':sizes,'salida':prod['casos_necesarios'],'con_evidencia_parcial':prod['con_evidencia_parcial'],'tareas_previas_abiertas':len(prod['tareas_previas_abiertas'])}))
