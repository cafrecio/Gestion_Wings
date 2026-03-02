@extends('adminlte::page')

@section('title', 'Cliente - ' . $cliente->razon_social)

@section('css')
<style>
/* ANTI-OVERFLOW CRÍTICO */
html, body {
    overflow-x: hidden !important;
    max-width: 100vw !important;
}
.content-wrapper, .content {
    overflow-x: hidden !important;
    max-width: 100vw !important;
}

/* CUIT VISIBLE EN HEADER */
.content-header h1 small,
.content-header .text-muted {
    color: white !important;
    opacity: 0.9 !important;
}

/* CARDS DE CONTACTOS - DISEÑO SISTEMA */
.contacto-card {
    background: #FFFFFF;
    border: none;
    border-left: 4px solid #00A8CC;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.contacto-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.estado-indicador {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
    display: inline-block;
    margin-right: 8px;
}

.estado-indicador.activo {
    background: #28A745;
}

.estado-indicador.inactivo {
    background: #DC3545;
}

/* Nombre del contacto - Igual que cliente-nombre */
.contacto-nombre {
    font-size: 18px;
    font-weight: bold;
    color: #2C3E50;
}

/* GRID HORIZONTAL DE DATOS - Sección 10 */
.contacto-datos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin: 12px 0;
}

.contacto-dato-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.contacto-dato-item i {
    color: #00A8CC;
    width: 20px;
    flex-shrink: 0;
}

.contacto-dato-item .etiqueta {
    color: #6C757D;
    font-size: 0.9em;
}

.contacto-dato-item .valor {
    color: #2C3E50;
    font-weight: 600;
}

/* SWITCHES CELESTES - Sección 8 */
.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #00A8CC !important;
    border-color: #00A8CC !important;
}

/* Override adicional para asegurar color celeste en todos los estados */
.custom-switch .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #00A8CC !important;
    border-color: #00A8CC !important;
}

.custom-switch .custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(0, 168, 204, 0.25) !important;
}

/* BADGE CELESTE CyE */
.badge-cye {
    background-color: #00A8CC;
    color: #fff;
}

/* FLEXBOX TOGGLES - Sistema V3.3 (Proximidad Corregida) */
.toggle-container {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 16px;
    border: 2px solid #E9ECEF;
    border-radius: 8px;
    background: #F8F9FA;
}

.toggle-label {
    font-weight: bold;
    color: #2C3E50;
    margin: 0;
}

.toggle-label i {
    color: #00A8CC;
    margin-right: 8px;
}

.helper-text {
    font-size: 0.85em;
    color: #6C757D;
    margin-top: 4px;
}

/* Estilos de alineación de botones ahora están en cye-styles.css */

/* ==========================================================================
   ACCESIBILIDAD Y FOCO - VERSIÓN SUTIL DEFINITIVA (Sin bordes negros)
   ========================================================================== */

/* 1. SELECCIÓN DE TEXTO */
::selection {
    background-color: #00A8CC !important;
    color: #FFFFFF !important;
}

/* 2. INPUTS Y SELECTS */
.form-control:focus,
.custom-select:focus,
.custom-control-input:focus ~ .custom-control-label::before {
    background-color: #c5e2f6 !important; /* Celeste muy suave */
    border-color: #00A8CC !important;
    color: #000000 !important;
    outline: none !important;
    box-shadow: none !important;
}

/* 3. BOTONES - CERO BORDE NEGRO */
.btn:focus,
.btn.focus,
.btn:active:focus {
    outline: none !important;
    transform: translateY(-1px) !important;
    z-index: 10 !important;
    position: relative;
}

/* Verde */
.btn-success:focus {
    background-color: #218838 !important;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3) !important;
}

/* Celeste */
.btn-info:focus,
.btn-ver-detalle:focus {
    background-color: #0096B8 !important;
    box-shadow: 0 0 0 3px rgba(0, 168, 204, 0.3) !important;
}

/* Gris */
.btn-secondary:focus {
    background-color: #545b62 !important;
    box-shadow: 0 0 0 3px rgba(108, 117, 125, 0.3) !important;
}

/* Rojo */
.btn-danger:focus {
    background-color: #c82333 !important;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.3) !important;
}

/* 4. SWITCHES Y TOGGLES - SUTIL */
.custom-switch .custom-control-input:focus ~ .custom-control-label::before {
    border-color: #00A8CC !important;
    box-shadow: none !important;
}
</style>
@stop

