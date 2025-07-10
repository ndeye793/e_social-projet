<?php
$page_title = "Gestion des Abonnés à la Newsletter";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon ta préférence

function redirect($url) {
    header("Location: $url");
    exit;
}

$pdo = getPDO();

// Suppression d’un abonné
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_abonnement') {
    if (!empty($_POST['abonnement_id'])) {
        $abonnement_id_to_delete = (int)$_POST['abonnement_id'];
        try {
            $stmtDelete = $pdo->prepare("DELETE FROM abonnements_newsletter WHERE id = :id");
            $stmtDelete->execute([':id' => $abonnement_id_to_delete]);
            set_flash_message("Abonné supprimé avec succès de la newsletter.", "success");
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la suppression de l'abonné : " . $e->getMessage(), "danger");
        }
    } else {
        set_flash_message("ID d'abonné manquant pour la suppression.", "warning");
    }
    redirect('newsletter.php');
}

// Export CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    try {
        $stmt_export = $pdo->query("SELECT email, date_abonnement FROM abonnements_newsletter ORDER BY date_abonnement DESC");
        $abonnes_export = $stmt_export->fetchAll(PDO::FETCH_ASSOC);

        if (empty($abonnes_export)) {
            set_flash_message("Aucun abonné à exporter.", "info");
            redirect('newsletter.php');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="abonnes_newsletter_esocial_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Email', 'Date d\'abonnement'], ';');

        foreach ($abonnes_export as $abonne_export) {
            fputcsv($output, [
                $abonne_export['email'],
                date("d/m/Y H:i:s", strtotime($abonne_export['date_abonnement']))
            ], ';');
        }

        fclose($output);
        exit;

    } catch (PDOException $e) {
        set_flash_message("Erreur lors de l'exportation CSV : " . $e->getMessage(), "danger");
        redirect('newsletter.php');
    }
}

// Affichage abonnés avec recherche, tri et pagination
$search_email = isset($_GET['search_email']) ? sanitize($_GET['search_email']) : '';
$sort_order_nl = (isset($_GET['sort_nl']) && $_GET['sort_nl'] === 'ASC') ? 'ASC' : 'DESC';

$page_nl = isset($_GET['page_nl']) ? max(1, (int)$_GET['page_nl']) : 1;
$per_page_nl = 20;
$offset_nl = ($page_nl - 1) * $per_page_nl;

$sql_abonnements = "SELECT id, email, date_abonnement FROM abonnements_newsletter";
$params_abonnements = [];

if (!empty($search_email)) {
    $sql_abonnements .= " WHERE email LIKE :search_email";
    $params_abonnements[':search_email'] = "%$search_email%";
}

$sql_abonnements .= " ORDER BY date_abonnement $sort_order_nl";

// Récupération du total pour la pagination
$sql_count = str_replace("SELECT id, email, date_abonnement", "SELECT COUNT(*)", $sql_abonnements);
$stmt_count_nl = $pdo->prepare($sql_count);
$stmt_count_nl->execute($params_abonnements);
$total_abonnements = (int)$stmt_count_nl->fetchColumn();
$total_pages_nl = ceil($total_abonnements / $per_page_nl);

// Ajout pagination sans paramètre lié (MySQL n'accepte pas les bind pour LIMIT/OFFSET)
$sql_abonnements .= " LIMIT $per_page_nl OFFSET $offset_nl";

