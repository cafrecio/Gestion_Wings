---
titulo: Confirmar la importación masiva de primera carga
rol: admin
categoria: Primera carga
orden: 23
resumen: Cómo verificar el resumen final e importar los alumnos y sus deudas a la base de datos de Wings.
pantalla: /sistema/primera-carga
---

# Confirmar la importación masiva de primera carga

Esta tarea te explica cómo confirmar la carga inicial de datos una vez que la planilla Excel superó la revisión con cero errores.

> [!IMPORTANT]
> *Operación atómica y sin dinero en caja:* Wings procesa toda la planilla en una sola operación continua. Si una fila fallara, se cancela todo para no dejar datos a medias. Además, la carga inicial genera deudas pendientes en Cobranza pero no ingresa dinero a caja: los pagos se registrarán a medida que los socios abonen en el club.

---

## Pasos para confirmar la carga en Wings

### Paso 1. Revisar el resumen consolidado
1. En **Primera carga**, andá a la sección **4. Cargar en Wings**.
2. Verificá los totales informados por el sistema:
   - Cantidad de alumnos a registrar.
   - Cantidad de cuotas pendientes e importes consolidados de cuotas e inscripción.
   - Monto total de deuda inicial.
3. Si los totales coinciden con tu planilla, tocá el botón **Cargar**.

![Ver el resumen en el paso 4 y tocar Cargar](/ayuda/img/admin/primera-carga-confirmar-paso1.png)

---

### Paso 2. Confirmar la operación definitiva
1. Al tocar Cargar, se desplegará el cuadro de confirmación con la advertencia: `Se cargará todo en una sola operación. Confirmá el resumen de arriba. No entra dinero a caja.`
2. Tocá el botón **Confirmar**.
3. Wings creará las fichas de todos los alumnos, los asignará a sus deportes y registrará sus cuotas adeudadas en **Cobranza**.
4. Al finalizar, la pantalla indicará: `Carga terminada. Los alumnos están en Alumnos y las deudas en Cobranza. Quien no debía arranca al día.`

![Confirmar la importación definitiva](/ayuda/img/admin/primera-carga-confirmar-paso2.png)

---

## Qué hago si…

### ¿Qué hago si los números del resumen no coinciden con lo que esperaba?
No toques Confirmar. Tocá el botón **Volver**, abrí tu planilla Excel en la computadora, revisá qué filas sobran o faltan, guardá y volvé a subirla en el paso 3.

### ¿A dónde van los alumnos que no debían nada?
Los alumnos sin deuda quedan registrados como socios activos en el módulo **Alumnos**, con su grupo y plan asignado, al día y listos para tomar asistencia.
