# Wings - Plan de Pruebas Funcionales

> Version inicial: 2026-06-15  
> Objetivo: probar todo lo que existe hoy y detectar que falta, que falla y que datos minimos hacen falta para seeders realistas.  
> Alcance: prueba funcional manual del sistema web completo. API solo como apoyo tecnico.

## 1. Objetivo

Este plan busca ejecutar una primera prueba integral del sistema Wings con una carga minima realista. La prueba no intenta demostrar que el sistema esta listo para produccion; intenta descubrir con precision:

- Que modulos funcionan.
- Que modulos existen pero estan incompletos.
- Que flujos se rompen.
- Que datos actuales de la BD no sirven.
- Que seeders hacen falta para repetir una prueba completa.
- Que mejoras, correcciones o decisiones quedan bloqueando una prueba realista.

## 2. Criterio de Exito de Esta Primera Prueba

La prueba se considera util si al final tenemos:

1. Una lista de casos que pasan.
2. Una lista de casos que fallan, con ruta, rol, pasos y evidencia.
3. Una lista de datos que faltan para seeders.
4. Un flujo minimo completo probado: alumno -> deuda -> cobro -> caja -> validacion -> cashflow.
5. Un flujo minimo completo probado: clase -> asistencia -> liquidacion -> pago/recibo.
6. Una decision sobre como armar la BD de prueba funcional.

No se espera que todo pase en esta primera vuelta.

## 3. Ambientes y Preparacion

### Ambiente local esperado

- XAMPP con MariaDB.
- App en `C:/xampp/htdocs/gestion-wings`.
- Laravel 12.
- Usuario admin disponible.
- Usuario operativo disponible.
- Usuario profesor disponible.

### Antes de probar

1. Exportar o respaldar la BD actual si hay datos que no se quieren perder.
2. Confirmar que se puede entrar por `/login`.
3. Confirmar que el servidor local esta corriendo.
4. Confirmar que assets Vite estan compilados o el dev server activo.
5. No corregir durante la prueba: registrar primero, corregir despues.

## 4. Roles a Probar

| Rol | Usuario minimo | Objetivo |
|---|---|---|
| ADMIN | `admin@wings.test` | Gestion completa, configuracion, cashflow, validaciones, liquidaciones. |
| OPERATIVO | `operativo@wings.test` | Caja diaria, alumnos, cobro de cuotas, clases/asistencias. |
| PROFESOR | `profesor@wings.test` | Ver clases y tomar asistencia segun permisos esperados. |

Las credenciales exactas deben quedar definidas por el seeder funcional.

## 5. Carga Minima Realista para Seeder

La BD actual no alcanza si solo tiene datos aislados. El seeder funcional debe representar casos reales, no solo registros para que no falle un select.

### 5.1 Usuarios

| Tipo | Cantidad | Detalle |
|---|---:|---|
| Admin | 1 | Acceso total. |
| Operativo | 2 | Uno con caja actual, otro con caja vieja abierta para probar bloqueo. |
| Profesor | 2 | Vinculados a profesores reales; uno por hora y uno por comision. |

### 5.2 Catalogos base

| Catalogo | Minimo |
|---|---|
| Deportes | Patin y Futbol. |
| Niveles | Inicial, Intermedio, Avanzado. |
| Tipos de caja | Efectivo, Mercado Pago, Banco, BNA. |
| Rubros | Cuotas, Productos, Torneos, Gastos operativos, Sueldos, Alquiler, Servicios. |
| Subrubros | Cuota Mensual reservado, Venta indumentaria, Inscripcion torneo, Limpieza, Libreria, Sueldo profesor, Alquiler salon. |
| Reglas primer pago | 1-15: 100%, 16-23: 70%, 24-31: 40%. |
| Configuraciones | `dias_gracia_cobranza`, `dia_generacion_deuda`. |

### 5.3 Grupos y planes

| Deporte | Grupo | Planes |
|---|---|---|
| Patin | Inicial | 1x semana, 2x semana |
| Patin | Intermedio | 2x semana, 3x semana |
| Patin | Avanzado | 3x semana |
| Futbol | Inicial | 1x semana, 2x semana |

Cada plan debe tener precio distinto para probar cambios de monto.

### 5.4 Alumnos

Minimo recomendado: 24 alumnos.

