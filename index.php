<!-- public/index.php -->
<?php include 'config/constantes.php'; ?>

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section avec animation de vagues -->
<section class="hero-section text-white text-center d-flex align-items-center" style="height: 100vh; background: linear-gradient(#001f3f, #003366); position: relative; overflow: hidden;">
  <div class="container position-relative z-2">
    <h1 class="display-4 fw-bold animate__animated animate__fadeInDown">Bienvenue sur <span class="text-warning">E-Social</span></h1>
    <p class="lead mt-3 animate__animated animate__fadeInUp">Une plateforme solidaire pour transformer la générosité en action au Sénégal.</p>
    <a href="/e_social/public/campagnes.php" class="btn btn-outline-light btn-lg mt-4 animate__animated animate__fadeInUp">Voir les campagnes</a>
  </div>
  <!-- Vagues animées -->
  <div class="custom-shape-divider-bottom-1683651230">
    <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
      <path d="M985.66,83.29c-74.39,9.44-148.3,18.76-222.59,24.19-63.39,4.66-127.39,5.06-190.55-.39-57.15-4.9-113.9-13.76-170.56-20.45C281.43,74.69,188.88,71.37,96,72.52V0H1200V27.35C1141.79,43.75,1060.05,73.73,985.66,83.29Z" opacity=".25" class="shape-fill"></path>
    </svg>
  </div>
</section>

<!-- Section avec cartes (cards) animées -->
<section class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-5">Pourquoi choisir <span class="text-primary">E-Social</span> ?</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card h-100 shadow-lg border-0 animate__animated animate__zoomIn">
          <div class="card-body text-center">
            <i class="fas fa-hands-helping fa-3x text-success mb-3"></i>
            <h5 class="card-title">Solidarité</h5>
            <p class="card-text">Chaque don a un impact direct sur les vies des bénéficiaires.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-lg border-0 animate__animated animate__zoomIn animate__delay-1s">
          <div class="card-body text-center">
            <i class="fas fa-search-dollar fa-3x text-warning mb-3"></i>
            <h5 class="card-title">Transparence</h5>
            <p class="card-text">Suivez en temps réel l'utilisation des fonds collectés.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-lg border-0 animate__animated animate__zoomIn animate__delay-2s">
          <div class="card-body text-center">
            <i class="fas fa-lightbulb fa-3x text-primary mb-3"></i>
            <h5 class="card-title">Innovation</h5>
            <p class="card-text">Des campagnes modernes, efficaces et sécurisées grâce au digital.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Slider dynamique avec Bootstrap Carousel -->
<section class="py-5">
  <div class="container">
    <h2 class="text-center mb-4">Nos campagnes en vedette</h2>
    <div id="campagnesCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="../assets/images/campagnes/camp1.jpg" class="d-block w-100 rounded" alt="...">
        </div>
        <div class="carousel-item">
          <img src="../assets/images/campagnes/camp2.jpg" class="d-block w-100 rounded" alt="...">
        </div>
        <div class="carousel-item">
          <img src="../assets/images/campagnes/camp3.jpg" class="d-block w-100 rounded" alt="...">
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#campagnesCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#campagnesCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
  </div>
</section>
<!-- Section d'abonnement à la newsletter -->
<!-- Section d'abonnement à la newsletter -->
<section class="newsletter-section py-5 text-white position-relative overflow-hidden">
  <div class="container text-center position-relative z-2">
    <h2 class="fw-bold mb-3 animate__animated animate__fadeInDown">📬 Restez informé</h2>
    <p class="mb-4 fs-5 animate__animated animate__fadeInUp">
      Inscrivez-vous à notre newsletter pour recevoir les dernières campagnes solidaires directement dans votre boîte mail.
    </p>

 
    <form action="public/traitement_newsletter.php" method="POST" class="row justify-content-center g-2 animate__animated animate__fadeInUp">
      <div class="col-md-6 col-sm-10">
        <div class="input-group input-group-lg shadow">
          <input type="email" name="email" class="form-control border-0 rounded-start" placeholder="Votre adresse email" required>
          <button type="submit" name="subscribe" class="btn btn-warning text-dark px-4 rounded-end">S'abonner</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Arrière-plan décoratif -->
  <div class="background-shapes position-absolute top-0 start-0 w-100 h-100 z-1 opacity-10" style="background: url('assets/images/newsletter-bg.svg') no-repeat center center / cover;"></div>
</section>


  <!-- Décor animé -->
  <div class="background-shapes position-absolute top-0 start-0 w-100 h-100 z-1 opacity-10" style="background: url('../assets/images/newsletter-bg.svg') no-repeat center center / cover;"></div>
</section>
<!-- Ajout de lumières (animation de points brillants) -->
<div class="floating-lights"></div>

<?php include 'includes/footer.php'; ?>

<style>
  .newsletter-section {
  background: linear-gradient(135deg, #003366, #00509e);
  border-top-left-radius: 80px;
  border-top-right-radius: 80px;
  margin-top: 80px;
}

.newsletter-section h2,
.newsletter-section p {
  color: #ffffff;
}

.input-group input::placeholder {
  color: #999;
}

.input-group .form-control:focus {
  box-shadow: none;
  border-color: #ffc107;
}

.newsletter-section .btn-warning {
  transition: background 0.3s ease, color 0.3s ease;
}

.newsletter-section .btn-warning:hover {
  background-color: #e0a800;
  color: #fff;
}

</style>