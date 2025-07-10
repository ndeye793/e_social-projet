 <?php

 require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';
if (isset($_SESSION['utilisateur_id'])) {
    header('Location: ' . SITE_URL . '/utilisateur/dashboard.php');
    exit;
}




// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-social</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

  <!-- FontAwesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <style>
    .navbar {
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .navbar-nav .nav-link:hover {
      background-color: rgba(255, 255, 255, 0.15);
      border-radius: 8px;
      transition: all 0.3s ease-in-out;
    }

    .btn-success {
      box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
    }

    .btn-success:hover {
      box-shadow: 0 0 20px rgba(40, 167, 69, 0.8);
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-lg sticky-top">
  <div class="container">
    <!-- Logo + Titre -->
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <!-- Logo SVG inline -->
      <a class="navbar-brand logo-animate" href="<?= SITE_URL ?>">
            <i class="fas fa-hand-holding-heart me-2" style="color:#e74c3c;"></i>
        </a>
      <span class="fw-bold fs-4 text-white">E-social</span>
    </a>

    <!-- Bouton hamburger -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarEsocial" aria-controls="navbarEsocial" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu -->
    <div class="collapse navbar-collapse" id="navbarEsocial">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="index.php">
            <i class="fas fa-home me-1"></i> Accueil
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="campagnes.php" id="dropdownCampagnes" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            
            <i class="fas fa-hand-holding-heart me-1"></i> Campagnes
          </a>
          <ul class="dropdown-menu" aria-labelledby="dropdownCampagnes">
            <li><a class="dropdown-item" href="campagnes.php?status=active">En cours</a></li>
            <li><a class="dropdown-item" href="campagnes.php?status=ended">Terminées</a></li>
            <li><a class="dropdown-item" href="campagnes.php">Toutes</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/e_social/public/apropos.php">
            <i class="fas fa-info-circle me-1"></i> À propos
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/e_social/public/contact.php">
            <i class="fas fa-envelope me-1"></i> Contact
          </a>
        </li>

        <a href="/e_social/public/dons.php" class="btn btn-success btn-lg shadow-sm animate__animated animate__pulse animate__infinite">
        <i class="fas fa-donate me-2"></i> Faire un don
         </a>


        <!-- Connexion ou Profil -->
        <?php if (isLoggedIn()) : ?>
          <li class="nav-item dropdown ms-lg-3">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle me-2"></i>
              Bonjour, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="utilisateur/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Tableau de bord</a></li>
              <li><a class="dropdown-item" href="utilisateur/profil.php"><i class="fas fa-user-edit me-2"></i> Profil</a></li>
              <li><a class="dropdown-item" href="utilisateur/mes_dons.php"><i class="fas fa-heart me-2"></i> Mes dons</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="utilisateur/deconnexion.php"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
            </ul>
          </li>
        <?php else : ?>
          <li class="nav-item ms-lg-3">
            <a href="/e_social/public/connexion.php" class="btn btn-outline-light btn-lg">
              <i class="fas fa-sign-in-alt me-2"></i> Connexion
            </a>
          </li>
          <li class="nav-item ms-2">
            <a href="/e_social/public/inscription.php" class="btn btn-outline-warning btn-lg">
              <i class="fas fa-user-plus me-2"></i> Inscription
            </a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>
