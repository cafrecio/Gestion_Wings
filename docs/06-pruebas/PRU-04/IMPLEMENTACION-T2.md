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