| Caso | Cantidad | Proposito |
|---|---:|---|
| Activo al dia | 4 | No debe mostrar deuda anterior. |
| Activo con deuda mes actual dentro de gracia | 3 | Estado al dia pendiente. |
| Moroso mes actual fuera de gracia | 3 | Estado moroso. |
| Deudor con meses anteriores | 4 | FIFO y cobranza vieja. |
| Pago parcial | 2 | Saldo pendiente y ultimo periodo parcial. |
| Sin asistencia mes anterior | 3 | Revision de cobranza / a controlar. |
| Inactivo con deuda historica | 2 | Ver comportamiento de inactivos. |
| Cambio de plan | 2 | Cobrar periodo viejo con plan viejo y periodo nuevo con plan nuevo. |
| Misma persona en dos deportes | 1 caso duplicado | DNI permitido por deporte distinto. |

### 5.5 Deudas y pagos

Crear deudas para por lo menos 4 periodos:

- Mes anterior - 2.
- Mes anterior - 1.
- Mes actual.
- Mes siguiente.

Casos necesarios:

- Deuda pagada.
- Deuda pendiente.
- Deuda parcial.
- Deuda condonada.
- Deuda ajustada.
- Pago que cubre varios periodos.
- Pago rechazado por FIFO.

### 5.6 Caja operativa

| Caso | Proposito |
|---|---|
| Operativo sin caja hoy | Probar apertura automatica al primer movimiento. |
| Operativo con caja abierta hoy | Probar movimientos, resumen, detalle y cierre. |
| Operativo con caja vieja abierta | Probar bloqueo. |
| Caja cerrada pendiente | Probar validacion admin. |
| Caja rechazada | Probar edicion y recierre. |
| Caja validada | Probar reflejo en cashflow e idempotencia visible. |

### 5.7 Clases y asistencias

Crear clases:

- Hoy: una finalizada sin asistencia, una en curso o por comenzar, una cancelada.
- Pasadas: varias con asistencia y varias sin asistencia.
- Futuras: una unica y una serie recurrente.
- Con profesor por hora.
- Con profesor por comision.
- Con alumno que excede plan semanal.

### 5.8 Liquidaciones

Crear datos para probar:

- Liquidacion por hora.
- Liquidacion por comision.
- Liquidacion abierta.
- Liquidacion cerrada.
- Liquidacion pagada.
- Recalculo antes de cierre.
- Recibo de liquidacion si corresponde.

## 6. Matriz de Prueba por Modulo

| Modulo | Rol | Que se prueba | Resultado esperado |
|---|---|---|---|
| Login/logout | Todos | Entrar, salir, sesion expirada | Redireccion correcta por rol. |
| Dashboard admin | Admin | KPIs y accesos | Carga sin error y datos coherentes. |
| Usuarios | Admin | Crear/editar/inactivar, profesor vinculado | Validaciones y toggles correctos. |
| Configuracion | Admin | Configs inline y reglas primer pago | Guarda sin romper UI. |
| Deportes | Admin | CRUD y toggle | No elimina datos necesarios. |
| Niveles | Admin | CRUD y check disponible | Nombres unicos. |
| Grupos/planes | Admin | Crear grupo, planes, editar, ver | Planes activos y precios correctos. |
| Alumnos | Admin/Operativo | Crear, editar, buscar, filtrar, activar | DNI unico por deporte y plan vigente. |
| Cobranza | Admin/Operativo | Ver deudas, cobrar, estados | Estados coherentes con deudas. |
| Caja | Operativo | Movimiento, cobro, resumen, detalle, cierre | Caja abre automaticamente y cierra. |
| Validacion caja | Admin | Validar/rechazar caja | Cashflow refleja validada; rechazada vuelve a operativo. |
| Cashflow | Admin | Movimiento directo y listado | Solo admin, saldos coherentes. |
| Clases | Admin/Operativo/Profesor | Crear, ver, cancelar, asistencia | Permisos y estados correctos. |
| Liquidaciones | Admin | Generar, cerrar, recalcular, pagar | Totales correctos. |
| Recibos | Admin/Operativo | Cuota y liquidacion | PDF/info accesible segun rol. |
| Seguridad rol | Todos | Acceder a rutas ajenas | Bloqueo o redireccion correcta. |

## 7. Flujos End-to-End Obligatorios

### Flujo A - Cobro operativo completo

1. Login como operativo.
2. Entrar a `/caja`.
3. Confirmar que no se abre caja solo por entrar.
4. Cobrar una cuota a alumno con deuda actual.
5. Confirmar que se abre caja automaticamente.
6. Ver caja resumen.
7. Ver caja detalle.
8. Cerrar caja.
9. Login como admin.
10. Validar caja cerrada.
11. Confirmar movimiento en cashflow.
12. Confirmar deuda del alumno como pagada o parcial segun monto.

