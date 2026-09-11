<?php
/**
 * includes/unidades.php
 * Catálogo de unidades de medida del sistema
 */

/**
 * Devuelve el catálogo de unidades de medida (traducidas)
 * Formato: código => etiqueta visible
 */
function getUnidadesMedida() {
    $idioma = $_SESSION['idioma'] ?? 'es';
    
    $unidades = [
        'es' => [
            'unidad'    => 'Unidad',
            'caja'      => 'Caja',
            'paquete'   => 'Paquete',
            'kg'        => 'Kilogramo (kg)',
            'g'         => 'Gramo (g)',
            'ton'       => 'Tonelada (ton)',
            'lb'        => 'Libra (lb)',
            'm'         => 'Metro (m)',
            'cm'        => 'Centímetro (cm)',
            'mm'        => 'Milímetro (mm)',
            'km'        => 'Kilómetro (km)',
            'pulgada'   => 'Pulgada (in)',
            'm2'        => 'Metro cuadrado (m²)',
            'm3'        => 'Metro cúbico (m³)',
            'l'         => 'Litro (L)',
            'ml'        => 'Mililitro (ml)',
            'gal'       => 'Galón (gal)',
            'hora'      => 'Hora (h)',
            'dia'       => 'Día',
            'mes'       => 'Mes',
            'juego'     => 'Juego',
            'par'       => 'Par',
            'docena'    => 'Docena',
            'rollo'     => 'Rollo',
            'bolsa'     => 'Bolsa',
            'lata'      => 'Lata',
            'frasco'    => 'Frasco',
            'saco'      => 'Saco',
            'otros'     => 'Otros',
        ],
        'pt' => [
            'unidad'    => 'Unidade',
            'caja'      => 'Caixa',
            'paquete'   => 'Pacote',
            'kg'        => 'Quilograma (kg)',
            'g'         => 'Grama (g)',
            'ton'       => 'Tonelada (ton)',
            'lb'        => 'Libra (lb)',
            'm'         => 'Metro (m)',
            'cm'        => 'Centímetro (cm)',
            'mm'        => 'Milímetro (mm)',
            'km'        => 'Quilômetro (km)',
            'pulgada'   => 'Polegada (in)',
            'm2'        => 'Metro quadrado (m²)',
            'm3'        => 'Metro cúbico (m³)',
            'l'         => 'Litro (L)',
            'ml'        => 'Mililitro (ml)',
            'gal'       => 'Galão (gal)',
            'hora'      => 'Hora (h)',
            'dia'       => 'Dia',
            'mes'       => 'Mês',
            'juego'     => 'Jogo',
            'par'       => 'Par',
            'docena'    => 'Dúzia',
            'rollo'     => 'Rolo',
            'bolsa'     => 'Saco',
            'lata'      => 'Lata',
            'frasco'    => 'Frasco',
            'saco'      => 'Saco',
            'otros'     => 'Outros',
        ]
    ];
    
    return $unidades[$idioma] ?? $unidades['es'];
}

/**
 * Verifica si un código de unidad es válido
 */
function esUnidadValida($codigo) {
    if (empty($codigo)) return true; // permitir vacío (opcional)
    
    $idioma = $_SESSION['idioma'] ?? 'es';
    $catalogo = [
        'es' => ['unidad','caja','paquete','kg','g','ton','lb','m','cm','mm','km','pulgada',
                 'm2','m3','l','ml','gal','hora','dia','mes','juego','par','docena','rollo',
                 'bolsa','lata','frasco','saco','otros'],
        'pt' => ['unidad','caja','paquete','kg','g','ton','lb','m','cm','mm','km','pulgada',
                 'm2','m3','l','ml','gal','hora','dia','mes','juego','par','docena','rollo',
                 'bolsa','lata','frasco','saco','otros']
    ];
    
    return in_array($codigo, $catalogo[$idioma] ?? $catalogo['es']);
}

/**
 * Renderiza un <select> con el catálogo de unidades.
 * 
 * @param string $name       Nombre del campo
 * @param string $selected   Valor seleccionado
 * @param string $id         ID del select (opcional)
 * @param bool   $required   Si es obligatorio
 */
function renderSelectUnidades($name, $selected = '', $id = '', $required = false) {
    $unidades = getUnidadesMedida();
    $id_attr = $id ? 'id="' . htmlspecialchars($id) . '"' : '';
    $req_attr = $required ? 'required' : '';
    
    echo '<select name="' . htmlspecialchars($name) . '" ' . $id_attr . ' ' . $req_attr . '>';
    echo '<option value="">-- ' . traducir('Seleccionar') . ' --</option>';
    foreach ($unidades as $codigo => $etiqueta) {
        $sel = ($selected === $codigo) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($codigo) . '"' . $sel . '>'
             . htmlspecialchars($etiqueta) . '</option>';
    }
    echo '</select>';
}

/**
 * Devuelve la etiqueta visible de un código de unidad
 */
function getUnidadLabel($codigo) {
    if (empty($codigo)) return '-';
    
    $unidades = getUnidadesMedida();
    return $unidades[$codigo] ?? $codigo;
}