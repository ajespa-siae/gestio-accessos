@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>{{ $empleat->nom_complet }}</h1>
            <p class="text-muted">
                <span class="badge {{ $empleat->estat === 'actiu' ? 'bg-success' : 'bg-danger' }}">
                    {{ $empleat->estat === 'actiu' ? 'Actiu' : 'Baixa' }}
                </span>
                <span class="ms-2">ID: {{ $empleat->identificador_unic }}</span>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <a href="{{ route('empleats.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Tornar al llistat
                </a>
                <a href="{{ route('empleats.edit', $empleat) }}" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                @if($empleat->estat === 'actiu')
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#baixaModal">
                        <i class="bi bi-x-circle"></i> Donar de Baixa
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Información Personal -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informació Personal</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>NIF:</th>
                            <td>{{ $empleat->nif }}</td>
                        </tr>
                        <tr>
                            <th>Correu Personal:</th>
                            <td>{{ $empleat->correu_personal }}</td>
                        </tr>
                        <tr>
                            <th>Departament:</th>
                            <td>{{ $empleat->departament->nom }}</td>
                        </tr>
                        <tr>
                            <th>Càrrec:</th>
                            <td>{{ $empleat->carrec }}</td>
                        </tr>
                        <tr>
                            <th>Data d'Alta:</th>
                            <td>{{ $empleat->data_alta->format('d/m/Y') }}</td>
                        </tr>
                        @if($empleat->data_baixa)
                        <tr>
                            <th>Data de Baixa:</th>
                            <td>{{ $empleat->data_baixa->format('d/m/Y') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Creat per:</th>
                            <td>{{ $empleat->usuariCreador->name ?? 'Desconegut' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Observacions</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#observacioModal">
                        <i class="bi bi-plus-circle"></i> Afegir
                    </button>
                </div>
                <div class="card-body">
                    @if($empleat->observacions)
                        <div class="observacions-text">
                            {!! nl2br(e($empleat->observacions)) !!}
                        </div>
                    @else
                        <p class="text-muted">No hi ha observacions registrades.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Checklist y Cronología -->
        <div class="col-md-8">
            @if($checklistOnboarding)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Estat Checklist Onboarding</h5>
                    </div>
                    <div class="card-body">
                        <!-- Progress Bar -->
                        @include('empleats.partials.checklist-progress', ['checklist' => $checklistOnboarding])
                        
                        <!-- Tabla de Tareas -->
                        @include('empleats.partials.tasques-table', ['checklist' => $checklistOnboarding])
                        
                        <!-- Acciones -->
                        <div class="d-flex justify-content-end mt-3">
                            @if($checklistOnboarding->tasquesPendents()->count() > 0)
                                <form action="{{ route('empleats.recordatori', $empleat) }}" method="POST" class="me-2">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-bell"></i> Enviar Recordatori a IT
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> No s'ha trobat cap checklist d'onboarding per aquest empleat.
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cronologia d'Esdeveniments</h5>
                </div>
                <div class="card-body">
                    @include('empleats.partials.cronologia', ['esdeveniments' => $esdeveniments])
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Baixa -->
<div class="modal fade" id="baixaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('empleats.baixa', $empleat) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar baixa d'empleat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Estàs segur que vols donar de baixa a <strong>{{ $empleat->nom_complet }}</strong>?</p>
                    <p>Aquesta acció iniciarà el procés d'offboarding automàticament.</p>
                    <div class="mb-3">
                        <label for="observacions" class="form-label">Observacions (opcional)</label>
                        <textarea name="observacions" id="observacions" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·lar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Baixa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Observació -->
<div class="modal fade" id="observacioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('empleats.observacio', $empleat) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Afegir Observació</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="observacio" class="form-label">Observació</label>
                        <textarea name="observacio" id="observacio" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·lar</button>
                    <button type="submit" class="btn btn-primary">Guardar Observació</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
