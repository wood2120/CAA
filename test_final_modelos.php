<?php
echo "<h2>Prueba Final de Modelos - " . date('H:i:s') . "</h2>";

try {
    // Test desde el dashboard
    echo "<h3>Simulando Dashboard:</h3>";
    require_once 'includes/functions.php';
    require_once 'config/database.php';
    require_once 'models/Cliente.php';
    require_once 'models/Trabajo.php';
    require_once 'models/Inventario.php';
    
    echo "✅ Todas las inclusiones exitosas<br>";
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Conexión a base de datos obtenida<br>";
        
        $clienteModel = new Cliente($db);
        echo "✅ Modelo Cliente instanciado<br>";
        
        $trabajoModel = new Trabajo($db);
        echo "✅ Modelo Trabajo instanciado<br>";
        
        $inventarioModel = new Inventario($db);
        echo "✅ Modelo Inventario instanciado<br>";
        
        // Probar métodos básicos
        $clientes = $clienteModel->readAll();
        echo "✅ readAll() Cliente: " . $clientes->rowCount() . " registros<br>";
        
        $trabajos = $trabajoModel->readAll();
        echo "✅ readAll() Trabajo: " . $trabajos->rowCount() . " registros<br>";
        
        $inventario = $inventarioModel->readAll();
        echo "✅ readAll() Inventario: " . $inventario->rowCount() . " registros<br>";
        
        echo "<br><strong>🎉 Todos los modelos funcionan correctamente!</strong>";
        
    } else {
        echo "❌ No se pudo obtener conexión a la base de datos";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
    echo "<br>Archivo: " . $e->getFile();
    echo "<br>Línea: " . $e->getLine();
}
?>
