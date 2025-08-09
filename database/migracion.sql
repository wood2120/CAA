-- Script de migración para actualizar base de datos existente
-- Ejecutar paso a paso para evitar errores

USE SistemaGestionEmpresa;

-- Paso 1: Crear nuevas tablas

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS TB_Categorias (
    ID_Categoria INT AUTO_INCREMENT PRIMARY KEY,
    Nombre_Categoria VARCHAR(100) NOT NULL UNIQUE,
    Descripcion TEXT,
    Tipo ENUM('Material', 'Herramienta') NOT NULL
);

-- Tabla de proveedores
CREATE TABLE IF NOT EXISTS TB_Proveedores (
    ID_Proveedor INT AUTO_INCREMENT PRIMARY KEY,
    Nombre_Proveedor VARCHAR(150) NOT NULL,
    Contacto VARCHAR(100),
    Email VARCHAR(100),
    Direccion TEXT
);

-- Paso 2: Modificar tabla de inventario existente
-- Hacer backup de datos existentes
CREATE TABLE TB_Inventario_Backup AS SELECT * FROM TB_Inventario;

-- Agregar nuevas columnas a TB_Inventario
ALTER TABLE TB_Inventario 
ADD COLUMN Nombre VARCHAR(150) AFTER ID_Inventario,
ADD COLUMN Descripcion TEXT AFTER Nombre,
ADD COLUMN ID_Categoria INT AFTER Descripcion,
ADD COLUMN ID_Proveedor INT AFTER ID_Categoria,
ADD COLUMN Stock_Minimo INT DEFAULT 5 AFTER Cantidad,
ADD COLUMN Unidad_Medida VARCHAR(50) DEFAULT 'Unidad' AFTER Precio,
ADD COLUMN Fecha_Ingreso DATE DEFAULT (CURRENT_DATE) AFTER Unidad_Medida,
ADD COLUMN Estado ENUM('Activo', 'Inactivo', 'Agotado') DEFAULT 'Activo' AFTER Fecha_Ingreso;

-- Renombrar columnas existentes
ALTER TABLE TB_Inventario 
CHANGE COLUMN Herramienta_Material Nombre_Temporal VARCHAR(150),
CHANGE COLUMN Cantidad Cantidad_Stock INT NOT NULL,
CHANGE COLUMN Precio Precio_Unitario DECIMAL(10, 2) NOT NULL;

-- Migrar datos existentes
UPDATE TB_Inventario SET Nombre = Nombre_Temporal WHERE Nombre IS NULL;
UPDATE TB_Inventario SET Descripcion = Nombre_Temporal WHERE Descripcion IS NULL;

-- Eliminar columna temporal
ALTER TABLE TB_Inventario DROP COLUMN Nombre_Temporal;

-- Agregar claves foráneas
ALTER TABLE TB_Inventario 
ADD CONSTRAINT FK_Inventario_Categoria FOREIGN KEY (ID_Categoria) REFERENCES TB_Categorias(ID_Categoria),
ADD CONSTRAINT FK_Inventario_Proveedor FOREIGN KEY (ID_Proveedor) REFERENCES TB_Proveedores(ID_Proveedor);

-- Paso 3: Modificar tabla de trabajos
ALTER TABLE TB_Trabajos 
ADD COLUMN Descripcion TEXT AFTER Tipo_Trabajo,
ADD COLUMN Estado ENUM('Pendiente', 'En Proceso', 'Completado', 'Cancelado') DEFAULT 'Pendiente' AFTER Descripcion,
ADD COLUMN Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP AFTER Fecha_Final;

-- Renombrar columna de precio
ALTER TABLE TB_Trabajos 
CHANGE COLUMN Precio Precio_Mano_Obra DECIMAL(10, 2) NOT NULL,
ADD COLUMN Precio_Total DECIMAL(10, 2) NOT NULL AFTER Precio_Mano_Obra;

-- Migrar datos existentes de trabajos
UPDATE TB_Trabajos SET Precio_Total = Precio_Mano_Obra WHERE Precio_Total = 0;
UPDATE TB_Trabajos SET Estado = 'Completado' WHERE Fecha_Final <= CURDATE() AND Estado IS NULL;
UPDATE TB_Trabajos SET Estado = 'Pendiente' WHERE Estado IS NULL;

-- Paso 4: Crear nuevas tablas de relación

-- Tabla de trabajo-inventario
CREATE TABLE IF NOT EXISTS TB_Trabajo_Inventario (
    ID_Detalle INT AUTO_INCREMENT PRIMARY KEY,
    ID_Trabajo INT,
    ID_Inventario INT,
    Cantidad_Usada INT NOT NULL,
    Precio_Unitario_Usado DECIMAL(10, 2) NOT NULL,
    Subtotal DECIMAL(10, 2) AS (Cantidad_Usada * Precio_Unitario_Usado) STORED,
    Fecha_Uso DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Trabajo) REFERENCES TB_Trabajos(ID_Trabajo) ON DELETE CASCADE,
    FOREIGN KEY (ID_Inventario) REFERENCES TB_Inventario(ID_Inventario)
);

