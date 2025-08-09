<?php
function removeComments($filePath) {
    $content = file_get_contents($filePath);
    
    // Eliminar comentarios de línea
    $content = preg_replace('/^\s*\/\/.*$/m', '', $content);
    
    // Eliminar comentarios HTML
    $content = preg_replace('/<!--.*?-->/s', '', $content);
    
    // Eliminar líneas vacías múltiples
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    
    file_put_contents($filePath, $content);
    echo "Procesado: $filePath\n";
}

function scanDirectory($dir) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );
    
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            removeComments($file->getPathname());
        }
    }
}

echo "Eliminando comentarios del sistema...\n";
scanDirectory(__DIR__);
echo "Proceso completado.\n";
?>
