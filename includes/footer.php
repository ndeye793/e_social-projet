<?php
// includes/footer.php

// Assurez-vous que constantes.php est requis une seule fois, idéalement au début de vos scripts de page.
// Si ce n'est pas le cas, require_once ici est OK.
require_once __DIR__ . '/../config/constantes.php'; // Pour SITE_URL et BASE_URL
//require_once __DIR__ . '/../config/db.php'; // Pour getPDO() si vous l'utilisez directement ici (pour le compteur)

// --- Compteur d'abonnés à la newsletter ---
$newsletter_subscribers_count = 0; // Valeur par défaut
// Vérifiez si getPDO est disponible (si db.php n'est pas inclus, il faut l'inclure ou passer le $pdo)
if (function_exists('getPDO')) { // Ou si vous avez $pdo disponible globalement depuis la page parente
    try {
        $pdo_footer = getPDO();
        $stmt_count_nl = $pdo_footer->query("SELECT COUNT(*) FROM abonnements_newsletter");
        if ($stmt_count_nl) {
            $newsletter_subscribers_count = (int) $stmt_count_nl->fetchColumn();
        }
    } catch (PDOException $e) {
        // Log l'erreur en production
        // error_log("Erreur footer - comptage newsletter: " . $e->getMessage());
        $newsletter_subscribers_count = 0; // En cas d'erreur, afficher 0
    }
} else {
    // Optionnel: message si getPDO n'est pas dispo
    // echo "<!-- Fonction getPDO non disponible pour le compteur de newsletter -->";
}

// Définir BASE_URL si ce n'est pas déjà fait (par exemple, pour l'action du formulaire AJAX)
// Idéalement, BASE_URL est défini dans constantes.php
if (!defined('BASE_URL')) {
    // Détection basique, à affiner si besoin
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_name_parts = explode('/', $_SERVER['SCRIPT_NAME']);
    // Supposer que 'e_social' est le dossier racine du projet
    $project_root_index = array_search('e_social', $script_name_parts);
    if ($project_root_index !== false) {
        $base_path_array = array_slice($script_name_parts, 0, $project_root_index + 1);
        $base_path = implode('/', $base_path_array) . '/';
    } else {
        $base_path = '/'; // Fallback
    }
    define('BASE_URL', $protocol . $host . $base_path);
}
?>