-- Tabla de movimientos de inventario
CREATE TABLE IF NOT EXISTS TB_Movimientos_Inventario (
    ID_Movimiento INT AUTO_INCREMENT PRIMARY KEY,
    ID_Inventario INT,
    Tipo_Movimiento ENUM('Entrada', 'Salida', 'Ajuste') NOT NULL,
    Cantidad INT NOT NULL,
    Motivo VARCHAR(255),
    ID_Usuario INT,
    ID_Trabajo INT NULL,
    Fecha_Movimiento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Inventario) REFERENCES TB_Inventario(ID_Inventario),
    FOREIGN KEY (ID_Usuario) REFERENCES TB_Usuarios(ID_Usuario),
    FOREIGN KEY (ID_Trabajo) REFERENCES TB_Trabajos(ID_Trabajo)
);

-- Paso 5: Insertar datos iniciales

-- Categorías iniciales
INSERT IGNORE INTO TB_Categorias (Nombre_Categoria, Descripcion, Tipo) VALUES
('Cemento y Mortero', 'Materiales de construcción base', 'Material'),
('Arena y Grava', 'Materiales agregados', 'Material'),
('Hierro y Acero', 'Materiales estructurales', 'Material'),
('Pintura y Acabados', 'Materiales de acabado', 'Material'),
('Herramientas Manuales', 'Herramientas de mano básicas', 'Herramienta'),
('Herramientas Eléctricas', 'Herramientas con motor eléctrico', 'Herramienta'),
('Equipos de Seguridad', 'Elementos de protección personal', 'Herramienta'),
('Maquinaria Pesada', 'Equipos de construcción grandes', 'Herramienta');

-- Proveedores iniciales
INSERT IGNORE INTO TB_Proveedores (Nombre_Proveedor, Contacto, Email, Direccion) VALUES
('Ferretería Central', '2222-3333', 'ventas@ferreteriacentral.com', 'San José Centro'),
('Materiales del Pacífico', '2777-8888', 'info@materialespacifico.com', 'Puntarenas'),
('Herramientas Industriales SA', '2555-4444', 'pedidos@herramientasind.com', 'Cartago');

-- Asignar categorías por defecto a inventario existente
UPDATE TB_Inventario SET 
    ID_Categoria = (
        CASE 
            WHEN LOWER(Nombre) LIKE '%cemento%' OR LOWER(Nombre) LIKE '%mortero%' THEN 1
            WHEN LOWER(Nombre) LIKE '%arena%' OR LOWER(Nombre) LIKE '%grava%' THEN 2
            WHEN LOWER(Nombre) LIKE '%hierro%' OR LOWER(Nombre) LIKE '%acero%' OR LOWER(Nombre) LIKE '%varilla%' THEN 3
            WHEN LOWER(Nombre) LIKE '%pintura%' OR LOWER(Nombre) LIKE '%barniz%' THEN 4
            WHEN LOWER(Nombre) LIKE '%martillo%' OR LOWER(Nombre) LIKE '%destornillador%' OR LOWER(Nombre) LIKE '%llave%' THEN 5
            WHEN LOWER(Nombre) LIKE '%taladro%' OR LOWER(Nombre) LIKE '%sierra%' OR LOWER(Nombre) LIKE '%amoladora%' THEN 6
            WHEN LOWER(Nombre) LIKE '%casco%' OR LOWER(Nombre) LIKE '%guante%' OR LOWER(Nombre) LIKE '%gafa%' THEN 7
            ELSE 8
        END
    ),
    ID_Proveedor = 1
WHERE ID_Categoria IS NULL;

-- Paso 6: Crear triggers

DELIMITER //

-- Trigger para actualizar stock después de movimientos
CREATE TRIGGER IF NOT EXISTS actualizar_stock_movimiento 
AFTER INSERT ON TB_Movimientos_Inventario
FOR EACH ROW
BEGIN
    IF NEW.Tipo_Movimiento = 'Entrada' THEN
        UPDATE TB_Inventario 
        SET Cantidad_Stock = Cantidad_Stock + NEW.Cantidad
        WHERE ID_Inventario = NEW.ID_Inventario;
    ELSEIF NEW.Tipo_Movimiento = 'Salida' THEN
        UPDATE TB_Inventario 
        SET Cantidad_Stock = Cantidad_Stock - NEW.Cantidad
        WHERE ID_Inventario = NEW.ID_Inventario;
    ELSEIF NEW.Tipo_Movimiento = 'Ajuste' THEN
        UPDATE TB_Inventario 
        SET Cantidad_Stock = NEW.Cantidad
        WHERE ID_Inventario = NEW.ID_Inventario;
    END IF;
    
    -- Actualizar estado si está agotado
    UPDATE TB_Inventario 
    SET Estado = CASE 
        WHEN Cantidad_Stock <= 0 THEN 'Agotado'
        WHEN Cantidad_Stock <= Stock_Minimo THEN 'Activo'
        ELSE 'Activo'
    END
    WHERE ID_Inventario = NEW.ID_Inventario;
