# Pendientes verificados — 1 de septiembre 2026

> Todo lo que sigue fue comprobado ese día contra el código, la base y el servidor.
> **Lo que no se pudo verificar está marcado como tal.** Nada acá es inferencia.

---

## Verificado en vivo contra el servidor

`https://wings.gestionar-te.com.ar/login` responde **HTTP 200** con TLS válido.

Las cabeceras que devuelve:

| Cabecera | Valor |
|---|---|
| `X-Frame-Options` | `DENY` |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `same-origin` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` |
| `Content-Security-Policy-Report-Only` | `default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https:; …` |

La política de seguridad de contenido está **en modo aviso**, no bloquea.

---

## A · Tener con qué probar

| # | Qué falta | Estado verificado |
|---|---|---|
| **A1** | Seeder de prueba | `TestSeeder` tiene **21 líneas**. `DemoSeeder` crea **19 alumnos**, la especificación pide **15**, y hace **0 verificaciones** de los estados esperados que exige su sección 7 |
| **A2** | Simulador de tres meses | **No existe código.** Solo la especificación en `SIMULADOR-TRES-MESES-V1.md` |
| **A3** | Base de prueba limpia | `wings_test` **no existe en la máquina de CyE** |

---

## B · Probar de verdad

| # | Qué falta | Estado verificado |
|---|---|---|
| **B1** | Recorrido humano completo | Bloqueado por A |
| **B2** | Suite sobre la base real | Corre sobre **SQLite** (`phpunit.xml`). Son 77 pruebas en verde, pero no sobre el motor de producción |
| **B3** | Concurrencia con dos conexiones | Sin hacer |
| **B4** | Rutas que escriben | Sin hacer |

---

## C · Cerrar lo que quedó a medias

### C1 · La política de contenido

La política en vivo dice `script-src 'self'`, **sin permitir código incrustado**.

En las vistas quedan:

- **24 archivos** con `<script>` adentro
- **40 manejadores inline** del tipo `onclick=`, `onchange=`

Si se pasa a modo bloqueo hoy, esos 64 puntos dejan de funcionar.

### C2 · El dump, con las dos puertas abiertas

1. `database/dump.sql` **sigue versionado** — confirmado con `git ls-files`.
2. `DemoSeeder.php:691-692` **lo reexporta solo**, corriendo `mysqldump`.

Cerrar una sin la otra no cierra nada.

---

## D · Entregar

| # | Qué falta |
|---|---|
| **D1** | Gate del servidor |
| **D2** | Datos reales del club |
| **D3** | Usuarios reales |
| **D4** | Deuda del primer mes |
| **D5** | Primera caja acompañada |

---

## E · Después de entregar

| # | Qué falta |
|---|---|
| **E1** | Cuatro defectos con vencimiento: `AUD-018`, `AUD-019`, `AUD-020`, `AUD-025` |
| **E2** | Reportes que el sistema todavía no da |
| **E3** | Deuda técnica |
| **E4** | `formas_pago`, que **sigue existiendo en la base de CyE** |

---

## Puntos ciegos — NO verificados

Desde la máquina de CyE no hay acceso al servidor. **Estos cuatro puntos no se saben y
no se suponen:**

1. **Qué commit está corriendo.** El último dato registrado es del 30/08 y decía que el
   servidor estaba en un commit anterior a los arreglos de la primera cuota.
2. **Si los backups siguen corriendo.**
3. **Si hay monitoreo de errores.**
4. **Si el proceso mensual disparó el 1/09 a las 06:00.** Era su primera ejecución real.

Desde el 31/08, `deploy.sh` escribe cada despliegue en `storage/logs/despliegues.log`
del servidor, así que el punto 1 se responde mirando ese archivo.

---

# Qué buscamos lograr con el bloque A

Los tres puntos apuntan a lo mismo: **hoy no se puede probar el sistema**, y sin
probarlo no se puede entregar.

## A3 · Una base de prueba limpia

**Qué es:** una base separada de la de trabajo, vacía y lista.

**Para qué:** que probar no toque los datos propios, y que cada prueba arranque siempre
del mismo punto conocido.

Si se prueba sobre una base con cosas cargadas a mano, cuando algo falla no se sabe si
falló el sistema o si el dato ya estaba mal.

## A1 · El seeder de prueba

**Qué es:** llena esa base con un club inventado a propósito. Quince alumnos, y **cada
uno existe para verificar un caso concreto**.

Uno está al día. Otro debe un mes. Otro debe tres. Uno es nuevo y fue a una sola clase.
Otro fue a tres. Uno cambió de plan habiendo asistido, otro sin asistir. Uno está dado
de baja.

**Para qué:** que al sentarse frente al sistema **todos los casos que hay que probar ya
estén ahí**, sin tener que fabricarlos.

**El problema hoy:** `DemoSeeder` carga diecinueve alumnos que son todos más o menos
iguales. Con eso no se prueba nada: se puede cobrar una cuota, pero no se puede ver qué
pasa con el que debe tres meses, porque no existe.

## A2 · El simulador de tres meses

**Qué es:** un programa que juega tres meses de club solo. Cobra, cancela, abre y cierra
cajas, toma asistencia, cambia planes, corre el proceso mensual. Y después **revisa que
las cuentas cierren**.

**Para qué:** hay una parte del sistema que **solo existe en el tiempo** y nunca se
probó. Ni una vez.

La generación mensual de deuda, que corre el día 1 y mira el mes anterior. Tres de los
cuatro estados de cobranza, que dependen de meses cerrados. El cambio de plan que aplica
al mes siguiente. Las liquidaciones, que son mensuales.

**Y la acumulación:** los errores de plata no aparecen en la primera operación. Aparecen
después de semanas de cobrar, cancelar, volver a cobrar y validar cajas. Eso a mano no
se prueba nunca.

## Por qué A1 y A2 no se pisan

Son complementarios, y lo dice la propia especificación del simulador:

**El simulador no habría encontrado los dos errores del 30/08.** El precio en cero y no
poder cobrarle a un alumno nuevo estaban en las pantallas, no en las cuentas.

| | Prueba que… |
|---|---|
| **A1** | el sistema **se puede usar** |
| **A2** | las **cuentas cierran** |

## El orden

**A3 y A1 van juntas.** El seeder necesita una base donde vivir. Rearmar `wings_test` es
de minutos; el seeder es el trabajo grande. Destraban la prueba humana, que es lo que
bloquea la entrega.

**A2 es independiente.** Su especificación dice que corre sobre una base propia que se
arma desde cero en cada ejecución, y que **no toca `gestion_wings` ni `wings_test`**.
Arma sus propios veinte alumnos. Puede ir en paralelo.
