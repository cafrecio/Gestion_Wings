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

curl -fsSL "${BASE}/acceso-agentes.sh" -o /root/acceso-agentes.sh
chmod +x /root/acceso-agentes.sh
exec /root/acceso-agentes.sh --instalar
