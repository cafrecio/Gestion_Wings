"""Informe B10 desde evidencia local; no modifica la aplicación ni consulta la base."""
from pathlib import Path
import hashlib
import html
import json
import re
from pypdf import PdfReader

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[4]
result = json.loads((HERE / "resultado.json").read_text(encoding="utf-8-sig"))
seeders = json.loads((HERE / "seeders.json").read_text(encoding="utf-8-sig"))
assert result["completo"] and len(result["casos"]) == 73
assert len(result["rutas"]) == 150
assert len(seeders["casos"]) == 7 and all(x["ok"] for x in seeders["casos"])
reader = PdfReader(HERE / "recibo-liquidacion.pdf")
pages = [p.extract_text() or "" for p in reader.pages]
text = "\n".join(pages)
pdf = {
    "archivo": "recibo-liquidacion.pdf", "paginas": len(pages),
    "lector": "pypdf", "sha256": hashlib.sha256((HERE / "recibo-liquidacion.pdf").read_bytes()).hexdigest(),
    "valores_bancarios_encontrados": [v for v in ["FICTICIO.PROF.B10", "FICTICIO.OP.B10", "FICTICIO.COM.B10"] if v in text],
    "etiquetas_bancarias_encontradas": [v for v in ["CBU", "CVU", "ALIAS"] if v in text.upper()],
    "importe_7500_en_ambas_paginas": all("7.500,00" in p for p in pages),
    "beneficiario_ficticio_presente": "Profesor FICTICIO B10 Privacidad" in text,
    "descarga_http": {"ADMIN": 200, "OPERATIVO": 403, "PROFESOR": 403},
}
assert len(pages) == 2 and not pdf["valores_bancarios_encontrados"] and not pdf["etiquetas_bancarias_encontradas"]
assert pdf["importe_7500_en_ambas_paginas"] and pdf["beneficiario_ficticio_presente"]
(HERE / "pdf.json").write_text(json.dumps(pdf, ensure_ascii=False, indent=2) + "\n", encoding="utf-8", newline="\n")
log = (ROOT / "storage/app/b10-suite-completa.log").read_text(encoding="utf-8-sig", errors="replace")
assert re.search(r"581 passed", log) and re.search(r"2 skipped", log) and "4650 assertions" in log
controls = {
    "commit": result["commit"], "database": result["database"],
    "build": {"comando": "npm run build", "exit_code": 0, "segundos": 44.01},
    "suite": {"comando": "DB_DATABASE=wings_testing_codex APP_ENV=testing php artisan test", "exit_code": 0,
              "aprobadas": 581, "omitidas": 2, "aserciones": 4650, "segundos": 700.07},
    "casos_navegador": 73, "fallas_criterio": [c["caso"] for c in result["casos"] if not c["ok"]],
    "rutas_privacidad": 150, "seeders_aprobados": 7,
    "runtime_storage": "storage/app/verificacion-b10-runtime", "aplicacion_modificada": False,
}
(HERE / "controles.json").write_text(json.dumps(controls, ensure_ascii=False, indent=2) + "\n", encoding="utf-8", newline="\n")

def cell(v):
    return html.escape(str(v), quote=False).replace("|", "\\|").replace("\n", " ").replace("\r", " ")

def value(v):
    return json.dumps(v, ensure_ascii=False)

def table(headers, rows):
    return "\n".join(["| " + " | ".join(headers) + " |", "|" + "|".join(["---"] * len(headers)) + "|"] +
                     ["| " + " | ".join(cell(x) for x in row) + " |" for row in rows]) + "\n"

privacy = ["# B10 — Direcciones verificadas", "", "Chrome real, sesiones OPERATIVO/PROFESOR; 75 GET por rol.", "",
           "Valores exactos buscados: `FICTICIO.PROF.B10` y `FICTICIO.OP.B10`.", "",
           table(["Rol", "Dirección", "HTTP", "Tipo", "Redirección", "Valores encontrados"],
                 [[r["rol"], r["url"], r["status"], r["content_type"], r.get("location", ""), r["valores_encontrados"]] for r in result["rutas"]]),
           "", "Los 302 apuntan a páginas incluidas en este recorrido. El 404 de recibo de cuota corresponde a un pago inexistente; no certifica el contenido de un PDF de cuota válido.",
           "", "## Respuestas JSON consultadas", "",
           table(["Rol", "Dirección", "HTTP", "Incluye cbu_alias"],
                 [[r["rol"], r["url"], r["status"], r.get("tiene_cbu_alias", False)] for r in result["json"]])]
