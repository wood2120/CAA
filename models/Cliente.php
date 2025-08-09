<?php
// No incluir database.php aquí para evitar problemas de ruta

class Cliente {
    private $conn;
    private $table_name = "TB_Clientes";

    public $Cedula;
    public $Nombre;
    public $Contacto;
    public $Empresa;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (Cedula, Nombre, Contacto, Empresa) 
                  VALUES 
                  (:cedula, :nombre, :contacto, :empresa)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->Cedula = htmlspecialchars(strip_tags($this->Cedula));
        $this->Nombre = htmlspecialchars(strip_tags($this->Nombre));
        $this->Contacto = htmlspecialchars(strip_tags($this->Contacto));
        $this->Empresa = htmlspecialchars(strip_tags($this->Empresa));

        // Bind valores
        $stmt->bindParam(":cedula", $this->Cedula);
        $stmt->bindParam(":nombre", $this->Nombre);
        $stmt->bindParam(":contacto", $this->Contacto);
        $stmt->bindParam(":empresa", $this->Empresa);

        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY Nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($cedula) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE Cedula = :cedula LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->Cedula = $row['Cedula'];
            $this->Nombre = $row['Nombre'];
            $this->Contacto = $row['Contacto'];
            $this->Empresa = $row['Empresa'];
            return $row;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET Nombre = :nombre,
                      Contacto = :contacto,
                      Empresa = :empresa
                  WHERE Cedula = :cedula";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->Cedula = htmlspecialchars(strip_tags($this->Cedula));
        $this->Nombre = htmlspecialchars(strip_tags($this->Nombre));
        $this->Contacto = htmlspecialchars(strip_tags($this->Contacto));
        $this->Empresa = htmlspecialchars(strip_tags($this->Empresa));

        // Bind valores
        $stmt->bindParam(":nombre", $this->Nombre);
        $stmt->bindParam(":contacto", $this->Contacto);
        $stmt->bindParam(":empresa", $this->Empresa);
        $stmt->bindParam(":cedula", $this->Cedula);

        return $stmt->execute();
    }

    public function delete() {
        // Verificar si tiene trabajos asociados
        $query = "SELECT COUNT(*) as count FROM TB_Trabajos WHERE Cedula_Cliente = :cedula";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $this->Cedula);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return false; // No se puede eliminar cliente con trabajos
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE Cedula = :cedula";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $this->Cedula);

        return $stmt->execute();
    }

    public function exists($cedula) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE Cedula = :cedula";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    public function search($termino) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Nombre LIKE :termino 
                     OR Cedula LIKE :termino 
                     OR Empresa LIKE :termino
                  ORDER BY Nombre";

        $stmt = $this->conn->prepare($query);
        $termino = "%{$termino}%";
        $stmt->bindParam(':termino', $termino);
        $stmt->execute();

        return $stmt;
    }

    public function getTrabajosByCliente($cedula) {
        $query = "SELECT * FROM TB_Trabajos WHERE Cedula_Cliente = :cedula ORDER BY Fecha_Creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        return $stmt;
    }

    public function getEstadisticasCliente($cedula) {
        $query = "SELECT 
                    COUNT(*) as total_trabajos,
                    SUM(CASE WHEN Estado = 'Completado' THEN 1 ELSE 0 END) as trabajos_completados,
                    SUM(CASE WHEN Estado = 'Pendiente' THEN 1 ELSE 0 END) as trabajos_pendientes,
                    SUM(CASE WHEN Estado = 'En Proceso' THEN 1 ELSE 0 END) as trabajos_proceso,
                    COALESCE(SUM(Precio_Total), 0) as total_ingresos
                  FROM TB_Trabajos 
                  WHERE Cedula_Cliente = :cedula";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>