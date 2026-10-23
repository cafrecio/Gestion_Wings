"""Integridad documental: no ejecuta aplicación, migraciones ni suite."""
import hashlib
import copy
import sys
sys.dont_write_bytecode=True
from salida import actualizar
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
        super().__init__(); self.ids=[];self.links=[];self.rows=0
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if 'id' in a:self.ids.append(a['id'])
        if tag=='a':self.links.append(a.get('href',''))
        if tag=='tr' and 'data-search' in a:self.rows+=1

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
pages={}
for name in ['CONTROL-INTEGRAL.html','CATALOGO.html','DETALLE-TECNICO.html']:
    p=Page();p.feed((HERE/name).read_text(encoding='utf-8'))
    assert len(p.ids)==len(set(p.ids)), ('ID HTML duplicado',name)
    assert all(p.ids),name
    pages[name]=p
assert pages['CATALOGO.html'].rows==len(d['casos'])
assert pages['CONTROL-INTEGRAL.html'].rows==d['produccion']['casos_necesarios']+len(d['produccion']['tareas_previas_abiertas'])+16
assert pages['DETALLE-TECNICO.html'].rows==43+len(d['recorridos'])+len(d['controles'])
for name,p in pages.items():
    for href in p.links:
        url=urlsplit(href)
        if url.scheme:continue
        file=(HERE/unquote(url.path)).resolve() if url.path else HERE/name
        assert file.is_file(),(name,href)
        if url.fragment and file.name in pages and file.parent==HERE:
            assert unquote(url.fragment) in pages[file.name].ids,(name,href)
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
before={name:hashlib.sha256((HERE/name).read_bytes()).hexdigest() for name in pages}
subprocess.run(['python',str(HERE/'generar.py')],cwd=REPO,check=True,capture_output=True)
assert {name:hashlib.sha256((HERE/name).read_bytes()).hexdigest() for name in pages}==before,'Generación no determinista'
result={'fecha':'2026-10-23','catalogo_fuente_sin_editar':True,'campos_fuente_copiados_intactos':331,
        'aplicacion_y_tests_sin_editar':True,'ejecuciones':len(ids),'filas_html':sum(p.rows for p in pages.values()),
        'enlaces_comprobados':sum(len(p.links) for p in pages.values()),'fragmentos_e_ids_validos':True,
        'generacion_determinista':True,'junit':{'aprobadas':41,'aserciones':331,'fallidas':0},
        'revision_independiente':'59 registros pendientes; tres intentos de firma inválida rechazados en copia en memoria, sin guardarlos',
        'produccion':{'seleccionados':d['produccion']['casos_necesarios'],'con_evidencia_parcial':d['produccion']['con_evidencia_parcial'],'firmas_independientes':0,'tareas_previas_abiertas':len(d['produccion']['tareas_previas_abiertas'])},
        'bytes_html':{name:(HERE/name).stat().st_size for name in pages},
        'visual':'Versión nueva: validación estática, no recorrido visual ni medición de apertura móvil. El navegador rechazó observar file://; no se eludió esa restricción.'}
(HERE/'integridad-visor-v3.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print(json.dumps(result,ensure_ascii=False))
