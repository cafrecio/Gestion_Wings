from pathlib import Path
import subprocess, json, hashlib

raiz = Path(__file__).resolve().parents[5]
destino = raiz / 'storage/app/t65-segunda-vuelta/13b85f68'
archivos = subprocess.check_output(['git', 'ls-tree', '-r', '--name-only', '13b85f68', 'app'], cwd=raiz, text=True).splitlines()
huellas = {}
for nombre in archivos:
    contenido = subprocess.check_output(['git', 'show', '13b85f68:' + nombre], cwd=raiz)
    archivo = destino / nombre
    archivo.parent.mkdir(parents=True, exist_ok=True)
    archivo.write_bytes(contenido)
    huellas[nombre] = hashlib.sha256(contenido).hexdigest()
(Path(__file__).parent / 'contraprueba-fuentes.json').write_text(json.dumps({'commit': '13b85f68', 'app_original': huellas}, indent=2), encoding='utf-8')
print('Copia aislada de app en storage, sin modificar producto:', len(archivos), 'archivos')
