<?php
/**
 * api/doctors.php
 */
require_once '../config/database.php';
require_once '../models/Doctor.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$doctorModel = new Doctor($pdo);

$date = $_GET['date'] ?? '';
$specialtyId = (int)($_GET['specialty_id'] ?? 0);

try {
    // Return doctors that have a schedule for that day of week
    $doctors = $doctorModel->getAvailableByDate($date, $specialtyId ?: null);
    
    $formatted = array_map(function($d) {
        return [
            'id' => $d['id'],
            'name' => $d['name'],
            'specialty' => $d['specialty_name'],
            'fee' => $d['consultation_fee'],
            'avatar' => $d['avatar']
        ];
    }, $doctors);

    echo json_encode(['success' => true, 'data' => $formatted]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
