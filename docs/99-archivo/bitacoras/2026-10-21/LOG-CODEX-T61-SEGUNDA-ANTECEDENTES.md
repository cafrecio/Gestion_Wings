## 2026-10-20 — Codex CyE — Toma T42, después T43

Carlos ordenó T42 y luego T43, tras terminar verificaciones T52/T61. T61 publicada 43a70976, visual corregido aprobado y completa 976/6 omitidas verde; lógica para Claude. T52 reverificada y devuelta por H5 en ff49b709.
T42 tomada por script: comisión de cada parte en max(mes cobro, mes pagado), después ingreso adelantado en Reportes/Inicio; Caja/Cashflow conservan fecha real del dinero.
Leer DU-19/DU-36, COB-012/LIQ-002 y contratos/código; si el diseño contradice el sistema, frenar. Sin código T42 antes de publicar esta toma.
Base propia wings_testing_codex y completa sin otra suite activa por tarea. No escribir en wingstest/wings ni desplegar. T43 se implementa después, con revisión visual de Carlos antes de commit de vistas.
Seguimiento tras T61 f089768b: Carlos resolvió «Sí, conservar el mes original solo en esos casos.» Comisión/Reportes locales, 29/150 dirigidas verdes y COB-012 vivo versión 10 comprobado tras envío autorizado. Primera completa: 995/6 omitidas y una prueba propia corregida por ID; segunda frenó por H5 T52 agregado en paralelo. Contador real 1003; completa de 11:51 finalizada verde (997/6 omitidas), salida en evidencia-t42/suite-completa-corte1003.txt. T52 e1e7c499 revisada: 21/139 verdes; T43 después de entregar T42.

## 2026-10-20 — Codex CyE — T52, reverificación d148a241 y H5

H1–H4 pasan en reproducciones propias; N+1 corregido: 12 consultas tanto con 1 como con 12 alumnos. NULL inicial se verifica como ingreso efectivo, sin confundir valor físico y fecha efectiva.
Nueve independientes en wings_testing_codex, sin otra suite activa: 8 aprobadas/1 fallida, 59 aserciones, 21,50 s, salida 1.
H5 web reproducido: ingreso 01/11, cambio A→B 20/10 confirmado; clase de B del 20/10 lo lista y POST guarda presente (200), antes del ingreso.
[Informe y evidencia](../06-pruebas/PRU-04/VERIFICACION-T52-CODEX.md). Devuelta a Claude por tablero; no se corrige producto ni se alteran clases/vistas. Visual aprobado de Carlos conservado. Sin despliegue.
La completa conjunta reciente 976/6 omitidas verde no cubría H5. La verificación no aprueba T52. Continúa T42 y después T43 según la orden de Carlos.
