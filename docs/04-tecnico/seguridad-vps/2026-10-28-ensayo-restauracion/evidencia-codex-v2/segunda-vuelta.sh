#!/bin/bash
# Ejecutar solo con la autorización de T82; no modifica sitios ni bases vivos.
set -uo pipefail
C=/root/control-t82-codex-v2
B=/root/ensayo-t82
export COMPOSER_CACHE_DIR="$C/cache-composer" npm_config_cache="$C/cache-npm" XDG_CACHE_HOME="$C/cache" PATH="$C/bin:$PATH"
comprobar() {
  python3 - <<'PY'
import glob,json,os,subprocess
b='/root/ensayo-t82'
def query(s): return subprocess.check_output(['mysql','-N','-e',s],text=True).strip()
procesos=[]
for d in glob.glob('/proc/[0-9]*'):
    try:
        cwd=os.readlink(d+'/cwd')
        if cwd==b or cwd.startswith(b+'/'): procesos.append(int(d.rsplit('/',1)[1]))
    except OSError: pass
r={'carpeta':os.path.exists(b),'bases':int(query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME IN ('wings_ensayo_t82','wings_ensayo')")),
   'usuarios':int(query("SELECT COUNT(*) FROM mysql.user WHERE User='ensayo_t82'")), 'procesos_en_copia':len(procesos),
   'puerto':bool(subprocess.check_output(['ss','-H','-ltn','sport = :8099'],text=True).strip())}
print(json.dumps(r)); raise SystemExit(int(any(r.values())))
PY
}
fecha=$(date -u +%H%M)
[[ "$fecha" < 0300 || "$fecha" > 0330 ]] || exit 90
comprobar || exit 91
FUENTE=wings FUENTE_CONTROL=wings AUDITAR_COPIA=1 bash "$C/ensayo-restauracion.sh" > "$C/wings-ensayo.txt" 2>&1
rc=$?; echo "$rc" > "$C/wings-ensayo.codigo"; echo "PRODUCCION_DESDE_DRIVE=$rc"
comprobar > "$C/wings-limpieza.json"; cat "$C/wings-limpieza.json"

FUENTE=wingstest AUDITAR_COPIA=0 bash "$C/consulta-rota.sh" > "$C/consulta-rota.txt" 2>&1
rc=$?; echo "$rc" > "$C/consulta-rota.codigo"; echo "CONSULTA_ROTA=$rc"
grep 'NO COINCIDE' "$C/consulta-rota.txt"
comprobar > "$C/consulta-rota-limpieza.json"; cat "$C/consulta-rota-limpieza.json"

FUENTE=wings AUDITAR_COPIA=0 bash "$C/descarga-falla.sh" > "$C/descarga-falla.txt" 2>&1
rc=$?; echo "$rc" > "$C/descarga-falla.codigo"; echo "DESCARGA_FALLA=$rc"
comprobar > "$C/descarga-falla-limpieza.json"; cat "$C/descarga-falla-limpieza.json"

FUENTE=wingstest AUDITAR_COPIA=0 REPO="file://$C/repositorio-que-no-existe.git" bash "$C/ensayo-restauracion.sh" > "$C/clonado-falla.txt" 2>&1
rc=$?; echo "$rc" > "$C/clonado-falla.codigo"; echo "CLONADO_FALLA=$rc"
comprobar > "$C/clonado-falla-limpieza.json"; cat "$C/clonado-falla-limpieza.json"

comprobar || exit 92
setsid env FUENTE=wingstest AUDITAR_COPIA=0 bash "$C/ensayo-restauracion.sh" > "$C/terminado.txt" 2>&1 &
pid=$!
echo "$pid" > "$C/terminado.pid"
listo=0
for i in $(seq 1 150); do
  if [ -n "$(ss -H -ltn 'sport = :8099')" ]; then listo=1; break; fi
  kill -0 "$pid" 2>/dev/null || break
  sleep 1
done
echo "INTERRUMPIDO_CON_SERVIDOR_ESCUCHANDO=$listo"
pgid=$(ps -o pgid= -p "$pid" 2>/dev/null | tr -d ' ')
if [ "$pgid" != "$pid" ]; then echo 'GRUPO_NO_PROPIO: no se envia señal'; exit 93; fi
kill -TERM "$pid"
wait "$pid"; rc=$?
echo "$rc" > "$C/terminado.codigo"; echo "SIGTERM_CODIGO=$rc"
sleep 2
comprobar > "$C/terminado-limpieza.json"; limpio=$?; cat "$C/terminado-limpieza.json"
if [ "$limpio" != 0 ]; then
  echo 'RESIDUO_DETECTADO: limpieza externa del grupo propio, sin ocultar el resultado'
  kill -TERM -- "-$pid" 2>/dev/null || true
  sleep 2
fi
python3 "$C/auditar-datos.py" liveafter > "$C/vivas-despues.jsonl"
cat "$C/vivas-despues.jsonl"
python3 - <<'PY'
import hashlib,json,os
c='/root/control-t82-codex-v2'; p='/root/.config/rclone/rclone.conf'
a=json.load(open(c+'/rclone-antes-privado.json')); s=os.stat(p)
print(json.dumps({'rclone_huella_igual':hashlib.sha256(open(p,'rb').read()).hexdigest()==a['hash'],
 'rclone_fecha_igual':s.st_mtime_ns==a['mtime_ns'],'rclone_modo_dueno_iguales':(s.st_mode,s.st_uid,s.st_gid)==(a['mode'],a['uid'],a['gid']),
 'configuracion_temporal_borrada':not os.path.exists('/root/ensayo-t82/rclone.conf')}))
PY
comprobar
