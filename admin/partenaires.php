<?php
$page_title = "Gestion des Partenaires";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}


$pdo = getPDO();

// Logique de suppression de partenaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_partenaire') {
    if (isset($_POST['partenaire_id']) && !empty($_POST['partenaire_id'])) {
        $partenaire_id_to_delete = (int)$_POST['partenaire_id'];
        try {
            // Récupérer le nom du logo pour le supprimer du serveur
            $stmtLogo = $pdo->prepare("SELECT logo FROM partenaires WHERE id = :id");
            $stmtLogo->execute([':id' => $partenaire_id_to_delete]);
            $logo_file = $stmtLogo->fetchColumn();

            $stmtDelete = $pdo->prepare("DELETE FROM partenaires WHERE id = :id");
            $stmtDelete->execute([':id' => $partenaire_id_to_delete]);

            // Supprimer le fichier logo physique
            if ($logo_file && file_exists(ROOT_PATH . 'assets/images/logos/' . $logo_file)) {
                unlink(ROOT_PATH . 'assets/images/logos/' . $logo_file);
            }
            set_flash_message("Partenaire supprimé avec succès.", "success");
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la suppression du partenaire: " . $e->getMessage(), "danger");
        }
    } else {
        set_flash_message("ID de partenaire manquant pour la suppression.", "warning");
    }
    redirect('partenaires.php');
}


// Récupération des partenaires
$search_partenaire = isset($_GET['search_partenaire']) ? sanitize($_GET['search_partenaire']) : '';

$sql_partenaires = "SELECT id, nom_partenaire, logo, site_web, description FROM partenaires";
$params_partenaires = [];

if (!empty($search_partenaire)) {
    $sql_partenaires .= " WHERE nom_partenaire LIKE :search OR description LIKE :search OR site_web LIKE :search";
    $params_partenaires[':search'] = "%$search_partenaire%";
}
$sql_partenaires .= " ORDER BY nom_partenaire ASC";

$stmt_partenaires = $pdo->prepare($sql_partenaires);
$stmt_partenaires->execute($params_partenaires);
$partenaires = $stmt_partenaires->fetchAll(PDO::FETCH_ASSOC);


?>

  <!-- Design partenaire stylisé -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Couleurs personnalisées */
:root {
    --partner-bg: #f5f7fa;
    --partner-primary: #4e73df;
    --partner-accent: #1cc88a;
    --partner-danger: #e74a3b;
    --partner-info: #36b9cc;
    --partner-dark: #343a40;
    --partner-border: #e3e6f0;
}

/* Page container */
.page-title-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--partner-primary);
    color: white;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    animation: fadeInDown 0.8s ease-out;
}
.page-title-container h1 {
    margin: 0;
    font-size: 1.8rem;
}
.page-title-container i {
    margin-right: 10px;
}
.btn-add-partner {
    background: var(--partner-accent);
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: bold;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}
.btn-add-partner:hover {
    background: #17a673;
    transform: scale(1.05);
}

/* Carte recherche */
.card-admin {
    background: white;
    border: 1px solid var(--partner-border);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.05);
    animation: fadeIn 1s ease;
}
.card-admin-header {
    font-weight: bold;
    font-size: 1.2rem;
    margin-bottom: 15px;
    color: var(--partner-dark);
}
.form-label-admin {
    font-weight: 600;
}
.btn-admin-primary {
    background: var(--partner-primary);
    border: none;
    color: white;
    font-weight: bold;
    transition: background 0.3s;
}
.btn-admin-primary:hover {
    background: #2e59d9;
}

/* Grille partenaire */
.partner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    animation: fadeInUp 0.7s ease-in-out;
}

/* Card partenaire */
.partner-card {
    background: #ffffff;
    border-radius: 15px;
    padding: 20px;
    border: 1px solid var(--partner-border);
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.partner-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

/* Logo */
.partner-logo-container {
    text-align: center;
    margin-bottom: 15px;
}
.partner-logo-container img {
    max-height: 70px;
    object-fit: contain;
    transition: transform 0.3s;
}
.partner-logo-container img:hover {
    transform: scale(1.1);
}
.no-logo {
    font-size: 3rem;
    color: #ccc;
}

/* Texte et liens */
.partner-card-body h5 {
    font-weight: bold;
    font-size: 1.2rem;
    color: var(--partner-dark);
}
.site-web-link {
    color: var(--partner-info);
    text-decoration: none;
    font-size: 0.95rem;
}
.site-web-link:hover {
    text-decoration: underline;
}
.partner-description {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 10px;
}

/* Boutons */
.partner-card-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
}
.btn-admin-info {
    background: var(--partner-info);
    color: white;
    padding: 5px 12px;
    border-radius: 8px;
}
.btn-admin-info:hover {
    background: #2c9faf;
}
.btn-admin-danger {
    background: var(--partner-danger);
    color: white;
    padding: 5px 12px;
    border-radius: 8px;
}
.btn-admin-danger:hover {
    background: #c92b1d;
}

