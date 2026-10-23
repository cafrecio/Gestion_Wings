"""HTML estático pequeño por módulo, caso y jornada; funciona sin servidor ni fetch."""
from pathlib import Path
from collections import Counter
import html
import json
import os
from seguimiento_casos import construir

HERE = Path(__file__).resolve().parent
REPO = HERE.parents[3]
E = lambda s: html.escape(str(s))
CLASES = {'Plan de acción': 'Plan', 'Atención': 'Atencion',
          'Sin consolidar': 'Pendiente', 'Sin probar': 'Pendiente'}


def generar_dashboard(d):
    m = construir(d)
    (HERE/'seguimiento-casos.json').write_text(json.dumps(m, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
    archivos = []
    casos = {c['codigo']: c for c in m['casos']}
    modulos = {x['nombre']: x for x in m['modulos']}
    def ruta(origin, target):
        return os.path.relpath(HERE/target, (HERE/origin).parent).replace('\\', '/')
    def a(origin, target, label):
        return '<a href="'+E(ruta(origin,target))+'">'+E(label)+'</a>'
    def evidencia(origin, paths):
        return ' · '.join(a(origin, os.path.relpath(REPO/p,HERE), Path(p).name) for p in paths)
    def tag(s):
        color = CLASES.get(s,s)
        return '<span class="tag '+E(color)+'">'+E(s)+'</span>'
    def tabla(cabeceras, filas, body=''):
        return '<div class="wrap"><table><thead><tr>'+''.join('<th scope="col">'+E(x)+'</th>' for x in cabeceras)+'</tr></thead><tbody '+body+'>'+''.join(filas)+'</tbody></table></div>'
    def tr(celdas, attrs=''):
        return '<tr '+attrs+'>'+''.join('<td>'+x+'</td>' for x in celdas)+'</tr>'
    def cifras(pares):
        return '<div class="metrics">'+''.join('<div class="metric"><b>'+E(n)+'</b>'+E(t)+'</div>' for n,t in pares)+'</div>'
    def pagina(path, title, body, timeline=False):
        nav='<nav>'+a(path,'CONTROL-INTEGRAL.html','Estado general')+' · '+a(path,'jornadas/DIA-04.html','Día 4')+' · '+a(path,'informes-ia/SEGUIMIENTO-CASOS.md','Informe para IA')+'</nav>'
        linea=''
        if timeline:
            linea='<section class="panel" aria-label="Línea de tiempo"><b>Jornadas · acceso directo</b><p><label for="elegir-jornada">Ir al día </label><select id="elegir-jornada"><option value="">Elegir jornada</option>'+''.join('<option value="'+E(ruta(path,dia['archivo']))+'">Día '+str(dia['dia'])+' · '+E(dia['estado'])+'</option>' for dia in m['dias'])+'</select></p><div class="timeline">'
            for dia in m['dias']:
                color=CLASES.get(dia['estado'],dia['estado'])
                fecha=dia['fecha_club'][8:10]+'/'+dia['fecha_club'][5:7]
                linea+='<a class="day '+E(color)+'" href="'+E(ruta(path,dia['archivo']))+'" title="'+E(dia['estado'])+'"'+(' aria-current="date"' if dia['dia']==4 else '')+'><b>'+str(dia['dia'])+'</b><small>'+fecha+'</small><small>'+E(dia['estado'])+'</small></a>'
            linea+='</div><ul class="legend" aria-label="Colores de los estados">'+''.join('<li class="legend-item '+clase+'">'+E(texto)+'</li>' for clase,texto in [('OK','✓ Pasó'),('Atencion','! Requiere atención'),('Plan','→ Plan definido'),('Reparado','✓ Reparación probada'),('Pendiente','— Sin consolidar')])+'</ul><p class="meta">Los mismos colores identifican los días y los estados de cada caso. El color no sustituye la revisión independiente.</p></section>'
        text='<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'+E(title)+'</title><link rel="stylesheet" href="'+E(ruta(path,'dashboard.css'))+'"><script defer src="'+E(ruta(path,'dashboard.js'))+'"></script></head><body><main>'+nav+linea+body+'<footer>Corte documental: 23/10/2026. '+E(m['alcance'])+'<p>Revisión independiente pendiente. '+a(path,'LEEME.md','Criterios y límites')+' · '+a(path,'seguimiento-casos.json','Datos del seguimiento')+'</p></footer></main></body></html>'
        target=HERE/path;target.parent.mkdir(parents=True,exist_ok=True);target.write_text(text,encoding='utf-8');archivos.append(path)
    def enlace_caso(path,c):
        return a(path,'casos/'+c['codigo']+'.html',c['codigo']+' · '+c['titulo'])
    def filtros():
        return '<div class="filters"><label for="buscar">Buscar caso</label><input id="buscar" type="search" placeholder="Código, nombre o pantalla"><label for="estado">Estado</label><select id="estado"><option value="">Todos</option>'+''.join('<option>'+x+'</option>' for x in ['Sin probar','Parcial','Plan de acción','Fallo','OK','Reparado'])+'</select></div>'
    def pager():
        return '<p id="vacio" class="empty" hidden>No hay casos con ese filtro.</p><div class="pager"><button id="anterior" type="button">Anterior</button><span id="conteo" aria-live="polite"></span><button id="siguiente" type="button">Siguiente</button></div>'
    def agenda(c):
        textos=['Día '+', '.join(map(str,p['dias']))+': '+p['metodo'] for p in c['programacion']]
        if c['plan']:textos.insert(0,'Día '+str(c['plan']['diagnostico_dia_propuesto'])+': diagnóstico propuesto; retest sin fecha hasta implementar.')
        return textos or ['Sin programar. No hay una jornada asignada para completar el caso.']
    def fila_caso(path,c):
        nums=c['resultados']
        detalle='<details><summary>Método y esperado</summary><p><b>Cómo:</b> '+E(c['metodo'])+'</p>'+''.join('<p><b>'+E(q['l']+' · '+q['donde'])+':</b> '+E(q['texto'])+'</p>' for q in c['esperado'])+'</details>'
        return tr([enlace_caso(path,c)+detalle,tag(c['estado']),str(c['cantidad_pruebas']),
                   f"{nums['OK']} / {nums['Fallo']} / {nums['Reparado']} / {nums['Parcial']}",
                   '<br>'.join(E(t) for t in agenda(c))],
                  'data-caso="'+E(c['codigo'])+'" data-texto="'+E(c['codigo']+' '+c['titulo']+' '+c['metodo'])+'" data-estado="'+E(c['estado'])+'"')
    def metas(path, filas):
        if not filas:return '<p>No hay una medición numérica comparable registrada para este alcance. El resultado esperado funcional se detalla por criterio.</p>'
        return tabla(['Indicador / ejecución','Óptimo o esperado','Obtenido','Comparación / alcance'],[
            tr([E(x['indicador'])+'<p>'+E(x['ejecucion'])+'</p>',E(x['optimo']),E(x['resultado']),
                ('Coincide en este indicador' if x['optimo']==x['resultado'] else 'Difiere')+'<p class="meta">'+E(x['origen'])+'</p>']) for x in filas])+'<p class="meta">Una comparación numérica correcta no aprueba los demás criterios del caso. Los invariantes posteriores se identifican como tales; no son una base inicial reconstruida.</p>'
    def plan_html(p):
        return '<article class="panel"><h3>'+E(p['id']+' · '+p['naturaleza'])+'</h3>'+tag('Plan de acción')+'<p><b>Detectado:</b> día '+str(p['dia_detectado'])+' · plan '+E(p['fecha_plan'])+'</p><p><b>Qué pasó:</b> '+E(p['problema'])+'</p><p><b>Impacto:</b> '+E(p['impacto'])+'</p><p><b>Solución propuesta:</b> '+E(p['solucion_propuesta'])+'</p><ol>'+''.join('<li>'+E(s)+'</li>' for s in p['pasos'])+'</ol><p><b>Debe cumplir:</b> '+E(p['aceptacion'])+'</p><p><b>Responsable:</b> '+E(p['responsable'])+'</p><p><b>Implementación:</b> pendiente · <b>Versión:</b> sin commit de solución · <b>Retest:</b> sin ejecución, fecha ni probador asignados.</p></article>'
    s=m['resumen'];path='CONTROL-INTEGRAL.html'
    body='<header><h1>Estado general de las pruebas</h1><p><b>No hay aprobación de producción.</b> El Día 4 tiene evidencia parcial, tres planes definidos y revisión independiente pendiente.</p><p>Se cuentan casos del catálogo y ejecuciones en <b>test.gestionar-te.com.ar / wingstest</b>. Los ensayos aislados se muestran como apoyo.</p></header>'
    body+=cifras([(s['casos'],'casos del catálogo'),(s['probados'],'con pruebas parciales en test'),(s['sin_probar'],'sin prueba consolidada'),(s['OK'],'casos completos OK'),(s['planes'],'planes definidos'),(s['Reparado'],'reparados y probados')])
    body+='<section class="panel"><b>Avance de la selección para producción:</b> '+str(s['salida_probados'])+' de '+str(s['salida_seleccionados'])+' casos tienen prueba parcial en test; 0 aprobados completos. Esta selección de trabajo aún requiere revisión.<p>'+str(s['intentos'])+' vínculos caso–ejecución, provenientes de '+str(s['operaciones'])+' operaciones. Una operación puede comprobar varios casos. '+str(s['operaciones_sin_caso'])+' operación espera vinculación al catálogo.</p><p><b>Cómo leerlo:</b> «con prueba» significa que se comprobó una parte; «OK» exige el caso completo. «Sin prueba consolidada» no significa que el sistema falle.</p></section>'
    body+='<h2>Casos por módulo</h2><p>OK, Fallo y Reparado describen el estado completo del caso. «Parcial» indica evidencia incompleta y puede tener un plan de acción.</p>'
    body+=tabla(['Módulo','Total','Con prueba','Sin prueba','OK','Fallo','Reparado','Parciales','Con plan'],[
        tr([a(path,'modulos/'+x['id']+'.html',x['nombre'])]+[str(x[k]) for k in ['total','probados','sin_probar','OK','Fallo','Reparado','parciales','planes']]) for x in m['modulos']])
    body+='<h2>Qué requiere atención</h2>'+''.join('<p>'+enlace_caso(path,c)+' · '+E(c['plan']['naturaleza'])+'</p>' for c in m['casos'] if c['plan'])
    body+='<details class="panel"><summary>'+str(len(d['produccion']['tareas_previas_abiertas']))+' requisitos previos de producción siguen abiertos en el tablero</summary><p>Son compromisos previos; no fallas nuevas detectadas en el Día 4.</p><ul>'+''.join('<li><b>'+E(t['id']+' · '+t['titulo'])+'</b> · '+E(t['estado']+' / '+t['responsable'])+'</li>' for t in d['produccion']['tareas_previas_abiertas'])+'</ul>'+evidencia(path,['docs/00-estado/TABLERO.html'])+'</details>'
    body+='<section class="panel"><h2>Qué falta medir</h2><p>Seguridad negativa en el sitio de test, tiempos visibles, volumen y concurrencia, ciclo mensual y liquidaciones. Los 41 métodos locales aprobados no completan estos controles en test.</p><p>'+a(path,'jornadas/DIA-04.html#tecnico','Ver pruebas técnicas y sus límites por caso')+'</p></section>'
    pagina(path,'Wings · Estado de las pruebas por caso',body,True)
    for modulo in m['modulos']:
        path='modulos/'+modulo['id']+'.html';grupo=[casos[code] for code in modulo['casos']]
        body='<header><h1>'+E(modulo['nombre'])+'</h1><p>'+str(modulo['total'])+' casos · '+str(modulo['probados'])+' con evidencia parcial en test · '+str(modulo['sin_probar'])+' sin prueba consolidada.</p><p>Diez casos por página. Abrí un caso para ver método, esperado, historia, medidas y solución.</p></header>'+filtros()
        body+=tabla(['Caso','Estado actual','Intentos en test','OK / Fallo / Reparado / Parcial','Próxima comprobación propuesta'],[fila_caso(path,c) for c in grupo],'data-casos')+pager()
        pagina(path,'Wings · '+modulo['nombre'],body)
    for c in m['casos']:
        path='casos/'+c['codigo']+'.html';mod=modulos[c['modulo']]
        body='<header><p>'+a(path,'modulos/'+mod['id']+'.html',c['modulo'])+'</p><h1>'+E(c['codigo']+' · '+c['titulo'])+'</h1>'+tag(c['estado'])+'<p>'+('Necesario en la selección de salida' if c['necesario_salida'] else 'Fuera de la selección inicial de salida')+' · Revisión independiente: pendiente</p></header>'
        body+=cifras([(c['cantidad_pruebas'],'intentos en test')]+[(c['resultados'][r],r) for r in ['OK','Fallo','Reparado','Parcial']])
        body+='<section class="panel"><h2>Cómo y cuándo se prueba</h2><p><b>Roles:</b> '+E(', '.join(c['roles']))+'</p><p><b>Condición:</b> '+E(c['requiere'])+'</p><p><b>Método del catálogo:</b> '+E(c['metodo'])+'</p><p><b>Agenda propuesta:</b> '+'<br>'.join(E(t) for t in agenda(c))+'</p><p>Las fechas propuestas no son ejecuciones ni compromisos de un probador asignado.</p></section>'
        letras={q['letra'] for i in c['intentos'] for q in i['criterios']}
        body+='<h2>Resultado esperado por criterio</h2>'+tabla(['Criterio','Esperado del catálogo','Evidencia en test'],[tr([E(q['l']+' · '+q['donde']),E(q['texto']),'Parcial' if q['l'] in letras else 'Sin evidencia consolidada']) for q in c['esperado']])
        body+='<p>'+evidencia(path,[c['fuente']])+' · Catálogo de solo lectura. Las variantes de la historia se describen a continuación; no lo reemplazan.</p><h2>Historial de pruebas</h2>'
        if not c['intentos']:body+='<p class="empty">No hay una ejecución vinculada en el sitio de test. No hay resultado, fallo ni reparación acreditados.</p>'
        for i in c['intentos']:
            body+='<article class="panel" id="'+E(i['ejecucion'])+'"><h3>'+E(i['ejecucion'])+' · Día '+str(i['dia'])+'</h3>'+tag(i['resultado_caso'])+'<p><b>Fecha del club:</b> '+E(i['fecha_club'])+' · <b>Real:</b> '+E(i['fecha_real'])+'</p><p><b>Probador:</b> '+E(i['ejecutor'])+' · '+E(i['rol'])+' / '+E(i['usuario'])+'</p><p><b>Pantalla:</b> '+E(i['pantalla'])+'</p><dl>'+''.join('<dt>'+t+'</dt><dd>'+E(i[k])+'</dd>' for t,k in [('Cómo se hizo','metodo'),('Esperado de la variante','esperado_variante'),('Resultado obtenido','obtenido'),('Límite de la prueba','limite')])+'</dl><p><b>Operación del cuento:</b> '+E(i['resultado_operacion'])+' · <b>Caso del catálogo:</b> '+E(i['resultado_caso'])+'</p><p class="meta">'+E(i['sitio']+' / '+i['base'])+' · versión <code>'+E(i['version'])+'</code></p><details><summary>Evidencia, controles técnicos y alcance por letra</summary><p>'+evidencia(path,i['evidencia'])+'</p>'+''.join('<p><b>'+E(k)+':</b> '+E(v)+'</p>' for k,v in i['tecnico'].items())+''.join('<p>'+E(q['letra']+': '+q['alcance'])+(' (vínculo retrospectivo del 23/10, no nueva ejecución)' if q.get('retrospectivo') else '')+'</p>' for q in i['criterios'])+'</details></article>'
        body+='<h2>Óptimo o esperado frente a obtenido</h2>'+metas(path,c['metas'])
        body+='<h2>Incidencia, solución y nueva prueba</h2>'+(plan_html(c['plan']) if c['plan'] else '<p>Sin incidencia o plan registrado para este caso. Esto no certifica ausencia de errores. Día de fallo, solución y retest: sin registro.</p>')
        body+='<h2>Apoyo técnico aislado</h2><p>No suma intentos, OK ni reparaciones del sitio de test.</p>'
        if c['apoyo_aislado']:
            body+=tabla(['Método','Fecha / base','Resultado auxiliar','Evidencia'],[tr([E(e['ejecucion']),E(str(e['fecha'])+' / '+str(e['base'])),E(e['resultado']),evidencia(path,e['evidencia'])]) for e in c['apoyo_aislado']])
        else:body+='<p>No hay un método aislado vinculado a este caso en esta entrega.</p>'
        pagina(path,'Wings · '+c['codigo']+' · '+c['titulo'],body)
    for dia in m['dias']:
        n=dia['dia'];path=dia['archivo']
        body='<header><h1>Día '+str(n)+' · '+E(dia['fecha_club'])+'</h1>'+tag(dia['estado'])+'</header>'
        if n!=4:
            body+='<section class="panel"><h2>Sin resultados consolidados por caso</h2><p>No se heredan resultados de otra jornada. Esta fecha corresponde al calendario ficticio; no demuestra que el reloj haya avanzado.</p>'
            if n<=3:body+='<p>Existe un antecedente que falta conciliar por caso: '+evidencia(path,[f'docs/06-pruebas/PRU-04/DIA-{n:02}.md'])+'</p>'
            propuestos=[c for c in m['casos'] if any(n in p['dias'] for p in c['programacion']) or (c['plan'] and c['plan']['diagnostico_dia_propuesto']==n)]
            if propuestos:body+='<h3>Planificación propuesta</h3>'+''.join('<p>'+enlace_caso(path,c)+' · '+'<br>'.join(E(t) for t in agenda(c))+'</p>' for c in propuestos)
            body+='</section>'
            pagina(path,'Wings · Día '+str(n),body,True);continue
        grupo=[c for c in m['casos'] if c['intentos']]
        body+='<p>Ejecutado el 22/10/2026 en test / wingstest, versión <code>'+E(d['commit_sitio_leido'])+'</code>. Revisión independiente pendiente.</p>'
        body+=cifras([(s['parciales'],'casos parciales'),(s['intentos'],'vínculos caso–ejecución'),(s['OK'],'casos completos OK'),(s['Fallo'],'casos completos Fallo'),(s['Reparado'],'Reparados'),(s['planes'],'planes de acción')])
        body+='<p>16 operaciones del cuento: 14 coinciden y 2 parciales. Ese resultado operativo no equivale a 14 casos completos aprobados. El vínculo retrospectivo de dos consultas del alta agrega evidencia, no operaciones.</p><h2>Todos los casos probados</h2>'+filtros()+tabla(['Caso','Estado actual','Intentos del día','OK / Fallo / Reparado / Parcial','Siguiente comprobación'],[fila_caso(path,c) for c in grupo],'data-casos')+pager()
        body+='<h2 id="tecnico">Pruebas técnicas por caso y por día</h2><p>Contraste posterior del 22/10: '+evidencia(path,['docs/06-pruebas/PRU-04/VERIFICACION-DIA-04.md','docs/06-pruebas/PRU-04/evidencia-verificacion-dia-04/conciliacion.json'])+'. Sin nueva ejecución del sistema al reorganizar este informe.</p>'+tabla(['Caso / operaciones','Resultado contrastado y límite','Seguridad / rendimiento'],[tr([enlace_caso(path,c)+'<p>'+E(', '.join(i['ejecucion'] for i in c['intentos']))+'</p>',''.join('<p>'+E(i['obtenido'])+'</p><p class="meta">'+E(i['limite'])+'</p>' for i in c['intentos']),'Datos conciliados solo en el alcance documentado. Seguridad negativa remota y tiempos visibles pendientes; ver evidencia del caso.']) for c in grupo])
        body+='<h3>Comparaciones numéricas</h3>'+metas(path,[x for c in grupo for x in c['metas']])
        body+='<section class="panel"><h3>Rendimiento · una muestra técnica</h3><p>Reporte mensual: 35,83 ms de llamada CLI; 29 consultas, 11,37 ms SQL; 101 alumnos activos. Fecha real: 22/10, base wingstest.</p><p><b>Óptimo comparable:</b> no fijado para esa llamada. Para respuesta visible se propone ≤1.000 ms como criterio de trabajo, pendiente de ratificación y medición. La llamada CLI no mide apertura visible, guardado, concurrencia ni carga: no se compara contra ese objetivo.</p><p>'+evidencia(path,['docs/06-pruebas/PRU-04/evidencia-verificacion-dia-04/servicios.json'])+'</p></section>'
        body+='<section class="panel"><h3>Seguridad y otros ensayos aislados · 23/10</h3><p>41 métodos aprobados / 331 aserciones en wings_testing_codex. Incluyen permisos, validaciones e integridad. Se conservan como apoyo; <b>0 casos de seguridad acreditados en el sitio de test</b> por esa corrida. Faltan peticiones negativas al sitio y comprobación de su configuración.</p><p>'+a(path,'DETALLE-TECNICO.html#seguridad','Ver los 41 métodos y sus límites')+'</p><p>Visual: evidencia de algunas pantallas de escritorio; cobertura completa y móvil pendientes. Sin nuevo render de este dashboard.</p></section>'
        body+='<h2>Planes de acción y retests</h2>'+''.join(plan_html(c['plan']) for c in grupo if c['plan'])
        body+='<h2>Operación pendiente de vincular a un caso</h2>'+''.join('<p><b>'+E(p['ejecucion']+' · '+p['titulo'])+'</b>: '+E(p['motivo'])+'</p>' for p in m['operaciones_sin_caso'])
        body+='<p>La asistencia histórica reciente no demuestra la variante «clase de hoy» ni una carga histórica extrema. Se propone revisar el vínculo o agregar un caso mediante Carlos/Claude; el catálogo no se modifica aquí.</p><p>'+a(path,'relatos/DIA-04.html','Relato operativo y las 16 situaciones')+' · '+a(path,'informes-ia/DIA-04.md','Detalle técnico original de la jornada')+'</p>'
        pagina(path,'Wings · Día 4 por caso',body,True)
    md=['# Seguimiento por casos — corte 23/10/2026','',
        'Estado: entrega para revisión independiente. No aprueba producción.',
        '', '## Modelo y ámbito de evidencia', '',m['alcance'],m['regla'],
        '', 'Fuente de aceptación: https://test.gestionar-te.com.ar, base wingstest, versión '+str(d['commit_sitio_leido'])+'. Ejecución y contraste: 22/10/2026; calendario del club 13/10/2026.',
        'La generación de este informe no vuelve a ejecutar cobros ni revalida el servidor actual. La corrida local del 23/10 es auxiliar y sus resultados siguen disponibles en el anexo original.',
        '', '## Recuento', '', '```json',json.dumps(s,ensure_ascii=False,indent=2),'```',
        '', 'Un intento es la asociación única (caso, operación). No se cuenta una vez por letra, SQL o captura. Dos casos pueden compartir una operación; dos consultas de un mismo alta pueden ser dos intentos parciales del mismo caso.',
        'Todos los vínculos actuales son parciales. Los contadores OK/Fallo/Reparado son resultados del caso completo, no los 14 resultados Coincide del relato. No se transforma una diferencia del relato en un fallo formal de todos los criterios.',
        '', '## Historial por módulo y caso', '']
    for modulo in m['modulos']:
        md+=['### '+modulo['nombre'],'','| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |','|---|---|---:|---|---|']
        for code in modulo['casos']:
            c=casos[code];rr=c['resultados']
            md.append(f"| [{code} · {c['titulo']}](../casos/{code}.html) | {c['estado']} | {c['cantidad_pruebas']} | {rr['OK']}/{rr['Fallo']}/{rr['Reparado']}/{rr['Parcial']} | "+'; '.join(agenda(c)).replace('|','/')+' |')
        md+=['']
    md+=['## Reconciliación de vínculos','',json.dumps(m['vinculos_retrospectivos'],ensure_ascii=False,indent=2),
         'Estos dos vínculos se justifican en ALU-002.e (efectos en Cobranza y Reportes). Se registran el 23/10 retrospectivamente; no son nuevas operaciones. D4-T12 queda sin código hasta resolver su cobertura. No se modifica el catálogo Carlos/Claude.',
         '', '## Planes, soluciones y retests','']
    for c in m['casos']:
        if not c['plan']:continue
        p=c['plan'];md+=['### '+p['id']+' / '+c['codigo'],'',p['naturaleza']+'. '+p['problema'],p['impacto'],
                          'Solución propuesta: '+p['solucion_propuesta'],'']+['- '+x for x in p['pasos']]+[
                          '','Aceptación: '+p['aceptacion'],'Responsable: '+p['responsable'],
                          'Implementación, commit, retest, fecha y probador de retest: pendientes. No hay reparación acreditada.','']
    md+=['## Metas y mediciones','', 'Las metas funcionales proceden del guion o de invariantes posteriores identificados en cada fila. El valor de una variante no reemplaza el importe ni las condiciones originales del catálogo.',
         'Rendimiento CLI: 35,83 ms, 29 SQL, 11,37 ms SQL, n=1. Óptimo comparable no definido; sin percentiles ni carga. ≤1.000 ms visible es una propuesta de criterio, no una medición ni una meta aprobada.',
         '', '## Archivos y continuación','',
         '- [Datos por caso](../seguimiento-casos.json): historial, criterios, ambiente, evidencias, contadores, metas, planes y agenda.',
         '- [Día 4 técnico original](DIA-04.md): operaciones, código, datos y métodos auxiliares. Sus recuentos históricos mezclaban apoyo aislado: para aceptación usar la proyección por casos de esta entrega.',
         '- [Dashboard](../CONTROL-INTEGRAL.html): lectura humana; una página por módulo, caso y jornada.',
         'Para nuevas jornadas: incorporar operaciones y vínculos comprobados, con fecha, entorno, rol, versión, esperado previo y evidencia; extender la importación más allá del Día 4. Nunca copiar los resultados de este día a otro.',
         'Reparado requiere falla previa, solución identificada y nueva ejecución OK en el sitio, con fecha, probador y evidencia. Plan no equivale a solución implementada. Otro agente debe revisar esta entrega; no firmar por otro Codex sin identidad y cuenta distinta confirmadas.', '']
    path='informes-ia/SEGUIMIENTO-CASOS.md';(HERE/path).write_text('\n'.join(md),encoding='utf-8')
    manifest={'version':5,'informe_humano':'CONTROL-INTEGRAL.html','informe_ia':path,
              'paginas':archivos,'jornadas':m['dias'],'casos_por_pagina':10}
    (HERE/'paginas-casos.json').write_text(json.dumps(manifest,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    (HERE/'jornadas.json').write_text(json.dumps({k:v for k,v in manifest.items() if k!='paginas'},ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    return {'paginas':len(archivos),'resumen':s,'entrada_bytes':(HERE/'CONTROL-INTEGRAL.html').stat().st_size}
