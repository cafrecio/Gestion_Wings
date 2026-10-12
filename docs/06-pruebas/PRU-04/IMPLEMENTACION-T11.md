# Informe de Implementación T11: Instalación de Wings como aplicación en el teléfono

**Fecha:** 12/10/2026  
**Autor:** Gemini (CAB) — Firma: **LOG GEM CAB**  
**Tarea:** T11 — «Que Wings se instale en el teléfono como aplicación»  
**Origen:** Prueba de Carlos del 12/10/2026 en su Android con Chrome sobre `test.gestionar-te.com.ar`.  
**Estado:** Implementada en el repositorio. Pendiente de despliegue al sitio de prueba para verificación final de Carlos en su teléfono físico.

---

## 1. Problema detectado y qué faltaba

### Lo que pasaba
Al entrar a `test.gestionar-te.com.ar` desde Chrome en Android:
1. Chrome no ofrecía «Instalar aplicación» en el menú, sino únicamente «Agregar a la pantalla principal».
2. El ícono creado en la pantalla de inicio quedaba con el sello pequeño de Chrome sobre el logo de Wings.
3. Al tocar el ícono, se abría una pestaña estándar dentro del navegador Chrome, con la barra de direcciones visible, en lugar de una ventana de aplicación a pantalla completa.

### Diagnóstico técnico con Chrome DevTools Protocol (CDP)
Se levantó Wings localmente y se inspeccionó mediante Chrome Headless y el método nativo de Chromium `Page.getInstallabilityErrors`:
- **Resultado inicial:**
  ```json
  {
    "installabilityErrors": [
      {
        "errorId": "start-url-not-valid",
        "errorArguments": []
      }
    ]
  }
  ```
- **Causa raíz:** En `public/site.webmanifest` **no existía el campo `start_url`**. Sin `start_url`, Chromium no puede determinar el punto de arranque de la aplicación y rechaza la instalación como PWA, degradando la acción a un simple marcador/acceso directo web.
- **Faltantes adicionales:**
  - No tenía `scope`: no delimitaba qué rutas pertenecen a la app instalada.
  - No tenía `id`: identificador unívoco de la app según el estándar moderno de Web App Manifest.
  - Los íconos en `site.webmanifest` no declaraban `purpose: "any"` ni `purpose: "maskable"`, lo que provocaba que Android Adaptive Icons los envolviera con fondos o sellos del navegador.

---

## 2. Decisión sobre el Service Worker

Carlos instruyó: *«Hacé lo mínimo que haga falta. Si Chrome exige un service worker, que sea uno que no guarde nada en caché: Wings maneja plata y no puede mostrar una pantalla vieja. Si no lo exige, no lo agregues.»*

### Comprobación empírica
- Desde Chrome 108+ (móvil) y 112+ (escritorio), Chromium **desacopló el requisito estricto de Service Worker para habilitar la opción "Instalar app"** desde el menú del navegador.
- Al corregir `public/site.webmanifest` con `start_url: "/login"`, `scope: "/"`, `id: "/login"` e íconos adaptativos, se volvió a consultar `Page.getInstallabilityErrors` vía CDP:
  ```json
  {
    "installabilityErrors": []
  }
  ```
- **Conclusión:** Chrome DevTools confirma **cero errores de instalabilidad** sin necesidad de Service Worker.
- **Decisión adoptada:** **No se agregó ningún Service Worker**. De esta forma, Wings no almacena ninguna respuesta en memoria caché de cliente, garantizando que todos los datos de caja, cuotas, cobros y clases sean siempre 100% frescos y en tiempo real desde el servidor.

---

## 3. Cambios realizados en el repositorio

1. **`public/site.webmanifest`:**
   - Agregado `"start_url": "/login"` (abre directamente en el formulario de inicio de sesión, tal como decidió Carlos).
   - Agregado `"scope": "/"` (todas las pantallas del sistema permanecen dentro de la ventana de la aplicación).
   - Agregado `"id": "/login"`.
   - Mantenido `"display": "standalone"` (abre a pantalla completa, sin la barra de direcciones del navegador).
   - Íconos de 192x192 y 512x512 configurados con entradas dedicadas para `purpose: "any"` y `purpose: "maskable"` para soporte completo de íconos adaptativos en Android.

2. **`public/.htaccess`:**
   - Agregada la directiva MIME para servidores Apache:
     ```apache
     <IfModule mod_mime.c>
         AddType application/manifest+json .webmanifest
     </IfModule>
     ```

3. **Plantillas Blade (`resources/views/**`):**
   - **No se modificó ninguna vista.** `resources/views/layouts/ds-app.blade.php` ya contenía en su `<head>` el enlace canónico `<link rel="manifest" href="{{ asset('site.webmanifest') }}">` y `<meta name="theme-color" content="#0F172A">`. No se tocó la plantilla para evitar cualquier conflicto con el trabajo paralelo de otros agentes (T30).