### Flujo B - FIFO y deuda parcial

1. Elegir alumno con tres deudas.
2. Intentar pagar periodo nuevo dejando deuda vieja parcial.
3. Confirmar que el sistema lo rechaza.
4. Pagar deuda vieja completa y ultima parcial.
5. Confirmar saldos.

### Flujo C - Caja rechazada

1. Operativo carga movimientos y cierra caja.
2. Admin rechaza caja.
3. Operativo edita/agrega movimientos.
4. Operativo vuelve a cerrar.
5. Admin valida.
6. Confirmar que cashflow no duplica movimientos.

### Flujo D - Alumno, grupo y plan

1. Admin crea deporte/grupo/plan si hace falta.
2. Operativo crea alumno.
3. Edita alumno y cambia plan.
4. Cobra periodo anterior y actual.
5. Confirmar monto segun plan vigente del periodo.

### Flujo E - Clases, asistencia y liquidacion

1. Admin crea clase o serie.
2. Profesor u operativo entra al show de clase.
3. Carga asistencia.
4. Admin genera liquidacion del periodo.
5. Revisa detalle.
6. Cierra liquidacion.
7. Paga liquidacion.
8. Verifica cashflow/recibo si aplica.

### Flujo F - Motor/revision de cobranza

1. Ejecutar command de generacion mensual en ambiente de prueba.
2. Confirmar que alumnos con asistencia/pago previo reciben deuda.
3. Confirmar que alumnos sin asistencia/pago previo van a revision.
4. Resolver revision generando deuda.
5. Resolver revision marcando inactivo.
6. Registrar lo que falta respecto de deuda fantasma.

## 8. Casos de Seguridad Manual

| Caso | Resultado esperado |
|---|---|
| Operativo intenta entrar a `/admin/dashboard` | Redireccion o 403. |
| Profesor intenta entrar a `/alumnos` | Debe bloquearse o redirigir segun regla definida. |
| Operativo intenta validar caja por URL | Debe bloquearse. |
| Operativo intenta entrar a cashflow admin | Debe bloquearse. |
| Usuario inactivo intenta login | Debe bloquearse si esta implementado. |
| API protegida sin token | 401. |

## 9. Evidencia a Registrar

Por cada falla:

- Fecha y hora.
- Rol.
- URL.
- Datos usados.
- Pasos exactos.
- Resultado esperado.
- Resultado obtenido.
- Captura de pantalla.
- Error de logs si existe.
- Severidad: bloqueante, alta, media, baja.
- Si requiere cambio funcional, dato de seeder o decision de negocio.

## 10. Resultado Esperado del Primer Ciclo

Al terminar la primera prueba debe quedar:

1. `docs/06-pruebas/RESULTADO-PRUEBA-INICIAL.md` con hallazgos.
2. Lista de seeders a crear.
3. Lista de correcciones funcionales priorizadas.
4. Lista de inconsistencias de UI/design system.
5. Lista de decisiones de negocio pendientes.
6. Decision sobre limpiar BD actual o crear BD de prueba separada.

## 11. Seeders Propuestos Despues de la Prueba

La prueba inicial debe derivar en seeders separados por intencion:

| Seeder | Proposito |
|---|---|
| `UsuariosRolesSeeder` | Admin, operativos, profesores. |
| `CatalogosBaseSeeder` | Deportes, niveles, tipos caja, rubros, subrubros, reglas. |
| `GruposPlanesSeeder` | Grupos y planes realistas. |
| `AlumnosEscenariosSeeder` | Alumnos con estados variados. |
| `DeudasPagosEscenariosSeeder` | Deudas pagadas, parciales, viejas, condonadas. |
| `CajaEscenariosSeeder` | Cajas abierta, cerrada, rechazada, validada. |
| `ClasesAsistenciasEscenariosSeeder` | Clases pasadas/hoy/futuras y asistencias. |
| `LiquidacionesEscenariosSeeder` | Liquidaciones por hora/comision. |
| `PruebaFuncionalCompletaSeeder` | Orquestador que corre todos en orden. |

## 12. Orden Recomendado de Ejecucion

1. Probar login y roles.
2. Probar catalogos base.
3. Probar alumnos/grupos/planes.
4. Probar deudas/pagos sin caja compleja.
5. Probar caja operativa.
6. Probar validacion admin/cashflow.
7. Probar clases/asistencias.
8. Probar liquidaciones/recibos.
9. Probar cobranza mensual/revision.
10. Probar seguridad por rol.
11. Consolidar hallazgos y convertirlos en seeders/correcciones.
