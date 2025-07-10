<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="about-hero text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-3 fw-bold mb-4 animate__animated animate__fadeInDown">Notre histoire, notre mission</h1>
                <p class="lead mb-4 animate__animated animate__fadeInLeft">E-Social est né d'une simple idée : créer un pont entre ceux qui peuvent aider et ceux qui ont besoin d'aide.</p>
                <div class="d-flex gap-3 animate__animated animate__fadeInUp">
                    <a href="../public/dons.php" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-hand-holding-heart me-2"></i> Faire un don
                    </a>
                    <a href="#notre-equipe" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-users me-2"></i> Rencontrer l'équipe
                    </a>
                </div>
            </div>
            <div class="col-lg-6 animate__animated animate__fadeIn">
                <img src="../assets/images/about-hero.svg" alt="À propos" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- Notre mission -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 order-lg-2">
                <div class="p-4 p-lg-5">
                    <h2 class="display-5 fw-bold mb-4 text-primary animate__animated animate__fadeIn">Notre mission</h2>
                    <p class="lead animate__animated animate__fadeIn">Connecter les donateurs aux personnes dans le besoin avec transparence et efficacité.</p>
                    <div class="mt-4 animate__animated animate__fadeInUp">
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0 text-primary me-3">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Transparence totale</h5>
                                <p class="mb-0 text-muted">Chaque don est tracé jusqu'au bénéficiaire final.</p>
                            </div>
                        </div>
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0 text-primary me-3">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Impact direct</h5>
                                <p class="mb-0 text-muted">Votre contribution change directement des vies.</p>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="flex-shrink-0 text-primary me-3">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Communauté solidaire</h5>
                                <p class="mb-0 text-muted">Rejoignez un mouvement de générosité.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="p-3 animate__animated animate__fadeInLeft">
                    <img src="../assets/images/mission.jpg" alt="Notre mission" class="img-fluid rounded-4 shadow">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Chiffres clés -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold animate__animated animate__fadeInDown">Notre impact en chiffres</h2>
            <p class="lead opacity-75 animate__animated animate__fadeIn">Des résultats concrets grâce à votre générosité</p>
        </div>
        <div class="row g-4">
            <?php
            $pdo = getPDO();
