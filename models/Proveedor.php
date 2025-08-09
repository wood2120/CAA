<?php
require_once __DIR__ . '/../config/database.php';

class Proveedor {
    private $conn;
    private $table_name = "TB_Proveedores";

    public $id_proveedor;
    public $nombre_proveedor;
    public $contacto;
    public $email;
    public $direccion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (Nombre_Proveedor, Contacto, Email, Direccion) 
                  VALUES (:nombre_proveedor, :contacto, :email, :direccion)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre_proveedor', $this->nombre_proveedor);
        $stmt->bindParam(':contacto', $this->contacto);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':direccion', $this->direccion);

        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY Nombre_Proveedor";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ID_Proveedor = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_proveedor);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->nombre_proveedor = $row['Nombre_Proveedor'];
            $this->contacto = $row['Contacto'];
            $this->email = $row['Email'];
            $this->direccion = $row['Direccion'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
                  Nombre_Proveedor = :nombre_proveedor,
                  Contacto = :contacto,
                  Email = :email,
                  Direccion = :direccion
                  WHERE ID_Proveedor = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre_proveedor', $this->nombre_proveedor);
        $stmt->bindParam(':contacto', $this->contacto);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':direccion', $this->direccion);
        $stmt->bindParam(':id', $this->id_proveedor);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE ID_Proveedor = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_proveedor);
        return $stmt->execute();
    }

    public function search($searchTerm) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Nombre_Proveedor LIKE :search 
                  OR Contacto LIKE :search
                  OR Email LIKE :search
                  ORDER BY Nombre_Proveedor";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%{$searchTerm}%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        return $stmt;
    }

    public function exists() {
        $query = "SELECT ID_Proveedor FROM " . $this->table_name . " 
                  WHERE Nombre_Proveedor = :nombre_proveedor AND ID_Proveedor != :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre_proveedor', $this->nombre_proveedor);
        $stmt->bindParam(':id', $this->id_proveedor);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getConEstadisticas() {
        $query = "SELECT 
                    p.*,
                    COUNT(i.ID_Inventario) as total_items,
                    COALESCE(SUM(i.Cantidad_Stock), 0) as total_stock,
                    COALESCE(SUM(i.Cantidad_Stock * i.Precio_Unitario), 0) as valor_total
                  FROM " . $this->table_name . " p
                  LEFT JOIN TB_Inventario i ON p.ID_Proveedor = i.ID_Proveedor
                  GROUP BY p.ID_Proveedor
                  ORDER BY p.Nombre_Proveedor";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getItemsProveedor() {
        $query = "SELECT * FROM VW_Inventario_Completo 
                  WHERE ID_Proveedor = :id_proveedor 
                  ORDER BY Nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_proveedor', $this->id_proveedor);
        $stmt->execute();
        return $stmt;
    }
}
?>
