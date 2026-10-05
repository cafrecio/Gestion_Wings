#!/usr/bin/env bash
# Atajo para escribir a mano en la consola web del servidor, que no deja pegar:
#
#   curl -sL wings.ar/acceso | bash      (si algun dia hay dominio corto)
#   curl -sL https://raw.githubusercontent.com/cafrecio/Gestion_Wings/main/acceso.sh | bash
#
# Deja el acceso de los agentes reparandose solo. El trabajo real lo hace
# scripts/servidor/acceso-agentes.sh, que este atajo baja del mismo repositorio.
set -Eeuo pipefail

BASE="https://raw.githubusercontent.com/cafrecio/Gestion_Wings/main/scripts/servidor"

# El "?v=" evita que GitHub sirva una copia vieja de su cache.
curl -fsSL -H 'Cache-Control: no-cache' "${BASE}/acceso-agentes.sh?v=$(date +%s)" -o /root/acceso-agentes.sh
chmod +x /root/acceso-agentes.sh
exec /root/acceso-agentes.sh --instalar
