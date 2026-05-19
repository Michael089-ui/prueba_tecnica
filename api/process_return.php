<?php
// api/process_return.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db/db.php';

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Método de solicitud no válido.');
}

try {
    $db = Database::getInstance();
    
    // ----------------------------------------------------
    // 1. Validar ID de la Guía
    // ----------------------------------------------------
    $guia_id = isset($_POST['guia_id']) ? (int)$_POST['guia_id'] : 0;
    if ($guia_id <= 0) {
        respond(false, 'ID de guía no válido.');
    }
    
    // Consultar guía y verificar estado
    $stmtGuia = $db->prepare("SELECT * FROM guias WHERE id = :id FOR UPDATE");
    $stmtGuia->execute([':id' => $guia_id]);
    $guia = $stmtGuia->fetch(PDO::FETCH_ASSOC);
    
    if (!$guia) {
        respond(false, 'La guía de transporte no existe.');
    }
    
    if ($guia['estado'] === 'ENTREGADO') {
        respond(false, 'No se puede devolver una guía que ya ha sido ENTREGADA.');
    }
    
    // Iniciar Transacción
    $db->beginTransaction();
    
    // ----------------------------------------------------
    // 2. Validar Datos de Entrada
    // ----------------------------------------------------
    $motivo_devolucion = isset($_POST['motivo_devolucion']) ? trim($_POST['motivo_devolucion']) : '';
    if (empty($motivo_devolucion)) {
        respond(false, 'El motivo de la devolución es obligatorio.');
    }
    
    $observacion = isset($_POST['observacion']) ? trim($_POST['observacion']) : '';
    
    // Geolocalización (tolerancia a fallos)
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT) : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT) : null;
    
    // ----------------------------------------------------
    // 3. Crear Carpetas de Subidas
    // ----------------------------------------------------
    $baseUploadDir = __DIR__ . '/../uploads';
    $evidenceDir = $baseUploadDir . '/evidence';
    $signaturesDir = $baseUploadDir . '/signatures';
    
    if (!is_dir($baseUploadDir)) {
        mkdir($baseUploadDir, 0755, true);
    }
    if (!is_dir($evidenceDir)) {
        mkdir($evidenceDir, 0755, true);
    }
    if (!is_dir($signaturesDir)) {
        mkdir($signaturesDir, 0755, true);
    }
    
    $maxFileSize = 5 * 1024 * 1024; // 5 MB
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    
    // ----------------------------------------------------
    // 4. Procesar Foto Evidencia de Devolución
    // ----------------------------------------------------
    if (!isset($_FILES['pod_image']) || $_FILES['pod_image']['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'La foto de evidencia de devolución es obligatoria y no pudo subirse.');
    }
    
    $podFile = $_FILES['pod_image'];
    
    if ($podFile['size'] > $maxFileSize) {
        respond(false, 'La foto de evidencia supera el límite permitido de 5MB.');
    }
    
    $realPodMimeType = $finfo->file($podFile['tmp_name']);
    if (!in_array($realPodMimeType, $allowedMimeTypes)) {
        respond(false, 'Formato de foto de evidencia no válido. Solo se permiten archivos JPG y PNG.');
    }
    
    if (getimagesize($podFile['tmp_name']) === false) {
        respond(false, 'El archivo subido como evidencia no es una imagen válida.');
    }
    
    $podExt = strtolower(pathinfo($podFile['name'], PATHINFO_EXTENSION));
    if (!in_array($podExt, ['jpg', 'jpeg', 'png'])) {
        $podExt = ($realPodMimeType === 'image/png') ? 'png' : 'jpg';
    }
    $podFilename = 'ret_' . bin2hex(random_bytes(16)) . '.' . $podExt;
    $podSavePath = $evidenceDir . '/' . $podFilename;
    $podRelativePath = 'uploads/evidence/' . $podFilename;
    
    // ----------------------------------------------------
    // 5. Procesar Firma Digital (Soporta archivo binary o base64 $_POST)
    // ----------------------------------------------------
    $firmaFilename = 'sig_' . bin2hex(random_bytes(16)) . '.png';
    $firmaSavePath = $signaturesDir . '/' . $firmaFilename;
    $firmaRelativePath = 'uploads/signatures/' . $firmaFilename;
    $firmaGuardada = false;
    
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
        $sigFile = $_FILES['signature'];
        if ($sigFile['size'] > $maxFileSize) {
            respond(false, 'La firma digital es demasiado grande.');
        }
        $realSigMimeType = $finfo->file($sigFile['tmp_name']);
        if ($realSigMimeType !== 'image/png') {
            respond(false, 'La firma digital debe ser una imagen PNG.');
        }
        if (getimagesize($sigFile['tmp_name']) === false) {
            respond(false, 'La firma digital no es una imagen válida.');
        }
        if (move_uploaded_file($sigFile['tmp_name'], $firmaSavePath)) {
            $firmaGuardada = true;
        }
    } 
    elseif (isset($_POST['signature_base64']) && !empty($_POST['signature_base64'])) {
        $base64String = $_POST['signature_base64'];
        if (!preg_match('/^data:image\/png;base64,(.*)$/i', $base64String, $matches)) {
            respond(false, 'Formato de firma digital Base64 no válido.');
        }
        $decodedData = base64_decode($matches[1]);
        if ($decodedData === false) {
            respond(false, 'Error al decodificar la firma digital.');
        }
        if (strlen($decodedData) > $maxFileSize) {
            respond(false, 'La firma digital supera el límite de tamaño.');
        }
        if (getimagesizefromstring($decodedData) === false) {
            respond(false, 'El contenido decodificado de la firma no es una imagen válida.');
        }
        if (file_put_contents($firmaSavePath, $decodedData) !== false) {
            $firmaGuardada = true;
        }
    }
    
    if (!$firmaGuardada) {
        respond(false, 'La firma digital es obligatoria y no pudo guardarse.');
    }
    
    // Guardar imagen de evidencia
    if (!move_uploaded_file($podFile['tmp_name'], $podSavePath)) {
        if (file_exists($firmaSavePath)) {
            unlink($firmaSavePath);
        }
        respond(false, 'Error al guardar la foto de evidencia en el servidor.');
    }
    
    // ----------------------------------------------------
    // 6. Registrar en Base de Datos
    // ----------------------------------------------------
    // Crear registro de devolución
    $stmtInsert = $db->prepare("
        INSERT INTO registros_guia 
        (guia_id, tipo_registro, motivo_devolucion, observacion, foto_path, firma_path, latitud, longitud)
        VALUES 
        (:guia_id, 'DEVOLUCION', :motivo_devolucion, :observacion, :foto_path, :firma_path, :latitud, :longitud)
    ");
    $stmtInsert->execute([
        ':guia_id' => $guia_id,
        ':motivo_devolucion' => $motivo_devolucion,
        ':observacion' => $observacion,
        ':foto_path' => $podRelativePath,
        ':firma_path' => $firmaRelativePath,
        ':latitud' => $latitude,
        ':longitud' => $longitude
    ]);
    
    // Actualizar estado de la guía a DEVUELTO
    $stmtUpdate = $db->prepare("UPDATE guias SET estado = 'DEVUELTO' WHERE id = :id");
    $stmtUpdate->execute([':id' => $guia_id]);
    
    // Confirmar transacción
    $db->commit();
    
    respond(true, 'La guía de transporte ha sido devuelta exitosamente.', [
        'nuevo_estado' => 'DEVUELTO'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    respond(false, 'Error interno del servidor: ' . $e->getMessage());
}
