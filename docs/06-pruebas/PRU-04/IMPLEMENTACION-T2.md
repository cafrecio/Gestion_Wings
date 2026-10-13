# Implementación T2 — Retiro de los dos importadores viejos de la primera carga

**Fecha:** 13/10/2026  
**Responsable:** Gemini CyE  
**Verificación:** Claude  
**Decisión de referencia:** `docs/06-pruebas/PRU-02/DEFECTOS.md` (P0/P1, línea ~782):
> «Se retiran los dos importadores viejos (`wings:importar-padron`, `wings:importar-deuda-inicial`) cuando el nuevo esté andando, no antes.»

La condición se cumplió: la primera carga del sitio de prueba (100 alumnos) se realizó con éxito utilizando el nuevo importador web unificado (`/primera-carga`), verificado fila por fila por Claude.

---

## 1. Archivos retirados del repositorio

Se eliminaron del proyecto los dos comandos de consola antiguos, sus servicios asociados y las pruebas específicas:

| Tipo | Archivo | Motivo |
|---|---|---|
| Comando | `app/Console/Commands/ImportarSaldoInicialPadronCommand.php` | Comando de consola `wings:importar-padron` reemplazado por `/primera-carga`. |
| Comando | `app/Console/Commands/ImportarDeudaInicialExcelCommand.php` | Comando de consola `wings:importar-deuda-inicial` reemplazado por `/primera-carga`. |
| Servicio | `app/Services/CargaSaldoInicialPadronService.php` | Lógica de importación de padrón con saldo inicial reemplazada por `PrimeraCargaExcelService`. |
| Servicio | `app/Services/CargaDeudaInicialExcelService.php` | Lógica de importación de deuda en Excel reemplazada por `PrimeraCargaExcelService`. |
| Test | `tests/Feature/CargaPadronFormatosExcelTest.php` | 6 pruebas del servicio de padrón retirado. |
| Test | `tests/Feature/CargaSaldoInicialPadronTest.php` | 5 pruebas de integración del comando `wings:importar-padron`. |
| Test | `tests/Feature/ImportarDeudaInicialExcelCommandTest.php` | 3 pruebas de integración del comando `wings:importar-deuda-inicial`. |

**Total de pruebas retiradas:** 14 pruebas (la suite pasa de 738 a 724 pruebas).

---

## 2. Archivos preservados intactos

- `app/Services/FormatoExcelCargaService.php`: **No se tocó.** Lo utiliza `PrimeraCargaExcelService` para normalizar montos, períodos y formatos Excel.
- `app/Services/PrimeraCargaExcelService.php`: Intacto.
- `app/Http/Controllers/PrimeraCargaWebController.php`: Intacto.
- Seeders (`PrimeraCargaAlumnosSeeder`, `PrimeraCargaCompletaSeeder`): Intactos; no referencian ninguna clase retirada.
- `tests/Feature/PrimeraCargaExcelTest.php`: Intacta; pasa al 100% (20 pruebas, 553 aserciones).

---

## 3. Barrido Grep previo al retiro

Antes de eliminar los archivos se realizó una búsqueda exhaustiva en `app/`, `routes/`, `database/`, `scripts/`, `tests/` y `docs/`:

```text
git grep -n "ImportarSaldoInicialPadronCommand|wings:importar-padron"
app/Console/Commands/ImportarSaldoInicialPadronCommand.php:8:class ImportarSaldoInicialPadronCommand extends Command
app/Console/Commands/ImportarSaldoInicialPadronCommand.php:10:    protected $signature = 'wings:importar-padron
docs/00-estado/ESTADO-ACTUAL.md:410:- **Pendiente de despliegue:** ... retiro posterior de importadores antiguos...
docs/05-pendientes/PRIMERA-CARGA-EXCEL.md:61:...
docs/06-pruebas/PRU-02/DEFECTOS.md:782:...
tests/Feature/CargaSaldoInicialPadronTest.php (líneas 90, 119, 141, 153, 166)
tests/Feature/ExportarPadronCargaInicialTest.php:124

git grep -n "ImportarDeudaInicialExcelCommand|wings:importar-deuda-inicial"
app/Console/Commands/ImportarDeudaInicialExcelCommand.php:8:class ImportarDeudaInicialExcelCommand extends Command
app/Console/Commands/ImportarDeudaInicialExcelCommand.php:10:    protected $signature = 'wings:importar-deuda-inicial
tests/Feature/ImportarDeudaInicialExcelCommandTest.php (líneas 16, 54, 61, 85, 95, 97)

git grep -n "CargaSaldoInicialPadronService"
app/Console/Commands/ImportarSaldoInicialPadronCommand.php:5
app/Services/CargaSaldoInicialPadronService.php:26
tests/Feature/CargaPadronFormatosExcelTest.php:11, 207

git grep -n "CargaDeudaInicialExcelService"
app/Console/Commands/ImportarDeudaInicialExcelCommand.php:5
app/Services/CargaDeudaInicialExcelService.php:11
tests/Feature/ImportarDeudaInicialExcelCommandTest.php:9, 77
```