$stats = $pdo->query("SELECT 
    COUNT(*) AS total_dons,
    SUM(montant) AS montant_total,
    COUNT(DISTINCT utilisateur_id) AS donateurs
FROM dons")->fetch(PDO::FETCH_ASSOC);

            ?>
            <div class="col-md-3 col-6 animate__animated animate__fadeInLeft">
                <div class="text-center p-3">
                    <div class="icon-stat mb-3">
                        <i class="fas fa-hand-holding-usd fa-3x"></i>
                    </div>
                    <h3 class="display-4 fw-bold counter" data-target="<?= $stats['montant_total'] ?? 0 ?>">0</h3>
                    <p class="mb-0 fw-bold">FCFA collectés</p>
                </div>
            </div>
            <div class="col-md-3 col-6 animate__animated animate__fadeInUp">
                <div class="text-center p-3">
                    <div class="icon-stat mb-3">
                        <i class="fas fa-heart fa-3x"></i>
                    </div>
                    <h3 class="display-4 fw-bold counter" data-target="<?= $stats['total_dons'] ?? 0 ?>">0</h3>
                    <p class="mb-0 fw-bold">Dons effectués</p>
                </div>
            </div>
            <div class="col-md-3 col-6 animate__animated animate__fadeInUp">
                <div class="text-center p-3">
                    <div class="icon-stat mb-3">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                    <h3 class="display-4 fw-bold counter" data-target="<?= $stats['donateurs'] ?? 0 ?>">0</h3>
                    <p class="mb-0 fw-bold">Donateurs</p>
                </div>
            </div>
            <div class="col-md-3 col-6 animate__animated animate__fadeInRight">
                <div class="text-center p-3">
                    <div class="icon-stat mb-3">
                        <i class="fas fa-smile fa-3x"></i>
                    </div>
                    <h3 class="display-4 fw-bold counter" data-target="<?= $stats['beneficiaires'] ?? 0 ?>">0</h3>
                    <p class="mb-0 fw-bold">Bénéficiaires</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Témoignages -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold animate__animated animate__fadeIn">Ils témoignent</h2>
            <p class="lead text-muted animate__animated animate__fadeIn">Découvrez ce que disent ceux qui nous font confiance</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 animate__animated animate__fadeInLeft">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <img src="../assets/images/testimonial-1.jpg" class="rounded-circle me-3" width="60" height="60" alt="Témoignage">
                            <div>
                                <h5 class="mb-1">Aminata Diop</h5>
                                <p class="text-muted mb-0">Donatrice</p>
                            </div>
                        </div>
                        <p class="mb-0">"Grâce à E-Social, je peux suivre l'impact de mes dons. Voir les sourires des bénéficiaires me motive à donner encore plus."</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 animate__animated animate__fadeInUp">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <img src="../assets/images/testimonial-2.jpg" class="rounded-circle me-3" width="60" height="60" alt="Témoignage">
                            <div>
                                <h5 class="mb-1">Moussa Fall</h5>
                                <p class="text-muted mb-0">Bénéficiaire</p>
                            </div>
                        </div>
                        <p class="mb-0">"E-Social a changé ma vie. Grâce aux dons reçus, j'ai pu payer les frais médicaux de ma mère. Merci à tous les donateurs."</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 animate__animated animate__fadeInRight">
                <div class="card h-100 border-0 shadow-sm hover-effect">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <img src="../assets/images/testimonial-3.jpg" class="rounded-circle me-3" width="60" height="60" alt="Témoignage">
                            <div>
                                <h5 class="mb-1">Fatou Ndiaye</h5>
                                <p class="text-muted mb-0">Partenaire</p>
                            </div>
                        </div>
                        <p class="mb-0">"Travailler avec E-Social est un honneur. Leur transparence et leur engagement envers les bénéficiaires sont exemplaires."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Notre équipe -->
<section id="notre-equipe" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold animate__animated animate__fadeIn">Notre équipe</h2>
            <p class="lead text-muted animate__animated animate__fadeIn">Passionnés par la solidarité et l'impact social</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6 animate__animated animate__fadeInLeft">
                <div class="card border-0 shadow-sm team-card">
                    <img src="../assets/images/team-1.jpg" class="card-img-top" alt="Équipe">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Mamadou Diallo</h5>
                        <p class="text-muted mb-3">Fondateur & Directeur</p>
                        <div class="social-links">
                            <a href="#" class="text-primary me-2"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-primary me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-primary"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 animate__animated animate__fadeInUp">
                <div class="card border-0 shadow-sm team-card">
                    <img src="../assets/images/team-2.jpg" class="card-img-top" alt="Équipe">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Aïssatou Bâ</h5>
                        <p class="text-muted mb-3">Responsable des partenariats</p>
                        <div class="social-links">
                            <a href="#" class="text-primary me-2"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-primary me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-primary"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 animate__animated animate__fadeInUp">
                <div class="card border-0 shadow-sm team-card">
                    <img src="../assets/images/team-3.jpg" class="card-img-top" alt="Équipe">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Oumar Sow</h5>
                        <p class="text-muted mb-3">Développeur web</p>
                        <div class="social-links">
                            <a href="#" class="text-primary me-2"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-primary me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-primary"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 animate__animated animate__fadeInRight">
                <div class="card border-0 shadow-sm team-card">
                    <img src="../assets/images/team-4.jpg" class="card-img-top" alt="Équipe">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Khadija Diouf</h5>
                        <p class="text-muted mb-3">Chargée de communication</p>
                        <div class="social-links">
                            <a href="#" class="text-primary me-2"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-primary me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-primary"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 bg-dark text-white">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4 animate__animated animate__fadeIn">Prêt à faire la différence ?</h2>
        <p class="lead mb-4 animate__animated animate__fadeIn">Rejoignez notre communauté et participez à changer des vies.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="../public/inscription.php" class="btn btn-primary btn-lg px-4 animate__animated animate__pulse animate__infinite">
                <i class="fas fa-user-plus me-2"></i> S'inscrire
            </a>
            <a href="../public/dons.php" class="btn btn-outline-light btn-lg px-4">
                <i class="fas fa-hand-holding-heart me-2"></i> Faire un don
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<style>
.about-hero {
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('../assets/images/about-bg.jpg');
    background-size: cover;
    background-position: center;
    padding: 8rem 0;
    position: relative;
}

.icon-stat {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.team-card {
    transition: all 0.3s ease;
    overflow: hidden;
}

.team-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
}

.team-card img {
    transition: transform 0.5s ease;
}

.team-card:hover img {
    transform: scale(1.05);
}

.social-links a {
    transition: all 0.3s ease;
}

.social-links a:hover {
    transform: translateY(-3px);
}
</style>

<script>
// Animation des compteurs
document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.counter');
    const speed = 200;
    
    counters.forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const count = +counter.innerText;
        const increment = target / speed;
        
        if (count < target) {
            counter.innerText = Math.ceil(count + increment);
            setTimeout(updateCounter, 1);
        } else {
            counter.innerText = target.toLocaleString();
        }
        
        function updateCounter() {
            const count = +counter.innerText.replace(/,/g, '');
            if (count < target) {
                counter.innerText = Math.ceil(count + increment).toLocaleString();
                setTimeout(updateCounter, 1);
            } else {
                counter.innerText = target.toLocaleString();
            }
        }
    });
});
</script>