<?php
/**
 * controllers/DoctorController.php
 */
require_once BASE_PATH . 'models/User.php';
require_once BASE_PATH . 'models/Doctor.php';
require_once BASE_PATH . 'models/Patient.php';
require_once BASE_PATH . 'models/Appointment.php';
require_once BASE_PATH . 'models/MedicalRecord.php';
require_once BASE_PATH . 'models/Prescription.php';
require_once BASE_PATH . 'models/Urgency.php';
require_once BASE_PATH . 'models/Schedule.php';

class DoctorController {
    private Doctor        $doctorModel;
    private Patient       $patientModel;
    private Appointment   $appointmentModel;
    private MedicalRecord $recordModel;
    private Prescription  $prescriptionModel;
    private Urgency       $urgencyModel;
    private Schedule      $scheduleModel;
    private User          $userModel;
    private array         $doctor;

    public function __construct(private PDO $pdo) {
        $this->doctorModel       = new Doctor($pdo);
        $this->patientModel      = new Patient($pdo);
        $this->appointmentModel  = new Appointment($pdo);
        $this->recordModel       = new MedicalRecord($pdo);
        $this->prescriptionModel = new Prescription($pdo);
        $this->urgencyModel      = new Urgency($pdo);
        $this->scheduleModel     = new Schedule($pdo);
        $this->userModel         = new User($pdo);

        $this->doctor = $this->doctorModel->findByUserId($_SESSION['user_id']) ?? [];
    }

    private function notifCount(): int {
        return $this->userModel->countUnreadNotifications($_SESSION['user_id']);
    }

    public function dashboard(): void {
        $doctor         = $this->doctor;
        $todayAppts     = $this->appointmentModel->getByDoctor($doctor['id'] ?? 0, date('Y-m-d'));
        $urgencies      = $this->urgencyModel->getActive();
        $stats          = $this->doctorModel->getStats($doctor['id'] ?? 0);
        $urgencyCount   = $this->urgencyModel->countWaiting();
        $flash          = getFlash();
        $notifCount     = $this->notifCount();
        $pageTitle      = 'Mi Dashboard';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/doctor/dashboard.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    public function appointments(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAppointmentAction();
            return;
        }
        $doctor = $this->doctor;
        $appointments = $this->appointmentModel->getByDoctor($doctor['id'] ?? 0);
        $notifCount = $this->notifCount();
        $pageTitle = 'Gestión de Citas';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/doctor/appointments.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function handleAppointmentAction(): void {
        if (!validateCsrf()) {
            redirect('index.php?module=doctor&action=appointments', 'Token inválido.', 'error');
        }
        $id     = (int)($_POST['appointment_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        
        $appt = $this->appointmentModel->findById($id);
        if (!$appt || $appt['doctor_id'] != $this->doctor['id']) {
            redirect('index.php?module=doctor&action=appointments', 'Cita no encontrada.', 'error');
        }

        if ($action === 'confirm') {
            $this->appointmentModel->updateStatus($id, 'confirmed');
            
            // Notificar al paciente
            $this->userModel->createNotification(
                $appt['patient_user_id'], 
                'Cita Confirmada', 
                "El Dr. {$this->doctor['name']} ha confirmado tu cita para el {$appt['appointment_date']}."
            );
            
            setFlash('Cita confirmada correctamente.', 'success');
        } elseif ($action === 'cancel') {
            $reason = htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES);
            $this->appointmentModel->updateStatus($id, 'cancelled', ['cancellation_reason' => $reason]);
            
            // Notificar al paciente
            $this->userModel->createNotification(
                $appt['patient_user_id'], 
                'Cita Cancelada', 
                "Tu cita para el {$appt['appointment_date']} ha sido cancelada por el doctor."
            );
            
            setFlash('Cita cancelada.', 'info');
        }
        $redirect = htmlspecialchars($_POST['redirect_to'] ?? 'appointments', ENT_QUOTES);
        header('Location: index.php?module=doctor&action=' . $redirect);
        exit;
    }

    public function schedule(): void {
        $doctor    = $this->doctor;
        $weekStart = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrf()) {
                redirect('index.php?module=doctor&action=schedule&week=' . $weekStart, 'Token no válido.', 'error');
            }
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $data = [
                    'patient_id'       => (int)$_POST['patient_id'],
                    'doctor_id'        => $doctor['id'] ?? 0,
                    'hospital_id'      => (int)$_POST['hospital_id'],
                    'appointment_date' => $_POST['appointment_date'],
                    'appointment_time' => $_POST['appointment_time'] . ':00',
                    'reason'           => htmlspecialchars(trim($_POST['reason'] ?? ''), ENT_QUOTES),
                    'status'           => 'confirmed',
                    'type'             => 'consultation'
                ];
                $this->appointmentModel->create($data);
                
                // Notify patient
                $patient = $this->patientModel->findById($data['patient_id']);
                if ($patient) {
                    $this->userModel->createNotification(
                        $patient['user_id'],
                        'Nueva Cita Agendada',
                        "El Dr. {$doctor['name']} ha agendado una cita para ti el {$data['appointment_date']} a las {$_POST['appointment_time']}."
                    );
                }
                
                redirect('index.php?module=doctor&action=schedule&week=' . ($_POST['week_start'] ?? $weekStart), 'Cita agendada correctamente.', 'success');
            }
        }

