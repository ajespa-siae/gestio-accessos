@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Tasques pendents</h1>
            <p class="text-muted">Tasques assignades al teu rol: <span class="badge bg-primary">{{ strtoupper($user->rol_principal) }}</span></p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tasca</th>
                            <th>Empleat</th>
                            <th>Procés</th>
                            <th>Data límit</th>
                            <th>Estat</th>
                            <th>Accions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasques as $tasca)
                            <tr>
                                <td>{{ $tasca->nom }}</td>
                                <td>
                                    @if($tasca->checklistInstance?->empleat)
                                        <a href="{{ route('empleats.show', $tasca->checklistInstance->empleat) }}" class="text-decoration-none">
                                            {{ $tasca->checklistInstance->empleat->nom }} {{ $tasca->checklistInstance->empleat->cognoms }}
                                        </a>
                                    @else
                                        <span class="text-muted">No disponible</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tasca->checklistInstance?->template)
                                        <span class="badge bg-{{ $tasca->checklistInstance->template->tipus === 'onboarding' ? 'success' : 'danger' }}">
                                            {{ ucfirst($tasca->checklistInstance->template->tipus) }}
                                        </span>
                                    @else
                                        <span class="text-muted">No disponible</span>
                                    @endif
                                </td>
                                <td>
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
                                </td>
                                <td>{!! $tasca->getEstatFormatted() !!}</td>
                                <td>
                                    <a href="{{ route('tasques.show', $tasca) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Veure
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0">No tens tasques pendents</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $tasques->links() }}
    </div>
</div>
@endsection
