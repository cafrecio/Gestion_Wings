from pathlib import Path
import hashlib
import json
import re
import subprocess

root=Path(__file__).resolve().parent
repo=root.parents[3]
actual=json.loads((root/'fuentes.json').read_text(encoding='utf8'))
suite=json.loads((root/'fuentes-suite.json').read_text(encoding='utf8'))
diferencias=[f for f in actual['fuentes'] if actual['fuentes'][f]!=suite['fuentes'][f]]
assert diferencias==['resources/css/app.css']
for f,h in actual['fuentes'].items():
    assert hashlib.sha256((repo/f).read_bytes().replace(b'\r\n',b'\n')).hexdigest()==h,f
for f in actual['expresiones_y_bloques_php_identicos']:
    anterior=subprocess.check_output(['git','show',actual['base']+':'+f],cwd=repo).decode('utf8')
    nuevo=(repo/f).read_text(encoding='utf8')
    assert re.findall(r'{{.*?}}',anterior,re.S)==re.findall(r'{{.*?}}',nuevo,re.S),f
    assert re.findall(r'@php(?!\s*\().*?@endphp',anterior,re.S)==re.findall(r'@php(?!\s*\().*?@endphp',nuevo,re.S),f
    assert actual['fuentes'][f]==suite['fuentes'][f]

controles=0
for archivo in ['jerarquia.json','jerarquia-negativos.json']:
    casos=json.loads((root/archivo).read_text(encoding='utf8'))['resultados']
    assert len(casos)==63
    for c in casos:
        assert not c['antes'] and not c['despues'] and not c['fuera'],(archivo,c['slug'],c['ancho'])
        for e in c['estilos']:
            assert abs(e['tamano']-{'total':17.6,'summary':14.4,'row':12.8}[e['nivel']])<0.1
            assert e['peso']=={'total':'800','summary':'700','row':'600'}[e['nivel']]
    controles+=len(casos)

casos=json.loads((root/'mediciones-despues.json').read_text(encoding='utf8'))['casos']
assert len(casos)==297 and not any(c['desborde'] for c in casos)
firmas={}
for c in casos:
    for e in c['cifras']:
        firmas.setdefault(e['nivel'],set()).add((e['tamano'],e['peso'],e['fuente'],e['linea']))
assert len(firmas)==3 and all(len(v)==1 for v in firmas.values())
png=json.loads((root/'capturas-manifiesto.json').read_text(encoding='utf8'))
for foto in png:
    assert hashlib.sha256((root/foto['archivo']).read_bytes()).hexdigest()==foto['sha256']
galeria=(root/'GALERIA.html').read_text(encoding='utf8')
assert len(re.findall('<section id=',galeria))==5
for doc in [repo/'docs/06-pruebas/PRU-04/IMPLEMENTACION-T75-JERARQUIA.md',repo/'docs/03-diseno-ui/design-system/DESIGN-RULES.md']:
    for link in re.findall(r'\]\(([^)]+)\)',doc.read_text(encoding='utf8')):
        if re.match(r'https?://|#',link):continue
        assert (doc.parent/link.split('#')[0]).exists(),(doc,link)
estado=json.loads((repo/'docs/00-estado/tareas.json').read_text(encoding='utf8'))
assert all(next(t for t in estado if t['id']==i)['estado']=='cerrado' for i in ['T36','T37'])
resultado={'controles_de_jerarquia_y_espacio':controles,'recorrido_general':len(casos),'firmas_por_nivel':{k:len(v) for k,v in firmas.items()},'capturas':len(png),'vistas_en_galeria':5,'fuentes_y_expresiones':True,'php_final_identico_a_suite':True,'ajuste_css_final_documentado':True,'enlaces':True,'t36_t37_cerradas':True}
(root/'integridad.json').write_text(json.dumps(resultado,indent=2),encoding='utf8')
print(json.dumps(resultado))
