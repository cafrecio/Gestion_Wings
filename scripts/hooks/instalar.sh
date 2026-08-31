#!/bin/bash
# Instala los hooks de Wings en esta maquina.
# Los hooks viven en .git/ y no se versionan: hay que correr esto una vez por computadora.

set -e
RAIZ="$(git rev-parse --show-toplevel)"

install -m 755 "$RAIZ/scripts/hooks/commit-msg" "$RAIZ/.git/hooks/commit-msg"
echo "  commit-msg instalado — guardia del diseno activa"

if [ -f "$RAIZ/.git/hooks/pre-commit" ]; then
    mv "$RAIZ/.git/hooks/pre-commit" "$RAIZ/.git/hooks/pre-commit.disabled"
    echo "  pre-commit viejo desactivado — exportaba la base en cada commit"
fi

echo ""
echo "  Listo. Probalo: un commit que toque resources/views sin declararlo debe fallar."
