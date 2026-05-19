<?php
// api/get_guides.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db/db.php';

try {
    $db = Database::getInstance();
    
    // Obtener parámetros de filtro
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $ciudad = isset($_GET['ciudad']) ? trim($_GET['ciudad']) : '';
    
    // Construir consulta base
    $query = "SELECT * FROM guias WHERE 1=1";
    $params = [];
    
    if (!empty($estado)) {
        $query .= " AND estado = :estado";
        $params[':estado'] = $estado;
    }
    
    if (!empty($ciudad)) {
        $query .= " AND ciudad_destino = :ciudad";
        $params[':ciudad'] = $ciudad;
    }
    
    $query .= " ORDER BY fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener contadores consolidados
    $stmtCounters = $db->query("
        SELECT 
            SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN estado = 'ENTREGADO' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN estado = 'DEVUELTO' THEN 1 ELSE 0 END) as returned
        FROM guias
    ");
    $counters = $stmtCounters->fetch(PDO::FETCH_ASSOC);
    
    // Asegurar que los contadores sean enteros
    $counters['pending'] = (int)($counters['pending'] ?? 0);
    $counters['delivered'] = (int)($counters['delivered'] ?? 0);
    $counters['returned'] = (int)($counters['returned'] ?? 0);
    
    // Obtener ciudades distintas para el select del filtro
    $stmtCities = $db->query("SELECT DISTINCT ciudad_destino FROM guias ORDER BY ciudad_destino ASC");
    $cities = $stmtCities->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'guides' => $guides,
        'counters' => $counters,
        'cities' => $cities
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al consultar guías: ' . $e->getMessage()
    ]);
}
