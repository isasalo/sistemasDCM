<?php
/**
 * install.php — Script de instalación único
 * Visitar: http://localhost/proyecto%20hospital/install.php
 * ELIMINAR este archivo después de la instalación
 */

// Prevent timeout
set_time_limit(120);

$user = 'root';
$pass = '';
$messages = [];
$success  = true;

$configs = [
    ['host' => '127.0.0.1', 'port' => '3306'],
    ['host' => 'localhost', 'port' => '3306'],
    ['host' => '127.0.0.1', 'port' => '3307'],
    ['host' => 'localhost', 'port' => '3307'],
];

$pdo = null;
$workingHost = '';

foreach ($configs as $cfg) {
    try {
        $h = $cfg['host'];
        $p = $cfg['port'];
        $pdo = new PDO("mysql:host=$h;port=$p;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ]);
        $workingHost = "$h:$p";
        $messages[] = ['ok', "Conexión exitosa a MySQL en $workingHost"];
        break;
    } catch (PDOException $e) {
        // Continue to next config
    }
}

if (!$pdo) {
    $passText = $pass === '' ? 'vacía' : "'$pass'";
    throw new Exception("No se pudo conectar a MySQL con el usuario '$user' y contraseña $passText en ninguno de los puertos comunes (3306, 3307). Verifica que MySQL esté encendido en el Panel de Control de XAMPP.");
}

try {

    // 2. Crear base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS hospital_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE hospital_db");
    $messages[] = ['ok', 'Base de datos hospital_db creada/verificada'];

    // 3. Ejecutar schema
    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
    // Strip USE/CREATE DATABASE lines (already connected)
    $schema = preg_replace('/^(CREATE DATABASE|USE hospital_db).+$/mi', '', $schema);
    // Split and execute statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) $pdo->exec($stmt);
    }
    $messages[] = ['ok', 'Tablas creadas exitosamente'];

    // 4. Insertar datos de prueba
    // ——— Especialidades ———
    $pdo->exec("DELETE FROM specialties");
    $pdo->exec("ALTER TABLE specialties AUTO_INCREMENT = 1");
    $specs = [
        [1, 'Medicina General', 'Atención primaria y consulta general', 'fa-stethoscope'],
        [2, 'Cardiología',      'Enfermedades del corazón y sistema cardiovascular', 'fa-heart-pulse'],
        [3, 'Pediatría',        'Atención médica para niños y adolescentes', 'fa-child'],
        [4, 'Psicología',       'Apoyo emocional, salud mental y psicoterapia', 'fa-brain'],
        [5, 'Ginecología',      'Cuidado del sistema reproductor femenino y salud de la mujer', 'fa-venus'],
        [6, 'Nutrición',        'Planes de alimentación personalizada y vida saludable', 'fa-apple-whole'],
    ];
    $stmtSpec = $pdo->prepare("INSERT INTO specialties (id, name, description, icon) VALUES (?,?,?,?)");
    foreach ($specs as $s) $stmtSpec->execute($s);
    $messages[] = ['ok', '6 especialidades insertadas'];

    // ——— Hospitales (Antioquia) ———
    $pdo->exec("DELETE FROM hospitals");
    $pdo->exec("ALTER TABLE hospitals AUTO_INCREMENT = 1");
    $hospitals = [
        [1, 'Hospital Universitario San Vicente Fundación', 'Calle 64 #51D-154', 'Medellín', '310-100-0001'],
        [2, 'Hospital Pablo Tobón Uribe', 'Calle 78B #69-240', 'Medellín', '310-100-0002'],
        [3, 'Hospital San Juan de Dios', 'Calle 50 #50-50', 'Rionegro', '310-100-0003'],
        [4, 'Hospital Marco Fidel Suárez', 'Calle 44 #49-31', 'Bello', '310-100-0004'],
    ];
    $stmtHosp = $pdo->prepare("INSERT INTO hospitals (id, name, address, city, phone) VALUES (?,?,?,?,?)");
    foreach ($hospitals as $h) $stmtHosp->execute($h);
    $messages[] = ['ok', '4 hospitales de Antioquia insertados'];

    // ——— Usuarios ———
    $adminPass   = password_hash('admin123',    PASSWORD_BCRYPT);
    $doctorPass  = password_hash('doctor123',   PASSWORD_BCRYPT);
    $patientPass = password_hash('paciente123', PASSWORD_BCRYPT);

    $pdo->exec("DELETE FROM users");
    $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");

    $users = [
        // Admin
        [1, 'Administrador',     'admin@hospital.com',          $adminPass,   'admin',   '310-000-0001', null],
        // Doctores
        [2, 'Samuel Goe',        'samuel.goe@hospital.com',     $doctorPass,  'doctor',  '310-000-0002', null],
        [3, 'Elizabeth Ira',     'elizabeth.ira@hospital.com',  $doctorPass,  'doctor',  '310-000-0003', null],
        [4, 'Tanya Collins',     'tanya.collins@hospital.com',  $doctorPass,  'doctor',  '310-000-0004', null],
        // Pacientes
        [5,  'Carlos García',   'carlos.garcia@email.com',     $patientPass, 'patient', '311-100-0001', null],
        [6,  'María López',     'maria.lopez@email.com',       $patientPass, 'patient', '311-100-0002', null],
        [7,  'Andrés Torres',   'andres.torres@email.com',     $patientPass, 'patient', '311-100-0003', null],
        [8,  'Lucía Martínez',  'lucia.martinez@email.com',    $patientPass, 'patient', '311-100-0004', null],
        [9,  'Felipe Ramírez',  'felipe.ramirez@email.com',    $patientPass, 'patient', '311-100-0005', null],
    ];
    $stmtUser = $pdo->prepare("INSERT INTO users (id,name,email,password,role,phone,avatar) VALUES (?,?,?,?,?,?,?)");
    foreach ($users as $u) $stmtUser->execute($u);
    $messages[] = ['ok', '9 usuarios insertados (1 admin, 3 doctores, 5 pacientes)'];

    // ——— Doctores ———
    $pdo->exec("DELETE FROM doctors");
    $pdo->exec("ALTER TABLE doctors AUTO_INCREMENT = 1");
    $doctors = [
        [1, 2, 1, 'MED-001', 'Médico general con 10 años de experiencia en atención primaria.', 50000.00],
        [2, 3, 2, 'CAR-002', 'Cardióloga especialista en enfermedades coronarias y preventiva.',  80000.00],
        [3, 4, 3, 'PED-003', 'Pediatra dedicada a la salud infantil y adolescente.',               60000.00],
    ];
    $stmtDoc = $pdo->prepare("INSERT INTO doctors (id,user_id,specialty_id,license_number,bio,consultation_fee) VALUES (?,?,?,?,?,?)");
    foreach ($doctors as $d) $stmtDoc->execute($d);
    $messages[] = ['ok', '3 doctores insertados'];

    // ——— Pacientes ———
    $pdo->exec("DELETE FROM patients");
    $pdo->exec("ALTER TABLE patients AUTO_INCREMENT = 1");
    $patients = [
        [1, 5,  '1990-03-15', 'O+',  'Penicilina',    'Ana García',    '311-200-0001', 'Sura',    'SEG-001'],
        [2, 6,  '1985-07-22', 'A+',  null,            'Juan López',    '311-200-0002', 'Colsanitas','SEG-002'],
        [3, 7,  '1998-11-08', 'B+',  'Ibuprofeno',    'Rosa Torres',   '311-200-0003', null,       null     ],
        [4, 8,  '1975-01-30', 'AB-', 'Mariscos',      'Pedro Martínez','311-200-0004', 'Famisanar','SEG-004'],
        [5, 9,  '2002-06-18', 'A-',  null,            'Laura Ramírez', '311-200-0005', 'Compensar','SEG-005'],
    ];
    $stmtPat = $pdo->prepare("INSERT INTO patients (id,user_id,birth_date,blood_type,allergies,emergency_contact_name,emergency_contact_phone,insurance_provider,insurance_number) VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($patients as $p) $stmtPat->execute($p);
    $messages[] = ['ok', '5 pacientes insertados'];

    // ——— Horarios (Lun-Vie 8am-5pm, slot 30min) ———
    $pdo->exec("DELETE FROM schedules");
    $pdo->exec("ALTER TABLE schedules AUTO_INCREMENT = 1");
    $stmtSched = $pdo->prepare("INSERT INTO schedules (doctor_id,day_of_week,start_time,end_time,slot_duration,active) VALUES (?,?,?,?,?,1)");
    foreach ([1,2,3] as $docId) {
        foreach ([1,2,3,4,5] as $day) { // Lun=1 ... Vie=5
            $stmtSched->execute([$docId, $day, '08:00:00', '17:00:00', 30]);
        }
    }
    $messages[] = ['ok', 'Horarios L-V 8am-5pm creados para 3 doctores'];

    // ——— Servicios ———
    $pdo->exec("DELETE FROM services");
    $pdo->exec("ALTER TABLE services AUTO_INCREMENT = 1");
    $services = [
        [1, 'Consulta General',      'Consulta médica de atención primaria',        'consultation', 50000.00, 19.00],
        [2, 'Consulta Especializada', 'Consulta con médico especialista',            'consultation', 80000.00, 19.00],
        [3, 'Examen de Laboratorio', 'Análisis clínicos básicos',                   'exam',         35000.00, 19.00],
        [4, 'Urgencias',             'Atención urgente en sala de emergencias',     'urgency',      90000.00, 19.00],
        [5, 'Consulta Pediátrica',   'Atención médica para niños y adolescentes',  'consultation', 60000.00, 19.00],
    ];
    $stmtSvc = $pdo->prepare("INSERT INTO services (id,name,description,category,base_price,tax_rate) VALUES (?,?,?,?,?,?)");
    foreach ($services as $s) $stmtSvc->execute($s);
    $messages[] = ['ok', '5 servicios insertados'];

    // ——— Citas (10 en diferentes estados) ———
    $pdo->exec("DELETE FROM appointments");
    $pdo->exec("ALTER TABLE appointments AUTO_INCREMENT = 1");
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $nextWeek = date('Y-m-d', strtotime('+7 days'));

    $appointments = [
        [1, 1, 1, 1, $today,     '09:00:00', 'Control de rutina',          'confirmed',  'consultation'],
        [2, 2, 2, 2, $today,     '10:00:00', 'Dolor en el pecho',           'confirmed',  'consultation'],
        [3, 3, 1, 1, $tomorrow,  '11:00:00', 'Fiebre persistente',          'pending',    'consultation'],
        [4, 4, 3, 2, $tomorrow,  '14:00:00', 'Control pediatría',           'pending',    'consultation'],
        [5, 5, 1, 3, $nextWeek,  '08:30:00', 'Revisión general',            'pending',    'consultation'],
        [6, 1, 2, 1, $yesterday, '09:00:00', 'Seguimiento cardiológico',    'completed',  'consultation'],
        [7, 2, 3, 2, $yesterday, '10:30:00', 'Control bebé 6 meses',        'completed',  'consultation'],
        [8, 3, 1, 3, $yesterday, '11:00:00', 'Gripa y tos',                 'cancelled',  'consultation'],
        [9, 4, 2, 4, $nextWeek,  '15:00:00', 'Electrocardiograma',          'confirmed',  'exam'],
        [10,5, 3, 1, $nextWeek,  '09:00:00', 'Vacuna anual',                'pending',    'consultation'],
    ];
    $stmtApp = $pdo->prepare("INSERT INTO appointments (id,patient_id,doctor_id,hospital_id,appointment_date,appointment_time,reason,status,type) VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($appointments as $a) $stmtApp->execute($a);
    $messages[] = ['ok', '10 citas insertadas'];

    // ——— Historial Clínico (5 registros) ———
    $pdo->exec("DELETE FROM medical_records");
    $pdo->exec("ALTER TABLE medical_records AUTO_INCREMENT = 1");
    $vitals1 = json_encode(['blood_pressure'=>'120/80','heart_rate'=>72,'temperature'=>36.5,'weight'=>70]);
    $vitals2 = json_encode(['blood_pressure'=>'130/85','heart_rate'=>80,'temperature'=>36.8,'weight'=>65]);
    $vitals3 = json_encode(['blood_pressure'=>'118/78','heart_rate'=>68,'temperature'=>37.1,'weight'=>80]);
    $records = [
        [1, 1, 1, 6, 'consultation', 'Paciente sano, sin alteraciones',            'Control preventivo',    'Todo en orden',    $vitals1],
        [2, 2, 2, 7, 'consultation', 'Hipertensión arterial leve',                 'Losartán 50mg diario', 'Seguimiento mensual',$vitals2],
        [3, 3, 3, null,'consultation','Infección respiratoria alta',                'Amoxicilina 500mg',    'Reposo 3 días',    $vitals3],
        [4, 4, 2, null,'consultation','Desarrollo infantil normal',                 'Vitaminas pediátricas','Próximo control 1 mes',null],
        [5, 1, 1, null,'general',    'Antecedentes registrados al sistema',        'Ninguno',              'Paciente activo',  $vitals1],
    ];
    $stmtRec = $pdo->prepare("INSERT INTO medical_records (id,patient_id,doctor_id,appointment_id,record_type,diagnosis,treatment,observations,vital_signs) VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($records as $r) $stmtRec->execute($r);
    $messages[] = ['ok', '5 registros de historial clínico insertados'];

    // ——— Recetas ———
    $pdo->exec("DELETE FROM prescriptions");
    $pdo->exec("ALTER TABLE prescriptions AUTO_INCREMENT = 1");
    $meds1 = json_encode([['name'=>'Losartán','dose'=>'50mg','frequency'=>'1 vez al día','duration'=>'30 días'],['name'=>'Aspirina','dose'=>'100mg','frequency'=>'1 vez al día','duration'=>'30 días']]);
    $meds2 = json_encode([['name'=>'Amoxicilina','dose'=>'500mg','frequency'=>'cada 8 horas','duration'=>'7 días'],['name'=>'Ibuprofeno','dose'=>'400mg','frequency'=>'cada 8 horas','duration'=>'5 días']]);
    $prescriptions = [
        [1, 2, 2, 2, $meds1, 'Tomar con abundante agua. Control en 1 mes.', date('Y-m-d'), date('Y-m-d', strtotime('+30 days')), 'active'],
        [2, 3, 1, 3, $meds2, 'Reposo en casa. Tomar con alimentos.',         date('Y-m-d'), date('Y-m-d', strtotime('+7 days')),  'active'],
    ];
    $stmtPres = $pdo->prepare("INSERT INTO prescriptions (id,medical_record_id,doctor_id,patient_id,medications,instructions,issue_date,expiry_date,status) VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($prescriptions as $p) $stmtPres->execute($p);
    $messages[] = ['ok', '2 recetas insertadas'];

    // ——— Pagos (3 pagos) ———
    $pdo->exec("DELETE FROM payments");
    $pdo->exec("ALTER TABLE payments AUTO_INCREMENT = 1");
    $payments = [
        [1, 1, 6, 1, 'Consulta Medicina General', 50000, 9500,  0, 59500,  'cash',     'paid',    date('Y-m-d H:i:s', strtotime('-1 day')), 'FAC-2026-001'],
        [2, 2, 7, 2, 'Consulta Cardiología',       80000, 15200, 0, 95200,  'card',     'paid',    date('Y-m-d H:i:s', strtotime('-1 day')), 'FAC-2026-002'],
        [3, 3, null, 1,'Consulta General Pendiente',50000, 9500, 0, 59500,  'cash',     'pending', null,                                     'FAC-2026-003'],
    ];
    $stmtPay = $pdo->prepare("INSERT INTO payments (id,patient_id,appointment_id,service_id,description,subtotal,tax_amount,discount,total,payment_method,status,paid_at,invoice_number) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($payments as $p) $stmtPay->execute($p);
    $messages[] = ['ok', '3 pagos insertados (2 pagados, 1 pendiente)'];

    // ——— Urgencias (2 activas) ———
    $pdo->exec("DELETE FROM urgencies");
    $pdo->exec("ALTER TABLE urgencies AUTO_INCREMENT = 1");
    $urgencies = [
        [1, 1, null, '3_urgente',       'Dolor abdominal severo',   'waiting'],
        [2, 4, 2,    '2_emergencia',    'Dificultad respiratoria',  'in_treatment'],
    ];
    $stmtUrg = $pdo->prepare("INSERT INTO urgencies (id,patient_id,attending_doctor_id,triage_level,chief_complaint,status) VALUES (?,?,?,?,?,?)");
    foreach ($urgencies as $u) $stmtUrg->execute($u);
    $messages[] = ['ok', '2 urgencias insertadas'];

    // ——— Notificaciones ———
    $pdo->exec("DELETE FROM notifications");
    $pdo->exec("ALTER TABLE notifications AUTO_INCREMENT = 1");
    $notifs = [
        [2, 'Nueva cita agendada',    'Carlos García agendó cita para hoy 09:00', 'appointment'],
        [3, 'Nueva cita agendada',    'María López agendó cita para hoy 10:00',   'appointment'],
        [5, 'Cita confirmada',        'Tu cita para hoy a las 09:00 fue confirmada', 'appointment'],
        [6, 'Pago procesado',         'Tu pago por $95.200 fue recibido',          'payment'],
        [1, 'Urgencia activa',        'Hay 2 urgencias en espera de atención',     'urgency'],
    ];
    $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)");
    foreach ($notifs as $n) $stmtNotif->execute($n);
    $messages[] = ['ok', '5 notificaciones insertadas'];

    $messages[] = ['ok', '✅ Instalación completada exitosamente'];

} catch (Throwable $e) {
    $success  = false;
    $messages[] = ['error', 'Error: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación — Tu Salud Primero</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F0F7FF; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 40px 20px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(10,110,189,0.15); padding: 40px; max-width: 600px; width: 100%; }
        .logo { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
        .logo-icon { width: 48px; height: 48px; background: linear-gradient(135deg,#0A6EBD,#00B4A6); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
        h1 { font-size: 1.5rem; color: #0F172A; font-weight: 700; }
        h2 { font-size: 0.9rem; color: #64748B; font-weight: 400; margin-top: 4px; }
        .step { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid #F1F5F9; }
        .step:last-child { border-bottom: none; }
        .icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; margin-top: 2px; }
        .ok .icon { background: #D1FAE5; color: #065F46; }
        .error .icon { background: #FEE2E2; color: #991B1B; }
        .step p { font-size: 0.9rem; color: #374151; }
        .btn { display: inline-block; background: linear-gradient(135deg,#0A6EBD,#0090D0); color: white; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 600; margin-top: 28px; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .warning { background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 14px 16px; border-radius: 8px; margin-top: 20px; font-size: 0.85rem; color: #92400E; }
        .creds { background: #F0F7FF; border-radius: 10px; padding: 16px; margin-top: 20px; }
        .creds h3 { font-size: 0.85rem; font-weight: 600; color: #1E40AF; margin-bottom: 10px; }
        .cred-row { display: flex; justify-content: space-between; font-size: 0.8rem; padding: 4px 0; color: #475569; }
        .cred-row span:last-child { font-family: monospace; color: #0A6EBD; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">❤</div>
        <div>
            <h1>Tu Salud Primero</h1>
            <h2>Instalación del sistema</h2>
        </div>
    </div>

    <?php foreach ($messages as [$type, $msg]): ?>
    <div class="step <?= $type ?>">
        <div class="icon"><?= $type === 'ok' ? '✓' : '✗' ?></div>
        <p><?= htmlspecialchars($msg) ?></p>
    </div>
    <?php endforeach; ?>

    <?php if ($success): ?>
    <div class="creds">
        <h3>🔐 Credenciales de acceso</h3>
        <div class="cred-row"><span>Admin</span><span>admin@hospital.com / admin123</span></div>
        <div class="cred-row"><span>Doctor 1</span><span>samuel.goe@hospital.com / doctor123</span></div>
        <div class="cred-row"><span>Doctor 2</span><span>elizabeth.ira@hospital.com / doctor123</span></div>
        <div class="cred-row"><span>Doctor 3</span><span>tanya.collins@hospital.com / doctor123</span></div>
        <div class="cred-row"><span>Paciente 1</span><span>carlos.garcia@email.com / paciente123</span></div>
    </div>
    <div class="warning">⚠️ Por seguridad, elimina o renombra este archivo <strong>install.php</strong> después de la instalación.</div>
    <a href="index.php" class="btn">Ir al sistema →</a>
    <?php endif; ?>
</div>
</body>
</html>
