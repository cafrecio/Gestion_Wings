# P1 — primera carga por Excel · entrega 05/10/2026

Autor: Codex CAB. **IMPLEMENTADA; pendiente de verificación independiente de Gemini.**
No cerrada, no desplegada. Maqueta aprobada por Carlos el 05/10.
Base de entrega: main `f1df4fd`; los cambios simultáneos ajenos de P2 no se incluyen.

## Qué se entrega

1. Plantilla generada por Wings: Alumnos vacía (38 columnas, 200 filas preparadas,
   12 pares O–AL), Catálogos de planes/grupos/deportes activos con precio y Guía.
   Desplegables J–N; los ejemplos están solo en Guía, nunca en Alumnos.
2. Revisión de todas las filas sin escritura. Todos los errores: fila, columna,
   mensaje y esperado. Informe con la misma planilla y Errores en AM; valores y
   tipos originales conservados. Archivo ilegible se informa, no genera un informe falso.
3. Confirmación con cero errores: revalidación de archivo, catálogos y resumen;
   transacción única. Alumno/deporte, plan desde ingreso real, inscripción declarada
   única por DNI y exactamente los saldos declarados. Sin descuentos automáticos,
   creación de cuotas por fecha, pagos, imputaciones ni entradas de caja.
4. Deshacer: borra solamente lo creado por esta carga, conserva usuarios/catálogos.
   Rechaza cualquier cobro, incluso anulado, y nueva actividad/deudas/cargos/planes.
   Bloqueos de persona y alumno compatibles con el orden del servicio de cobro.

`primera_carga` guarda PENDIENTE/TERMINADA, autor e IDs de esta importación. No se
deduce el estado contando alumnos. ADMIN entra automáticamente al recorrido; alta
individual y acceso directo no lo saltean. Otros roles reciben 403 en las siete
acciones P1. Catálogos/configuración siguen accesibles. Al terminar vuelve el acceso
habitual y desaparece Primera carga del menú; la URL ADMIN conserva Deshacer.

Importes y períodos comparten `FormatoExcelCargaService`, extraído del padrón,
sin reinventar su criterio. DNI con puntos/espacios, nombres con espacios sobrantes,
92026, 082026 y texto 52.000 admitidos con su significado correcto.
Reglas de catálogos, formatos, fechas, tutor, Sí/No y pares se explican en pantalla.
El plan debe pertenecer al grupo/deporte y tener precio; P1 no crea catálogos.

## Verificaciones propias

- Antes de implementar: 16 pruebas P1 **rojas**, 4 aserciones; 29,74 s. El código
  anterior no tenía las clases/rutas P1. No se cambia la prueba histórica de A43.
- Pruebas P1 definitivas: **20 aprobadas / 548 aserciones**, 50,79 s; repetidas
  después de la revisión del informe descargado, en la misma copia exclusiva.
- Suite completa: ejecución en copia exclusiva sobre main + entrega P1, únicamente
  `wings_testing_codex`; resultado final se registra abajo.
- Casos: seis errores juntos sin escritura; informe conserva valores/tipos; plantilla
  vacía; 4 alumnos/6 cuotas $296.000/1 inscripción $5.000; catálogos inexistentes,
  ajenos, inactivos o sin precio; última fila inválida; rollback por excepción final;
  doble carga; Sí/No independientes; 12.º par; formatos conocidos; DNI multideporte;
  fórmulas/cabeceras/archivo ilegible; sesión, hash y confirmación; roles; bloqueo de
  alta por URL aun habiendo un alumno; Deshacer libre, tras cobro anulado y nueva deuda.
- Navegador real local: cuatro pasos, descargar plantilla, subir archivo malo y ver
  los seis errores, descargar Excel marcado, revisar correcto, confirmar carga y
  deshacer. Error propio de navegación tras descargar corregido y recorrido repetido.