(HERE / "privacidad.md").write_text("\n".join(privacy).rstrip() + "\n", encoding="utf-8", newline="\n")

parts = ["# B10 — Verificación independiente · DEVUELTO a Claude", "",
         "10/10/2026 · Codex CyE. Verificado sobre `" + result["commit"] + "` (main), después de `git pull --ff-only`. Incluye B10 del autor `438ee55` y el ajuste de etiquetas `580f380`.", "",
         "Base exclusiva `wings_testing_codex`, migración B10 aplicada; archivos de sesión, vistas compiladas y PDF aislados en `storage/app/verificacion-b10-runtime`. Chrome real sobre Laravel en `http://127.0.0.1:8010`. Todos los datos de estos programas son ficticios. Sin cambios de aplicación, vistas, CSS o tests; sin despliegue.", "",
         "**Dictamen:** devolver por dos fallas: acepta y transforma alias con espacios/tabulación/salto de línea; el rechazo de 200 caracteres aparece en inglés. Cuatro casos fallidos de 73 comprobaciones, correspondientes a esas dos causas. Pago, acceso, Excel y siete seeders comprobados. Suite completa: 581 aprobadas, 2 omitidas, 4650 aserciones.", "",
         "[Pedidos, filas y resultados](evidencia/verificacion-b10/resultado.json) · [Programa navegador](evidencia/verificacion-b10/verificar.cjs) · [Escenarios aislados](evidencia/verificacion-b10/escenarios.php) · [Controles generales](evidencia/verificacion-b10/controles.json).", ""]

