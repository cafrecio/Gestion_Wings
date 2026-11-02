# Wings — Lo legal

Revisión del 01/11/2026, a cargo de Claude por orden de Carlos, sobre el borrador del 17/10. Nadie
matriculado la revisó. Marca lo que se leyó en la norma y lo que sale de comentarios de estudios.

| Documento | Para quién | Dónde va |
|---|---|---|
| [Condiciones de servicio](CONDICIONES-DE-SERVICIO.md) | Gestionar-te y cada club. Incluye el encargo de tratamiento de datos (anexo) | Se firma con cada club |
| [Política de privacidad](POLITICA-DE-PRIVACIDAD.md) | Familias, alumnos y usuarios de Wings | Enlazada desde el ingreso a Wings |
| [Política de cookies](POLITICA-DE-COOKIES.md) | Usuarios de Wings | Enlazada desde el ingreso; es corta porque Wings solo usa cookies necesarias |
| [Leyenda para la ficha de inscripción](POLITICA-DE-PRIVACIDAD.md#anexo--leyenda-para-la-ficha-de-inscripción) | Familias que inscriben a un alumno | Impresa en la ficha del club |

## De qué hay que cubrirse

| Riesgo | Norma | Cómo queda | Base |
|---|---|---|---|
| **Cargar datos sin avisar ni tener consentimiento.** Son datos de menores | Ley 25.326, arts. 5 y 6; Res. AAIP 14/2018; Código Civil y Comercial, art. 26 | Leyenda de la ficha, firmada por madre, padre o tutor. La ley no trae reglas propias para menores | Ley leída; menores, por comentarios |
| **No inscribir las bases.** El art. 21 habla de archivos «destinados a proporcionar informes», pero la AAIP pide inscribir toda base que no sea de uso personal | Ley 25.326, art. 21; Disp. 2-E/2005; Res. AAIP 132/2018 | Se inscriben: es gratis y no vence. Cada club la suya; Gestionar-te, la de clientes | Ley y página de la AAIP leídas |
| **Datos fuera de Argentina.** El servidor está en Estados Unidos, que no figura como país adecuado | Ley 25.326, art. 12; Disp. 60-E/2016 | Consentimiento expreso en la leyenda. Corregida: antes decía «pueden estar» | IP ubicada en Boston por ipinfo; no se vio el panel de Hostinger |
| **Usar los datos del club para otra cosa, o no borrarlos al terminar** | Ley 25.326, art. 25 | Anexo de las condiciones | Ley leída |
| **Seguridad y filtraciones.** Hay delito por acceder o revelar datos | Ley 25.326, art. 9; Res. AAIP 47/2018; Código Penal, art. 157 bis | Medidas en el anexo; aviso al club en 72 horas, aunque hoy no hay obligación exigible | Art. 9 leído; penal y filtraciones, por la guía Multilaw 2025 |
| **Agentes de IA leyendo datos reales.** Entran como root al servidor; ningún proveedor de IA figura en la política | Ley 25.326, arts. 11 y 25 | Regla nueva: `AGENTS.md` §6-quater. En producción no leen datos personales | Criterio de Claude |
| **Datos de más en los avisos.** Telegram puede recibir el nombre de un alumno | Ley 25.326, art. 4 | Declarado y con consentimiento. Sacar el nombre del aviso queda para después de producción | Ley leída; el contenido del aviso sale del borrador |
| **El tope de responsabilidad se cae.** Si el club es «consumidor», esa cláusula se tiene por no escrita | Ley 24.240, arts. 1 y 37; Código Civil y Comercial, art. 988 | Hay un fallo a favor: software de una empresa integrado a su giro no es consumo. No es seguro. El tope queda; hay que contar con que puede caer | Comentarios; fallo no leído completo |
| **Que el recibo de Wings pase por factura.** ARCA extiende la factura electrónica; la RG 5893/2026 alcanzaría a no alcanzados por IVA desde el 1/3/2027 | RG ARCA 5866/2026 y 5893/2026 | Las condiciones dicen que lo fiscal es del club. **No se pudo leer la resolución; si cubre a los clubes queda sin confirmar** | Comentarios |
| **Profesores que reclaman relación laboral.** Wings liquida, pero no es recibo de sueldo | Ley 20.744 | Las condiciones dicen que lo laboral es del club | Criterio de Claude |
| **Cobrar un abono en dólares** | Código Civil y Comercial, art. 765 | Se eligió pagar en pesos, al vendedor del Banco Nación: es lo que un club puede pagar. Carlos puede cambiarlo | Comentario de Marval |

**Multas:** la ley dice de $1.000 a $100.000 (art. 31). No se pudo confirmar si hay un monto actualizado. También hay apercibimiento, publicación del sancionado, clausura y demanda por daños.

## Lo que puede cambiar

- **Reforma:** hay proyectos en Diputados (entre ellos 1751-D-2026). No obligan a nada todavía; la Ley 25.326 sigue vigente. Uno fija en 16 años la edad para que un adolescente consienta. No se comprobó en qué comisión están.
- **Convenio 108+ (Ley 27.699):** prevé avisar a la AAIP en 72 horas. Según la guía de 2025 todavía no es exigible; el anexo ya lo cumple. No se comprobó si entró en vigor en 2026.
- **Cookies:** sin norma específica de la AAIP. Wings solo usa cookies necesarias: alcanza con informarlas.

## Lo que solo Carlos puede dar

No hace falta saber de leyes:

1. Nombre o razón social, CUIT, domicilio, correo de reclamos y ciudad de Gestionar-te.
2. Inscribir a Gestionar-te en la AAIP: trámite en TAD, con **su** clave fiscal nivel 2.

Lo demás lo da o lo hace cada club: sus datos, su inscripción, la firma y la leyenda en su ficha.

## Lo que falta en Wings

Un enlace a la privacidad y a las cookies en la pantalla de ingreso. Es una vista: necesita el OK de Carlos.

## Fuentes consultadas el 01/11/2026

- [Ley 25.326, texto](https://www.argentina.gob.ar/normativa/nacional/ley-25326-64790/texto).
- [AAIP: trámites](https://www.argentina.gob.ar/aaip/datospersonales/tramites) y [obligaciones de los responsables](https://www.argentina.gob.ar/node/53779).
- [Resolución AAIP 14/2018](https://www.boletinoficial.gob.ar/detalleAviso/primera/179914/20180309), [Resolución AAIP 47/2018](https://www.argentina.gob.ar/normativa/nacional/resolución-47-2018-312662/texto), [Disposición 60-E/2016](https://www.argentina.gob.ar/normativa/nacional/267922/texto) y [Resolución AAIP 198/2023](https://www.argentina.gob.ar/normativa/nacional/resoluci%C3%B3n-198-2023-391538/texto).
- [Guía Multilaw de Argentina, 2025](https://www.multilaw.com/Multilaw/Data_Protection_Laws_Guide/DataProtection_Guide_Argentina.aspx): registro, filtraciones, multas, Convenio 108+.
- Reforma: [Allende & Brea](https://allende.com/privacidad-y-ciberseguridad/se-reintroduce-en-el-congreso-el-proyecto-de-reforma-integral-del-regimen-argentino-de-proteccion-de-datos-personales-08-12-2026/) y [proyecto 1751-D-2026](https://leydedatospersonales.tech/).
- Consumidor: [fallo comentado sobre software y consumo final](https://aldiaargentina.microjuris.com/2019/10/11/consumidor-o-no-consumidor-la-ley-de-defensa-del-consumidor-es-inaplicable-si-el-software-adquirido-por-la-sociedad-comercial-no-tuvo-destino-de-consumo-final/) y [art. 988](https://camoron.org.ar/nuevas-normas/derecho-del-consumidor/el-nuevo-codigo-y-las-clausulas-abusivas/).
- ARCA: [RG 5866/2026, comentario](https://siap.blogdelcontador.com.ar/?p=86043) y [nuevo cronograma](https://www.canal26.com/economia/2026/08/31/arca-amplia-el-uso-de-facturas-electronicas-quienes-deberan-emitir-comprobantes-desde-noviembre-de-2026/).
- [Código Civil y Comercial, art. 765, comentario de Marval](https://www.marval.com/publicacion/luego-del-dnu-702023-pueden-pagarse-en-pesos-las-obligaciones-pendientes-en-dolares-15745?lang=es).
