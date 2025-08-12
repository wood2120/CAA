    // Actualiza solo el estado del item
    public function updateEstado() {
        $query = "UPDATE " . $this->table_name . " SET Estado = :estado WHERE ID_Inventario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id', $this->id_inventario);
        return $stmt->execute();
    }
<?php
// No incluir database.php aquí para evitar problemas de ruta

class Inventario {
    private $conn;
    private $table_name = "TB_Inventario";

    public $id_inventario;
    public $nombre;
    public $descripcion;
    public $id_categoria;
    public $id_proveedor;
    public $cantidad_stock;
    public $stock_minimo;
    public $precio_unitario;
    public $unidad_medida;
    public $fecha_ingreso;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (Nombre, Descripcion, ID_Categoria, ID_Proveedor, Cantidad_Stock, Stock_Minimo, Precio_Unitario, Unidad_Medida) 
                  VALUES (:nombre, :descripcion, :id_categoria, :id_proveedor, :cantidad_stock, :stock_minimo, :precio_unitario, :unidad_medida)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':id_categoria', $this->id_categoria);
        $stmt->bindParam(':id_proveedor', $this->id_proveedor);
        $stmt->bindParam(':cantidad_stock', $this->cantidad_stock);
        $stmt->bindParam(':stock_minimo', $this->stock_minimo);
        $stmt->bindParam(':precio_unitario', $this->precio_unitario);
        $stmt->bindParam(':unidad_medida', $this->unidad_medida);

        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT * FROM VW_Inventario_Completo ORDER BY Nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ID_Inventario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_inventario);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->nombre = $row['Nombre'];
            $this->descripcion = $row['Descripcion'];
            $this->id_categoria = $row['ID_Categoria'];
            $this->id_proveedor = $row['ID_Proveedor'];
            $this->cantidad_stock = $row['Cantidad_Stock'];
            $this->stock_minimo = $row['Stock_Minimo'];
            $this->precio_unitario = $row['Precio_Unitario'];
            $this->unidad_medida = $row['Unidad_Medida'];
            $this->fecha_ingreso = $row['Fecha_Ingreso'];
            $this->estado = $row['Estado'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
                  Nombre = :nombre,
                  Descripcion = :descripcion,
                  ID_Categoria = :id_categoria,
                  ID_Proveedor = :id_proveedor,
                  Stock_Minimo = :stock_minimo,
                  Precio_Unitario = :precio_unitario,
                  Unidad_Medida = :unidad_medida,
                  Estado = :estado
                  WHERE ID_Inventario = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':id_categoria', $this->id_categoria);
        $stmt->bindParam(':id_proveedor', $this->id_proveedor);
        $stmt->bindParam(':stock_minimo', $this->stock_minimo);
        $stmt->bindParam(':precio_unitario', $this->precio_unitario);
        $stmt->bindParam(':unidad_medida', $this->unidad_medida);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id', $this->id_inventario);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE ID_Inventario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_inventario);
        return $stmt->execute();
    }

    public function search($searchTerm) {
        $query = "SELECT * FROM VW_Inventario_Completo 
                  WHERE Nombre LIKE :search 
                  OR Descripcion LIKE :search
                  OR Nombre_Categoria LIKE :search
                  OR Nombre_Proveedor LIKE :search
                  ORDER BY Nombre";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%{$searchTerm}%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        return $stmt;
    }

    public function updateCantidad($nuevaCantidad) {
        $query = "UPDATE " . $this->table_name . " SET Cantidad_Stock = :cantidad WHERE ID_Inventario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cantidad', $nuevaCantidad);
        $stmt->bindParam(':id', $this->id_inventario);
        return $stmt->execute();
    }

    public function registrarMovimiento($tipo, $cantidad, $motivo, $idUsuario, $idTrabajo = null) {
        $query = "INSERT INTO TB_Movimientos_Inventario 
                  (ID_Inventario, Tipo_Movimiento, Cantidad, Motivo, ID_Usuario, ID_Trabajo) 
                  VALUES (:id_inventario, :tipo, :cantidad, :motivo, :id_usuario, :id_trabajo)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_inventario', $this->id_inventario);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':cantidad', $cantidad);
        $stmt->bindParam(':motivo', $motivo);
        $stmt->bindParam(':id_usuario', $idUsuario);
        $stmt->bindParam(':id_trabajo', $idTrabajo);
        return $stmt->execute();
    }

    public function getStockBajo($limite = 5) {
        $query = "SELECT * FROM VW_Stock_Bajo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getEstadisticas() {
        $query = "SELECT 
                    COUNT(*) as total_items,
                    SUM(Cantidad_Stock) as total_unidades,
                    SUM(Cantidad_Stock * Precio_Unitario) as valor_total_inventario,
                    AVG(Precio_Unitario) as precio_promedio,
                    MIN(Cantidad_Stock) as stock_minimo,
                    MAX(Cantidad_Stock) as stock_maximo,
                    COUNT(CASE WHEN Estado = 'Agotado' THEN 1 END) as items_agotados,
                    COUNT(CASE WHEN Cantidad_Stock <= Stock_Minimo THEN 1 END) as items_stock_bajo
                  FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function exists() {
        $query = "SELECT ID_Inventario FROM " . $this->table_name . " 
                  WHERE Nombre = :nombre AND ID_Inventario != :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':id', $this->id_inventario);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getItemsMasCostosos($limite = 10) {
        $query = "SELECT *, (Cantidad_Stock * Precio_Unitario) as valor_total
                  FROM VW_Inventario_Completo 
                  ORDER BY valor_total DESC 
                  LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getValorPorCategoria() {
        $query = "SELECT 
                    c.Nombre_Categoria as categoria,
                    c.Tipo as tipo_categoria,
                    COUNT(i.ID_Inventario) as cantidad_items,
                    SUM(i.Cantidad_Stock) as total_unidades,
                    SUM(i.Cantidad_Stock * i.Precio_Unitario) as valor_total
                  FROM TB_Categorias c
                  LEFT JOIN TB_Inventario i ON c.ID_Categoria = i.ID_Categoria
                  GROUP BY c.ID_Categoria, c.Nombre_Categoria, c.Tipo
                  ORDER BY valor_total DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getMovimientosRecientes($limite = 20) {
        $query = "SELECT 
                    m.*,
                    i.Nombre as item_nombre,
                    u.Usuario,
                    t.Tipo_Trabajo
                  FROM TB_Movimientos_Inventario m
                  LEFT JOIN TB_Inventario i ON m.ID_Inventario = i.ID_Inventario
                  LEFT JOIN TB_Usuarios u ON m.ID_Usuario = u.ID_Usuario
                  LEFT JOIN TB_Trabajos t ON m.ID_Trabajo = t.ID_Trabajo
                  WHERE m.ID_Inventario = :id_inventario
                  ORDER BY m.Fecha_Movimiento DESC
                  LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_inventario', $this->id_inventario);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getPorCategoria($idCategoria) {
        $query = "SELECT * FROM VW_Inventario_Completo 
                  WHERE ID_Categoria = :id_categoria 
                  ORDER BY Nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_categoria', $idCategoria);
        $stmt->execute();
        return $stmt;
    }

    public function getPorTipo($tipo) {
        $query = "SELECT * FROM VW_Inventario_Completo 
                  WHERE Tipo_Categoria = :tipo 
                  ORDER BY Nombre_Categoria, Nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        return $stmt;
    }
}
?>
