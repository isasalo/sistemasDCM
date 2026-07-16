<?php
/**
 * controllers/AdminController.php
 */
require_once BASE_PATH . 'models/User.php';
require_once BASE_PATH . 'models/Doctor.php';
require_once BASE_PATH . 'models/Patient.php';
require_once BASE_PATH . 'models/Appointment.php';
require_once BASE_PATH . 'models/Payment.php';
require_once BASE_PATH . 'models/Schedule.php';
require_once BASE_PATH . 'models/Urgency.php';
require_once BASE_PATH . 'models/Hospital.php';
require_once BASE_PATH . 'models/Specialty.php';

class AdminController {
    private Doctor      $doctorModel;
    private Patient     $patientModel;
    private Appointment $appointmentModel;
    private Payment     $paymentModel;
    private Schedule    $scheduleModel;
    private Urgency     $urgencyModel;
    private User        $userModel;
    private Hospital    $hospitalModel;
    private Specialty   $specialtyModel;

    public function __construct(private PDO $pdo) {
        $this->doctorModel      = new Doctor($pdo);
        $this->patientModel     = new Patient($pdo);
        $this->appointmentModel = new Appointment($pdo);
        $this->paymentModel     = new Payment($pdo);
        $this->scheduleModel    = new Schedule($pdo);
        $this->urgencyModel     = new Urgency($pdo);
        $this->userModel        = new User($pdo);
        $this->hospitalModel    = new Hospital($pdo);
        $this->specialtyModel   = new Specialty($pdo);
    }

    private function notifCount(): int {
        return $this->userModel->countUnreadNotifications($_SESSION['user_id']);
    }

    public function dashboard(): void {
        $totalPatients  = $this->patientModel->countTotal();
        $totalDoctors   = count($this->doctorModel->getAll(false));
        $apptStats      = $this->appointmentModel->countByStatus();
        $financial      = $this->paymentModel->getFinancialSummary();
        $urgencyCount   = $this->urgencyModel->countActive();
        $recentPayments = $this->paymentModel->getAll(['date_from' => date('Y-m-d', strtotime('-7 days'))]);
        $flash          = getFlash();
        $notifCount     = $this->notifCount();
        $pageTitle      = 'Panel de Control';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/admin/dashboard.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    public function doctors(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleDoctorPost();
            return;
        }
        $doctors      = $this->doctorModel->getAll(false);
        $specialties  = $this->doctorModel->getAllSpecialties();
        $flash        = getFlash();
        $notifCount   = $this->notifCount();
        $pageTitle    = 'Gestión de Doctores';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/admin/doctors.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function handleDoctorPost(): void {
        if (!validateCsrf()) {
            redirect('index.php?module=admin&action=doctors', 'Token inválido.', 'error');
        }
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $name        = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES);
            $email       = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $phone       = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES);
            $specialtyId = (int)($_POST['specialty_id'] ?? 0);
            $license     = htmlspecialchars(trim($_POST['license_number'] ?? ''), ENT_QUOTES);
            $bio         = htmlspecialchars(trim($_POST['bio'] ?? ''), ENT_QUOTES);
            $fee         = (float)($_POST['consultation_fee'] ?? 0);
            $password    = $_POST['password'] ?? 'doctor123';

            if (!$name || !$email || !$specialtyId || !$license) {
                redirect('index.php?module=admin&action=doctors', 'Completa todos los campos requeridos.', 'warning');
            }

