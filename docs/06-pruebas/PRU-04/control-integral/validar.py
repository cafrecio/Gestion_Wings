"""Integridad documental: no ejecuta aplicación, migraciones ni suite."""
import hashlib
import copy
import sys
import re
sys.dont_write_bytecode=True
from salida import actualizar
from seguimiento_casos import construir, es_sitio, estado_jornada, resultado_retest
from html.parser import HTMLParser
import json
from pathlib import Path
import subprocess
from urllib.parse import unquote, urlsplit
import xml.etree.ElementTree as ET

HERE=Path(__file__).resolve().parent
REPO=HERE.parents[3]
class Page(HTMLParser):
    def __init__(self):
        super().__init__(); self.ids=[];self.links=[];self.rows=0;self.text=[];self.ignore=0;self.situaciones=0
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if 'id' in a:self.ids.append(a['id'])
        if tag=='a':self.links.append(a.get('href',''))
        if tag=='link' and a.get('href'):self.links.append(a['href'])
        if tag in ['script','style']:self.ignore+=1
        if tag=='script' and a.get('src'):self.links.append(a['src'])
        if 'data-situacion' in a:self.situaciones+=1
        if tag=='tr' and 'data-search' in a:self.rows+=1
    def handle_endtag(self,tag):
        if tag in ['script','style']:self.ignore-=1
    def handle_data(self,text):
        if not self.ignore:self.text.append(text)

d=json.loads((HERE/'control-integral.json').read_text(encoding='utf-8'))
base=json.loads(subprocess.check_output(['git','show','a15d6095:docs/06-pruebas/PRU-04/control-integral/control-integral.json'],cwd=REPO))
assert len(d['casos'])==len(base['casos'])==331
old={c['codigo']:c for c in base['casos']}
for c in d['casos']:
    for k,v in old[c['codigo']].items():
        if k!='revision_actual':assert c[k]==v,(c['codigo'],k)
assert not subprocess.check_output(['git','diff','a15d6095','--','docs/06-pruebas/PRU-04/tabla-de-pruebas'],cwd=REPO)
assert not subprocess.check_output(['git','diff','--','app','routes','resources','tests'],cwd=REPO)
ids={e['id'] for e in d['ejecuciones']}
assert len(ids)==len(d['ejecuciones'])
for e in d['ejecuciones']:
    for p in e['evidencia']:assert (REPO/p).is_file(),p
for c in d['casos']:
    for q in c['criterios_seguimiento']:
        assert set(q['ejecuciones'])<=ids
        assert bool(q['ejecuciones'])==(q['estado']!='Pendiente')
manifest=json.loads((HERE/'paginas-casos.json').read_text(encoding='utf-8'))
pages={}
for name in ['CONTROL-TECNICO.html','CATALOGO.html','DETALLE-TECNICO.html','relatos/DIA-04.html']+manifest['paginas']:
    p=Page();p.feed((HERE/name).read_text(encoding='utf-8'))
    assert len(p.ids)==len(set(p.ids)), ('ID HTML duplicado',name)
    assert all(p.ids),name
    pages[name]=p
assert pages['CATALOGO.html'].rows==len(d['casos'])
assert pages['CONTROL-TECNICO.html'].rows==d['produccion']['casos_necesarios']+len(d['produccion']['tareas_previas_abiertas'])+16
assert pages['DETALLE-TECNICO.html'].rows==43+len(d['recorridos'])+len(d['controles'])
assert pages['relatos/DIA-04.html'].situaciones==16
assert len(manifest['jornadas'])==60
projection=construir(d)
assert projection==json.loads((HERE/'seguimiento-casos.json').read_text(encoding='utf-8'))
assert projection['resumen']=={'casos':331,'modulos':20,'probados':15,'sin_probar':316,
    'intentos':18,'operaciones':16,'operaciones_sin_caso':1,'parciales':15,'planes':3,
    'metodos_aislados':41,'OK':0,'Fallo':0,'Reparado':0,'salida_seleccionados':69,'salida_probados':15}