for point, title in [(1, "Profesor"), (2, "Validación"), (3, "Liquidación y pago"), (4, "Operativo y roles"), (5, "Privacidad"), (6, "Compatibilidad")]:
    parts += [f"## {point}. {title}", ""]
    cases = [c for c in result["casos"] if c["punto"] == point]
    rows = []
    for c in cases:
        name, ok = c["caso"], "APROBADO" if c["ok"] else "FALLA"
        if point == 1:
            old = c["antes"]["filas"]
            rows.append([name, value(c["enviado"]), c["http"]["status"], value(old[0]["cbu_alias"]) if old else "sin fila", value(c["despues"]["filas"][0]["cbu_alias"]), ok])
        elif point == 2:
            before = c.get("antes", {"filas": []})["filas"]
            after = c["despues"]["filas"]
            bank = value(after[0]["cbu_alias"]) if after else "sin fila nueva"
            rejection = c.get("rechazado")
            msg = c.get("mensaje", "")
            language = ("Castellano" if c.get("mensaje_castellano") else msg) if rejection else "Alta aceptada"
            if "conservados" in c:
                kept = "10 campos conservados" if c["otros_campos_conservados"] else "No hubo vuelta al formulario"
            else:
                kept = "Alta válida"
            rows.append([name, value(c["enviado"]), c["http"]["status"], bank, kept, language, ok])
        else:
            detail = ""
            if point == 3:
                detail = {
                    "abierta": "ABIERTA/PENDIENTE, $7.500. Sin dato bancario ni formulario de pago.",
                    "cerrada-con-dato": "CERRADA/PENDIENTE. Alias presente antes del formulario de pago.",
                    "cerrada-sin-dato": "Aviso de dato faltante; clic Editar abrió /profesores/1/edit; BD: NULL.",
                    "convivencia-con-580f380": "Fixture COMISION cerrada: Monto ajustado, ayuda y cinco labels con SVG, junto al alias. No se recalculó esta comisión.",
                    "pagada": "Generar → cerrar → pagar por aplicación. CERRADA/PAGADA, $7.500, exactamente un cashflow −$7.500; alias y formulario de pago ausentes.",
                }[name]
            elif point == 4:
                if "despues" in c:
                    row = c["despues"]["filas"][0]
                    detail = "POST/BD: rol " + row["rol"] + "; cbu_alias=" + value(row["cbu_alias"]) + "; usuario activo=" + str(row["activo"])
                else:
                    detail = "Visibilidad comprobada en navegador: " + ("visible" if c.get("visible") else "oculto")
            elif point == 5:
                if name == "datos-cargados-para-privacidad":
                    detail = "Filas concretas profesor 1 y operativo 2 con ambos valores exactos; clase/asistencia/caja/movimiento ficticios vinculados."
                elif "HTML accesible" in name:
                    detail = str(len(c["pantallas"])) + " pantallas HTML 200; ningún valor exacto encontrado. Lista completa enlazada abajo."
                elif "status" in c:
                    detail = "HTTP " + str(c["status"]) + "; " + ("PDF descargado por ADMIN" if name == "recibo-admin" else "ningún dato bancario en respuesta")
                else:
                    detail = "HTTP " + str(c.get("http", {}).get("status", "ver JSON")) + "; sin cbu_alias ni valores exactos en payload."
            elif point == 6:
                detail = "Excel generado con plantilla real; revisar/cargar por HTTP302. P1 TERMINADA; alumno ficticio DNI99101098 creado. Datos bancarios existentes conservados en BD."
            rows.append([name, detail, ok])
    headers = {1: ["Caso", "Enviado", "HTTP", "Antes", "CBU/alias guardado", "Resultado"],
               2: ["Caso", "Enviado", "HTTP", "BD después", "Otros campos", "Mensaje/acción", "Resultado"]}.get(point, ["Caso", "Comprobación", "Resultado"])
    parts += [table(headers, rows), ""]
    if point == 2:
        parts += ["Los rechazos conservan nombre, apellido, DNI, nacimiento, dirección, localidad, teléfono, email, deporte y CBU/alias. El `<script>` se rechazó y quedó como valor escapado, sin ejecutarse.", "",
                  "**Falla 1:** `CbuOAlias::normalizar()` elimina `\\s+` también de los alias. `alias con espacio` se guarda como `aliasconespacio`; tabulación y salto como `aliasprueba`. La validación opera después de esa transformación. [Captura real](evidencia/verificacion-b10/aceptado-indebido-alias-espacio.png).", "",
                  "**Falla 2:** `ProfesorWebController::validationRules()` aplica `max:60` y sus mensajes no traducen `cbu_alias.max`: el rechazo de 200 caracteres dice `The cbu alias field must not be greater than 60 characters.` La fila no se crea y los demás campos se conservan. [Captura real](evidencia/verificacion-b10/rechazo-texto-200.png).", "",
                  "**Observación para Carlos:** `12345678` se rechaza como exige esta orden, pero el BCRA describe 6–20 caracteres y admite números sin exigir una letra. Infiero de esa gramática que un alias enteramente numérico cumple el formato; no comprobé una cuenta bancaria con ese alias. La regla agrega `!ctype_digit`, excluyéndolo. Requiere una decisión de criterio; no modifiqué la regla. [Norma BCRA, §3.7.2.1, página 3 de la sección / página 9 del PDF](https://www.bcra.gob.ar/Pdfs/Texord/t-snp-spd.pdf).", ""]
    if point == 3:
        parts += ["[Cerrada, dato encima del pago](evidencia/verificacion-b10/liquidacion-cerrada-dato.png) · [Convivencia con Monto ajustado](evidencia/verificacion-b10/convivencia-monto-ajustado-banco.png) · [Pagada](evidencia/verificacion-b10/liquidacion-pagada.png). La coexistencia se comprobó en comisión; el pago completo fue de una liquidación por hora, en efectivo.", ""]
    if point == 5:
        parts += ["[150 direcciones, códigos, redirecciones y JSON](evidencia/verificacion-b10/privacidad.md). Recorrido de todos los GET web inventariados, sustituyendo parámetros por los IDs ficticios. OPERATIVO: 23 páginas HTML200; PROFESOR: 2. Las siete direcciones sensibles por rol devolvieron403. Los 302 llevan a destinos comprobados; recibo de cuota inexistente devolvió404.", "",
                  table(["Caso adicional", "Comprobación", "Resultado"], [["Contenido del PDF real", "Dos páginas leídas con pypdf: sin CBU/CVU/alias ni valores exactos; total $7.500 en ambas; beneficiario ficticio presente.", "APROBADO"], ["Destinatario y acceso del recibo", "Descarga para ADMIN200; OPERATIVO/PROFESOR403. Documento interno de haberes del profesor. El circuito leído genera/guarda después del commit; no envía email automáticamente.", "COMPROBADO EN ESTE CIRCUITO"]]), "",
                  "[PDF descargado](evidencia/verificacion-b10/recibo-liquidacion.pdf) · [Lectura y huella](evidencia/verificacion-b10/pdf.json). La entrega física o el envío manual al profesor depende del admin y no se realizó.", "",
                  "Fuente leída: `AlumnoWebController::autocomplete` selecciona y mapea campos (no modelos completos); `ClaseWebController::actualizarProfesores` devuelve nombres como string; validaciones en vivo de usuarios/grupos devuelven disponibilidad; el cobro de Caja devuelve confirmaciones y valores de deuda. `ReciboService::generarReciboLiquidacion` arma explícitamente nombre/deporte/tipo/modalidad y excluye CBU. Las rutas de profesores, usuarios y recibos de liquidación tienen control ADMIN. `bootstrap/app.php` mantiene deshabilitada la API; serializaciones de controladores API sin ruta activa no prueban exposición actual. Se leyó `PERMISOS-ROLES.md` antes de los controles.", ""]
    if point == 6:
        extra = [[c["seeder"].split("\\")[-1], "Ejecutado en base recién migrada con catálogos; filas concretas guardadas, cbu_alias NULL.", "APROBADO"] for c in seeders["casos"]]
        extra += [["Build", "npm run build; salida0, 44,01s.", "APROBADO"], ["Suite completa", "581 aprobadas / 2 omitidas; 4650 aserciones, 700,07s; wings_testing_codex.", "APROBADO"]]
        parts += [table(["Caso", "Comprobación", "Resultado"], extra), "",
                  "[Filas y resultados de seeders](evidencia/verificacion-b10/seeders.json). Asistencia requiere el escenario financiero antes; Sueldos lo crea internamente; UserSeeder requiere los profesores1/2, preparados con PrimeraCargaCompletaSeeder. Los intentos previos sin respetar esos prerrequisitos fallaron (escenarios con alumnos previos; UserSeeder sin profesores). No fueron fallas de B10. Control final: cada escenario con sus prerrequisitos y base nueva. Demo crea profesores, no usuarios en estas filas. Sin volcar contraseñas ni hashes.", ""]