**Resultado:** Ningún controlador web, ruta, migración, seeder ni script utilizaba los componentes retirados.  
En `tests/Feature/ExportarPadronCargaInicialTest.php` (línea 124), el segundo test invocaba `wings:importar-padron` para comprobar que el archivo exportado podía leerse si la hoja de instrucciones quedaba activa. Se desacopló esa aserción verificando que ambas hojas coexisten con la estructura de celdas requerida, manteniendo activas las 2 pruebas del comando `wings:exportar-padron`.

---

## 4. Análisis e informe sobre `wings:exportar-padron`

### ¿Para qué servía?
El comando `wings:exportar-padron` (`app/Console/Commands/ExportarPadronCargaInicialCommand.php`, 189 líneas, 2 pruebas en `tests/Feature/ExportarPadronCargaInicialTest.php`):
- Recorre los alumnos activos con su deporte (`Alumno::where('activo', true)`).
- Genera un archivo `.xlsx` con dos hojas: `Padron` (columnas `DNI`, `Alumno`, `Deporte`, `DEBE`, `Periodo 1`, `Monto 1`, etc.) e `Instrucciones`.
- Su objetivo explícito era generar la planilla que luego completaba el club y que consumía exclusivamente el comando de consola `wings:importar-padron`.

### ¿Algo lo usa actualmente?
- **Rutas y controladores:** Ninguna ruta web ni controlador invoca este comando.
- **Flujo de primera carga:** El nuevo flujo web (`PrimeraCargaWebController` / `PrimeraCargaExcelService`) genera su propia plantilla integral (`/primera-carga/plantilla`, `PrimeraCargaExcelService::plantilla()`), que incluye 38 columnas con catálogos validados, desplegables y períodos hasta el duodécimo mes. No utiliza `wings:exportar-padron`.
- **Uso actual:** Únicamente existe como comando huérfano de consola y sus 2 pruebas unitarias.

### Recomendación
Tal como fue instruido, **no se retira en este commit**.  
Se recomienda planificar su retiro en una tarea posterior de limpieza una vez que Carlos lo confirme, junto con su archivo de prueba `tests/Feature/ExportarPadronCargaInicialTest.php`.

---

## 5. Documentación y archivos complementarios

1. **Documentos técnicos archivados:**
   - `docs/06-pruebas/CARGA-DEUDA-INICIAL-EXCEL.md` -> `docs/99-archivo/CARGA-DEUDA-INICIAL-EXCEL.md` (con cabecera de archivo indicando retiro en T2).
   - `docs/06-pruebas/CARGA-PADRON-SALDO-INICIAL.md` -> `docs/99-archivo/CARGA-PADRON-SALDO-INICIAL.md` (con cabecera de archivo indicando retiro en T2).
   - `docs/README.md`: actualizado para reflejar la reubicación de ambos documentos en `docs/99-archivo/`.

2. **Archivos Excel históricos archivados en `docs/99-archivo/`:**
   - `DEUDA-INICIAL-PRUEBA-V1.xlsx`
   - `DEUDA-INICIAL-PRUEBA-V2.xlsx`
   - `DEUDA-INICIAL-RECHAZOS-V1.xlsx`
   - `EJEMPLO-CARGA-DEUDA-INICIAL.xlsx`
   - `PADRON-PRUEBA-v2.xlsx`
   - `PADRON-PRUEBA.xlsx`
   - `PLANTILLA-CARGA-DEUDA-INICIAL.xlsx`

