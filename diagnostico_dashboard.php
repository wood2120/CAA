<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnóstico Paso a Paso del Dashboard</h2>";
echo "Fecha/Hora: " . date('Y-m-d H:i:s') . "<br><br>";

// Paso 1: Verificar archivos
echo "<h3>1. Verificación de Archivos:</h3>";
$archivos = [
    'includes/functions.php',
    'config/database.php',
    'models/Cliente.php',
    'models/Trabajo.php',
    'models/Inventario.php'
];

foreach ($archivos as $archivo) {
    echo "- $archivo: ";
    if (file_exists($archivo)) {
        echo "✅ Existe (" . filesize($archivo) . " bytes)<br>";
    } else {
        echo "❌ NO EXISTE<br>";
    }
}

// Paso 2: Incluir archivos uno por uno
echo "<br><h3>2. Inclusión Paso a Paso:</h3>";

try {
    echo "Incluyendo functions.php...<br>";
    require_once 'includes/functions.php';
    echo "✅ functions.php incluido correctamente<br>";
} catch (Throwable $e) {
    echo "❌ Error en functions.php: " . $e->getMessage() . "<br>";
    exit;
}

try {
    echo "Incluyendo database.php...<br>";
    require_once 'config/database.php';
    echo "✅ database.php incluido correctamente<br>";
} catch (Throwable $e) {
    echo "❌ Error en database.php: " . $e->getMessage() . "<br>";
    exit;
}

// Verificar clase Database
if (class_exists('Database')) {
    echo "✅ Clase Database disponible<br>";
} else {
    echo "❌ Clase Database NO disponible<br>";
}

try {
    echo "Incluyendo Cliente.php...<br>";
    require_once 'models/Cliente.php';
    echo "✅ Cliente.php incluido correctamente<br>";
} catch (Throwable $e) {
    echo "❌ Error en Cliente.php: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    exit;
}

// Verificar clase Cliente
if (class_exists('Cliente')) {
    echo "✅ Clase Cliente disponible<br>";
} else {
    echo "❌ Clase Cliente NO disponible<br>";
}

try {
    echo "Incluyendo Trabajo.php...<br>";
    require_once 'models/Trabajo.php';
    echo "✅ Trabajo.php incluido correctamente<br>";
} catch (Throwable $e) {
    echo "❌ Error en Trabajo.php: " . $e->getMessage() . "<br>";
    exit;
}

// Verificar clase Trabajo
if (class_exists('Trabajo')) {
    echo "✅ Clase Trabajo disponible<br>";
} else {
    echo "❌ Clase Trabajo NO disponible<br>";
}

try {
    echo "Incluyendo Inventario.php...<br>";
    require_once 'models/Inventario.php';
    echo "✅ Inventario.php incluido correctamente<br>";
} catch (Throwable $e) {
    echo "❌ Error en Inventario.php: " . $e->getMessage() . "<br>";
    exit;
}

// Verificar clase Inventario
if (class_exists('Inventario')) {
    echo "✅ Clase Inventario disponible<br>";
} else {
    echo "❌ Clase Inventario NO disponible<br>";
}

// Paso 3: Instanciar objetos
echo "<br><h3>3. Instanciación de Objetos:</h3>";

try {
    echo "Creando instancia de Database...<br>";
    $database = new Database();
    echo "✅ Database instanciada<br>";
    
    echo "Obteniendo conexión...<br>";
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Conexión obtenida<br>";
        
        echo "Creando instancia de Cliente...<br>";
        $clienteModel = new Cliente($db);
        echo "✅ Cliente instanciado<br>";
        
        echo "Creando instancia de Trabajo...<br>";
        $trabajoModel = new Trabajo($db);
        echo "✅ Trabajo instanciado<br>";
        
        echo "Creando instancia de Inventario...<br>";
        $inventarioModel = new Inventario($db);
        echo "✅ Inventario instanciado<br>";
        
        echo "<br><strong>🎉 Todas las clases funcionan correctamente!</strong><br>";
        echo "El dashboard debería funcionar sin problemas.<br>";
        
    } else {
        echo "❌ No se pudo obtener conexión a la base de datos<br>";
    }
    
} catch (Throwable $e) {
    echo "❌ Error durante la instanciación: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

// Mostrar clases declaradas que contengan nuestros nombres
echo "<br><h3>4. Clases Declaradas:</h3>";
$todasLasClases = get_declared_classes();
$nuestrasClases = array_filter($todasLasClases, function($clase) {
    return in_array($clase, ['Database', 'Cliente', 'Trabajo', 'Inventario']);
});

if (!empty($nuestrasClases)) {
    foreach ($nuestrasClases as $clase) {
        echo "✅ $clase está declarada<br>";
    }
} else {
    echo "❌ Ninguna de nuestras clases está declarada<br>";
}
?>
