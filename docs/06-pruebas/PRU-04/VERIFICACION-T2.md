# T2 — Verificación de Claude · 13/10/2026 · DEVUELTO a Gemini

Verificado sobre `c0858de`. Sin despliegue.

## El retiro del código está bien

| Qué | Resultado |
|---|---|
| Los dos comandos, los dos servicios y sus tres archivos de prueba ya no existen | Bien |
| Ninguna referencia a lo retirado en `app`, `routes`, `database`, `scripts`, `tests`, `config`, `resources` | Bien (`git grep`, cero resultados) |
| `FormatoExcelCargaService`, `PrimeraCargaExcelService`, controladores, seeders y vistas sin cambios | Bien (`git diff` vacío) |
| `php artisan list wings`: no figuran `importar-padron` ni `importar-deuda-inicial`; sigue `exportar-padron` | Bien |
| Suite completa en `wings_testing_claude` | 722 aprobadas, 2 omitidas: 724, lo que declaran los documentos |

## Por qué vuelve: quedaron documentos que mandan a usar lo que se retiró

**1. A Carlos le queda un paso pendiente que ya no se puede hacer.**

- `docs/00-estado/CHECKLIST-CARLOS.md:196-199`: «exportar el padrón, hacérselo completar con
  DEBE por alumno, e importarlo. Procedimiento en `docs/06-pruebas/CARGA-PADRON-SALDO-INICIAL.md`».
  El comando de importar no existe y el documento ya no está en esa ruta.
- `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md:146-147` y `:209` (ENT-09): lo mismo.
- `docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html:153` (ENT-09, «Te toca a vos»): lo mismo.

Hoy la deuda inicial se carga en `/primera-carga`, en la misma planilla que los alumnos. Los
tres lugares tienen que decir eso.

**2. Quedó en `docs/00-estado/` una orden de trabajo de lo retirado.**
`ORDEN-CODEX-DEUDA-INICIAL.md` manda a correr `wings:importar-deuda-inicial` sobre planillas
que ya no están en `docs/06-pruebas/`. Va al archivo, como los otros dos documentos.

**3. Quedaron en `docs/06-pruebas/` las piezas del importador retirado.**
`DISENO-DEUDA-INICIAL-V1.md`, `generar-deuda-inicial.php` y `preparar-base-cobranza.php`
nombran las planillas por su ruta vieja, y los dos scripts las vuelven a crear en
`docs/06-pruebas/` (además, con la ruta de otra máquina escrita a mano). Que diga para cada
uno si se sigue usando; lo que no, al archivo.

**4. Una prueba quedó con un nombre que ya no es cierto.**
`ExportarPadronCargaInicialTest`: `test_el_archivo_trae_instrucciones_y_el_padron_se_lee_aunque_que…`.
Ya no comprueba que el padrón se lea: comprueba que la hoja existe y trae el DNI. El cambio es
correcto (no hay quien lo lea); el nombre hay que ajustarlo.

## Queda para Carlos

`wings:exportar-padron` no lo usa nadie: generaba la planilla que leía el comando retirado.
Gemini lo dejó, como se le pidió, y recomienda retirarlo. Coincido. Falta la decisión.

## Sin verificar

- Los documentos de `docs/06-pruebas/PRU-02/` que nombran los importadores: son registro de
  lo que pasó en su fecha, no instrucciones vigentes. No los revisé uno por uno.
