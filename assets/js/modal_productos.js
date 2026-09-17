// assets/js/modal_productos.js
// Sistema de modal para agregar productos a un proyecto

// Usar la URL base global definida en header.php
const MODAL_BASE_URL = window.BASE_URL || '/';

let itemsPendientes = [];
let productosCache = [];
let debounceTimer = null;

// ============ ABRIR / CERRAR MODAL ============
function abrirModalProducto() {
    document.getElementById('modal-producto').style.display = 'flex';
    cargarCategorias();
    buscarProductos();
}

async function cerrarModalProducto() {
    if (itemsPendientes.length > 0) {
        const idioma = document.documentElement.lang || 'es';
        const ok = await Confirm.show({
            titulo: idioma === 'pt' ? 'Itens pendentes' : 'Items pendientes',
            mensaje: idioma === 'pt'
                ? 'Há itens pendentes sem salvar. Deseja fechar mesmo assim? Eles serão perdidos.'
                : 'Hay items pendientes sin guardar. ¿Desea cerrar de todas formas? Se perderán.',
            textoConfirmar: idioma === 'pt' ? 'Fechar' : 'Cerrar',
            textoCancelar: idioma === 'pt' ? 'Cancelar' : 'Cancelar',
            tipo: 'warning'
        });
        
        if (!ok) return;
    }
    document.getElementById('modal-producto').style.display = 'none';
    itemsPendientes = [];
    renderizarItemsPendientes();
    limpiarFormularioItem();
}

// ============ CATEGORÍAS ============
function cargarCategorias() {
    fetch(MODAL_BASE_URL + 'modules/productos/categorias_ajax.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const selects = [
                document.getElementById('filtro-categoria'),
                document.getElementById('nuevo-prod-categoria')
            ];
            selects.forEach(sel => {
                if (!sel) return;
                const firstOption = sel.options[0];
                sel.innerHTML = '';
                sel.appendChild(firstOption);
                data.categorias.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.id;
                    opt.textContent = cat.nombre;
                    sel.appendChild(opt);
                });
            });
        });
}

// ============ BUSCAR PRODUCTOS ============
function debounceBuscar() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(buscarProductos, 300);
}

function buscarProductos() {
    const q = document.getElementById('filtro-busqueda').value.trim();
    const cat = document.getElementById('filtro-categoria').value;
    
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (cat) params.append('categoria', cat);
    
    fetch(MODAL_BASE_URL + 'modules/productos/buscar_ajax.php?' + params.toString())
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            productosCache = data.productos;
            renderizarProductos(data.productos);
        });
}

function renderizarProductos(productos) {
    const cont = document.getElementById('productos-lista');
    if (!productos || productos.length === 0) {
        cont.innerHTML = '<p class="empty-msg">' + t('noProductos') + '</p>';
        return;
    }
    
    let html = '<table class="tabla-productos"><thead><tr>' +
        '<th>' + (t('nombre') || 'Nombre') + '</th>' +
        '<th>' + (t('categoria') || 'Categoría') + '</th>' +
        '<th>' + (t('unidad') || 'Unidad') + '</th>' +
        '<th></th>' +
        '</tr></thead><tbody>';
    
    productos.forEach(p => {
        html += `<tr>
            <td>${escapeHtml(p.nombre)}</td>
            <td>${escapeHtml(p.categoria_nombre || '-')}</td>
            <td>${escapeHtml(p.unidad_medida || '-')}</td>
            <td>
                <button type="button" class="btn-small btn-primary"
                    onclick='seleccionarProducto(${JSON.stringify(p).replace(/'/g, "&#39;")})'>
                    ${t('seleccionar') || 'Seleccionar'}
                </button>
            </td>
        </tr>`;
    });
    html += '</tbody></table>';
    cont.innerHTML = html;
}

function seleccionarProducto(producto) {
    document.getElementById('item-producto-id').value = producto.id;
    document.getElementById('item-nombre').value = producto.nombre;
    
    const unidadSelect = document.getElementById('item-unidad');
    if (unidadSelect) {
        unidadSelect.value = producto.unidad_medida || '';
    }
    
    document.getElementById('item-cantidad').focus();
}