@section('content_header')
    <div class="bg-cye-celeste p-3 text-white">
        <h1 class="m-0">
            <i class="fas fa-user mr-2"></i> {{ $cliente->razon_social }}
            <small class="text-white-50 ml-2" style="font-size: 0.6em;">{{ $cliente->cuit_formateado }}</small>
        </h1>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- DATOS ISIS --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-database"></i> Datos ISIS (Solo Lectura)</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5">
                            <i class="fas fa-barcode" style="color: #00A8CC; width: 20px;"></i> Código ISIS:
                        </dt>
                        <dd class="col-sm-7">{{ $cliente->codigo_isis }}</dd>

                        <dt class="col-sm-5">
                            <i class="fas fa-id-card" style="color: #00A8CC; width: 20px;"></i> CUIT:
                        </dt>
                        <dd class="col-sm-7">{{ $cliente->cuit_formateado }}</dd>

                        <dt class="col-sm-5">
                            <i class="fas fa-building" style="color: #00A8CC; width: 20px;"></i> Razón Social:
                        </dt>
                        <dd class="col-sm-7">{{ $cliente->razon_social }}</dd>

                        <dt class="col-sm-5">
                            <i class="fas fa-check-circle" style="color: #00A8CC; width: 20px;"></i> Estado:
                        </dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-success">{{ $cliente->estado_isis }}</span>
                        </dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5">
                            <i class="fas fa-user-tie" style="color: #00A8CC; width: 20px;"></i> Vendedor:
                        </dt>
                        <dd class="col-sm-7">{{ $cliente->vendedor->nombre ?? '-' }}</dd>

                        <dt class="col-sm-5">
                            <i class="fas fa-tags" style="color: #00A8CC; width: 20px;"></i> Lista de Precios:
                        </dt>
                        <dd class="col-sm-7">{{ $cliente->listaPrecio->descripcion ?? '-' }}</dd>

                        <dt class="col-sm-5">
                            <i class="fas fa-calendar-check" style="color: #00A8CC; width: 20px;"></i> Condición de Pago:
                        </dt>
                        <dd class="col-sm-7">
                            {{ $cliente->condicionPago->descripcion ?? '-' }}
                            @if($cliente->condicionPago)
                                <small class="text-muted">
                                    ({{ $cliente->condicionPago->dias_plazo }} días)
                                </small>
                            @endif
                        </dd>

                        <dt class="col-sm-5">
                            <i class="fas fa-dollar-sign" style="color: #00A8CC; width: 20px;"></i> Moneda:
                        </dt>
                        <dd class="col-sm-7">
                            {{ $cliente->moneda->descripcion ?? '-' }}
                            <small class="text-muted">{{ $cliente->moneda->signo ?? '' }}</small>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- DATOS LOCALES - EDITABLE INLINE --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Datos Locales</h3>
        </div>
        <form id="formDatosLocales" action="{{ route('clientes.update', $cliente) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="row">
                    {{-- Campo Entregamos - Flexbox Toggle --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="toggle-container">
                                <label class="toggle-label" for="entregamos">
                                    <i class="fas fa-truck"></i> Entregamos
                                </label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="entregamos"
                                           name="entregamos"
                                           value="1"
                                           {{ old('entregamos', $cliente->entregamos) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="entregamos"></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Campo Tipo Dólar - Switch Único --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="toggle-container">
                                <label class="toggle-label" for="tipo_dolar">
                                    <i class="fas fa-money-bill-wave"></i> Dólar Billete
                                </label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="tipo_dolar"
                                           name="tipo_dolar"
                                           value="billete"
                                           {{ old('tipo_dolar', $cliente->tipo_dolar) === 'billete' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tipo_dolar"></label>
                                </div>
                            </div>
                            <small class="helper-text">Desactivado: Aplica cotización Divisa</small>
                        </div>
                    </div>
                </div>

                {{-- Observaciones --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="observaciones" class="font-weight-bold">
                                <i class="fas fa-sticky-note" style="color: #00A8CC;"></i> Observaciones
                            </label>
                            <textarea class="form-control"
                                      id="observaciones"
                                      name="observaciones"
                                      rows="3">{{ old('observaciones', $cliente->datosLocales->observaciones ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- CONTACTOS --}}
    <div class="card">
        <div class="card-header" style="padding-right: 43px;">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="card-title"><i class="fas fa-address-book"></i> Contactos</h3>
                </div>
                <div class="col-md-4 d-flex justify-content-end align-items-center">
                    <a href="{{ route('clientes.contactos.create', $cliente) }}"
                       class="btn btn-success mr-3"
                       style="height: 38px; padding: 6px 12px;">
                        <i class="fas fa-plus mr-2"></i>Nuevo
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @forelse($cliente->contactos as $contacto)
                <div class="contacto-card">
                    {{-- Encabezado con nombre y estado --}}
                    <div class="mb-2">
                        <h5 class="contacto-nombre mb-2">
                            <span class="estado-indicador {{ $contacto->activo ? 'activo' : 'inactivo' }}"></span>
                            {{ $contacto->nombre_completo }}
                            @if($contacto->principal)
                                <span class="badge badge-cye ml-2">Principal</span>
                            @endif
                        </h5>
                    </div>

                    {{-- Grid horizontal de datos - UNA SOLA FILA --}}
                    <div class="contacto-datos-grid">
                        {{-- Tipo --}}
                        <div class="contacto-dato-item">
                            <i class="fas fa-tag"></i>
                            <span class="etiqueta">Tipo:</span>
                            <span class="valor">{{ $contacto->tipo->nombre }}</span>
                        </div>
                        {{-- Cargo --}}
                        @if($contacto->cargo)
                            <div class="contacto-dato-item">
                                <i class="fas fa-briefcase"></i>
                                <span class="etiqueta">Cargo:</span>
                                <span class="valor">{{ $contacto->cargo }}</span>
                            </div>
                        @endif
                        {{-- Email --}}
                        @if($contacto->email)
                            <div class="contacto-dato-item">
                                <i class="fas fa-envelope"></i>
                                <span class="etiqueta">Email:</span>
                                <span class="valor">{{ $contacto->email }}</span>
                            </div>
                        @endif
                        {{-- Celular --}}
                        @if($contacto->celular)
                            <div class="contacto-dato-item">
                                <i class="fas fa-mobile-alt"></i>
                                <span class="etiqueta">Tel:</span>
                                <span class="valor">{{ $contacto->celular }}</span>
                            </div>
                        @endif
                        {{-- Teléfono --}}
                        @if($contacto->telefono)
                            <div class="contacto-dato-item">
                                <i class="fas fa-phone"></i>
                                <span class="etiqueta">Tel:</span>
                                <span class="valor">{{ $contacto->telefono }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Botones abajo a la izquierda --}}
                    <div class="mt-2">
                        <a href="{{ route('clientes.contactos.edit', [$cliente, $contacto]) }}"
                           class="btn btn-info text-white mr-2"
                           style="height: 38px; padding: 6px 12px;">
                            <i class="fas fa-pen mr-2"></i>Editar
                        </a>
                        <form action="{{ route('clientes.contactos.destroy', [$cliente, $contacto]) }}"
                              method="POST"
                              style="display: inline-block; margin: 0;"
                              onsubmit="return confirm('¿Está seguro de eliminar este contacto?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="height: 38px; padding: 6px 12px;">
                                <i class="fas fa-trash mr-2"></i>Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Este cliente no tiene contactos registrados.
                </div>
            @endforelse
        </div>
    </div>

    <div class="row" style="margin-top: 24px; margin-bottom: 40px; padding-right: 43px;">
        <div class="col-md-8"></div>
        <div class="col-md-4 d-flex justify-content-end align-items-center">
            <a href="{{ route('clientes.index') }}"
               class="btn btn-secondary mr-2"
               style="height: 38px; padding: 6px 12px;">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
            <button type="submit"
                    form="formDatosLocales"
                    class="btn btn-success mr-3"
                    style="height: 38px; padding: 6px 12px;">
                <i class="fas fa-save mr-2"></i>Guardar
            </button>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // ALERTA DE DESCARTE DE CAMBIOS - Datos Locales
    let formModificado = false;
    let enviandoFormulario = false;

    // Valores iniciales del formulario
    const valoresIniciales = {
        entregamos: $('#entregamos').is(':checked'),
        tipo_dolar: $('#tipo_dolar').is(':checked'),
        observaciones: $('#observaciones').val()
    };

    // Detectar cambios en los campos del formulario
    $('#entregamos, #tipo_dolar, #observaciones').on('change input', function() {
        const valoresActuales = {
            entregamos: $('#entregamos').is(':checked'),
            tipo_dolar: $('#tipo_dolar').is(':checked'),
            observaciones: $('#observaciones').val()
        };

        // Comparar valores actuales con iniciales
        formModificado = (
            valoresActuales.entregamos !== valoresIniciales.entregamos ||
            valoresActuales.tipo_dolar !== valoresIniciales.tipo_dolar ||
            valoresActuales.observaciones !== valoresIniciales.observaciones
        );
    });

    // Evento antes de abandonar la página
    window.onbeforeunload = function(e) {
        // No mostrar alerta si el formulario no está modificado
        if (!formModificado) {
            return undefined;
        }

        // No mostrar alerta si se está enviando el formulario
        if (enviandoFormulario) {
            return undefined;
        }

        // Mensaje de advertencia
        const mensaje = "ADVERTENCIA: Tienes cambios sin guardar. Si continúas, se perderán.";
        e.returnValue = mensaje; // Para navegadores antiguos
        return mensaje; // Para navegadores modernos
    };

    // Marcar que se está enviando el formulario (no mostrar alerta)
    $('#formDatosLocales').on('submit', function() {
        enviandoFormulario = true;
    });

    // Desactivar alerta al hacer click en Volver o Cancelar
    $('a.btn-secondary[href*="clientes.index"]').on('click', function() {
        if (formModificado && !confirm("ADVERTENCIA: Tienes cambios sin guardar. ¿Continuar sin guardar?")) {
            return false;
        }
        window.onbeforeunload = null;
    });
});
</script>
@stop
