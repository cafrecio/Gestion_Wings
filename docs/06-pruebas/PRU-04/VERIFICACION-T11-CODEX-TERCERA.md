# T11 — Tercera verificación de Codex

16/10/2026 · Codex CyE · entrega documental examinada `51059f6`.

**Chrome local: aprobado en los controles repetidos. Se corrigió un enlace documental
que devolvía una página 404.** Durante esta revisión entró `512f375`, que
cierra T11 y registra que Carlos la instaló y probó en su teléfono. Se conserva ese
cierre; esta revisión no certifica por sí misma aquella prueba física ni despliega.

## Evidencia propia

- `PwaInstalacionTest`: tres pruebas, 63 aserciones, 7,05 s, aprobadas en
  `wings_testing_codex`. Leídos sus tres cuerpos y `WebController::loginForm`:
  el ingreso conserva la redirección por rol con sesión activa.
- Repetí el generador de Gemini con una carpeta de evidencia propia y perfiles
  nuevos. El primer intento no pudo iniciar Chrome; con ejecución autorizada fuera
  del entorno restringido terminó correctamente. Solo PHP local, `APP_ENV=testing`,
  base propia, sesión y caché en memoria; no se ingresó en el sitio del club.
- El panel **Application > Manifest** muestra `Errors and warnings` vacío,
  `start_url=/login`, `display=standalone` y ambas capturas reconocidas.
  [Captura superior](evidencia-t11-codex-tercera/chrome-devtools-manifest.png),
  [capturas reconocidas](evidencia-t11-codex-tercera/chrome-devtools-manifest-screenshots.png),
  [árbol de accesibilidad](evidencia-t11-codex-tercera/application-manifest-dom.txt).
- CDP devuelve `manifest.errors=[]` e `installabilityErrors=[]`.
  [Resultado](evidencia-t11-codex-tercera/cdp-installability.json).
  El indicador general de Issues muestra una mejora posible: no se presenta como
  ausencia de avisos en todo Chrome; el criterio medido es el panel Manifest.
- Abrí las dos imágenes públicas: muestran el ingreso y textos de ejemplo, sin
  datos de personas o dinero. Verificador independiente de dimensiones, MIME real
  y proporción: [programa](evidencia-t11-codex-tercera/comprobar-archivos.php),
  [resultado](evidencia-t11-codex-tercera/archivos-comprobados.json).

## Documentación y observación

La sección 3 ahora declara PNG/JPEG y usa los dos enlaces válidos solicitados en
la segunda devolución. Comprobados contra
[la guía de web.dev](https://web.dev/articles/web-apps/richer-install-ui) y
[el artículo de Chrome](https://developer.chrome.com/blog/richer-pwa-installation).
Los tamaños, proporciones, formatos, máximo de ocho y descripción recomendada
coinciden con la guía consultada el 16/10.

Sin embargo, la sección 2 agregó como fuente de Chrome 108/112:
`https://developer.chrome.com/docs/web-platform/install-criteria`.
Abrí ese enlace en Chrome: muestra **404 · No se encontró la página**.
[Pantalla](evidencia-t11-codex-tercera/enlace-install-criteria-404.jpg),
[texto leído](evidencia-t11-codex-tercera/enlace-install-criteria-404.txt).
El navegador real confirma el fallo; el error del buscador, por sí solo, no lo probaba.

Codex sustituyó ese enlace en el informe por la fuente oficial
[Revisiting Chrome's installability criteria](https://developer.chrome.com/blog/update-install-criteria),
que respalda el cambio del requisito de Service Worker desde Chrome 108 móvil y
112 escritorio. También actualizó estado y próximo paso según el cierre registrado.
No se modificaron los PNG ni el manifest.
El inspector y la suite propia de esta vuelta quedan registrados; no se heredan
las capturas de Gemini. El enlace roto no se usa para reabrir el cierre posterior
registrado por Claude. El control automático rechazó consultar el chat privado
de Claude por falta de autorización para ese destino: no se leyó la conversación
citada por el commit ni se atribuye a esta revisión una confirmación directa de Carlos.

## Suite y límites

La corrida completa propia terminó con salida 1: **724 aprobadas, 2 omitidas y
un fallo documental; 6943 aserciones, 1085,72 s**. Ejecutó 727 pruebas. Después de
su descubrimiento de métodos, Claude agregó en la carpeta compartida la sexta
prueba de T3: el árbol quedó con 728, mientras los documentos declaraban 727.
El único fallo es `DocumentacionNoMienteTest`; no se atribuye a T11 ni se afirma
suite completa verde. Codex actualizó los cuatro contadores: el guardián aislado
posterior pasa (1 prueba, 7 aserciones), sin tocar pruebas. La sexta prueba de T3
se ejecutó en la corrida acotada de seis pruebas/27 aserciones, no en esta suite.
Se conserva la salida completa en
[suite-completa.txt](evidencia-t11-codex-tercera/suite-completa.txt).

No se probó instalación física, iPhone, frescura de pantallas de dinero sin conexión
ni vencimiento natural de sesión en esta vuelta. Tampoco se midió el sitio de prueba
o producción. No se modificó aplicación, vistas, CSS, pruebas existentes ni reloj.
