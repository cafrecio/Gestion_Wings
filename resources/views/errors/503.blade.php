@extends('errors.sin-contexto')

@php($title = 'Estamos actualizando Wings')
@section('title', 'Estamos actualizando Wings – Wings')
@section('module-title', 'Estamos actualizando Wings')

@section('content')
@include('errors._contenido', [
    'mensaje' => 'Wings se está actualizando',
    'ayuda' => 'Volvé en unos minutos.',
    'inicio' => route('login'),
])
@endsection