assert sum(m['total'] for m in projection['modulos'])==331
assert sum(m['probados'] for m in projection['modulos'])==15
assert projection['dias'][3]['estado']=='Plan de acción'
assert projection['dias'][37]['estado']=='Sin consolidar'
for c in projection['casos']:
    assert c['codigo'] in ' '.join(pages['casos/'+c['codigo']+'.html'].text)
    assert c['cantidad_pruebas']==sum(c['resultados'].values())
    assert len({i['ejecucion'] for i in c['intentos']})==c['cantidad_pruebas']
    assert all(i['base']=='wingstest' and i['sitio']==projection['sitio'] for i in c['intentos'])
    assert not any(i['ejecucion'].startswith('SEG-') for i in c['intentos'])
    assert all(c['codigo']==q['caso'] for i in c['intentos'] for q in i['criterios'])
    for p in c['programacion']:assert all(1<=n<=60 for n in p['dias'])
    if c['plan']:assert c['plan']['implementacion'] is None and c['plan']['retest'] is None
assert projection['operaciones_sin_caso'][0]['ejecucion']=='D4-T12'
assert all(not es_sitio(e,{'base':'wingstest','sitio':projection['sitio']}) for e in d['ejecuciones'] if e['id'].startswith('SEG-'))
# Estados y reparaciones: probar las reglas, no fabricar evidencia de aplicación.
assert estado_jornada(['OK'])=='OK'
assert estado_jornada(['Parcial'])=='Atención'
assert estado_jornada(['Fallo'],True)=='Plan de acción'
assert estado_jornada(['OK','Reparado'])=='Reparado'
assert estado_jornada([],True)=='Sin consolidar'
assert not resultado_retest(True,{'commit':'x','fecha':'2026-10-23'},None)
assert not resultado_retest(True,{'commit':'x','fecha':'2026-10-23'},
    {'fecha':'2026-10-24','ejecutor':'ejemplo','evidencia':['ejemplo'],'sitio_validado':False,'resultado':'OK'})
assert resultado_retest(True,{'commit':'x','fecha':'2026-10-23'},
    {'fecha':'2026-10-24','ejecutor':'ejemplo','evidencia':['ejemplo'],'sitio_validado':True,'resultado':'OK'})
for name,p in pages.items():
    for href in p.links:
        url=urlsplit(href)
        if url.scheme:continue
        file=(HERE/name).parent/unquote(url.path) if url.path else HERE/name
        file=file.resolve()
        assert file.is_file(),(name,href)
        try:key=file.relative_to(HERE).as_posix()
        except ValueError:key=''
        if url.fragment and key in pages:
            assert unquote(url.fragment) in pages[key].ids,(name,href)
for e in d['ejecuciones']:
    assert e['verifica']=='Pendiente — otro agente',e['id']
    assert e['revision_independiente']['estado']=='Pendiente',e['id']
assert d['produccion']['con_firma_independiente']==0
assert d['produccion']['casos_necesarios']==sum(c['produccion']['necesario'] for c in d['casos'])
assert d['produccion']['con_evidencia_parcial']==sum(c['produccion']['necesario'] and bool(c['ejecuciones']) for c in d['casos'])
rechazos=0
for variant in ['mismo_ejecutor','sin_evidencia','misma_cuenta_no_confirmada']:
    trial=copy.deepcopy(d)
    target=trial['ejecuciones'][0]
    target['revision_independiente']={'estado':'Verificada','agente':'Codex','identidad':target['identidad_ejecutor'],'evidencia':target['evidencia'][:1]}
    if variant=='sin_evidencia':target['revision_independiente'].update(agente='Gemini',identidad='Revisor de ejemplo',evidencia=[])
    if variant=='misma_cuenta_no_confirmada':target['revision_independiente']['identidad']='Otra identidad de ejemplo'
    try:actualizar(trial)
    except AssertionError:rechazos+=1
    else:raise AssertionError('Se aceptó firma inválida: '+variant)
