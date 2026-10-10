@extends('errors.sin-contexto')

@php($title = 'Algo falló')
@section('title', 'Algo falló – Wings')
@section('module-title', 'Algo falló')

@section('content')
@include('errors._contenido', [
    'mensaje' => 'Algo falló de nuestro lado',
    'ayuda' => 'El problema ya quedó registrado. Probá de nuevo en un momento.',
    'inicio' => route('login'),
])
@endsection
