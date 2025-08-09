<?php
require_once 'config/database.php';

echo "<h2>Prueba de Conexión a la Base de Datos</h2>";
echo "<hr>";

echo "<p><strong>Configuración actual:</strong></p>";
echo "<ul>";
echo "<li>Host: " . DB_HOST . "</li>";
echo "<li>Puerto: " . DB_PORT . "</li>";
echo "<li>Base de datos: " . DB_NAME . "</li>";
echo "<li>Usuario: " . DB_USER . "</li>";
echo "<li>Contraseña: " . (empty(DB_PASS) ? '(vacía)' : '(configurada)') . "</li>";
echo "</ul>";

echo "<p><strong>Probando conexión...</strong></p>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<div style='color: green; font-weight: bold;'>✅ Conexión exitosa!</div>";
        
        // Probar una consulta simple
        $stmt = $db->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        if ($result) {
            echo "<div style='color: green;'>✅ Consulta de prueba exitosa</div>";
        }
        
        // Verificar si existe la base de datos
        $stmt = $db->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
        $dbExists = $stmt->fetch();
        
        if ($dbExists) {
            echo "<div style='color: green;'>✅ Base de datos '" . DB_NAME . "' encontrada</div>";
            
            // Mostrar algunas tablas si existen
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($tables)) {
                echo "<p><strong>Tablas encontradas:</strong></p>";
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li>$table</li>";
                }
                echo "</ul>";
            } else {
                echo "<div style='color: orange;'>⚠️ La base de datos existe pero no tiene tablas</div>";
            }
        } else {
            echo "<div style='color: red;'>❌ Base de datos '" . DB_NAME . "' no encontrada</div>";
            echo "<p>Bases de datos disponibles:</p>";
            $stmt = $db->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<ul>";
            foreach ($databases as $database_name) {
                echo "<li>$database_name</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ Error: No se pudo establecer conexión</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Error de conexión: " . $e->getMessage() . "</div>";
    
    echo "<h3>Sugerencias de diagnóstico:</h3>";
    echo "<ul>";
    echo "<li>Verificar que MySQL esté ejecutándose en el puerto 3307</li>";
    echo "<li>Verificar que el usuario 'root' tenga permisos</li>";
    echo "<li>Verificar que la base de datos 'SistemaGestionEmpresa' exista</li>";
    echo "<li>Probar diferentes puertos (3306, 3307, etc.)</li>";
    echo "</ul>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
ul { margin: 10px 0; padding-left: 20px; }
div { margin: 10px 0; padding: 5px; }
</style>