END//

-- Trigger para registrar movimientos cuando se usa inventario en trabajos
CREATE TRIGGER IF NOT EXISTS registrar_uso_inventario
AFTER INSERT ON TB_Trabajo_Inventario
FOR EACH ROW
BEGIN
    INSERT INTO TB_Movimientos_Inventario 
    (ID_Inventario, Tipo_Movimiento, Cantidad, Motivo, ID_Trabajo)
    VALUES 
    (NEW.ID_Inventario, 'Salida', NEW.Cantidad_Usada, 
     CONCAT('Usado en trabajo ID: ', NEW.ID_Trabajo), NEW.ID_Trabajo);
END//

-- Trigger para actualizar precio total del trabajo
CREATE TRIGGER IF NOT EXISTS actualizar_precio_total_trabajo
AFTER INSERT ON TB_Trabajo_Inventario
FOR EACH ROW
BEGIN
    UPDATE TB_Trabajos 
    SET Precio_Total = Precio_Mano_Obra + (
        SELECT COALESCE(SUM(Subtotal), 0)
        FROM TB_Trabajo_Inventario 
        WHERE ID_Trabajo = NEW.ID_Trabajo
    )
    WHERE ID_Trabajo = NEW.ID_Trabajo;
END//

DELIMITER ;

-- Paso 7: Crear vistas

-- Vista de inventario con información completa
CREATE OR REPLACE VIEW VW_Inventario_Completo AS
SELECT 
    i.ID_Inventario,
    i.Nombre,
    i.Descripcion,
    c.Nombre_Categoria,
    c.Tipo as Tipo_Categoria,
    p.Nombre_Proveedor,
    i.Cantidad_Stock,
    i.Stock_Minimo,
    i.Precio_Unitario,
    i.Unidad_Medida,
    i.Estado,
    (i.Cantidad_Stock * i.Precio_Unitario) as Valor_Total_Stock
FROM TB_Inventario i
LEFT JOIN TB_Categorias c ON i.ID_Categoria = c.ID_Categoria
LEFT JOIN TB_Proveedores p ON i.ID_Proveedor = p.ID_Proveedor;

-- Vista de trabajos con costos detallados
CREATE OR REPLACE VIEW VW_Trabajos_Detalle AS
SELECT 
    t.ID_Trabajo,
    t.Tipo_Trabajo,
    t.Descripcion,
    cl.Nombre as Cliente_Nombre,
    t.Cedula_Cliente,
    t.Precio_Mano_Obra,
    COALESCE(SUM(ti.Subtotal), 0) as Costo_Materiales,
    t.Precio_Total,
    t.Estado,
    t.Fecha_Inicio,
    t.Fecha_Final,
    t.Fecha_Creacion
FROM TB_Trabajos t
LEFT JOIN TB_Clientes cl ON t.Cedula_Cliente = cl.Cedula
LEFT JOIN TB_Trabajo_Inventario ti ON t.ID_Trabajo = ti.ID_Trabajo
GROUP BY t.ID_Trabajo;

-- Vista de items con stock bajo
CREATE OR REPLACE VIEW VW_Stock_Bajo AS
SELECT 
    i.ID_Inventario,
    i.Nombre,
    c.Nombre_Categoria,
    i.Cantidad_Stock,
    i.Stock_Minimo,
    i.Estado
FROM TB_Inventario i
LEFT JOIN TB_Categorias c ON i.ID_Categoria = c.ID_Categoria
WHERE i.Cantidad_Stock <= i.Stock_Minimo
ORDER BY i.Cantidad_Stock ASC;

-- Registrar movimientos de entrada para el stock existente
INSERT INTO TB_Movimientos_Inventario (ID_Inventario, Tipo_Movimiento, Cantidad, Motivo, ID_Usuario)
SELECT 
    ID_Inventario, 
    'Entrada', 
    Cantidad_Stock, 
    'Stock inicial migrado', 
    1
FROM TB_Inventario 
WHERE Cantidad_Stock > 0;

-- Mensaje de finalización
SELECT 'Migración completada exitosamente. Revise los datos y pruebe la funcionalidad.' as Resultado;