- Inspección de filas después de cargar: Ana, Bruno, Carla y Diego (ficticios);
  cuotas 48.000 + 52.000 + 52.000 + 48.000 + 48.000 + 48.000, cargo 5.000.
  Total de deuda **301.000**; cero pagos, movimientos operativos y cashflow.
  Después de Deshacer: PENDIENTE, sin alumnos/deudas/cargos; usuarios/catálogos intactos.
- Diseño: DS existente, sin CSS ni componentes DS modificados. Escritorio y 375 px,
  cuatro pasos e informe; captura de confirmación y resultado. Al agregar un enlace
  condicional al menú compartido, también se capturaron sus 15 pantallas ADMIN.
  [Índice de capturas](capturas-p1/README.md).

## Resultado final

`php artisan test` completo en copia exclusiva de entrega: **404 aprobadas,
2815 aserciones; 134,16 s**, MariaDB `wings_testing_codex`. Incluye el guardián
de documentación, CSP, roles y regresiones de Cobranza, recibos y alta.
PHP sin errores en los 12 archivos propios; Blade compila y se limpia; build verde
(8,67 s). Sin CSS ni componentes DS modificados; solo vista P1 y enlace de menú.

El control del XLSX descargado descubrió tres textos vacíos que el writer convertía
en celdas nulas. Corregido: el informe copia el contenedor original y agrega solamente
AM, sin reserializar datos originales. Prueba reabre el archivo final, compara las
190 celdas A:AL por valor y tipo y admite revisarlo de nuevo con los mismos seis errores.
No se presenta una comparación en memoria como verificación del archivo guardado.
Repetida la descarga por navegador de la entrega final: **7638 celdas A:AL**
(201 filas), con valores y tipos conservados, incluidos los textos vacíos; AM Errores correcto.
Repetidos ingreso ADMIN automático, descarga y revisión; acceso Sistema a 375 capturado.

## Alcance y pase a Gemini

El dato «producción sin alumnos/deudas/pagos» lo informó Carlos el 05/10;
**no es una inspección propia**. Esta entrega no toca producción, servidor,
`gestion_wings`, sitio de prueba ni usuarios reales. No corre seeders ni limpia bases.
Se conservan los cambios ajenos de Gemini sin incluirlos en este commit.

Gemini debe repetir el flujo en su base propia, comprobar pantalla/archivo/filas y
revisar servidor/roles/transacción/Deshacer antes de aceptar. Codex no cierra su tarea.
Excel de escritorio no fue controlado: las listas y tipos se comprueban en el XLSX
generado; no se presenta eso como una prueba nativa de Excel.

Tras aceptación independiente, retirar `wings:importar-padron` y
`wings:importar-deuda-inicial` en **otro commit**, revisando pruebas/llamadores y
actualizando sus instrucciones. Siguen disponibles en esta entrega para no retirarlos
antes de aceptar el reemplazo.

## Despliegue posterior — no ejecutado

- Backup y comprobación real del estado del club antes de desplegar.
- Migración nueva `2026_10_05_180000_create_primera_carga.php` y build de Vite son
  obligatorios, en ventana de mantenimiento: menú/entrada consultan esa tabla.
- Migración crea PENDIENTE, no limpia alumnos/usuarios/catálogos ni corrige precios.
  No aplicar sin decisión a un club ya operativo con alumnos existentes.
- Entregar plantilla vacía del catálogo real a Vanina. El ejemplo ficticio se usa
  solo para pruebas, nunca se importa en producción.
- Archivo de revisión en disco local privado, ligado a usuario/sesión y SHA-256;
  se elimina al reemplazarlo o cargar. Un archivo abandonado puede quedar privado
  en storage hasta limpieza operativa; no se publica ni se versiona.
- No revertir la migración después de una carga real: primero evaluar datos y
  backup; Deshacer solo antes de actividad posterior y jamás tras cobros.

Contrato vigente: [Alumno–grupo–deporte–deuda V4](../../02-contratos/Wings-contrato-alumno-grupo-deporte-deuda-v4.md).
