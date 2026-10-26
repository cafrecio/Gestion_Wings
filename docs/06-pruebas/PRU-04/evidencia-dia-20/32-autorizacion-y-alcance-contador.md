# Excepción de mantenimiento autorizada por Carlos · 26/10/2026

Carlos respondió: «Sí, revisá y reiniciá solo ese contador».

Diagnóstico por SSH, solo staging/wingstest: un único contador ingreso bloqueado tenía5intentos y temporizador hasta31/10/2026 18:02:08UTC, mientras el reloj de la reanudación estaba en29/10. Quedaban171386segundos; esperar un minuto no lo liberaba.

Se ejecutó RateLimiter::clear únicamente sobre ingresoad22fdc9f5b732f5403777e7d2108e5bd890e662; comprobado después:0intentos,0segundos,bloqueado=false. No limpieza general de caché, ni cambios de límite, usuarios, contraseñas o datos de negocio.

Ver30-diagnostico-contador-login.txt y31-reinicio-unico-contador-autorizado.txt. Incidente del reloj de prueba; no atribuirlo a intentos de contraseña errónea sin evidencia.
