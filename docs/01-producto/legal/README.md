# Wings — Lo legal mínimo

Borradores del 17/10/2026, escritos por Claude a pedido de Carlos, sin abogado. Están armados
sobre el texto de las normas y sobre lo que Wings guarda de verdad (leído en el código y la base).
Lo que no se pudo verificar está marcado **[A CONFIRMAR]**.

| Documento | Para quién | Dónde va |
|---|---|---|
| [Condiciones de servicio](CONDICIONES-DE-SERVICIO.md) | Gestionar-te y cada club. Incluye el encargo de tratamiento de datos (anexo) | Se firma con cada club |
| [Política de privacidad](POLITICA-DE-PRIVACIDAD.md) | Familias, alumnos y usuarios de Wings | Enlazada desde el ingreso a Wings |
| [Política de cookies](POLITICA-DE-COOKIES.md) | Usuarios de Wings | Enlazada desde el ingreso; es corta porque Wings solo usa cookies necesarias |
| [Leyenda para la ficha de inscripción](POLITICA-DE-PRIVACIDAD.md#anexo--leyenda-para-la-ficha-de-inscripción) | Familias que inscriben a un alumno | Impresa en la ficha del club |

## Qué hay que cumplir y por qué

| Obligación | Norma | Cómo queda cubierta |
|---|---|---|
| Informar al titular, **antes** de recolectar, para qué se usan sus datos, quién los recibe, quién es el responsable, si dar el dato es obligatorio y cómo ejercer sus derechos | Ley 25.326, art. 6; Res. AAIP 14/2018, art. 2 | Política de privacidad y leyenda de la ficha |
| Incluir la leyenda de la AAIP como órgano de control | Res. AAIP 14/2018, art. 3 | Textual en la política y en la ficha |
| Consentimiento libre, expreso e informado, por escrito o equivalente | Ley 25.326, art. 5 | Firma de la ficha (madre, padre o tutor si es menor) |
| Datos sensibles (salud, religión, etc.): nadie está obligado a darlos | Ley 25.326, art. 7 | Wings **no guarda** datos de salud ni sensibles. Si un club quiere cargar aptos médicos, hay que revisar esto antes |
| Derecho de acceso: gratis cada 6 meses, respuesta en 10 días corridos | Ley 25.326, art. 14 | Política de privacidad |
| Rectificación, actualización y supresión: respuesta en 5 días hábiles | Ley 25.326, art. 16 | Política de privacidad |
| Seguridad y confidencialidad | Ley 25.326, arts. 9 y 10; Res. AAIP 47/2018 (recomendaciones) | Condiciones de servicio, anexo |
| Quien trata datos por cuenta de otro (Gestionar-te para el club) no los usa para otra cosa, no los cede, y al terminar los destruye (o los guarda hasta 2 años si el club lo autoriza) | Ley 25.326, art. 25 | Condiciones de servicio, anexo |
| Transferencia a países sin protección adecuada (Estados Unidos no está en la lista) solo con consentimiento expreso del titular o contrato con cláusulas modelo | Ley 25.326, art. 12 y su reglamentación (Decreto 1558/2001, art. 12, consentimiento); Disp. 60-E/2016 y Res. AAIP 34/2019 (lista); Res. AAIP 198/2023 (cláusulas) | Consentimiento en la leyenda de la ficha y aviso en la política |
| Inscribir la base de datos en el Registro Nacional de Bases de Datos | Ley 25.326, art. 21 | **Pendiente**: lo hace cada club con su base de alumnos, y Gestionar-te con la suya de clientes, por TAD con clave fiscal nivel 2 |
| Cookies | No hay norma específica en Argentina | Wings solo usa cookies necesarias para funcionar: alcanza con informarlas, sin cartel de consentimiento |

## Lo que Carlos tiene que hacer (fuera del código)

1. **Completar los datos marcados con corchetes**: nombre o razón social y CUIT de Gestionar-te,
   domicilio, correo para reclamos, ciudad de los tribunales.
2. **Inscribirse en la AAIP** como responsable y declarar la base de clientes de Gestionar-te.
   Indicarle a cada club que inscriba la suya (puede hacerlo el mismo club con su clave fiscal).
   [A CONFIRMAR] si el club, por ser chico, quiere que Gestionar-te le haga el trámite.
3. **Decidir cómo se paga el abono**: en dólares, o en pesos al tipo de cambio que diga el
   contrato. El borrador deja las dos opciones (cláusula 4).
4. **Confirmar dónde está el servidor**: Hostinger, IP `2.25.204.38`. El país del centro de
   datos no figura en `ESTADO-SERVIDOR.md`. Se nombra en la política de privacidad.
5. **Imprimir la leyenda** en la ficha de inscripción de cada club y guardar las firmas.

## Lo que hay que hacer en Wings (código)

- Un enlace a la política de privacidad y a la de cookies en la pantalla de ingreso. Es una vista:
  queda para una tarea aparte cuando Carlos dé el texto por bueno.

## Fuentes consultadas el 17/10/2026

- Ley 25.326, texto vigente (arts. 5, 6, 7, 9, 10, 12, 14, 16, 21, 25).
- [Resolución AAIP 14/2018](https://www.boletinoficial.gob.ar/detalleAviso/primera/179914/20180309) — leyenda y aviso previo.
- [Resolución AAIP 47/2018](https://www.argentina.gob.ar/normativa/nacional/resolución-47-2018-312662/texto) — medidas de seguridad recomendadas.
- [Disposición 60-E/2016](https://www.argentina.gob.ar/normativa/nacional/267922/texto) y Res. AAIP 34/2019 — países adecuados y cláusulas modelo.
- [Resolución AAIP 198/2023](https://www.argentina.gob.ar/normativa/nacional/resoluci%C3%B3n-198-2023-391538/texto) — cláusulas modelo de la Red Iberoamericana.
- [Registro Nacional de Bases de Datos — trámites](https://www.argentina.gob.ar/aaip/datospersonales/tramites).
- Código Civil y Comercial, art. 765, reformado por DNU 70/2023 ([comentario Marval](https://www.marval.com/publicacion/luego-del-dnu-702023-pueden-pagarse-en-pesos-las-obligaciones-pendientes-en-dolares-15745?lang=es)) — obligaciones en dólares.
- Sobre cookies no hay norma de la AAIP; se tomó la práctica general (fuentes comerciales, no oficiales).
