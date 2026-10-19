@extends('layouts.app')

{{-- T47 (DU-02, ACC-014): la dirección de una acción abierta a mano desde la barra. --}}
@section('title', 'Dirección de una acción – Wings')
@section('module-title', 'Dirección de una acción')

@section('content')
@php
    $title = 'Dirección de una acción';
    $inicio = match (auth()->user()?->rol) {
        'ADMIN' => route('admin.dashboard'),
        'OPERATIVO' => route('web.operativo.dashboard'),
        'PROFESOR' => route('web.clases.index'),
        default => route('login'),
    };
@endphp
@include('errors._contenido', [
    'mensaje' => 'Esta dirección no se abre a mano',
    'ayuda' => 'Es la dirección de una acción: se usa desde su botón en la pantalla. No se hizo ningún cambio.',
    'inicio' => $inicio,
])
@endsection
