<?php
require_once __DIR__ . '/../config/database.php';

class TrabajoInventario {
    private $conn;
    private $table_name = "TB_Trabajo_Inventario";

    public $id_detalle;
    public $id_trabajo;
    public $id_inventario;
    public $cantidad_usada;
    public $precio_unitario_usado;
    public $subtotal;
    public $fecha_uso;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (ID_Trabajo, ID_Inventario, Cantidad_Usada, Precio_Unitario_Usado) 
                  VALUES (:id_trabajo, :id_inventario, :cantidad_usada, :precio_unitario_usado)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_trabajo', $this->id_trabajo);
        $stmt->bindParam(':id_inventario', $this->id_inventario);
        $stmt->bindParam(':cantidad_usada', $this->cantidad_usada);
        $stmt->bindParam(':precio_unitario_usado', $this->precio_unitario_usado);

        return $stmt->execute();
    }

    public function getItemsPorTrabajo($idTrabajo) {
        $query = "SELECT 
                    ti.*,
                    i.Nombre as item_nombre,
                    i.Unidad_Medida,
                    c.Nombre_Categoria,
                    c.Tipo as tipo_categoria
                  FROM " . $this->table_name . " ti
                  LEFT JOIN TB_Inventario i ON ti.ID_Inventario = i.ID_Inventario
                  LEFT JOIN TB_Categorias c ON i.ID_Categoria = c.ID_Categoria
                  WHERE ti.ID_Trabajo = :id_trabajo
                  ORDER BY ti.Fecha_Uso DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_trabajo', $idTrabajo);
        $stmt->execute();
        return $stmt;
    }

    public function getTotalCostoMateriales($idTrabajo) {
        $query = "SELECT COALESCE(SUM(Subtotal), 0) as total_materiales
                  FROM " . $this->table_name . " 
                  WHERE ID_Trabajo = :id_trabajo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_trabajo', $idTrabajo);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_materiales'];
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE ID_Detalle = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_detalle);
        return $stmt->execute();
    }

    public function verificarStock($idInventario, $cantidadRequerida) {
        $query = "SELECT Cantidad_Stock FROM TB_Inventario WHERE ID_Inventario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idInventario);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['Cantidad_Stock'] >= $cantidadRequerida;
        }
        return false;
    }

    public function getResumenPorCategoria($idTrabajo) {
        $query = "SELECT 
                    c.Nombre_Categoria,
                    c.Tipo,
                    COUNT(ti.ID_Detalle) as items_usados,
                    SUM(ti.Cantidad_Usada) as cantidad_total,
                    SUM(ti.Subtotal) as costo_total
                  FROM " . $this->table_name . " ti
                  LEFT JOIN TB_Inventario i ON ti.ID_Inventario = i.ID_Inventario
                  LEFT JOIN TB_Categorias c ON i.ID_Categoria = c.ID_Categoria
                  WHERE ti.ID_Trabajo = :id_trabajo
                  GROUP BY c.ID_Categoria, c.Nombre_Categoria, c.Tipo
                  ORDER BY costo_total DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_trabajo', $idTrabajo);
        $stmt->execute();
        return $stmt;
    }
}
?>
