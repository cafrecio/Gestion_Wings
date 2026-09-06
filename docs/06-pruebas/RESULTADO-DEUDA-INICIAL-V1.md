# Resultado de deuda inicial V1

Fecha: 06/09/2026. Responsable: **Codex CAB**.
Entorno comprobado antes de operar: local, 127.0.0.1, `wings_test`.
Rama `main`, commit `eae0ff6`; `git pull` confirmó repositorio actualizado.

## Resultado: PASA, pasos 1 a 4 completos

Carlos resolvió la pausa: ejecutar únicamente el importador. El Paso 0 fue
retirado en `dde9357`. No se ejecutó ningún seeder. Los catálogos se cargarán
por pantalla en otra tarea todavía no definida por Carlos. El defecto de
protección de rubros del seeder queda separado y no se corrigió en esta prueba.

## Método de verificación

Se consultó directamente la base antes de operar, después de las validaciones,
después de cargar, después del rechazo por duplicación, después de revertir,
después de revalidar y después de recargar. Se leyeron cinco filas concretas de
deuda además de los agregados, y las cuatro cuotas de la inscripción doble.

Se compararon huellas SHA-256 del contenido completo, ordenado de forma estable,
de alumnos, alumno_planes, users y pagos: idénticas en todos los momentos.
No se exportaron las filas de usuarios ni sus contraseñas. La segunda importación
rechazada conservó también la huella completa de deuda_cuotas.

## Pasos y números leídos de la base

| Paso / momento | Resultado | Deudas | Suma original | Con deuda / sin deuda | Pagos / imputaciones |
|---|---|---:|---:|---|---|
| Inicial | PASA | 0 | $0 | 0 / 60 | 0 / 0 |
| 1. Validar los dos Excel | PASA | 0 | $0 | 0 / 60 | 0 / 0 |
| 2. Cargar | PASA | 81 | $2.997.000 | 48 / 12 | 0 / 0 |
| 3. Rechazar segunda corrida | PASA | 81 | $2.997.000 | 48 / 12 | 0 / 0 |
| 4a. Revertir | PASA | 0 | $0 | 0 / 60 | 0 / 0 |
| 4b. Revalidar | PASA | 0 | $0 | 0 / 60 | 0 / 0 |
| 4c. Recargar | PASA | 81 | $2.997.000 | 48 / 12 | 0 / 0 |

En todas las lecturas: **60 alumnos, 60 alumno_planes y 7 users**, con contenido
intacto. Después de cargar, rechazar duplicación y recargar: las 81 deudas están
`PENDIENTE`; cero filas tienen monto_pagado distinto de cero.

### Paso 1: validación y rechazo esperado

El archivo válido aprobó 81 deudas, código de salida 0. El archivo de rechazos
terminó con código 1 e informó juntas las ocho filas (2 a 9), con 11 mensajes:

| Fila | Mensajes obtenidos |
|---:|---|
| 2 | No existe un alumno para ese DNI y deporte |
| 3 | El deporte Hockey no existe |
| 4 | Monto debe ser numérico y mayor que cero |
| 5 | DNI + deporte repetido respecto de fila 4; monto no positivo |
| 6 | Período debe ser mmYYYY válido desde 2025 |
| 7 | DNI + deporte repetido respecto de fila 6; período inválido |
| 8 | DNI + deporte repetido respecto de fila 6; monto sin período |
| 9 | DNI + deporte repetido respecto de fila 4 |

Son rechazos esperados, no fallas. La base permaneció sin deudas ni pagos.

### Pasos 2 y 4: detalle comprobado

La inscripción doble tiene en ambas cargas:

| Deporte | Período | Monto original | Estado |
|---|---|---:|---|
| Patín | 2026-07 | $30.000 | PENDIENTE |
| Patín | 2026-08 | $30.000 | PENDIENTE |
| Patín | 2026-09 | $30.000 | PENDIENTE |
| Fútbol | 2026-09 | $28.000 | PENDIENTE |

La muestra de cinco filas incluye alumno 52 / 2025-06 / $22.000;
alumno 53 / 2025-08 y 2025-09 / $38.000 cada una;
alumno 54 / 2025-11 / $24.000; alumno 58 / 2026-08 / $28.000.
Todas pendientes y con monto_pagado 0.00.

La reversión retiró las 81 filas (salida 0). La revalidación aprobó 81 (salida 0)
y mantuvo cero filas. La recarga creó 81 (salida 0). Se comprobó la conservación
de alumnos y planes en los tres momentos, no solo al terminar.

