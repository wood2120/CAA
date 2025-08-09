<?php
/**
 * Cargador de variables de entorno desde archivo .env
 */

class EnvLoader {
    private static $loaded = false;
    
    /**
     * Carga las variables de entorno desde el archivo .env
     * @param string $envFile Ruta al archivo .env
     */
    public static function load($envFile = null) {
        if (self::$loaded) {
            return; // Ya se cargó anteriormente
        }
        
        if ($envFile === null) {
            $envFile = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($envFile)) {
            throw new Exception("Archivo .env no encontrado en: " . $envFile);
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Buscar variables en formato KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas si existen
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Solo establecer si no existe ya en $_ENV o $_SERVER
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Obtiene una variable de entorno con valor por defecto
     * @param string $key Nombre de la variable
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public static function get($key, $default = null) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }
    
    /**
     * Convierte string a boolean
     * @param string $value
     * @return bool
     */
    public static function toBool($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
?>
