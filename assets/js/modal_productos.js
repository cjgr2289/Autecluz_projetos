// assets/js/modal_productos.js
// Sistema de modal para agregar productos a un proyecto sin perder los ya agregados

let itemsPendientes = []; // Items que se van a guardar al final
let productosCache = [];
let debounceTimer = null;

// ============ ABRIR / CERRAR MODAL ============
function abrirModalProducto() {
    document.getElementById('modal-producto').style.display = 'flex';
    cargarCategorias();
    buscarProductos();
}

function cerrarModalProducto() {
    if (itemsPendientes.length > 0) {
        if (!confirm('Hay items pendientes sin guardar. ¿Desea cerrar de todas formas? Se perderán.')) {
            return;
        }
    }
    document.getElementById('modal-producto').style.display = 'none';
    itemsPendientes = [];
    renderizarItemsPendientes();
    limpiarFormularioItem();
}

// ============ CATEGORÍAS ============
function cargarCategorias() {
    fetch(BASE_URL + 'modules/productos/categorias_ajax.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const selects = [
                document.getElementById('filtro-categoria'),
                document.getElementById('nuevo-prod-categoria')
            ];
            selects.forEach(sel => {
                if (!sel) return;
                // Conservar opción inicial
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
    
    fetch(BASE_URL + 'modules/productos/buscar_ajax.php?' + params.toString())
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
        cont.innerHTML = '<p class="empty-msg">No se encontraron productos</p>';
        return;
    }
    
    let html = '<table class="tabla-productos"><thead><tr><th>Nombre</th><th>Categoría</th><th>Unidad</th><th></th></tr></thead><tbody>';
    productos.forEach(p => {
        html += `<tr>
            <td>${escapeHtml(p.nombre)}</td>
            <td>${escapeHtml(p.categoria_nombre || '-')}</td>
            <td>${escapeHtml(p.unidad_medida || '-')}</td>
            <td>
                <button type="button" class="btn-small btn-primary"
                    onclick='seleccionarProducto(${JSON.stringify(p).replace(/'/g, "&#39;")})'>
                    Seleccionar
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
    
    // Ahora es un select, se asigna directo con value
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
    const unidad_medida = document.getElementById('nuevo-prod-unidad').value.trim();
    const codigo = document.getElementById('nuevo-prod-codigo').value.trim();
    const descripcion = document.getElementById('nuevo-prod-descripcion').value.trim();
    
    if (!nombre) {
        Toast.warning('El nombre del producto es obligatorio');
        return;
    }
    
    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('categoria_id', categoria_id);
    formData.append('unidad_medida', unidad_medida);
    formData.append('codigo', codigo);
    formData.append('descripcion', descripcion);
    
    fetch(BASE_URL + 'modules/productos/crear_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Autoseleccionar el producto creado
            document.getElementById('item-producto-id').value = data.producto.id;
            document.getElementById('item-nombre').value = data.producto.nombre;
            document.getElementById('item-unidad').value = data.producto.unidad_medida || '';
            
            // Limpiar form
            document.getElementById('nuevo-prod-nombre').value = '';
            document.getElementById('nuevo-prod-unidad').value = '';
            document.getElementById('nuevo-prod-codigo').value = '';
            document.getElementById('nuevo-prod-descripcion').value = '';
            document.getElementById('nuevo-producto-form').style.display = 'none';
            
            // Refrescar la lista
            buscarProductos();
            document.getElementById('item-cantidad').focus();
        } else {
            Toast.error('Error: ' + (data.error || 'No se pudo crear el producto'));
        }
    });
}

// ============ AGREGAR ITEM A LA LISTA PENDIENTE ============
function agregarItem() {
    const producto_id = document.getElementById('item-producto-id').value || null;
    const nombre = document.getElementById('item-nombre').value.trim();
    const cantidad = parseInt(document.getElementById('item-cantidad').value) || 0;
    const unidad = document.getElementById('item-unidad').value.trim();
    const fecha = document.getElementById('item-fecha').value;
    const especificaciones = document.getElementById('item-especificaciones').value.trim();
    
    if (!nombre) {
        Toast.warning('Debe seleccionar o escribir un producto');
        return;
    }
    if (cantidad < 1) {
        Toast.warning('La cantidad debe ser al menos 1');
        return;
    }
    if (!fecha) {
        Toast.warning('Debe indicar la fecha requerida');
        return;
    }
    
    // Verificar duplicado (mismo producto o nombre)
    const existe = itemsPendientes.find(i => 
        (producto_id && i.producto_id == producto_id) || 
        (!producto_id && i.nombre_item.toLowerCase() === nombre.toLowerCase())
    );
    
    if (existe) {
        if (confirm('Este producto ya está en la lista. ¿Desea sumar la cantidad?')) {
            existe.cantidad += cantidad;
            renderizarItemsPendientes();
            limpiarFormularioItem();
        }
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
        cont.innerHTML = '<p class="empty-msg">Aún no ha agregado items</p>';
        return;
    }
    
    let html = '<table class="tabla-items-pendientes"><thead><tr>' +
        '<th>Item</th><th>Cantidad</th><th>Unidad</th><th>Fecha</th><th></th>' +
        '</tr></thead><tbody>';
    
    itemsPendientes.forEach((item, idx) => {
        html += `<tr>
            <td>${escapeHtml(item.nombre_item)}</td>
            <td>${item.cantidad}</td>
            <td>${escapeHtml(item.unidad_medida || '-')}</td>
            <td>${item.fecha_requerida}</td>
            <td><button type="button" class="btn-small btn-danger" onclick="eliminarPendiente(${idx})">X</button></td>
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
    btn.disabled = true;
    btn.textContent = t('guardando');
    
    // Enviar todos los items en UNA sola petición
    const payload = {
        proyecto_id: PROYECTO_ID,
        items: itemsPendientes
    };
    
    fetch(BASE_URL + 'modules/proyectos/agregar_items_lote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = t('guardarTodos');
        
        if (data.success) {
            // Mostrar errores si hay alguno
            if (data.errores && data.errores.length > 0) {
                Toast.error(t('errorGuardar') + '\n' + data.errores.join('\n'));
            }
            
            // Limpiar items pendientes
            itemsPendientes = [];
            renderizarItemsPendientes();
            
            // Cerrar modal
            document.getElementById('modal-producto').style.display = 'none';
            
            // ✅ SOLUCIÓN: Recargar la página para mostrar los items actualizados
            // Esto evita errores de DOM y siempre muestra los datos reales
            window.location.reload();
        } else {
            Toast.error(t('errorGuardar') + ' ' + (data.error || ''));
        }
    })
    .catch(e => {
        btn.disabled = false;
        btn.textContent = t('guardarTodos');
        Toast.error('Error: ' + e.message);
    });
}

function agregarFilaTabla(item, itemId) {
    const tbody = document.getElementById('items-tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${escapeHtml(item.nombre_item)}</td>
        <td>-</td>
        <td>${item.cantidad}</td>
        <td>${escapeHtml(item.unidad_medida || '-')}</td>
        <td>${item.fecha_requerida}</td>
        <td><span class="estado-badge estado-solicitado">Solicitado</span></td>
        <td>
            <a href="../items/actualizar_estado.php?id=${itemId}&proyecto=${PROYECTO_ID}" class="btn-small">Estado</a>
            <a href="../items/editar.php?id=${itemId}&proyecto=${PROYECTO_ID}" class="btn-small">Editar</a>
        </td>
    `;
    tbody.insertBefore(tr, tbody.firstChild);
}

// ============ UTILIDADES ============
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// Cerrar modal al hacer click fuera
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-producto');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) cerrarModalProducto();
        });
        
        // Poner fecha de hoy por defecto
        const fechaInput = document.getElementById('item-fecha');
        if (fechaInput) {
            const hoy = new Date().toISOString().split('T')[0];
            fechaInput.value = hoy;
        }
        
        // Enter para agregar item
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