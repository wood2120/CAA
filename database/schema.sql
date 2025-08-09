-- Crear base de datos
CREATE DATABASE IF NOT EXISTS SistemaGestionEmpresa;
USE SistemaGestionEmpresa;

-- Tabla de usuarios
CREATE TABLE TB_Usuarios (
    ID_Usuario INT AUTO_INCREMENT PRIMARY KEY,
    Usuario VARCHAR(150) NOT NULL,
    Contrasena VARCHAR(255) NOT NULL,
    Rol ENUM('Administrador', 'Dueño') NOT NULL
);

-- Tabla de clientes
CREATE TABLE TB_Clientes (
    Cedula VARCHAR(15) PRIMARY KEY,
    Nombre VARCHAR(150) NOT NULL,
    Contacto VARCHAR(100),
    Empresa VARCHAR(150)
);

-- Tabla de trabajos
CREATE TABLE TB_Trabajos (
    ID_Trabajo INT AUTO_INCREMENT PRIMARY KEY,
    Cedula_Cliente VARCHAR(15),
    Tipo_Trabajo VARCHAR(150) NOT NULL,
    Precio DECIMAL(10, 2) NOT NULL,
    Fecha_Inicio DATE,
    Fecha_Final DATE,
    FOREIGN KEY (Cedula_Cliente) REFERENCES TB_Clientes(Cedula)
);

-- Tabla de inventario
CREATE TABLE TB_Inventario (
    ID_Inventario INT AUTO_INCREMENT PRIMARY KEY,
    Herramienta_Material VARCHAR(150) NOT NULL,
    Cantidad INT NOT NULL,
    Precio DECIMAL(10, 2) NOT NULL
);

-- Tabla de bitácora (registro de acciones del sistema)
CREATE TABLE TB_Bitacora (
    ID_Bitacora INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT,
    Accion VARCHAR(255),
    Fecha_Hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Usuario) REFERENCES TB_Usuarios(ID_Usuario)
);

-- Insertar usuario administrador por defecto
INSERT INTO TB_Usuarios (Usuario, Contrasena, Rol) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador');
-- Contraseña: password (hasheada con bcrypt)