parts += ["## No verificado", "",
          "- No se revalidó el aspecto ni se ejecutó el navegador en celular. Carlos aprobó el aspecto; esta orden lo excluye del juicio. Capturas solo prueban funcionamiento.",
          "- Existencia, titularidad y dígitos verificadores de una cuenta real; transferencias bancarias reales.",
          "- Envío físico/manual del recibo al profesor; correo de terceros y producción. No se desplegó ni se consultó la base del club.",
          "- Todos los estados/filtros posibles de cada página; la privacidad se comprobó con los registros ficticios descritos y la lista explícita de URLs. El PDF de cuota de un pago válido no forma parte del comprobante de liquidación analizado.",
          "- Pago completo de comisión y nueva modificación del monto ajustado: se verificó la coexistencia en pantalla; el pago de punta a punta fue por hora.", "",
          "## Devolución", "",
          "B10 vuelve a Claude: rechazar blancos internos en alias sin transformar el identificador; conservar limpieza de espacios para CBU; traducir el rechazo de longitud. No se propone implementación ni se toca el aspecto aprobado. El criterio para alias numéricos queda como observación para Carlos. Tras la corrección deberá verificarse el nuevo commit.", ""]
(HERE.parent.parent / "VERIFICACION-B10.md").write_text("\n".join(parts), encoding="utf-8", newline="\n")
print(json.dumps({"casos": 73, "fallas": controls["fallas_criterio"], "rutas": 150, "seeders": 7, "pdf_paginas": len(pages)}, ensure_ascii=False))
