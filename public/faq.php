<?php
session_start();
// Si vous avez un fichier de configuration ou des fonctions communes :
// require_once '../config/config.php'; // Exemple
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FAQ - e_social | Vos Questions, Nos Réponses</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (optionnel, pour les icônes) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts (optionnel, pour des polices plus sympas) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd; /* Bleu Bootstrap par défaut, modifiable */
            --secondary-color: #6c757d;
            --accent-color: #ffc107; /* Jaune pour l'accent */
            --light-glow: rgba(255, 255, 255, 0.1);
            --dark-glow: rgba(0, 0, 0, 0.1);
            --gradient-start: #050e3a;
            --gradient-mid: #003973;
            --gradient-end: #005f9a;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5; /* Fond général légèrement gris */
            color: #333;
            line-height: 1.7;
        }

        /* Effet de fond animé pour le header */
        .faq-header {
            padding: 5rem 1rem;
            text-align: center;
            color: white;
            background: linear-gradient(-45deg, var(--gradient-start), var(--gradient-mid), var(--gradient-end), #0089ba);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            border-bottom: 5px solid var(--accent-color);
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Petites "particules" animées pour le header */
        .faq-header::before, .faq-header::after {
            content: '';
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            opacity: 0;
            animation: sparkle 5s infinite;
        }
        .faq-header::before { top: 20%; left: 10%; animation-delay: 0s; }
        .faq-header::after { top: 80%; left: 90%; animation-delay: 1s; }
        /* Ajoutez plus de ::before/::after ou utilisez JS pour plus de particules */

        @keyframes sparkle {
            0% { opacity: 0; transform: scale(0.5) translateY(0); }
            25% { opacity: 0.7; transform: scale(1) translateY(-10px); }
            50% { opacity: 0; transform: scale(0.5) translateY(-20px); }
            100% { opacity: 0; transform: scale(0.5) translateY(0); }
        }


        .faq-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .faq-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .faq-search-bar {
            margin-top: 2rem;
            margin-bottom: 3rem;
        }

        .faq-search-bar .form-control {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--accent-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .faq-search-bar .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(var(--primary-color-rgb, 13, 110, 253), .25), 0 4px 20px rgba(0,0,0,0.2);
        }

        .faq-section-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-color);
            display: inline-block;
        }

        .accordion-item {
            background-color: #fff;
            border: none;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px var(--dark-glow);
            transition: all 0.3s ease-in-out;
            overflow: hidden; /* Important pour le border-radius avec le bouton */
        }
        .accordion-item:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .accordion-button {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #333;
            background-color: #fff;
            border-radius: 10px !important; /* Appliquer aux coins */
            padding: 1.25rem 1.5rem;
            transition: background-color 0.3s ease;
            position: relative; /* Pour le pseudo-élément lumineux */
        }
        .accordion-button:not(.collapsed) {
            color: white;
            background-image: linear-gradient(to right, var(--primary-color), #007bffc9);
            box-shadow: inset 0 -3px 0 rgba(0,0,0,0.1);
        }
        .accordion-button:not(.collapsed)::after { /* Style de l'icône flèche */
            filter: brightness(0) invert(1);
        }

        /* Effet de "lumière" au survol du bouton d'accordéon */
        .accordion-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            width: 5px;
            height: 70%;
            background-color: var(--accent-color);
            border-radius: 3px;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .accordion-button:hover::before,
        .accordion-button:focus::before {
            opacity: 1;
            transform: translateY(-50%) scaleY(1.1);
        }
        .accordion-button:not(.collapsed)::before {
            opacity: 1; /* Garder la barre visible quand ouvert */
            background-color: white; /* Changer la couleur sur fond bleu */
        }


        .accordion-button:focus {
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-color-rgb, 13, 110, 253), .25);
            z-index: 1;
        }

        .accordion-body {
            padding: 1.5rem;
            background-color: #fdfdfd; /* Légèrement différent pour contraster */
            border-top: 1px solid #eee;
        }
        .accordion-body p:last-child {
            margin-bottom: 0;
        }

        .faq-contact-cta {
            margin-top: 4rem;
            padding: 3rem;
            background-color: #fff;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        .faq-contact-cta h3 {
            font-family: 'Poppins', sans-serif;
            color: var(--primary-color);
        }
        .btn-contact-faq {
            background-color: var(--accent-color);
            color: #333;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .btn-contact-faq:hover {
            background-color: #e6ac00;
            color: black;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }

        /* Pour le filtrage, cacher les éléments non correspondants */
        .faq-item.hidden {
            display: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .faq-header h1 {
                font-size: 2.2rem;
            }
            .faq-header p {
                font-size: 1rem;
            }
            .accordion-button {
                padding: 1rem;
                font-size: 0.95rem;
            }
            .accordion-body {
                padding: 1rem;
                font-size: 0.9rem;
            }
        }

    </style>
