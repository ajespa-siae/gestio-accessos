/**
 * EXEMPLE D'IMPLEMENTACIÓ FRONTEND PER SISTEMA HÍBRID
 * 
 * Aquest fitxer mostra com integrar la nova funcionalitat híbrida
 * mantenint total compatibilitat amb el sistema actual.
 */

class SolicitudHibridaManager {
    constructor() {
        this.sistemaSeleccionat = null;
        this.configuracioSistema = null;
        this.elementsSeleccionats = [];
    }

    /**
     * Inicialitzar el gestor quan es carrega la pàgina
     */
    init() {
        this.setupEventListeners();
        this.carregarSistemes();
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Event per quan es selecciona un sistema
        document.addEventListener('change', (e) => {
            if (e.target.matches('#sistema_select')) {
                this.onSistemaSelected(e.target.value);
            }
        });

        // Event per enviar el formulari
        document.addEventListener('submit', (e) => {
            if (e.target.matches('#solicitud_form')) {
                e.preventDefault();
                this.enviarSolicitud();
            }
        });
    }

    /**
     * Carregar llista de sistemes amb informació de tipus
     */
    async carregarSistemes() {
        try {
            const response = await fetch('/api/sistemes', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Error carregant sistemes');

            const data = await response.json();
            this.renderSistemesSelect(data.data);

        } catch (error) {
            console.error('Error:', error);
            this.showError('Error carregant sistemes');
        }
    }

    /**
     * Renderitzar select de sistemes
     */
    renderSistemesSelect(sistemes) {
        const select = document.getElementById('sistema_select');
        if (!select) return;

        select.innerHTML = '<option value="">Selecciona un sistema...</option>';
        
        sistemes.forEach(sistema => {
            const option = document.createElement('option');
            option.value = sistema.id;
            option.textContent = `${sistema.nom} ${sistema.tipus_formulari === 'mixt' ? '(Híbrid)' : '(Simple)'}`;
            option.dataset.tipus = sistema.tipus_formulari;
            select.appendChild(option);
        });
    }

    /**
     * Quan es selecciona un sistema
     */
    async onSistemaSelected(sistemaId) {
        if (!sistemaId) {
            this.netejFormulariPermisos();
            return;
        }

        try {
            const response = await fetch(`/api/sistemes/${sistemaId}/details`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Error obtenint detalls del sistema');

            const data = await response.json();
            this.configuracioSistema = data.data;
            this.renderFormulariPermisos();

        } catch (error) {
            console.error('Error:', error);
            this.showError('Error carregant configuració del sistema');
        }
    }

    /**
     * Renderitzar formulari de permisos (simple, híbrid o mixt)
     */
    renderFormulariPermisos() {
        const container = document.getElementById('permisos-container');
        if (!container) return;

        const { te_elements_complexos, nivells_simples, elements_extra } = this.configuracioSistema;

        let html = '<div class="sistema-permisos">';

        // Mostrar nivells simples si existeixen
        if (nivells_simples && nivells_simples.length > 0) {
            html += this.renderNivellsSimples(nivells_simples);
        }

        // Mostrar elements extra si existeixen
        if (te_elements_complexos && elements_extra && elements_extra.length > 0) {
            html += this.renderElementsExtra(elements_extra);
        }

        html += '</div>';
        container.innerHTML = html;

        // Afegir event listeners als nous elements
        this.setupPermisosEventListeners();
    }

    /**
     * Renderitzar nivells simples (funcionalitat actual)
     */
    renderNivellsSimples(nivells) {
        let html = '<div class="nivells-simples">';
        html += '<h4>📋 Perfiles Base</h4>';
        html += '<div class="form-group">';

        nivells.forEach(nivell => {
            html += `
                <label class="form-check">
                    <input type="checkbox" 
                           name="nivells_simples[]" 
                           value="${nivell.id}"
                           class="form-check-input">
                    <span class="form-check-label">
                        <strong>${nivell.nom}</strong>
                        ${nivell.descripcio ? `<br><small class="text-muted">${nivell.descripcio}</small>` : ''}
                    </span>
                </label>
            `;
        });

        html += '</div></div>';
        return html;
    }

    /**
     * Renderitzar elements extra (nova funcionalitat)
     */
    renderElementsExtra(elements) {
        let html = '<div class="elements-extra">';
        html += '<h4>⚙️ Configuració Avançada</h4>';

        elements.forEach(element => {
            html += `<div class="element-extra-item" data-element-id="${element.id}">`;
            
            // Checkbox principal
            html += `
                <label class="form-check element-header">
                    <input type="checkbox" 
                           name="elements_extra[${element.id}][selected]" 
                           value="1"
                           class="form-check-input element-checkbox">
                    <span class="form-check-label">
                        <strong>${element.nom}</strong>
                        ${element.descripcio ? `<br><small class="text-muted">${element.descripcio}</small>` : ''}
                    </span>
                </label>
            `;

            // Opcions addicionals (es mostren quan es selecciona)
            html += '<div class="element-options" style="display: none; margin-left: 2rem; margin-top: 0.5rem;">';

            // Select d'opcions si n'hi ha
            if (element.opcions_disponibles && element.opcions_disponibles.length > 0) {
                html += `
                    <div class="form-group">
                        <label for="element_${element.id}_opcio">Opció:</label>
                        <select name="elements_extra[${element.id}][opcio]" 
                                id="element_${element.id}_opcio"
                                class="form-control">
                            <option value="">Selecciona una opció...</option>
                `;
                
                element.opcions_disponibles.forEach(opcio => {
                    html += `<option value="${opcio}">${opcio}</option>`;
                });
                
                html += '</select></div>';
            }

            // Textarea per text lliure si està permès
            if (element.permet_text_lliure) {
                html += `
                    <div class="form-group">
                        <label for="element_${element.id}_text">Especifica:</label>
                        <textarea name="elements_extra[${element.id}][text]" 
                                  id="element_${element.id}_text"
                                  class="form-control" 
                                  rows="3"
                                  placeholder="Detalla els requisits específics..."></textarea>
                    </div>
                `;
            }

            html += '</div></div>';
        });

        html += '</div>';
        return html;
    }

    /**
     * Configurar event listeners per elements de permisos
     */
    setupPermisosEventListeners() {
        // Mostrar/amagar opcions quan es selecciona un element extra
        document.querySelectorAll('.element-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const elementItem = e.target.closest('.element-extra-item');
                const options = elementItem.querySelector('.element-options');
                
                if (e.target.checked) {
                    options.style.display = 'block';
                } else {
                    options.style.display = 'none';
                    // Netejar valors quan es desmarca
                    options.querySelectorAll('input, select, textarea').forEach(input => {
                        input.value = '';
                    });
                }
            });
        });
    }

    /**
     * Enviar sol·licitud (simple o híbrida)
     */
    async enviarSolicitud() {
        try {
            const formData = this.recopilarDadesFormulari();
            
            const response = await fetch('/api/solicituds', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error creant sol·licitud');
            }

            this.showSuccess('Sol·licitud creada correctament!');
            this.resetFormulari();

        } catch (error) {
            console.error('Error:', error);
            this.showError(error.message);
        }
    }

    /**
     * Recopilar dades del formulari
     */
    recopilarDadesFormulari() {
        const form = document.getElementById('solicitud_form');
        const formData = new FormData(form);
        
        const data = {
            empleat_destinatari_id: formData.get('empleat_destinatari_id'),
            justificacio: formData.get('justificacio'),
            sistemes_simples: [],
            elements_extra: []
        };

        // Recopilar nivells simples seleccionats
        const nivellsSeleccionats = formData.getAll('nivells_simples[]');
        if (nivellsSeleccionats.length > 0) {
            nivellsSeleccionats.forEach(nivellId => {
                data.sistemes_simples.push({
                    sistema_id: this.configuracioSistema.sistema.id,
                    nivell_acces_id: parseInt(nivellId)
                });
            });
        }

        // Recopilar elements extra seleccionats
        document.querySelectorAll('.element-checkbox:checked').forEach(checkbox => {
            const elementId = checkbox.closest('.element-extra-item').dataset.elementId;
            const elementOptions = checkbox.closest('.element-extra-item').querySelector('.element-options');
            
            const opcioSelect = elementOptions.querySelector(`select[name="elements_extra[${elementId}][opcio]"]`);
            const textArea = elementOptions.querySelector(`textarea[name="elements_extra[${elementId}][text]"]`);
            
            data.elements_extra.push({
                element_extra_id: parseInt(elementId),
                opcio_seleccionada: opcioSelect ? opcioSelect.value : null,
                valor_text_lliure: textArea ? textArea.value : null
            });
        });

        return data;
    }

    /**
     * Mètodes auxiliars
     */
    netejFormulariPermisos() {
        const container = document.getElementById('permisos-container');
        if (container) container.innerHTML = '';
    }

    resetFormulari() {
        document.getElementById('solicitud_form').reset();
        this.netejFormulariPermisos();
        this.sistemaSeleccionat = null;
        this.configuracioSistema = null;
    }

    getAuthToken() {
        // Implementar segons el sistema d'autenticació utilitzat
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    showSuccess(message) {
        // Implementar notificació d'èxit
        alert('✅ ' + message);
    }

    showError(message) {
        // Implementar notificació d'error
        alert('❌ ' + message);
    }
}

// Inicialitzar quan es carrega la pàgina
document.addEventListener('DOMContentLoaded', () => {
    const manager = new SolicitudHibridaManager();
    manager.init();
});

// Exportar per ús en altres fitxers
window.SolicitudHibridaManager = SolicitudHibridaManager;
