from pathlib import Path
import hashlib
import json
import re

root=Path(__file__).resolve().parent
repo=root.parents[3]
antes=json.loads((root/'mediciones-antes.json').read_text(encoding='utf8'))['casos']
despues=json.loads((root/'mediciones-despues.json').read_text(encoding='utf8'))['casos']
assert len(antes)==15 and len(despues)==297
assert not any(c['desborde'] for c in despues)
for c in despues:
    if c['ancho']>=640:
        continue
    if 'liquidaciones/show.blade.php' in '|'.join(c['vistas']):
        assert len({(v['font'],v['color']) for v in c['importes']})<=1
    if c['slug']=='admin-cashflow-movimientos-id-eliminar':
        cuerpo=c['cuerpos'][0]
        assert cuerpo['padding']=='0px'
        assert abs(cuerpo['rect']['derecha']-cuerpo['accion']['derecha'])<1
    if c['slug']=='admin-clases-create':
        campos={f['id']:f for f in c['campos']}
        assert len({campos[k]['rect']['w'] for k in ['fecha','grupo_id','hora_inicio','hora_fin']})==1
for f,h in json.loads((root/'fuentes-suite.json').read_text(encoding='utf8'))['fuentes'].items():
    assert hashlib.sha256((repo/f).read_bytes().replace(b'\r\n',b'\n')).hexdigest()==h, f
png=json.loads((root/'capturas-manifiesto.json').read_text(encoding='utf8'))
for foto in png:
    assert hashlib.sha256((root/foto['archivo']).read_bytes()).hexdigest()==foto['sha256']
galeria=(root/'GALERIA.html').read_text(encoding='utf8')
assert len(re.findall(r'<section id=',galeria))==5
for doc in [repo/'docs/06-pruebas/PRU-04/IMPLEMENTACION-T75-OBSERVACIONES.md', repo/'docs/03-diseno-ui/design-system/DESIGN-RULES.md']:
    texto=doc.read_text(encoding='utf8')
    for link in re.findall(r'\]\(([^)]+)\)',texto):
        if re.match(r'https?://|#',link):continue
        target=link.split('#')[0]
        assert (doc.parent/target).exists(), (doc,link)
comision=json.loads((root/'mediciones-comision.json').read_text(encoding='utf8'))['casos']
assert len(comision)==3 and not any(c['desborde'] for c in comision)
for c in comision: assert len({(v['font'],v['color']) for v in c['importes']})<=1
resultado={'comision':len(comision),'mediciones':len(despues),'comparaciones_anteriores':len(antes),'desbordes':0,'capturas_comprobadas':len(png),'pantallas_en_galeria':5,'fuentes_iguales_a_suite':True,'enlaces':True}
(root/'integridad.json').write_text(json.dumps(resultado,indent=2),encoding='utf8')
print(json.dumps(resultado))
