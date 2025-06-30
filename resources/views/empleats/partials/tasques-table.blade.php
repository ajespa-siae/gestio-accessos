<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Tasca</th>
                <th>Responsable</th>
                <th>Data Límit</th>
                <th>Estat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($checklist->tasques as $tasca)
                @php
                    $estatClass = $tasca->completada ? 'bg-success' : 'bg-warning';
                    $estatText = $tasca->completada ? 'Completada' : 'Pendent';
                    
                    // Si está pendiente y ha pasado la fecha límite, mostrar como retrasada
                    if (!$tasca->completada && $tasca->data_limit && $tasca->data_limit < now()) {
                        $estatClass = 'bg-danger';
                        $estatText = 'Retard';
                    }
                @endphp
                <tr>
                    <td>{{ $tasca->descripcio }}</td>
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
                        @if($tasca->completada && $tasca->data_completada)
                            <small class="d-block text-muted">
                                {{ $tasca->data_completada->format('d/m/Y H:i') }}
                            </small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No hi ha tasques assignades</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
