"""Integridad documental: no ejecuta aplicación, migraciones ni suite."""
import hashlib
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
p=Page();p.feed((HERE/'CONTROL-INTEGRAL.html').read_text(encoding='utf-8'))
assert len(p.ids)==len(set(p.ids)), 'ID HTML duplicado'
assert all(p.ids),'ID vacío'
assert p.rows==len(d['casos'])+len(d['recorridos'])+len(d['controles'])+len(d['ejecuciones'])
for href in p.links:
    url=urlsplit(href)
    if url.scheme:continue
    file=(HERE/unquote(url.path)).resolve() if url.path else HERE/'CONTROL-INTEGRAL.html'
    assert file.is_file(),href
    if url.fragment and file==HERE/'CONTROL-INTEGRAL.html':assert unquote(url.fragment) in p.ids,href
junit=ET.parse(HERE/'evidencia-seguridad-2026-10-23/junit.xml')
cases=junit.findall('.//testcase')
assert len(cases)==d['resumen']['automatizadas']==41
assert sum(int(t.attrib.get('assertions',0)) for t in cases)==d['resumen']['aserciones']==331
assert not junit.findall('.//failure') and not junit.findall('.//error') and not junit.findall('.//skipped')
human=[e for e in d['ejecuciones'] if e['id'].startswith('D4-T')]
assert len(human)==16 and sum(e['resultado']=='Coincide' for e in human)==14
assert sum(e['resultado']=='Parcial' for e in human)==2
before=hashlib.sha256((HERE/'CONTROL-INTEGRAL.html').read_bytes()).hexdigest()
subprocess.run(['python',str(HERE/'generar.py')],cwd=REPO,check=True,capture_output=True)
assert hashlib.sha256((HERE/'CONTROL-INTEGRAL.html').read_bytes()).hexdigest()==before,'Generación no determinista'
result={'fecha':'2026-10-23','catalogo_fuente_sin_editar':True,'campos_fuente_copiados_intactos':331,
        'aplicacion_y_tests_sin_editar':True,'ejecuciones':len(ids),'filas_html':p.rows,
        'enlaces_comprobados':len(p.links),'fragmentos_e_ids_validos':True,
        'generacion_determinista':True,'junit':{'aprobadas':41,'aserciones':331,'fallidas':0},
        'visual':'Chrome: búsqueda D4-T06, filtro Pendiente (342/443), restauración Todos (443/443) y captura visible revisadas; no revisión móvil.'}
(HERE/'evidencia-seguridad-2026-10-23/integridad.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print(json.dumps(result,ensure_ascii=False))
