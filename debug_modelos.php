<?php
echo "<h3>Diagnóstico de Inclusión de Modelos</h3>";
echo "Directorio actual: " . __DIR__ . "<br>";
echo "Ruta absoluta: " . realpath('.') . "<br><br>";

echo "Verificando archivos:<br>";

$archivos = [
    'config/database.php',
    'models/Cliente.php',
    'models/Trabajo.php',
    'models/Inventario.php'
];

foreach ($archivos as $archivo) {
    $path = __DIR__ . '/' . $archivo;
    echo "- $archivo: ";
    if (file_exists($path)) {
        echo "✅ EXISTE (" . filesize($path) . " bytes)<br>";
    } else {
        echo "❌ NO EXISTE<br>";
    }
}

echo "<br>Intentando incluir archivos:<br>";

try {
    require_once __DIR__ . '/config/database.php';
    echo "✅ config/database.php incluido<br>";
} catch (Exception $e) {
    echo "❌ Error en config/database.php: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/models/Cliente.php';
    echo "✅ models/Cliente.php incluido<br>";
} catch (Exception $e) {
    echo "❌ Error en models/Cliente.php: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/models/Trabajo.php';
    echo "✅ models/Trabajo.php incluido<br>";
} catch (Exception $e) {
    echo "❌ Error en models/Trabajo.php: " . $e->getMessage() . "<br>";
}

echo "<br>Verificando clases:<br>";

if (class_exists('Database')) {
    echo "✅ Clase Database disponible<br>";
} else {
    echo "❌ Clase Database NO disponible<br>";
}

if (class_exists('Cliente')) {
    echo "✅ Clase Cliente disponible<br>";
} else {
    echo "❌ Clase Cliente NO disponible<br>";
}

if (class_exists('Trabajo')) {
    echo "✅ Clase Trabajo disponible<br>";
} else {
    echo "❌ Clase Trabajo NO disponible<br>";
}

echo "<br>Probando instanciación:<br>";

try {
    $database = new Database();
    echo "✅ Database instanciada<br>";
    
    $db = $database->getConnection();
    if ($db) {
        echo "✅ Conexión obtenida<br>";
        
        $clienteModel = new Cliente($db);
        echo "✅ Cliente instanciado<br>";
        
        $trabajoModel = new Trabajo($db);
        echo "✅ Trabajo instanciado<br>";
    } else {
        echo "❌ No se pudo obtener conexión<br>";
    }
} catch (Exception $e) {
    echo "❌ Error al instanciar: " . $e->getMessage() . "<br>";
}
?>
