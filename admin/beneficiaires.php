<?php
$page_title = "Gestion des Bénéficiaires";
$page_title = "Modifier la Campagne";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}
$pdo = getPDO();

// Logique de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_beneficiaire') {
    if (isset($_POST['beneficiaire_id']) && !empty($_POST['beneficiaire_id'])) {
        $beneficiaire_id_to_delete = (int)$_POST['beneficiaire_id'];
        try {
            // Vérifier si le bénéficiaire est lié à des campagnes actives
            $stmtCheckCampagnes = $pdo->prepare("SELECT COUNT(*) FROM campagnes WHERE beneficiaire_id = :id AND statut = 'en cours'");
            $stmtCheckCampagnes->execute([':id' => $beneficiaire_id_to_delete]);
            if ($stmtCheckCampagnes->fetchColumn() > 0) {
                set_flash_message("Impossible de supprimer le bénéficiaire car il est associé à des campagnes en cours. Mettez à jour ces campagnes d'abord.", "warning");
            } else {
                // Mettre à NULL beneficiaire_id dans les campagnes terminées/suspendues ou supprimer ces campagnes (selon votre logique métier)
                $stmtUpdateCampagnes = $pdo->prepare("UPDATE campagnes SET beneficiaire_id = NULL WHERE beneficiaire_id = :id");
                $stmtUpdateCampagnes->execute([':id' => $beneficiaire_id_to_delete]);

                // Récupérer les noms des fichiers d'identité pour suppression
                $stmtFiles = $pdo->prepare("SELECT identite_recto, identite_verso FROM beneficiaires WHERE id = :id");
                $stmtFiles->execute([':id' => $beneficiaire_id_to_delete]);
                $files = $stmtFiles->fetch(PDO::FETCH_ASSOC);

                $stmtDelete = $pdo->prepare("DELETE FROM beneficiaires WHERE id = :id");
                $stmtDelete->execute([':id' => $beneficiaire_id_to_delete]);

                // Supprimer les fichiers physiques
                if ($files) {
                    if ($files['identite_recto'] && file_exists(ROOT_PATH . 'uploads/identites/' . $files['identite_recto'])) {
                        unlink(ROOT_PATH . 'uploads/identites/' . $files['identite_recto']);
                    }
                    if ($files['identite_verso'] && file_exists(ROOT_PATH . 'uploads/identites/' . $files['identite_verso'])) {
                        unlink(ROOT_PATH . 'uploads/identites/' . $files['identite_verso']);
                    }
                }
                set_flash_message("Bénéficiaire supprimé avec succès.", "success");
            }
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la suppression du bénéficiaire: " . $e->getMessage(), "danger");
        }
    } else {
        set_flash_message("ID de bénéficiaire manquant pour la suppression.", "warning");
    }
    redirect('beneficiaires.php');
}

// Récupération des bénéficiaires
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$statut_filter = isset($_GET['statut']) ? sanitize($_GET['statut']) : '';

$sql = "SELECT b.*, COUNT(c.id) as nombre_campagnes
        FROM beneficiaires b
        LEFT JOIN campagnes c ON b.id = c.beneficiaire_id";
$params = [];
$whereClauses = [];

