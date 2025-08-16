<?php
require_once __DIR__ . '/../config/database.php';

class Usuario {
    private $conn;
    private $table_name = "TB_Usuarios";

    public $id_usuario;
    public $usuario;
    public $contrasena;
    public $rol;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Autenticar usuario
    public function login($usuario, $contrasena) {
        $query = "SELECT ID_Usuario, Usuario, Contrasena, Rol FROM " . $this->table_name . " WHERE Usuario = :usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($contrasena, $row['Contrasena'])) {
                $this->id_usuario = $row['ID_Usuario'];
                $this->usuario = $row['Usuario'];
                $this->rol = $row['Rol'];
                return true;
            }
        }
        return false;
    }

    // Crear usuario
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (Usuario, Contrasena, Rol) VALUES (:usuario, :contrasena, :rol)";
        $stmt = $this->conn->prepare($query);

        // Hash de la contraseña
        $hashed_password = password_hash($this->contrasena, PASSWORD_DEFAULT);

        $stmt->bindParam(':usuario', $this->usuario);
        $stmt->bindParam(':contrasena', $hashed_password);
        $stmt->bindParam(':rol', $this->rol);

        return $stmt->execute();
    }

    // Leer todos los usuarios
    public function readAll() {
        $query = "SELECT ID_Usuario, Usuario, Rol FROM " . $this->table_name . " ORDER BY Usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer un usuario específico
    public function readOne($id) {
        $query = "SELECT ID_Usuario, Usuario, Rol FROM " . $this->table_name . " WHERE ID_Usuario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar usuario
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET Usuario = :usuario, Rol = :rol";
        
        if (!empty($this->contrasena)) {
            $query .= ", Contrasena = :contrasena";
        }
        
        $query .= " WHERE ID_Usuario = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':usuario', $this->usuario);
        $stmt->bindParam(':rol', $this->rol);
        $stmt->bindParam(':id', $this->id_usuario);
        
        if (!empty($this->contrasena)) {
            $hashed_password = password_hash($this->contrasena, PASSWORD_DEFAULT);
            $stmt->bindParam(':contrasena', $hashed_password);
        }

        return $stmt->execute();
    }

    // Eliminar usuario
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE ID_Usuario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_usuario);
        return $stmt->execute();
    }

    // Verificar si el usuario existe
    public function exists() {
    $query = "SELECT ID_Usuario FROM " . $this->table_name . " WHERE Usuario = :usuario AND ID_Usuario != :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $this->usuario);
        $stmt->bindParam(':id', $this->id_usuario);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