<style>
    /* Animation des vagues pour footer horizontal */
    .wave-footer-horizontal {
        position: relative;
        background: linear-gradient(90deg, #1e88e5, #0d47a1); /* Couleurs de vagues */
        overflow: hidden; /* Important pour que les vagues ne dépassent pas */
        padding-top: 4rem; /* Plus d'espace pour les vagues en haut */
        padding-bottom: 2rem;
    }
    
    .wave-footer-horizontal::before, .wave-footer-horizontal::after {
        content: "";
        position: absolute;
        top: 0; /* Vagues en haut du footer */
        left: 0;
        width: 200%; /* Double largeur pour l'animation de défilement */
        height: 100px; /* Hauteur des vagues */
        background-repeat: repeat-x;
        animation: waveAnimationHorizontal 15s linear infinite;
    }

    .wave-footer-horizontal::before {
        /* SVG data URI pour la première vague */
        background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V120H0Z" fill="%23ffffff" opacity=".15"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V120H0Z" fill="%23ffffff" opacity=".3"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V120H0Z" fill="%23ffffff" opacity=".5"/></svg>');
        background-size: 50% 100px;
        transform: scaleY(-1); /* Retourner les vagues pour qu'elles pointent vers le bas depuis le haut */
        z-index: 1; /* Pour être au-dessus du contenu mais en dessous des bulles si nécessaire */
    }
     .wave-footer-horizontal::after { /* Une deuxième vague pour plus de profondeur */
        background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V120H0Z" fill="%23ffffff" opacity=".2"/></svg>');
        background-size: 50% 80px; /* Taille légèrement différente */
        transform: scaleY(-1);
        animation-direction: reverse; /* La faire bouger dans l'autre sens */
        animation-duration: 20s;
        z-index: 0;
    }
    
    @keyframes waveAnimationHorizontal {
        0% { transform: translateX(0) scaleY(-1); } /* Maintenir le scaleY(-1) */
        100% { transform: translateX(-50%) scaleY(-1); }
    }
    
    /* Disposition horizontale */
    .footer-horizontal-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        align-items: flex-start;
        max-width: 1200px;
        margin: 0 auto;
        position: relative; /* Pour que le contenu soit au-dessus des vagues */
        z-index: 2;
    }
    
    .footer-horizontal-section {
        flex: 1;
        min-width: 220px; /* Un peu plus large */
        padding: 0 15px;
        margin-bottom: 30px; /* Plus d'espacement */
    }
    
    /* Effets de bulles */
    #bubbles-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none; /* Pour que les bulles ne bloquent pas les clics */
        z-index: 3; /* Au-dessus de tout */
    }
    .bubble {
        position: absolute;
        background-color: rgba(255, 255, 255, 0.2); /* Bulles plus visibles */
        border-radius: 50%;
        animation: bubbleRise linear infinite;
    }
    
    @keyframes bubbleRise {
        0% {
            transform: translateY(100%) scale(0.5); /* Commence du bas */
            opacity: 0;
        }
        20% {
            opacity: 0.7; /* Devient visible */
        }
        90% {
            opacity: 0.7;
        }
        100% {
            transform: translateY(-100px) scale(1.2); /* Monte et disparaît en haut */
            opacity: 0;
        }
    }
    
    /* Style général */
    .footer-horizontal-section h5 {
        font-weight: 600;
        color: #fff; /* Titres plus clairs */
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }
    .footer-link {
        transition: all 0.3s ease;
        position: relative;
        display: inline-block;
        margin-bottom: 8px;
        padding-bottom: 2px; /* Espace pour le soulignement */
    }
    
    .footer-link:hover {
        color: #bbdefb !important;
        transform: translateX(5px);
    }
    
    .footer-link::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: 0;
        left: 0;
        background-color: #bbdefb;
        transition: width 0.3s ease;
    }
    
    .footer-link:hover::after {
        width: 100%;
    }
    
    .social-icon {
        transition: all 0.3s ease;
        margin: 0 8px; /* Plus d'espace entre les icônes */
        font-size: 1.3rem; /* Icônes plus grandes */
    }
    
    .social-icon:hover {
        transform: translateY(-5px) scale(1.2);
        color: #bbdefb !important; /* Changement de couleur au survol */
    }
    
    .newsletter-form .form-control {
        border-radius: 20px 0 0 20px; /* Coins arrondis à gauche */
    }
    .newsletter-form .btn {
        border-radius: 0 20px 20px 0; /* Coins arrondis à droite */
        transition: all 0.3s ease;
    }
    .newsletter-form .btn:hover {
        transform: scale(1.05); /* Léger zoom au survol */
        background-color: #0056b3; /* Couleur plus foncée au survol */
    }
    #newsletterMessage .alert {
        font-size: 0.9em;
        padding: 0.5rem 1rem;
    }

    .footer-divider {
        flex-basis: 100%;
        height: 1px;
        background: rgba(255,255,255,0.15); /* Diviseur plus subtil */
        margin: 20px 0;
    }
    
    .footer-copyright {
        flex-basis: 100%;
        text-align: center;
        padding-top: 20px;
        font-size: 0.9em;
    }
    /* S'assurer que les liens du footer utilisent la constante SITE_URL */
    /* Les chemins relatifs sont ok si footer.php est toujours inclus depuis le même niveau de profondeur */
</style>

