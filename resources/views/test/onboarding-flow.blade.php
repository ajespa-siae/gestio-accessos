@extends('layouts.app')

@section('title', 'Test de Flujo de Onboarding')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Test de Flujo de Onboarding</h1>
            <p class="lead">Esta página permite probar el flujo completo de onboarding de empleados.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('onboarding.dashboard') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Paso 1: Alta de Empleado</h5>
        </div>
        <div class="card-body">
            <p>Crear un nuevo empleado en el sistema. Esto iniciará automáticamente el proceso de onboarding.</p>
            <a href="{{ route('empleats.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Crear Nuevo Empleado
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Paso 2: Verificar Checklist de Onboarding</h5>
        </div>
        <div class="card-body">
            <p>Una vez creado el empleado, verificar que se ha generado correctamente la checklist de onboarding.</p>
            <p>Empleados recientes con checklist de onboarding:</p>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Fecha Alta</th>
                            <th>Estado Onboarding</th>
                            <th>Progreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($empleatsRecents as $empleat)
                            @php
                                $checklistOnboarding = $empleat->checklists()
                                    ->whereHas('template', function($q) {
                                        $q->where('tipus', 'onboarding');
                                    })
                                    ->first();
                                
                                $percentatge = $checklistOnboarding ? $checklistOnboarding->getProgressPercentage() : 0;
                                
                                if (!$checklistOnboarding) {
                                    $estatClass = 'bg-warning';
                                    $estatText = 'Pendiente';
                                } elseif ($checklistOnboarding->estat === 'completada') {
                                    $estatClass = 'bg-success';
                                    $estatText = 'Completado';
                                } elseif ($checklistOnboarding->estat === 'en_progres') {
                                    $estatClass = 'bg-primary';
                                    $estatText = 'En Proceso';
                                } else {
                                    $tasquesRetard = $checklistOnboarding->tasques()
                                        ->where('completada', false)
                                        ->where('data_limit', '<', now())
                                        ->count();
                                    
                                    if ($tasquesRetard > 0) {
                                        $estatClass = 'bg-danger';
                                        $estatText = 'Con Problemas';
                                    } else {
                                        $estatClass = 'bg-warning';
                                        $estatText = 'Pendiente';
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ $empleat->nom_complet }}</td>
                                <td>{{ $empleat->data_alta->format('d/m/Y') }}</td>
                                <td><span class="badge {{ $estatClass }}">{{ $estatText }}</span></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar {{ $estatClass }}" role="progressbar" 
                                            style="width: {{ $percentatge }}%;" 
                                            aria-valuenow="{{ $percentatge }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ $percentatge }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('empleats.show', $empleat) }}" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i> Ver Detalle
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No hay empleados recientes</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">Paso 3: Enviar Recordatorio</h5>
        </div>
        <div class="card-body">
            <p>Si hay tareas pendientes, enviar un recordatorio al departamento IT.</p>
            <p>Este paso simula el envío de recordatorios para tareas pendientes.</p>
            
            <form action="{{ route('test.enviar-recordatoris') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-info">
                    <i class="bi bi-bell"></i> Enviar Recordatorios de Prueba
                </button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="card-title mb-0">Paso 4: Simular Completar Tareas</h5>
        </div>
        <div class="card-body">
            <p>Simular la finalización de tareas de onboarding por parte de los diferentes departamentos.</p>
            
            <form action="{{ route('test.completar-tasques') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="empleat_id" class="form-label">Seleccionar Empleado</label>
                    <select class="form-select" id="empleat_id" name="empleat_id" required>
                        <option value="">Seleccionar empleado...</option>
                        @foreach($empleatsRecents as $empleat)
                            <option value="{{ $empleat->id }}">{{ $empleat->nom_complet }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="departament" class="form-label">Departamento que completa las tareas</label>
                    <select class="form-select" id="departament" name="departament" required>
                        <option value="">Seleccionar departamento...</option>
                        <option value="rrhh">Recursos Humanos</option>
                        <option value="it">Departamento IT</option>
                        <option value="gestor">Gestor</option>
                        <option value="admin">Administración</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-check-circle"></i> Simular Completar Tareas
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="card-title mb-0">Paso 5: Dar de Baja y Verificar Offboarding</h5>
        </div>
        <div class="card-body">
            <p>Dar de baja a un empleado y verificar que se inicia correctamente el proceso de offboarding.</p>
            
            <form action="{{ route('test.simular-baixa') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="empleat_baixa_id" class="form-label">Seleccionar Empleado para Baja</label>
                    <select class="form-select" id="empleat_baixa_id" name="empleat_id" required>
                        <option value="">Seleccionar empleado...</option>
                        @foreach($empleatsActius as $empleat)
                            <option value="{{ $empleat->id }}">{{ $empleat->nom_complet }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="observacions" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="observacions" name="observacions" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-person-dash"></i> Simular Baja de Empleado
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar toasts si hay mensajes flash
        initToasts();
    });
</script>
@endpush
