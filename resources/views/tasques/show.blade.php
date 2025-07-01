@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Detall de la tasca</h1>
                <a href="{{ route('tasques.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Tornar al llistat
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">{{ $tasca->nom }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Descripció</h6>
                        <p>{{ $tasca->descripcio ?: 'Sense descripció' }}</p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Estat</h6>
                            <p>{!! $tasca->getEstatFormatted() !!}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Prioritat</h6>
                            <p>
                                @if($tasca->getPrioritat() === 'alta')
                                    <span class="badge bg-danger">Alta</span>
                                @elseif($tasca->getPrioritat() === 'mitjana')
                                    <span class="badge bg-warning text-dark">Mitjana</span>
                                @else
                                    <span class="badge bg-success">Baixa</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Data d'assignació</h6>
                            <p>{{ $tasca->data_assignacio ? $tasca->data_assignacio->format('d/m/Y') : 'No definida' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Data límit</h6>
                            <p>
                                @if($tasca->data_limit)
                                    <span class="@if($tasca->estaVencuda()) text-danger fw-bold @elseif($tasca->estaProximaAVencer()) text-warning fw-bold @endif">
                                        {{ $tasca->data_limit->format('d/m/Y') }}
                                        @if($tasca->estaVencuda())
                                            <i class="bi bi-exclamation-triangle-fill text-danger" title="Vencida"></i>
                                        @elseif($tasca->estaProximaAVencer())
                                            <i class="bi bi-clock-fill text-warning" title="Propera a vèncer"></i>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-muted">No definida</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Rol assignat</h6>
                            <p><span class="badge bg-primary">{{ strtoupper($tasca->rol_assignat ?: 'No definit') }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Obligatòria</h6>
                            <p>{{ $tasca->obligatoria ? 'Sí' : 'No' }}</p>
                        </div>
                    </div>

                    @if(!$tasca->completada)
                        <hr>
                        <form action="{{ route('tasques.completar', $tasca) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="observacions" class="form-label">Observacions (opcional)</label>
                                <textarea class="form-control" id="observacions" name="observacions" rows="3" placeholder="Afegeix comentaris o observacions sobre la tasca completada"></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Marcar com a completada
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> Aquesta tasca ja ha estat completada el {{ $tasca->data_completada->format('d/m/Y') }}
                            @if($tasca->usuari_completat_id)
                                per {{ $tasca->usuariCompletat->name }}
                            @endif
                        </div>
                        @if($tasca->observacions)
                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Observacions</h6>
                                <p>{{ $tasca->observacions }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informació de l'empleat</h5>
                </div>
                <div class="card-body">
                    @if($tasca->checklistInstance?->empleat)
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary text-white me-3">
                                {{ substr($tasca->checklistInstance->empleat->nom, 0, 1) }}{{ substr($tasca->checklistInstance->empleat->cognoms, 0, 1) }}
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $tasca->checklistInstance->empleat->nom }} {{ $tasca->checklistInstance->empleat->cognoms }}</h6>
                                <small class="text-muted">{{ $tasca->checklistInstance->empleat->email }}</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Departament</h6>
                            <p>{{ $tasca->checklistInstance->empleat->departament->nom ?? 'No definit' }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Data d'alta</h6>
                            <p>{{ $tasca->checklistInstance->empleat->data_alta ? $tasca->checklistInstance->empleat->data_alta->format('d/m/Y') : 'No definida' }}</p>
                        </div>
                        
                        <div class="text-center">
                            <a href="{{ route('empleats.show', $tasca->checklistInstance->empleat) }}" class="btn btn-outline-primary">
                                <i class="bi bi-person"></i> Veure perfil complet
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-person-x text-muted" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-0">Informació d'empleat no disponible</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Procés</h5>
                </div>
                <div class="card-body">
                    @if($tasca->checklistInstance?->template)
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Tipus</h6>
                            <p>
                                <span class="badge bg-{{ $tasca->checklistInstance->template->tipus === 'onboarding' ? 'success' : 'danger' }}">
                                    {{ ucfirst($tasca->checklistInstance->template->tipus) }}
                                </span>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Plantilla</h6>
                            <p>{{ $tasca->checklistInstance->template->nom }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Progrés</h6>
                            @php
                                $totalTasques = $tasca->checklistInstance->tasques->count();
                                $completades = $tasca->checklistInstance->tasques->where('completada', true)->count();
                                $percentatge = $totalTasques > 0 ? round(($completades / $totalTasques) * 100) : 0;
                            @endphp
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentatge }}%;" aria-valuenow="{{ $percentatge }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">{{ $completades }} de {{ $totalTasques }} tasques completades ({{ $percentatge }}%)</small>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-clipboard-x text-muted" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-0">Informació del procés no disponible</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>
@endsection
