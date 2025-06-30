@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Gestió d'Empleats</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('empleats.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nou Empleat
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Filtres</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('empleats.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="departament" class="form-label">Departament</label>
                    <select name="departament" id="departament" class="form-select">
                        <option value="">Tots els departaments</option>
                        @foreach($departaments as $departament)
                            <option value="{{ $departament->id }}" {{ request('departament') == $departament->id ? 'selected' : '' }}>
                                {{ $departament->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="estat" class="form-label">Estat</label>
                    <select name="estat" id="estat" class="form-select">
                        <option value="">Tots els estats</option>
                        <option value="actiu" {{ request('estat') == 'actiu' ? 'selected' : '' }}>Actiu</option>
                        <option value="baixa" {{ request('estat') == 'baixa' ? 'selected' : '' }}>Baixa</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="data_alta" class="form-label">Data d'alta</label>
                    <input type="date" name="data_alta" id="data_alta" class="form-control" value="{{ request('data_alta') }}">
                </div>
                <div class="col-md-3">
                    <label for="cerca" class="form-label">Cercar</label>
                    <div class="input-group">
                        <input type="text" name="cerca" id="cerca" class="form-control" placeholder="Nom, NIF, correu..." value="{{ request('cerca') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom Complet</th>
                            <th>NIF</th>
                            <th>Departament</th>
                            <th>Càrrec</th>
                            <th>Data Alta</th>
                            <th>Estat Onboarding</th>
                            <th>Accions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($empleats as $empleat)
                            <tr>
                                <td>{{ $empleat->nom_complet }}</td>
                                <td>{{ $empleat->nif }}</td>
                                <td>{{ $empleat->departament->nom }}</td>
                                <td>{{ $empleat->carrec }}</td>
                                <td>{{ $empleat->data_alta->format('d/m/Y') }}</td>
                                <td>
                                    @php
                                        $checklistOnboarding = $empleat->checklists()
                                            ->whereHas('template', function($q) {
                                                $q->where('tipus', 'onboarding');
                                            })
                                            ->first();
                                        
                                        if (!$checklistOnboarding) {
                                            $estatOnboarding = 'pendent';
                                            $badgeClass = 'bg-warning';
                                            $estatText = 'Pendent';
                                        } elseif ($checklistOnboarding->estat === 'completada') {
                                            $estatOnboarding = 'completat';
                                            $badgeClass = 'bg-success';
                                            $estatText = 'Completat';
                                        } elseif ($checklistOnboarding->estat === 'en_progres') {
                                            $estatOnboarding = 'en_proces';
                                            $badgeClass = 'bg-primary';
                                            $estatText = 'En Procés';
                                        } else {
                                            // Verificar si hay tareas con retraso
                                            $tasquesRetard = $checklistOnboarding->tasques()
                                                ->where('completada', false)
                                                ->where('data_limit', '<', now())
                                                ->count();
                                            
                                            if ($tasquesRetard > 0) {
                                                $estatOnboarding = 'problemes';
                                                $badgeClass = 'bg-danger';
                                                $estatText = 'Problemes';
                                            } else {
                                                $estatOnboarding = 'pendent';
                                                $badgeClass = 'bg-warning';
                                                $estatText = 'Pendent';
                                            }
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $estatText }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('empleats.show', $empleat) }}" class="btn btn-sm btn-info" title="Veure Detall">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('empleats.edit', $empleat) }}" class="btn btn-sm btn-warning" title="Editar Empleat">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if($empleat->estat === 'actiu')
                                            <button type="button" class="btn btn-sm btn-danger" title="Donar de Baixa" 
                                                data-bs-toggle="modal" data-bs-target="#baixaModal{{ $empleat->id }}">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        @endif
                                    </div>
                                    
                                    <!-- Modal Baixa -->
                                    <div class="modal fade" id="baixaModal{{ $empleat->id }}" tabindex="-1" aria-hidden="true">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No s'han trobat empleats</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                {{ $empleats->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
