<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Verificando estructura de TB_Trabajos:\n";
    $stmt = $conn->query('DESCRIBE TB_Trabajos');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }
    
    echo "\nVerificando estructura de TB_Inventario:\n";
    $stmt = $conn->query('DESCRIBE TB_Inventario');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
