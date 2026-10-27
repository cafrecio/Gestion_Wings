# Verificación Técnica T32 — Límite de intentos de ingreso y reloj simulado

**Fecha:** 27/10/2026  
**Verificador:** Gemini CAB  
**Autor del cambio:** Claude (commits `602efcc` y `c699bc1`)  
**Casos asociados en tabla de pruebas:** DU-46, SIS-009, ACC-003  
**Veredicto:** **APROBADA**

---

## 1. Alcance y contexto de la verificación

Durante los días 2 y 3 de la prueba por días (PRU-04), los usuarios experimentaron bloqueos prematuros y persistentes con error HTTP 429 («Demasiados intentos»). La causa raíz identificada constaba de dos componentes:
1. **Colisión de contadores por dirección IP (producción / sistema):** El middleware de límite de 5 intentos por minuto en `POST /login` (`throttle:5,1`) y el límite de 60 avisos por minuto en `POST /csp-reporte` (`throttle:60,1`) no declaraban prefijo de limitador. En Laravel, al omitir el prefijo y no haber usuario autenticado, ambos endpoints computan la misma firma de solicitud (`sha1($domain . '|' . $ip)`). Como la pantalla de login enviaba un reporte CSP por visita/recarga, los reportes de seguridad consumían directamente los intentos de ingreso permitidos.
2. **Inmovilidad del decaimiento temporal con reloj simulado (solo sitio de prueba):** Cuando el reloj simulado de pruebas se detenía (por ejemplo, al alcanzar la medianoche del día simulado fijado), el tiempo no avanzaba. En consecuencia, la ventana de 60 segundos de decaimiento del `RateLimiter` nunca expiraba, haciendo permanente el bloqueo una vez alcanzado el umbral.

La verificación evaluó exhaustivamente los dos commits de Claude:
- `602efcc`: Asignación de prefijos independientes (`throttle:5,1,ingreso` y `throttle:60,1,csp`) en `routes/web.php`.
- `c699bc1`: Introducción de `App\Support\LimiteConRelojDetenido`, extendiendo `RateLimiter` en `AppServiceProvider` solo cuando hay fecha simulada activa, garantizando que nunca se active en producción.

---

## 2. Comprobaciones técnicas en código y pruebas automatizadas

Las verificaciones fueron ejecutadas en la base aislada `wings_testing_gemini` mediante la suite existente (`LoginThrottleTest`, `RelojSimuladoTest`) y una suite de pruebas dedicadas escrita para este control en [`docs/06-pruebas/PRU-04/evidencia-verificacion-t32-gemini/VerificacionT32GeminiTest.php`](evidencia-verificacion-t32-gemini/VerificacionT32GeminiTest.php).

### A. El ingreso frena contra fuerza bruta
- **Comportamiento verificado:**
  - Tras 5 intentos con credenciales incorrectas desde una IP, cada intento devuelve redirección con error en sesión.
  - Al 6º intento dentro del minuto, el servidor responde con **HTTP 429 Too Many Requests**.
  - Si un atacante intenta el 6º intento con la clave correcta (o si un usuario legítimo intenta ingresar desde esa misma IP bloqueada), **sigue recibiendo 429**, impidiendo ataques de fuerza bruta en línea.
  - Transcurridos 60 segundos (simulados con desplazamiento temporal de Carbon), el bloqueo decae automáticamente y el siguiente intento con credenciales correctas ingresa exitosamente (HTTP 302 hacia dashboard).
- **Pruebas:**
  - `LoginThrottleTest::test_sexto_intento_fallido_en_un_minuto_responde_429` (PASS)
  - `VerificacionT32GeminiTest::test_login_bloquea_al_sexto_intento_fallido_y_con_clave_correcta_tambien_recibe_429` (PASS)
  - `VerificacionT32GeminiTest::test_bloqueo_de_login_se_libera_al_pasar_el_minuto` (PASS)

### B. Contadores estrictamente separados
- **Comportamiento verificado:**
  - Se emitieron **60 peticiones consecutivas a `POST /csp-reporte`** desde una misma IP (todas respondieron HTTP 204 No Content). Inmediatamente después, se intentó un login desde esa IP: el intento fue procesado con éxito sin ningún 429, confirmando que los reportes de seguridad no consumen intentos de login.
  - Se bloquearon los intentos de `POST /login` (5 fallos + 6º intento 429). Inmediatamente después, se enviaron reportes a `POST /csp-reporte` desde la misma IP: continuaron siendo aceptados con HTTP 204 No Content, confirmando que el bloqueo de login no interrumpe la telemetría de seguridad del navegador.
- **Pruebas:**
  - `VerificacionT32GeminiTest::test_reportes_csp_no_consumen_intentos_de_login` (PASS)
  - `VerificacionT32GeminiTest::test_login_bloqueado_no_bloquea_reportes_csp` (PASS)

### C. El límite no se debilitó y no se puede esquivar cambiando de correo
- **Análisis de la clave en Laravel (`Illuminate\Routing\Middleware\ThrottleRequests`):**
  - La clave del limitador se resuelve en el middleware como:
    ```php
    $key = $prefix . $this->resolveRequestSignature($request);
    ```
  - Al no haber sesión de usuario autenticada (`$request->user() === null`), el método `resolveRequestSignature` resuelve:
    ```php
    return sha1($route->getDomain() . '|' . $request->ip());
    ```
  - Por lo tanto, para `/login`, la clave almacenada en cache es:
    `'ingreso' . sha1($domain . '|' . $ip)`.
  - Para `/csp-reporte`, la clave es:
    `'csp' . sha1($domain . '|' . $ip)`.
  - **Inviolabilidad por cambio de correo:** El payload de la petición (`email`, `password`) **no interviene** en la clave. En consecuencia, un atacante que rote correos en cada intento (`usuario1@wings.test`, `usuario2@wings.test`, etc.) desde una misma IP impacta siempre sobre el mismo contador y queda bloqueado al 6º intento con HTTP 429.
