<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Salud Primero - Inicio</title>
    <meta name="description" content="Sistema de información hospitalario Tu Salud Primero. Agenda tus citas, conoce nuestros servicios y personal médico.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <i class="fa-solid fa-heart-pulse"></i>
                <span>Tu Salud Primero</span>
            </a>
            <ul class="nav-links">
                <li><a href="#sobre-nosotros">Sobre nosotros</a></li>
                <li><a href="#personal">Personal</a></li>
                <li><a href="#servicios">Servicios</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ul>
            <div class="nav-actions">
                <a href="index.php?module=auth&action=login" class="btn-primary">Iniciar Sesión</a>
                <button class="mobile-menu-btn" id="menuBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <span class="hero-badge"><i class="fa-solid fa-shield-heart"></i> Sistema de Información Hospitalario</span>
                <h1>Agenda tus citas<br><span class="highlight">sin problema</span></h1>
                <p>Contamos con un equipo de profesionales altamente capacitados que trabajan con responsabilidad y vocación de servicio para garantizarte la mejor atención.</p>
                <div class="hero-buttons">
                    <a href="index.php?module=auth&action=login" class="btn-primary btn-lg">
                        <i class="fa-solid fa-calendar-check"></i> Agendar cita
                    </a>
                    <a href="#servicios" class="btn-outline btn-lg">
                        <i class="fa-solid fa-stethoscope"></i> Ver servicios
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Urgencias</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-number">+50</span>
                        <span class="stat-label">Doctores</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-number">+1000</span>
                        <span class="stat-label">Pacientes</span>
                    </div>
                </div>
            </div>
           
            <div class="hero-image">
                <img src="images/Doctores-y-Equipo-Profesional-Clinica-Odontologia-Proteccion-Oral-Bogota-Colombia-12-1024x683.jpg" alt="Equipo médico" style="border-radius: 20px; width: 100%; height: auto;">
            </div>
           
        </div>
    </section>

    <!-- About Section -->
    <section id="sobre-nosotros" class="about-section">
        <div class="section-container">
            <div class="section-header">
                <span class="section-badge">Conócenos</span>
                <h2>Sobre nosotros</h2>
            </div>
            <div class="about-grid">
                <div class="about-card about-text-card">
                    <div class="about-icon-wrapper">
                        <i class="fa-solid fa-hospital"></i>
                    </div>
                    <p>Nuestro hospital está ubicado en el municipio de Ebéjico donde te ofrecemos la mejor atención y servicios con la mejor calidad para garantizarte una buena salud. Contamos con un equipo de profesionales altamente capacitados que trabajan con responsabilidad y vocación de servicio.</p>
                    <p>Nuestras instalaciones están equipadas con tecnología adecuada para ofrecer diagnósticos y tratamientos oportunos.</p>
                </div>
                <div class="about-card about-image-card">
                    <img src="images/equipo-medicos-especialistas-jovenes-pie-pasillo-hospital_1303-21199 (1).avif" alt="Hospital">
                </div>
            </div>
        </div>
    </section>

    <!-- Quote Banner -->
    <section class="quote-banner">
        <div class="section-container">
            <div class="quote-card">
                <i class="fa-solid fa-quote-left quote-icon"></i>
                <blockquote>Cuando eres joven y estás sano, nunca se te ocurre que en un solo segundo toda tu vida podría cambiar.</blockquote>
                <i class="fa-solid fa-quote-right quote-icon"></i>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section id="personal" class="doctors-section">
        <div class="section-container">
            <div class="section-header">
                <span class="section-badge">Equipo médico</span>
                <h2>Nuestro personal médico</h2>
            </div>
            <div class="doctors-grid">
                <div class="doctor-card">
                    <div class="doctor-img-wrapper">
                        <img src="images/doctor-pie-pasillo-hospital_23-2151997627.avif" alt="Samuel Goe">
                        <div class="doctor-overlay">
                            <div class="doctor-social">
                                <a href="#"><i class="fa-solid fa-envelope"></i></a>
                                <a href="#"><i class="fa-solid fa-phone"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="doctor-info">
                        <h4>Samuel Goe</h4>
                        <p>Médico General</p>
                    </div>
                </div>
                <div class="doctor-card">
                    <div class="doctor-img-wrapper">
                        <img src="images/estockphoto-2187596982-612x612.jpg" alt="Elizabeth Ira">
                        <div class="doctor-overlay">
                            <div class="doctor-social">
                                <a href="#"><i class="fa-solid fa-envelope"></i></a>
                                <a href="#"><i class="fa-solid fa-phone"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="doctor-info">
                        <h4>Elizabeth Ira</h4>
                        <p>Ginecóloga</p>
                    </div>
                </div>
                <div class="doctor-card">
                    <div class="doctor-img-wrapper">
                        <img src="images/istockphoto-1372002650-612x612.jpg" alt="Tanya Collins">
                        <div class="doctor-overlay">
                            <div class="doctor-social">
                                <a href="#"><i class="fa-solid fa-envelope"></i></a>
                                <a href="#"><i class="fa-solid fa-phone"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="doctor-info">
                        <h4>Tanya Collins</h4>
                        <p>Psicóloga</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="servicios" class="services-section">
        <div class="section-container">
            <div class="section-header">
                <span class="section-badge">Atención médica</span>
                <h2>Nuestros servicios</h2>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <h3>Caso regular</h3>
                    <p>Corresponde a un paciente que requiere atención médica sin presentar una emergencia grave. Estos casos incluyen consultas por enfermedades comunes, controles médicos o síntomas leves que pueden ser evaluados con calma por el personal de salud.</p>
                    <span class="service-tag">Consulta externa</span>
                </div>
                <div class="service-card service-featured">
                    <div class="service-icon">
                        <i class="fa-solid fa-calendar-alt"></i>
                    </div>
                    <h3>Caso grave</h3>
                    <p>Paciente que presenta una condición médica crítica que pone en riesgo su vida o su salud de forma inmediata. Estos casos requieren atención urgente en el área de emergencias, donde el personal médico actúa rápidamente para estabilizar al paciente.</p>
                    <span class="service-tag">Emergencias</span>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <h3>Caso de emergencia</h3>
                    <p>Situación en la que la vida del paciente está en peligro inmediato y requiere atención médica instantánea. El personal de salud actúa de manera rápida y prioritaria para estabilizar al paciente y evitar complicaciones mayores.</p>
                    <span class="service-tag">Urgencias 24/7</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contacto" class="footer">
        <div class="section-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <i class="fa-solid fa-heart-pulse"></i>
                        <span>Tu Salud Primero</span>
                    </div>
                    <p class="footer-desc">Somos un hospital comprometido con la salud y el bienestar de la comunidad.</p>
                    <address>
                        Municipio de Ebéjico<br>
                        Antioquia, Colombia
                    </address>
                </div>
                <div class="footer-col">
                    <h4>Etiquetas</h4>
                    <div class="footer-tags">
                        <span class="tag">Atención médica</span>
                        <span class="tag">Emergencia</span>
                        <span class="tag">Terapia</span>
                        <span class="tag">Cirugía</span>
                        <span class="tag">Medicamento</span>
                        <span class="tag">Enfermería</span>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Enlaces rápidos</h4>
                    <ul class="footer-links">
                        <li><a href="#servicios">Nuestros servicios</a></li>
                        <li><a href="#sobre-nosotros">Sobre nosotros</a></li>
                        <li><a href="#">Política de privacidad</a></li>
                        <li><a href="#">Horario de citas</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Horarios</h4>
                    <p>Nuestro hospital cuenta con atención las 24 horas en el área de urgencias.</p>
                    <ul class="schedule-list">
                        <li><i class="fa-regular fa-clock"></i> Lunes a viernes: 6:00 AM - 10:00 PM</li>
                        <li><i class="fa-regular fa-clock"></i> Sábados y domingo: 8:00 AM - 3:00 PM</li>
                    </ul>
                    <div class="footer-contact-info">
                        <p><i class="fa-solid fa-envelope"></i> sanrafa@gmail.com</p>
                        <p><i class="fa-solid fa-phone"></i> 310 5671349</p>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-social">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <p>&copy; 2026 Tu Salud Primero. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const menuBtn = document.getElementById('menuBtn');
        const navLinks = document.querySelector('.nav-links');
        if (menuBtn) {
            menuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>