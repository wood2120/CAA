// Funciones JavaScript comunes para el Sistema de Gestión Empresarial

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers de Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-ocultar alertas después de 5 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Funciones de utilidad
const Utils = {
    // Formatear números como moneda
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('es-CR', {
            style: 'currency',
            currency: 'CRC',
            minimumFractionDigits: 2
        }).format(amount);
    },

    // Formatear fechas
    formatDate: function(dateString, locale = 'es-CR') {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString(locale);
    },

    validateCedula: function(cedula) {
        cedula = cedula.replace(/[\s-]/g, '');
        
        return /^\d{9}$/.test(cedula);
    },

    confirm: function(message, callback) {
        if (confirm(message)) {
            if (typeof callback === 'function') {
                callback();
            }
            return true;
        }
        return false;
    },

    showAlert: function(message, type = 'info', container = 'body') {
        const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const alertContainer = document.querySelector(container);
        if (alertContainer) {
            alertContainer.insertAdjacentHTML('afterbegin', alertHTML);
        }
    },

    validateForm: function(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });

        return isValid;
    },

    clearFormValidation: function(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const fields = form.querySelectorAll('.is-invalid, .is-valid');
        fields.forEach(function(field) {
            field.classList.remove('is-invalid', 'is-valid');
        });
    },

    formatPhone: function(phone) {
        phone = phone.replace(/\D/g, '');
        
        if (phone.length === 8) {
            return phone.replace(/(\d{4})(\d{4})/, '$1-$2');
        } else if (phone.length === 10 && phone.startsWith('506')) {
            return phone.replace(/(\d{3})(\d{4})(\d{3})/, '+$1 $2-$3');
        }
        
        return phone;
    },

    exportTableToCSV: function(tableId, filename = 'export.csv') {
        const table = document.getElementById(tableId);
        if (!table) return;

        let csv = '';
        const rows = table.querySelectorAll('tr');

        rows.forEach(function(row) {
            const cols = row.querySelectorAll('td, th');
            const rowData = [];
            
            cols.forEach(function(col) {
                let data = col.textContent.trim();
                data = data.replace(/"/g, '""');
                if (data.includes(',')) {
                    data = `"${data}"`;
                }
                rowData.push(data);
            });
            
            csv += rowData.join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    },

    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

const SistemaKris = {
    confirmDelete: function(message = '¿Está seguro de que desea eliminar este elemento?') {
        return Utils.confirm(message);
    },

    setupCedulaValidation: function(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('blur', function() {
            const cedula = this.value.trim();
            if (cedula && !Utils.validateCedula(cedula)) {
                this.classList.add('is-invalid');
                this.setCustomValidity('Cédula debe tener exactamente 9 dígitos');
            } else {
                this.classList.remove('is-invalid');
                this.setCustomValidity('');
            }
        });
    },

    setupLiveSearch: function(inputId, targetId, searchCallback) {
        const searchInput = document.getElementById(inputId);
        if (!searchInput) return;

        const debouncedSearch = Utils.debounce(function(event) {
            const query = event.target.value.trim();
            if (typeof searchCallback === 'function') {
                searchCallback(query);
            }
        }, 300);

        searchInput.addEventListener('input', debouncedSearch);
    },

    setupPagination: function(containerId, itemsPerPage = 10) {
       
    },

    initDataTable: function(tableId, options = {}) {
        if (typeof DataTable !== 'undefined') {
            const defaultOptions = {
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
            };

            const finalOptions = Object.assign(defaultOptions, options);
            return new DataTable('#' + tableId, finalOptions);
        }
    }
};

window.Utils = Utils;
window.SistemaKris = SistemaKris;
