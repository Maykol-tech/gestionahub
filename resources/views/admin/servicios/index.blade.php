@extends('layouts.app')

@section('titulo', 'Servicios')
@section('subtitulo', 'Gestiona tu agenda y catálogo')

@section('contenido')
<div class="container-fluid py-4">
    
    <!-- 1. TARJETAS KPI (Diseño original) -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm border-0">
                <small class="text-muted fw-bold" style="font-size: 11px;">CITAS HOY</small>
                <h3 class="mb-0 fw-normal">0</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm border-0">
                <small class="text-muted fw-bold" style="font-size: 11px;">PENDIENTES DE CONFIRMAR</small>
                <h3 class="mb-0 fw-normal">0</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm border-0">
                <small class="text-muted fw-bold" style="font-size: 11px;">PRÃ“XIMAS CITAS</small>
                <h3 class="mb-0 fw-normal">0</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm border-0">
                <small class="text-muted fw-bold" style="font-size: 11px;">INGRESOS DEL MES</small>
                <h3 class="mb-0 fw-normal">S/ 0.00</h3>
            </div>
        </div>
    </div>

    <!-- 2. BARRA DE BÃšSQUEDA Y BOTÃ“N -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2 w-50">
                <input type="text" class="form-control" placeholder="Buscar cliente o servicio...">
                <select class="form-select w-50">
                    <option>Todos los estados</option>
                    <option>Confirmada</option>
                    <option>Pendiente</option>
                </select>
            </div>
            <button type="button" class="btn text-white fw-bold" style="background-color: #F47B20; border: none;" data-bs-toggle="modal" data-bs-target="#modalNuevaCita">
                + Nueva cita
            </button>
        </div>
    </div>

    <!-- 3. AGENDA VACÃA -->
    <div class="card shadow-sm border-0 mb-4 text-center py-5">
        <div class="card-body">
            <i class="bi bi-wrench text-muted" style="font-size: 2rem;"></i>
            <h6 class="mt-3 fw-bold">Aún no hay citas agendadas</h6>
            <p class="text-muted" style="font-size: 14px;">La agenda muestra los últimos 7 días y los próximos 30. Crea una cita con el botón Nueva cita.</p>
        </div>
    </div>

    <!-- 4. CATÃLOGO DE SERVICIOS -->
    <div class="mb-3">
        <h6 class="fw-bold">Catálogo de servicios</h6>
    </div>
    <div class="card shadow-sm border-0 mb-4 text-center py-5">
        <div class="card-body">
            <h6 class="fw-bold">Módulo de Servicios en preparación</h6>
            <p class="text-muted" style="font-size: 14px;">La interfaz de este módulo está lista para ser configurada.</p>
        </div>
    </div>

</div>

<!-- VENTANA FLOTANTE (MODAL) -->
<div class="modal fade" id="modalNuevaCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.citas.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Agendar Nueva Cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Cliente</label>
                        <input type="text" name="cliente_nombre" class="form-control" required placeholder="Ej. Juan Pérez">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Servicio</label>
                        <select name="servicio_id" class="form-select" required>
                            <option value="">Seleccione un servicio...</option>
                            <option value="1">Servicio de Prueba</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hora</label>
                            <input type="time" name="hora" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn text-white" style="background-color: #F47B20; border: none;">Guardar Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection