<?php
// Test simple de los modelos
ob_start();

try {
    require_once 'config/database.php';
    require_once 'models/Cliente.php';
    require_once 'models/Trabajo.php';
    
    echo "<h3>Prueba de Modelos - " . date('Y-m-d H:i:s') . "</h3>";
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("No hay conexión a la base de datos");
    }
    
    echo "✅ Conexión establecida<br>";
    
    // Probar modelo Cliente
    $clienteModel = new Cliente($db);
    echo "✅ Modelo Cliente creado<br>";
    
    $clientes = $clienteModel->readAll();
    echo "✅ Método readAll() de Cliente ejecutado - Registros: " . $clientes->rowCount() . "<br>";
    
    // Probar modelo Trabajo
    $trabajoModel = new Trabajo($db);
    echo "✅ Modelo Trabajo creado<br>";
    
    $trabajos = $trabajoModel->readAll();
    echo "✅ Método readAll() de Trabajo ejecutado - Registros: " . $trabajos->rowCount() . "<br>";
    
    // Probar estadísticas
    $estadisticas = $trabajoModel->getEstadisticas();
    echo "✅ Estadísticas obtenidas: " . json_encode($estadisticas) . "<br>";
    
    echo "<br><strong>Todos los modelos funcionan correctamente!</strong>";
    
} catch (Exception $e) {
    ob_clean();
    echo "❌ Error: " . $e->getMessage();
    echo "<br>Línea: " . $e->getLine();
    echo "<br>Archivo: " . $e->getFile();
}

ob_end_flush();
?>
