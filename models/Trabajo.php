<?php
class Trabajo {
    private $conn;
    private $table_name = "TB_Trabajos";

    public $ID_Trabajo;
    public $Cedula_Cliente;
    public $Tipo_Trabajo;
    public $Descripcion;
    public $Precio_Mano_Obra;
    public $Precio_Total;
    public $Estado;
    public $Fecha_Inicio;
    public $Fecha_Final;
    public $Fecha_Creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (Cedula_Cliente, Tipo_Trabajo, Descripcion, Precio_Mano_Obra, 
                   Precio_Total, Estado, Fecha_Inicio, Fecha_Final) 
                  VALUES 
                  (:cedula_cliente, :tipo_trabajo, :descripcion, :precio_mano_obra, 
                   :precio_total, :estado, :fecha_inicio, :fecha_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->Cedula_Cliente = htmlspecialchars(strip_tags($this->Cedula_Cliente));
        $this->Tipo_Trabajo = htmlspecialchars(strip_tags($this->Tipo_Trabajo));
        $this->Descripcion = htmlspecialchars(strip_tags($this->Descripcion));
        $this->Precio_Mano_Obra = htmlspecialchars(strip_tags($this->Precio_Mano_Obra));
        $this->Precio_Total = htmlspecialchars(strip_tags($this->Precio_Total));
        $this->Estado = htmlspecialchars(strip_tags($this->Estado));
        $this->Fecha_Inicio = htmlspecialchars(strip_tags($this->Fecha_Inicio));
        $this->Fecha_Final = htmlspecialchars(strip_tags($this->Fecha_Final));

        // Bind valores
        $stmt->bindParam(":cedula_cliente", $this->Cedula_Cliente);
        $stmt->bindParam(":tipo_trabajo", $this->Tipo_Trabajo);
        $stmt->bindParam(":descripcion", $this->Descripcion);
        $stmt->bindParam(":precio_mano_obra", $this->Precio_Mano_Obra);
        $stmt->bindParam(":precio_total", $this->Precio_Total);
        $stmt->bindParam(":estado", $this->Estado);
        $stmt->bindParam(":fecha_inicio", $this->Fecha_Inicio);
        $stmt->bindParam(":fecha_final", $this->Fecha_Final);

        if ($stmt->execute()) {
            $this->ID_Trabajo = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function readAll() {
        $query = "SELECT t.*, c.Nombre as NombreCliente, c.Empresa as EmpresaCliente 
                  FROM " . $this->table_name . " t 
                  LEFT JOIN TB_Clientes c ON t.Cedula_Cliente = c.Cedula 
                  ORDER BY t.Fecha_Creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT t.*, c.Nombre as NombreCliente, c.Contacto as ContactoCliente,
                         c.Empresa as EmpresaCliente
                  FROM " . $this->table_name . " t 
                  LEFT JOIN TB_Clientes c ON t.Cedula_Cliente = c.Cedula 
                  WHERE t.ID_Trabajo = :id 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->ID_Trabajo = $row['ID_Trabajo'];
            $this->Cedula_Cliente = $row['Cedula_Cliente'];
            $this->Tipo_Trabajo = $row['Tipo_Trabajo'];
            $this->Descripcion = $row['Descripcion'];
            $this->Precio_Mano_Obra = $row['Precio_Mano_Obra'];
            $this->Precio_Total = $row['Precio_Total'];
            $this->Estado = $row['Estado'];
            $this->Fecha_Inicio = $row['Fecha_Inicio'];
            $this->Fecha_Final = $row['Fecha_Final'];
            $this->Fecha_Creacion = $row['Fecha_Creacion'];
            return $row;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET Cedula_Cliente = :cedula_cliente,
                      Tipo_Trabajo = :tipo_trabajo,
                      Descripcion = :descripcion,
                      Precio_Mano_Obra = :precio_mano_obra,
                      Precio_Total = :precio_total,
                      Estado = :estado,
                      Fecha_Inicio = :fecha_inicio,
                      Fecha_Final = :fecha_final
                  WHERE ID_Trabajo = :id";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->Cedula_Cliente = htmlspecialchars(strip_tags($this->Cedula_Cliente));
        $this->Tipo_Trabajo = htmlspecialchars(strip_tags($this->Tipo_Trabajo));
        $this->Descripcion = htmlspecialchars(strip_tags($this->Descripcion));
        $this->Precio_Mano_Obra = htmlspecialchars(strip_tags($this->Precio_Mano_Obra));
        $this->Precio_Total = htmlspecialchars(strip_tags($this->Precio_Total));
        $this->Estado = htmlspecialchars(strip_tags($this->Estado));
        $this->Fecha_Inicio = htmlspecialchars(strip_tags($this->Fecha_Inicio));
        $this->Fecha_Final = htmlspecialchars(strip_tags($this->Fecha_Final));
        $this->ID_Trabajo = htmlspecialchars(strip_tags($this->ID_Trabajo));

        // Bind valores
        $stmt->bindParam(":cedula_cliente", $this->Cedula_Cliente);
        $stmt->bindParam(":tipo_trabajo", $this->Tipo_Trabajo);
        $stmt->bindParam(":descripcion", $this->Descripcion);
        $stmt->bindParam(":precio_mano_obra", $this->Precio_Mano_Obra);
        $stmt->bindParam(":precio_total", $this->Precio_Total);
        $stmt->bindParam(":estado", $this->Estado);
        $stmt->bindParam(":fecha_inicio", $this->Fecha_Inicio);
        $stmt->bindParam(":fecha_final", $this->Fecha_Final);
        $stmt->bindParam(":id", $this->ID_Trabajo);

        return $stmt->execute();
    }

    public function delete() {
        // Primero eliminar los materiales asociados
        $query = "DELETE FROM TB_Trabajo_Inventario WHERE ID_Trabajo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->ID_Trabajo);
        $stmt->execute();

        // Luego eliminar el trabajo
        $query = "DELETE FROM " . $this->table_name . " WHERE ID_Trabajo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->ID_Trabajo);

        return $stmt->execute();
    }

    public function getEstadisticas() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN Estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN Estado = 'En Proceso' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN Estado = 'Completado' THEN 1 ELSE 0 END) as completados,
                    SUM(CASE WHEN Estado = 'Cancelado' THEN 1 ELSE 0 END) as cancelados,
                    AVG(Precio_Total) as promedio_costo,
                    SUM(Precio_Total) as total_ingresos
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTrabajosPorMes($año = null) {
        if (!$año) {
            $año = date('Y');
        }

        $query = "SELECT 
                    MONTH(Fecha_Inicio) as mes,
                    COUNT(*) as cantidad,
                    SUM(Precio_Total) as total_ingresos
                  FROM " . $this->table_name . " 
                  WHERE YEAR(Fecha_Inicio) = :año AND Fecha_Inicio IS NOT NULL
                  GROUP BY MONTH(Fecha_Inicio)
                  ORDER BY mes";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':año', $año);
        $stmt->execute();

        return $stmt;
    }

    public function getTrabajosRecientes($limit = 5) {
        $query = "SELECT t.*, c.Nombre as NombreCliente, c.Empresa as EmpresaCliente 
                  FROM " . $this->table_name . " t 
                  LEFT JOIN TB_Clientes c ON t.Cedula_Cliente = c.Cedula 
                  ORDER BY t.Fecha_Creacion DESC 
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function search($termino) {
        $query = "SELECT t.*, c.Nombre as NombreCliente, c.Empresa as EmpresaCliente 
                  FROM " . $this->table_name . " t 
                  LEFT JOIN TB_Clientes c ON t.Cedula_Cliente = c.Cedula 
                  WHERE t.Tipo_Trabajo LIKE :termino 
                     OR t.Descripcion LIKE :termino 
                     OR c.Nombre LIKE :termino 
                     OR t.Estado LIKE :termino
                  ORDER BY t.Fecha_Creacion DESC";

        $stmt = $this->conn->prepare($query);
        $termino = "%{$termino}%";
        $stmt->bindParam(':termino', $termino);
        $stmt->execute();

        return $stmt;
    }

    public function getMaterialesByTrabajo($id_trabajo) {
        $query = "SELECT ti.*, i.Nombre as NombreItem, i.Precio_Unitario,
                         ti.Subtotal
                  FROM TB_Trabajo_Inventario ti
                  JOIN TB_Inventario i ON ti.ID_Inventario = i.ID_Inventario
                  WHERE ti.ID_Trabajo = :id_trabajo";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_trabajo', $id_trabajo);
        $stmt->execute();

        return $stmt;
    }

    public function actualizarCostoMateriales($id_trabajo) {
        // Esto se maneja automáticamente por el trigger en la base de datos
        // Pero podemos forzar la actualización si es necesario
        $query = "UPDATE " . $this->table_name . " 
                  SET Precio_Total = Precio_Mano_Obra + (
                      SELECT COALESCE(SUM(Subtotal), 0)
                      FROM TB_Trabajo_Inventario 
                      WHERE ID_Trabajo = :id_trabajo
                  )
                  WHERE ID_Trabajo = :id_trabajo";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_trabajo', $id_trabajo);

        return $stmt->execute();
    }
}
?>