        $rawAppts = $this->appointmentModel->getWeeklyForDoctor($doctor['id'] ?? 0, $weekStart);
        $weekAppts = [];
        foreach ($rawAppts as $appt) {
            $date = $appt['appointment_date'];
            $time = date('H:i', strtotime($appt['appointment_time']));
            $weekAppts[$date][$time] = $appt;
        }
        $notifCount= $this->notifCount();
        
        $patients  = $this->patientModel->getAll();
        require_once BASE_PATH . 'models/Hospital.php';
        $hospitalModel = new Hospital($this->pdo);
        $hospitals = $hospitalModel->getAll(true);

        $pageTitle = 'Mi Agenda';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/doctor/schedule.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    public function patients(): void {
        $doctor    = $this->doctor;
        $search    = htmlspecialchars(trim($_GET['q'] ?? ''), ENT_QUOTES);
        $patients  = $search ? $this->patientModel->search($search) : $this->patientModel->getAll();
        $selected  = null;
        $records   = [];
        $activeRx  = [];

        if (!empty($_GET['patient_id'])) {
            $pid      = (int)$_GET['patient_id'];
            $selected = $this->patientModel->findById($pid);
            $records  = $this->recordModel->getByPatient($pid);
            $activeRx = $this->prescriptionModel->getByPatient($pid, true);
        }

        // POST: crear historial clínico
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_record') {
            $this->createRecord();
            return;
        }

