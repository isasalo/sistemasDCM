<?php
/**
 * api/appointments.php
 */
require_once '../config/database.php';
require_once '../models/Appointment.php';
require_once '../models/Urgency.php';
require_once '../models/User.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$appointmentModel = new Appointment($pdo);
$urgencyModel = new Urgency($pdo);
$userModel = new User($pdo);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'slots':
        $date = $_GET['date'] ?? '';
        $doctorId = (int)($_GET['doctor_id'] ?? 0);
        if (!$date || !$doctorId) {
            echo json_encode(['success' => false, 'message' => 'Missing params']);
            break;
        }
        $available = $appointmentModel->getAvailableSlots($doctorId, $date);
        $booked = $appointmentModel->getBookedSlots($doctorId, $date);
        echo json_encode(['success' => true, 'data' => ['available' => $available, 'booked' => $booked]]);
        break;

    case 'urgency_count':
        $count = $urgencyModel->countWaiting();
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    case 'notif_count':
        $count = $userModel->countUnreadNotifications($_SESSION['user_id']);
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    case 'reschedule':
        // Handle post for rescheduling (from modal)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrf($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF']);
                break;
            }
            $id = (int)$_POST['appointment_id'];
            $newDate = $_POST['new_date'];
            $newTime = $_POST['new_time'];
            
            $res = $appointmentModel->reschedule($id, $newDate, $newTime);
            $actionRedirect = $_SESSION['user_role'] === 'doctor' ? 'schedule' : 'appointments';
            if ($res) {
                setFlash('Cita reprogramada correctamente.', 'success');
                header('Location: ../index.php?module=' . $_SESSION['user_role'] . '&action=' . $actionRedirect);
            } else {
                setFlash('No se pudo reprogramar la cita.', 'error');
                header('Location: ../index.php?module=' . $_SESSION['user_role'] . '&action=' . $actionRedirect);
            }
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