- **Pruebas:**
  - `VerificacionT32GeminiTest::test_cambiar_correo_en_cada_intento_no_evade_el_limite_de_la_ip` (PASS)

### D. Protección estricta de Entorno de Producción
- **Análisis de seguridad en el código:**
  - En `app/Support/RelojSimulado.php:39`:
    ```php
    if ($entorno === 'production') {
        return null;
    }
    ```
    Si el entorno es `production`, `RelojSimulado::aplicar(...)` retorna `null` de forma incondicional y resetea `$detenidoDesde = null`.
  - En `app/Providers/AppServiceProvider.php:30-38`:
    ```php
    $aplicada = RelojSimulado::aplicar(config('app.fecha_simulada'), $this->app->environment(), is_numeric($desde) ? (int) $desde : null);

    if ($aplicada !== null) {
        $this->app->extend(\Illuminate\Cache\RateLimiter::class, fn ($limite, $app) => new \App\Support\LimiteConRelojDetenido(
            $app->make('cache')->driver($app['config']->get('cache.limiter'))
        ));
    }
    ```
    Como `$aplicada` es estrictamente `null` en producción, el bloque condicional nunca se ejecuta. `LimiteConRelojDetenido` jamás se registra en el contenedor de servicios de producción.
  - Además, `RelojSimulado::detenido()` retorna `false` en producción (`self::$detenidoDesde === null`). Incluso si se instanciara manualmente la clase, delegaría al limitador padre nativo.
- **Pruebas:**
  - `RelojSimuladoTest::test_en_produccion_se_ignora_aunque_este_escrita` (PASS)
  - `RelojSimuladoTest::test_el_comando_no_cambia_nada_en_produccion` (PASS)
  - `VerificacionT32GeminiTest::test_en_produccion_reloj_simulado_se_ignora_y_no_se_registra_limite_con_reloj_detenido` (PASS)

### E. Comportamiento en el sitio de prueba (staging / reloj simulado)
- **Reloj avanzando:** Cuando la fecha simulada avanza en el tiempo (`RelojSimulado::detenido() === false`), `LimiteConRelojDetenido` delega completamente a `parent::tooManyAttempts($key, $maxAttempts)`, aplicando el límite de 5 intentos por minuto normalmente.
- **Reloj detenido:** Cuando el reloj llega a la medianoche del día simulado o se fija clavado (`RelojSimulado::detenido() === true`), `LimiteConRelojDetenido::tooManyAttempts(...)` retorna `false`. Esto previene bloqueos no recuperables para los evaluadores humanos mientras el reloj no corre.
- **Pruebas:**
  - `RelojSimuladoTest::test_el_reloj_que_avanza_no_cruza_la_medianoche_por_su_cuenta` (PASS)
  - `VerificacionT32GeminiTest::test_limite_con_reloj_detenido_bypass_solo_si_esta_detenido` (PASS)

### F. Relevamiento de otros límites en la aplicación
- Se inspeccionó todo el repositorio con `codebase-memory-mcp` y búsquedas de directivas de limitación:
  - `routes/web.php`: Únicamente `/login` (`throttle:5,1,ingreso`) y `/csp-reporte` (`throttle:60,1,csp`). Ambos poseen prefijos unívocos.
  - `routes/api.php`: Contiene rutas Sanctum, pero se encuentran completamente deshabilitadas (comentadas en `bootstrap/app.php:17` desde julio de 2026).
  - `config/auth.php`: Define `passwords.users.throttle = 60` para reseteo de contraseñas de Laravel (no expuesto vía web en Wings actualmente).
  - **Conclusión:** No existe ninguna otra ruta ni controlador que comparta los contadores o que quede afectado por este cambio.

---

## 3. Resultados de ejecución de pruebas

| Suite de pruebas | Pruebas | Aserciones | Resultado |
|---|---|---|---|
| `tests/Feature/LoginThrottleTest.php` | 1 | 22 | **PASS** |
| `tests/Feature/RelojSimuladoTest.php` | 7 | 62 | **PASS** |
| `docs/06-pruebas/PRU-04/evidencia-verificacion-t32-gemini/VerificacionT32GeminiTest.php` | 7 | 168 | **PASS** |
| `tests/Feature/DocumentacionNoMienteTest.php` | 1 | 7 | **PASS** |
| `tests/Feature/TableroAlDiaTest.php` | 6 | 27 | **PASS** |
| `tests/Feature/CspSinCodigoIncrustadoTest.php` | 2 | 2 | **PASS** |
| Suite rápida (`--exclude-group=lento`) | 939 | 7.629 | **PASS** (6 omitidas, 0 fallidas) |

---

## 4. Dictamen final

- **Veredicto:** **APROBADA**.
- El arreglo 1 (`602efcc`) resuelve la raíz del problema separando los contadores de `/login` y `/csp-reporte` sin debilitar la seguridad del ingreso (continúa bloqueando al 6º intento por IP, independientemente del correo ingresado).
- El arreglo 2 (`c699bc1`) resuelve la traba del sitio de prueba cuando el reloj se detiene a la medianoche, con una salvaguarda a nivel de código que garantiza que en producción (`APP_ENV=production`) nunca se altere el limitador nativo.
- La tarea T32 queda lista para cerrarse en el tablero.
