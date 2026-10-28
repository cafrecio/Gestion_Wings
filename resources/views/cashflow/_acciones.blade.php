<span style="display:inline-flex; gap:4px; align-items:center;">
@foreach($acciones as $accion)
<a class="ds-btn-row {{ in_array($accion['nombre'], ['Eliminar', 'Anular'], true) ? 'ds-btn-row--dang' : 'ds-btn-row--sec' }}" style="width:28px; height:28px;" href="{{ $accion['url'] }}" title="{{ $accion['nombre'] }}" aria-label="{{ $accion['nombre'] }}">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@if($accion['nombre'] === 'Editar')
<path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/>
@elseif($accion['nombre'] === 'Eliminar')
<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>
@elseif($accion['nombre'] === 'Anular')
<circle cx="12" cy="12" r="9"/><path d="m6 6 12 12"/>
@elseif($accion['nombre'] === 'Historial')
<path d="M3 11a9 9 0 1 1 2 7M3 4v7h7M12 7v5l3 2"/>
@else
<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
@endif
</svg>
</a>
@endforeach
</span>
