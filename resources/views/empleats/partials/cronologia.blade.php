<div class="timeline">
    @forelse($esdeveniments as $esdeveniment)
        <div class="timeline-item">
            <div class="timeline-marker {{ $esdeveniment->tipus === 'sistema' ? 'bg-info' : 'bg-primary' }}">
                <i class="bi {{ getIconForEvent($esdeveniment->accio) }}"></i>
            </div>
            <div class="timeline-content">
                <div class="d-flex justify-content-between">
                    <h6 class="mb-1">{{ $esdeveniment->descripcio }}</h6>
                    <small class="text-muted">{{ $esdeveniment->created_at->format('d/m/Y H:i') }}</small>
                </div>
                @if($esdeveniment->detalls)
                    <p class="text-muted mb-0">{{ $esdeveniment->detalls }}</p>
                @endif
                <small>{{ $esdeveniment->usuari ? 'Per: ' . $esdeveniment->usuari->name : 'Sistema' }}</small>
            </div>
        </div>
    @empty
        <div class="text-center text-muted">
            <p>No hi ha esdeveniments registrats</p>
        </div>
    @endforelse
</div>

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem;
        border-left: 2px solid #e9ecef;
    }
    
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    
    .timeline-marker {
        position: absolute;
        left: -11px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        text-align: center;
        line-height: 20px;
        color: white;
        font-size: 0.75rem;
    }
    
    .timeline-content {
        padding-left: 15px;
        padding-bottom: 10px;
    }
</style>
@endpush

@push('scripts')
<script>
    function getIconForEvent(accio) {
        // Esta función se utiliza en la vista para determinar el icono según el tipo de evento
        switch(accio) {
            case 'alta':
                return 'bi-person-plus';
            case 'baixa':
                return 'bi-person-dash';
            case 'edicio':
                return 'bi-pencil';
            case 'observacio':
                return 'bi-chat-left-text';
            case 'recordatori':
                return 'bi-bell';
            case 'tasca_completada':
                return 'bi-check-circle';
            case 'checklist_completada':
                return 'bi-list-check';
            default:
                return 'bi-info-circle';
        }
    }
</script>
@endpush
