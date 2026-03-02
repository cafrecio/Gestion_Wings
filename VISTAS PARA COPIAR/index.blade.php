@extends('adminlte::page')

@section('title', 'Clientes')

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
.main-sidebar, .navbar {
    max-width: 100% !important;
}

/* PAGINACIÓN HORIZONTAL SIN BULLETS */
.pagination-custom {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    list-style: none !important;
    padding: 0 !important;
    margin: 0 !important;
    gap: 0.5rem !important;
    align-items: center !important;
    justify-content: center !important;
}
.pagination-custom .page-item {
    margin: 0 !important;
    list-style: none !important;
    display: inline-block !important;
}
.pagination-custom .page-item::before,
.pagination-custom .page-item::after {
    content: none !important;
    display: none !important;
}
.pagination-custom .page-link {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border: 2px solid #E9ECEF;
    color: #2C3E50;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.9rem;
    text-decoration: none;
    min-height: 36px;
    min-width: 36px;
    cursor: pointer;
}
.pagination-custom .page-link:hover {
    border-color: #00A8CC;
    color: #00A8CC;
    background: #E6F7FB;
}
.pagination-custom .page-item.active .page-link {
    background: #00A8CC !important;
    border-color: #00A8CC !important;
    color: white !important;
}
.pagination-custom .page-item.disabled .page-link {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
</style>
@stop

@section('content_header')
    <h1><i class="fas fa-users"></i> Clientes</h1>
@stop

@section('content')
    <!-- Filtros -->
    <div class="filtros-card">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="search-input-group">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text"
                           id="buscar"
                           class="form-control"
                           placeholder="Buscar por código, CUIT o razón social..."
                           autocomplete="off"
                           autofocus>
                </div>
            </div>
            <div class="col-md-3">
                <select id="vendedor_filter" class="form-select form-control">
                    <option value="">Todos los vendedores</option>
                    @foreach($vendedores as $vendedor)
                        <option value="{{ $vendedor->id }}">{{ $vendedor->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button id="limpiar" class="btn btn-cye-secondary w-100" title="Limpiar filtros">
                    <i class="fas fa-eraser"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-bar mb-3">
        <div class="stats-info">
            Mostrando <strong>{{ $clientes->firstItem() }}</strong>
            a <strong>{{ $clientes->lastItem() }}</strong>
            de <strong>{{ $clientes->total() }}</strong> clientes
        </div>
    </div>

    <!-- Loading -->
    <div id="loading" class="loading" style="display: none;">
        <i class="fas fa-spinner"></i>
        <p class="mt-3">Buscando clientes...</p>
    </div>

    <!-- Resultados -->
    <div id="resultados">
        @forelse($clientes as $cliente)
            <div class="cliente-card">
                <div class="cliente-card-header">
                    <div class="cliente-estado activo"></div>
                    <h3 class="cliente-nombre">{{ $cliente->razon_social }}</h3>
                </div>

                <div class="cliente-info">
                    <div class="info-item">
                        <i class="fas fa-barcode"></i>
                        <span class="info-label">Código:</span>
                        <span class="info-value">{{ $cliente->codigo_isis }}</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-id-card"></i>
                        <span class="info-label">CUIT:</span>
                        <span class="info-value">{{ $cliente->cuit_formateado }}</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-user-tie"></i>
                        <span class="info-label">Vendedor:</span>
                        <span class="info-value">{{ $cliente->vendedor->nombre ?? '-' }}</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-tag"></i>
                        <span class="info-label">Lista:</span>
                        <span class="info-value">{{ $cliente->listaPrecio->descripcion ?? '-' }}</span>
                    </div>
                </div>

                <div class="cliente-actions">
                    <a href="{{ route('clientes.show', $cliente) }}" class="btn-action btn-ver-detalle">
                        <i class="fas fa-eye"></i> Ver Detalle
                    </a>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>No se encontraron clientes</h3>
                <p>Intenta con otros criterios de búsqueda</p>
            </div>
        @endforelse
    </div>

    {{-- Paginación personalizada --}}
    @if ($clientes->hasPages())
    <nav class="d-flex justify-content-center my-4">
        <ul class="pagination-custom">
            {{-- Botón Anterior --}}
            @if ($clientes->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $clientes->previousPageUrl() }}">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                </li>
            @endif

            {{-- Números de página --}}
            @php
                $start = max(1, $clientes->currentPage() - 2);
                $end = min($clientes->lastPage(), $clientes->currentPage() + 2);
            @endphp

            {{-- Primera página si no está en el rango --}}
            @if ($start > 1)
                <li class="page-item">
                    <a class="page-link" href="{{ $clientes->url(1) }}">1</a>
                </li>
                @if ($start > 2)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
            @endif

            {{-- Páginas del rango --}}
            @for ($i = $start; $i <= $end; $i++)
                @if ($i == $clientes->currentPage())
                    <li class="page-item active">
                        <span class="page-link">{{ $i }}</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $clientes->url($i) }}">{{ $i }}</a>
                    </li>
                @endif
            @endfor

            {{-- Última página si no está en el rango --}}
            @if ($end < $clientes->lastPage())
                @if ($end < $clientes->lastPage() - 1)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
                <li class="page-item">
                    <a class="page-link" href="{{ $clientes->url($clientes->lastPage()) }}">
                        {{ $clientes->lastPage() }}
                    </a>
                </li>
            @endif

            {{-- Botón Siguiente --}}
            @if ($clientes->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $clientes->nextPageUrl() }}">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
    @endif
