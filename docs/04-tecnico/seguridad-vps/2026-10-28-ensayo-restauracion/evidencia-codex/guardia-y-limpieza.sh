#!/bin/bash
set -uo pipefail
printf 'CONTROL_UTC ';date -u +%FT%TZ
printf 'LOAD ';cat /proc/loadavg
for d in /root/control-t82 /root/ensayo-t82;do [ ! -e "$d" ] || { echo "NOMBRE_OCUPADO $d";exit 73;};done
[ "$(mysql -N -e "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name IN ('wings_ensayo_t82','wings_ensayo')")" = 0 ] || { echo BASE_OCUPADA;exit 73;}
[ "$(mysql -N -e "SELECT COUNT(*) FROM mysql.user WHERE User='ensayo_t82'")" = 0 ] || { echo USUARIO_OCUPADO;exit 73;}
[ "$(ss -ltnH '( sport = :8099 )' | wc -l)" = 0 ] || { echo PUERTO_OCUPADO;exit 73;}
[ "$(find /tmp -maxdepth 1 -type d -name 'restaurar-wings-*' | wc -l)" = 0 ] || { echo TEMP_PREVIO;exit 73;}
h=$(date -u +%H%M);if [[ "$h" > 0259 && "$h" < 0330 ]];then echo VENTANA_RESPALDO;exit 75;fi
awk '{exit !($1<1.0)}' /proc/loadavg || { echo CARGA_ALTA;exit 75;}
[ "$(mysql -N -e 'SELECT COUNT(*) FROM wingstest.sessions WHERE last_activity BETWEEN UNIX_TIMESTAMP()-600 AND UNIX_TIMESTAMP()')" = 0 ] || { echo SESIONES_RECIENTES;exit 75;}
for log in /usr/local/apache/domlogs/test.gestionar-te.com.ar.log /usr/local/apache/domlogs/test.gestionar-te.com.ar.ssl.log;do
 [ -f "$log" ] || { echo SIN_REGISTRO_ACCESO;exit 75;}
 age=$(( $(date +%s)-$(stat -c %Y "$log") ));echo "ACCESO_EDAD_SEGUNDOS $age";[ "$age" -gt 600 ] || { echo ACTIVIDAD_RECIENTE;exit 75;}
