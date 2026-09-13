# Wings — Checklist de Carlos

> **Actualizado:** 08/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v4.

Solo contiene acciones o decisiones que necesitan a Carlos. Las tareas tecnicas de
Codex y Claude no van aca.

## 1. Al cambiar de computadora

1. Ejecutar `git pull --ff-only`.
2. Ejecutar `composer install`, `npm install` y `npm run build` si cambiaron
   dependencias o assets.
3. Instalar/verificar la guardia de diseño con `bash scripts/hooks/instalar.sh`.
4. Ejecutar `php artisan migrate` sobre la base local correspondiente.
5. Ejecutar la suite completa:

```bash
php artisan test          # 187 pruebas deben pasar
```

Corte verificado del 13/09: **187 pruebas**, todas verdes.
Las fallas están en nuevas pruebas de contraseñas y recibos de tareas SEG en curso.
FIN-03 requiere aplicar la nueva migracion al desplegar; no fue aplicada a la
base de trabajo ni al servidor. No recupera detalles de anulaciones antiguas.

**No importar ni volver a versionar `database/dump.sql`.** Fue retirado el 05/09.

**No correr `CatalogosSeeder` a mano sobre una base con datos**: puede quitar la
proteccion de los rubros `Cuotas` y `Sueldos`. Sirve solo para armar una base nueva.
El despliegue del servidor no lo ejecuta, y el script viejo de Windows que lo corria
solo (`deploy-wings.bat`) se elimino el 11/09.

## 2. Decisiones necesarias antes de programar

- [ ] **Profesores por hora: ¿cobran por clase o por hora de duracion?** El diseño del
  recibo aprobado por Vanina paga por hora (una clase de 1,5 hs = $7.500); el sistema hoy
  paga por clase (cualquier clase = $5.000). Si es por hora, la liquidacion paga de menos
  y hay que corregirla antes de la primera real. **Bloquea el recibo nuevo.**
- [ ] **`monto_base` de los pagos:** definir que tiene que valer en un cobro con seña o
  con varios meses. Hoy se guarda mal y nadie lo lee, pero el recibo nuevo lo va a querer.
- [x] ~~**Regla en la base para liquidaciones**~~ — **RETIRADA el 12/09. No la apruebes.**
  La regla que te propuse (una unica sobre `referencia_tipo` + `referencia_id`)
  **rompe la validacion de cajas**: una caja validada crea un asiento por cada cobro y
  todos llevan la misma referencia, asi que cualquier caja con mas de un movimiento
  fallaria. La version que funcionaria exige una columna generada y un indice sobre
  ella, en la tabla de plata de la base definitiva. **Recomendacion corregida: no
  hacerlo**; el bloqueo de FIN-05 ya resuelve el caso real y esta probado con dos
  conexiones. Detalle en el plan, seccion FIN-05.
- [x] **COB-04:** un pago anulado **no** cuenta como primer pago. Decidido el 10/09;
  corregido y verificado en navegador.
- [ ] **FIN-04:** decidir si “Balance” de Cashflow significa saldo acumulado o
  resultado del periodo.
- [ ] **FIN-08:** definir como tratar una revision con pago parcial, observaciones
  previas y precio historico.
- [ ] **FIN-09:** definir hasta que fecha pasada o futura se permite cargar movimientos.
- [ ] **PRU-03:** confirmar que significa DEUDOR cuando el alumno no tiene pagos ni
  saldo pendiente.
- [ ] **ENT-01:** confirmar como se contabiliza la inscripcion configurable del alumno
  nuevo.

## 3. Recursos que faltan

- [ ] **ENT-02:** entregar o aprobar logo y paleta del club para rediseñar el recibo.
- [x] **ENT-03:** entregar o aprobar el recurso del favicon. (Aprobado por Carlos el 12/09/2026: patín artístico con alas zoom 95% e implementado)
- [ ] Pasar credenciales del servidor y clave de backups a un administrador de
  contraseñas. No ponerlas en el repositorio ni en las bitacoras.

## 4. Para la entrega

- [x] FDS-02: Carlos confirmo email y Telegram de las pruebas de monitoreo el 09/09. No falta configurar otra cuenta ni entregar nuevamente el token.
- [x] Vanina tiene cuenta ADMIN en el servidor, verificado el 08/09.
- [ ] Confirmar si habra otros usuarios reales y sus roles.
- [ ] Cargar deportes, niveles, grupos, planes, tipos de caja y demas datos reales por
  las pantallas acordadas.
- [ ] **Cuando Vanina termine de cargar los alumnos:** exportar el padron, hacerselo
  completar con DEBE por alumno, e importarlo. Cierra el mes de corte y Wings arranca
  a facturar el mes siguiente. Procedimiento en
  `docs/06-pruebas/CARGA-PADRON-SALDO-INICIAL.md`.
- [ ] Reservar dos o tres horas para el recorrido humano completo, despues de cerrar
  los defectos prioritarios de cobro.
- [ ] Acompañar la apertura y cierre de la primera caja real.
- [ ] Firmar el gate final de produccion.

## 5. Ya resuelto — no volver a pedir

- Acceso SSH, dominio, TLS y Cloudflare.
- Backups diarios cifrados con copia a Drive.
- Dump fuera del repositorio y exportacion automatica eliminada.
- Suite funcionando sobre MariaDB.
- Base del servidor preparada para la carga humana.
- Cuenta ADMIN de Vanina creada.
- Acceso de Cobranza para OPERATIVO.
