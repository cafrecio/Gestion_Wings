"""Dos entregas desde evidencia compartida: lectura humana y continuidad técnica."""
from pathlib import Path
from datetime import date
import html
import json
import os

HERE=Path(__file__).resolve().parent
REPO=HERE.parents[3]
esc=lambda value:html.escape(str(value))
def generar_devoluciones(d):
    model=json.loads((HERE/'devolucion-humana.json').read_text(encoding='utf-8'))
    records={e['id']:e for e in d['ejecuciones']}
    out=[]
    def href(origin,path,label):
        target=(REPO/path).resolve()
        assert target.is_file(),path
        return '<a href="'+esc(os.path.relpath(target,origin.parent).replace('\\','/'))+'">'+esc(label)+'</a>'
    def mdlink(origin,path,label=None):
        target=REPO/path;assert target.is_file(),path
        return '['+(label or target.name)+']('+os.path.relpath(target,origin.parent).replace('\\','/')+')'
    def write(path,text):
        path.parent.mkdir(parents=True,exist_ok=True);path.write_text(text,encoding='utf-8');out.append(path)
    css='''*{box-sizing:border-box}body{margin:0;background:#f4f6f8;color:#20313e;font:16px/1.55 system-ui,sans-serif}main{max-width:1120px;margin:auto;padding:24px}h1{font-size:28px;line-height:1.2;margin:8px 0 12px}h2{font-size:22px;margin:18px 0 10px}h3{font-size:18px;margin:0 0 8px}p{margin:8px 0}a{color:#205f88}header,.panel,article{background:white;border:1px solid #d4dce2;border-radius:12px;padding:20px;margin-bottom:16px}.meta{color:#526471;font-size:14px}.eyebrow{font-weight:700;color:#526471}.lead{font-size:19px;max-width:900px}.facts,.two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.fact{background:#f0f5f8;padding:12px;border-radius:8px}.fact b{display:block;font-size:22px}.attention{border-left:5px solid #b77c1d}.ok{border-left:5px solid #3a8563}.pill{display:inline-block;font-size:13px;font-weight:700;padding:3px 9px;border-radius:20px;background:#e8eef2}.pill.warn{background:#fff0cf;color:#744b00}.pill.good{background:#dfefe7;color:#245941}form,.tabs,.pager,nav{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:12px 0}input,select,button{font:inherit;border:1px solid #9eafbc;border-radius:7px;padding:9px;background:white;color:inherit}button{cursor:pointer}button:disabled{opacity:.45;cursor:default}button[aria-selected=true]{background:#203f54;color:white}input[type=number]{width:90px}input[type=search]{flex:1;min-width:220px}label{font-weight:650}summary{cursor:pointer;font-weight:700;padding:5px 0}dl{margin:12px 0}dt{font-weight:700;margin-top:13px}dd{margin:4px 0 0;max-width:850px}li{margin:6px 0}ul{padding-left:22px}.pager{justify-content:space-between}footer{font-size:14px;color:#526471;margin-top:18px}[hidden]{display:none!important}article:target{outline:2px solid #b77c1d}a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible{outline:3px solid #c08523;outline-offset:3px}@media(max-width:650px){main{padding:12px}.two,.facts{grid-template-columns:1fr}h1{font-size:24px}header,.panel,article{padding:15px}input[type=search]{min-width:0;width:100%;flex-basis:100%}.pager{gap:6px}.pager span{flex-basis:100%;text-align:center}.lead{font-size:17px}}@media print{form,.tabs,.pager{display:none}body{background:white}main{padding:0}article{break-inside:avoid}}'''
    def shell(path,title,body,day=4):
        routes={str(n):os.path.relpath(HERE/f'jornadas/DIA-{n:02}.html',path.parent).replace('\\','/') for n in [1,2,3]+[j['dia'] for j in model['jornadas']]}
        script=os.path.relpath(HERE/'informe-humano.js',path.parent).replace('\\','/')
        picker='<section class="panel"><form id="elegir-dia" data-dia="'+str(day)+'" data-rutas="'+esc(json.dumps(routes))+'"><label for="numero-dia">Ir al día</label><input id="numero-dia" type="number" min="1" max="60" value="'+str(day)+'" required><button type="submit">Abrir</button><span class="meta">Elegís una jornada; no recorrés las anteriores.</span></form><p id="aviso-dia" role="status"></p></section>'
        empty='<section id="sin-informe" class="panel" hidden tabindex="-1"><h2 id="titulo-sin-informe"></h2><p>No hay resultados publicados para esa jornada. No se muestran los resultados de otro día ni se completa con datos inventados.</p><button id="volver-informe" type="button">Volver al informe</button></section>'
        return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'+esc(title)+'</title><style>'+css+'</style><script defer src="'+esc(script)+'"></script></head><body><main>'+picker+empty+'<div id="contenido-informe">'+body+'</div></main></body></html>'
    days=[]
    for j in model['jornadas']:
        day=j['dia']; path=HERE/f'jornadas/DIA-{day:02}.html'
        expected_ids={f'D4-T{n:02}' for n in range(1,17)} if day==4 else {s['referencia'] for s in j['situaciones']}
        assert {s['referencia'] for s in j['situaciones']}==expected_ids
        raw=[records[s['referencia']] for s in j['situaciones']]
        match=sum(e['resultado']=='Coincide' for e in raw);partial=sum(e['resultado']=='Parcial' for e in raw)
        club=date.fromisoformat(j['fecha_club']); real=date.fromisoformat(j['fecha_ejecucion']); contraste=date.fromisoformat(j['fecha_contraste'])
        label=['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][club.weekday()]+' '+str(club.day)+' de '+['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][club.month-1]
        heading='<header><p class="eyebrow">Devolución de pruebas · Día '+str(day)+'</p><h1>'+esc(label)+' · '+esc(j['titulo'])+'</h1><p class="lead">'+esc(j['conclusion'])+'</p><p class="meta">Fecha del club: '+club.strftime('%d/%m/%Y')+' · Ejecutado por pantalla: '+real.strftime('%d/%m/%Y')+' · Contraste de datos: '+contraste.strftime('%d/%m/%Y')+'.</p><p><span class="pill warn">Revisión de otro agente pendiente</span></p></header>'
        tabs='<div class="tabs" role="tablist" aria-label="Lectura del día"><button id="tab-resumen" data-vista="resumen" role="tab" aria-selected="true" aria-controls="resumen">Resumen del día</button><button id="tab-situaciones" data-vista="situaciones" role="tab" aria-selected="false" aria-controls="situaciones">Las '+str(len(raw))+' situaciones</button></div>'
        summary='<section id="resumen" role="tabpanel" aria-labelledby="tab-resumen"><div class="facts"><div class="fact"><b>'+str(match)+' coinciden</b>Con lo esperado en el recorrido.</div><div class="fact"><b>'+str(partial)+' parciales</b>Plazo de Mora y alcance del pago parcial.</div></div><section class="panel ok"><h2>Qué quedó bien</h2><ul>'+''.join('<li>'+esc(x)+'</li>' for x in j['bien'])+'</ul></section><h2 id="pendientes">Qué requiere atención y cómo seguir</h2>'
        for x in j['atender']:
            summary+='<article class="attention"><h3>'+esc(x['titulo'])+'</h3><p>'+esc(x['impacto'])+'</p><p><b>Propuesta:</b> '+esc(x['propuesta'])+'</p><p class="meta">'+esc(x['estado'])+'</p><a href="#situacion-'+str(x['situacion'])+'">Ver qué se hizo y qué pasó</a>'
            if x.get('opciones'):
                summary+='<details><summary>Soluciones posibles, sin implementar</summary><ol>'+''.join('<li>'+esc(option)+'</li>' for option in x['opciones'])+'</ol></details>'
            summary+='</article>'
        summary+='<section class="panel"><h2>Próximos pasos</h2><ol>'+''.join('<li>'+esc(x)+'</li>' for x in j['siguiente'])+'</ol></section><section class="panel" id="comprobaciones"><h2>Seguridad, rendimiento y producción</h2>'+''.join('<p><b>'+name+':</b> '+esc(j['comprobaciones'][key])+'</p>' for key,name in [('seguridad','Seguridad posterior, 23/10'),('rendimiento','Rendimiento'),('visual','Pantallas'),('produccion','Producción')])+'</section></section>'
        caseview='<section id="situaciones" role="tabpanel" aria-labelledby="tab-situaciones" hidden><h2>Qué pasó en cada situación</h2><div role="search"><label for="buscar-situacion">Buscar</label><input id="buscar-situacion" type="search" placeholder="Ej.: Mora, pago, asistencia"><label for="estado-situacion">Mostrar</label><select id="estado-situacion"><option value="">Todas</option><option value="Parcial">Resultados parciales</option><option value="Coincide">Coinciden con el guion</option></select></div><p class="meta">Cuatro situaciones por página. Abrí una para ver el método, el impacto y la propuesta.</p><div id="lista-situaciones">'
        for c in j['situaciones']:
            record=records[c['referencia']];state=record['resultado']; tone='good' if state=='Coincide' else 'warn'
            badge_text=state
            if day==4 and c['numero'] in (8,16):badge_text='Coincide · observación abierta';tone='warn'
            imgs=sorted((REPO/'docs/06-pruebas/PRU-04/evidencia-dia-04').glob(f'dia04-{c["numero"]:02}*.png')) if day==4 else []
            ev=' · '.join(href(path,p.relative_to(REPO).as_posix(),'Imagen '+str(i+1)) for i,p in enumerate(imgs))
            if ev:ev='<p>'+ev+'</p>'
            info='<details><summary>Cuándo, cómo, impacto y propuesta</summary><dl>'
            fields=[('Cuándo','Fecha del club '+club.strftime('%d/%m/%Y')+'. Ejecutado el '+real.strftime('%d/%m/%Y')+'; contrastado el '+contraste.strftime('%d/%m/%Y')+'. '+('Cierre a las 19:33.' if day==4 and c['numero']==13 else 'Validación a las 19:36.' if day==4 and c['numero']==14 else 'Hora exacta del paso no registrada.')),
                    ('Quién y dónde',c['quien']+' · '+c['donde']),('Cómo se probó',c['como']),('Qué se esperaba (resumen del guion)',c['esperado']),('Qué quedó bien',c['bien']),('Qué implica',c['impacto']),('Qué no quedó demostrado',c['pendiente']),('Propuesta o siguiente control',c['propuesta'])]
            info+=''.join('<dt>'+esc(label)+'</dt><dd>'+esc(value)+'</dd>' for label,value in fields)+'</dl><p class="meta">Resultado entregado por '+esc(record['hizo'])+'; revisión independiente: '+esc(record['revision_independiente']['estado'])+'. Las propuestas no se implementaron durante esta prueba.</p>'+ev+href(path,f'docs/06-pruebas/PRU-04/DIA-{day:02}.md','Registro original por pantalla')+'</details>'
            search=' '.join(str(v) for v in c.values())
            caseview+='<article id="situacion-'+str(c['numero'])+'" data-situacion data-busqueda="'+esc(search)+'" data-estado="'+esc(state)+'"><span class="pill '+tone+'">'+esc(badge_text)+'</span><h3>'+esc(c['titulo'])+'</h3><p>'+esc(c['ocurrio'])+'</p>'+info+'</article>'
        caseview+='</div><nav class="pager" aria-label="Páginas de situaciones"><button id="anterior" type="button">Anterior</button><span id="pagina-situaciones" role="status"></span><button id="siguiente" type="button">Siguiente</button></nav></section>'
        footer='<footer>'+href(path,'docs/06-pruebas/PRU-04/control-integral/CONTROL-INTEGRAL.html','Volver a las jornadas')+' · <a href="../informes-ia/DIA-'+str(day).zfill(2)+'.md">Entrega técnica para las IA</a><p>Corte de esta devolución: 23/10/2026. Refleja la evidencia fechada, no una consulta en vivo al club.</p></footer>'
        write(path,shell(path,f'Wings · Devolución del Día {day}',heading+tabs+summary+caseview+footer,day))
        days.append({'dia':day,'archivo':path.relative_to(HERE).as_posix(),'fecha_club':j['fecha_club'],'estado':'Devolución disponible'})
        # Entrega técnica: trazabilidad exhaustiva sin imponerla a la lectura humana.
        md=HERE/f'informes-ia/DIA-{day:02}.md'
        tech=[f'# Entrega técnica — Día {day}', '',f'Corte documental: {model["corte"]}. Club: {j["fecha_club"]}. Ejecución humana: {j["fecha_ejecucion"]}. Contraste: {j["fecha_contraste"]}.',
              '', '**Revisión independiente pendiente.** Ejecución y contraste de Codex no constituyen dos verificadores. Esta reorganización no repitió operaciones, suites ni consultas de negocio.',
              '', '## Lectura y límites', '', 'Los 16 escenarios, los métodos automatizados y los casos fuente son unidades distintas. Conservar los 14 Coincide / 2 Parcial del ejecutor; no convertirlos en casos de catálogo completos.',
              'Las horas de cierre/validación del campo de contexto no son la hora individual de todos los pasos. No reconstruir snapshots previos inexistentes.',
              '', '## Entorno y fuentes', '', mdlink(md,'docs/06-pruebas/PRU-04/DIA-04.md','Informe del ejecutor')+' · '+mdlink(md,'docs/06-pruebas/PRU-04/VERIFICACION-DIA-04.md','Contraste técnico')+' · '+mdlink(md,'docs/06-pruebas/PRU-04/control-integral/control-integral.json','Registro canónico')+' · '+mdlink(md,'docs/06-pruebas/PRU-04/control-integral/devolucion-humana.json','Síntesis humana trazable'),
              '', '- Staging: test.gestionar-te.com.ar; base wingstest; versión leída 56379295a33782858ce9c986e200f13382d61cc4; reloj leído 13/10 19:51 el 22/10, sin cambio por este contraste.',
              '- Corrida dirigida posterior: local testing, wings_testing_codex, a15d6095000e0605cae71fc89211f4db111e6983; 23/10. HTTP interno/servicios, sin CSRF real ni certificación remota.',
              '', '## Hallazgos y orden de resolución', '', '1. **D4-OBS-01:** alumno 331 AL_DIA sin deuda octubre persistida; CajaWebController::cobrar agrega una DeudaCuota en memoria. La clasificación usa filas persistidas. Alcance y cuerpos leídos al corte en el contraste; no una nueva auditoría del código actual.',
              '   - Riesgo: discrepancia de concepto entre deuda exigible y cuota potencial; octubre no se cobró. Confirmar regla de negocio antes de escribir deudas o cambiar la pantalla.',
              '   - Alternativas: generar una deuda realmente faltante; no ofrecer como deuda una cuota ya resuelta; separar explícitamente la acción de crear/cobrar cuota del saldo exigible. No elegir por conveniencia de cuadre.',
              '   - Verificar después: misma persona/período/rol entre Cobranza, Cobrar, ficha y reportes; rechazo sin escrituras si la operación no procede; conservar noviembre pagado.',
              '2. **T2:** alta y deuda correctas, presentación sin vencimiento explícito/total conjunto. Propuesta humana: fecha y total. Requiere flujo visual del repo antes de implementación; no confundir con error del plazo calculado.',
              '3. **T6:** 20.000 Sep + 10.000 Oct escritos en campos separados; saldo Oct 38.000. Reparto global, excepción FIFO y rechazo sin motivo no acreditados. Ensayar en base aislada; no repetir el cobro real de la jornada.',
              '', '## Seguimiento temporal', '', '- Mora: presente pendiente el 14/10; EN_PLAZO hasta 19/10, MOROSO desde 20/10 si conserva deuda.', '- Julieta, no Sofía: adelanto noviembre 30.000; comprobar no duplicación 01/11 y comisión de noviembre con asistencia aplicable. No inferir comisión por observar el pago.', '- Corridas 06:00/06:15/08:00 y liquidaciones del 02/11 pendientes; la tarde ejecutada no las demuestra.', '', '## Situaciones: evidencia, impacto y cobertura']
        if day!=4:
            tech=[f'# Entrega técnica — Día {day}', '',f'Club: {j["fecha_club"]}. Ejecución: {j["fecha_ejecucion"]}. Contraste: {j["fecha_contraste"]}.', '', 'La revisión independiente depende de los registros canónicos. Esta generación no ejecuta operaciones de negocio.', '', '## Situaciones y trazabilidad']
        for c in j['situaciones']:
            e=records[c['referencia']]
            tech += ['',f'### {e["id"]} — {c["titulo"]}', '',f'- Resultado humano: **{e["resultado"]}**. Rol: {e["rol"]}.',f'- Método por pantalla: {c["como"]}',f'- Esperado resumido: {c["esperado"]}',f'- Observado técnico conservado: {e["observado"]["resultado"]}',f'- Límite técnico: {e["observado"]["limites"]}',f'- Impacto: {c["impacto"]}',f'- Propuesta/siguiente comprobación, no ejecutada: {c["propuesta"]}',f'- Base/entorno: {e["base"]} / {e["entorno"]}; commit {e["commit"]}.',f'- Revisión independiente: {e["revision_independiente"]["estado"]}.', '- Evidencia: '+' · '.join(mdlink(md,p) for p in e['evidencia'])]
            if e.get('criterios'):
                tech += ['', '| Caso.letra | Cobertura | Alcance de la variante |','|---|---|---|']+[f'| {q["caso"]}.{q["letra"]} | {q["estado"]} | {q["alcance"].replace("|","/")} |' for q in e['criterios']]
            else:tech += ['- Cruce propio; no forzar equivalencia a un caso fuente.']
        methods=[e for e in d['ejecuciones'] if e.get('dia')==f'Seguridad posterior al Día {day}']
        tech += ['', '## Métodos automatizados complementarios', '', f'{len(methods)} métodos vinculados a esta jornada; resultados conservados, no reejecutados para esta entrega.', '', '| Método | Resultado | Aserciones | Duración del test (s) |', '|---|---|---|---|']
        tech += [f'| {e["titulo"]} | {e["resultado"]} | {e["observado"]["aserciones"]} | {e["observado"]["duracion_s"]} |' for e in methods]
        if day==4:tech += ['', mdlink(md,'docs/06-pruebas/PRU-04/control-integral/evidencia-seguridad-2026-10-23/junit.xml','JUnit')+' · '+mdlink(md,'docs/06-pruebas/PRU-04/control-integral/evidencia-seguridad-2026-10-23/LEEME.md','Preflight, incidencias del arnés y límites')]
        tech += ['', '## Producción y continuidad', '', f'Selección de trabajo: {d["produccion"]["casos_necesarios"]} códigos únicos; {d["produccion"]["con_evidencia_parcial"]} con evidencia parcial. No reemplaza PRU-05. Los requisitos previos siguientes son estados documentales del tablero, no nuevos bugs reproducidos.', '']
        tech += [f'- {t["id"]}: {t["titulo"]} — {t["estado"]}; responsable {t["responsable"]}.' for t in d['produccion']['tareas_previas_abiertas']]
        tech += ['', 'Otro agente revisa código/evidencia/pantallas con herramientas permitidas, deja su identidad y reporte y actualiza el registro. No firmar por el nombre de otro chat. Si también usa Codex, debe ser otra cuenta conforme al acuerdo de Carlos.', 'Las propuestas no son órdenes de alterar datos de negocio ni de cambiar vistas sin el protocolo del repositorio.', '']
        write(md,'\n'.join(tech))
    # Antecedentes: se enlazan sin convertir sus dictámenes históricos en hallazgos actuales.
    for n,fecha_antecedente in [(1,'10/10/2026'),(2,'11/10/2026'),(3,'12/10/2026')]:
        if any(j['dia']==n for j in model['jornadas']):continue
        path=HERE/f'jornadas/DIA-{n:02}.html'
        body=f'<header><p class="eyebrow">Día {n} · {fecha_antecedente}</p><h1>Antecedente disponible</h1><p class="lead">Hay un registro de esta jornada. Todavía no tiene una devolución consolidada con este formato.</p></header><section class="panel"><p>Los informes originales contienen resultados y revisiones posteriores. Esta reorganización no volvió a comprobar su vigencia: no se presentan sus observaciones iniciales como defectos actuales.</p>'+href(path,f'docs/06-pruebas/PRU-04/DIA-{n:02}.md','Abrir registro original de la jornada')+'</section><footer>'+href(path,'docs/06-pruebas/PRU-04/control-integral/CONTROL-INTEGRAL.html','Volver a las jornadas')+'</footer>'
        write(path,shell(path,f'Wings · Antecedente del Día {n}',body,n));days.append({'dia':n,'archivo':path.relative_to(HERE).as_posix(),'estado':'Antecedente, devolución nueva pendiente'})
    j=next(j for j in model['jornadas'] if j['dia']==model['ultima_jornada']);path=HERE/'CONTROL-INTEGRAL.html'
    current=j['dia'];current_page=f'jornadas/DIA-{current:02}.html'
    overview='<header><p class="eyebrow">Wings · Resultados de las jornadas</p><h1>Qué funcionó, qué pasó y qué hacemos después</h1><p class="lead">Última devolución: Día '+str(current)+' · '+date.fromisoformat(j['fecha_club']).strftime('%d/%m/%Y')+'.</p><p>'+esc(j['conclusion'])+'</p><p class="meta">Prueba por pantalla: '+date.fromisoformat(j['fecha_ejecucion']).strftime('%d/%m/%Y')+'. Corte documental: '+date.fromisoformat(model['corte']).strftime('%d/%m/%Y')+'.</p><p><a href="'+current_page+'">Leer la devolución del Día '+str(current)+' →</a></p></header>'
    overview+='<div class="two"><section class="panel ok"><h2>Lo que ya sabemos</h2><ul>'+''.join('<li>'+esc(text)+'</li>' for text in j['bien'][:2])+'</ul></section><section class="panel attention"><h2>Lo que debemos resolver</h2><ul>'+''.join('<li>'+esc(item['titulo'])+'</li>' for item in j['atender'])+'</ul><a href="'+current_page+'#pendientes">Ver impacto y propuestas →</a></section></div>'
    overview+='<section class="panel" id="salida"><h2>¿Esta versión puede ir a producción?</h2><p><b>Todavía no hay evidencia suficiente para aprobarla.</b> Los resultados de una tarde no cubren todos los permisos, el cambio de mes ni las liquidaciones. Además, otro agente debe revisar el trabajo entregado.</p><details id="requisitos"><summary>Qué compromisos previos siguen pendientes</summary><p>Los siguientes requisitos están abiertos en el tablero. No son fallas nuevas comprobadas hoy.</p><ul>'+''.join('<li>'+esc(t['titulo'])+'</li>' for t in d['produccion']['tareas_previas_abiertas'])+'</ul></details></section>'
    overview+='<section class="panel" id="dia4"><h2>Elegí la jornada que querés consultar</h2><p>Usá el selector de arriba para ir directamente al día que buscás, del 1 al 60. Cada jornada abre su propia devolución. Si aún no se publicó, se indica sin mostrar resultados de otro día. Aquí se muestran solo las cuatro más recientes.</p><nav>'+''.join('<a href="'+esc(x['archivo'])+'">Día '+str(x['dia'])+' · '+esc(x['estado'])+'</a>' for x in sorted(days,key=lambda x:x['dia'])[-4:])+'</nav></section>'
    overview+='<section class="panel" id="seguridad"><h2>Qué se midió y qué falta medir</h2><p>Como complemento del Día 4, el 23/10 se aprobaron 41 métodos técnicos de permisos, validaciones e integridad en una base aislada. La medición puntual de un reporte no es una prueba de carga.</p><a href="jornadas/DIA-04.html#comprobaciones">Leer alcance y límites →</a></section><footer><details><summary>Continuidad y evidencia para otras IA</summary><p><a href="informes-ia/DIA-'+str(current).zfill(2)+'.md">Entrega técnica de la última jornada</a> · <a href="CONTROL-TECNICO.html">Control técnico y catálogo</a></p></details><p>Los resultados se conservan por jornada. Esta página no consulta el club en vivo.</p></footer>'
    write(path,shell(path,'Wings · Devoluciones por jornada',overview,current))
    manifest={'version':1,'dias_previstos':60,'jornadas':sorted(days,key=lambda x:x['dia']),'casos_por_pagina':4,'informe_humano':'CONTROL-INTEGRAL.html','informe_ia':'informes-ia/DIA-04.md'}
    write(HERE/'jornadas.json',json.dumps(manifest,ensure_ascii=False,indent=2)+'\n')
    return {p.relative_to(HERE).as_posix():p.stat().st_size for p in out}