        $notifCount = $this->notifCount();
        $pageTitle  = 'Mis Pacientes';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/doctor/patients.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function createRecord(): void {
        if (!validateCsrf()) {
            redirect('index.php?module=doctor&action=patients', 'Token inválido.', 'error');
        }
        $patientId   = (int)($_POST['patient_id'] ?? 0);
        $apptId      = !empty($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null;
        $recordType  = $_POST['record_type'] ?? 'consultation';
        $diagnosis   = htmlspecialchars(trim($_POST['diagnosis'] ?? ''), ENT_QUOTES);
        $treatment   = htmlspecialchars(trim($_POST['treatment'] ?? ''), ENT_QUOTES);
        $observations= htmlspecialchars(trim($_POST['observations'] ?? ''), ENT_QUOTES);

        $vitals = null;
        if (!empty($_POST['blood_pressure'])) {
            $vitals = [
                'blood_pressure' => htmlspecialchars($_POST['blood_pressure']),
                'heart_rate'     => (int)($_POST['heart_rate'] ?? 0),
                'temperature'    => (float)($_POST['temperature'] ?? 0),
                'weight'         => (float)($_POST['weight'] ?? 0),
            ];
        }

        $recordId = $this->recordModel->create([
            'patient_id'     => $patientId,
            'doctor_id'      => $this->doctor['id'],
            'appointment_id' => $apptId,
            'record_type'    => $recordType,
            'diagnosis'      => $diagnosis,
            'treatment'      => $treatment,
            'observations'   => $observations,
            'vital_signs'    => $vitals,
        ]);

        // Crear receta si se enviaron medicamentos
        if (!empty($_POST['medications']) && is_array($_POST['medications'])) {
            $meds = [];
            foreach ($_POST['medications'] as $med) {
                if (!empty($med['name'])) {
                    $meds[] = [
                        'name'      => htmlspecialchars($med['name']),
                        'dose'      => htmlspecialchars($med['dose'] ?? ''),
                        'frequency' => htmlspecialchars($med['frequency'] ?? ''),
                        'duration'  => htmlspecialchars($med['duration'] ?? ''),
                    ];
                }
            }
            if (!empty($meds)) {
                $this->prescriptionModel->create([
                    'medical_record_id' => $recordId,
                    'doctor_id'         => $this->doctor['id'],
                    'patient_id'        => $patientId,
                    'medications'       => $meds,
                    'instructions'      => htmlspecialchars(trim($_POST['rx_instructions'] ?? ''), ENT_QUOTES),
                    'issue_date'        => date('Y-m-d'),
                    'expiry_date'       => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                ]);
            }
        }

        // Marcar cita como completada
        if ($apptId) $this->appointmentModel->updateStatus($apptId, 'completed');

        redirect("index.php?module=doctor&action=patients&patient_id=$patientId", 'Registro clínico guardado.', 'success');
    }

    public function records(): void {
        $doctor     = $this->doctor;
        $records    = $this->recordModel->getByPatient((int)($_GET['patient_id'] ?? 0));
        $notifCount = $this->notifCount();
        $pageTitle  = 'Historial Clínico';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/doctor/medical_records.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    public function urgencies(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUrgencyAction();
            return;
        }
        $urgencies  = $this->urgencyModel->getAll();
        $patients   = $this->patientModel->getAll();
        $triageMap  = Urgency::$TRIAGE_COLORS;
        $notifCount = $this->notifCount();
        $pageTitle  = 'Urgencias';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/doctor/urgencies.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function handleUrgencyAction(): void {
        if (!validateCsrf()) {
            redirect('index.php?module=doctor&action=urgencies', 'Token inválido.', 'error');
        }
        $action = $_POST['action'] ?? '';
        $id     = (int)($_POST['urgency_id'] ?? 0);

        if ($action === 'attend') {
            $this->urgencyModel->attend($id, $this->doctor['id']);
            redirect('index.php?module=doctor&action=urgencies', 'Urgencia asignada.', 'success');
        }
        if ($action === 'discharge') {
            $notes = htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES);
            $this->urgencyModel->discharge($id, $notes);
            redirect('index.php?module=doctor&action=urgencies', 'Paciente dado de alta.', 'success');
        }
        if ($action === 'create') {
            $data = [
                'patient_id'      => !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null,
                'triage_level'    => htmlspecialchars($_POST['triage_level'] ?? '', ENT_QUOTES),
                'chief_complaint' => htmlspecialchars(trim($_POST['chief_complaint'] ?? ''), ENT_QUOTES),
                'status'          => htmlspecialchars($_POST['status'] ?? 'waiting', ENT_QUOTES),
            ];
            $this->urgencyModel->create($data);
            redirect('index.php?module=doctor&action=urgencies', 'Nueva urgencia agregada.', 'success');
        }
        if ($action === 'update') {
            $data = [
                'patient_id'      => !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null,
                'triage_level'    => htmlspecialchars($_POST['triage_level'] ?? '', ENT_QUOTES),
                'chief_complaint' => htmlspecialchars(trim($_POST['chief_complaint'] ?? ''), ENT_QUOTES),
                'status'          => htmlspecialchars($_POST['status'] ?? 'waiting', ENT_QUOTES),
                'notes'           => htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES),
            ];
            $this->urgencyModel->update($id, $data);
            redirect('index.php?module=doctor&action=urgencies', 'Urgencia modificada correctamente.', 'success');
        }
        if ($action === 'delete') {
            $this->urgencyModel->delete($id);
            redirect('index.php?module=doctor&action=urgencies', 'Urgencia eliminada correctamente.', 'success');
        }
        redirect('index.php?module=doctor&action=urgencies');
    }
}
