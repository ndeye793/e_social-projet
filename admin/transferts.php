<?php
$page_title = "Gestion des Preuves de Transferts";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}


function upload_file($file, $destination_dir, $allowed_extensions = [], $max_size = 5242880, $prefix = '') {
    // Vérifier les erreurs
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ["Erreur lors de l'envoi du fichier."];
    }

    // Vérifier la taille
    if ($file['size'] > $max_size) {
        return ["Le fichier dépasse la taille maximale autorisée de " . ($max_size / 1048576) . " Mo."];
    }

    // Vérifier l'extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_extensions)) {
        return ["Extension de fichier non autorisée. Extensions autorisées : " . implode(', ', $allowed_extensions)];
    }

    // Créer le dossier s'il n'existe pas
    if (!file_exists($destination_dir)) {
        if (!mkdir($destination_dir, 0755, true)) {
            return ["Impossible de créer le dossier de destination."];
        }
    }

    // Générer un nom de fichier unique
    $new_filename = $prefix . uniqid() . '.' . $file_ext;
    $destination_path = rtrim($destination_dir, '/') . '/' . $new_filename;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $destination_path)) {
        return ["Échec du déplacement du fichier vers le dossier de destination."];
    }

    return $new_filename;
}


define('ROOT_PATH', __DIR__ . '/../'); // Racine du projet


$pdo = getPDO();

// Récupérer les campagnes pour le formulaire d'ajout
try {
    $stmt_campagnes_form = $pdo->query("SELECT id, titre FROM campagnes WHERE statut IN ('en cours', 'terminee') ORDER BY titre ASC");
    $campagnes_form = $stmt_campagnes_form->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_flash_message("Erreur lors de la récupération des campagnes pour le formulaire: " . $e->getMessage(), "danger");
    $campagnes_form = [];
}


// Initialisation pour le formulaire d'ajout
$campagne_id_form = '';
$commentaire_form = '';
$errors_ajout = [];

// Logique d'ajout de preuve de transfert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_transfert') {
    $campagne_id_form = filter_input(INPUT_POST, 'campagne_id', FILTER_VALIDATE_INT);
    $commentaire_form = sanitize($_POST['commentaire'] ?? '', true);

    if (empty($campagne_id_form)) $errors_ajout['campagne_id'] = "Veuillez sélectionner une campagne.";

    $fichier_justificatif_name = null;
    if (isset($_FILES['fichier_justificatif']) && $_FILES['fichier_justificatif']['error'] == UPLOAD_ERR_OK) {
        // Utiliser la fonction upload_file (s'assurer qu'elle est dans fonctions.php)
        $upload_result_file = upload_file(
            $_FILES['fichier_justificatif'],
            ROOT_PATH . 'uploads/transferts_admin/',
            ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'], // Extensions autorisées
            5242880, // 5MB Max
            'transfert_preuve_'
        );
        if (is_array($upload_result_file)) { // Erreur d'upload
            $errors_ajout['fichier_justificatif'] = implode(', ', $upload_result_file);
        } else {
            $fichier_justificatif_name = $upload_result_file;
        }
    } elseif (isset($_FILES['fichier_justificatif']) && $_FILES['fichier_justificatif']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors_ajout['fichier_justificatif'] = "Erreur d'upload du fichier (code: ".$_FILES['fichier_justificatif']['error'].").";
    } else {
        $errors_ajout['fichier_justificatif'] = "Un fichier justificatif est requis.";
    }


    if (empty($errors_ajout)) {
        try {
            $sql_insert = "INSERT INTO preuves_transfert (campagne_id, fichier_justificatif, commentaire, date_transfert)
                           VALUES (:campagne_id, :fichier, :commentaire, NOW())";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                ':campagne_id' => $campagne_id_form,
                ':fichier' => $fichier_justificatif_name,
                ':commentaire' => !empty($commentaire_form) ? $commentaire_form : null
            ]);
            set_flash_message("Preuve de transfert ajoutée avec succès !", "success");
            // Réinitialiser les champs du formulaire après succès
            $campagne_id_form = '';
            $commentaire_form = '';
        } catch (PDOException $e) {
            if ($fichier_justificatif_name && file_exists(ROOT_PATH . 'uploads/transferts_admin/' . $fichier_justificatif_name)) {
                unlink(ROOT_PATH . 'uploads/transferts_admin/' . $fichier_justificatif_name);
            }
            set_flash_message("Erreur lors de l'ajout de la preuve: " . $e->getMessage(), "danger");
        }
    } else {
        if ($fichier_justificatif_name && file_exists(ROOT_PATH . 'uploads/transferts_admin/' . $fichier_justificatif_name) && !isset($errors_ajout['fichier_justificatif'])) {
             unlink(ROOT_PATH . 'uploads/transferts_admin/' . $fichier_justificatif_name); // Supprimer si upload ok mais autres erreurs
        }
        set_flash_message("Veuillez corriger les erreurs dans le formulaire d'ajout.", "warning");
    }
    // Pas de redirect ici pour garder le formulaire visible avec les erreurs/succès
}


