/**
 * Funciones JavaScript para el sistema de onboarding
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar toasts de Bootstrap
    initToasts();
    
    // Inicializar tooltips de Bootstrap
    initTooltips();
    
    // Inicializar validaciones de formularios
    initFormValidations();
    
    // Inicializar filtros de tablas
    initTableFilters();
});

/**
 * Inicializa los toasts de Bootstrap
 */
function initToasts() {
    const toastElList = document.querySelectorAll('.toast');
    toastElList.forEach(toastEl => {
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });
        toast.show();
    });
}

/**
 * Inicializa los tooltips de Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipTriggerEl => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Inicializa las validaciones de formularios
 */
function initFormValidations() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Validación específica para NIF
    const nifInputs = document.querySelectorAll('input[name="nif"]');
    nifInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateNIF(this);
        });
    });
    
    // Validación específica para email
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateEmail(this);
        });
    });
}

/**
 * Valida un NIF español
 * @param {HTMLInputElement} input - El campo de entrada del NIF
 */
function validateNIF(input) {
    const nif = input.value.trim().toUpperCase();
    const nifRegex = /^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/;
    
    if (nif && !nifRegex.test(nif)) {
        input.setCustomValidity('El format del NIF no és vàlid');
        input.classList.add('is-invalid');
        
        // Añadir mensaje de error si no existe
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
            const feedback = document.createElement('div');
            feedback.classList.add('invalid-feedback');
            feedback.textContent = 'El format del NIF no és vàlid';
            input.parentNode.appendChild(feedback);
        }
    } else {
        input.setCustomValidity('');
        input.classList.remove('is-invalid');
    }
}

/**
 * Valida un correo electrónico
 * @param {HTMLInputElement} input - El campo de entrada del email
 */
function validateEmail(input) {
    const email = input.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        input.setCustomValidity('El format del correu electrònic no és vàlid');
        input.classList.add('is-invalid');
        
        // Añadir mensaje de error si no existe
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
            const feedback = document.createElement('div');
            feedback.classList.add('invalid-feedback');
            feedback.textContent = 'El format del correu electrònic no és vàlid';
            input.parentNode.appendChild(feedback);
        }
    } else {
        input.setCustomValidity('');
        input.classList.remove('is-invalid');
    }
}

/**
 * Inicializa los filtros de tablas
 */
function initTableFilters() {
    const filterInputs = document.querySelectorAll('.table-filter');
    
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const tableId = this.dataset.tableTarget;
            const table = document.getElementById(tableId);
            
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
}

/**
 * Muestra una notificación toast
 * @param {string} message - El mensaje a mostrar
 * @param {string} type - El tipo de notificación (success, error, warning, info)
 */
function showToast(message, type = 'success') {
    // Crear contenedor de toasts si no existe
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    // Crear el toast
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type}`;
    toast.id = toastId;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    // Contenido del toast
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Añadir el toast al contenedor
    toastContainer.appendChild(toast);
    
    // Inicializar y mostrar el toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    bsToast.show();
    
    // Eliminar el toast del DOM cuando se oculte
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

/**
 * Confirma una acción con un modal
 * @param {string} title - El título del modal
 * @param {string} message - El mensaje a mostrar
 * @param {Function} callback - La función a ejecutar si se confirma
 */
function confirmAction(title, message, callback) {
    // Crear modal de confirmación
    const modalId = 'confirmModal-' + Date.now();
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = modalId;
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-hidden', 'true');
    
    // Contenido del modal
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·lar</button>
                    <button type="button" class="btn btn-primary" id="${modalId}-confirm">Confirmar</button>
                </div>
            </div>
        </div>
    `;
    
    // Añadir el modal al body
    document.body.appendChild(modal);
    
    // Inicializar el modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Evento de confirmación
    document.getElementById(`${modalId}-confirm`).addEventListener('click', function() {
        bsModal.hide();
        callback();
    });
    
    // Eliminar el modal del DOM cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
}