if (!empty($search)) {
    $whereClauses[] = "(b.prenom LIKE :search OR b.nom LIKE :search OR b.email LIKE :search OR b.telephone LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($statut_filter)) {
    $whereClauses[] = "b.statut = :statut";
    $params[':statut'] = $statut_filter;
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " GROUP BY b.id ORDER BY b.date_enregistrement DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$beneficiaires_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<style>
    /* Styles spécifiques pour beneficiaires.php - "Design Extraordinaire" */
    :root {
        /* Palette de couleurs inspirée d'un coucher de soleil sénégalais moderne */
        --benef-primary-deep: #8C3A00; /* Marron profond terreux */
        --benef-primary-orange: #FF6B00; /* Orange vif */
        --benef-accent-gold: #FFC107; /* Or / Jaune soleil */
        --benef-neutral-sand: #F5E9D4; /* Sable clair */
        --benef-text-dark: #4A3B31;  /* Marron foncé pour texte */
        --benef-text-light: #795548; /* Marron plus clair pour texte secondaire */
    }

    body { /* Hérite de header_admin, mais on peut surcharger */
        /* background: linear-gradient(120deg, var(--benef-neutral-sand) 0%, #fff 100%); */
    }

    .page-title-container h1 i {
        color: var(--benef-primary-orange); /* Icône du titre de page */
    }

    .btn-add-beneficiaire {
        background: linear-gradient(45deg, var(--benef-primary-orange), var(--benef-accent-gold));
        border: none;
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);
        transition: all 0.3s ease;
    }
    .btn-add-beneficiaire:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 20px rgba(255, 107, 0, 0.5);
        color:white;
    }

    .card-beneficiaire-item {
        background-color: #fff;
        border-radius: 15px; /* Coins plus arrondis */
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); /* Ombre plus douce et diffuse */
        transition: transform 0.3s ease-out, box-shadow 0.3s ease-out;
        overflow: hidden; /* Pour le bandeau de statut */
        border: 1px solid #eee;
    }
    .card-beneficiaire-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .beneficiaire-header {
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #f0f0f0;
    }
    .beneficiaire-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: var(--benef-neutral-sand);
        color: var(--benef-primary-orange);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        font-weight: 600;
        margin-right: 1.5rem;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }
    .beneficiaire-info h5 {
        font-family: 'Poppins', sans-serif;
        color: var(--benef-text-dark);
        margin-bottom: 0.25rem;
        font-weight: 600;
    }
    .beneficiaire-info p {
        font-size: 0.9em;
        color: var(--benef-text-light);
        margin-bottom: 0;
    }
    .beneficiaire-info .fa-envelope, .beneficiaire-info .fa-phone { margin-right: 5px;}

    .beneficiaire-status-band {
        padding: 0.5rem 1.5rem;
        font-size: 0.85em;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-en-attente .beneficiaire-status-band { background-color: var(--benef-accent-gold); color: var(--benef-text-dark); }
    .status-valide .beneficiaire-status-band { background-color: var(--admin-success); color: white; } /* Vert de l'admin */
    .status-aide .beneficiaire-status-band { background-color: var(--admin-info); color: white; } /* Bleu de l'admin */

    .beneficiaire-details {
        padding: 1.5rem;
        font-size: 0.95em;
    }
    .beneficiaire-details strong { color: var(--benef-text-dark); }
    .beneficiaire-details .situation-preview {
        max-height: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 3; /* Nombre de lignes */
        -webkit-box-orient: vertical;
        color: var(--benef-text-light);
    }

    .beneficiaire-actions {
        padding: 1rem 1.5rem;
        text-align: right;
        background-color: #f9f9f9; /* Fond légèrement différent pour les actions */
        border-top: 1px solid #f0f0f0;
    }
    .beneficiaire-actions .btn {
        margin-left: 0.5rem;
        border-radius: 20px;
        padding: 0.4rem 1rem;
        font-size: 0.85rem;
    }
    .btn-view-details { background-color: var(--benef-primary-orange); border-color: var(--benef-primary-orange); color:white; }
    .btn-view-details:hover { background-color: var(--benef-primary-deep); border-color: var(--benef-primary-deep); color:white;}
    .btn-edit-benef { background-color: var(--admin-info); border-color: var(--admin-info); color:white; }
    .btn-edit-benef:hover { background-color: #2980b9; border-color: #2980b9; color:white;}

    .filter-bar-benef { /* Style de base hérité de style_admin.css, personnalisation ici */
        border: 1px solid var(--benef-neutral-sand);
        box-shadow: 0 5px 15px rgba(140, 58, 0, 0.05);
    }
    .filter-bar-benef .form-control-admin:focus {
        border-color: var(--benef-primary-orange);
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 0, .25);
    }
    .filter-bar-benef .btn-admin-primary {
        background-color: var(--benef-primary-deep);
        border-color: var(--benef-primary-deep);
    }
    .filter-bar-benef .btn-admin-primary:hover {
        background-color: #6D2D00; /* Marron plus foncé */
        border-color: #6D2D00;
    }

    /* Animation pour l'apparition des cartes */
    @keyframes fadeInCard {
        from { opacity: 0; transform: translateY(20px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .card-beneficiaire-item {
        animation: fadeInCard 0.5s ease-out forwards;
        opacity:0; /* Initial state for animation */
    }
    /* Appliquer un délai d'animation progressif */
    <?php for ($i=0; $i < 10; $i++): // Génère jusqu'à 10 délais ?>
    .card-beneficiaire-item:nth-child(<?= $i+1 ?>) {
        animation-delay: <?= $i * 0.07 ?>s;
    }
    <?php endfor; ?>

</style>


<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">



<div class="page-title-container">
    <h1><i class="fas fa-street-view"></i> Gestion des Bénéficiaires</h1>
    <a href="<?= BASE_URL ?>admin/ajouter_beneficiaire.php" class="btn btn-add-beneficiaire">
        <i class="fas fa-user-plus"></i> Ajouter un Bénéficiaire
    </a>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin filter-bar-benef">
    <div class="card-admin-header">
        <i class="fas fa-search-location"></i> Filtrer et Rechercher
    </div>
    <div class="card-body">
        <form method="GET" action="beneficiaires.php" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label-admin">Rechercher</label>
                <input type="text" class="form-control form-control-admin" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, prénom, email, tél...">
            </div>
            <div class="col-md-4">
                <label for="statut" class="form-label-admin">Statut du bénéficiaire</label>
                <select class="form-select form-control-admin" id="statut" name="statut">
                    <option value="">Tous les statuts</option>
                    <option value="en attente" <?= ($statut_filter == 'en attente') ? 'selected' : '' ?>>En attente</option>
                    <option value="validé" <?= ($statut_filter == 'validé') ? 'selected' : '' ?>>Validé</option>
                    <option value="aidé" <?= ($statut_filter == 'aidé') ? 'selected' : '' ?>>Aidé</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary w-100"><i class="fas fa-filter"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="row mt-4">
    <?php if (empty($beneficiaires_data)): ?>
        <div class="col-12">
            <div class="alert alert-light text-center p-4" style="border: 2px dashed var(--benef-neutral-sand); color: var(--benef-text-light);">
                <i class="fas fa-users-slash fa-3x mb-3" style="color: var(--benef-primary-orange);"></i>
                <h4>Aucun bénéficiaire trouvé.</h4>
                <p><?= (!empty($search) || !empty($statut_filter)) ? 'Essayez d\'ajuster vos filtres de recherche.' : 'Commencez par ajouter un nouveau bénéficiaire.' ?></p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($beneficiaires_data as $benef):
            $initiales = strtoupper(substr($benef['prenom'], 0, 1) . substr($benef['nom'], 0, 1));
            $statut_class = 'status-' . str_replace(' ', '-', strtolower($benef['statut'])); // e.g., status-en-attente
            if ($benef['statut'] === 'validé') $statut_class = 'status-valide';
            if ($benef['statut'] === 'aidé') $statut_class = 'status-aide';
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card-beneficiaire-item <?= $statut_class ?>">
                <div class="beneficiaire-status-band">
                    Statut : <?= ucfirst(htmlspecialchars($benef['statut'])) ?>
                </div>
                <div class="beneficiaire-header">
                    <div class="beneficiaire-avatar">
                        <?= htmlspecialchars($initiales) ?>
                    </div>
                    <div class="beneficiaire-info">
                        <h5><?= htmlspecialchars($benef['prenom'] . ' ' . $benef['nom']) ?></h5>
                        <p>
                            <?php if($benef['email']): ?><i class="fas fa-envelope"></i> <?= htmlspecialchars($benef['email']) ?><br><?php endif; ?>
                            <?php if($benef['telephone']): ?><i class="fas fa-phone"></i> <?= htmlspecialchars($benef['telephone']) ?><?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="beneficiaire-details">
                    <p><strong>Adresse:</strong> <?= htmlspecialchars($benef['adresse'] ?: 'N/A') ?></p>
                    <p><strong>Situation:</strong> <span class="situation-preview"><?= htmlspecialchars($benef['situation'] ?: 'N/A') ?></span></p>
                    <p><strong>Enregistré le:</strong> <?= date("d/m/Y", strtotime($benef['date_enregistrement'])) ?></p>
                    <p><strong>Campagnes associées:</strong> <span class="badge bg-secondary"><?= (int)$benef['nombre_campagnes'] ?></span></p>
                    <?php if ($benef['identite_recto'] || $benef['identite_verso']): ?>
                    <p><strong>Pièces d'identité:</strong>
                        <?php if ($benef['identite_recto']): ?>
                            <a href="<?= BASE_URL ?>uploads/identites/<?= htmlspecialchars($benef['identite_recto']) ?>" target="_blank" class="badge bg-info text-dark text-decoration-none"><i class="fas fa-id-card"></i> Recto</a>
                        <?php endif; ?>
                        <?php if ($benef['identite_verso']): ?>
                            <a href="<?= BASE_URL ?>uploads/identites/<?= htmlspecialchars($benef['identite_verso']) ?>" target="_blank" class="badge bg-info text-dark text-decoration-none ms-1"><i class="fas fa-id-card-alt"></i> Verso</a>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="beneficiaire-actions">
                    <!-- Pourrait y avoir un bouton "Voir détails complets" qui ouvre une modale ou une page dédiée -->
                    <a href="<?= BASE_URL ?>admin/modifier_beneficiaire.php?id=<?= (int)$benef['id'] ?>" class="btn btn-edit-benef btn-sm" title="Modifier">
                        <i class="fas fa-user-edit"></i> Modifier
                    </a>
                    <form method="POST" action="beneficiaires.php" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce bénéficiaire ? Les campagnes associées seront déliées.');">
                        <input type="hidden" name="action" value="delete_beneficiaire">
                        <input type="hidden" name="beneficiaire_id" value="<?= (int)$benef['id'] ?>">
                        <button type="submit" class="btn btn-admin-danger btn-sm" title="Supprimer">
                            <i class="fas fa-user-times"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

