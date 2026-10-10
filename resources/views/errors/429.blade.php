@extends('layouts.app')

@section('title', 'Esperá un momento – Wings')
@section('module-title', 'Esperá un momento')

@section('content')
@php
    $title = 'Esperá un momento';
    $inicio = match (auth()->user()?->rol) {
        'ADMIN' => route('admin.dashboard'),
        'OPERATIVO' => route('web.operativo.dashboard'),
        'PROFESOR' => route('web.clases.index'),
        default => route('login'),
    };
@endphp
@include('errors._contenido', [
    'mensaje' => 'Demasiados intentos seguidos',
    'ayuda' => 'Esperá un momento y probá de nuevo.',
    'inicio' => $inicio,
])
@endsection
