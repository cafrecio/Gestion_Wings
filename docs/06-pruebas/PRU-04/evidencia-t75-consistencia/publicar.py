"""Publica únicamente las capturas referidas por las mediciones finales y verifica sus hashes."""
import hashlib
import html
import json
import pathlib
import shutil

HERE = pathlib.Path(__file__).resolve().parent
ROOT = HERE.parents[3]
RUNTIME = ROOT / "storage/app/t75-consistencia-runtime/app"


def load(name):
    return json.loads((HERE / name).read_text(encoding="utf-8"))


def main():
    previous = load("capturas-manifiesto.json") if (HERE / "capturas-manifiesto.json").exists() else []
    phases = {phase: load(f"mediciones-{phase}.json")["casos"] for phase in ("antes", "despues")}
    extras = load("mediciones-complementos.json")["casos"]
    manifest = []
    for phase, cases in phases.items():
        for case in cases + (extras if phase == "despues" else []):
            photos = case["fotos"] + [v["foto"] for v in case["variantes"]]
            for photo in photos:
                source = RUNTIME / "capturas" / phase / photo["nombre"]
                digest = hashlib.sha256(source.read_bytes()).hexdigest()
                assert digest == photo["sha256"], source
                target = HERE / "capturas" / phase / photo["nombre"]
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(source, target)
                assert hashlib.sha256(target.read_bytes()).hexdigest() == digest
                manifest.append({"archivo": str(target.relative_to(HERE)).replace("\\", "/"), "sha256": digest, "bytes": target.stat().st_size})
    assert len(manifest) == len({p["archivo"] for p in manifest})
    current_paths = {p["archivo"] for p in manifest}
    for old in previous:
        if old["archivo"] not in current_paths:
            obsolete = (HERE / old["archivo"]).resolve()
            assert obsolete.is_relative_to((HERE / "capturas").resolve())
            assert hashlib.sha256(obsolete.read_bytes()).hexdigest() == old["sha256"]
            obsolete.unlink()
    (HERE / "capturas-manifiesto.json").write_text(json.dumps(manifest, indent=2), encoding="utf-8")
    def photo(case, phase):
        name = case["fotos"][0]["nombre"]
        return f'<a href="capturas/{phase}/{name}"><img loading="lazy" src="capturas/{phase}/{name}" alt="{phase} {html.escape(case["slug"])} a {case["ancho"]}"></a>'

    out = ['<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>T75 — Consistencia móvil</title>',
           '<style>body{font:16px system-ui;margin:24px;background:#f4f6f8;color:#111827}article{background:white;padding:20px;margin:24px 0;border:1px solid #d8e0ea;border-radius:12px}h1,h2{line-height:1.2}.pares{display:flex;gap:20px;flex-wrap:wrap}.pares img{width:100%;max-width:375px;height:auto;border:1px solid #d8e0ea}figure{margin:0}summary{cursor:pointer;margin:14px 0}li{margin:8px 0}a{color:#24496c}</style>',
           '<h1>T75 — Consistencia de botones en celular</h1><p>Entrega para revisión visual de Carlos. Botones con texto de 96 × 32, padding común y acciones al mismo borde derecho. No desplegada.</p>',
           '<p>Respuestas reales de Wings, datos sintéticos y Chrome dentro de un marco de 360/375 píxeles. Las imágenes de 1366 comprueban escritorio. Los paneles ocultos se muestran sobre el HTML real para revisar su presentación; esto no certifica el flujo de negocio.</p>',
           '<p><a href="../IMPLEMENTACION-T75-CONSISTENCIA.md">Informe</a> · <a href="verificacion.json">Mediciones verificadas</a> · <a href="INVENTARIO.md">Cobertura</a> · <a href="REPRODUCIR.md">Reproducción</a></p><nav><ol>']
    slugs = list(dict.fromkeys(c["slug"] for c in phases["despues"]))
    for slug in slugs:
        out.append(f'<li><a href="#{slug}">{html.escape(slug)}</a></li>')
    out.append('</ol><a href="#complementos">Casos con botones condicionales</a></nav>')
    for slug in slugs:
        after = next(c for c in phases["despues"] if c["slug"] == slug and c["ancho"] == 375)
        before = next(c for c in phases["antes"] if c["slug"] == slug and c["ancho"] == 375)
        out.append(f'<article id="{slug}"><h2>{html.escape(slug)}</h2><p>{html.escape(after["rol"]+" · "+str(after["ruta"]))}</p><div class="pares"><figure><figcaption>Antes · 375</figcaption>{photo(before,"antes")}</figure><figure><figcaption>Después · 375</figcaption>{photo(after,"despues")}</figure></div><details><summary>360, escritorio, pie, tablas y paneles</summary><ul>')
        for phase in ("antes", "despues"):
            for case in phases[phase]:
                if case["slug"] != slug:
                    continue
                for p in case["fotos"] + [v["foto"] for v in case["variantes"]]:
                    label = f'{phase} · {case["ancho"]} · {p["nombre"].split("--",2)[2][:-4]}'
                    out.append(f'<li><a href="capturas/{phase}/{p["nombre"]}">{html.escape(label)}</a></li>')
        out.append('</ul></details></article>')
    out.append('<h2 id="complementos">Presencia y ausencia de acciones complementarias</h2><p>Comparar Liquidaciones con/sin Limpiar y la clase futura con/sin Cancelar serie. Las coordenadas de Filtrar, Cerrar y Cancelar esta coinciden. En la ficha ADMIN/OPERATIVO, Cobrar conserva su lugar con/sin Condonar.</p>')
    for case in extras:
        out.append(f'<article><h2>{case["slug"]} · {case["ancho"]}</h2>{photo(case,"despues")}<ul>')
        for p in [v["foto"] for v in case["variantes"]]:
            out.append(f'<li><a href="capturas/despues/{p["nombre"]}">{html.escape(p["nombre"])}</a></li>')
        out.append('</ul></article>')
    out.append('</html>')
    (HERE / "GALERIA.html").write_text("\n".join(out), encoding="utf-8")
    inventory = load("inventario-fuente-antes.json")["vistas"]
    rendered = set(v for c in load("respuestas-despues.json")["casos"] for v in c["vistas"])
    missing = [v["archivo"] for v in inventory if v["archivo"] not in rendered]
    text = '# T75 — Cobertura de la revisión móvil\n\n'
    text += f'{len(inventory)} fuentes Blade inventariadas; {sum(bool(v["marcas_interactivas"]) for v in inventory)} contienen controles. '
    text += f'{len(rendered.intersection(v["archivo"] for v in inventory))} vistas del repositorio renderizadas, más paginación de Laravel. '
    text += f'99 casos antes/después a 360, 375 y 1366; 2 casos condicionales adicionales. {len(manifest)} PNG publicados con hash verificado.\n\n'
    text += 'El inventario separa pantallas, componentes y plantillas. Los siguientes archivos no son pantallas web ejercitadas por estos casos:\n\n'
    text += '\n'.join('- `'+v+'`' for v in missing)
    text += '\n\n[Galería](GALERIA.html) · [Fuentes completas](inventario-fuente-antes.json) · [Respuestas reales](respuestas-despues.json) · [Manifiesto PNG](capturas-manifiesto.json).\n'
    (HERE / "INVENTARIO.md").write_text(text, encoding="utf-8")
    print(json.dumps({"casos": len(slugs), "PNG": len(manifest), "bytes": sum(p["bytes"] for p in manifest), "vistasNoRenderizadas": missing}, ensure_ascii=False))


if __name__ == "__main__":
    main()
