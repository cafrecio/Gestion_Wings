# A11 — propuesta visual, 04/10/2026

**Pendiente de autorización escrita por Carlos. No implementada.**
[Pantalla normal](index.html) y [ejemplo de errores](errores.html).
Las páginas son estáticas; no envían formularios, no guardan y no cargan JavaScript.
Usan únicamente las clases y el CSS compilado existente de Wings, sin CSS nuevo.
En esta máquina requieren la aplicación local para cargar su CSS y las fuentes.
Se mostraron en el navegador en `http://127.0.0.1:8770/`.
Revisadas en escritorio y 375 × 720: sin desborde de página, botones de 96 × 32.
[Vista completa](vista-escritorio.png), [errores](errores-escritorio.png) y
[errores en celular](errores-375.png). No son evidencia de validación implementada.

Para volver a abrirlas desde la raíz del repositorio: `php -S 127.0.0.1:8770 -t docs/05-pendientes/maqueta-configuracion`.
El CSS enlazado es el asset ya compilado de esta máquina; si otro build cambia su
nombre, actualizar únicamente el enlace al CSS existente, sin crear estilos.

## Qué se propone

- Tres grupos: La plata, La cobranza y Los avisos.
- Nombre en castellano, explicación del efecto y etiqueta visible en cada campo.
- Guardar explícito por valor; no guardar silenciosamente al salir del campo.
- Mensaje de error arriba, con enlaces a los campos, y al lado del valor incorrecto.
- Conservar lo ingresado al fallar y anunciar Guardado cuando el servidor confirme.
- Inscripción obligatoria y positiva, con hasta dos decimales: conserva la regla de
  ENT-01 (`ConfiguracionWebController.php:29-30` e `InscripcionService.php:35-40`).
- Días de gracia enteros del 1 al 28. Correo vacío conserva el destino ADMIN;
  Telegram vacío desactiva solo ese canal. Correo no vacío debe ser válido.
- Reglas de primera cuota permanecen en sus tramos actuales, incluidos los días
  29–31. La maqueta muestra el resumen; la implementación debe conservar su edición.

## Un dato que hoy parece editable y no gobierna nada

Verificado: `routes/console.php:12` fija la generación en día 1 a las 06:00.
`dia_generacion_deuda` no se lee para ese cron (búsqueda del literal en `app/` y
`routes/`). El contrato de Punitorios §2 ya registra esta diferencia.
La propuesta es mostrar **1 · Fijo**, sin input ni Guardar, y rechazar su edición
en el servidor. No se propone cambiar el scheduler ni eliminar datos existentes.
Esto queda incluido en la revisión de Carlos antes de implementar.

## Siguiente paso

Carlos mira ambas pantallas y escribe su propia línea `Diseno-autorizado:`.
El agente no redacta ni atribuye esa autorización. Luego: pruebas en rojo,
validación en servidor, vista con diseño aprobado, archivo `resources/js/configuraciones.js`
registrado en Vite, extracción del script incrustado actual, errores visibles y suite
completa. A11 queda entregado con commit para que Gemini lo verifique; no lo cierra Codex.
