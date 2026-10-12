{{-- Los íconos abren el contexto de la propuesta; no ejecutan cambios. --}}
<span class="t27-iconos">
@foreach($opcion['acciones'] as $accion)
<a class="t27-icono {{ in_array($accion, ['Eliminar', 'Anular'], true) ? 't27-icono--danger' : '' }}" href="{{ $destino }}" title="{{ $accion }}" aria-label="{{ $accion }}">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@if($accion === 'Editar')
<path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/>
@elseif($accion === 'Eliminar')
<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>
@elseif($accion === 'Anular')
<circle cx="12" cy="12" r="9"/><path d="m6 6 12 12"/>
@else
<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
@endif
</svg>
</a>
@endforeach
</span>
