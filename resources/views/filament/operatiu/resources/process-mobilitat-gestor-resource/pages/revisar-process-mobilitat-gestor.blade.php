<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Informació del Procés -->
        <x-filament::section>
            <x-slot name="heading">
                📋 Informació del Procés
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <strong>Empleat/da:</strong> {{ $this->record->empleat->nom_complet }}
                </div>
                <div>
                    <strong>Departament Actual:</strong> {{ $this->record->departamentActual->nom }}
                </div>
                <div>
                    <strong>Nou Departament:</strong> {{ $this->record->departamentNou->nom }}
                </div>
                <div>
                    <strong>Sol·licitat per:</strong> {{ $this->record->usuariSolicitant->name }}
                </div>
                <div class="md:col-span-2">
                    <strong>Justificació:</strong><br>
                    {{ $this->record->justificacio }}
                </div>
            </div>
        </x-filament::section>

        <!-- Sistemes d'Accés -->
        <x-filament::section>
            <x-slot name="heading">
                🔐 Sistemes d'Accés
            </x-slot>
            
            @if($this->record->estat === 'pendent_dept_actual')
                <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-amber-800">
                        <strong>Instruccions:</strong> Revisa els sistemes actuals de l'empleat/da i marca aquells que s'han d'eliminar amb el canvi de departament.
                    </p>
                </div>
            @elseif($this->record->estat === 'pendent_dept_nou')
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-blue-800">
                        <strong>Instruccions:</strong> Revisa els sistemes de l'empleat/da. Pots modificar nivells d'accés, afegir nous sistemes o revertir eliminacions marcades pel departament anterior.
                    </p>
                </div>
            @endif

            <div class="space-y-4">
                @forelse($this->getSistemesData() as $sistema)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">
                                    {{ $sistema['sistema']['nom'] }}
                                </h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    <strong>Nivell Actual:</strong> {{ $sistema['nivell_acces_original']['nom'] ?? 'No definit' }}
                                </p>
                                
                                @if($this->record->estat === 'pendent_dept_actual')
                                    <div class="mt-3">
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50"
                                                   wire:model="sistemes.{{ $loop->index }}.eliminar"
                                                   {{ $sistema['accio_dept_actual'] === 'eliminar' ? 'checked' : '' }}>
                                            <span class="ml-2 text-sm text-red-600">Eliminar accés</span>
                                        </label>
                                    </div>
                                @elseif($this->record->estat === 'pendent_dept_nou')
                                    <div class="mt-3 space-y-2">
                                        @if($sistema['accio_dept_actual'] === 'eliminar')
                                            <div class="text-sm text-red-600 bg-red-50 p-2 rounded">
                                                ⚠️ Marcat per eliminar pel departament anterior
                                            </div>
                                        @endif
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Acció:</label>
                                            <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                <option value="mantenir" {{ $sistema['accio_dept_nou'] === 'mantenir' ? 'selected' : '' }}>Mantenir</option>
                                                <option value="modificar" {{ $sistema['accio_dept_nou'] === 'modificar' ? 'selected' : '' }}>Modificar nivell</option>
                                                <option value="eliminar" {{ $sistema['accio_dept_nou'] === 'eliminar' ? 'selected' : '' }}>Eliminar</option>
                                                @if($sistema['accio_dept_actual'] === 'eliminar')
                                                    <option value="recuperar" {{ $sistema['accio_dept_nou'] === 'recuperar' ? 'selected' : '' }}>Recuperar accés</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="ml-4">
                                @if($sistema['accio_dept_actual'] === 'eliminar')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Per eliminar
                                    </span>
                                @elseif($sistema['accio_dept_nou'] === 'modificar')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Per modificar
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Mantenir
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <div class="text-gray-500 mb-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No hi ha sistemes actuals</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Aquest empleat/da no té sistemes d'accés assignats actualment.<br>
                                Pots afegir nous sistemes utilitzant el botó de sota.
                            </p>
                        </div>
                        
                        @if($this->record->estat === 'pendent_dept_nou')
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <p class="text-sm text-blue-800">
                                    <strong>Nota:</strong> Com que no hi ha sistemes actuals, només podràs afegir nous sistemes d'accés.
                                </p>
                            </div>
                        @endif
                    </div>
                @endforelse
            </div>
            
            @if($this->record->estat === 'pendent_dept_nou')
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="font-medium text-gray-900 mb-4">Afegir Nous Sistemes</h4>
                    <div class="space-y-3">
                        <button type="button" 
                                onclick="afegirNouSistema()"
                                class="fi-btn fi-btn-size-md fi-color-primary fi-btn-color-primary fi-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-outlined ring-1 bg-white text-gray-950 hover:bg-gray-50 focus-visible:ring-primary-600 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:focus-visible:ring-primary-500 ring-gray-300 dark:ring-gray-600 gap-1.5 px-3 py-2 text-sm inline-flex">
                            <svg class="fi-btn-icon transition duration-75 h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            <span class="fi-btn-label">Afegir Sistema</span>
                        </button>
                    </div>
                </div>
            @endif
        </x-filament::section>

        <!-- Estat del Procés -->
        <x-filament::section>
            <x-slot name="heading">
                📊 Estat del Procés
            </x-slot>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Estat Actual:</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @if($this->record->estat === 'pendent_dept_actual') bg-amber-100 text-amber-800
                        @elseif($this->record->estat === 'pendent_dept_nou') bg-blue-100 text-blue-800
                        @elseif($this->record->estat === 'validant') bg-purple-100 text-purple-800
                        @elseif($this->record->estat === 'aprovada') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800 @endif">
                        @switch($this->record->estat)
                            @case('pendent_dept_actual')
                                Pendent Departament Actual
                                @break
                            @case('pendent_dept_nou')
                                Pendent Departament Nou
                                @break
                            @case('validant')
                                En Validació
                                @break
                            @case('aprovada')
                                Aprovada
                                @break
                            @case('finalitzada')
                                Finalitzada
                                @break
                            @default
                                {{ $this->record->estat }}
                        @endswitch
                    </span>
                </div>
                
                <div class="text-sm text-gray-600">
                    <strong>Data Sol·licitud:</strong> {{ $this->record->created_at->format('d/m/Y H:i') }}
                </div>
                
                @if($this->record->data_finalitzacio)
                    <div class="text-sm text-gray-600">
                        <strong>Data Finalització:</strong> {{ $this->record->data_finalitzacio->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

<script>
// Dades dels sistemes i els seus nivells d'accés
const sistemesNivells = {!! $this->getSistemesNivellsJson() !!};

function afegirNouSistema() {
    // Crear un nou element per afegir sistema
    const nouSistemaDiv = document.createElement('div');
    nouSistemaDiv.className = 'bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4';
    
    // Generar un ID únic per aquest sistema
    const uniqueId = 'sistema_' + Date.now();
    
    nouSistemaDiv.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sistema</label>
                <select class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" name="nou_sistema[]" onchange="actualitzarNivells('${uniqueId}', this.value)">
                    <option value="">Selecciona un sistema...</option>
                    @foreach($this->getSistemesDisponibles() as $sistema)
                        <option value="{{ $sistema->id }}">{{ $sistema->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nivell d'Accés</label>
                <select class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" name="nou_nivell[]" id="nivells_${uniqueId}" disabled>
                    <option value="">Primer selecciona un sistema...</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" onclick="eliminarNouSistema(this)" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Eliminar
                </button>
            </div>
        </div>
    `;
    
    // Afegir el nou element abans del botó "Afegir Sistema"
    const afegirButton = document.querySelector('button[onclick="afegirNouSistema()"]');
    const container = afegirButton.parentElement.parentElement;
    container.insertBefore(nouSistemaDiv, afegirButton.parentElement);
}

function actualitzarNivells(uniqueId, sistemaId) {
    const nivellSelect = document.getElementById('nivells_' + uniqueId);
    
    // Netejar opcions existents
    nivellSelect.innerHTML = '';
    
    if (!sistemaId) {
        nivellSelect.innerHTML = '<option value="">Primer selecciona un sistema...</option>';
        nivellSelect.disabled = true;
        return;
    }
    
    // Afegir opció per defecte
    nivellSelect.innerHTML = '<option value="">Selecciona nivell...</option>';
    
    // Afegir nivells del sistema seleccionat
    if (sistemesNivells[sistemaId]) {
        sistemesNivells[sistemaId].forEach(function(nivell) {
            const option = document.createElement('option');
            option.value = nivell.id;
            option.textContent = nivell.nom;
            nivellSelect.appendChild(option);
        });
        nivellSelect.disabled = false;
    } else {
        nivellSelect.innerHTML = '<option value="">No hi ha nivells disponibles</option>';
        nivellSelect.disabled = true;
    }
}

function eliminarNouSistema(button) {
    button.closest('div.bg-blue-50').remove();
}

function marcarEliminacio(sistemaId, button) {
    const row = button.closest('tr');
    const isMarked = button.classList.contains('bg-red-600');
    
    if (isMarked) {
        // Desmarcar
        button.classList.remove('bg-red-600', 'text-white');
        button.classList.add('bg-red-100', 'text-red-800');
        button.textContent = 'Eliminar Accés';
        row.classList.remove('bg-red-50');
    } else {
        // Marcar per eliminació
        button.classList.remove('bg-red-100', 'text-red-800');
        button.classList.add('bg-red-600', 'text-white');
        button.textContent = 'Marcat per Eliminar';
        row.classList.add('bg-red-50');
    }
    
    // Actualitzar camp ocult
    const hiddenInput = document.querySelector(`input[name="eliminar_sistema[${sistemaId}]"]`);
    if (hiddenInput) {
        hiddenInput.value = isMarked ? '0' : '1';
    }
}
</script>
