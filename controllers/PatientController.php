<?php
/**
 * controllers/PatientController.php
 */
require_once BASE_PATH . 'models/User.php';
require_once BASE_PATH . 'models/Patient.php';
require_once BASE_PATH . 'models/Appointment.php';
require_once BASE_PATH . 'models/Doctor.php';
require_once BASE_PATH . 'models/MedicalRecord.php';
require_once BASE_PATH . 'models/Prescription.php';
require_once BASE_PATH . 'models/Payment.php';
require_once BASE_PATH . 'models/Hospital.php';

class PatientController {
    private Patient $patientModel;
    private Appointment $appointmentModel;
    private Doctor $doctorModel;
    private MedicalRecord $recordModel;
    private Prescription $prescriptionModel;
    private Payment $paymentModel;
    private User $userModel;
    private Hospital $hospitalModel;
    private array $patient;

    public function __construct(private PDO $pdo) {
        $this->patientModel = new Patient($pdo);
        $this->appointmentModel = new Appointment($pdo);
        $this->doctorModel = new Doctor($pdo);
        $this->recordModel = new MedicalRecord($pdo);
        $this->prescriptionModel = new Prescription($pdo);
        $this->paymentModel = new Payment($pdo);
        $this->userModel = new User($pdo);
        $this->hospitalModel = new Hospital($pdo);

        $this->patient = $this->patientModel->findByUserId($_SESSION['user_id']) ?? [];
    }

    private function notifCount(): int {
        return $this->userModel->countUnreadNotifications($_SESSION['user_id']);
    }

    public function dashboard(): void {
        $patient = $this->patient;
        $upcoming = $this->appointmentModel->getByPatient($patient['id'], 'confirmed');
        $totalAppts = count($this->appointmentModel->getByPatient($patient['id']));
        $pendingPayments = count($this->paymentModel->getByPatient($patient['id'], 'pending'));
        $activeRx = count($this->prescriptionModel->getByPatient($patient['id'], true));
        
        $notifCount = $this->notifCount();
        $pageTitle = 'Mi Salud Primero - Dashboard';
        
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/patient/dashboard.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    public function appointments(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAppointmentAction();
            return;
        }

        $patient = $this->patient;
        $appointments = $this->appointmentModel->getByPatient($patient['id']);
        $specialties = $this->doctorModel->getAllSpecialties();
        $hospitals = $this->hospitalModel->getAll();
        
        $notifCount = $this->notifCount();
        $pageTitle = 'Mis Citas';
        
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/patient/appointments.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function handleAppointmentAction(): void {
        if (!validateCsrf()) {
            setFlash('Token de seguridad inválido.', 'error');
            header('Location: index.php?module=patient&action=appointments');
            exit;
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'book') {
            // Validar datos básicos
            if (empty($_POST['doctor_id']) || empty($_POST['appointment_date']) || empty($_POST['appointment_time'])) {
                setFlash('Error: Faltan datos obligatorios para agendar la cita.', 'error');
                header('Location: index.php?module=patient&action=appointments');
                exit;
            }

            try {
                $data = [
                    'patient_id'       => $this->patient['id'],
                    'doctor_id'        => (int)$_POST['doctor_id'],
                    'hospital_id'      => (int)($_POST['hospital_id'] ?? 0),
                    'appointment_date' => $_POST['appointment_date'],
                    'appointment_time' => $_POST['appointment_time'],
                    'reason'           => htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES),
                    'status'           => 'pending',
                    'type'             => 'consultation'
                ];

                $this->appointmentModel->create($data);
                setFlash('¡Cita agendada con éxito! Pendiente de confirmación.', 'success');
            } catch (Exception $e) {
                setFlash('Error al agendar la cita: ' . $e->getMessage(), 'error');
            }
        } elseif ($action === 'cancel') {
            $id = (int)$_POST['appointment_id'];
            $reason = htmlspecialchars($_POST['cancellation_reason'] ?? '', ENT_QUOTES);
            
            if ($this->appointmentModel->updateStatus($id, 'cancelled', ['cancellation_reason' => $reason])) {
                setFlash('Cita cancelada correctamente.', 'success');
            } else {
                setFlash('No se pudo cancelar la cita.', 'error');
            }
        }

        header('Location: index.php?module=patient&action=appointments');
        exit;
    }

    public function health(): void {
        $patient = $this->patient;
        $records = $this->recordModel->getByPatient($patient['id']);
        $prescriptions = $this->prescriptionModel->getByPatient($patient['id']);
        $vitalHistory = $this->recordModel->getVitalHistory($patient['id'], 10);
        
        $notifCount = $this->notifCount();
        $pageTitle = 'Mi Historial de Salud';
        
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/patient/health_info.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    public function payments(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePaymentAction();
            return;
        }

        $patient = $this->patient;
        $payments = $this->paymentModel->getByPatient($patient['id']);
        
        $notifCount = $this->notifCount();
        $pageTitle = 'Mis Pagos';
        
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/patient/payments.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function handlePaymentAction(): void {
        if (!validateCsrf()) {
            setFlash('Token de seguridad inválido.', 'error');
            header('Location: index.php?module=patient&action=payments');
            exit;
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'pay') {
            $id = (int)$_POST['payment_id'];
            $method = $_POST['payment_method'] ?? 'card';

            if ($this->paymentModel->markAsPaid($id, $method)) {
                setFlash('Pago procesado correctamente.', 'success');
            } else {
                setFlash('Error al procesar el pago.', 'error');
            }
        }

        header('Location: index.php?module=patient&action=payments');
        exit;
    }
}
