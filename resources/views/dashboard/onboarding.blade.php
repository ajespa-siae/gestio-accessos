@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Dashboard d'Onboarding</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('empleats.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nou Empleat
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Widget: Resumen de Onboarding -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Resum d'Onboarding</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="display-4 fw-bold">{{ $totalOnboardingActius }}</div>
                            <div class="text-muted">En Procés</div>
                        </div>
                        <div class="col-4">
                            <div class="display-4 fw-bold">{{ $totalOnboardingCompletats }}</div>
                            <div class="text-muted">Completats</div>
                        </div>
                        <div class="col-4">
                            <div class="display-4 fw-bold">{{ $totalOnboardingProblemes }}</div>
                            <div class="text-muted">Amb Problemes</div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>Temps mitjà d'onboarding:</div>
                        <div class="fw-bold">{{ $tempsMitjaOnboarding }} dies</div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('empleats.index') }}" class="btn btn-sm btn-outline-primary">Veure tots els empleats</a>
                </div>
            </div>
        </div>

        <!-- Widget: Próximas Incorporaciones -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Properes Incorporacions</h5>
                </div>
                <div class="card-body">
                    @if(count($properesIncorporacions) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Departament</th>
                                        <th>Data Alta</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($properesIncorporacions as $empleat)
                                        <tr>
                                            <td>{{ $empleat->nom_complet }}</td>
                                            <td>{{ $empleat->departament->nom }}</td>
                                            <td>{{ $empleat->data_alta->format('d/m/Y') }}</td>
                                            <td>
                                                <a href="{{ route('empleats.show', $empleat) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted my-4">No hi ha properes incorporacions programades</p>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('empleats.create') }}" class="btn btn-sm btn-outline-success">Programar nova incorporació</a>
                </div>
            </div>
        </div>

        <!-- Widget: Tareas Pendientes -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">Tasques Pendents</h5>
                </div>
                <div class="card-body">
                    @if(count($tasquesPendents) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tasca</th>
                                        <th>Empleat</th>
                                        <th>Responsable</th>
                                        <th>Data Límit</th>
                                        <th>Estat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tasquesPendents as $tasca)
                                        @php
                                            $estatClass = 'bg-warning';
                                            $estatText = 'Pendent';
                                            
                                            // Si ha pasado la fecha límite, mostrar como retrasada
                                            if ($tasca->data_limit && $tasca->data_limit < now()) {
                                                $estatClass = 'bg-danger';
                                                $estatText = 'Retard';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $tasca->descripcio }}</td>
                                            <td>
                                                @if($tasca->checklistInstance && $tasca->checklistInstance->empleat)
                                                    <a href="{{ route('empleats.show', $tasca->checklistInstance->empleat) }}">
                                                        {{ $tasca->checklistInstance->empleat->nom_complet }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">No assignat</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $tasca->responsable }}</span>
                                            </td>
                                            <td>
                                                @if($tasca->data_limit)
                                                    {{ $tasca->data_limit->format('d/m/Y') }}
                                                @else
                                                    <span class="text-muted">No definida</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $estatClass }}">{{ $estatText }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted my-4">No hi ha tasques pendents</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Les plantilles de checklist ara es gestionen des del panell Filament per administradors -->
    </div>
</div>
@endsection
