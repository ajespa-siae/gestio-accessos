@php
    $percentatge = $checklist->getProgressPercentage();
    $totalTasques = $checklist->tasques->count();
    $tasquesCompletades = $checklist->tasquesCompletades()->count();
    
    // Determinar el color de la barra de progreso según el estado
    if ($checklist->estat === 'completada') {
        $progressClass = 'bg-success';
    } elseif ($checklist->estat === 'en_progres') {
        $progressClass = 'bg-primary';
    } else {
        // Verificar si hay tareas con retraso
        $tasquesRetard = $checklist->tasques()
            ->where('completada', false)
            ->where('data_limit', '<', now())
            ->count();
        
        if ($tasquesRetard > 0) {
            $progressClass = 'bg-danger';
        } else {
            $progressClass = 'bg-warning';
        }
    }
@endphp

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <span class="fw-bold">Progrés:</span> 
            <span class="badge {{ $progressClass }}">{{ $percentatge }}%</span>
        </div>
        <div>
            <span class="text-muted">{{ $tasquesCompletades }} de {{ $totalTasques }} tasques completades</span>
        </div>
    </div>
    
    <div class="progress" style="height: 20px;">
        <div class="progress-bar {{ $progressClass }}" role="progressbar" 
            style="width: {{ $percentatge }}%;" 
            aria-valuenow="{{ $percentatge }}" aria-valuemin="0" aria-valuemax="100">
            {{ $percentatge }}%
        </div>
    </div>
</div>
