#!/bin/bash
# Inspecciona antes del curl real, para comparar sesiones sin crear una propia.
if [[ "$*" = *127.0.0.1:8099/login* ]] && [ "${AUDITAR_COPIA:-0}" = 1 ]; then
  bash /root/control-t82-codex-v2/inspeccionar-copia.sh >&2
fi
exec /usr/bin/curl "$@"
