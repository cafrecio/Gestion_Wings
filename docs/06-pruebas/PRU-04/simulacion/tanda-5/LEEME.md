# Tanda 5 (días 48 a 57, 26/11 al 5/12): simulada — Claude CAB, 01/11/2026

**El guion es [GUION-DIAS-48-57.md](../../GUION-DIAS-48-57.md)**; el prompt, [PROMPT-CODEX-DIAS-48-57.md](../../PROMPT-CODEX-DIAS-48-57.md).

**Todavía no se entrega:** falta T100 y desplegar. El sitio sigue en `6214fd4a`. `ano.php` es la prueba del paso de año, aparte: corre sobre la foto del 57 después de lanzar la generación del 1/1/2027.

## Cómo retomar

1. Bajar la base del sitio al 25/11 (solo sirve mientras el reloj siga ahí): `ssh vps "mysqldump --single-transaction wingstest | gzip" > fotos/d47.sql.gz`.
2. Restaurarla en `wings_testing_guion`, correr `php artisan migrate --force` con esa base y guardar la foto como `fotos/d47m.sql.gz`.
3. `python -X utf8 -I generar_rutinas.py` escribe las rutinas. `bash correr.sh 48`, después 49 … 57. Cada día arranca de la foto del anterior. Las fotos no se suben.
4. Las salidas quedan en `salida-*.txt` y los cierres en `cierres-48-57.txt`.

## Qué apareció

- **T110:** guardar un grupo con la pantalla vieja borra la frecuencia que agregó la otra pestaña, sin aviso. GRU-005 pide rechazarlo. Ejecutado (48.4).
- **T111:** quien tiene una ficha por deporte queda presente en dos clases a la misma hora; el control mira la ficha y no la persona. ASI-008 pide rechazarlo. Ejecutado (53.4).
- **T112:** un presente no saca a nadie de Revisión. CBZ-016 a pide que sí. Ya estaba anotado en el catálogo el 13/10, sin tarea. Ejecutado (53.4).
- **Liquidación de $0:** generar la de quien no tuvo nada deja una liquidación vacía, sin aviso (53.9). Anotado.
- **T113:** Validar se ofrece en una clase que todavía no ocurrió y después no se puede deshacer (56.8). CLA-024 lo describe desde el 13/10; T35 no lo tocó.
- **T114:** Reportes → Sueldos no refleja lo pagado: Mariela $342.000 contra $361.000 y Hernán $561.000 contra $561.100 (55.4).
- **T115:** al paso de año, Nueva liquidación propone enero de 2027; quien cambia solo el mes genera una liquidación de diciembre de 2027 vacía, sin aviso (ano.php).
- **Catálogo viejo:** ACC-005 c (una operativa con caja abierta no se puede desactivar: «Este usuario tiene una caja abierta…»), ALU-010 (la inscripción no se puede dejar en cero por pantalla) y SIS-002 (el formato es `$1,250.50` desde T30).

## Límites

- Corrió por los controladores, no por el navegador, y `http()` no pasa el control de sesión de la misma manera que un navegador real.
- Las cuentas se hicieron a mano después de correr, medio por medio. Las comisiones no se recalcularon a mano.
- Estas corridas se solaparon con la reserva de Codex para su suite (`docs/00-estado/RESERVA-SUITE-T100-T106-T102.md`, creada a las 08:10): la leí recién a las 08:18 y ahí paré. No toqué su base ni sus fuentes, pero pudo demorar su arranque.