<footer class="wave-footer-horizontal text-light">
    <div id="bubbles-container"></div>
    
    <div class="footer-horizontal-container">
        <!-- Section À propos -->
        <div class="footer-horizontal-section">
            <h5 class="text-uppercase mb-3">À propos</h5>
            <p class="text-white-75 small">
                Plateforme solidaire de collecte de fonds pour des causes justes au Sénégal et ailleurs. Ensemble, faisons la différence.
            </p>
            <a href="<?= htmlspecialchars(SITE_URL . '/public/apropos.php') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3 mt-2 footer-link">
                En savoir plus <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <!-- Section Liens rapides -->
        <div class="footer-horizontal-section">
            <h5 class="text-uppercase mb-3">Liens Rapides</h5>
            <ul class="list-unstyled">
                <li><a href="<?= htmlspecialchars(SITE_URL . '/public/index.php') ?>" class="text-light footer-link">Accueil</a></li>
                <li><a href="<?= htmlspecialchars(SITE_URL . '/public/campagnes.php') ?>" class="text-light footer-link">Campagnes</a></li>
                 <li><a href="<?= htmlspecialchars(SITE_URL . '/public/contact.php') ?>" class="text-light footer-link">Nous Contacter</a></li>
                <li><a href="<?= htmlspecialchars(SITE_URL . '/public/faq.php') ?>" class="text-light footer-link">FAQ</a></li>
            </ul>
        </div>

        <!-- Section Contact -->
        <div class="footer-horizontal-section">
            <h5 class="text-uppercase mb-3">Contactez-nous</h5>
            <ul class="list-unstyled text-white-75 small">
                <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-info"></i> Dakar, Sénégal</li>
                <li class="mb-2"><i class="fas fa-phone me-2 text-info"></i> +221 77 123 45 67</li>
                <li class="mb-2"><i class="fas fa-envelope me-2 text-info"></i> <a href="mailto:contact@e-social.sn" class="text-white-75 footer-link">contact@e-social.sn</a></li>
            </ul>
        </div>

        <!-- Section Newsletter & Réseaux -->
        <div class="footer-horizontal-section">
            <h5 class="text-uppercase mb-3">Newsletter</h5>
            <p class="small text-white-75 mb-2">Restez informé de nos actions.</p>
            <!-- L'action pointe vers le script de traitement. BASE_URL est utile si le footer est inclus dans des pages à différentes profondeurs -->
            <form id="newsletterSubscribeForm" method="POST" action="<?= htmlspecialchars(BASE_URL . 'traitement/subscribe_newsletter.php') ?>" class="newsletter-form">
                <div class="input-group mb-2">
                    <input type="email" class="form-control form-control-sm" name="newsletter_email" placeholder="Votre adresse email" aria-label="Votre adresse email" required>
                    <button class="btn btn-primary btn-sm" type="submit" name="subscribe_newsletter_submit" aria-label="S'abonner">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div id="newsletterMessage" class="mt-2"></div> <!-- Pour afficher les messages de succès/erreur via AJAX -->
            </form>
             <p class="small text-white-75 mt-1">
                <i class="fas fa-users text-info"></i> Déjà <strong class="text-white"><?= htmlspecialchars($newsletter_subscribers_count) ?></strong> abonné(s) !
            </p>
            
            <h5 class="text-uppercase mb-3 mt-4">Suivez-nous</h5>
            <div class="d-flex justify-content-start">
                <a href="https://facebook.com/esocial" target="_blank" class="text-light social-icon" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/esocial" target="_blank" class="text-light social-icon" aria-label="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://instagram.com/esocial" target="_blank" class="text-light social-icon" aria-label="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://linkedin.com/company/esocial" target="_blank" class="text-light social-icon" aria-label="LinkedIn">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            </div>
        </div>

        <div class="footer-divider"></div>
        
        <div class="footer-copyright small text-white-75">
            © <?= date('Y') ?> E-Social. Plateforme de solidarité. Tous droits réservés. Conçu avec <i class="fas fa-heart text-danger"></i>.
        </div>
    </div>
</footer>

