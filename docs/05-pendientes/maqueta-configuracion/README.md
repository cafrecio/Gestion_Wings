# A11 — propuesta visual, 04/10/2026

**Maqueta y línea Diseno-autorizado aprobadas por Carlos el 04/10. A11 entregada; pendiente de Gemini.**
La línea fue escrita personalmente por Carlos, después de aprobar la maqueta.
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
  ENT-01 (validación por clave en `ConfiguracionWebController.php` e `InscripcionService::importe`).
- Días de gracia enteros del 1 al 28. Correo vacío conserva el destino ADMIN;
  Telegram vacío conserva el destino de respaldo si existe (`TelegramChannel.php:55–56`);
  se corrigió la explicación inicial de la maqueta sin cambiar el envío. Correo no vacío debe ser válido.
- Reglas de primera cuota permanecen en sus tramos actuales, incluidos los días
  29–31. La maqueta muestra el resumen; la implementación debe conservar su edición.

## Un dato que hoy parece editable y no gobierna nada

Verificado: `routes/console.php:12` fija la generación en día 1 a las 06:00.
`dia_generacion_deuda` no se lee para ese cron (búsqueda del literal en `app/` y
`routes/`). El contrato de Punitorios §2 ya registra esta diferencia.
La propuesta es mostrar **1 · Fijo**, sin input ni Guardar, y rechazar su edición
en el servidor. No se propone cambiar el scheduler ni eliminar datos existentes.
Esto queda incluido en la revisión de Carlos antes de implementar.

## Entrega y siguiente paso

Carlos escribió:

```text
Diseno-autorizado: Carlos aprueba A11 Configuración según la maqueta presentada, conservando el diseño Wings.
```

[Implementación y pruebas](../../06-pruebas/PRU-02/IMPLEMENTACION-A11.md):
grupos, explicaciones, validación de servidor, Guardar explícito y errores persistentes.
El editor de primera cuota conserva sus tramos hasta el 31 y su edición.
A11 queda entregada; Gemini verifica en pantalla y código antes de cerrarla.
Sin despliegue. Las capturas de esta carpeta siguen siendo la propuesta estática;
las de la implementación están enlazadas en el informe.
