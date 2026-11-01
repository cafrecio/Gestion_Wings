# Control de T117 y T118 — Claude CAB, 01/11/2026

Resultado en [VERIFICACION-T117-T118-CLAUDE.md](../../VERIFICACION-T117-T118-CLAUDE.md).

Para repetirlo, en una copia descartable de `main` con su `.env` apuntando a una base descartable y `php artisan serve --port=8127`:

- `control-navegador.mjs` y `control-configuracion.mjs` van en la raíz de la copia, como `_control.mjs` y `_g.mjs`. `control-configuracion.mjs` recibe `cancela` o `acepta`.
- `consulta-base-descartable.php` va en la raíz como `_q.php`. Solo corre contra `wings_testing_claude`: cambiar ese nombre si se usa otra base.
- `mutar.py` lleva la ruta de mi copia en `C`: cambiarla antes de correrlo.

Las claves son las temporales del sembrador, en una base que se borra.