assert rechazos==3
junit=ET.parse(HERE/'evidencia-seguridad-2026-10-23/junit.xml')
cases=junit.findall('.//testcase')
assert len(cases)==d['resumen']['automatizadas']==41
assert sum(int(t.attrib.get('assertions',0)) for t in cases)==d['resumen']['aserciones']==331
assert not junit.findall('.//failure') and not junit.findall('.//error') and not junit.findall('.//skipped')
human=[e for e in d['ejecuciones'] if e['id'].startswith('D4-T')]
assert len(human)==16 and sum(e['resultado']=='Coincide' for e in human)==14
assert sum(e['resultado']=='Parcial' for e in human)==2
tracked=[*pages,'seguimiento-casos.json','paginas-casos.json','informes-ia/SEGUIMIENTO-CASOS.md']
before={name:hashlib.sha256((HERE/name).read_bytes()).hexdigest() for name in tracked}
md=HERE/'informes-ia/DIA-04.md'
assert md.is_file()
for p in re.findall(r'\]\(([^)]+)\)',md.read_text(encoding='utf-8')):
    assert (md.parent/p).is_file(),p
assert all(f'### D4-T{n:02}' in md.read_text(encoding='utf-8') for n in range(1,17))
for p in re.findall(r'\]\(([^)]+)\)',(HERE/'informes-ia/SEGUIMIENTO-CASOS.md').read_text(encoding='utf-8')):
    assert (md.parent/p).is_file(),p
md_before=hashlib.sha256(md.read_bytes()).hexdigest()
subprocess.run(['python',str(HERE/'generar.py')],cwd=REPO,check=True,capture_output=True)
assert {name:hashlib.sha256((HERE/name).read_bytes()).hexdigest() for name in tracked}==before,'Generación no determinista'
assert hashlib.sha256(md.read_bytes()).hexdigest()==md_before
navigation=json.loads(subprocess.check_output(['node',str(HERE/'verificar-navegacion.cjs')],cwd=REPO,encoding='utf-8'))
dashboard=json.loads(subprocess.check_output(['node',str(HERE/'verificar-dashboard.cjs')],cwd=REPO,encoding='utf-8'))
result={'fecha':'2026-10-23','catalogo_fuente_sin_editar':True,'campos_fuente_copiados_intactos':331,
        'aplicacion_y_tests_sin_editar':True,'ejecuciones':len(ids),'filas_html':sum(p.rows for p in pages.values()),
        'enlaces_comprobados':sum(len(p.links) for p in pages.values()),'fragmentos_e_ids_validos':True,
        'generacion_determinista':True,'junit':{'aprobadas':41,'aserciones':331,'fallidas':0},
        'revision_independiente':'59 registros pendientes; tres intentos de firma inválida rechazados en copia en memoria, sin guardarlos',
        'produccion':{'seleccionados':projection['resumen']['salida_seleccionados'],'con_evidencia_parcial_en_test':projection['resumen']['salida_probados'],'firmas_independientes':0,'tareas_previas_abiertas':len(d['produccion']['tareas_previas_abiertas'])},
        'bytes_html':{name:(HERE/name).stat().st_size for name in ['CONTROL-INTEGRAL.html','jornadas/DIA-04.html']},
        'navegacion':navigation,
        'dashboard':dashboard,'recuento_aceptacion':projection['resumen'],'paginas_por_caso':len(projection['casos']),
        'entregables':{'humano':'CONTROL-INTEGRAL.html, módulos, casos y 60 jornadas','ia':'informes-ia/SEGUIMIENTO-CASOS.md','situaciones_dia4':16,'codigos_visibles_en_entrega_humana':True},
        'visual':'Validación documental y lógica de navegación sin navegador. Sin afirmar render ni latencia móvil. Se conserva la restricción previa de observar file://.'}
(HERE/'integridad-entregables.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print(json.dumps(result,ensure_ascii=False))
