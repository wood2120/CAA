<?php
require_once __DIR__ . '/../config/database.php';

class Categoria {
    private $conn;
    private $table_name = "TB_Categorias";

    public $id_categoria;
    public $nombre_categoria;
    public $descripcion;
    public $tipo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (Nombre_Categoria, Descripcion, Tipo) 
                  VALUES (:nombre_categoria, :descripcion, :tipo)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':tipo', $this->tipo);

        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY Tipo, Nombre_Categoria";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ID_Categoria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_categoria);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->nombre_categoria = $row['Nombre_Categoria'];
            $this->descripcion = $row['Descripcion'];
            $this->tipo = $row['Tipo'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
                  Nombre_Categoria = :nombre_categoria,
                  Descripcion = :descripcion,
                  Tipo = :tipo
                  WHERE ID_Categoria = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':tipo', $this->tipo);
        $stmt->bindParam(':id', $this->id_categoria);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE ID_Categoria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_categoria);
        return $stmt->execute();
    }

    public function search($searchTerm) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Nombre_Categoria LIKE :search 
                  OR Descripcion LIKE :search
                  ORDER BY Tipo, Nombre_Categoria";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%{$searchTerm}%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        return $stmt;
    }

    public function exists() {
        $query = "SELECT ID_Categoria FROM " . $this->table_name . " 
                  WHERE Nombre_Categoria = :nombre_categoria AND ID_Categoria != :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
        $stmt->bindParam(':id', $this->id_categoria);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getPorTipo($tipo) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Tipo = :tipo 
                  ORDER BY Nombre_Categoria";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        return $stmt;
    }

    public function getConEstadisticas() {
        $query = "SELECT 
                    c.*,
                    COUNT(i.ID_Inventario) as total_items,
                    COALESCE(SUM(i.Cantidad_Stock), 0) as total_stock,
                    COALESCE(SUM(i.Cantidad_Stock * i.Precio_Unitario), 0) as valor_total
                  FROM " . $this->table_name . " c
                  LEFT JOIN TB_Inventario i ON c.ID_Categoria = i.ID_Categoria
                  GROUP BY c.ID_Categoria
                  ORDER BY c.Tipo, c.Nombre_Categoria";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>
