@extends('layouts.app')

@section('title', 'Página no disponible – Wings')
@section('module-title', 'Página no disponible')

@section('content')
@php
    $title = 'Página no disponible';
    $inicio = match (auth()->user()?->rol) {
        'ADMIN' => route('admin.dashboard'),
        'OPERATIVO' => route('web.operativo.dashboard'),
        'PROFESOR' => route('web.clases.index'),
        default => route('login'),
    };
@endphp
@include('errors._contenido', [
    'mensaje' => 'La página no existe o el registro ya no está',
    'ayuda' => 'Podés volver y seguir trabajando.',
    'inicio' => $inicio,
])
@endsection
