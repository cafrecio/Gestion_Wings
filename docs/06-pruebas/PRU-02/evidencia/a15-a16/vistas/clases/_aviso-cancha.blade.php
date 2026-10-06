@if(session('aviso_cancha'))
    <div class="ds-flash ds-flash--warning mb-4" role="alert" data-aviso-cancha style="align-items:flex-start;">
        <div>
            <strong>El alquiler se cuenta por bloques del reloj.</strong>
            @foreach(session('aviso_cancha.horarios', []) as $horario)
                <p class="text-sm mt-1">{{ $horario['detalle'] }}</p>
            @endforeach
            <p class="text-sm mt-2">Revisá los horarios. Confirmar guarda las clases con estos horarios.</p>
            <a href="#{{ session('aviso_cancha.campo', 'hora_inicio') }}" class="ds-btn-row ds-btn-row--sec mt-2">Revisar</a>
        </div>
    </div>
@endif
