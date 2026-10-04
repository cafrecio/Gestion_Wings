# A11 — Configuración entregada, pendiente de Gemini

04/10/2026 · Codex CAB. Sin despliegue; no se cierra el defecto.

## Autorización de Carlos

La maqueta fue aprobada y después Carlos escribió personalmente:

```text
Diseno-autorizado: Carlos aprueba A11 Configuración según la maqueta presentada, conservando el diseño Wings.
```

## Cambio comprobado

- Tres grupos: La plata, La cobranza y Los avisos. Etiquetas en castellano y
  explicación del efecto en el club, sin claves internas como títulos.
- Guardar explícito por campo. El servidor confirma antes de mostrar Guardado;
  los valores rechazados permanecen para corregirlos.
- Validación de servidor: inscripción obligatoria, positiva y de hasta dos
  decimales; días de gracia enteros del 1 al 28; correo válido si se completa;
  chat de Telegram numérico, también admite IDs negativos de grupos.
- Errores arriba y junto al campo, con enlace para corregir. Permanecen visibles;
  no los retira el cierre automático general de avisos. Aviso al salir con una
  configuración modificada y sin guardar.
- Generación mensual como texto fijo: día 1 a las 06:00, hora Argentina.
  Se rechaza también modificar el parámetro por una petición directa.
- Se conserva el editor de porcentajes de la primera cuota (días 1–31), con
  mensajes de validación en castellano. No se modifican importes de deudas creadas.
- JavaScript propio en `resources/js/configuraciones.js`, registrado en Vite;
  ningún script incrustado ni handler en las vistas de Configuración. El contador
  CSP baja de 20 a 19 y explica la extracción; los 10 handlers restantes no cambian.

Fuentes revisadas: `ConfiguracionWebController.php:39` y `:48`,
`ReglaPrimerPagoWebController.php:51`, `configuraciones/index.blade.php:8`,
`configuraciones/_campo.blade.php`, `configuraciones/_regla.blade.php`,
`configuraciones.js:28` y `:50`; horario real en `routes/console.php:12`.
No hay cambios propios en `resources/css/app.css` ni en componentes compartidos.

## Pruebas

Antes de implementar: **7 fallos / 1 correcta, 58 aserciones**, como registra
[la preparación](A11-PREPARACION-2026-10-04.md). En particular, el servidor ya
rechazaba −100; el problema comprobado allí era su mensaje y su ausencia en pantalla,
no que ese importe quedara almacenado.

Después: las ocho pruebas A11 y las dos de CSP pasan (**10 / 100**).
La primera suite completa contra `wings_testing` coincidió con otra corrida:
74 fallos con tablas que desaparecían, 281 correctas. No se usa como certificación.
Se incorporó durante el turno `AGENTS.md` §6-bis y se repitió con la base exclusiva
`wings_testing_codex`: **355 pruebas / 2070 aserciones**, verde, 122,62 s.

Ese corte incluye tres pruebas de Cobranza ajenas todavía sin commit. Se preparó
además una copia de HEAD `af9d8b9` más exclusivamente los nueve archivos de código,
vista, Vite y pruebas de A11, con dependencias copiadas y la misma base descartable
de Codex: **352 pruebas / 2057 aserciones, todas verdes**, 177,13 s. Ese es el corte
versionado de esta entrega. SHA-256 de los nueve archivos coincide con la copia
probada. No se incorpora código ni pruebas ajenos. [Resumen](evidencia/configuracion-a11/suite-entrega.txt).

PHP sin errores, JavaScript comprobado, Vite compila y Blade compila/se limpia.

## Navegador local

ADMIN, `http://gestion-wings/configuraciones`, escritorio y **375 × 720**:

- Inscripción −100, gracia 29 y correo incorrecto rechazados; errores visibles
  arriba y junto a sus campos, sin desaparecer después de varios segundos.
- Corrección y guardado con los valores originales: 5000,00, día 10 y correo vacío.
  Aparece Guardado; al recargar permanecen los valores vigentes.
- Regla existente: porcentaje 0 rechazado; corregirlo al 100 vigente cierra el editor
  y mantiene el resumen. Nuevo vacío devuelve todos sus errores; Cancelar no crea regla.
- A 375: documento y contenido de 375 px, inputs dentro del ancho disponible;
  acciones de tarjetas uniformes de **96 × 32 px**, sin cortar sus textos.

[Pantalla](evidencia/configuracion-a11/configuracion-desktop.png),
[errores](evidencia/configuracion-a11/errores-desktop.png),
[errores completos](evidencia/configuracion-a11/errores-completos.png),
[celular](evidencia/configuracion-a11/configuracion-375.png) y
[error de porcentaje](evidencia/configuracion-a11/regla-error-375.png).

## Precisión y límites

La maqueta decía que Telegram vacío desactivaba ese canal. El cuerpo de
`TelegramChannel.php:55–56` demuestra un respaldo de destino; se corrigió únicamente
la explicación para decir que dejarlo vacío conserva ese respaldo, si existe.
No se cambió el envío de avisos, el token, el correo del servidor ni el scheduler.
La creación/eliminación exitosa de reglas no se ejecutó en la base de trabajo.
La base local se usó únicamente para el recorrido descrito, sin cobros ni cambios
en los valores efectivos de Configuración. Las capturas corresponden al checkout
compartido, cuyo CSS global contiene trabajo ajeno; ese CSS no entra en esta entrega.
La comprobación independiente en pantalla y código sigue a cargo de Gemini.
