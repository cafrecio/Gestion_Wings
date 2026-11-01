# Arma la vista propuesta a partir de la real: cambia solo el punto y las acciones de la tarjeta.
import pathlib, sys
raiz = pathlib.Path(sys.argv[1])
v = (raiz / "resources/views/niveles/index.blade.php").read_text(encoding="utf-8")
punto = """<span class="alumno-dot alumno-dot--{{ $nivel->grupos_count > 0 ? 'active' : 'neutral' }}"
                      role="img" aria-label="{{ $nivel->grupos_count > 0 ? 'Con grupos' : 'Sin grupos' }}"
                      title="{{ $nivel->grupos_count > 0 ? 'Con grupos' : 'Sin grupos' }}"></span>"""
assert punto in v
v = v.replace(punto, """<span class="alumno-dot alumno-dot--{{ $nivel->activo ? 'success' : 'neutral' }}"
                      role="img" aria-label="{{ $nivel->activo ? 'Activo' : 'Inactivo' }}"
                      title="{{ $nivel->activo ? 'Activo' : 'Inactivo' }}"></span>""")
acciones = """<div class="alumno-actions niveles-actions">
            <a href="{{ route('web.niveles.edit', $nivel->id) }}\""""
assert acciones in v
v = v.replace(acciones, """<div class="alumno-actions niveles-actions">
            <x-ds.toggle labelOn="Activo" labelOff="Inactivo" :checked="(bool) $nivel->activo" data-url="#" style="min-width:100px; justify-content:space-between;" />
            <a href="{{ route('web.niveles.edit', $nivel->id) }}\"""")
(pathlib.Path(sys.argv[2])).write_text(v, encoding="utf-8")
