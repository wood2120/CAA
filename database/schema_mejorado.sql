-- Esquema mejorado con materiales, herramientas y relaciones de inventario
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS SistemaGestionEmpresa;
USE SistemaGestionEmpresa;

-- Tabla de usuarios
CREATE TABLE TB_Usuarios (
    ID_Usuario INT AUTO_INCREMENT PRIMARY KEY,
    Usuario VARCHAR(150) NOT NULL,
    Contrasena VARCHAR(255) NOT NULL,
    Rol ENUM('Administrador','Contador','Trabajador') NOT NULL;
);

-- Tabla de clientes
CREATE TABLE TB_Clientes (
    Cedula VARCHAR(15) PRIMARY KEY,
    Nombre VARCHAR(150) NOT NULL,
    Contacto VARCHAR(100),
    Empresa VARCHAR(150)
);

-- Tabla de categorías de inventario
CREATE TABLE TB_Categorias (
    ID_Categoria INT AUTO_INCREMENT PRIMARY KEY,
    Nombre_Categoria VARCHAR(100) NOT NULL UNIQUE,
    Descripcion TEXT,
    Tipo ENUM('Material', 'Herramienta') NOT NULL
);

-- Tabla de proveedores
CREATE TABLE TB_Proveedores (
    ID_Proveedor INT AUTO_INCREMENT PRIMARY KEY,
    Nombre_Proveedor VARCHAR(150) NOT NULL,
    Contacto VARCHAR(100),
    Email VARCHAR(100),
    Direccion TEXT
);

-- Tabla de inventario general (materiales y herramientas)
CREATE TABLE TB_Inventario (
    ID_Inventario INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(150) NOT NULL,
    Descripcion TEXT,
    ID_Categoria INT,
    ID_Proveedor INT,
    Cantidad_Stock INT NOT NULL DEFAULT 0,
    Stock_Minimo INT DEFAULT 5,
    Precio_Unitario DECIMAL(10, 2) NOT NULL,
    Unidad_Medida VARCHAR(50) DEFAULT 'Unidad',
    Fecha_Ingreso DATE DEFAULT (CURRENT_DATE),
    Estado ENUM('Activo', 'Inactivo', 'Agotado') DEFAULT 'Activo',
    FOREIGN KEY (ID_Categoria) REFERENCES TB_Categorias(ID_Categoria),
    FOREIGN KEY (ID_Proveedor) REFERENCES TB_Proveedores(ID_Proveedor)
);

-- Tabla de trabajos
CREATE TABLE TB_Trabajos (
    ID_Trabajo INT AUTO_INCREMENT PRIMARY KEY,
    Cedula_Cliente VARCHAR(15),
    Tipo_Trabajo VARCHAR(150) NOT NULL,
    Descripcion TEXT,
    Precio_Mano_Obra DECIMAL(10, 2) NOT NULL,
    Precio_Total DECIMAL(10, 2) NOT NULL,
    Estado ENUM('Pendiente', 'En Proceso', 'Completado', 'Cancelado') DEFAULT 'Pendiente',
    Fecha_Inicio DATE,
    Fecha_Final DATE,
    Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Cedula_Cliente) REFERENCES TB_Clientes(Cedula)
);

-- Tabla de detalle de materiales/herramientas usados en trabajos
CREATE TABLE TB_Trabajo_Inventario (
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

-- Tabla de movimientos de inventario (entradas y salidas)
CREATE TABLE TB_Movimientos_Inventario (
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

-- Tabla de bitácora (registro de acciones del sistema)
CREATE TABLE TB_Bitacora (
    ID_Bitacora INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT,
    Accion VARCHAR(255),
    Fecha_Hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Usuario) REFERENCES TB_Usuarios(ID_Usuario)
);

-- Insertar datos iniciales

-- Usuario administrador por defecto
INSERT INTO TB_Usuarios (Usuario, Contrasena, Rol) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador');

-- Categorías iniciales
INSERT INTO TB_Categorias (Nombre_Categoria, Descripcion, Tipo) VALUES
('Cemento y Mortero', 'Materiales de construcción base', 'Material'),
('Arena y Grava', 'Materiales agregados', 'Material'),
('Hierro y Acero', 'Materiales estructurales', 'Material'),
('Pintura y Acabados', 'Materiales de acabado', 'Material'),
('Herramientas Manuales', 'Herramientas de mano básicas', 'Herramienta'),
('Herramientas Eléctricas', 'Herramientas con motor eléctrico', 'Herramienta'),
('Equipos de Seguridad', 'Elementos de protección personal', 'Herramienta'),
('Maquinaria Pesada', 'Equipos de construcción grandes', 'Herramienta');

-- Proveedores iniciales
INSERT INTO TB_Proveedores (Nombre_Proveedor, Contacto, Email, Direccion) VALUES
('Ferretería Central', '2222-3333', 'ventas@ferreteriacentral.com', 'San José Centro'),
('Materiales del Pacífico', '2777-8888', 'info@materialespacifico.com', 'Puntarenas'),
('Herramientas Industriales SA', '2555-4444', 'pedidos@herramientasind.com', 'Cartago');

-- Trigger para actualizar stock después de movimientos
DELIMITER //
CREATE TRIGGER actualizar_stock_movimiento 
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
CREATE TRIGGER registrar_uso_inventario
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
CREATE TRIGGER actualizar_precio_total_trabajo
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

-- Vistas útiles

-- Vista de inventario con información completa
CREATE VIEW VW_Inventario_Completo AS
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
CREATE VIEW VW_Trabajos_Detalle AS
SELECT 
    t.ID_Trabajo,
    t.Tipo_Trabajo,
    cl.Nombre as Cliente_Nombre,
    t.Precio_Mano_Obra,
    COALESCE(SUM(ti.Subtotal), 0) as Costo_Materiales,
    t.Precio_Total,
    t.Estado,
    t.Fecha_Inicio,
    t.Fecha_Final
FROM TB_Trabajos t
LEFT JOIN TB_Clientes cl ON t.Cedula_Cliente = cl.Cedula
LEFT JOIN TB_Trabajo_Inventario ti ON t.ID_Trabajo = ti.ID_Trabajo
GROUP BY t.ID_Trabajo;

-- Vista de items con stock bajo
CREATE VIEW VW_Stock_Bajo AS
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
