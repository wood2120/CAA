<?php
require_once __DIR__ . '/../config/database.php';

class Bitacora {
    private $conn;
    private $table_name = "TB_Bitacora";

    public $id_bitacora;
    public $id_usuario;
    public $accion;
    public $fecha_hora;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (ID_Usuario, Accion) VALUES (:id_usuario, :accion)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_usuario', $this->id_usuario);
        $stmt->bindParam(':accion', $this->accion);

        return $stmt->execute();
    }

    public function readAll($limite = 100) {
        $query = "SELECT b.ID_Bitacora, b.ID_Usuario, u.Usuario, b.Accion, b.Fecha_Hora
                  FROM " . $this->table_name . " b
                  LEFT JOIN TB_Usuarios u ON b.ID_Usuario = u.ID_Usuario
                  ORDER BY b.Fecha_Hora DESC
                  LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getByUsuario($idUsuario, $limite = 50) {
        $query = "SELECT b.ID_Bitacora, b.ID_Usuario, u.Usuario, b.Accion, b.Fecha_Hora
                  FROM " . $this->table_name . " b
                  LEFT JOIN TB_Usuarios u ON b.ID_Usuario = u.ID_Usuario
                  WHERE b.ID_Usuario = :id_usuario
                  ORDER BY b.Fecha_Hora DESC
                  LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getByDateRange($fechaInicio, $fechaFin) {
        $query = "SELECT b.ID_Bitacora, b.ID_Usuario, u.Usuario, b.Accion, b.Fecha_Hora
                  FROM " . $this->table_name . " b
                  LEFT JOIN TB_Usuarios u ON b.ID_Usuario = u.ID_Usuario
                  WHERE DATE(b.Fecha_Hora) BETWEEN :fecha_inicio AND :fecha_fin
                  ORDER BY b.Fecha_Hora DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha_inicio', $fechaInicio);
        $stmt->bindParam(':fecha_fin', $fechaFin);
        $stmt->execute();
        return $stmt;
    }

    public function search($searchTerm) {
        $query = "SELECT b.ID_Bitacora, b.ID_Usuario, u.Usuario, b.Accion, b.Fecha_Hora
                  FROM " . $this->table_name . " b
                  LEFT JOIN TB_Usuarios u ON b.ID_Usuario = u.ID_Usuario
                  WHERE b.Accion LIKE :search OR u.Usuario LIKE :search
                  ORDER BY b.Fecha_Hora DESC";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%{$searchTerm}%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        return $stmt;
    }

    public function getEstadisticas($fechaInicio = null, $fechaFin = null) {
        $whereClause = "";
        if ($fechaInicio && $fechaFin) {
            $whereClause = "WHERE DATE(b.Fecha_Hora) BETWEEN :fecha_inicio AND :fecha_fin";
        }

        $query = "SELECT 
                    COUNT(*) as total_acciones,
                    COUNT(DISTINCT b.ID_Usuario) as usuarios_activos,
                    DATE(MIN(b.Fecha_Hora)) as primera_accion,
                    DATE(MAX(b.Fecha_Hora)) as ultima_accion
                  FROM " . $this->table_name . " b " . $whereClause;
        
        $stmt = $this->conn->prepare($query);
        
        if ($fechaInicio && $fechaFin) {
            $stmt->bindParam(':fecha_inicio', $fechaInicio);
            $stmt->bindParam(':fecha_fin', $fechaFin);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAccionesMasComunes($limite = 10) {
        $query = "SELECT 
                    SUBSTRING_INDEX(Accion, ' ', 2) as tipo_accion,
                    COUNT(*) as cantidad
                  FROM " . $this->table_name . " 
                  GROUP BY tipo_accion 
                  ORDER BY cantidad DESC 
                  LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getActividadPorUsuario($fechaInicio = null, $fechaFin = null) {
        $whereClause = "";
        if ($fechaInicio && $fechaFin) {
            $whereClause = "WHERE DATE(b.Fecha_Hora) BETWEEN :fecha_inicio AND :fecha_fin";
        }

        $query = "SELECT 
                    u.Usuario,
                    COUNT(*) as total_acciones,
                    MIN(b.Fecha_Hora) as primera_sesion,
                    MAX(b.Fecha_Hora) as ultima_sesion
                  FROM " . $this->table_name . " b
                  LEFT JOIN TB_Usuarios u ON b.ID_Usuario = u.ID_Usuario
                  " . $whereClause . "
                  GROUP BY b.ID_Usuario, u.Usuario
                  ORDER BY total_acciones DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($fechaInicio && $fechaFin) {
            $stmt->bindParam(':fecha_inicio', $fechaInicio);
            $stmt->bindParam(':fecha_fin', $fechaFin);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function deleteOldRecords($diasAntiguedad = 90) {
        $query = "DELETE FROM " . $this->table_name . " WHERE Fecha_Hora < DATE_SUB(NOW(), INTERVAL :dias DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':dias', $diasAntiguedad);
        return $stmt->execute();
    }
}
?>
