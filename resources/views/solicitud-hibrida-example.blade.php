{{-- 
    EXEMPLE DE PLANTILLA BLADE PER FORMULARI HÍBRID
    
    Aquesta plantilla mostra com integrar la nova funcionalitat híbrida
    mantenint total compatibilitat amb el sistema Filament actual.
--}}

@extends('layouts.app')

@section('title', 'Sol·licitud d\'Accés Híbrida - Exemple')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        🚀 Nova Sol·licitud d'Accés (Sistema Híbrid)
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-info">Exemple d'Implementació</span>
                    </div>
                </div>

                <form id="solicitud_form" class="card-body">
                    @csrf
                    
                    {{-- Informació bàsica de la sol·licitud --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="empleat_destinatari_id">Empleat Destinatari *</label>
                                <select name="empleat_destinatari_id" id="empleat_destinatari_id" 
                                        class="form-control" required>
                                    <option value="">Selecciona un empleat...</option>
                                    @foreach(\App\Models\Empleat::all() as $empleat)
                                        <option value="{{ $empleat->id }}">{{ $empleat->nom_complet }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sistema_select">Sistema *</label>
                                <select id="sistema_select" class="form-control" required>
                                    <option value="">Carregant sistemes...</option>
                                </select>
                                <small class="form-text text-muted">
                                    Els sistemes marcats com (Híbrid) tenen configuració avançada
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="justificacio">Justificació *</label>
                                <textarea name="justificacio" id="justificacio" 
                                          class="form-control" rows="3" required
                                          placeholder="Explica el motiu de la sol·licitud..."></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Container dinàmic per permisos (simple o híbrid) --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">⚙️ Configuració de Permisos</h5>
                                </div>
                                <div class="card-body">
                                    <div id="permisos-container">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-arrow-up"></i>
                                            <p>Selecciona un sistema per configurar els permisos</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Botons d'acció --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group text-right">
                                <button type="button" class="btn btn-secondary mr-2" onclick="resetFormulari()">
                                    <i class="fas fa-times"></i> Cancel·lar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Enviar Sol·licitud
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Exemples de sol·licituds --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📋 Exemples de Configuració</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-layer-group"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Sistema Simple</span>
                                    <span class="info-box-number">Nivells Base</span>
                                    <span class="progress-description">
                                        Només selecció de perfils predefinits
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-cogs"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Sistema Híbrid</span>
                                    <span class="info-box-number">Nivells + Extra</span>
                                    <span class="progress-description">
                                        Perfils base + configuració avançada
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-edit"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Text Lliure</span>
                                    <span class="info-box-number">Personalitzat</span>
                                    <span class="progress-description">
                                        Especificació manual de requisits
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de confirmació --}}
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Sol·licitud</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Estàs segur que vols enviar aquesta sol·licitud?</p>
                <div id="resum-solicitud"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel·lar</button>
                <button type="button" class="btn btn-primary" id="confirmar-envio">Confirmar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .sistema-permisos {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        background-color: #f8f9fa;
    }

    .nivells-simples, .elements-extra {
        margin-bottom: 1.5rem;
    }

    .nivells-simples h4, .elements-extra h4 {
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }

    .element-extra-item {
        border: 1px solid #e9ecef;
        border-radius: 0.25rem;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        background-color: white;
    }

    .element-extra-item:hover {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .element-header {
        font-weight: 500;
        margin-bottom: 0;
    }

    .element-options {
        border-top: 1px solid #e9ecef;
        padding-top: 0.75rem;
        margin-top: 0.75rem;
    }

    .form-check {
        margin-bottom: 0.5rem;
    }

    .form-check-label {
        margin-left: 0.25rem;
    }

    .info-box {
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        border-radius: 0.25rem;
        background-color: #fff;
        display: flex;
        margin-bottom: 1rem;
        min-height: 80px;
        padding: 0.5rem;
        position: relative;
        width: 100%;
    }

    .info-box-icon {
        border-radius: 0.25rem;
        align-items: center;
        display: flex;
        font-size: 1.875rem;
        justify-content: center;
        text-align: center;
        width: 70px;
        color: white;
    }

    .info-box-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        line-height: 1.8;
        margin-left: 0.5rem;
        padding: 0 0.5rem;
    }

    .info-box-text {
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .info-box-number {
        font-size: 1.125rem;
        font-weight: 700;
    }

    .progress-description {
        color: #6c757d;
        font-size: 0.75rem;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/solicitud-hibrida-example.js') }}"></script>
<script>
    function resetFormulari() {
        if (window.SolicitudHibridaManager) {
            const manager = new window.SolicitudHibridaManager();
            manager.resetFormulari();
        }
    }

    // Funcions auxiliars per la demo
    document.addEventListener('DOMContentLoaded', function() {
        // Afegir tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Configurar modal de confirmació
        $('#confirmar-envio').on('click', function() {
            $('#confirmModal').modal('hide');
            // Aquí es faria l'enviament real
        });
    });
</script>
@endpush