### Paso 3: duplicación rechazada

Salida 1, mensajes explícitos de deuda existente por período. Permanecieron 81
filas con exactamente el mismo contenido y suma. No hubo inserciones parciales,
sobrescrituras ni duplicaciones pese al error esperado.

## Cierre y pendiente

Los pasos 1–4 no presentaron fallas. Al cerrar esa etapa, los pasos 5 en adelante
quedaron pendientes. La ejecución posterior del paso 5 se registra abajo;
no se hicieron cobros ni se completaron catálogos.

Los dos Excel y el generador conservaron sus huellas SHA-256 originales.
No hubo cambios de código, vistas o CSS, ni commit de esta ejecución.
La suite al cierre de cada paso aprobó **121 pruebas y 694 aserciones** sobre
`wings_testing`, separada de la base de la carga. Vistas compiladas y limpiadas.

La carga queda lista en wings_test. Próxima acción: esperar la indicación de
Carlos para la tarea de catálogos y los pasos posteriores.


## Paso 5 — foto antes de cobrar (06/09/2026, Codex CAB)

**Resultado: FALLA por acceso de OPERATIVO.** Comparación ADMIN/servicio aprobada.
La sesión OPERATIVO no puede abrir cobranza. Se frenó ante ese incumplimiento;
PROFESOR queda NO SE PUDO por la pausa, no se presume validado. No se avanzó al paso 6.

Se leyó el cuerpo de estadoAlumno y calcularEstadoDesdeDeudas: el primer criterio
es !tienePagos, antes de considerar el mes vigente. Se ejecutó estadoAlumno para
cada uno de los 60 alumnos, sin sustituirlo por el resumen del dashboard.

### Contadores observados

| Fuente | AL_DIA | EN_PLAZO | MOROSO | DEUDOR | Total |
|---|---:|---:|---:|---:|---:|
| Servicio, 60 cálculos individuales | 0 | 0 | 0 | 60 | 60 |
| Pantalla /cobranza, ADMIN | 0 | 0 | 0 | 60 | 60 |

OPERATIVO: no hay contadores observables de cobranza, porque redirige a Caja.
PROFESOR: no observado; prueba detenida antes de iniciar esa sesión.

La pantalla ADMIN se abrió sin filtros: se observaron sus 60 filas completas,
identificadas por enlace al ID del alumno, nombre y deporte, y una captura visual
de los contadores. No se asumió que el controlador y el servicio coincidieran.

### Los 60 estados, uno por uno

