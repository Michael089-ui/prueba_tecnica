<?php
// db/db.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'prueba_tecnica');

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            // Intentar conectar primero al servidor para verificar/crear la BD si es necesario
            $this->conn = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Crear base de datos si no existe (facilita la inicialización automática)
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->conn->exec("USE `" . DB_NAME . "`");
            
            // Verificar si la tabla 'guias' existe, de lo contrario crearlas automáticamente usando el schema.sql
            $stmt = $this->conn->query("SHOW TABLES LIKE 'guias'");
            if ($stmt->rowCount() == 0) {
                $schemaFile = __DIR__ . '/schema.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    // PDO no puede ejecutar múltiples sentencias CREATE en una sola llamada exec() en algunos entornos,
                    // pero un script SQL estructurado normalmente funciona con exec(). Para estar seguros, ejecutamos el SQL.
                    $this->conn->exec($sql);
                }
            }
        } catch (PDOException $e) {
            // Si la conexión inicial al servidor falla, intentar conectar directamente a la base de datos especificada
            try {
                $this->conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $ex) {
                // Si todo falla, enviar respuesta JSON de error
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error de conexión a la base de datos. Por favor, asegúrese de que MySQL esté ejecutándose y las credenciales en db/db.php sean correctas. Detalle: ' . $ex->getMessage()
                ]);
                exit;
            }
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
