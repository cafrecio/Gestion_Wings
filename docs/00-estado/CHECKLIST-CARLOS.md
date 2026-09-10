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
php artisan test          # 150 pruebas deben pasar
```

El corte verificado del 10/09 es 150 pruebas y 835 aserciones.

**No importar ni volver a versionar `database/dump.sql`.** Fue retirado el 05/09.

**No reejecutar `CatalogosSeeder` sobre una base existente** hasta cerrar FIN-01: hoy
puede quitar la proteccion de los rubros `Cuotas` y `Sueldos`.

## 2. Decisiones necesarias antes de programar

- [ ] **COB-04:** decidir si un pago anulado cuenta como primer pago comercial,
  despues de reproducir el caso completo.
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
- [ ] **ENT-03:** entregar o aprobar el recurso del favicon.
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
