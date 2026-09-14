// assets/js/notificaciones.js
// Sistema de notificaciones - carga y muestra el panel

const NOTIF_URL_BASE = '/sistema_proyectos/modules/notificaciones/';
let notificacionesCache = null;
let notifRefreshInterval = null;
let panelAbierto = false;

const NOTIF_TEXTOS = {
    cargando: 'Cargando...',
    vacioTitulo: 'No tienes notificaciones',
    vacioSubtitulo: 'Todo está al día',
    error: 'Error al cargar notificaciones',
    vencidos: 'Vencidos',
    proximos: 'Próximos a vencer',
    modificados: 'Items modificados',
    agregados: 'Items agregados',
    diasAtraso: 'días de atraso',
    diasRestantes: 'días restantes',
    venceHoy: 'Vence hoy',
    agregadoPor: 'Agregado por',
    modificadoPor: 'Modificado por',
    en: 'en'
};

// ============================================
// CARGAR NOTIFICACIONES
// ============================================
function cargarNotificaciones(silencioso = false) {
    if (!silencioso && panelAbierto) {
        mostrarCargando();
    }
    
    fetch(NOTIF_URL_BASE + 'obtener.php', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            console.error('Error al obtener notificaciones:', data);
            return;
        }
        
        notificacionesCache = data.notificaciones;
        actualizarBadge(data.total);
        
        if (panelAbierto) {
            renderizarPanel(data.notificaciones);
        }
    })
    .catch(e => {
        console.error('Error fetch notificaciones:', e);
        if (panelAbierto && !silencioso) {
            mostrarError();
        }
    });
}

// ============================================
// BADGE
// ============================================
function actualizarBadge(total) {
    const badge = document.getElementById('badge-notificaciones');
    const btn = document.getElementById('btn-notificaciones');
    if (!badge || !btn) return;
    
    if (total > 0) {
        badge.textContent = total > 99 ? '99+' : total;
        badge.style.display = 'inline-block';
        btn.classList.add('con-notificaciones');
    } else {
        badge.style.display = 'none';
        btn.classList.remove('con-notificaciones');
    }
}

// ============================================
// ABRIR / CERRAR PANEL
// ============================================
function toggleNotificaciones(e) {
    if (e) e.stopPropagation();
    
    const panel = document.getElementById('panel-notificaciones');
    if (!panel) return;
    
    if (panel.style.display === 'none' || !panel.style.display) {
        panel.style.display = 'flex';
        panelAbierto = true;
        cargarNotificaciones();
    } else {
        panel.style.display = 'none';
        panelAbierto = false;
    }
}

function cerrarPanelNotificaciones() {
    const panel = document.getElementById('panel-notificaciones');
    if (panel) panel.style.display = 'none';
    panelAbierto = false;
}

// ============================================
// RENDERIZAR
// ============================================
function mostrarCargando() {
    const body = document.getElementById('panel-notificaciones-body');
    if (body) {
        body.innerHTML = '<div class="panel-loading"><span class="spinner"></span>' + NOTIF_TEXTOS.cargando + '</div>';
    }
}

function mostrarError() {
    const body = document.getElementById('panel-notificaciones-body');
    if (body) {
        body.innerHTML = '<div class="panel-empty"><div class="empty-icon">⚠️</div><p>' + NOTIF_TEXTOS.error + '</p></div>';
    }
}

function renderizarPanel(notif) {
    const body = document.getElementById('panel-notificaciones-body');
    if (!body) return;
    
    const total = (notif.vencidos?.length || 0) +
                  (notif.proximos?.length || 0) +
                  (notif.modificados?.length || 0) +
                  (notif.agregados?.length || 0);
    
    if (total === 0) {
        body.innerHTML = `
            <div class="panel-empty">
                <div class="empty-icon">✅</div>
                <p><strong>${NOTIF_TEXTOS.vacioTitulo}</strong></p>
                <p>${NOTIF_TEXTOS.vacioSubtitulo}</p>
            </div>`;
        return;
    }
    
    let html = '';
    
    // VENCIDOS
    if (notif.vencidos?.length > 0) {
        html += renderGrupo('vencidos', '🚨', NOTIF_TEXTOS.vencidos, notif.vencidos, renderItemVencido);
    }
    
    // PRÓXIMOS
    if (notif.proximos?.length > 0) {
        html += renderGrupo('proximos', '⏰', NOTIF_TEXTOS.proximos, notif.proximos, renderItemProximo);
    }
    
    // MODIFICADOS
    if (notif.modificados?.length > 0) {
        html += renderGrupo('modificados', '✎', NOTIF_TEXTOS.modificados, notif.modificados, renderItemModificado);
    }
    
    // AGREGADOS
    if (notif.agregados?.length > 0) {
        html += renderGrupo('agregados', '🆕', NOTIF_TEXTOS.agregados, notif.agregados, renderItemAgregado);
    }
    
    body.innerHTML = html;
}

function renderGrupo(clase, icono, titulo, items, renderFn) {
    return `
        <div class="notif-grupo ${clase}">
            <div class="notif-grupo-header">
                <span>${icono}</span>
                <span>${titulo}</span>
                <span class="grupo-count">${items.length}</span>
            </div>
            ${items.map(renderFn).join('')}
        </div>
    `;
}