// ============ CREAR PRODUCTO RÁPIDO ============
function toggleNuevoProducto() {
    const form = document.getElementById('nuevo-producto-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function crearProductoRapido() {
    const nombre = document.getElementById('nuevo-prod-nombre').value.trim();
    const categoria_id = document.getElementById('nuevo-prod-categoria').value;
    const unidad_medida = document.getElementById('nuevo-prod-unidad').value;
    const codigo = document.getElementById('nuevo-prod-codigo').value.trim();
    const descripcion = document.getElementById('nuevo-prod-descripcion').value.trim();
    
    if (!nombre) {
        Toast.warning(t('nombreObligatorio'));
        return;
    }
    
    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('categoria_id', categoria_id);
    formData.append('unidad_medida', unidad_medida);
    formData.append('codigo', codigo);
    formData.append('descripcion', descripcion);
    
    fetch(MODAL_BASE_URL + 'modules/productos/crear_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('item-producto-id').value = data.producto.id;
            document.getElementById('item-nombre').value = data.producto.nombre;
            
            const unidadSelect = document.getElementById('item-unidad');
            if (unidadSelect) {
                unidadSelect.value = data.producto.unidad_medida || '';
            }
            
            document.getElementById('nuevo-prod-nombre').value = '';
            document.getElementById('nuevo-prod-unidad').value = '';
            document.getElementById('nuevo-prod-codigo').value = '';
            document.getElementById('nuevo-prod-descripcion').value = '';
            document.getElementById('nuevo-producto-form').style.display = 'none';
            
            Toast.success(
                document.documentElement.lang === 'pt'
                    ? 'Produto criado com sucesso!'
                    : '¡Producto creado exitosamente!'
            );
            
            buscarProductos();
            document.getElementById('item-cantidad').focus();
        } else {
            Toast.error((t('errorCrear') || 'Error') + ': ' + (data.error || ''));
        }
    })
    .catch(e => {
        Toast.error('Error: ' + e.message);
    });
}

// ============ AGREGAR ITEM A LA LISTA PENDIENTE ============
function agregarItem() {
    const producto_id = document.getElementById('item-producto-id').value || null;
    const nombre = document.getElementById('item-nombre').value.trim();
    const cantidad = parseInt(document.getElementById('item-cantidad').value) || 0;
    const unidad = document.getElementById('item-unidad').value;
    const fecha = document.getElementById('item-fecha').value;
    const especificaciones = document.getElementById('item-especificaciones').value.trim();
    
    if (!nombre) {
        Toast.warning(t('errorNombre'));
        return;
    }
    if (cantidad < 1) {
        Toast.warning(t('errorCantidad'));
        return;
    }
    if (!fecha) {
        Toast.warning(t('errorFecha'));
        return;
    }
    
    const existe = itemsPendientes.find(i => 
        (producto_id && i.producto_id == producto_id) || 
        (!producto_id && i.nombre_item.toLowerCase() === nombre.toLowerCase())
    );
    
    if (existe) {
        (async () => {
            const ok = await Confirm.show({
                titulo: 'Item duplicado',
                mensaje: t('confirmarSumar'),
                textoConfirmar: document.documentElement.lang === 'pt' ? 'Somar' : 'Sumar',
                tipo: 'question'
            });
            
            if (ok) {
                existe.cantidad += cantidad;
                renderizarItemsPendientes();
                limpiarFormularioItem();
                Toast.info(
                    document.documentElement.lang === 'pt'
                        ? 'Quantidade atualizada'
                        : 'Cantidad actualizada'
                );
            }
        })();
        return;
    }
    
    itemsPendientes.push({
        producto_id: producto_id,
        nombre_item: nombre,
        cantidad: cantidad,
        unidad_medida: unidad,
        fecha_requerida: fecha,
        especificaciones: especificaciones
    });
    
    renderizarItemsPendientes();
    limpiarFormularioItem();
    
    Toast.success(
        document.documentElement.lang === 'pt'
            ? 'Item adicionado à lista'
            : 'Item agregado a la lista',
        { duracion: 2000 }
    );
}