$stmt_abonnements = $pdo->prepare($sql_abonnements);
$stmt_abonnements->execute($params_abonnements);
$abonnements = $stmt_abonnements->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Styles spécifiques pour newsletter.php */
    :root {
        --newsletter-icon-color: #FF9800; /* Orange pour la newsletter */
        --newsletter-header-bg: #fff8e1;  /* Jaune très pâle */
        --newsletter-row-hover-bg: #fff महेcf0; /* Jaune encore plus pâle */
        --newsletter-export-btn-bg: #4CAF50; /* Vert pour l'export */
    }

    .page-title-container h1 i { color: var(--newsletter-icon-color); }

    .btn-export-csv {
        background-color: var(--newsletter-export-btn-bg);
        border-color: var(--newsletter-export-btn-bg);
        color: white;
        font-weight: 500;
        box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
    }
    .btn-export-csv:hover {
        background-color: #43A047;
        border-color: #388E3C;
        color: white;
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    }

    .newsletter-table-container {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 6px 20px rgba(255, 152, 0, 0.08); /* Ombre orange subtile */
        overflow: hidden;
    }

    .table-newsletter th {
        font-family: 'Poppins', sans-serif; font-weight: 600;
        background-color: var(--newsletter-header-bg);
        color: var(--newsletter-icon-color);
        border-bottom-width: 1px; border-color: #ffecb3; /* Bordure jaune pâle */
        padding: 0.9rem 1rem; font-size: 0.85em; text-transform: uppercase;
    }
    .table-newsletter td { vertical-align: middle; padding: 0.9rem 1rem; color: #5a5c69; }
    .table-newsletter tbody tr:hover { background-color: var(--newsletter-row-hover-bg); }
    .table-newsletter .email-col a {
        color: var(--admin-primary); /* Bleu admin pour le lien mail */
        text-decoration: none;
        font-weight: 500;
    }
    .table-newsletter .email-col a:hover { color: var(--admin-info); text-decoration: underline;}

    .filter-bar-newsletter .form-control-admin:focus {
        border-color: var(--newsletter-icon-color);
        box-shadow: 0 0 0 0.2rem rgba(255, 152, 0, .25);
    }
    .filter-bar-newsletter .btn-admin-primary {
        background-color: var(--newsletter-icon-color);
        border-color: var(--newsletter-icon-color);
    }
     .filter-bar-newsletter .btn-admin-primary:hover {
        background-color: #FB8C00; border-color: #F57C00;
    }

    /* Pagination Styles (simplifiés, peuvent être améliorés) */
    .pagination-nl { list-style: none; padding: 0; display: flex; justify-content: center; margin-top: 1.5rem; }
    .pagination-nl li { margin: 0 5px; }
    .pagination-nl li a, .pagination-nl li span {
        display: block; padding: 0.5rem 0.9rem; text-decoration: none;
        border: 1px solid #ffcc80; color: var(--newsletter-icon-color);
        border-radius: 20px; /* Liens de pagination ronds */
        transition: all 0.2s ease;
    }
    .pagination-nl li a:hover { background-color: var(--newsletter-header-bg); color: #E65100; }
    .pagination-nl li.active a, .pagination-nl li.active span {
        background-color: var(--newsletter-icon-color); color: white; border-color: var(--newsletter-icon-color);
    }
    .pagination-nl li.disabled span { color: #ccc; border-color: #eee; }

</style>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">



<div class="page-title-container d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-newspaper"></i> Gestion des Abonnés Newsletter</h1>
    <a href="newsletter.php?action=export_csv" class="btn btn-export-csv">
        <i class="fas fa-file-csv"></i> Exporter en CSV
    </a>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin filter-bar-newsletter">
    <div class="card-admin-header">
        <i class="fas fa-filter"></i> Filtrer les Abonnés
    </div>
    <div class="card-body">
        <form method="GET" action="newsletter.php" class="row g-3">
            <div class="col-md-7">
                <label for="search_email" class="form-label-admin">Rechercher par Email</label>
                <input type="text" class="form-control form-control-admin" id="search_email" name="search_email" value="<?= htmlspecialchars($search_email) ?>">
            </div>
            <div class="col-md-3">
                <label for="sort_nl" class="form-label-admin">Trier par date</label>
                 <select class="form-select form-control-admin" id="sort_nl" name="sort_nl">
                    <option value="DESC" <?= ($sort_order_nl == 'DESC') ? 'selected' : '' ?>>Plus récents d'abord</option>
                    <option value="ASC" <?= ($sort_order_nl == 'ASC') ? 'selected' : '' ?>>Plus anciens d'abord</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="newsletter-table-container mt-4">
    <div class="card-admin-header d-flex justify-content-between align-items-center" style="border-bottom:none; border-top-left-radius: 10px; border-top-right-radius: 10px;">
        <span><i class="fas fa-users"></i> Liste des Abonnés (Total: <?= $total_abonnements ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-newsletter mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Email</th>
                    <th>Date d'Abonnement</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($abonnements)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="fas fa-envelope-open-text fa-2x text-muted mb-2"></i><br>
                            Aucun abonné trouvé <?= !empty($search_email) ? 'pour cette recherche.' : '.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($abonnements as $index => $abonne): ?>
                    <tr>
                        <td><?= $offset_nl + $index + 1 ?></td>
                        <td class="email-col"><a href="mailto:<?= htmlspecialchars($abonne['email']) ?>"><?= htmlspecialchars($abonne['email']) ?></a></td>
                        <td><?= date("d/m/Y à H:i", strtotime($abonne['date_abonnement'])) ?></td>
                        <td class="text-end action-buttons">
                            <form method="POST" action="newsletter.php" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet abonné de la newsletter ?');">
                                <input type="hidden" name="action" value="delete_abonnement">
                                <input type="hidden" name="abonnement_id" value="<?= (int)$abonne['id'] ?>">
                                <button type="submit" class="btn btn-admin-danger btn-sm" title="Supprimer l'abonné">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total_pages_nl > 1): ?>
    <div class="card-footer bg-light py-2">
        <nav aria-label="Pagination Newsletter">
            <ul class="pagination-nl">
                <?php if ($page_nl > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page_nl=<?= $page_nl - 1 ?>&search_email=<?=urlencode($search_email)?>&sort_nl=<?=$sort_order_nl?>">« Préc.</a></li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">« Préc.</span></li>
                <?php endif; ?>

                <?php
                // Logique de pagination plus avancée (ex: afficher quelques pages autour de l'actuelle)
                $start_page = max(1, $page_nl - 2);
                $end_page = min($total_pages_nl, $page_nl + 2);

                if ($start_page > 1) echo '<li class="page-item"><a class="page-link" href="?page_nl=1&search_email='.urlencode($search_email).'&sort_nl='.$sort_order_nl.'">1</a></li>';
                if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?= ($i == $page_nl) ? 'active' : '' ?>">
                        <a class="page-link" href="?page_nl=<?= $i ?>&search_email=<?=urlencode($search_email)?>&sort_nl=<?=$sort_order_nl?>"><?= $i ?></a>
                    </li>
                <?php endfor;

                if ($end_page < $total_pages_nl -1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                if ($end_page < $total_pages_nl) echo '<li class="page-item"><a class="page-link" href="?page_nl='.$total_pages_nl.'&search_email='.urlencode($search_email).'&sort_nl='.$sort_order_nl.'">'.$total_pages_nl.'</a></li>';
                ?>

                <?php if ($page_nl < $total_pages_nl): ?>
                    <li class="page-item"><a class="page-link" href="?page_nl=<?= $page_nl + 1 ?>&search_email=<?=urlencode($search_email)?>&sort_nl=<?=$sort_order_nl?>">Suiv. »</a></li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">Suiv. »</span></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

