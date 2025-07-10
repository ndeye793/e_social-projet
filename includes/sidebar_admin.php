<?php
// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Récupérer le nom de l'admin pour affichage
$adminName = $_SESSION['admin_name'] ?? 'Administrateur';
?>

<!-- Sidebar Admin -->
<nav id="sidebarAdmin" class="bg-dark text-white vh-100 position-fixed d-flex flex-column p-3 shadow-lg" style="width: 280px; transition: width 0.3s ease;">
  <div class="d-flex align-items-center mb-4">
    <img src="../assets/images/image.png" alt="Logo" width="50" height="50" class="me-2 animate__animated animate__bounceIn">
    <span class="fs-4 fw-bold">E-social Admin</span>
  </div>

  <!-- Profil Admin -->
  <div class="d-flex align-items-center mb-4 px-2 py-3 bg-secondary rounded-3 shadow-sm animate__animated animate__fadeInDown">
    <i class="fas fa-user-circle fa-3x me-3"></i>
    <div>
      <h5 class="mb-0"><?= htmlspecialchars($adminName) ?></h5>
      <small class="text-light-opacity">Admin</small>
    </div>
  </div>

  <!-- Menu -->
  <ul class="nav nav-pills flex-column mb-auto" id="adminMenu">
    <li class="nav-item">
      <a href="index.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-tachometer-alt fa-lg me-3"></i> Tableau de bord
      </a>
    </li>
    <li>
      <a href="campagnes.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-hand-holding-heart fa-lg me-3"></i> Campagnes
      </a>
    </li>
    <li>
      <a href="beneficiaires.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-users fa-lg me-3"></i> Bénéficiaires
      </a>
    </li>
    <li>
      <a href="dons.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-donate fa-lg me-3"></i> Dons
      </a>
    </li>
    <li>
      <a href="transferts.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-file-invoice-dollar fa-lg me-3"></i> Transferts
      </a>
    </li>
    <li>
      <a href="utilisateurs.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-user-friends fa-lg me-3"></i> Utilisateurs
      </a>
    </li>
    <li>
      <a href="messages.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-envelope fa-lg me-3"></i> Messages
      </a>
    </li>
    <li>
      <a href="partenaires.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-handshake fa-lg me-3"></i> Partenaires
      </a>
    </li>
    <li>
      <a href="categories.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-tags fa-lg me-3"></i> Catégories
      </a>
    </li>
    <li>
      <a href="newsletter.php" class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-3 mb-2 hover-bg-primary">
        <i class="fas fa-newspaper fa-lg me-3"></i> Newsletter
      </a>
    </li>
  </ul>

  <!-- Déconnexion -->
  <div class="mt-auto px-3 py-3">
    <a href="deconnexion.php" class="btn btn-danger w-100 d-flex align-items-center justify-content-center shadow-sm">
      <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
    </a>
  </div>
</nav>

<!-- Styles CSS additionnels -->
<style>
  #sidebarAdmin a:hover, #sidebarAdmin a.active {
    background-color: #0d6efd !important;
    color: white !important;
    text-decoration: none;
    box-shadow: 0 0 10px #0d6efd;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
  }

  .hover-bg-primary:hover {
    background-color: #0d6efd !important;
    transition: background-color 0.3s ease;
  }

  /* Scroll automatique si trop grand */
  #sidebarAdmin {
    overflow-y: auto;
  }
</style>

<!-- Animate.css CDN pour animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<!-- FontAwesome CDN -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
