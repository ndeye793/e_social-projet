<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/navbar.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom']);
    $email = sanitize($_POST['email']);
    $sujet = sanitize($_POST['sujet']);
    $message = sanitize($_POST['message']);

    // Validation et insertion en base
    if (!empty($nom) && !empty($email) && !empty($message)) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO messages_contact (nom, email, sujet, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $email, $sujet, $message]);
        
        $success = "Votre message a été envoyé avec succès !";
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!-- Hero Section animée -->
<section class="contact-hero animate__animated animate__fadeIn">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold text-white mb-4">Contactez-nous</h1>
                <p class="lead text-white mb-4">Votre voix compte pour nous. Partagez vos idées, questions ou préoccupations.</p>
                <div class="d-flex gap-3">
                    <a href="#contact-form" class="btn btn-primary btn-lg px-4 animate__animated animate__pulse animate__infinite">
                        <i class="fas fa-paper-plane me-2"></i> Écrire un message
                    </a>
                    <a href="tel:+221781234567" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-phone-alt me-2"></i> Appeler
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="../assets/images/contact.svg" alt="Contact" class="img-fluid animate__animated animate__fadeInRight">
            </div>
        </div>
    </div>
</section>

<!-- Formulaire de contact -->
<section id="contact-form" class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0 animate__animated animate__fadeInUp">
                    <div class="card-header bg-primary text-white py-4">
                        <h2 class="h3 mb-0 text-center"><i class="fas fa-envelope-open-text me-2"></i> Formulaire de contact</h2>
                    </div>
                    <div class="card-body p-5">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success animate__animated animate__bounceIn">
                                <i class="fas fa-check-circle me-2"></i> <?= $success ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nom" name="nom" placeholder="Votre nom" required>
                                        <label for="nom"><i class="fas fa-user me-2"></i> Votre nom complet</label>
                                        <div class="invalid-feedback">
                                            Veuillez entrer votre nom.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Votre email" required>
                                        <label for="email"><i class="fas fa-envelope me-2"></i> Votre email</label>
                                        <div class="invalid-feedback">
                                            Veuillez entrer un email valide.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="sujet" name="sujet" placeholder="Sujet">
                                        <label for="sujet"><i class="fas fa-tag me-2"></i> Sujet (facultatif)</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="message" name="message" placeholder="Votre message" style="height: 150px" required></textarea>
                                        <label for="message"><i class="fas fa-comment-dots me-2"></i> Votre message</label>
                                        <div class="invalid-feedback">
                                            Veuillez écrire votre message.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary btn-lg w-100 py-3" type="submit">
                                        <i class="fas fa-paper-plane me-2"></i> Envoyer le message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Informations de contact -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4 animate__animated animate__fadeInLeft">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body text-center p-4">
                        <div class="icon-contact bg-primary bg-opacity-10 text-primary mb-4">
                            <i class="fas fa-map-marker-alt fa-2x"></i>
                        </div>
                        <h3 class="h5">Notre adresse</h3>
                        <p class="mb-0 text-muted">123 Avenue de la Solidarité, Dakar, Sénégal</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body text-center p-4">
                        <div class="icon-contact bg-success bg-opacity-10 text-success mb-4">
                            <i class="fas fa-phone-alt fa-2x"></i>
                        </div>
                        <h3 class="h5">Téléphone</h3>
                        <p class="mb-0 text-muted">+221 78 123 45 67<br>Lun-Ven, 8h-18h</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInRight">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body text-center p-4">
                        <div class="icon-contact bg-info bg-opacity-10 text-info mb-4">
                            <i class="fas fa-envelope fa-2x"></i>
                        </div>
                        <h3 class="h5">Email</h3>
                        <p class="mb-0 text-muted">contact@e-social.sn<br>support@e-social.sn</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Vous avez une question urgente ?</h2>
        <p class="lead mb-4">Notre équipe est disponible pour vous aider à tout moment.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="tel:+221773588475" class="btn btn-light btn-lg px-4 animate__animated animate__pulse animate__infinite">
                <i class="fas fa-phone-alt me-2"></i> Appeler maintenant
            </a>
            <a href="https://wa.me/221773588475" target="_blank" class="btn btn-outline-light btn-lg px-4">
                <i class="fab fa-whatsapp me-2"></i> WhatsApp
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<style>
.contact-hero {
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
    padding: 6rem 0;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('../assets/images/pattern.svg') no-repeat;
    background-size: cover;
    opacity: 0.1;
}

.icon-contact {
    width: 70px;
    height: 70px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.hover-effect {
    transition: all 0.3s ease;
}

.hover-effect:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}

/* Animation personnalisée */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}
</style>

<script>
// Validation du formulaire
(() => {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>