// Logique de suppression de preuve de transfert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_transfert') {
    if (isset($_POST['transfert_id']) && !empty($_POST['transfert_id'])) {
        $transfert_id_to_delete = (int)$_POST['transfert_id'];
        try {
            // Récupérer le nom du fichier pour le supprimer du serveur
            $stmtFile = $pdo->prepare("SELECT fichier_justificatif FROM preuves_transfert WHERE id = :id");
            $stmtFile->execute([':id' => $transfert_id_to_delete]);
            $file_to_delete = $stmtFile->fetchColumn();

            $stmtDelete = $pdo->prepare("DELETE FROM preuves_transfert WHERE id = :id");
            $stmtDelete->execute([':id' => $transfert_id_to_delete]);

            if ($file_to_delete && file_exists(ROOT_PATH . 'uploads/transferts_admin/' . $file_to_delete)) {
                unlink(ROOT_PATH . 'uploads/transferts_admin/' . $file_to_delete);
            }
            set_flash_message("Preuve de transfert supprimée avec succès.", "success");
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la suppression: " . $e->getMessage(), "danger");
        }
    } else {
        set_flash_message("ID de transfert manquant pour la suppression.", "warning");
    }
    redirect('transferts.php'); // Recharger pour voir les changements dans la liste
}


// Récupération des preuves de transferts pour affichage
$search_transfert = isset($_GET['search_transfert']) ? sanitize($_GET['search_transfert']) : '';
$campagne_filter = isset($_GET['campagne_filter']) ? (int)$_GET['campagne_filter'] : 0;

$sql_transferts = "SELECT pt.id, pt.fichier_justificatif, pt.commentaire, pt.date_transfert, c.titre AS nom_campagne, c.id AS id_campagne
                   FROM preuves_transfert pt
                   JOIN campagnes c ON pt.campagne_id = c.id";
$params_transferts = [];
$whereClauses_transferts = [];

if (!empty($search_transfert)) {
    $whereClauses_transferts[] = "(pt.commentaire LIKE :search OR c.titre LIKE :search OR pt.fichier_justificatif LIKE :search)";
    $params_transferts[':search'] = "%$search_transfert%";
}
if ($campagne_filter > 0) {
    $whereClauses_transferts[] = "pt.campagne_id = :campagne_id_filter";
    $params_transferts[':campagne_id_filter'] = $campagne_filter;
}

if (!empty($whereClauses_transferts)) {
    $sql_transferts .= " WHERE " . implode(" AND ", $whereClauses_transferts);
}
$sql_transferts .= " ORDER BY pt.date_transfert DESC";

$stmt_transferts = $pdo->prepare($sql_transferts);
$stmt_transferts->execute($params_transferts);
$preuves_transferts = $stmt_transferts->fetchAll(PDO::FETCH_ASSOC);

// Récupérer toutes les campagnes pour le filtre (différent de $campagnes_form qui ne prend que actives/terminées)
try {
    $stmt_campagnes_filter = $pdo->query("SELECT id, titre FROM campagnes ORDER BY titre ASC");
    $campagnes_filter_list = $stmt_campagnes_filter->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $campagnes_filter_list = [];
}

