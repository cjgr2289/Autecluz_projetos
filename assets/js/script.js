// assets/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Cambio de idioma
    const idiomaSelect = document.getElementById('cambiar_idioma');
    if (idiomaSelect) {
        idiomaSelect.addEventListener('change', function() {
            cambiarIdioma(this.value);
        });
    }
    
    // Confirmar eliminaciones
    const deleteLinks = document.querySelectorAll('.btn-danger');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento?')) {
                e.preventDefault();
            }
        });
    });
    
    // Validaciones de formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos requeridos.');
            }
        });
    });
});

// Función para cambiar idioma
function cambiarIdioma(idioma) {
    fetch('../../modules/usuarios/cambiar_idioma.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'idioma=' + encodeURIComponent(idioma)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Función para formatear fechas
function formatearFecha(fecha) {
    if (!fecha) return '-';
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    return new Date(fecha).toLocaleDateString('es-ES', options);
}

// Función para mostrar mensajes de éxito/error
function mostrarMensaje(mensaje, tipo = 'success') {
    const container = document.querySelector('.container');
    if (!container) return;
    
    const div = document.createElement('div');
    div.className = tipo === 'success' ? 'success-message' : 'error-message';
    div.textContent = mensaje;
    div.style.marginTop = '1rem';
    
    // Insertar al inicio del container
    container.insertBefore(div, container.firstChild);
    
    // Auto desaparecer después de 5 segundos
    setTimeout(() => {
        div.remove();
    }, 5000);
}