@stop

@section('js')
<script>
let searchTimeout;

$(document).ready(function() {
    $('#buscar').on('input', function() {
        clearTimeout(searchTimeout);

        if (this.value.length >= 3 || this.value.length === 0) {
            searchTimeout = setTimeout(() => {
                buscarClientes();
            }, 500);
        }
    });

    $('#vendedor_filter').on('change', function() {
        buscarClientes();
    });

    $('#limpiar').on('click', function() {
        $('#buscar').val('');
        $('#vendedor_filter').val('');
        buscarClientes();
    });
});

function buscarClientes(page = 1) {
    $('#loading').show();
    $('#resultados').hide();

    $.ajax({
        url: '{{ route("clientes.index") }}',
        method: 'GET',
        data: {
            buscar: $('#buscar').val(),
            vendedor_id: $('#vendedor_filter').val(),
            page: page,
            ajax: 1
        },
        success: function(response) {
            $('#loading').hide();

            if (response.clientes.data.length === 0) {
                $('#resultados').html(`
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <h3>No se encontraron clientes</h3>
                        <p>Intenta con otros criterios de búsqueda</p>
                    </div>
                `).show();
                $('#showing').text('0');
                return;
            }

            let html = '';
            response.clientes.data.forEach(function(cliente) {
                html += `
                    <div class="cliente-card">
                        <div class="cliente-card-header">
                            <div class="cliente-estado activo"></div>
                            <h3 class="cliente-nombre">${cliente.razon_social}</h3>
                        </div>

                        <div class="cliente-info">
                            <div class="info-item">
                                <i class="fas fa-barcode"></i>
                                <span class="info-label">Código:</span>
                                <span class="info-value">${cliente.codigo_isis}</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-id-card"></i>
                                <span class="info-label">CUIT:</span>
                                <span class="info-value">${cliente.cuit_formateado}</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-user-tie"></i>
                                <span class="info-label">Vendedor:</span>
                                <span class="info-value">${cliente.vendedor ? cliente.vendedor.nombre : '-'}</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-tag"></i>
                                <span class="info-label">Lista:</span>
                                <span class="info-value">${cliente.lista_precio ? cliente.lista_precio.descripcion : '-'}</span>
                            </div>
                        </div>

                        <div class="cliente-actions">
                            <a href="/clientes/${cliente.id}" class="btn-action btn-ver-detalle">
                                <i class="fas fa-eye"></i> Ver Detalle
                            </a>
                        </div>
                    </div>
                `;
            });

            $('#resultados').html(html).show();
            $('#showing').text(response.clientes.data.length);
        },
        error: function() {
            $('#loading').hide();
            $('#resultados').html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Error al cargar los clientes</h3>
                    <p>Por favor, intenta nuevamente</p>
                </div>
            `).show();
        }
    });
}
</script>
@stop