?>
<style>
    /* Styles spécifiques pour transferts.php */
    :root {
        --transfer-icon-color: #17A2B8; /* Teal / Cyan pour transferts */
        --transfer-form-bg: #e8f7f9; /* Cyan très clair */
        --transfer-table-header-bg: #d1ecf1; /* Cyan plus clair */
        --transfer-row-hover-bg: #f3fbfd;
        --transfer-file-icon-color: #007BFF; /* Bleu pour les liens de fichiers */
    }

    .page-title-container h1 i { color: var(--transfer-icon-color); }

    .card-form-transfert {
        background-color: var(--transfer-form-bg);
        border: 1px solid #bee5eb; /* Bordure assortie */
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(23, 162, 184, 0.1);
        margin-bottom: 2rem;
    }
    .card-form-transfert .card-admin-header {
        color: var(--transfer-icon-color);
        background-color: transparent; /* Laisser voir le fond de la carte */
        border-bottom: 1px solid #bee5eb;
        font-size: 1.1rem;
    }
    .card-form-transfert .form-control-admin:focus {
        border-color: var(--transfer-icon-color);
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, .25);
    }
    .btn-submit-transfert {
        background: linear-gradient(45deg, var(--transfer-icon-color), #138496);
        border: none; color: white; font-weight:500;
    }
    .btn-submit-transfert:hover { opacity: 0.9; color:white; transform: scale(1.02); }

    .transfert-table-container {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.07);
        overflow: hidden;
    }
    .table-transferts th {
        font-family: 'Poppins', sans-serif; font-weight: 600;
        background-color: var(--transfer-table-header-bg);
        color: var(--transfer-icon-color);
        border-bottom-width: 1px; border-color: #abdde5;
        padding: 0.9rem 1rem; font-size: 0.85em; text-transform: uppercase;
    }
    .table-transferts td { vertical-align: middle; padding: 0.9rem 1rem; color: #5a5c69; }
    .table-transferts tbody tr:hover { background-color: var(--transfer-row-hover-bg); }

    .file-link {
        color: var(--transfer-file-icon-color);
        text-decoration: none;
        font-weight: 500;
    }
    .file-link:hover { text-decoration: underline; color: #0056b3; }
    .file-link i { margin-right: 5px; }

    .filter-bar-transferts .form-control-admin:focus {
        border-color: var(--transfer-icon-color);
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, .25);
    }
    .filter-bar-transferts .btn-admin-primary {
        background-color: var(--transfer-icon-color);
        border-color: var(--transfer-icon-color);
    }
     .filter-bar-transferts .btn-admin-primary:hover {
        background-color: #138496; border-color: #117a8b;
    }
</style>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<div class="page-title-container">
    <h1><i class="fas fa-exchange-alt"></i> Gestion des Preuves de Transferts</h1>
    <!-- Le bouton d'ajout est implicite via le formulaire ci-dessous -->
</div>

<?php display_flash_message(); ?>

<!-- Formulaire d'ajout de preuve de transfert -->
<div class="card card-admin card-form-transfert">
    <div class="card-admin-header">
        <i class="fas fa-plus-circle"></i> Ajouter une Nouvelle Preuve de Transfert
    </div>
    <div class="card-body p-4">
        <form method="POST" action="transferts.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_transfert">
            <div class="row g-3">
                <div class="col-md-5">
                    <label for="campagne_id_form" class="form-label-admin">Campagne Concernée <span class="text-danger">*</span></label>
                    <select class="form-select form-control-admin <?= isset($errors_ajout['campagne_id']) ? 'is-invalid' : '' ?>" id="campagne_id_form" name="campagne_id" required>
                        <option value="">Sélectionner une campagne...</option>
                        <?php foreach ($campagnes_form as $camp_form): ?>
                            <option value="<?= (int)$camp_form['id'] ?>" <?= ($campagne_id_form == $camp_form['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($camp_form['titre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors_ajout['campagne_id'])): ?><div class="invalid-feedback"><?= $errors_ajout['campagne_id'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-7">
                    <label for="fichier_justificatif_form" class="form-label-admin">Fichier Justificatif <span class="text-danger">*</span> <small>(JPG, PNG, PDF, DOC, XLS - Max 5MB)</small></label>
                    <input class="form-control form-control-admin <?= isset($errors_ajout['fichier_justificatif']) ? 'is-invalid' : '' ?>" type="file" id="fichier_justificatif_form" name="fichier_justificatif" required>
                    <?php if (isset($errors_ajout['fichier_justificatif'])): ?><div class="invalid-feedback"><?= $errors_ajout['fichier_justificatif'] ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label for="commentaire_form" class="form-label-admin">Commentaire (Optionnel)</label>
                    <textarea class="form-control form-control-admin" id="commentaire_form" name="commentaire" rows="2"><?= htmlspecialchars($commentaire_form) ?></textarea>
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-submit-transfert"><i class="fas fa-paper-plane"></i> Soumettre la Preuve</button>
            </div>
        </form>
    </div>
</div>


<!-- Filtres pour la liste des transferts -->
<div class="card card-admin filter-bar-transferts mt-4">
    <div class="card-admin-header">
        <i class="fas fa-search"></i> Rechercher/Filtrer les Preuves
    </div>
    <div class="card-body">
        <form method="GET" action="transferts.php" class="row g-3">
            <div class="col-md-5">
                <label for="search_transfert" class="form-label-admin">Rechercher (Commentaire, Campagne, Fichier)</label>
                <input type="text" class="form-control form-control-admin" id="search_transfert" name="search_transfert" value="<?= htmlspecialchars($search_transfert) ?>">
            </div>
            <div class="col-md-5">
                <label for="campagne_filter" class="form-label-admin">Filtrer par Campagne</label>
                <select class="form-select form-control-admin" id="campagne_filter" name="campagne_filter">
                    <option value="0">Toutes les campagnes</option>
                    <?php foreach ($campagnes_filter_list as $camp_filter): ?>
                        <option value="<?= (int)$camp_filter['id'] ?>" <?= ($campagne_filter == $camp_filter['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($camp_filter['titre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary w-100"><i class="fas fa-filter"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>


<!-- Liste des preuves de transferts -->
<div class="transfert-table-container mt-4">
    <div class="card-admin-header d-flex justify-content-between align-items-center" style="border-bottom:none; border-top-left-radius: 10px; border-top-right-radius: 10px;">
        <span><i class="fas fa-list-alt"></i> Preuves de Transferts Enregistrées (<?= count($preuves_transferts) ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-transferts mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Campagne</th>
                    <th>Fichier Justificatif</th>
                    <th>Commentaire</th>
                    <th>Date du Transfert</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($preuves_transferts)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-file-invoice-dollar fa-2x text-muted mb-2"></i><br>
                            Aucune preuve de transfert trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($preuves_transferts as $index => $preuve):
                        $file_extension = strtolower(pathinfo($preuve['fichier_justificatif'], PATHINFO_EXTENSION));
                        $file_icon = 'fa-file-alt'; // Icône par défaut
                        if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $file_icon = 'fa-file-image';
                        elseif ($file_extension === 'pdf') $file_icon = 'fa-file-pdf';
                        elseif (in_array($file_extension, ['doc', 'docx'])) $file_icon = 'fa-file-word';
                        elseif (in_array($file_extension, ['xls', 'xlsx'])) $file_icon = 'fa-file-excel';
                    ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>public/campagne.php?id=<?= (int)$preuve['id_campagne'] ?>" target="_blank" title="Voir la campagne">
                                <?= htmlspecialchars($preuve['nom_campagne']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>uploads/transferts_admin/<?= htmlspecialchars($preuve['fichier_justificatif']) ?>" target="_blank" class="file-link">
                                <i class="fas <?= $file_icon ?>"></i> <?= htmlspecialchars(basename($preuve['fichier_justificatif'])) ?>
                            </a>
                        </td>
                        <td title="<?= htmlspecialchars($preuve['commentaire']) ?>" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($preuve['commentaire'] ?: 'N/A') ?>
                        </td>
                        <td><?= date("d/m/Y à H:i", strtotime($preuve['date_transfert'])) ?></td>
                        <td class="text-end action-buttons">
                            <form method="POST" action="transferts.php" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette preuve de transfert ?');">
                                <input type="hidden" name="action" value="delete_transfert">
                                <input type="hidden" name="transfert_id" value="<?= (int)$preuve['id'] ?>">
                                <button type="submit" class="btn btn-admin-danger btn-sm" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


