<?php
// includes/header.php
// Ce fichier contient le header général du site e-social, avec intégration de Bootstrap 5, FontAwesome, animations, carrousel, menu responsive
 require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';
// Commence session si non démarrée
if (session_status() === PHP_SESSION_NONE) {
 
}

// Chargement des variables utilisateur pour affichage (ex: nom connecté)
$userName = $_SESSION['user_name'] ?? null;
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>E-Social - Collecte de Fonds Solidaire</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <!-- Animate.css pour animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet"/>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto&display=swap" rel="stylesheet" />

    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css" />

    <!-- Favicon -->
    <link rel="icon" href="<?= SITE_URL ?>/assets/images/logos/favicon.png" type="image/png" />

    <style>
        /* Styles personnalisés pour header et navbar */
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8f9fa;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: color 0.3s ease;
        }
        .navbar-brand:hover {
            color: #e74c3c;
        }

        .nav-link {
            font-weight: 500;
            color: #34495e;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #e74c3c;
        }

        .btn-don {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            font-weight: 700;
            border-radius: 50px;
            padding: 10px 25px;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.6);
            transition: all 0.3s ease;
        }
        .btn-don:hover {
            background: linear-gradient(45deg, #c0392b, #e74c3c);
            box-shadow: 0 6px 20px rgba(192, 57, 43, 0.8);
        }

        /* Animation sur logo */
        .logo-animate:hover {
            animation: bounce 1s infinite;
        }

        /* Sticky navbar */
        .sticky-top {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Custom carousel */
        .carousel-item {
            height: 60vh;
            min-height: 350px;
            background: no-repeat center center scroll;
            background-size: cover;
            position: relative;
            transition: transform 1s ease;
        }

        .carousel-caption {
            background-color: rgba(44, 62, 80, 0.6);
            padding: 20px;
            border-radius: 10px;
        }

        /* Icones animées dans navbar */
        .nav-icon {
            font-size: 1.3rem;
            transition: transform 0.3s ease;
        }
        .nav-icon:hover {
            transform: scale(1.2);
            color: #e74c3c;
        }
    </style>
</head>

<body>

<!-- NAVBAR PRINCIPALE -->
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
    <div class="container">
        <!-- Logo animé -->
        <a class="navbar-brand logo-animate" href="<?= SITE_URL ?>">
            <i class="fas fa-hand-holding-heart me-2" style="color:#e74c3c;"></i>E-Social
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
            aria-controls="mainNavbar" aria-expanded="false" aria-label="Basculer la navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>:../index.php"><i class="fas fa-home nav-icon me-1"></i>Accueil</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/public/campagnes.php"><i class="fas fa-bullhorn nav-icon me-1"></i>Campagnes</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/public/apropos.php"><i class="fas fa-info-circle nav-icon me-1"></i>À propos</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/public/contact.php"><i class="fas fa-envelope nav-icon me-1"></i>Contact</a>
                </li>

                <?php if ($isLoggedIn) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle nav-icon me-1"></i> <?= htmlspecialchars($userName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/utilisateur/dashboard.php">Tableau de bord</a></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/utilisateur/profil.php">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/utilisateur/deconnexion.php">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a></li>
                        </ul>
                    </li>
                <?php else : ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger ms-lg-3" href="<?= SITE_URL ?>/public/connexion.php"><i class="fas fa-sign-in-alt me-1"></i>Connexion</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-danger" href="<?= SITE_URL ?>/public/inscription.php"><i class="fas fa-user-plus me-1"></i>S'inscrire</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- CARROUSEL D'ACCUEIL -->
<div id="carouselAccueil" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="7000" data-bs-pause="hover">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#carouselAccueil" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#carouselAccueil" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#carouselAccueil" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>

    <div class="carousel-inner">
        <div class="carousel-item active" style="background-image: url('<?= SITE_URL ?>/assets/images/campagnes/slide1.jpg');">
            <div class="carousel-caption text-start animate__animated animate__fadeInDown">
                <h1>Agir ensemble pour l'entraide</h1>
                <p>Votre soutien change des vies. Rejoignez notre mission solidaire dès aujourd'hui.</p>
                <a href="<?= SITE_URL ?>/public/campagnes.php" class="btn btn-don btn-lg">Voir les campagnes</a>
            </div>
        </div>
        <div class="carousel-item" style="background-image: url('<?= SITE_URL ?>/assets/images/campagnes/slide2.jpg');">
            <div class="carousel-caption text-start animate__animated animate__fadeInDown">
                <h1>Collecte sécurisée et transparente</h1>
                <p>Chaque don est tracé et utilisé avec rigueur pour aider les plus démunis.</p>
                <a href="<?= SITE_URL ?>/public/faq.php" class="btn btn-don btn-lg">En savoir plus</a>
            </div>
        </div>
        <div class="carousel-item" style="background-image: url('<?= SITE_URL ?>/assets/images/campagnes/slide3.jpg');">
            <div class="carousel-caption text-start animate__animated animate__fadeInDown">
                <h1>Partagez votre générosité</h1>
                <p>Invitez vos proches à participer à une chaîne de solidarité sans fin.</p>
                <a href="<?= SITE_URL ?>/public/contact.php" class="btn btn-don btn-lg">Nous contacter</a>
            </div>
        </div>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#carouselAccueil" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Précédent</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselAccueil" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Suivant</span>
    </button>
</div>

<!-- JS Bootstrap 5 + Popper -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<!-- Script custom si besoin -->
<script>
    // Par exemple, animation ou interaction supplémentaire
    document.querySelectorAll('.logo-animate').forEach(el => {
        el.addEventListener('mouseenter', () => {
            el.classList.add('animate__animated', 'animate__bounce');
        });
        el.addEventListener('mouseleave', () => {
            el.classList.remove('animate__animated', 'animate__bounce');
        });
    });
</script>

