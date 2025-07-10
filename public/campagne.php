<?php
require_once '../config/db.php';

// Connexion à la base de données
$pdo = getPDO();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: campagnes.php');
    exit;
}

$campagne_id = (int) $_GET['id'];

$sql = "SELECT c.*, cat.nom_categorie, b.prenom AS b_prenom, b.nom AS b_nom, b.telephone AS b_tel
        FROM campagnes c
        LEFT JOIN categories cat ON c.categorie_id = cat.id
        LEFT JOIN beneficiaires b ON c.beneficiaire_id = b.id
        WHERE c.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $campagne_id, PDO::PARAM_INT);
$stmt->execute();

$campagne = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campagne) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Campagne introuvable.</div></div>";
    exit;
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($campagne['titre']) ?> - Détail campagne</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

    <script>AOS.init();</script>

    <style>
        body {
            background: #f2f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .carousel-inner img {
            height: 500px;
            object-fit: cover;
            border-radius: 10px;
        }
        .badge-category {
            font-size: 1rem;
            background-color: #007bff;
        }
        .section-title {
            font-weight: 700;
            margin-bottom: 1rem;
            color: #343a40;
            text-transform: uppercase;
        }
        .progress {
            height: 30px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.16);
            border-radius: 15px;
        }
        .btn-don {
            font-size: 1.4rem;
            padding: 0.75rem 2rem;
            animation: pulse 2.5s infinite;
            border-radius: 50px;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .testimonial-card {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
            background-color: #fff;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        .testimonial-card:hover {
            transform: translateY(-10px);
        }
        @keyframes fillProgress {
          from { width: 0%; }
          to { width: <?= $progress ?>%; }
        }
        .progress-bar {
         animation: fillProgress 2s ease-in-out forwards;
        }

        footer {
            background-color: #343a40;
            color: #f8f9fa;
            padding: 2rem 0;
        }
        footer a {
            color: #f8f9fa;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="container my-5">

    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="animate__animated animate__fadeInDown"><?= htmlspecialchars($campagne['titre']) ?></h1>
            <span class="badge badge-category"><?= htmlspecialchars($campagne['nom_categorie'] ?? 'Sans catégorie') ?></span>
            <p class="mt-3 text-muted">
                <i class="fa-solid fa-calendar-days"></i> Date de début : <strong><?= date('d/m/Y', strtotime($campagne['date_debut'])) ?></strong>  
                &nbsp;&nbsp;|&nbsp;&nbsp;  
                <i class="fa-solid fa-calendar-check"></i> Date de fin : <strong><?= date('d/m/Y', strtotime($campagne['date_fin'])) ?></strong>
            </p>
        </div>
              <div class="col-md-4 text-md-end align-self-center">
                 <a href="../public/dons.php?campagne_id=<?= $campagne_id ?>" class="btn btn-primary btn-don animate__animated animate__heartBeat d-none d-md-inline-block">
                <i class="fa-solid fa-hand-holding-dollar"></i> Faire un don
            </a>
        </div>
    </div>

    <!-- Carousel -->
    <div id="carouselCampagne" class="carousel slide mb-5 animate__animated animate__fadeIn" data-bs-ride="carousel">
      <div class="carousel-inner rounded">
        <?php
          $images = [];

          if (!empty($campagne['image_campagne']) && file_exists('../uploads/campagnes/' . $campagne['image_campagne'])) {
              $images[] = '../uploads/campagnes/' . htmlspecialchars($campagne['image_campagne']);
          }
          // Ajout images externes de qualité, exemples
          $images[] = "https://source.unsplash.com/1200x500/?help,charity";
          $images[] = "https://source.unsplash.com/1200x500/?community,donation";

          foreach ($images as $i => $img) {
              $active = ($i === 0) ? "active" : "";
              echo "<div class='carousel-item $active'>";
              echo "<img src='$img' class='d-block w-100' alt='Image campagne'>";
              echo "</div>";
          }
        ?>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselCampagne" data-bs-slide="prev">
        <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
        <span class="visually-hidden">Précédent</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselCampagne" data-bs-slide="next">
        <span class="carousel-control-next-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
        <span class="visually-hidden">Suivant</span>
      </button>
    </div>

    <!-- Description détaillée -->
    <section class="mb-5">
        <h2 class="section-title">Description détaillée</h2>
        <p class="fs-5"><?= nl2br(htmlspecialchars($campagne['description'])) ?></p>
    </section>

    <!-- Bénéficiaire -->
    <section class="mb-5">
        <h2 class="section-title">Bénéficiaire de la campagne</h2>
        <div class="card p-3 shadow-sm">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-user fa-3x text-primary me-3"></i>
                <div>
                  <h4 class="mb-1"><?= htmlspecialchars(($campagne['b_prenom'] ?? '') . ' ' . ($campagne['b_nom'] ?? '')) ?></h4>
                  <p class="mb-0"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($campagne['b_tel'] ?? '') ?></p>
                </div>

            </div>
        </div>
    </section>

    <!-- Objectifs et montant -->
<section data-aos="fade-up" class="mb-5">
    <h2 class="section-title">Objectifs financiers</h2>
    <p><i class="fa-solid fa-bullseye"></i> Montant visé : 
       <strong><?= number_format($campagne['montant_vise'], 0, ',', ' ') ?> FCFA</strong></p>

    <!-- Montant atteint avec compteur -->
    <p><i class="fa-solid fa-hand-holding-dollar"></i> Montant atteint : 
       <strong><span id="compteurMontant"><?= number_format($campagne['montant_atteint'], 0, ',', ' ') ?></span> FCFA</strong>
    </p>

    <?php
        $progress = 0;
        if ($campagne['montant_vise'] > 0) {
            $progress = min(100, ($campagne['montant_atteint'] / $campagne['montant_vise']) * 100);
        }
    ?>
    <div class="progress rounded-pill">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
             role="progressbar" style="width: <?= $progress ?>%" 
             aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
             <?= round($progress, 2) ?>%
        </div>
    </div>
</section>


    <!-- Galerie Photos -->
    <section class="mb-5">
        <h2 class="section-title">Galerie photos</h2>
        <div class="row g-3">
            <?php
            // Galerie avec images fixes et dynamiques
            $galerie_images = [
                "https://source.unsplash.com/400x300/?volunteering",
                "https://source.unsplash.com/400x300/?helping",
                "https://source.unsplash.com/400x300/?charity",
                "https://source.unsplash.com/400x300/?community",
                "https://source.unsplash.com/400x300/?fundraising",
                "https://source.unsplash.com/400x300/?donation"
            ];
            foreach ($galerie_images as $img) {
                echo "<div class='col-sm-6 col-md-4 col-lg-3'>";
                echo "<div class='card shadow-sm'>";
                echo "<img src='$img' class='card-img-top rounded' alt='Image galerie'>";
                echo "</div></div>";
            }
            ?>
        </div>
    </section>

    <!-- Témoignages -->
    <section class="mb-5">
        <h2 class="section-title">Témoignages</h2>
        <div class="row">
            <?php
            // Témoignages fictifs pour dynamiser la page
            $temoignages = [
                [
                    'texte' => "Cette campagne a changé ma vie. Merci à tous ceux qui ont contribué.",
                    'nom' => "Amina Diallo",
                    'role' => "Bénéficiaire"
                ],
                [
                    'texte' => "J'ai pu aider grâce à cette campagne. Une expérience enrichissante !",
                    'nom' => "Cheikh Ndiaye",
                    'role' => "Donateur"
                ],
                [
                    'texte' => "Bravo à toute l'équipe pour leur engagement.",
                    'nom' => "Fatou Sow",
                    'role' => "Volontaire"
                ]
            ];

            foreach ($temoignages as $temoignage) {
                echo '<div class="col-md-4">';
                echo '<div class="testimonial-card animate__animated animate__fadeInUp">';
                echo '<p class="fst-italic">"' . htmlspecialchars($temoignage['texte']) . '"</p>';
                echo '<h5 class="mt-3 mb-0">' . htmlspecialchars($temoignage['nom']) . '</h5>';
                echo '<small class="text-muted">' . htmlspecialchars($temoignage['role']) . '</small>';
                echo '</div></div>';
            }
            ?>
        </div>
    </section>

    <!-- Formulaire de don simplifié -->
    <section class="mb-5">
        <h2 class="section-title">Faire un don</h2>
        <form action="../public/dons.php" method="post" class="p-4 bg-white shadow-sm rounded">
            <input type="hidden" name="campagne_id" value="<?= $campagne_id ?>" />
            <div class="mb-3">
                <label for="nom" class="form-label">Votre nom</label>
                <input type="text" name="nom" id="nom" class="form-control" required placeholder="Ex: Abdoulaye Sene" />
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Votre e-mail</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="exemple@email.com" />
            </div>
                        <div class="mb-3">
                <label for="montant" class="form-label">Montant du don (FCFA)</label>
                <input type="number" name="montant" id="montant" class="form-control" min="100" required placeholder="Ex: 5000" />
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message (optionnel)</label>
                <textarea name="message" id="message" class="form-control" rows="3" placeholder="Un mot pour encourager..."></textarea>
            </div>
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fa-solid fa-hand-holding-dollar"></i> Envoyer mon don
            </button>
        </form>
    </section>

</div>

<?php include_once '../includes/footer.php'; ?>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const montant = <?= $campagne['montant_atteint'] ?>;
    const el = document.getElementById("compteurMontant");
    let current = 0;
    const step = Math.ceil(montant / 50);

    const interval = setInterval(() => {
      current += step;
      if (current >= montant) {
        current = montant;
        clearInterval(interval);
      }
      el.textContent = current.toLocaleString('fr-FR');
    }, 30);
  });
</script>


<!-- Bootstrap JS + Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