<!-- JS pour les bulles animées -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bubblesContainer = document.getElementById('bubbles-container');
    if (bubblesContainer) { // S'assurer que l'élément existe
        function createBubble() {
            const bubble = document.createElement('div');
            bubble.classList.add('bubble');
            
            const size = Math.random() * 20 + 8; // Bulles un peu plus grosses en moyenne
            bubble.style.width = `${size}px`;
            bubble.style.height = `${size}px`;
            
            bubble.style.left = `${Math.random() * 100}%`;
            // Faire commencer les bulles du bas du footer visible
            bubble.style.bottom = `-${size + Math.random() * 50}px`; // Commence sous le footer
            
            const duration = Math.random() * 10 + 6; // Durée entre 6 et 16 secondes
            bubble.style.animationDuration = `${duration}s`;
            
            bubble.style.animationDelay = `${Math.random() * 7}s`; // Délai un peu plus long pour un effet plus espacé
            
            bubblesContainer.appendChild(bubble);
            
            setTimeout(() => {
                if (bubble.parentNode) { // Vérifier si la bulle existe toujours avant de la supprimer
                    bubble.remove();
                }
            }, (duration + parseFloat(bubble.style.animationDelay || 0)) * 1000 + 500); // Supprimer après animation + délai + marge
        }
        
        let bubbleInterval = setInterval(createBubble, 400); // Une nouvelle bulle toutes les 400ms
        
        for (let i = 0; i < 15; i++) { // 15 bulles initiales
            setTimeout(createBubble, i * 200);
        }

        // Optionnel: Arrêter de créer des bulles si l'onglet n'est pas visible pour économiser les ressources
        document.addEventListener("visibilitychange", function() {
            if (document.hidden) {
                clearInterval(bubbleInterval);
            } else {
                bubbleInterval = setInterval(createBubble, 400);
            }
        });
    }

    // --- Gestion AJAX pour la Newsletter ---
    const newsletterForm = document.getElementById('newsletterSubscribeForm');
    const newsletterMessageDiv = document.getElementById('newsletterMessage');

    if (newsletterForm && newsletterMessageDiv) {
        newsletterForm.addEventListener('submit', function(event) {
            event.preventDefault(); 

            const formData = new FormData(newsletterForm);
            const submitButton = newsletterForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton.innerHTML; // Sauvegarder le contenu (icône)
            
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            submitButton.disabled = true;
            newsletterMessageDiv.innerHTML = ''; // Vider les messages précédents

            fetch(newsletterForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) { // Gérer les erreurs HTTP aussi (ex: 404, 500)
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const alertDiv = document.createElement('div');
                alertDiv.classList.add('alert', 'alert-dismissible', 'fade', 'show', 'py-1', 'px-2', 'small'); // Classes Bootstrap pour alerte
                alertDiv.setAttribute('role', 'alert');
                
                let iconHtml = '';
                if (data.success) {
                    alertDiv.classList.add('alert-success');
                    iconHtml = '<i class="fas fa-check-circle me-1"></i> ';
                    newsletterForm.reset(); 
                } else {
                    alertDiv.classList.add('alert-danger');
                    iconHtml = '<i class="fas fa-exclamation-triangle me-1"></i> ';
                }
                alertDiv.innerHTML = iconHtml + data.message + '<button type="button" class="btn-close btn-sm py-1 px-2" data-bs-dismiss="alert" aria-label="Close"></button>';
                
                newsletterMessageDiv.appendChild(alertDiv);

                // Optionnel: Mettre à jour le compteur d'abonnés si succès (nécessiterait une nouvelle requête ou une info du backend)
                // Pour l'instant, on ne le fait pas dynamiquement ici, mais ce serait une amélioration.
            })
            .catch(error => {
                console.error('Erreur d\'abonnement Newsletter:', error);
                const errorAlert = document.createElement('div');
                errorAlert.classList.add('alert', 'alert-danger', 'alert-dismissible', 'fade', 'show', 'py-1', 'px-2', 'small');
                errorAlert.setAttribute('role', 'alert');
                errorAlert.innerHTML = '<i class="fas fa-times-circle me-1"></i> Erreur de communication. Veuillez réessayer.' + '<button type="button" class="btn-close btn-sm py-1 px-2" data-bs-dismiss="alert" aria-label="Close"></button>';
                newsletterMessageDiv.appendChild(errorAlert);
            })
            .finally(() => {
                submitButton.innerHTML = originalButtonContent; // Restaurer le contenu original (icône)
                submitButton.disabled = false;
            });
        });
    }
});
</script>

<!-- JS Bootstrap 5 requis par certaines fonctionnalités comme les alertes dismissible -->
<!-- Assurez-vous qu'il est chargé une seule fois sur la page. Si déjà chargé par la page principale, pas besoin ici. -->
<!-- <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script> -->
<!-- Font Awesome pour les icônes (si pas déjà chargé globalement) -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> -->