{{--
    layouts/panel.blade.php
    Shim de compatibilidad: vistas que usan @section('panel-content') siguen
    funcionando dentro del área de contenido de ds-app.
    Migrar cada vista a @extends('layouts.app') + @section('content') cuando
    corresponda.
--}}
@extends('layouts.ds-app')

@section('content')
    @yield('panel-content')
@endsection