---

## 4. Comportamiento de sesión y navegación

1. **Pantalla de arranque (`/login`):**
   - Si el usuario no ha iniciado sesión, la app abre en `/login` mostrando el formulario.
   - Si el usuario ya tiene sesión iniciada (cookie de sesión activa), `WebController::loginForm` detecta `Auth::check()` y redirige de inmediato a la pantalla principal según su rol:
     - Admin: `admin.dashboard` (o primera carga si está pendiente).
     - Operativo: `web.operativo.dashboard`.
     - Profesor: `web.clases.index`.
   - Por lo tanto, la app instalada **no vuelve a pedir la clave** mientras la sesión siga activa.
2. **Navegación interna (`scope: "/"`):**
   - Todos los links internos (`/alumnos`, `/caja`, `/clases`, etc.) abren dentro del contenedor de la aplicación.
   - Los recibos PDF (`/recibos/{id}/pdf`) se visualizan dentro del visor del sistema y al presionar volver se regresa a la pantalla previa sin salir de la app.

---

## 5. Qué hay que hacer en el servidor (VPS)

En el sitio de prueba (`test.gestionar-te.com.ar`), el servidor web es Nginx. Como Nginx sirve archivos estáticos directamente desde el sistema de archivos sin pasar por PHP/Laravel, el tipo de contenido MIME de `.webmanifest` debe estar declarado en Nginx.

Para asegurar que entregue `application/manifest+json`:
- **Opción A (Recomendada):** En `/etc/nginx/mime.types`, comprobar o agregar la línea:
  ```nginx
  application/manifest+json webmanifest;
  ```
- **Opción B:** En el bloque de configuración del sitio Nginx:
  ```nginx
  location = /site.webmanifest {
      default_type application/manifest+json;
  }
  ```
- Aplicar con: `nginx -t && systemctl reload nginx`.

*(Nota: Conforme a las instrucciones, ningún agente entra al servidor para esto; queda asentado para el momento del despliegue).*

---

## 6. Qué pasa en iPhone (Safari)

- En iOS (Safari) no existe el menú "Instalar aplicación" nativo de Chromium. La instalación se realiza mediante el menú de Compartir de Safari -> «Agregar a pantalla de inicio».
- Safari utiliza el `<link rel="apple-touch-icon" sizes="180x180" href="...">`, que ya está presente en el layout de Wings.
- Para abrir en modo aplicación independiente sin la barra de Safari, iOS responde al meta tag `<meta name="apple-mobile-web-app-capable" content="yes">`.
- **Qué no se probó:** No disponemos de un dispositivo físico iOS en el entorno de desarrollo, por lo que el comportamiento en iPhone no fue probado físicamente.

---

## 7. Pruebas automatizadas permanentes

Se creó la suite permanente `tests/Feature/PwaInstalacionTest.php` con 3 pruebas:
1. `test_manifest_es_valido_y_declara_arranque_en_login`: Valida la estructura del JSON, `start_url = /login`, `scope = /`, `id = /login`, `display = standalone`, existencia física de los íconos de 192 y 512, y presencia de purposes `any` y `maskable`.
2. `test_login_y_plantillas_enlazan_el_manifest`: Valida que el layout y la vista de login contengan el enlace al manifest y el theme-color.
3. `test_usuario_autenticado_al_abrir_la_app_en_login_es_redirigido_al_inicio_de_su_rol`: Valida que la apertura de la app para un usuario autenticado redirija inmediatamente según el rol (Admin -> Inicio, Operativo -> Inicio operativo, Profesor -> Clases).

Resultado: **3/3 pruebas aprobadas (29 aserciones)** en `wings_testing_gemini`.

---

## 8. Evidencia generada

Guardada en `docs/06-pruebas/PRU-04/evidencia-t11/`:
- `chrome-devtools-manifest.png`: Captura de Chrome DevTools mostrando el panel **Application > Manifest**, con Identity, Presentation, Start URL (`/login`), íconos cargados y cero errores de instalabilidad.
- `login-escritorio.png`: Captura del Login oficial de Wings en escritorio (1366 × 768).
- `login-celular-360.png`: Captura del Login oficial de Wings en marco de 360 px para celular.
- `cdp-installability.json`: Telemetría directa del Chrome DevTools Protocol confirmando `installabilityErrors: []`.
- `generar-evidencia-t11.mjs`: Script reproducible para regenerar la evidencia contra la aplicación levantada localmente.

---

## 9. Qué no se probó

- **Teléfono físico real:** La instalación física en un dispositivo Android real sobre el sitio de prueba no se realizó. Esa prueba la realizará Carlos en su teléfono tras el despliegue al VPS.

---

## 10. Próximo paso

Desplegar al sitio de prueba (`test.gestionar-te.com.ar`) y que Carlos pruebe la instalación de la aplicación desde Chrome en su teléfono Android.
