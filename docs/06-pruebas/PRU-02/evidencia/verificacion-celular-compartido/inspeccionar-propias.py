from pathlib import Path
from PIL import Image, ImageDraw, ImageFont
import json, hashlib
p=Path(__file__).parent
files=sorted((p/'capturas').glob('*.jpg'))
out=p/'laminas-propias';out.mkdir(exist_ok=True)
font=ImageFont.truetype('C:/Windows/Fonts/arial.ttf',16)
for start in range(0,len(files),6):
    sheet=Image.new('RGB',(1530,1150),'#ddd');d=ImageDraw.Draw(sheet)
    for i,f in enumerate(files[start:start+6]):
        x=i%3*510;y=i//3*575;im=Image.open(f).convert('RGB')
        d.text((x+8,y+5),f.stem,font=font,fill='black')
        if 'desktop' in f.stem: im.thumbnail((500,530))
        else: im=im.crop((452,0,828,1000));im.thumbnail((500,530))
        sheet.paste(im,(x+int((510-im.width)/2),y+32))
    sheet.save(out/f'{start//6+1:02}.jpg',quality=95)
manifest=[{'archivo':f.name,'sha256':hashlib.sha256(f.read_bytes()).hexdigest(),'dimensiones':Image.open(f).size,'lamina':i//6+1} for i,f in enumerate(files)]
(p/'propias-inspeccion.json').write_text(json.dumps(manifest,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print(json.dumps({'capturas':len(files),'laminas':(len(files)+5)//6}))