            $userId   = $this->userModel->create(['name'=>$name,'email'=>$email,'password'=>$password,'role'=>'doctor','phone'=>$phone]);
            $this->doctorModel->create($userId, $specialtyId, $license, $bio, $fee);
            redirect('index.php?module=admin&action=doctors', 'Doctor creado exitosamente.', 'success');
        }

        if ($action === 'toggle') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $active = (int)($_POST['active'] ?? 0);
            $this->userModel->update($userId, ['active' => $active ? 0 : 1]);
            redirect('index.php?module=admin&action=doctors', 'Estado actualizado.', 'success');
        }

        if ($action === 'update') {
            $doctorId    = (int)($_POST['doctor_id'] ?? 0);
            $userId      = (int)($_POST['user_id'] ?? 0);
            $specialtyId = (int)($_POST['specialty_id'] ?? 0);
            $fee         = (float)($_POST['consultation_fee'] ?? 0);
            $bio         = htmlspecialchars(trim($_POST['bio'] ?? ''), ENT_QUOTES);
            $phone       = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES);

            $this->userModel->update($userId, ['phone' => $phone]);
            $this->doctorModel->update($doctorId, ['specialty_id'=>$specialtyId,'bio'=>$bio,'consultation_fee'=>$fee]);
            redirect('index.php?module=admin&action=doctors', 'Doctor actualizado.', 'success');
        }
    }

    public function patients(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePatientAction();
            return;
        }
        $patients = $this->patientModel->getAll();
        $pageTitle = 'Gestión de Pacientes';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/admin/patients.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function handlePatientAction(): void {
        $action = $_POST['action'] ?? '';
        if ($action === 'create' || $action === 'edit') {
            $name  = $_POST['name'];
            $email = $_POST['email'];
            $role  = 'patient';
            
            $userId = (int)($_POST['user_id'] ?? 0);
            
            try {
                if ($action === 'create') {
                    $newUserId = $this->userModel->create([
                        'name'     => $name,
                        'email'    => $email,
                        'password' => $_POST['password'], // Se hashea en el modelo
                        'role'     => 'patient'
                    ]);
                    $this->patientModel->create($newUserId, [
                        'birth_date' => $_POST['birth_date'],
                        'blood_type' => $_POST['blood_type']
                    ]);
                    setFlash('Paciente creado con éxito.', 'success');
                } else {
                    $this->userModel->update($userId, ['name' => $name, 'email' => $email]);
                    if (!empty($_POST['password'])) {
                        $this->userModel->updatePassword($userId, $_POST['password']);
                    }
                    
                    $patientId = (int)$_POST['patient_id'];
                    $this->patientModel->update($patientId, [
                        'birth_date' => $_POST['birth_date'],
                        'blood_type' => $_POST['blood_type']
                    ]);
                    setFlash('Paciente actualizado.', 'success');
                }
            } catch (Exception $e) {
                setFlash('Error: ' . $e->getMessage(), 'error');
            }
        }
        header('Location: index.php?module=admin&action=patients');
        exit;
    }

    public function schedules(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveSchedule();
            return;
        }
        $doctors    = $this->doctorModel->getAll(false);
        $selected   = null;
        $schedules  = [];
        if (!empty($_GET['doctor_id'])) {
            $did      = (int)$_GET['doctor_id'];
            $selected = $this->doctorModel->findById($did);
            $schedules= $this->scheduleModel->getByDoctor($did);
        }
        $flash      = getFlash();
        $notifCount = $this->notifCount();
        $pageTitle  = 'Gestión de Horarios';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/admin/schedules.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function saveSchedule(): void {
        if (!validateCsrf()) {
            redirect('index.php?module=admin&action=schedules', 'Token inválido.', 'error');
        }
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $days     = $_POST['days'] ?? [];

        if (!$doctorId) {
            redirect('index.php?module=admin&action=schedules', 'Doctor no válido.', 'error');
        }

        $parsed = [];
        foreach ($days as $day) {
            if (isset($day['enabled']) && $day['enabled'] === 'on') {
                $parsed[] = [
                    'day'   => (int)$day['day'],
                    'start' => $day['start'] ?? '08:00',
                    'end'   => $day['end']   ?? '17:00',
                    'slot'  => (int)($day['slot'] ?? 30),
                ];
            }
        }

        $this->scheduleModel->replaceAll($doctorId, $parsed);
        redirect("index.php?module=admin&action=schedules&doctor_id=$doctorId", 'Horarios guardados.', 'success');
    }

    public function finances(): void {
        $filters        = [
            'status'    => $_GET['status']    ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
        ];
        $payments       = $this->paymentModel->getAll(array_filter($filters));
        $financial      = $this->paymentModel->getFinancialSummary();
        $services       = $this->paymentModel->getAllServices();
        $flash          = getFlash();
        $notifCount     = $this->notifCount();
        $pageTitle      = 'Gestión Financiera';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/admin/finances.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    public function hospitals(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleHospitalPost();
            return;
        }
        $hospitals  = $this->hospitalModel->getAll(false);
        $flash      = getFlash();
        $notifCount = $this->notifCount();
        $pageTitle  = 'Gestión de Hospitales';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/admin/hospitals.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function handleHospitalPost(): void {
        if (!validateCsrf()) {
            redirect('index.php?module=admin&action=hospitals', 'Token inválido.', 'error');
        }
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $data = [
                'name'    => htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES),
                'address' => htmlspecialchars(trim($_POST['address'] ?? ''), ENT_QUOTES),
                'city'    => htmlspecialchars(trim($_POST['city'] ?? ''), ENT_QUOTES),
                'phone'   => htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES),
            ];
            $this->hospitalModel->create($data);
            redirect('index.php?module=admin&action=hospitals', 'Hospital añadido exitosamente.', 'success');
        }

        if ($action === 'update') {
            $id = (int)($_POST['hospital_id'] ?? 0);
            $data = [
                'name'    => htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES),
                'address' => htmlspecialchars(trim($_POST['address'] ?? ''), ENT_QUOTES),
                'city'    => htmlspecialchars(trim($_POST['city'] ?? ''), ENT_QUOTES),
                'phone'   => htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES),
            ];
            $this->hospitalModel->update($id, $data);
            redirect('index.php?module=admin&action=hospitals', 'Hospital actualizado.', 'success');
        }

        if ($action === 'toggle') {
            $id = (int)($_POST['hospital_id'] ?? 0);
            $this->hospitalModel->toggleActive($id);
            redirect('index.php?module=admin&action=hospitals', 'Estado del hospital actualizado.', 'success');
        }

        if ($action === 'delete') {
            $id = (int)($_POST['hospital_id'] ?? 0);
            try {
                $this->hospitalModel->delete($id);
                redirect('index.php?module=admin&action=hospitals', 'Hospital eliminado exitosamente.', 'success');
            } catch (PDOException $e) {
                redirect('index.php?module=admin&action=hospitals', 'No se puede eliminar el hospital porque está asociado a citas médicas u otros registros. Intenta desactivarlo.', 'error');
            }
        }
    }

    public function specialties(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSpecialtyPost();
            return;
        }
        $specialties = $this->specialtyModel->getAll();
        $flash       = getFlash();
        $notifCount  = $this->notifCount();
        $pageTitle   = 'Gestión de Especialidades';
        require BASE_PATH . 'views/layouts/header.php';
        require BASE_PATH . 'views/admin/specialties.php';
        require BASE_PATH . 'views/layouts/footer.php';
    }

    private function handleSpecialtyPost(): void {
        if (!validateCsrf()) {
            redirect('index.php?module=admin&action=specialties', 'Token inválido.', 'error');
        }
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $data = [
                'name'        => htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES),
                'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES),
                'icon'        => htmlspecialchars(trim($_POST['icon'] ?? 'fa-stethoscope'), ENT_QUOTES),
            ];
            if (empty($data['name'])) {
                redirect('index.php?module=admin&action=specialties', 'El nombre es obligatorio.', 'warning');
            }
            $this->specialtyModel->create($data);
            redirect('index.php?module=admin&action=specialties', 'Especialidad añadida exitosamente.', 'success');
        }

        if ($action === 'update') {
            $id = (int)($_POST['specialty_id'] ?? 0);
            $data = [
                'name'        => htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES),
                'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES),
                'icon'        => htmlspecialchars(trim($_POST['icon'] ?? 'fa-stethoscope'), ENT_QUOTES),
            ];
            if (empty($data['name'])) {
                redirect('index.php?module=admin&action=specialties', 'El nombre es obligatorio.', 'warning');
            }
            $this->specialtyModel->update($id, $data);
            redirect('index.php?module=admin&action=specialties', 'Especialidad actualizada.', 'success');
        }

        if ($action === 'delete') {
            $id = (int)($_POST['specialty_id'] ?? 0);
            try {
                $this->specialtyModel->delete($id);
                redirect('index.php?module=admin&action=specialties', 'Especialidad eliminada exitosamente.', 'success');
            } catch (Exception $e) {
                redirect('index.php?module=admin&action=specialties', $e->getMessage(), 'error');
            }
        }
    }
}