| ID | Alumno | Deporte | Cuotas pendientes | Servicio | Pantalla ADMIN |
|---:|---|---|---:|---|---|
| 6 | Morales, Sofía | Patín | 3 | DEUDOR | DEUDOR |
| 7 | Vega, Camila | Patín | 0 | DEUDOR | DEUDOR |
| 8 | Duarte, Mateo | Fútbol | 0 | DEUDOR | DEUDOR |
| 9 | Luna, Julieta | Patín | 2 | DEUDOR | DEUDOR |
| 10 | Gil, Renata | Patín | 2 | DEUDOR | DEUDOR |
| 11 | Navarro, Tomás | Fútbol | 2 | DEUDOR | DEUDOR |
| 12 | Méndez, Abril | Patín | 1 | DEUDOR | DEUDOR |
| 13 | Silva, Bruno | Fútbol | 1 | DEUDOR | DEUDOR |
| 14 | Ramos, Emilia | Patín | 1 | DEUDOR | DEUDOR |
| 15 | Leiva, Franco | Fútbol | 1 | DEUDOR | DEUDOR |
| 16 | Morales, Sofía | Fútbol | 1 | DEUDOR | DEUDOR |
| 18 | Arias, Valentina | Patín | 2 | DEUDOR | DEUDOR |
| 19 | Cabrera, Milagros | Patín | 2 | DEUDOR | DEUDOR |
| 20 | Domínguez, Catalina | Patín | 1 | DEUDOR | DEUDOR |
| 21 | Escudero, Florencia | Patín | 0 | DEUDOR | DEUDOR |
| 22 | Ferreyra, Agustina | Patín | 0 | DEUDOR | DEUDOR |
| 23 | Godoy, Martina | Patín | 0 | DEUDOR | DEUDOR |
| 24 | Herrera, Luciana | Patín | 0 | DEUDOR | DEUDOR |
| 25 | Ibarra, Camila | Patín | 0 | DEUDOR | DEUDOR |
| 26 | Ledesma, Josefina | Patín | 3 | DEUDOR | DEUDOR |
| 27 | Molina, Malena | Patín | 3 | DEUDOR | DEUDOR |
| 28 | Núñez, Natalia | Patín | 3 | DEUDOR | DEUDOR |
| 29 | Ortega, Olivia | Patín | 3 | DEUDOR | DEUDOR |
| 30 | Paz, Paula | Patín | 3 | DEUDOR | DEUDOR |
| 31 | Quiroga, Rocío | Patín | 2 | DEUDOR | DEUDOR |
| 32 | Roldán, Sabrina | Patín | 0 | DEUDOR | DEUDOR |
| 33 | Suárez, Tatiana | Patín | 0 | DEUDOR | DEUDOR |
| 34 | Toledo, Victoria | Patín | 3 | DEUDOR | DEUDOR |
| 35 | Urrutia, Ailén | Patín | 3 | DEUDOR | DEUDOR |
| 36 | Vargas, Bianca | Patín | 2 | DEUDOR | DEUDOR |
| 37 | Zárate, Clara | Patín | 1 | DEUDOR | DEUDOR |
| 38 | Bustos, Daniela | Patín | 1 | DEUDOR | DEUDOR |
| 39 | Correa, Elena | Patín | 1 | DEUDOR | DEUDOR |
| 40 | Figueroa, Giselle | Patín | 1 | DEUDOR | DEUDOR |
| 41 | López, Inés | Patín | 1 | DEUDOR | DEUDOR |
| 42 | Maidana, Karina | Patín | 2 | DEUDOR | DEUDOR |
| 43 | Ponce, Lorena | Patín | 2 | DEUDOR | DEUDOR |
| 44 | Rey, Micaela | Patín | 1 | DEUDOR | DEUDOR |
| 45 | Serrano, Noelia | Patín | 2 | DEUDOR | DEUDOR |
| 46 | Tissera, Paola | Patín | 1 | DEUDOR | DEUDOR |
| 47 | Valdez, Romina | Patín | 1 | DEUDOR | DEUDOR |
| 48 | Yáñez, Sofía | Patín | 2 | DEUDOR | DEUDOR |
| 49 | Benítez, Celeste | Patín | 1 | DEUDOR | DEUDOR |
| 50 | Cáceres, Eugenia | Patín | 1 | DEUDOR | DEUDOR |
| 51 | Delgado, Mariana | Patín | 1 | DEUDOR | DEUDOR |
| 52 | Acosta, Alan | Fútbol | 1 | DEUDOR | DEUDOR |
| 53 | Barrios, Benjamín | Fútbol | 2 | DEUDOR | DEUDOR |
| 54 | Cejas, Cristóbal | Fútbol | 1 | DEUDOR | DEUDOR |
| 55 | Díaz, Damián | Fútbol | 0 | DEUDOR | DEUDOR |
| 56 | Farías, Esteban | Fútbol | 0 | DEUDOR | DEUDOR |
| 57 | Giménez, Facundo | Fútbol | 0 | DEUDOR | DEUDOR |
| 58 | Herrera, Gonzalo | Fútbol | 2 | DEUDOR | DEUDOR |
| 59 | Luna, Iván | Fútbol | 2 | DEUDOR | DEUDOR |
| 60 | Mansilla, Joaquín | Fútbol | 2 | DEUDOR | DEUDOR |
| 61 | Navarro, Kevin | Fútbol | 2 | DEUDOR | DEUDOR |
| 62 | Ojeda, Lautaro | Fútbol | 2 | DEUDOR | DEUDOR |
| 63 | Pérez, Marcos | Fútbol | 1 | DEUDOR | DEUDOR |
| 64 | Ramos, Nicolás | Fútbol | 1 | DEUDOR | DEUDOR |
| 65 | Sosa, Pablo | Fútbol | 1 | DEUDOR | DEUDOR |
| 66 | Vera, Ramiro | Fútbol | 1 | DEUDOR | DEUDOR |

### Los 12 sin deuda

