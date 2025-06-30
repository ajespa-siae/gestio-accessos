@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Nou Empleat</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('empleats.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Tornar al llistat
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('empleats.store') }}" method="POST">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nom_complet" class="form-label">Nom Complet *</label>
                        <input type="text" class="form-control @error('nom_complet') is-invalid @enderror" 
                            id="nom_complet" name="nom_complet" value="{{ old('nom_complet') }}" required>
                        @error('nom_complet')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="nif" class="form-label">NIF *</label>
                        <input type="text" class="form-control @error('nif') is-invalid @enderror" 
                            id="nif" name="nif" value="{{ old('nif') }}" required>
                        @error('nif')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="correu_personal" class="form-label">Correu Personal *</label>
                        <input type="email" class="form-control @error('correu_personal') is-invalid @enderror" 
                            id="correu_personal" name="correu_personal" value="{{ old('correu_personal') }}" required>
                        @error('correu_personal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="departament_id" class="form-label">Departament *</label>
                        <select class="form-select @error('departament_id') is-invalid @enderror" 
                            id="departament_id" name="departament_id" required>
                            <option value="">Selecciona un departament</option>
                            @foreach($departaments as $departament)
                                <option value="{{ $departament->id }}" {{ old('departament_id') == $departament->id ? 'selected' : '' }}>
                                    {{ $departament->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('departament_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="carrec" class="form-label">Càrrec *</label>
                    <input type="text" class="form-control @error('carrec') is-invalid @enderror" 
                        id="carrec" name="carrec" value="{{ old('carrec') }}" required>
                    @error('carrec')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="observacions" class="form-label">Observacions</label>
                    <textarea class="form-control @error('observacions') is-invalid @enderror" 
                        id="observacions" name="observacions" rows="3">{{ old('observacions') }}</textarea>
                    @error('observacions')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="checklist_template_id" class="form-label">Plantilla d'Onboarding</label>
                    <select class="form-select @error('checklist_template_id') is-invalid @enderror" 
                        id="checklist_template_id" name="checklist_template_id">
                        <option value="">Plantilla per defecte</option>
                        @foreach($checklistTemplates as $template)
                            <option value="{{ $template->id }}" {{ old('checklist_template_id') == $template->id ? 'selected' : '' }}>
                                {{ $template->nom }} {{ $template->departament ? '(' . $template->departament->nom . ')' : '(Global)' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Si no selecciones cap plantilla, s'utilitzarà la plantilla per defecte.</div>
                    @error('checklist_template_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> En guardar l'empleat, es crearà automàticament una checklist d'onboarding segons la plantilla seleccionada.
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Empleat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Validación del formulario en el lado del cliente
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validar campos requeridos
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Validar formato de email
            const emailField = document.getElementById('correu_personal');
            if (emailField.value && !isValidEmail(emailField.value)) {
                emailField.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
        
        // Validar email en tiempo real
        const emailField = document.getElementById('correu_personal');
        emailField.addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.classList.add('is-invalid');
                if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.classList.add('invalid-feedback');
                    feedback.textContent = 'El format del correu electrònic no és vàlid';
                    this.parentNode.appendChild(feedback);
                }
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    });
</script>
@endsection
