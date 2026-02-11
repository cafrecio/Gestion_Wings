<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Nombre --}}
    <div>
        <label for="nombre" class="block text-xs font-medium mb-1.5 text-wings-muted">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $alumno->nombre ?? '') }}" required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Nombre">
        @error('nombre') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
    </div>

    {{-- Apellido --}}
    <div>
        <label for="apellido" class="block text-xs font-medium mb-1.5 text-wings-muted">Apellido</label>
        <input type="text" id="apellido" name="apellido" value="{{ old('apellido', $alumno->apellido ?? '') }}" required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Apellido">
        @error('apellido') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
    </div>

    {{-- DNI --}}
    <div>
        <label for="dni" class="block text-xs font-medium mb-1.5 text-wings-muted">DNI</label>
        <input type="text" id="dni" name="dni" value="{{ old('dni', $alumno->dni ?? '') }}" required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="12345678">
        @error('dni') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
    </div>

    {{-- Fecha Nacimiento --}}
    <div>
        <label for="fecha_nacimiento" class="block text-xs font-medium mb-1.5 text-wings-muted">Fecha de nacimiento</label>
        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
               value="{{ old('fecha_nacimiento', isset($alumno) && $alumno->fecha_nacimiento ? $alumno->fecha_nacimiento->format('Y-m-d') : '') }}" required
               class="w-full px-4 py-2.5 text-sm wings-input" style="color-scheme: dark;">
        @error('fecha_nacimiento') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
    </div>

    {{-- Celular --}}
    <div>
        <label for="celular" class="block text-xs font-medium mb-1.5 text-wings-muted">Celular</label>
        <input type="text" id="celular" name="celular" value="{{ old('celular', $alumno->celular ?? '') }}" required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="11-1234-5678">
        @error('celular') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
    </div>

    {{-- Email --}}
    <div>
        <label for="email" class="block text-xs font-medium mb-1.5 text-wings-muted">Email <span class="text-wings-muted">(opcional)</span></label>
        <input type="email" id="email" name="email" value="{{ old('email', $alumno->email ?? '') }}"
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="email@ejemplo.com">
        @error('email') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
    </div>

    {{-- Deporte --}}
    <div>
        <label for="deporte_id" class="block text-xs font-medium mb-1.5 text-wings-muted">Deporte</label>
        <select id="deporte_id" name="deporte_id" required class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
            <option value="">Seleccionar...</option>
            @foreach($deportes as $deporte)
                <option value="{{ $deporte->id }}" {{ old('deporte_id', $alumno->deporte_id ?? '') == $deporte->id ? 'selected' : '' }}>
                    {{ $deporte->nombre }}
                </option>
            @endforeach
        </select>
        @error('deporte_id') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
    </div>

    {{-- Grupo --}}
    <div>
        <label for="grupo_id" class="block text-xs font-medium mb-1.5 text-wings-muted">Grupo</label>
        <select id="grupo_id" name="grupo_id" required class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
            <option value="">Seleccionar...</option>
            @foreach($grupos as $grupo)
                <option value="{{ $grupo->id }}" data-deporte="{{ $grupo->deporte_id }}" {{ old('grupo_id', $alumno->grupo_id ?? '') == $grupo->id ? 'selected' : '' }}>
                    {{ $grupo->nombre }}
                </option>
            @endforeach
        </select>
        @error('grupo_id') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Tutor --}}
<div class="mt-4 p-4 glass-card-sm">
    <p class="text-xs font-medium text-wings-muted mb-3">Datos del tutor <span class="text-wings-muted">(obligatorio para menores de edad)</span></p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="nombre_tutor" class="block text-xs font-medium mb-1.5 text-wings-muted">Nombre del tutor</label>
            <input type="text" id="nombre_tutor" name="nombre_tutor" value="{{ old('nombre_tutor', $alumno->nombre_tutor ?? '') }}"
                   class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Nombre y Apellido">
            @error('nombre_tutor') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="telefono_tutor" class="block text-xs font-medium mb-1.5 text-wings-muted">Teléfono del tutor</label>
            <input type="text" id="telefono_tutor" name="telefono_tutor" value="{{ old('telefono_tutor', $alumno->telefono_tutor ?? '') }}"
                   class="w-full px-4 py-2.5 text-sm wings-input" placeholder="11-1234-5678">
            @error('telefono_tutor') <p class="text-xs mt-1" style="color: #E6252F;">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

{{-- Activo (solo en edit) --}}
@if(isset($alumno))
<div class="mt-4 flex items-center gap-2">
    <input type="checkbox" id="activo" name="activo" value="1" {{ old('activo', $alumno->activo) ? 'checked' : '' }}
           class="rounded" style="accent-color: #E6252F;">
    <label for="activo" class="text-sm cursor-pointer text-wings-soft">Alumno activo</label>
</div>
@endif

{{-- JS: Filtrar grupos por deporte --}}
<script>
    document.getElementById('deporte_id').addEventListener('change', function() {
        const deporteId = this.value;
        const grupoSelect = document.getElementById('grupo_id');
        const options = grupoSelect.querySelectorAll('option[data-deporte]');

        grupoSelect.value = '';
        options.forEach(opt => {
            opt.style.display = (!deporteId || opt.dataset.deporte === deporteId) ? '' : 'none';
        });
    });

    // Trigger on load to filter if deporte is pre-selected
    document.addEventListener('DOMContentLoaded', () => {
        const deporteId = document.getElementById('deporte_id').value;
        if (deporteId) {
            const options = document.getElementById('grupo_id').querySelectorAll('option[data-deporte]');
            options.forEach(opt => {
                opt.style.display = opt.dataset.deporte === deporteId ? '' : 'none';
            });
        }
    });
</script>