function renderItemVencido(n) {
    return `
        <div class="notif-item" onclick="abrirProyecto(${n.proyecto_id})">
            <div class="notif-item-icon">🚨</div>
            <div class="notif-item-content">
                <div class="notif-item-titulo">${escapeHtml(n.nombre_item)}</div>
                <div class="notif-item-subtitulo">${escapeHtml(n.proyecto_nombre)}</div>
                <div class="notif-item-meta">
                    <span>📅 ${formatearFecha(n.fecha_requerida)}</span>
                    <span class="fecha-urgente">${n.dias_atraso} ${NOTIF_TEXTOS.diasAtraso}</span>
                </div>
            </div>
            <button type="button" class="btn-cerrar-notif" onclick="event.stopPropagation(); marcarLeida('item_vencido', ${n.id})" title="Marcar como leída">×</button>
        </div>
    `;
}

function renderItemProximo(n) {
    const dias = n.dias_restantes;
    const labelDias = dias == 0 ? NOTIF_TEXTOS.venceHoy : `${dias} ${NOTIF_TEXTOS.diasRestantes}`;
    return `
        <div class="notif-item" onclick="abrirProyecto(${n.proyecto_id})">
            <div class="notif-item-icon">⏰</div>
            <div class="notif-item-content">
                <div class="notif-item-titulo">${escapeHtml(n.nombre_item)}</div>
                <div class="notif-item-subtitulo">${escapeHtml(n.proyecto_nombre)}</div>
                <div class="notif-item-meta">
                    <span>📅 ${formatearFecha(n.fecha_requerida)}</span>
                    <span class="fecha-proxima">${labelDias}</span>
                </div>
            </div>
            <button type="button" class="btn-cerrar-notif" onclick="event.stopPropagation(); marcarLeida('item_proximo', ${n.id})" title="Marcar como leída">×</button>
        </div>
    `;
}

function renderItemModificado(n) {
    let cambio = '';
    if (n.estado_anterior && n.estado_nuevo) {
        cambio = `${escapeHtml(n.estado_anterior)} → ${escapeHtml(n.estado_nuevo)}`;
    } else if (n.cantidad_anterior != n.cantidad_nueva && n.cantidad_anterior != null) {
        cambio = `${n.cantidad_anterior} → ${n.cantidad_nueva}`;
    } else {
        cambio = n.comentario || 'Actualizado';
    }
    
    return `
        <div class="notif-item" onclick="abrirProyecto(${n.proyecto_id})">
            <div class="notif-item-icon">✎</div>
            <div class="notif-item-content">
                <div class="notif-item-titulo">${escapeHtml(n.nombre_item)}</div>
                <div class="notif-item-subtitulo">${escapeHtml(n.proyecto_nombre)}</div>
                <div class="notif-item-meta">
                    <span>${cambio}</span>
                    ${n.usuario_cambio ? `<span>${NOTIF_TEXTOS.modificadoPor} ${escapeHtml(n.usuario_cambio)}</span>` : ''}
                </div>
            </div>
            <button type="button" class="btn-cerrar-notif" onclick="event.stopPropagation(); marcarLeida('item_modificado', ${n.historial_id})" title="Marcar como leída">×</button>
        </div>
    `;
}

function renderItemAgregado(n) {
    return `
        <div class="notif-item" onclick="abrirProyecto(${n.proyecto_id})">
            <div class="notif-item-icon">🆕</div>
            <div class="notif-item-content">
                <div class="notif-item-titulo">${escapeHtml(n.nombre_item)} (${n.cantidad})</div>
                <div class="notif-item-subtitulo">${escapeHtml(n.proyecto_nombre)}</div>
                <div class="notif-item-meta">
                    <span>📅 ${formatearFecha(n.fecha_requerida)}</span>
                    ${n.creador ? `<span>${NOTIF_TEXTOS.agregadoPor} ${escapeHtml(n.creador)}</span>` : ''}
                </div>
            </div>
            <button type="button" class="btn-cerrar-notif" onclick="event.stopPropagation(); marcarLeida('item_agregado', ${n.id})" title="Marcar como leída">×</button>
        </div>
    `;
}

// ============================================
// ACCIONES
// ============================================
function abrirProyecto(proyectoId) {
    window.location.href = '/sistema_proyectos/modules/proyectos/ver.php?id=' + proyectoId;
}

function marcarLeida(tipo, referenciaId) {
    const fd = new FormData();
    fd.append('tipo', tipo);
    fd.append('referencia_id', referenciaId);
    
    fetch(NOTIF_URL_BASE + 'marcar_leida.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones(true);
        }
    })
    .catch(e => console.error(e));
}

function marcarTodasLeidas() {
    const fd = new FormData();
    fd.append('todas', '1');
    
    fetch(NOTIF_URL_BASE + 'marcar_leida.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            actualizarBadge(0);
            notificacionesCache = null;
            cerrarPanelNotificaciones();
        }
    })
    .catch(e => console.error(e));
}

// ============================================
// UTILIDADES
// ============================================
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    const partes = fecha.split('-');
    if (partes.length !== 3) return fecha;
    return partes[2] + '/' + partes[1] + '/' + partes[0];
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Cargar al inicio
    cargarNotificaciones(true);
    
    // Auto-refresh cada 60 segundos
    notifRefreshInterval = setInterval(() => cargarNotificaciones(true), 60000);
    
    // Cerrar panel al hacer click fuera
    document.addEventListener('click', function(e) {
        const panel = document.getElementById('panel-notificaciones');
        const btn = document.getElementById('btn-notificaciones');
        if (!panel || panel.style.display === 'none') return;
        if (e.target.closest('.panel-notificaciones') || e.target.closest('#btn-notificaciones')) {
            return;
        }
        cerrarPanelNotificaciones();
    });
    
    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarPanelNotificaciones();
        }
    });
});