function limpiarFormularioItem() {
    document.getElementById('item-producto-id').value = '';
    document.getElementById('item-nombre').value = '';
    document.getElementById('item-cantidad').value = '1';
    document.getElementById('item-unidad').value = '';
    document.getElementById('item-fecha').value = '';
    document.getElementById('item-especificaciones').value = '';
}

function renderizarItemsPendientes() {
    const cont = document.getElementById('items-pendientes');
    document.getElementById('contador-items').textContent = itemsPendientes.length;
    
    if (itemsPendientes.length === 0) {
        cont.innerHTML = '<p class="empty-msg">' + t('noItems') + '</p>';
        return;
    }
    
    let html = '<table class="tabla-items-pendientes"><thead><tr>' +
        '<th>' + (t('nombre') || 'Item') + '</th>' +
        '<th>' + (t('cantidad') || 'Cantidad') + '</th>' +
        '<th>' + (t('unidad') || 'Unidad') + '</th>' +
        '<th>' + (t('fecha') || 'Fecha') + '</th>' +
        '<th></th>' +
        '</tr></thead><tbody>';
    
    itemsPendientes.forEach((item, idx) => {
        html += `<tr>
            <td>${escapeHtml(item.nombre_item)}</td>
            <td>${item.cantidad}</td>
            <td>${escapeHtml(item.unidad_medida || '-')}</td>
            <td>${formatearFecha(item.fecha_requerida)}</td>
            <td>
                <button type="button" class="btn-icon btn-icon-danger" 
                        onclick="eliminarPendiente(${idx})" 
                        title="${document.documentElement.lang === 'pt' ? 'Remover' : 'Eliminar'}">×</button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    cont.innerHTML = html;
}

function eliminarPendiente(idx) {
    itemsPendientes.splice(idx, 1);
    renderizarItemsPendientes();
}

// ============ GUARDAR TODOS LOS ITEMS ============
function guardarTodos() {
    if (itemsPendientes.length === 0) {
        Toast.warning(t('sinItemsGuardar'));
        return;
    }
    
    const btn = event.target;
    const textoOriginal = btn.textContent;
    btn.disabled = true;
    btn.textContent = t('guardando');
    
    const payload = {
        proyecto_id: PROYECTO_ID,
        items: itemsPendientes
    };
    
    fetch(MODAL_BASE_URL + 'modules/proyectos/agregar_items_lote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = textoOriginal;
        
        if (data.success) {
            const guardados = data.guardados || data.items?.length || 0;
            
            if (data.errores && data.errores.length > 0) {
                Toast.warning(t('errorGuardar') + ' ' + data.errores.join(' • '), { duracion: 6000 });
            }
            
            const msg = document.documentElement.lang === 'pt'
                ? `${guardados} item(s) salvos com sucesso!`
                : `¡${guardados} item(s) guardados exitosamente!`;
            
            try {
                sessionStorage.setItem('toast_pendiente', JSON.stringify({
                    tipo: 'success',
                    mensaje: msg
                }));
            } catch (e) {}
            
            itemsPendientes = [];
            renderizarItemsPendientes();
            
            window.location.reload();
        } else {
            Toast.error((t('errorGuardar') || 'Error') + ' ' + (data.error || ''));
        }
    })
    .catch(e => {
        btn.disabled = false;
        btn.textContent = textoOriginal;
        Toast.error('Error: ' + e.message);
    });
}

// ============ UTILIDADES ============
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

function t(key) {
    return (window.TRAD && TRAD[key]) ? TRAD[key] : key;
}

// ============ INICIALIZACIÓN ============
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-producto');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) cerrarModalProducto();
        });
        
        const fechaInput = document.getElementById('item-fecha');
        if (fechaInput && !fechaInput.value) {
            const hoy = new Date().toISOString().split('T')[0];
            fechaInput.value = hoy;
        }
        
        const inputs = ['item-nombre', 'item-cantidad', 'item-unidad', 'item-fecha'];
        inputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        agregarItem();
                    }
                });
            }
        });
    }
});