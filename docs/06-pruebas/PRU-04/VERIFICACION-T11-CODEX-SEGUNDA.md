# T11 — Segunda verificación de Codex

14/10/2026 — Codex CAB — commit examinado `f6bea354e9e3c08af644aecab44d0dadcc958b36`.

**Resultado: no aprobada; devolución documental a Gemini y verificación de ejecución bloqueada por el entorno.** No se cierra T11 ni se pasa todavía a Carlos. No se desplegó ni se midió el sitio de prueba. Las evidencias de Chrome de Gemini no sustituyen una medición propia.

## Comprobaciones propias

| Control | Resultado |
|---|---|
| Imágenes reales | `getimagesize` devuelve PNG 1280×720 y 720×1280, exactamente los `sizes` del manifest. Ambas tienen proporción máxima/mínima 1,777778, menor que 2,3, y lados entre 320 y 3840. Una imagen por form_factor: no hay discrepancia de proporciones dentro de cada grupo. |
| Contenido | Abrí las dos imágenes: muestran el ingreso, sin nombres de personas, alumnos, importes ni direcciones personales. `tu@email.com` es texto de ejemplo. |
| Fuente oficial | [How to add Richer Install UI](https://web.dev/articles/web-apps/richer-install-ui), consultada el 14/10, apartados Screenshots/Description: lados 320–3840, proporción hasta 2,3, proporciones iguales por form_factor, **solo JPEG y PNG**. Descripción recomendada, no obligatoria. Los PNG entregados cumplen estas condiciones. |
| Apache | Sección 5 corregida a Apache, mod_mime y AllowOverride All. La única aparición de Nginx es «no Nginx»; no queda configuración/instrucción Nginx. Contraste con el archivo Apache conservado de la primera verificación, sin certificación nueva del servidor. |
| Caché | Búsqueda en todo IMPLEMENTACION-T11.md de frescura, pantallas viejas y caché: distingue SW/Cache storage de caché HTTP/BFCache y declara el escenario de dinero no probado. No queda promesa de frescura universal. La cita del pedido de Carlos expresa un requisito, no un resultado. |
| Despliegue | El informe distingue primera entrega y segunda sin desplegar. `d4f02d4` identifica la primera entrega y la evidencia histórica; **no se comprobó el HEAD actual del sitio**. No debe leerse como certificación nueva de su versión actual. |
| Archivos prohibidos | `git show --stat f6bea35` y nombres del commit: ninguna vista ni CSS. Diff local de resources/views/resources/css vacío. Archivos examinados sin diferencia respecto del commit. |

SHA256 de las capturas examinadas:

- wide: `a053e30c0d157ab32baac1f4b801729651d1e480f43561b4838115dfd00929a5`.
- narrow: `5490b1095a4ea4c5659054981de4b8acc7e919b2c8deddef122a5f8194907889`.

## Qué comprueban realmente las pruebas nuevas

Leído el cuerpo completo de `tests/Feature/PwaInstalacionTest.php`. El agregado comprueba descripción y screenshots no vacíos, existencia de archivos, sizes y label no vacíos, **texto** type=image/png, dimensiones declaradas entre 320 y 3840, imagen reconocible y dimensiones reales iguales a las declaradas; exige wide y narrow.

No compara `getimagesize()['mime']` con image/png: una imagen de otro formato reconocible con dimensiones iguales puede pasar. Tampoco controla relación máxima 2,3 ni proporciones iguales por form_factor. No comprueba HTTP/MIME servido, avisos del navegador, privacidad de imágenes ni instalación física. Los tres controles previos siguen cubriendo JSON/valores/iconos existentes, enlace del login y redirección de sesión activa por rol. `php -l` correcto.

## Bloqueos de ejecución comprobados

- Intenté la suite exclusivamente con `DB_DATABASE=wings_testing_codex`. PHPUnit finaliza antes de ejecutar pruebas con `Cannot open bootstrap script ... vendor/autoload.php`. El archivo existe y puede leerse/incluirse, pero PHP `is_readable` devuelve false en este entorno. **No hay resultado de suite ni pruebas aprobadas propias hoy**. [Salida original](evidencia-t11-codex-segunda/suite-intento.txt).
- Computer Use falla al iniciar (`failed to launch codex app-server: program not found`); cua_repl falla con `cannot locate required Windows platform directory: HRESULT 0x80070003`.
- Intento alternativo con el generador existente en copia temporal y carpeta de salida propia: conexión CDP loopback rechazada `connect EACCES 127.0.0.1:61182`. No se obtuvo Application > Manifest propio ni captura. No se inventó evidencia.
- Codebase-memory no disponible; búsqueda directa. No se modificaron producto, base del club, reloj ni servidor.

## Devolución concreta y siguiente paso

Gemini: en IMPLEMENTACION-T11.md, sección 3, corregir la afirmación de soporte WebP: la fuente oficial consultada dice solo JPEG y PNG. Corregir además el enlace `https://developer.chrome.com/blog/richer-pwa-installation-ui`, que devuelve error, por [Richer PWA installation UI](https://developer.chrome.com/blog/richer-pwa-installation) y enlazar la guía de requisitos anterior. Esto no requiere cambiar los PNG válidos ni el producto.

Después Codex debe repetir Application > Manifest local y la suite completa en su base, desde un entorno que permita esas operaciones. Solo entonces puede aprobar y pasar a Carlos, quien necesita despliegue y prueba en teléfono. **Sitio de prueba sin medir en esta segunda vuelta; no desplegar por esta devolución.**