3. **Documentación de estado sincronizada:**
   - `docs/05-pendientes/PRIMERA-CARGA-EXCEL.md`: actualizado punto 1 indicando el retiro efectivo el 13/10/2026.
   - `docs/06-pruebas/PRU-02/DEFECTOS.md`: actualizado con fecha de retiro.
   - `docs/00-estado/ESTADO-ACTUAL.md`: actualizado estado, tabla y conteo en 724 pruebas.
   - `docs/00-estado/CHECKLIST-CARLOS.md`: actualizado comando de suite y conteo a 724 pruebas.
   - `docs/00-estado/PLAN-PRODUCCION.md`: actualizado conteo a 724 pruebas.
   - `docs/00-estado/RESUMEN-ARRANQUE.md`: actualizado conteo a 724 pruebas.

---

## 6. Verificaciones ejecutadas

1. **Comandos de consola (`php artisan list wings`):**
   - Confirmado: `wings:importar-padron` y `wings:importar-deuda-inicial` ya no existen.
   - Confirmado: `wings:exportar-padron` permanece disponible.
2. **Importador nuevo (`tests/Feature/PrimeraCargaExcelTest.php`):**
   - 20 pruebas aprobadas (553 aserciones).
3. **Exportador conservado (`tests/Feature/ExportarPadronCargaInicialTest.php`):**
   - 2 pruebas aprobadas (18 aserciones).
4. **Guardián de documentación (`tests/Feature/DocumentacionNoMienteTest.php`):**
   - 1 prueba aprobada (7 aserciones), verificando conteo exacto de 724 pruebas en los documentos de estado.

---

## 7. Segunda vuelta — Corrección documental y retiro de `wings:exportar-padron`

**Fecha:** 13/10/2026  
**Motivo:** Devolución por Claude (`docs/06-pruebas/PRU-04/VERIFICACION-T2.md`) y decisión de Carlos del 13/10 («si no sirve, no aporta nada»).

### A. Documentación corregida y archivada

1. **`docs/00-estado/CHECKLIST-CARLOS.md` (líneas 196-199):**
   - Se reescribió el ítem pendiente de Carlos para indicar la descarga de la plantilla desde `/primera-carga` y la carga por pantalla, eliminando la mención a exportar/importar padrón por consola y el enlace al documento retirado. Sigue siendo un paso pendiente de Carlos.
2. **`docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md` (líneas 146 y 209):**
   - Línea 146: Se reemplazó la mención de `wings:exportar-padron` / `wings:importar-padron` por la primera carga unificada web (`/primera-carga`, P1).
   - Línea 209 (ENT-09): Se actualizó la descripción para referenciar el importador web unificado y la plantilla por pantalla.
3. **`docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html` (línea 153, ENT-09):**
   - Se reescribió en lenguaje llano de usuario («Primera carga de alumnos y deuda inicial»), sin referencias a nombres de comandos de consola ni rutas de archivos.
4. **`docs/00-estado/ORDEN-CODEX-DEUDA-INICIAL.md` trasladada a `docs/99-archivo/`:**
   - Movida a `docs/99-archivo/ORDEN-CODEX-DEUDA-INICIAL.md` con cabecera de documento archivado.
   - Enlaces corregidos en `docs/07-evaluacion/Evaluacion Codex 8-9-26.HTML` y `.md`.
5. **Herramientas y guías históricas archivadas:**
   - Se investigó con `git grep` el uso vigente de:
     - `docs/06-pruebas/DISENO-DEUDA-INICIAL-V1.md`: solo referenciado en la orden histórica y notas de Claude. Sin uso activo en el sistema. Movido a `docs/99-archivo/DISENO-DEUDA-INICIAL-V1.md` con cabecera de archivo.
     - `docs/06-pruebas/generar-deuda-inicial.php`: generador de planillas de prueba de la herramienta vieja. Sin uso en el código. Movido a `docs/99-archivo/generar-deuda-inicial.php` con cabecera de archivo.
     - `docs/06-pruebas/preparar-base-cobranza.php`: script manual de preparación para el importador viejo. Sin uso en el código. Movido a `docs/99-archivo/preparar-base-cobranza.php` con cabecera de archivo.
   - Actualizado el índice en `docs/README.md`.

### B. Retiro de `wings:exportar-padron` (Decisión de Carlos)

