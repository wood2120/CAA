<?php
// Evitar cualquier salida antes de headers
ob_start();

try {
    require_once 'config/database.php';
    require_once 'models/Cliente.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $clienteModel = new Cliente($db);
        $clientes = $clienteModel->readAll();
        $count = $clientes->rowCount();
        
        // Limpiar buffer y mostrar resultado
        ob_clean();
        echo "✅ Conexión exitosa - Clientes encontrados: $count";
        
    } else {
        ob_clean();
        echo "❌ Error de conexión";
    }
    
} catch (Exception $e) {
    ob_clean();
    echo "❌ Error: " . $e->getMessage();
}

// Limpiar buffer al final
ob_end_flush();
?>
