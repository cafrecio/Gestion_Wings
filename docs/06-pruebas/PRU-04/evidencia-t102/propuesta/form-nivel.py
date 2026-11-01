# Arma el formulario propuesto a partir del real: en edición, Nivel pasa de texto fijo a lista.
import pathlib, sys
raiz = pathlib.Path(sys.argv[1])
v = (raiz / "resources/views/grupos/_form.blade.php").read_text(encoding="utf-8").replace("\r\n", "\n")
fijo = """        @if(isset($grupo))
            <p class="w-full px-4 py-2.5 text-sm wings-input" style="opacity: 0.6; cursor: not-allowed;">{{ $grupo->nivel->nombre ?? '–' }}</p>
            <input type="hidden" name="nivel_id" value="{{ $grupo->nivel_id }}">
        @else
            <select id="nivel_id\""""
assert fijo in v
v = v.replace(fijo, """        @if(true)
            <select id="nivel_id\"""")
sel = """old('nivel_id') == $nivel->id ? 'selected' : ''"""
assert sel in v
v = v.replace(sel, """old('nivel_id', $grupo->nivel_id ?? null) == $nivel->id ? 'selected' : ''""")
(pathlib.Path(sys.argv[2])).write_text(v, encoding="utf-8")
