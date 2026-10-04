# A11 — Verificación independiente de Configuración

04/10/2026 · Gemini CAB · **APROBADA**.

Revisión independiente del commit `7a8fe09` (implementación de Codex CAB). Se inspeccionaron controladores, vistas, scripts y pruebas en código, se ejecutó la suite completa en la base exclusiva `wings_testing_gemini` y se interactuó con la pantalla real en navegador Chrome visible tanto en escritorio (1280×950) como en móvil (375×812).

---

## 1. Qué se comprobó

| Criterio | Resultado y verificación |
|---|---|
| **Agrupación y nombres humanos** | Verificado en `ConfiguracionWebController.php:12-37` y en pantalla: secciones claras «La plata», «La cobranza» y «Los avisos». Claves internas reemplazadas por nombres claros («Inscripción al club», «Días de gracia para pagar», etc.) con explicaciones concisas y texto de ayuda en castellano. |
| **Generación de cuotas fija** | Verificado: tarjeta informativa que fija el día 1 a las 06:00 (hora Argentina) como dato inmutable. No expone input en pantalla y el controlador rechaza peticiones directas de modificación con error de validación (probado en `ConfiguracionA11Test:94-105`). |
| **Validación en servidor** | Verificado con valores inválidos: inscripción negativa (-100), días de gracia fuera de rango (29) y correo con formato erróneo son rechazados con mensajes precisos en castellano. No se persiste ningún valor inválido. |
| **Visibilidad y persistencia de errores** | Verificado en pantalla: ante errores de validación, se despliega el resumen superior (`#configuracion-error-resumen`) con enlaces a los campos y mensajes de error específicos debajo de cada input (`.ds-flash--error`). Se comprobó que **permanecen visibles** y no se ocultan automáticamente tras 5 segundos. |
| **Guardado explícito y asíncrono** | Verificado: cada campo cuenta con su botón «Guardar». Al corregir a valores válidos (ej. 5000,00 e inscripción 10), la petición PATCH asíncrona responde `ok: true`, limpia los mensajes de error y muestra el indicador «Guardado» junto al botón. |
| **Reglas de primer pago** | Verificado editor inline y creación: al ingresar porcentaje 0 en una regla existente, el servidor rechaza con «El porcentaje va del 1 al 100.» y se muestra en pantalla. El botón «Cancelar» cierra el editor y descarta los errores. Superposición de días validada en `ReglaPrimerPagoSinSuperposicionTest`. |
| **JavaScript sin código incrustado** | Verificado: lógica extraída a `resources/js/configuraciones.js` e integrada a Vite. No hay bloques `<script>` ni atributos `on...=` en las vistas de configuración. `CspSinCodigoIncrustadoTest` pasa en verde. |
| **Comportamiento en móvil (375 px)** | Verificado en navegador a 375×812: tarjetas, tipografía y botones se adaptan sin desbordes horizontales ni solapamientos. Botones de acción accesibles. |
| **Suite de pruebas** | Verificado: `ConfiguracionA11Test` (8 pruebas / 98 aserciones) y `ReglaPrimerPagoSinSuperposicionTest` (5 pruebas / 39 aserciones) pasan 100% verde en `wings_testing_gemini`. |

---

## 2. Evidencia capturada

Capturas generadas durante la sesión de verificación:
- **Vista general en escritorio:** [`evidencia/configuracion-a11/gemini/verificacion_a11_desktop.png`](evidencia/configuracion-a11/gemini/verificacion_a11_desktop.png)
- **Errores visibles y persistentes:** [`evidencia/configuracion-a11/gemini/verificacion_a11_errores_desktop.png`](evidencia/configuracion-a11/gemini/verificacion_a11_errores_desktop.png)
- **Guardado exitoso con estado «Guardado»:** [`evidencia/configuracion-a11/gemini/verificacion_a11_valores_restaurados.png`](evidencia/configuracion-a11/gemini/verificacion_a11_valores_restaurados.png)
- **Error en edición de regla de primer pago:** [`evidencia/configuracion-a11/gemini/verificacion_a11_regla_error.png`](evidencia/configuracion-a11/gemini/verificacion_a11_regla_error.png)
- **Vista en móvil a 375 px (sección 1):** [`evidencia/configuracion-a11/gemini/verificacion_a11_mobile_375.png`](evidencia/configuracion-a11/gemini/verificacion_a11_mobile_375.png)
- **Vista en móvil a 375 px (sección 2):** [`evidencia/configuracion-a11/gemini/verificacion_a11_mobile_375_seccion2.png`](evidencia/configuracion-a11/gemini/verificacion_a11_mobile_375_seccion2.png)

---

## 3. Conclusión y dictamen

La implementación de A11 en el commit `7a8fe09` cumple integralmente con el diseño aprobado por Carlos, respeta el Design System sin agregar CSS innecesario, mantiene los contratos de negocio y asegura la usabilidad y persistencia de validación.

**Dictamen:** Aprobada. El defecto **A11** queda verificado y en condiciones de darse por cerrado.
