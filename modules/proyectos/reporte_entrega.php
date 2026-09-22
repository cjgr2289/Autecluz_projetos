<?php
// modules/proyectos/reporte_entrega.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['id'] ?? 0;

if (!$proyecto_id) {
    redirigir('modules/proyectos/index.php');
}

$stmt = $db->prepare("SELECT p.*, 
                             u.nombre_completo as creador,
                             e.nombre_completo as encargado_nombre
                      FROM proyectos p 
                      LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
                      LEFT JOIN usuarios e ON p.encargado_id = e.id
                      WHERE p.id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php');
}

// ============================================
// Items SEPARADOS (listos para entregar)
// ============================================
$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? AND i.estado = 'separado'
                      ORDER BY c.nombre ASC, i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items_separados = $stmt->fetchAll();

$estados_item = getEstadosItem();

// Totales
$total_items = count($items_separados);
$total_cantidad = 0;
foreach ($items_separados as $it) {
    $total_cantidad += (int)($it['cantidad_stock'] ?: $it['cantidad']);
}

// Fecha de entrega = hoy (o editable por parámetro)
$fecha_entrega = $_GET['fecha'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_SESSION['idioma'] == 'pt' ? 'Comprovante de Entrega' : 'Comprobante de Entrega'; ?> - <?php echo htmlspecialchars($proyecto['nombre']); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/reportes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <!-- Acciones (no se imprimen) -->
        <div class="reporte-acciones no-print">
            <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto_id); ?>" class="btn-secondary">
                ← <?php echo traducir('Volver'); ?>
            </a>
            <button type="button" class="btn-primary" onclick="window.print()">
                🖨 <?php echo traducir('Imprimir'); ?>
            </button>
        </div>
        
        <div class="reporte-container">
            
            <!-- ============================================
                 ENCABEZADO INSTITUCIONAL
                 ============================================ -->
            <div class="entrega-header">
                <div class="entrega-header-empresa">
                    <div class="entrega-logo">
                        <div class="entrega-logo-placeholder">AUTECLUZ</div>
                    </div>
                    <div class="entrega-empresa-datos">
                        <strong>AutecLuz Soluções Industriais</strong><br>
                        sistema de gerenciamento de projetos
                    </div>
                </div>
                <div class="entrega-header-doc">
                    <div class="entrega-doc-titulo">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'COMPROVANTE DE ENTREGA' : 'COMPROBANTE DE ENTREGA'; ?>
                    </div>
                    <div class="entrega-doc-subtitulo">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Materiais / Equipamentos' : 'Materiales / Equipos'; ?>
                    </div>
                    <div class="entrega-doc-numero">
                        <strong>Nº:</strong> ENT-<?php echo str_pad($proyecto_id, 4, '0', STR_PAD_LEFT); ?>-<?php echo date('Ymd'); ?>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
                 DATOS DEL PROYECTO EN TARJETAS
                 ============================================ -->
            <div class="entrega-datos-grid">
                <div class="entrega-dato-card">
                    <div class="entrega-dato-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto' : 'Proyecto'; ?></div>
                    <div class="entrega-dato-valor"><?php echo htmlspecialchars($proyecto['nombre']); ?></div>
                </div>
                
                <div class="entrega-dato-card">
                    <div class="entrega-dato-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Ordem de Compra' : 'Orden de Compra'; ?></div>
                    <div class="entrega-dato-valor">
                        <?php echo htmlspecialchars($proyecto['orden_compra'] ?? '—'); ?>
                    </div>
                </div>
                
                <div class="entrega-dato-card">
                    <div class="entrega-dato-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Data de Entrega' : 'Fecha de Entrega'; ?></div>
                    <div class="entrega-dato-valor">
                        <?php echo formatearFecha($fecha_entrega); ?>
                    </div>
                </div>
                
                <div class="entrega-dato-card">
                    <div class="entrega-dato-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Responsável do Projeto' : 'Encargado del Proyecto'; ?></div>
                    <div class="entrega-dato-valor">
                        <?php echo htmlspecialchars($proyecto['encargado_nombre'] ?? '—'); ?>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
                 INSTRUCCIONES PARA EL OPERADOR
                 ============================================ -->
            <div class="entrega-instrucciones">
                <strong>📌 <?php echo $_SESSION['idioma'] == 'pt' ? 'Instruções:' : 'Instrucciones:'; ?></strong>
                <?php echo $_SESSION['idioma'] == 'pt'
                    ? 'Verifique cada item listado abaixo. Marque a coluna "OK" para confirmar o recebimento conforme. Se houver divergência, anote no campo de observações.'
                    : 'Verifique cada item listado abajo. Marque la columna "OK" para confirmar la recepción conforme. Si hay alguna divergencia, anótela en el campo de observaciones.'; ?>
            </div>
            
            <!-- ============================================
                 TABLA DE MATERIALES
                 ============================================ -->
            <table class="entrega-tabla">
                <thead>
                    <tr>
                        <th style="width:5%;" class="text-center">#</th>
                        <th style="width:30%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Descrição do Material' : 'Descripción del Material'; ?></th>
                        <th style="width:14%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Categoria' : 'Categoría'; ?></th>
                        <th style="width:9%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Qtd.' : 'Cant.'; ?></th>
                        <th style="width:9%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Unidade' : 'Unidad'; ?></th>
                        <th style="width:14%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Fornecedor' : 'Proveedor'; ?></th>
                        <th style="width:9%;" class="text-center">OK</th>
                        <th style="width:10%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Observações' : 'Observaciones'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items_separados)): ?>
                        <tr>
                            <td colspan="8" class="entrega-vacio">
                                ⚠ <?php echo $_SESSION['idioma'] == 'pt' 
                                    ? 'Nenhum material separado para entrega neste momento.'
                                    : 'Ningún material separado para entrega en este momento.'; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($items_separados as $item): 
                            $cantidad_entregar = $item['cantidad_stock'] ?: $item['cantidad'];
                        ?>
                            <tr>
                                <td class="text-center entrega-numero"><?php echo $i++; ?></td>
                                <td class="entrega-descripcion">
                                    <div class="entrega-item-nombre"><?php echo htmlspecialchars($item['nombre_item']); ?></div>
                                    <?php if (!empty($item['especificaciones'])): ?>
                                        <div class="entrega-item-nota"><?php echo htmlspecialchars($item['especificaciones']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['categoria_nombre']): ?>
                                        <span class="entrega-categoria"><?php echo htmlspecialchars($item['categoria_nombre']); ?></span>
                                    <?php else: ?>
                                        <span style="color:#bdc3c7;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center entrega-cantidad"><?php echo $cantidad_entregar; ?></td>
                                <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></td>
                                <td class="text-center entrega-proveedor"><?php echo htmlspecialchars($item['proveedor'] ?? '—'); ?></td>
                                <td class="text-center entrega-check">
                                    <span class="entrega-check-circulo"></span>
                                </td>
                                <td class="entrega-observaciones"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($items_separados)): ?>
                <tfoot>
                    <tr class="entrega-total-row">
                        <td colspan="3" class="text-right">
                            <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'TOTAL DE ITENS' : 'TOTAL DE ITEMS'; ?>:</strong>
                        </td>
                        <td class="text-center entrega-total-numero"><?php echo $total_items; ?></td>
                        <td colspan="4" class="text-right">
                            <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'TOTAL UNIDADES' : 'TOTAL UNIDADES'; ?>:</strong>
                            <span class="entrega-total-numero"><?php echo $total_cantidad; ?></span>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
            
            <!-- ============================================
                 OBSERVACIONES GENERALES
                 ============================================ -->
            <div class="entrega-observaciones-generales">
                <div class="entrega-observaciones-label">
                    <?php echo $_SESSION['idioma'] == 'pt' ? 'Observações Gerais:' : 'Observaciones Generales:'; ?>
                </div>
                <div class="entrega-observaciones-lineas">
                    <div class="entrega-linea"></div>
                    <div class="entrega-linea"></div>
                    <div class="entrega-linea"></div>
                </div>
            </div>
            
            <!-- ============================================
                 DECLARACIÓN
                 ============================================ -->
            <div class="entrega-declaracion">
                <?php echo $_SESSION['idioma'] == 'pt'
                    ? 'Declaro ter recebido os materiais acima relacionados em perfeito estado de conservação e em conformidade com as especificações do projeto.'
                    : 'Declaro haber recibido los materiales arriba relacionados en perfecto estado de conservación y en conformidad con las especificaciones del proyecto.'; ?>
            </div>
            
            <!-- ============================================
                 FIRMAS
                 ============================================ -->
            <div class="entrega-firmas">
                <div class="entrega-firma-box">
                    <div class="entrega-firma-espacio"></div>
                    <div class="entrega-firma-linea"></div>
                    <div class="entrega-firma-nombre">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Entregue por' : 'Entregado por'; ?>
                    </div>
                    <div class="entrega-firma-cargo">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Almoxarifado' : 'Almacén'; ?>
                    </div>
                    <div class="entrega-firma-campos">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Nome:' : 'Nombre:'; ?></span> _______________________
                    </div>
                    <div class="entrega-firma-campos">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Data:' : 'Fecha:'; ?></span> ____ / ____ / ________
                    </div>
                </div>
                
                <div class="entrega-firma-box">
                    <div class="entrega-firma-espacio"></div>
                    <div class="entrega-firma-linea"></div>
                    <div class="entrega-firma-nombre">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Recebido por' : 'Recibido por'; ?>
                    </div>
                    <div class="entrega-firma-cargo">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Operador do Projeto' : 'Operador del Proyecto'; ?>
                    </div>
                    <div class="entrega-firma-campos">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Nome:' : 'Nombre:'; ?></span> _______________________
                    </div>
                    <div class="entrega-firma-campos">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Data:' : 'Fecha:'; ?></span> ____ / ____ / ________
                    </div>
                </div>
            </div>
            
            <!-- ============================================
                 PIE DE PÁGINA
                 ============================================ -->
            <div class="entrega-footer">
                <div class="entrega-footer-info">
                    <?php echo $_SESSION['idioma'] == 'pt' ? 'Documento gerado em' : 'Documento generado el'; ?>
                    <?php echo date('d/m/Y H:i'); ?>
                    · <?php echo htmlspecialchars($proyecto['nombre']); ?>
                </div>
                <div class="entrega-footer-pagina">
                    <?php echo $_SESSION['idioma'] == 'pt' ? 'Página' : 'Página'; ?> 1 / 1
                </div>
            </div>
            
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>