</head>
<body>

    <?php
   
    include_once '../includes/navbar.php';

    ?>

    <header class="faq-header">
        <div class="container">
            <h1>Foire Aux Questions</h1>
            <p>Trouvez ici les réponses à vos interrogations les plus fréquentes sur e_social.</p>
        </div>
    </header>

    <main class="container py-5">
        <div class="faq-search-bar">
            <input type="text" id="faqSearchInput" class="form-control form-control-lg" placeholder="Rechercher une question ou un mot-clé...">
        </div>

        <section id="general-questions">
            <h2 class="faq-section-title">Questions Générales</h2>
            <div class="accordion" id="accordionGeneral">
                <!-- Item 1 -->
                <div class="accordion-item faq-item">
                    <h2 class="accordion-header" id="headingOneG">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOneG" aria-expanded="true" aria-controls="collapseOneG">
                            <i class="bi bi-patch-question-fill me-2"></i> Qu'est-ce que e_social ?
                        </button>
                    </h2>
                    <div id="collapseOneG" class="accordion-collapse collapse show" aria-labelledby="headingOneG" data-bs-parent="#accordionGeneral">
                        <div class="accordion-body">
                            <strong>e_social</strong> est une plateforme de collecte de fonds en ligne dédiée à soutenir des causes sociales et humanitaires. Nous connectons ceux qui souhaitent aider avec ceux qui ont besoin d'un soutien financier pour leurs projets ou leurs besoins urgents. Notre mission est de rendre la solidarité plus accessible et transparente.
                        </div>
                    </div>
                </div>
                <!-- Item 2 -->
                <div class="accordion-item faq-item">
                    <h2 class="accordion-header" id="headingTwoG">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwoG" aria-expanded="false" aria-controls="collapseTwoG">
                           <i class="bi bi-shield-lock-fill me-2"></i> La plateforme e_social est-elle sécurisée ?
                        </button>
                    </h2>
                    <div id="collapseTwoG" class="accordion-collapse collapse" aria-labelledby="headingTwoG" data-bs-parent="#accordionGeneral">
                        <div class="accordion-body">
                            Oui, la sécurité est notre priorité. Nous utilisons des protocoles de cryptage SSL pour toutes les transactions et la transmission de données. Les paiements sont traités par des prestataires reconnus pour leur fiabilité et leur conformité aux normes de sécurité internationales (PCI DSS). Vos informations personnelles sont protégées conformément à notre politique de confidentialité.
                        </div>
                    </div>
                </div>
                <!-- Item 3 -->
                 <div class="accordion-item faq-item">
                    <h2 class="accordion-header" id="headingThreeG">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThreeG" aria-expanded="false" aria-controls="collapseThreeG">
                           <i class="bi bi-diagram-3-fill me-2"></i> Comment fonctionne e_social ?
                        </button>
                    </h2>
                    <div id="collapseThreeG" class="accordion-collapse collapse" aria-labelledby="headingThreeG" data-bs-parent="#accordionGeneral">
                        <div class="accordion-body">
                            Les porteurs de projets (individus, associations, etc.) peuvent soumettre une campagne de collecte de fonds. Après vérification, si la campagne est approuvée, elle est publiée sur la plateforme. Les donateurs peuvent alors parcourir les campagnes et faire des dons aux causes qui leur tiennent à cœur. Les fonds récoltés (moins d'éventuels frais de transaction) sont ensuite versés au bénéficiaire de la campagne.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="donor-questions" class="mt-5">
            <h2 class="faq-section-title">Pour les Donateurs</h2>
            <div class="accordion" id="accordionDonors">
                <!-- Item 1 -->
                <div class="accordion-item faq-item">
                    <h2 class="accordion-header" id="headingOneD">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOneD" aria-expanded="false" aria-controls="collapseOneD">
                           <i class="bi bi-credit-card-2-front-fill me-2"></i> Comment puis-je faire un don ?
                        </button>
                    </h2>
                    <div id="collapseOneD" class="accordion-collapse collapse" aria-labelledby="headingOneD" data-bs-parent="#accordionDonors">
                        <div class="accordion-body">
                            Faire un don est simple ! Parcourez les campagnes actives sur notre plateforme. Lorsque vous avez trouvé une cause que vous souhaitez soutenir, cliquez sur le bouton "Faire un don" sur la page de la campagne. Vous serez guidé à travers un processus de paiement sécurisé où vous pourrez choisir votre montant et votre méthode de paiement (carte bancaire, mobile money, etc., selon les options disponibles).
                        </div>
                    </div>
                </div>
                <!-- Item 2 -->
                <div class="accordion-item faq-item">
                    <h2 class="accordion-header" id="headingTwoD">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwoD" aria-expanded="false" aria-controls="collapseTwoD">
                           <i class="bi bi-cash-coin me-2"></i> Y a-t-il des frais sur les dons ?
                        </button>
                    </h2>
                    <div id="collapseTwoD" class="accordion-collapse collapse" aria-labelledby="headingTwoD" data-bs-parent="#accordionDonors">
                        <div class="accordion-body">
                            e_social s'efforce de minimiser les frais. Il peut y avoir des frais de transaction standards appliqués par nos partenaires de paiement (généralement un petit pourcentage du don). Nous sommes transparents sur ces frais ; ils sont généralement indiqués lors du processus de don. La plateforme e_social elle-même peut appliquer des frais de service minimes pour couvrir ses coûts opérationnels et de maintenance, permettant ainsi de continuer à offrir ce service.
                        </div>
                    </div>
                </div>
                 <!-- Item 3 -->
                <div class="accordion-item faq-item">
                    <h2 class="accordion-header" id="headingThreeD">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThreeD" aria-expanded="false" aria-controls="collapseThreeD">
                           <i class="bi bi-receipt-cutoff me-2"></i> Puis-je obtenir un reçu pour mon don ?
                        </button>
                    </h2>
                    <div id="collapseThreeD" class="accordion-collapse collapse" aria-labelledby="headingThreeD" data-bs-parent="#accordionDonors">
                        <div class="accordion-body">
                            Oui, après chaque don effectué avec succès, vous recevrez une confirmation par e-mail qui peut servir de reçu. Si la campagne est menée par une organisation habilitée à émettre des reçus fiscaux, des informations spécifiques à ce sujet seront disponibles sur la page de la campagne ou fournies par l'organisateur.
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <section id="campaign-questions" class="mt-5">
            <h2 class="faq-section-title">Pour les Porteurs de Campagnes</h2>
            <div class="accordion" id="accordionCampaigns">
                <div class="accordion-item faq-item">
                    <h2 class="accordion-header" id="headingOneC">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOneC" aria-expanded="false" aria-controls="collapseOneC">
                           <i class="bi bi-megaphone-fill me-2"></i> Comment puis-je créer une campagne sur e_social ?
                        </button>
                    </h2>
                    <div id="collapseOneC" class="accordion-collapse collapse" aria-labelledby="headingOneC" data-bs-parent="#accordionCampaigns">
                        <div class="accordion-body">
                            Pour créer une campagne, vous devez d'abord créer un compte sur e_social. Une fois connecté, cherchez l'option "Créer une campagne" ou "Soumettre un projet". Vous devrez remplir un formulaire détaillé décrivant votre projet, vos objectifs financiers, et fournir des documents justificatifs si nécessaire. Notre équipe examinera votre soumission avant sa publication.
                        </div>
                    </div>
                </div>
                <div class="accordion-item faq-item">
                    <h2 class="accordion-header" id="headingTwoC">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwoC" aria-expanded="false" aria-controls="collapseTwoC">
                           <i class="bi bi-check-circle-fill me-2"></i> Quels types de projets sont acceptés ?
                        </button>
                    </h2>
                    <div id="collapseTwoC" class="accordion-collapse collapse" aria-labelledby="headingTwoC" data-bs-parent="#accordionCampaigns">
                        <div class="accordion-body">
                            Nous acceptons une large gamme de projets à vocation sociale, humanitaire, éducative, sanitaire, ou environnementale. Cela peut inclure l'aide à des personnes en situation de précarité, le financement de projets communautaires, le soutien à des initiatives éducatives, des urgences médicales, etc. Les projets doivent être clairs, transparents et avoir un impact positif tangible. Nous n'acceptons pas les projets illégaux, discriminatoires ou contraires à nos valeurs.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="faq-contact-cta">
            <h3>Vous ne trouvez pas votre réponse ?</h3>
            <p class="lead">Notre équipe est là pour vous aider. N'hésitez pas à nous contacter directement.</p>
            <a href="contact.php" class="btn btn-contact-faq"><i class="bi bi-envelope-fill me-2"></i>Nous Contacter</a>
        </div>

    </main>

    <?php
    
    include_once '../includes/footer.php';
   
    ?>

    <!-- Bootstrap JS Bundle (Popper.js inclus) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('faqSearchInput');
            const faqItems = document.querySelectorAll('.faq-item');

            searchInput.addEventListener('keyup', function(event) {
                const searchTerm = event.target.value.toLowerCase();

                faqItems.forEach(function(item) {
                    const questionButton = item.querySelector('.accordion-button');
                    const answerBody = item.querySelector('.accordion-body');
                    
                    // Vérifier si le texte est présent dans le bouton OU dans le corps
                    const questionText = questionButton ? questionButton.textContent.toLowerCase() : '';
                    const answerText = answerBody ? answerBody.textContent.toLowerCase() : '';

                    if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });
            });

            // Optionnel: Ouvrir le premier accordéon de chaque section par défaut si besoin
            // const firstAccordionButtons = document.querySelectorAll('.accordion:first-child .accordion-item:first-child .accordion-button');
            // firstAccordionButtons.forEach(btn => {
            //     if (btn.classList.contains('collapsed')) {
            //         // new bootstrap.Collapse(btn.nextElementSibling, { toggle: true }); // Ne fonctionne pas directement comme ça
            //         // btn.click(); // Alternative simple mais peut être visible
            //     }
            // });
        });
    </script>
</body>
</html>