<?php
$page_title = "Messages de Contact Reçus";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA'

function redirect($url) {
    header("Location: $url");
    exit;
}

$pdo = getPDO();

// Initialisation des messages de feedback
$error_page_msg = '';
$success_page_msg = '';

// --- Action : Marquer comme lu/non lu ou Supprimer ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_msg'])) {
    if (isset($_POST['message_id_msg']) && !empty($_POST['message_id_msg'])) {
        $message_id = (int)$_POST['message_id_msg'];
        $action_type = $_POST['action_msg'];
        $redirect_url = 'messages.php' . (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

        try {
            if ($action_type === 'toggle_read_msg') {
                $stmt_current_status = $pdo->prepare("SELECT est_lu FROM messages_contact WHERE id = :id");
                $stmt_current_status->execute([':id' => $message_id]);
                $current_est_lu = $stmt_current_status->fetchColumn();

                if ($current_est_lu !== false) {
                    $new_est_lu = !$current_est_lu;
                    if ($new_est_lu) {
                        $stmt_update = $pdo->prepare("UPDATE messages_contact SET est_lu = 1, date_lecture = NOW() WHERE id = :id");
                    } else {
                        $stmt_update = $pdo->prepare("UPDATE messages_contact SET est_lu = 0, date_lecture = NULL WHERE id = :id");
                    }
                    $stmt_update->execute([':id' => $message_id]);
                    set_flash_message("Statut du message mis à jour.", "success");
                } else {
                    set_flash_message("Message non trouvé pour la mise à jour du statut.", "warning");
                }
            } elseif ($action_type === 'delete_msg') {
                $stmt_delete = $pdo->prepare("DELETE FROM messages_contact WHERE id = :id");
                $stmt_delete->execute([':id' => $message_id]);
                set_flash_message("Message supprimé avec succès.", "success");
            }
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de l'action sur le message : " . $e->getMessage(), "danger");
        }

        redirect($redirect_url . (strpos($redirect_url, '?') === false ? '?' : '&') . 'timestamp=' . time() . '#message-card-' . $message_id);
    } else {
        set_flash_message("ID de message manquant.", "warning");
        redirect('messages.php' . (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    }
}

// --- Récupération des messages ---
$searchTerm_msg = isset($_GET['search_msg']) ? sanitize($_GET['search_msg']) : '';
$filterRead_msg = isset($_GET['filter_read_msg']) ? $_GET['filter_read_msg'] : 'all';
$sortOrder_msg = isset($_GET['sort_msg']) ? $_GET['sort_msg'] : 'DESC';

$sql_base_msg = "FROM messages_contact mc";
$sql_select_msg = "SELECT mc.id, mc.nom, mc.email, mc.sujet, mc.message, mc.date_reception, mc.est_lu, mc.date_lecture, mc.ip_address, mc.user_agent ";

$whereClauses_msg = [];
$params_msg = [];

if (!empty($searchTerm_msg)) {
    $whereClauses_msg[] = "(mc.nom LIKE :search_term OR mc.email LIKE :search_term OR mc.sujet LIKE :search_term OR mc.message LIKE :search_term)";
    $params_msg[':search_term'] = "%$searchTerm_msg%";
}

if ($filterRead_msg === 'read') {
    $whereClauses_msg[] = "mc.est_lu = 1";
} elseif ($filterRead_msg === 'unread') {
    $whereClauses_msg[] = "mc.est_lu = 0";
}

$sql_where_msg = "";
if (!empty($whereClauses_msg)) {
    $sql_where_msg = " WHERE " . implode(" AND ", $whereClauses_msg);
}

$sql_order_by_msg = " ORDER BY mc.date_reception " . ($sortOrder_msg === 'ASC' ? 'ASC' : 'DESC');

// Compter le total pour la pagination
$sql_count_query_msg = "SELECT COUNT(*) " . $sql_base_msg . $sql_where_msg;
$stmt_count_msg_list = $pdo->prepare($sql_count_query_msg);
$stmt_count_msg_list->execute($params_msg);
$total_messages_list = (int)$stmt_count_msg_list->fetchColumn();

// Pagination
$page_msg_list = isset($_GET['page_msg']) ? max(1, (int)$_GET['page_msg']) : 1;
$per_page_msg_list = 10;
$offset_msg_list = ($page_msg_list - 1) * $per_page_msg_list;
$total_pages_msg_list = ceil($total_messages_list / $per_page_msg_list);

if ($page_msg_list > $total_pages_msg_list && $total_pages_msg_list > 0) {
    $page_msg_list = $total_pages_msg_list;
    $offset_msg_list = ($page_msg_list - 1) * $per_page_msg_list;
}

// Requête finale avec LIMIT/OFFSET
$sql_final_msg = $sql_select_msg . $sql_base_msg . $sql_where_msg . $sql_order_by_msg . " LIMIT :limit OFFSET :offset";

try {
    $stmt_msg_list = $pdo->prepare($sql_final_msg);

    // Liaison des paramètres de filtre
    foreach ($params_msg as $key => $value) {
        $stmt_msg_list->bindValue($key, $value);
    }

    // Liaison LIMIT et OFFSET correctement en entier
    $stmt_msg_list->bindValue(':limit', (int)$per_page_msg_list, PDO::PARAM_INT);
    $stmt_msg_list->bindValue(':offset', (int)$offset_msg_list, PDO::PARAM_INT);

    $stmt_msg_list->execute();
    $messages_list = $stmt_msg_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_flash_message("Erreur lors de la récupération des messages : " . $e->getMessage(), "danger");
    $messages_list = [];
}

// Le header HTML peut être inclus ici, si nécessaire
?>

<style>
    /* Styles spécifiques pour messages.php - Design "Boîte de réception moderne" */
    :root {
        --message-icon-color: #00ACC1; /* Cyan un peu plus foncé */
        --message-card-unread-bg: #E0F7FA; /* Cyan très pâle */
        --message-card-read-bg: #F4F6F7;   /* Gris clair */
        --message-card-border-unread: var(--message-icon-color);
        --message-card-border-read: #CFD8DC; /* Gris bleu clair */
        --message-sender-text: #006064; /* Cyan très foncé */
        --message-subject-text: #37474F; /* Gris ardoise */
        --message-action-hover-bg: #B2EBF2; /* Cyan clair pour survol actions */
    }

    .page-title-container h1 i { color: var(--message-icon-color); }

    /* Styles pour la barre de filtres (hérités de style_admin.css, ici pour spécificité) */
    .filter-bar-messages .form-control-admin:focus,
    .filter-bar-messages .form-select-admin:focus {
        border-color: var(--message-icon-color);
        box-shadow: 0 0 0 0.2rem rgba(0, 172, 193, .25);
    }
    .filter-bar-messages .btn-admin-primary { /* Bouton "Filtrer" */
        background-color: var(--message-icon-color);
        border-color: var(--message-icon-color);
    }
     .filter-bar-messages .btn-admin-primary:hover {
        background-color: #00838F; /* Cyan plus foncé au survol */
        border-color: #00796B;
    }

    /* Carte pour chaque message */
    .message-entry-card {
        background-color: #fff;
        border-radius: 12px; /* Coins plus doux */
        margin-bottom: 1.5rem;
        box-shadow: 0 6px 22px rgba(0, 172, 193, 0.09);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); /* Transition plus douce */
        border-left-width: 6px; /* Bordure latérale plus épaisse */
        border-left-style: solid;
        position: relative; /* Pour le point "non lu" */
    }
    .message-entry-card.unread {
        background-color: var(--message-card-unread-bg);
        border-left-color: var(--message-card-border-unread);
    }
    .message-entry-card.read {
        background-color: var(--message-card-read-bg);
        border-left-color: var(--message-card-border-read);
    }
    .message-entry-card:hover {
        transform: translateY(-4px) scale(1.01); /* Effet de "soulèvement" léger */
        box-shadow: 0 10px 30px rgba(0, 172, 193, 0.15);
    }

    /* Indicateur "non lu" subtil */
    .message-entry-card.unread::before {
        content: '';
        position: absolute;
        top: 18px;
        left: -15px; /* Positionné à l'extérieur de la carte, sur la bordure */
        width: 8px;
        height: 8px;
        background-color: var(--admin-accent); /* Rouge (ou une autre couleur d'alerte) */
        border-radius: 50%;
        box-shadow: 0 0 5px var(--admin-accent);
    }


    .message-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #E0F2F1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9em;
    }
    .message-card-header .sender-info .sender-name {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        color: var(--message-sender-text);
    }
    .message-card-header .sender-info .sender-email {
        color: #455A64; margin-left: 8px; text-decoration: none;
    }
    .message-card-header .sender-info .sender-email:hover { text-decoration: underline;}

    .message-card-header .message-date-ip { font-size: 0.85em; color: #78909C; text-align: right; }
    .message-card-header .message-date-ip .ip-info { display:block; font-size:0.9em; margin-top:2px;}

    .message-card-body { padding: 1.25rem 1.5rem; }
    .message-card-body .message-subject {
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        color: var(--message-subject-text);
        margin-bottom: 0.75rem;
        font-size: 1.15rem; /* Sujet un peu plus grand */
        display: flex; align-items: center;
    }
    .message-card-body .message-subject i { margin-right: 8px; }

    .message-content-wrapper {
        max-height: 180px; overflow-y: auto;
        padding: 12px 15px;
        background-color: #FBFCFD; /* Fond très clair */
        border: 1px solid #E8F0F2;
        border-radius: 6px;
        font-size: 0.95em; line-height: 1.65; color: #37474F;
    }
    .message-content-wrapper::-webkit-scrollbar { width: 7px; }
    .message-content-wrapper::-webkit-scrollbar-track { background: #f0f4f5; border-radius: 4px; }
    .message-content-wrapper::-webkit-scrollbar-thumb { background: #c0d0d3; border-radius: 4px; }
    .message-content-wrapper::-webkit-scrollbar-thumb:hover { background: #a8bfc2; }

    .message-card-footer {
        padding: 0.8rem 1.5rem;
        background-color: #F8FAFB;
        border-top: 1px solid #E0F2F1;
        text-align: right;
    }
    .message-card-footer .btn { transition: background-color 0.2s ease, transform 0.2s ease; }
    .message-card-footer .btn:hover { transform: translateY(-1px); }

    .btn-toggle-read-msg.read { background-color: var(--admin-secondary); border-color: var(--admin-secondary); }
    .btn-toggle-read-msg.read:hover { background-color: #5a6268; border-color: #545b62; }
    .btn-toggle-read-msg.unread { background-color: var(--message-icon-color); border-color: var(--message-icon-color); }
    .btn-toggle-read-msg.unread:hover { background-color: #0097A7; border-color: #00838F; }

    /* Animation d'apparition des cartes messages */
    @keyframes cardEnter {
        from { opacity: 0; transform: scale(0.95) translateY(15px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .message-entry-card {
        animation: cardEnter 0.45s cubic-bezier(0.215, 0.610, 0.355, 1.000) forwards;
        opacity: 0;
    }
     <?php for ($i=0; $i < $per_page_msg_list; $i++): // Assigner un délai pour chaque carte sur la page ?>
    .message-entry-card:nth-child(<?= $i+1 ?>) { animation-delay: <?= $i * 0.07 ?>s; }
    <?php endfor; ?>

    /* Styles de pagination (copiés et adaptés si besoin) */
    .pagination-msg { list-style: none; padding: 0; display: flex; justify-content: center; margin-top: 2rem; }
    .pagination-msg li { margin: 0 4px; }
    .pagination-msg li a, .pagination-msg li span {
        display: inline-block; padding: 0.6rem 1rem; text-decoration: none;
        border: 1px solid #B2EBF2; color: var(--message-icon-color);
        border-radius: 25px; /* Forme de pilule */
        transition: all 0.25s ease-in-out;
        font-weight: 500;
    }
    .pagination-msg li a:hover { background-color: #E0F7FA; color: #00796B; transform: translateY(-2px); box-shadow: 0 2px 5px rgba(0,188,212,0.2); }
    .pagination-msg li.active a, .pagination-msg li.active span {
        background-color: var(--message-icon-color); color: white; border-color: var(--message-icon-color);
        box-shadow: 0 4px 10px rgba(0,188,212,0.3);
    }
    .pagination-msg li.disabled span { color: #B0BEC5; border-color: #ECEFF1; background-color: #F8F9FA; }
</style>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">



<div class="page-title-container d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-mail-bulk"></i> Messages de Contact</h1>
    <span class="badge bg-light text-dark p-2 fs-6 shadow-sm">
        <i class="fas fa-inbox"></i> Total: <?= $total_messages_list ?>
    </span>
</div>

<?php display_flash_message(); ?>
<!-- Affichage des messages d'erreur/succès spécifiques à la page, si flash_message n'est pas utilisé pour cela -->
<?php if (!empty($error_page_msg)): ?> <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_page_msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> <?php endif; ?>
<?php if (!empty($success_page_msg)): ?> <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_page_msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> <?php endif; ?>


<div class="card card-admin filter-bar-messages">
    <div class="card-admin-header">
        <i class="fas fa-filter"></i> Filtrer les Messages
    </div>
    <div class="card-body">
        <form method="GET" action="messages.php" class="row g-3 align-items-end">
            <div class="col-md-5 col-lg-4">
                <label for="search_msg" class="form-label-admin">Rechercher</label>
                <input type="text" class="form-control form-control-admin" id="search_msg" name="search_msg" value="<?= htmlspecialchars($searchTerm_msg) ?>" placeholder="Nom, email, sujet...">
            </div>
            <div class="col-md-3 col-lg-3">
                <label for="filter_read_msg" class="form-label-admin">Statut</label>
                <select class="form-select form-control-admin" id="filter_read_msg" name="filter_read_msg">
                    <option value="all" <?= $filterRead_msg === 'all' ? 'selected' : '' ?>>Tous</option>
                    <option value="unread" <?= $filterRead_msg === 'unread' ? 'selected' : '' ?>>Non lus</option>
                    <option value="read" <?= $filterRead_msg === 'read' ? 'selected' : '' ?>>Lus</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-3">
                <label for="sort_msg" class="form-label-admin">Trier par</label>
                <select class="form-select form-control-admin" id="sort_msg" name="sort_msg">
                    <option value="DESC" <?= $sortOrder_msg === 'DESC' ? 'selected' : '' ?>>Plus récents</option>
                    <option value="ASC" <?= $sortOrder_msg === 'ASC' ? 'selected' : '' ?>>Plus anciens</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-2">
                <button type="submit" class="btn btn-admin-primary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="mt-4">
    <?php if (empty($messages_list)): ?>
        <div class="alert alert-light text-center p-5" style="border: 2px dashed var(--message-card-border-read); color: var(--message-sender-text);">
            <i class="fas fa-comment-alt-slash fa-3x mb-3" style="opacity: 0.7;"></i>
            <h4>Boîte de réception vide.</h4>
            <p><?= (!empty($searchTerm_msg) || $filterRead_msg !== 'all') ? 'Aucun message ne correspond à vos critères de recherche.' : 'Vous n\'avez aucun nouveau message pour le moment.' ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($messages_list as $msg): ?>
        <div class="message-entry-card <?= $msg['est_lu'] ? 'read' : 'unread' ?>" id="message-card-<?= (int)$msg['id'] ?>">
            <div class="message-card-header">
                <div class="sender-info">
                    <strong class="sender-name"><?= htmlspecialchars($msg['nom']) ?></strong>
                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="sender-email" title="Envoyer un email à <?= htmlspecialchars($msg['email']) ?>">(<?= htmlspecialchars($msg['email']) ?>)</a>
                </div>
                <div class="message-date-ip">
                    <span>Reçu le: <?= date("d/m/Y à H:i", strtotime($msg['date_reception'])) ?></span>
                    <?php if ($msg['est_lu'] && $msg['date_lecture']): ?>
                        <small class="d-block">Lu le: <?= date("d/m/Y H:i", strtotime($msg['date_lecture'])) ?></small>
                    <?php endif; ?>
                    <?php if (!empty($msg['ip_address'])): ?>
                         <span class="ip-info text-muted" title="Agent: <?= htmlspecialchars($msg['user_agent'] ?: 'N/A') ?>">IP: <?= htmlspecialchars($msg['ip_address']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="message-card-body">
                <h5 class="message-subject">
                    <?php if (!$msg['est_lu']): ?> <i class="fas fa-envelope text-warning" title="Message non lu"></i> <?php else: ?> <i class="fas fa-envelope-open-text text-secondary" title="Message lu"></i> <?php endif; ?>
                    <?= htmlspecialchars($msg['sujet'] ?: '(Aucun sujet)') ?>
                </h5>
                <div class="message-content-wrapper">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                </div>
            </div>
            <div class="message-card-footer">
                <form method="POST" action="messages.php<?= ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '') ?>" style="display: inline;">
                    <input type="hidden" name="message_id_msg" value="<?= (int)$msg['id'] ?>">
                    <input type="hidden" name="action_msg" value="toggle_read_msg">
                    <button type="submit" class="btn btn-sm btn-toggle-read-msg <?= $msg['est_lu'] ? 'read' : 'unread' ?>">
                        <i class="fas <?= $msg['est_lu'] ? 'fa-undo-alt' : 'fa-check-circle' ?>"></i> <!-- Icônes plus explicites -->
                        <?= $msg['est_lu'] ? 'Marquer non lu' : 'Marquer lu' ?>
                    </button>
                </form>
                <form method="POST" action="messages.php" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.');">
                    <input type="hidden" name="action_msg" value="delete_msg">
                    <input type="hidden" name="message_id_msg" value="<?= (int)$msg['id'] ?>">
                    <button type="submit" class="btn btn-admin-danger btn-sm" title="Supprimer ce message">
                        <i class="fas fa-trash-alt"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($total_pages_msg_list > 1): ?>
<nav aria-label="Pagination Messages" class="mt-4">
    <ul class="pagination-msg">
        <?php
        // Construire les query params pour la pagination
        $queryParamsPagination = [];
        if (!empty($searchTerm_msg)) $queryParamsPagination['search_msg'] = $searchTerm_msg;
        if ($filterRead_msg !== 'all') $queryParamsPagination['filter_read_msg'] = $filterRead_msg;
        if ($sortOrder_msg !== 'DESC') $queryParamsPagination['sort_msg'] = $sortOrder_msg;
        $queryStringPagination = http_build_query($queryParamsPagination);

        if ($page_msg_list > 1): ?>
            <li class="page-item"><a class="page-link" href="?page_msg=<?= $page_msg_list - 1 ?>&<?= $queryStringPagination ?>">« Préc.</a></li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link">« Préc.</span></li>
        <?php endif;

        $start_page_m = max(1, $page_msg_list - 2);
        $end_page_m = min($total_pages_msg_list, $page_msg_list + 2);
        if ($start_page_m > 1) echo '<li class="page-item"><a class="page-link" href="?page_msg=1&'.$queryStringPagination.'">1</a></li>';
        if ($start_page_m > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $start_page_m; $i <= $end_page_m; $i++): ?>
            <li class="page-item <?= ($i == $page_msg_list) ? 'active' : '' ?>">
                <a class="page-link" href="?page_msg=<?= $i ?>&<?= $queryStringPagination ?>"><?= $i ?></a>
            </li>
        <?php endfor;
        if ($end_page_m < $total_pages_msg_list -1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        if ($end_page_m < $total_pages_msg_list) echo '<li class="page-item"><a class="page-link" href="?page_msg='.$total_pages_msg_list.'&'.$queryStringPagination.'">'.$total_pages_msg_list.'</a></li>';

        if ($page_msg_list < $total_pages_msg_list): ?>
            <li class="page-item"><a class="page-link" href="?page_msg=<?= $page_msg_list + 1 ?>&<?= $queryStringPagination ?>">Suiv. »</a></li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link">Suiv. »</span></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