/* Animations */
@keyframes fadeIn {
    from {opacity: 0;}
    to {opacity: 1;}
}
@keyframes fadeInUp {
    from {opacity: 0; transform: translateY(20px);}
    to {opacity: 1; transform: translateY(0);}
}
@keyframes fadeInDown {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}

/* Responsive */
@media (max-width: 576px) {
    .page-title-container {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}




     <?php for ($i=0; $i < 12; $i++): // Génère jusqu'à 12 délais ?>
    .partner-card:nth-child(<?= $i+1 ?>) {
        animation-delay: <?= $i * 0.06 ?>s;
    }
    <?php endfor; ?>

</style>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<div class="page-title-container">
    <h1><i class="fas fa-handshake"></i> Gestion des Partenaires</h1>
    <a href="<?= BASE_URL ?>admin/ajouter_partenaire.php" class="btn btn-add-partner">
        <i class="fas fa-plus-circle"></i> Ajouter un Partenaire
    </a>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin">
    <div class="card-admin-header">
        <i class="fas fa-search"></i> Rechercher un Partenaire
    </div>
    <div class="card-body">
        <form method="GET" action="partenaires.php" class="row g-3">
            <div class="col-md-10">
                <label for="search_partenaire" class="form-label-admin">Nom, description, site web...</label>
                <input type="text" class="form-control form-control-admin" id="search_partenaire" name="search_partenaire" value="<?= htmlspecialchars($search_partenaire) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="partner-grid mt-4">
    <?php if (empty($partenaires)): ?>
        <div class="col-12"> <!-- S'assurer que ce message prend toute la largeur si la grille est vide -->
            <div class="alert alert-light text-center p-5" style="border: 2px dashed #e0e0e0; color: #757575;">
                <i class="fas fa-users-slash fa-3x mb-3" style="color: var(--partner-accent-color);"></i>
                <h4>Aucun partenaire trouvé.</h4>
                <p><?= !empty($search_partenaire) ? 'Essayez d\'affiner votre recherche.' : 'Commencez par ajouter un nouveau partenaire.' ?></p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($partenaires as $partenaire): ?>
        <div class="partner-card">
            <div class="partner-logo-container">
                <?php if (!empty($partenaire['logo'])): ?>
                    <img src="<?= BASE_URL ?>assets/images/logos/<?= htmlspecialchars($partenaire['logo']) ?>"
                         alt="Logo <?= htmlspecialchars($partenaire['nom_partenaire']) ?>"
                         onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\'no-logo\'><i class=\'fas fa-building\'></i></span>';">
                <?php else: ?>
                    <span class="no-logo"><i class="fas fa-building"></i></span>
                <?php endif; ?>
            </div>
            <div class="partner-card-body">
                <h5><?= htmlspecialchars($partenaire['nom_partenaire']) ?></h5>
                <?php if (!empty($partenaire['site_web'])): ?>
                    <a href="<?= htmlspecialchars(add_http_if_needed($partenaire['site_web'])) ?>" target="_blank" class="site-web-link">
                        <i class="fas fa-globe"></i> <?= htmlspecialchars($partenaire['site_web']) ?>
                    </a>
                <?php endif; ?>
                <p class="partner-description"><?= nl2br(htmlspecialchars($partenaire['description'] ?: 'Aucune description.')) ?></p>
            </div>
            <div class="partner-card-actions">
                <a href="<?= BASE_URL ?>admin/modifier_partenaire.php?id=<?= (int)$partenaire['id'] ?>" class="btn btn-admin-info btn-sm" title="Modifier">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <form method="POST" action="partenaires.php" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce partenaire ?');">
                    <input type="hidden" name="action" value="delete_partenaire">
                    <input type="hidden" name="partenaire_id" value="<?= (int)$partenaire['id'] ?>">
                    <button type="submit" class="btn btn-admin-danger btn-sm" title="Supprimer">
                        <i class="fas fa-trash-alt"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
// Fonction utilitaire à mettre dans fonctions.php si elle n'y est pas déjà
if (!function_exists('add_http_if_needed')) {
    function add_http_if_needed($url) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        return $url;
    }
}

?>