done
mkdir -m 700 /root/control-t82
cleanup(){
 rc=$?;trap - EXIT HUP INT TERM
 echo "CONTROL_SALIDA_ORIGINAL $rc"
 python3 - <<'PYCLEAN'
import os,signal,time
from pathlib import Path
mine=os.getpid();parents={os.getppid(),mine}
for p in Path('/proc').glob('[0-9]*'):
 try:
  pid=int(p.name);cwd=os.readlink(str(p/'cwd'))
  if pid not in parents and (cwd.startswith('/root/ensayo-t82/') or cwd.startswith('/root/control-t82/')):
   os.kill(pid,signal.SIGTERM);print('PROCESO_ENSAYO_TERMINADO',pid)
 except (OSError,ValueError):pass
time.sleep(1)
PYCLEAN
 if [ -f /root/control-t82/antes-privado.json ];then python3 /root/control-t82/auditar-datos.py liveafter || rc=81;fi
 mysql -e "DROP DATABASE IF EXISTS wings_ensayo_t82;DROP DATABASE IF EXISTS wings_ensayo;DROP USER IF EXISTS 'ensayo_t82'@'localhost';" || rc=82
 for d in /root/ensayo-t82 /root/control-t82;do if [ -e "$d" ];then [ "$(realpath "$d")" = "$d" ] && [ ! -L "$d" ] && rm -rf -- "$d" || rc=83;fi;done
 echo LIMPIEZA_COMPROBADA
 for d in /root/ensayo-t82 /root/control-t82;do [ ! -e "$d" ] && echo "CARPETA_AUSENTE $d" || rc=84;done
 mysql -N -e "SELECT 'bases_residuales',COUNT(*) FROM information_schema.schemata WHERE schema_name IN ('wings_ensayo_t82','wings_ensayo');SELECT 'usuarios_residuales',COUNT(*) FROM mysql.user WHERE User='ensayo_t82';"
 printf 'PUERTO_8099_RESIDUAL ';ss -ltnH '( sport = :8099 )' | wc -l
 printf 'TEMP_RESTAURAR_RESIDUAL ';find /tmp -maxdepth 1 -type d -name 'restaurar-wings-*' | wc -l
 exit "$rc"
}
trap cleanup EXIT HUP INT TERM
mkdir -p /root/control-t82/tmp
export COMPOSER_HOME=/root/control-t82/composer-home COMPOSER_CACHE_DIR=/root/control-t82/composer-cache npm_config_cache=/root/control-t82/npm-cache RCLONE_CACHE_DIR=/root/control-t82/rclone-cache TMPDIR=/root/control-t82/tmp
printf %s aW1wb3J0IHN5cyxqc29uLHN1YnByb2Nlc3MsaGFzaGxpYg0KZnJvbSBwYXRobGliIGltcG9ydCBQYXRoDQoNCmRlZiBxKHMpOnJldHVybiAnYCcrcy5yZXBsYWNlKCdgJywnYGAnKSsnYCcNCmRlZiBzcWwocyk6DQogcD1zdWJwcm9jZXNzLnJ1bihbJ215c3FsJywnLU4nLCctQicsJy0tcmF3JywnLWUnLHNdLGNhcHR1cmVfb3V0cHV0PVRydWUpDQogaWYgcC5yZXR1cm5jb2RlOnJhaXNlIFJ1bnRpbWVFcnJvcignQ29uc3VsdGEgZmFsbG86ICcrcC5zdGRlcnIuZGVjb2RlKGVycm9ycz0ncmVwbGFjZScpWzozMDBdKQ0KIHJldHVybiBwLnN0ZG91dC5kZWNvZGUoJ3V0Zi04JykNCmRlZiBtZXRhKGRiKToNCiByZXR1cm4gW2pzb24ubG9hZHMoeCkgZm9yIHggaW4gc3FsKCJTRUxFQ1QgSlNPTl9BUlJBWShUQUJMRV9OQU1FLENPTFVNTl9OQU1FLENPTFVNTl9UWVBFLElTX05VTExBQkxFLERBVEFfVFlQRSkgRlJPTSBpbmZvcm1hdGlvbl9zY2hlbWEuY29sdW1ucyBXSEVSRSB0YWJsZV9zY2hlbWE9JyIrZGIrIicgT1JERVIgQlkgVEFCTEVfTkFNRSxPUkRJTkFMX1BPU0lUSU9OIikuc3BsaXRsaW5lcygpXQ0KZGVmIHNjYW4oZGIpOg0KIHRhYmxlcz17fQ0KIGZvciB0LGMsdHlwLG51bGwsZHQgaW4gbWV0YShkYik6dGFibGVzLnNldGRlZmF1bHQodCxbXSkuYXBwZW5kKFtjLHR5cCxudWxsLGR0XSkNCiBvdXQ9e30NCiBmb3IgdCxjb2xzIGluIHRhYmxlcy5pdGVtcygpOg0KICBleHByPScsJy5qb2luKCdJRignK3EoYykrJyBJUyBOVUxMLE5VTEwsSEVYKENBU1QoJytxKGMpKycgQVMgQklOQVJZKSkpJyBmb3IgYywqXyBpbiBjb2xzKQ0KICByb3dzPXNvcnRlZChzcWwoJ1NFTEVDVCBKU09OX0FSUkFZKCcrZXhwcisnKSBGUk9NICcrcShkYikrJy4nK3EodCkpLnNwbGl0bGluZXMoKSkNCiAgZGlnZXN0PWhhc2hsaWIuc2hhMjU2KCgnXG4nLmpvaW4ocm93cykpLmVuY29kZSgpKS5oZXhkaWdlc3QoKQ0KICBvdXRbdF09eydjb2x1bW5hcyc6Y29scywnZmlsYXMnOmxlbihyb3dzKSwncHJpdmFkb19jb250ZW5pZG8nOmRpZ2VzdH0NCiByZXR1cm4gb3V0DQoNCmRlZiBiYXNlbGluZSgpOg0KIGRhdGE9e2RiOnNjYW4oZGIpIGZvciBkYiBpbiAoJ3dpbmdzJywnd2luZ3N0ZXN0Jyl9DQogUGF0aCgnL3Jvb3QvY29udHJvbC10ODIvYW50ZXMtcHJpdmFkby5qc29uJykud3JpdGVfdGV4dChqc29uLmR1bXBzKGRhdGEpKQ0KIHByaW50KGpzb24uZHVtcHMoeydjb250cm9sJzonZXN0YWRvX3Zpdm9faW5pY2lhbCcsJ3RhYmxhcyc6e2s6bGVuKHYpIGZvciBrLHYgaW4gZGF0YS5pdGVtcygpfX0pKQ0KZGVmIGxpdmVhZnRlcigpOg0KIGJlZm9yZT1qc29uLmxvYWRzKFBhdGgoJy9yb290L2NvbnRyb2wtdDgyL2FudGVzLXByaXZhZG8uanNvbicpLnJlYWRfdGV4dCgpKTtyZXN1bHRzPXt9O2JhZD1GYWxzZQ0KIGZvciBkYixvbGQgaW4gYmVmb3JlLml0ZW1zKCk6DQogIG5vdz1zY2FuKGRiKTtjaGFuZ2VkPVt0IGZvciB0IGluIHNvcnRlZChzZXQob2xkKXxzZXQobm93KSkgaWYgb2xkLmdldCh0KSE9bm93LmdldCh0KV0NCiAgcmVzdWx0c1tkYl09eyd0YWJsYXMnOmxlbihub3cpLCd0YWJsYXNfY2FtYmlhZGFzJzpjaGFuZ2VkLCdzaW5fY2FtYmlvcyc6bm90IGNoYW5nZWR9O2JhZHw9Ym9vbChjaGFuZ2VkKQ0KIHByaW50KGpzb24uZHVtcHMoeydjb250cm9sJzonYmFzZXNfdml2YXNfZGVzcHVlcycsJ3Jlc3VsdGFkbyc6cmVzdWx0c30pKTtyZXR1cm4gaW50KGJhZCkNCmRlZiBjb21wYXJlKHNyYyxkc3QpOg0KIGEsYj1zY2FuKHNyYyksc2Nhbihkc3QpO3Jlc3VsdD1bXTtiYWQ9RmFsc2UNCiBmb3IgdCBpbiBzb3J0ZWQoc2V0KGEpfHNldChiKSk6DQogIHgseT1hLmdldCh0KSxiLmdldCh0KTtzYW1lPXg9PXk7YmFkfD1ub3Qgc2FtZQ0KICByZXN1bHQuYXBwZW5kKHsndGFibGEnOnQsJ2ZpbGFzX3ZpdmEnOnhbJ2ZpbGFzJ10gaWYgeCBlbHNlIE5vbmUsJ2ZpbGFzX2NvcGlhJzp5WydmaWxhcyddIGlmIHkgZWxzZSBOb25lLCdjb2x1bW5hc19pZGVudGljYXMnOmJvb2woeCBhbmQgeSBhbmQgeFsnY29sdW1uYXMnXT09eVsnY29sdW1uYXMnXSksJ2NvbnRlbmlkb19jb21wbGV0b19pZGVudGljbyc6Ym9vbCh4IGFuZCB5IGFuZCB4Wydwcml2YWRvX2NvbnRlbmlkbyddPT15Wydwcml2YWRvX2NvbnRlbmlkbyddKX0pDQogcHJpbnQoanNvbi5kdW1wcyh7J2NvbnRyb2wnOidjb21wYXJhY2lvbl9pbmRlcGVuZGllbnRlX3RvZGFzX2xhc190YWJsYXMnLCd2aXZhJzpzcmMsJ2NvcGlhJzpkc3QsJ3RhYmxhcyc6cmVzdWx0LCdkaWZlcmVuY2lhcyc6c3VtKG5vdCh4LmdldCgnY29sdW1uYXNfaWRlbnRpY2FzJykgYW5kIHguZ2V0KCdjb250ZW5pZG9fY29tcGxldG9faWRlbnRpY28nKSkgZm9yIHggaW4gcmVzdWx0KX0pKQ0KIGltcG9ydGFudD17J3BhZ29zJywncGFnb19kZXVkYV9jdW90YScsJ2RldWRhX2N1b3RhcycsJ21vdmltaWVudG9zX29wZXJhdGl2b3MnLCdjYXNoZmxvd19tb3ZpbWllbnRvcycsJ2xpcXVpZGFjaW9uZXMnLCdsaXF1aWRhY2lvbl9kZXRhbGxlcycsJ2Nhcmdvc19hbHVtbm8nLCdjYWphc19vcGVyYXRpdmFzJ30NCiBtb25leT1bXQ0KIGZvciB0IGluIHNvcnRlZChpbXBvcnRhbnQgJiBzZXQoYSkgJiBzZXQoYikpOg0KICBmb3IgYyx0eXAsbnVsbCxkdCBpbiBhW3RdWydjb2x1bW5hcyddOg0KICAgaWYgZHQgbm90IGluICgnZGVjaW1hbCcsJ2RvdWJsZScsJ2Zsb2F0Jyk6Y29udGludWUNCiAgIHF1ZXJ5PSdTRUxFQ1QgSlNPTl9BUlJBWShDT1VOVCgnK3EoYykrJyksQ0FTVChTVU0oQ0FTVCgnK3EoYykrJyBBUyBERUNJTUFMKDQwLDYpKSkgQVMgQ0hBUikpIEZST00ge2RifS4nK3EodCkNCiAgIHg9anNvbi5sb2FkcyhzcWwocXVlcnkuZm9ybWF0KGRiPXEoc3JjKSkpKTt5PWpzb24ubG9hZHMoc3FsKHF1ZXJ5LmZvcm1hdChkYj1xKGRzdCkpKSk7c2FtZT14PT15O2JhZHw9bm90IHNhbWUNCiAgIG1vbmV5LmFwcGVuZCh7J3RhYmxhJzp0LCdjb2x1bW5hJzpjLCd2YWxvcmVzX25vX251bG9zJzppbnQoeFswXSksJ3N1bWFfdml2YSc6eFsxXSwnc3VtYV9jb3BpYSc6eVsxXSwnaWd1YWwnOnNhbWUsJ2V2aWRlbmNpYV9tb25ldGFyaWEnOmludCh4WzBdKT4wfSkNCiBwcmludChqc29uLmR1bXBzKHsnY29udHJvbCc6J3BsYXRhX2luZGVwZW5kaWVudGVfZGVjaW1hbF9zaW5fZmxvYXRzX3B5dGhvbicsJ2NvbXBhcmFjaW9uZXMnOm1vbmV5fSkpDQogcmV0dXJuIGludChiYWQpDQpkZWYgZmlsZXMoc3JjLGRzdCk6DQogaW1wb3J0IG9zDQogZGVmIGludmVudG9yeShiYXNlKToNCiAgcmVzdWx0PXt9DQogIGZvciBwIGluIFBhdGgoYmFzZSkucmdsb2IoJyonKToNCiAgIGlmIHAuaXNfZmlsZSgpOnJlc3VsdFtzdHIocC5yZWxhdGl2ZV90byhiYXNlKSldPWhhc2hsaWIuc2hhMjU2KHAucmVhZF9ieXRlcygpKS5oZXhkaWdlc3QoKQ0KICByZXR1cm4gcmVzdWx0DQogYSxiPWludmVudG9yeShzcmMpLGludmVudG9yeShkc3QpO2RpZmY9W3ggZm9yIHggaW4gc2V0KGEpfHNldChiKSBpZiBhLmdldCh4KSE9Yi5nZXQoeCldDQogcHJpbnQoanNvbi5kdW1wcyh7J2NvbnRyb2wnOidhcmNoaXZvc19zdWJpZG9zX2NvbXBsZXRvcycsJ2FyY2hpdm9zX3Zpdm9zJzpsZW4oYSksJ2FyY2hpdm9zX3Jlc3RhdXJhZG9zJzpsZW4oYiksJ2NvbnRlbmlkb3NfaWd1YWxlcyc6bm90IGRpZmYsJ2RpZmVyZW5jaWFzJzpsZW4oZGlmZil9KSk7cmV0dXJuIGludChib29sKGRpZmYpKQ0KaWYgX19uYW1lX189PSdfX21haW5fXyc6DQogdHJ5Og0KICBhY3Rpb249c3lzLmFyZ3ZbMV07Y29kZT1iYXNlbGluZSgpIGlmIGFjdGlvbj09J2Jhc2VsaW5lJyBlbHNlIGxpdmVhZnRlcigpIGlmIGFjdGlvbj09J2xpdmVhZnRlcicgZWxzZSBmaWxlcyhzeXMuYXJndlsyXSxzeXMuYXJndlszXSkgaWYgYWN0aW9uPT0nZmlsZXMnIGVsc2UgY29tcGFyZShzeXMuYXJndlsyXSxzeXMuYXJndlszXSk7c3lzLmV4aXQoY29kZSBvciAwKQ0KIGV4Y2VwdCBFeGNlcHRpb24gYXMgZXhjOnByaW50KGpzb24uZHVtcHMoeydjb250cm9sJzonZXJyb3InLCd0aXBvJzp0eXBlKGV4YykuX19uYW1lX18sJ2RldGFsbGUnOnN0cihleGMpfSkpO3N5cy5leGl0KDEpDQo= | base64 -d > /root/control-t82/auditar-datos.py
python3 /root/control-t82/auditar-datos.py baseline || exit 80
