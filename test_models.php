<?php
require_once 'config/database.php';

echo "<h2>Prueba de Conexión y Modelos</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Conexión exitosa<br>";
        
        // Probar una consulta simple
        $stmt = $db->query("SELECT COUNT(*) as count FROM TB_Clientes");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Clientes en base: " . $result['count'] . "<br>";
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM TB_Trabajos");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Trabajos en base: " . $result['count'] . "<br>";
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM TB_Inventario");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Items de inventario en base: " . $result['count'] . "<br>";
        
        // Probar modelos
        require_once 'models/Cliente.php';
        require_once 'models/Trabajo.php';
        require_once 'models/Inventario.php';
        
        $clienteModel = new Cliente($db);
        $trabajoModel = new Trabajo($db);
        $inventarioModel = new Inventario($db);
        
        echo "✅ Modelos cargados correctamente<br>";
        
        // Probar métodos
        $clientes = $clienteModel->readAll();
        echo "✅ Método readAll() de Cliente funciona<br>";
        
        $trabajos = $trabajoModel->readAll();
        echo "✅ Método readAll() de Trabajo funciona<br>";
        
        $inventario = $inventarioModel->readAll();
        echo "✅ Método readAll() de Inventario funciona<br>";
        
        $estadisticas = $trabajoModel->getEstadisticas();
        echo "✅ Estadísticas de trabajos: " . json_encode($estadisticas) . "<br>";
        
    } else {
        echo "❌ Error de conexión";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