1. **Barrido de dependencias previo:**
   - Búsqueda: `git grep -n -E "exportar-padron|ExportarPadronCargaInicial"`.
   - Resultado: Únicamente referenciado por el propio comando, su prueba unitaria `ExportarPadronCargaInicialTest` y documentos de evaluación/históricos. Cero invocaciones en `app/`, `routes/`, `resources/` o flujos de usuario.
2. **Archivos eliminados:**
   - `app/Console/Commands/ExportarPadronCargaInicialCommand.php`
   - `tests/Feature/ExportarPadronCargaInicialTest.php` (2 pruebas retiradas)
3. **Independencia de la plantilla de primera carga:**
   - Se verificó `PrimeraCargaExcelService::plantilla()`: construye la planilla de 38 columnas de forma completamente autónoma con PhpSpreadsheet y `App\Support\FormatoImporte`, sin requerir ningún componente retirado.
4. **Sincronización del conteo de suite (722 pruebas):**
   - La suite desciende de 724 a 722 pruebas (720 aprobadas, 2 omitidas).
   - Se actualizó el conteo en los cuatro documentos vigilados: `docs/00-estado/ESTADO-ACTUAL.md`, `docs/00-estado/CHECKLIST-CARLOS.md`, `docs/00-estado/PLAN-PRODUCCION.md` y `docs/00-estado/RESUMEN-ARRANQUE.md`.
   - `DocumentacionNoMienteTest` verificado en verde (7 aserciones).

### C. Clasificación del barrido final obligatorio

Comando ejecutado:
`git grep -n -E "importar-padron|importar-deuda-inicial|exportar-padron|CARGA-PADRON-SALDO-INICIAL|CARGA-DEUDA-INICIAL-EXCEL|DEUDA-INICIAL-PRUEBA|DEUDA-INICIAL-RECHAZOS|PADRON-PRUEBA" -- docs AGENTS.md CLAUDE.md GEMINI.md ':!docs/99-archivo'`

Clasificación de cada coincidencia:
- `docs/00-estado/ESTADO-ACTUAL.md:5`: **Corregido** (declara el retiro de los importadores y exportador en T2).
- `docs/00-estado/ESTADO-ACTUAL.md:414`: **Corregido** (indica importadores antiguos retirados en T2).
- `docs/00-estado/ESTADO-ACTUAL.md:582`: **Corregido** (indica importadores antiguos y exportador retirados en T2).
- `docs/00-estado/LOG-GEMINI.md`: **Corregido** (registro de trabajo de la tarea T2).
- `docs/00-estado/tareas.json`: **Corregido** (descripción y estado de T2 en tablero).
- `docs/05-pendientes/PRIMERA-CARGA-EXCEL.md:61-62`: **Corregido** (declara el retiro efectivo de los comandos viejos).
- `docs/06-pruebas/PRIMERA-CARGA-V1.md:408`: **Corregido** (apunta al documento histórico en `docs/99-archivo/`).
- `docs/06-pruebas/PRU-02/DEFECTOS.md:787`: **Corregido** (anotación de retiro en T2 al lado de la decisión).
- `docs/06-pruebas/PRU-02/DIAGNOSTICO-A2.md:20`: **Registro histórico, no se toca** (hallazgo fechado del 23/09/2026).
- `docs/06-pruebas/PRU-02/P1-IMPLEMENTACION-2026-10-05.md:90-91`: **Registro histórico, no se toca** (nota de implementación de P1 del 05/10/2026).
- `docs/06-pruebas/PRU-02/VERIFICACION-P1.md:119, 146`: **Registro histórico, no se toca** (informe de verificación de P1 del 05/10/2026).
- `docs/06-pruebas/PRU-02/evidencia/a6-a10/suite-a32-nombres-2026-10-08.txt:1501`: **Registro histórico, no se toca** (volcado de salida de suite del 08/10/2026).
- `docs/06-pruebas/PRU-04/IMPLEMENTACION-T2.md`: **Corregido** (informe de la tarea actual).
- `docs/06-pruebas/PRU-04/VERIFICACION-T2.md:12, 20, 29, 45`: **Registro de devolución** (informe de Claude del 13/10/2026).
- `docs/README.md:55`: **Corregido** (índice declarando documentos archivados en `docs/99-archivo/`).

**Conclusión:** No subsiste ninguna instrucción ni referencia activa que ordene utilizar herramientas retiradas.