| ID | Alumno | Deporte | Servicio | Pantalla ADMIN |
|---:|---|---|---|---|
| 7 | Vega, Camila | Patín | DEUDOR | DEUDOR |
| 8 | Duarte, Mateo | Fútbol | DEUDOR | DEUDOR |
| 21 | Escudero, Florencia | Patín | DEUDOR | DEUDOR |
| 22 | Ferreyra, Agustina | Patín | DEUDOR | DEUDOR |
| 23 | Godoy, Martina | Patín | DEUDOR | DEUDOR |
| 24 | Herrera, Luciana | Patín | DEUDOR | DEUDOR |
| 25 | Ibarra, Camila | Patín | DEUDOR | DEUDOR |
| 32 | Roldán, Sabrina | Patín | DEUDOR | DEUDOR |
| 33 | Suárez, Tatiana | Patín | DEUDOR | DEUDOR |
| 55 | Díaz, Damián | Fútbol | DEUDOR | DEUDOR |
| 56 | Farías, Esteban | Fútbol | DEUDOR | DEUDOR |
| 57 | Giménez, Facundo | Fútbol | DEUDOR | DEUDOR |

### Grupos del diseño: base contra Excel

Se leyó el Excel sin modificarlo y se compararon los pares período/monto por
DNI+deporte contra las deudas de los 60 alumnos: 60 coincidencias, cero diferencias.

| Grupo | ID | Alumno | Períodos y montos pendientes, iguales al Excel |
|---|---:|---|---|
| Solo septiembre | 12 | Méndez, Abril (Patín) | 2026-09 / $40.000 |
| Dos meses | 9 | Luna, Julieta (Patín) | 2026-08 / $35.000; 2026-09 / $35.000 |
| Tres meses | 6 | Morales, Sofía (Patín) | 2026-07 / $30.000; 2026-08 / $30.000; 2026-09 / $30.000 |
| Deuda vieja 2025 | 18 | Arias, Valentina (Patín) | 2025-03 / $24.000; 2025-04 / $24.000 |

Todos los ejemplos están PENDIENTE y tienen monto_pagado 0.00.

### H-DI-01 — OPERATIVO no puede consultar cobranza — FALLA

- Esperado: OPERATIVO accede a /cobranza y ve los mismos 60 alumnos y cuatro
  contadores que ADMIN, conforme al pedido y PERMISOS-ROLES.md.
- Observado: ingreso exitoso con usuario de prueba ID 6, rol OPERATIVO,
  identificado en pantalla como Valentina Ríos. El menú no ofrece Cobranza.
  Al navegar directamente a http://gestion-wings/cobranza, termina en
  http://gestion-wings/caja, título Caja, sin listado ni contadores de cobranza.
  Se tomó captura visual del destino. No fue un error 500 ni una validación
  de formulario: fue una redirección de acceso.
- Causa comprobada leyendo el código: routes/web.php:88 ubica /cobranza dentro
  de ensure.admin.web; EnsureAdminWeb::handle redirige los roles distintos de
  ADMIN y PROFESOR hacia web.caja.index. Coincide con lo observado en navegador.
- Alcance: los 12 sin deuda se compararon uno a uno en ADMIN contra el servicio,
  pero no se les puede asignar un estado observado en la pantalla OPERATIVO,
  porque esa pantalla no abre. La prueba PROFESOR queda NO SE PUDO tras el freno.
- No se corrigieron permisos ni se tocaron vistas. Pendiente de Carlos: decisión
  sobre la corrección separada y posterior revalidación de OPERATIVO/PROFESOR.

### Base después de consultar y del freno — PASA

| Control | Antes | Después |
|---|---:|---:|
| deuda_cuotas | 81 | 81 |
| suma monto_original | $2.997.000 | $2.997.000 |
| pagos | 0 | 0 |
| pago_deuda_cuota | 0 | 0 |
| alumnos | 60 | 60 |
| alumno_planes | 60 | 60 |
| users | 7 | 7 |

Las huellas de contenido completo de estas seis tablas son idénticas antes y
después. Las deudas conservan SHA-256
`73a9d3dc4800a586bd3a3b3be2bbbd8725e2cd153a0dc2bdaffa545e13e04c97`.
Los 60 resultados individuales y sus períodos/montos tampoco cambiaron.
No hubo escrituras operativas pese al rechazo de acceso. La comprobación de
integridad abarca esas seis tablas; no afirma ausencia de actividad técnica
de sesión o caché propia del inicio de sesión.

Suite: 121 pruebas aprobadas, 694 aserciones. Vistas compiladas y limpiadas;
diff de vistas/CSS vacío. Sin cambios funcionales ni commit. Firma: Codex CAB.
