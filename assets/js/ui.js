// assets/js/ui.js
// Toasts, modales de confirmación y utilidades de UI

// ============================================
// SISTEMA DE TOASTS
// ============================================
const Toast = (function() {
    let container = null;
    
    function getContainer() {
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }
    
    const iconos = {
        success: '✓',
        error:   '✕',
        warning: '!',
        info:    'i'
    };
    
    function show(mensaje, tipo = 'info', opciones = {}) {
        const duracion = opciones.duracion || 4000;
        const titulo = opciones.titulo || '';
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${tipo}`;
        
        const iconoHtml = opciones.sinIcono 
            ? '' 
            : `<div class="toast-icono">${iconos[tipo] || 'i'}</div>`;
        
        const tituloHtml = titulo 
            ? `<div class="toast-titulo">${escapeHtml(titulo)}</div>` 
            : '';
        
        toast.innerHTML = `
            ${iconoHtml}
            <div class="toast-contenido">
                ${tituloHtml}
                <div class="toast-mensaje">${escapeHtml(mensaje)}</div>
            </div>
            <button type="button" class="toast-cerrar" aria-label="Cerrar">×</button>
        `;
        
        // Progress bar
        if (duracion > 0) {
            const progress = document.createElement('div');
            progress.className = 'toast-progress';
            progress.style.animationDuration = duracion + 'ms';
            progress.style.color = tipo === 'success' ? '#27ae60'
                                 : tipo === 'error' ? '#e74c3c'
                                 : tipo === 'warning' ? '#f39c12'
                                 : '#3498db';
            toast.style.position = 'relative';
            toast.appendChild(progress);
        }
        
        // Event listeners
        toast.querySelector('.toast-cerrar').addEventListener('click', () => remove(toast));
        
        getContainer().appendChild(toast);
        
        if (duracion > 0) {
            setTimeout(() => remove(toast), duracion);
        }
        
        return toast;
    }
    
    function remove(toast) {
        if (!toast || !toast.parentNode) return;
        toast.classList.add('toast-leaving');
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 250);
    }
    
    return {
        success: (msg, opts) => show(msg, 'success', opts),
        error:   (msg, opts) => show(msg, 'error', opts),
        warning: (msg, opts) => show(msg, 'warning', opts),
        info:    (msg, opts) => show(msg, 'info', opts),
        show:    show
    };
})();

// ============================================
// MODAL DE CONFIRMACIÓN
// ============================================
const Confirm = (function() {
    let activeOverlay = null;
    
    function show(opciones) {
        return new Promise((resolve) => {
            const {
                titulo = 'Confirmar',
                mensaje = '¿Estás seguro?',
                textoConfirmar = 'Confirmar',
                textoCancelar = 'Cancelar',
                tipo = 'question'  // question | warning | danger | info
            } = opciones;
            
            // Si ya hay un modal abierto, lo cerramos
            if (activeOverlay) {
                activeOverlay.remove();
            }
            
            const iconos = {
                question: '?',
                warning:  '!',
                danger:   '⚠',
                info:     'i'
            };
            
            const overlay = document.createElement('div');
            overlay.className = 'confirm-overlay';
            
            const dangerClass = tipo === 'danger' ? 'confirm-danger' : '';
            const warningClass = tipo === 'warning' ? 'confirm-warning' : '';
            
            overlay.innerHTML = `
                <div class="confirm-box" role="dialog" aria-modal="true">
                    <div class="confirm-header">
                        <div class="confirm-icono ${tipo}">${iconos[tipo]}</div>
                        <h3 class="confirm-titulo">${escapeHtml(titulo)}</h3>
                    </div>
                    <div class="confirm-body">
                        <p>${escapeHtml(mensaje).replace(/\n/g, '<br>')}</p>
                    </div>
                    <div class="confirm-footer">
                        <button type="button" class="confirm-btn confirm-btn-cancelar" data-action="cancelar">
                            ${escapeHtml(textoCancelar)}
                        </button>
                        <button type="button" class="confirm-btn confirm-btn-confirmar ${dangerClass} ${warningClass}" data-action="confirmar">
                            ${escapeHtml(textoConfirmar)}
                        </button>
                    </div>
                </div>
            `;
            
            // Event listeners
            overlay.querySelector('[data-action="cancelar"]').addEventListener('click', () => {
                cleanup();
                resolve(false);
            });
            
            overlay.querySelector('[data-action="confirmar"]').addEventListener('click', () => {
                cleanup();
                resolve(true);
            });
            
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    cleanup();
                    resolve(false);
                }
            });
            
            // ESC para cancelar
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    cleanup();
                    resolve(false);
                }
            };
            document.addEventListener('keydown', escHandler);
            
            function cleanup() {
                document.removeEventListener('keydown', escHandler);
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                activeOverlay = null;
            }
            
            document.body.appendChild(overlay);
            activeOverlay = overlay;
            
            // Focus en el botón de confirmar
            setTimeout(() => {
                const btn = overlay.querySelector('[data-action="confirmar"]');
                if (btn) btn.focus();
            }, 50);
        });
    }
    
    return { show };
})();

// ============================================
// DROPDOWNS GENÉRICOS
// Cierra cualquier otro dropdown abierto antes de abrir el nuevo.
// ============================================
function toggleDropdown(e, menuId) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    const container = e ? e.target.closest('.dropdown-acciones') : null;
    if (!container) return;
    
    const estabaAbierto = container.classList.contains('abierto');
    
    // Cerrar todos los dropdowns
    document.querySelectorAll('.dropdown-acciones.abierto').forEach(el => {
        el.classList.remove('abierto');
    });
    
    // Si estaba cerrado, abrir
    if (!estabaAbierto) {
        container.classList.add('abierto');
    }
}

// Cerrar al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-acciones')) {
        document.querySelectorAll('.dropdown-acciones.abierto').forEach(el => {
            el.classList.remove('abierto');
        });
    }
});

// Cerrar con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.dropdown-acciones.abierto').forEach(el => {
            el.classList.remove('abierto');
        });
    }
});

// ============================================
// HELPERS
// ============================================
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// Exponer globalmente
window.Toast = Toast;
window.Confirm = Confirm;