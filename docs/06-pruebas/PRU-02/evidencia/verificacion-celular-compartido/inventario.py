from pathlib import Path
from PIL import Image,ImageDraw,ImageFont
import subprocess,json,hashlib

out=Path(__file__).parent
source=out.parent/'celular-compartido/todas'
views=subprocess.check_output(['git','grep','-l','filtros-actions','--','resources/views'],text=True).splitlines()
mapping={
'alumnos/create':'11-alumnos-create','alumnos/edit':'12-alumnos-edit','alumnos/index':'10-alumnos-index',
'caja/apertura':'15-caja-apertura','caja/cancelar-movimiento':'21-caja-cancelar-movimiento','caja/cierre':'16-caja-cierre','caja/cobrar-cuota':'06-cobrar-selector','caja/cobrar':'18-caja-cobrar-alumno','caja/configuracion':'19-caja-configuracion','caja/editar':'20-caja-editar','caja/historial':'17-caja-historial','caja/index':'14-caja-index','caja/movimiento':'04-caja-movimiento',
'cashflow/movimiento':'22-cashflow-movimiento','clases/create':'25-clases-create','clases/edit':'26-clases-edit','clases/index':'24-clases-index','cobranza/index':'23-cobranza-index','deportes/create':'28-deportes-create','deportes/edit':'29-deportes-edit',
'grupos/create':'46-grupos-create','grupos/edit':'47-grupos-edit','grupos/index':'05-grupos-index','grupos/show':None,'liquidaciones/create':'45-liquidaciones-create','movimientos/index':'09-movimientos-index','niveles/create':'31-niveles-create','niveles/edit':'32-niveles-edit','profesores/create':'34-profesores-create','profesores/edit':'35-profesores-edit','profesores/index':'33-profesores-index','profesores/show':'36-profesores-show','rubros/create':'48-rubros-create','rubros/edit':'49-rubros-edit','subrubros/create':'50-subrubros-create','subrubros/edit':'51-subrubros-edit','tipos-caja/create':'38-tipos-caja-create','tipos-caja/edit':'39-tipos-caja-edit','usuarios/create':'41-usuarios-create','usuarios/edit':'42-usuarios-edit'}
rows=[]
for v in views:
    key=v.removeprefix('resources/views/').removesuffix('.blade.php')
    ref=mapping[key]
    rows.append({'vista':v,'pantalla':ref,'capturas':bool(ref and all((source/(ref+s)).exists() for s in ['-escritorio.png','-celular-375.png']))})
(out/'cobertura.json').write_text(json.dumps(rows,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
images=sorted(source.glob('*-escritorio.png'))
assert len(images)==51
manifest=[]
analisis=out/'laminas';analisis.mkdir(exist_ok=True)
font=ImageFont.truetype('C:/Windows/Fonts/arial.ttf',18)
for start in range(0,51,4):
    sheet=Image.new('RGB',(1820,1450),'#ddd');d=ImageDraw.Draw(sheet)
    for n,p in enumerate(images[start:start+4]):
        stem=p.name.removesuffix('-escritorio.png');x=(n%2)*910;y=(n//2)*725
        d.text((x+8,y+4),stem+' — escritorio / 375',font=font,fill='black')
        desktop=Image.open(p).convert('RGB');mobile=Image.open(source/(stem+'-celular-375.png')).convert('RGB')
        sheet.paste(desktop.resize((640,450)),(x+5,y+32))
        # Solo recorta el marco gris para inspeccion interna; las originales permanecen intactas.
        sheet.paste(mobile.crop((112,0,488,1000)).resize((250,665)),(x+655,y+32))
        for f in [p,source/(stem+'-celular-375.png')]:
            manifest.append({'archivo':str(f).replace('\\','/'),'sha256':hashlib.sha256(f.read_bytes()).hexdigest(),'lamina':start//4+1})
    sheet.save(analisis/f'{start//4+1:02}.jpg',quality=94)
(out/'originales-inspeccion.json').write_text(json.dumps(manifest,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print(json.dumps({'vistas':len(rows),'cubiertas':sum(r['capturas'] for r in rows),'sin_captura':[r['vista'] for r in rows if not r['capturas']],'imagenes':len(manifest),'laminas':13}))
