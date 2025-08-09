<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Probando consulta de estadísticas de inventario:\n";
    
    $stats_query = "SELECT 
                      COUNT(*) as total_items,
                      SUM(CASE WHEN Estado = 'Activo' THEN 1 ELSE 0 END) as items_activos,
                      SUM(CASE WHEN Cantidad_Stock <= Stock_Minimo THEN 1 ELSE 0 END) as stock_critico,
                      SUM(CASE WHEN Cantidad_Stock <= Stock_Minimo * 1.5 AND Cantidad_Stock > Stock_Minimo THEN 1 ELSE 0 END) as stock_bajo,
                      COALESCE(SUM(Cantidad_Stock * Precio_Unitario), 0) as valor_total_inventario,
                      COALESCE(AVG(Precio_Unitario), 0) as precio_promedio
                    FROM TB_Inventario i";
    
    $stmt = $db->query($stats_query);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Resultados:\n";
    print_r($stats);
    
    echo "\nProbando consulta de inventario:\n";
    
    $query = "SELECT 
                i.ID_Inventario as ID_Item,
                i.Nombre as Nombre_Item,
                i.Descripcion,
                i.Cantidad_Stock,
                i.Stock_Minimo,
                i.Precio_Unitario,
                i.Estado,
                i.Fecha_Ingreso as Fecha_Ultima_Actualizacion,
                c.Nombre_Categoria as categoria_nombre,
                p.Nombre_Proveedor as proveedor_nombre,
                (i.Cantidad_Stock * i.Precio_Unitario) as valor_total
              FROM TB_Inventario i
              LEFT JOIN TB_Categorias c ON i.ID_Categoria = c.ID_Categoria
              LEFT JOIN TB_Proveedores p ON i.ID_Proveedor = p.ID_Proveedor
              LIMIT 5";
    
    $stmt = $db->query($query);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Items encontrados: " . count($items) . "\n";
    if (!empty($items)) {
        echo "Primer item:\n";
        print_r($items[0]);
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
