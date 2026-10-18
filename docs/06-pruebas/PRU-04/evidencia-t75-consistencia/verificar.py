"""Comprueba medidas reales de Chrome y el alcance de las fuentes de T75.

Ejecutar desde la raíz: python -X utf8 docs/06-pruebas/PRU-04/evidencia-t75-consistencia/verificar.py
No escribe ni consulta una base de datos.
"""
import hashlib
import json
import pathlib
import re
import subprocess

ROOT = pathlib.Path(__file__).resolve().parents[4]
HERE = pathlib.Path(__file__).resolve().parent


def load(name):
    return json.loads((HERE / name).read_text(encoding="utf-8"))


def controls(case):
    return [b for z in case["zonas"] for row in z["filas"] for b in row["controles"]]


def action(b):
    return b["esAccion"] and b.get("textoVisible") and b["textoVisible"] not in ("✕", "×")


def same_rect(a, b):
    return all(abs(a[k] - b[k]) <= .6 for k in ("x", "y", "w", "h"))


def main():
    before = load("mediciones-antes.json")["casos"]
    after = load("mediciones-despues.json")["casos"]
    extras = load("mediciones-complementos.json")["casos"]
    probe = load("sondeo-despues.json")
    assert len(before) == len(after) == 297
    assert len(extras) == 6
    assert len(probe["casos"]) == 303
    assert len(probe["variantes"]) == 80
    variants = probe["variantes"]
    records = after + extras + variants
    mobile_actions = 0
    checked_rows = 0
    for case in records:
        assert not case["desborde"], (case["slug"], case["ancho"], "desborde")
        if case["ancho"] > 640:
            continue
        for zone in case["zonas"]:
            for row in zone["filas"]:
                buttons = [b for b in row["controles"] if action(b)]
                for b in buttons:
                    mobile_actions += 1
                    assert abs(b["rect"]["w"] - 96) <= .6, (case["slug"], b)
                    assert abs(b["rect"]["h"] - 32) <= .6, (case["slug"], b)
                    style = b["estilo"]
                    assert style["padding"] == "0px", (case["slug"], b)
                    assert style["fontSize"] == "13.12px" and style["fontWeight"] == "600", (case["slug"], b)
                table = any(b["categoria"] == "fila-tabla" for b in buttons)
                if buttons and (not table or case.get("tipo") == "tabla"):
                    checked_rows += 1
                    right = max(b["rect"]["derecha"] for b in row["controles"])
                    assert abs(right - case["referenciaDerecha"]) <= 1, (case["slug"], right, case["referenciaDerecha"])
                    for b in buttons:
                        if b["etiqueta"] == "Cobrar":
                            assert abs(b["rect"]["derecha"] - case["referenciaDerecha"]) <= 1, (case["slug"], "Cobrar")
        for nav in case.get("navegacion", []):
            if nav.get("etiqueta") == "Salir":
                assert abs(nav["rect"]["w"] - 96) <= .6 and abs(nav["rect"]["h"] - 32) <= .6
                assert abs(nav["rect"]["derecha"] - case["referenciaDerecha"]) <= 1

    preserved = 0
    for case in after:
        if case["ancho"] > 640:
            continue
        old = next(c for c in before if c["slug"] == case["slug"] and c["ancho"] == case["ancho"])
        previous = {b["ruta"]: b for b in controls(old)}
        for b in controls(case):
            special = not b.get("textoVisible") or b.get("textoVisible") in ("✕", "×") or any(k in b.get("clases", "") for k in ("ds-toggle", "btn-toggle-password", "ds-btn--icon"))
            if special and b["ruta"] in previous:
                preserved += 1
                assert all(abs(b["rect"][k] - previous[b["ruta"]]["rect"][k]) <= .6 for k in ("w", "h")), (case["slug"], b["etiqueta"])
    assert preserved == 126
    desktop = 0
    for case in after:
        if case["ancho"] != 1366:
            continue
        old = next(c for c in before if c["slug"] == case["slug"] and c["ancho"] == 1366)
        a, b = controls(old), controls(case)
        assert len(a) == len(b), case["slug"]
        assert old["altoDocumento"] == case["altoDocumento"], case["slug"]
        for x, y in zip(a, b):
            assert x["etiqueta"] == y["etiqueta"] and same_rect(x["rect"], y["rect"]), case["slug"]
        desktop += 1

    stable = []
    def case(slug, width):
        return next(c for c in probe["casos"] + variants if c["slug"] == slug and c["ancho"] == width)

    for width in (360, 375):
        for left, right, labels in (
            ("admin-alumnos-id", "operativo-2", ["Cobrar"]),
            ("admin-liquidaciones", "admin-liquidaciones-filtradas", ["Filtrar"]),
            ("admin-clase-futura :: panel-cancelar", "admin-clase-serie :: panel-cancelar", ["Cerrar", "Cancelar esta"]),
        ):
            for label in labels:
                a = [b["rect"] for b in controls(case(left, width)) if action(b) and b["etiqueta"] == label]
                b = [b["rect"] for b in controls(case(right, width)) if action(b) and b["etiqueta"] == label]
                assert a and len(a) == len(b) and all(same_rect(x, y) for x, y in zip(a, b)), (width, left, right, label)
                stable.append({"ancho": width, "casos": [left, right], "accion": label, "rectangulos": a})
        detail = case("admin-caja-cerrada-detalle", width)
        selected = [b for b in controls(detail) if b["etiqueta"] in ("Validar", "Rechazar", "Resumen", "Volver")]
        assert len(selected) == 4
        by_label = {b["etiqueta"]: b["rect"] for b in selected}
        assert by_label["Validar"]["y"] == by_label["Rechazar"]["y"]
        assert by_label["Resumen"]["y"] == by_label["Volver"]["y"]
        assert by_label["Validar"]["y"] < by_label["Resumen"]["y"]
        assert by_label["Validar"]["x"] == by_label["Resumen"]["x"]
        assert by_label["Rechazar"]["x"] == by_label["Volver"]["x"]
        for slug in ("admin-liquidaciones-id", "admin-liquidacion-mes_actual", "admin-liquidacion-cerrada", "admin-liquidacion-pagada"):
            liquidation = case(slug, width)
            back = next(b for b in controls(liquidation) if action(b) and b["etiqueta"] == "Volver")
            assert abs(back["rect"]["derecha"] - liquidation["referenciaDerecha"]) <= 1, (slug, "Volver cambia de columna")
            stable.append({"ancho": width, "caso": slug, "accion": "Volver", "bordeDerecho": back["rect"]["derecha"]})
        current_month = case("admin-liquidacion-mes_actual", width)
        assert current_month["avisosAcciones"] and all(abs(a["rect"]["w"] - 200) <= .6 for a in current_month["avisosAcciones"])
        recalculations = [next(b for b in controls(case(slug, width)) if b["etiqueta"] == "Recalcular")["rect"] for slug in ("admin-liquidaciones-id", "admin-liquidacion-mes_actual")]
        assert recalculations[0]["x"] == recalculations[1]["x"]

    sources = load("fuentes-verificadas.json")
    for source in sources["fuentes"]:
        path = ROOT / source["archivo"]
        current = path.read_text(encoding="utf-8").replace("\r\n", "\n")
        assert hashlib.sha256(current.encode("utf-8")).hexdigest() == source["sha256Lf"], path
        original = subprocess.check_output(["git", "show", "1d73322:" + source["archivo"]], cwd=ROOT).decode("utf-8").replace("\r\n", "\n")
        if source["soloClases"]:
            strip = lambda s: re.sub(r'\s+class="[^"]*"', "", s)
            assert strip(current) == strip(original), path
        else:
            start = current.index("/* T75, decisión de Carlos 18/10:")
            end = current.index("/* empty-state", start)
            assert (current[:start] + current[end:]).strip() == original.strip()
            block = current[start:end]
            assert block.count("@media") == 1 and "@media (max-width: 640px)" in block

    result = {"resultado": "APROBADO", "medicionesPrincipales": len(after), "complementos": len(extras), "variantes": len(variants), "accionesMovilesMedidas": mobile_actions, "filasAlineadas": checked_rows, "escritoriosSinCambiosDeGeometria": desktop, "fuentesSoloPresentacion": len(sources["fuentes"]), "posicionesEstables": stable}
    result["iconosEInterruptoresConservados"] = preserved
    (HERE / "verificacion.json").write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({k: v for k, v in result.items() if k != "posicionesEstables"}, ensure_ascii=False))


if __name__ == "__main__":
    main()
