<?php
session_start();
require_once '../config/db.php';          // Pour getPDO()
require_once '../config/constantes.php';  // Pour BASE_URL (si utilisé)
require_once '../includes/fonctions.php'; // Pour sanitize(), redirect(), etc.

// --- Redirection simple ---
function redirect($url) {
    header("Location: $url");
    exit;
}

// --- Initialisation ---
$error_message = '';
$success_message = '';
$messages = [];
$pdo = getPDO();

// --- Traitement des actions : lire / supprimer ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['message_id'])) {
        $message_id = (int) $_POST['message_id'];
        $action = $_POST['action'];

        try {
            if ($action === 'toggle_read') {
                // Inverser le statut de lecture et ajuster date_lecture
                $stmt = $pdo->prepare("
                    UPDATE messages_contact 
                    SET 
                        est_lu = NOT est_lu, 
                        date_lecture = CASE WHEN est_lu = 0 THEN NOW() ELSE NULL END 
                    WHERE id = :id
                ");
                $stmt->execute([':id' => $message_id]);
                $success_message = "Statut du message mis à jour.";
            } elseif ($action === 'delete') {
                // Supprimer le message
                $stmt = $pdo->prepare("DELETE FROM messages_contact WHERE id = :id");
                $stmt->execute([':id' => $message_id]);
                $success_message = "Message supprimé avec succès.";
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'action sur le message : " . $e->getMessage();
        }
    }
}

// --- Récupération des messages ---
$searchTerm = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filterRead = $_GET['filter_read'] ?? 'all';  // 'all', 'read', 'unread'
$sortOrder = strtoupper($_GET['sort'] ?? 'DESC'); // 'ASC' ou 'DESC'

$sql = "SELECT id, nom, email, sujet, message, date_reception, est_lu, date_lecture FROM messages_contact";
$whereClauses = [];
$params = [];

if (!empty($searchTerm)) {
    $whereClauses[] = "(nom LIKE :search OR email LIKE :search OR sujet LIKE :search OR message LIKE :search)";
    $params[':search'] = "%$searchTerm%";
}

if ($filterRead === 'read') {
    $whereClauses[] = "est_lu = 1";
} elseif ($filterRead === 'unread') {
    $whereClauses[] = "est_lu = 0";
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY date_reception " . ($sortOrder === 'ASC' ? 'ASC' : 'DESC');

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des messages : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Messages de Contact - E-Social Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #343a40; /* Dark grey / black */
            --admin-secondary: #6c757d; /* Medium grey */
            --admin-light: #f8f9fa;    /* Light grey */
            --admin-accent: #007bff;   /* Bootstrap blue for accents */
            --unread-bg: #fff3cd;      /* Light yellow for unread messages */
            --read-bg: #e9ecef;        /* Lighter grey for read messages */
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--admin-light);
            color: #212529;
        }

        .admin-navbar {
            background-color: var(--admin-primary);
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .admin-navbar .navbar-brand, .admin-navbar .nav-link {
            color: white;
            font-family: 'Poppins', sans-serif;
        }
        .admin-navbar .nav-link:hover {
            color: #e9ecef;
        }
        .admin-navbar .nav-link.active {
            font-weight: 600;
            border-bottom: 2px solid var(--admin-accent);
        }


        .page-header {
            background-color: #ffffff;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .page-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--admin-primary);
        }
        .page-header .breadcrumb-item a {
            color: var(--admin-accent);
            text-decoration: none;
        }
        .page-header .breadcrumb-item.active {
            color: var(--admin-secondary);
        }

        .filter-bar {
            background-color: #fff;
            padding: 1rem;
            border-radius: .375rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .message-card {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
            margin-bottom: 1rem;
            transition: box-shadow .2s ease-in-out;
        }
        .message-card:hover {
            box-shadow: 0 .25rem .75rem rgba(0,0,0,.075);
        }
        .message-card.unread {
            background-color: var(--unread-bg);
            border-left: 5px solid var(--admin-accent);
        }
         .message-card.read {
            background-color: var(--read-bg);
            border-left: 5px solid var(--admin-secondary);
        }

        .message-header {
            padding: .75rem 1.25rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .message-header .sender-info strong {
            font-family: 'Poppins', sans-serif;
        }
        .message-header .date-info {
            font-size: .85em;
            color: var(--admin-secondary);
        }

        .message-body {
            padding: 1.25rem;
        }
        .message-body .subject {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin-bottom: .5rem;
            color: var(--admin-primary);
        }
        .message-body .content {
            white-space: pre-wrap; /* Conserver les sauts de ligne du message */
            max-height: 200px; /* Limiter la hauteur, rendre déroulant si plus long */
            overflow-y: auto;
            padding-right: 10px; /* Pour la barre de défilement */
            border: 1px dashed #eee;
            padding: 10px;
            background: #fcfcfc;
            border-radius: 4px;
        }
        /* Style de la scrollbar pour le contenu du message */
        .message-body .content::-webkit-scrollbar {
            width: 8px;
        }
        .message-body .content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .message-body .content::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 4px;
        }
        .message-body .content::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }


        .message-actions {
            padding: .75rem 1.25rem;
            border-top: 1px solid #dee2e6;
            text-align: right;
        }
        .message-actions .btn {
            margin-left: .5rem;
        }
        .btn-toggle-read.read { /* Style pour le bouton quand le message est lu */
            background-color: var(--admin-secondary);
            border-color: var(--admin-secondary);
        }
        .btn-toggle-read.read:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        .btn-admin-primary {
            background-color: var(--admin-accent);
            border-color: var(--admin-accent);
            color: white;
        }
        .btn-admin-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        .btn-admin-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .btn-admin-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .btn-admin-secondary {
             background-color: var(--admin-secondary);
            border-color: var(--admin-secondary);
            color: white;
        }
         .btn-admin-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        .no-messages {
            text-align: center;
            padding: 3rem;
            background-color: #fff;
            border-radius: .375rem;
            color: var(--admin-secondary);
        }
        .no-messages i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        /* Effet de badge pour non lu */
        .unread-badge {
            background-color: var(--admin-accent);
            color: white;
            padding: 0.2em 0.5em;
            border-radius: .25rem;
            font-size: 0.75em;
            font-weight: bold;
            vertical-align: middle;
            margin-left: 5px;
        }
    </style>
</head>
<body>

    <!-- Barre de navigation Admin (Exemple) -->
    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-cogs"></i> E-Social Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="background-image: url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.55%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e\");"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php"><i class="fas fa-users"></i> Utilisateurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_campaigns.php"><i class="fas fa-bullhorn"></i> Campagnes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="contact_view_messages.php"><i class="fas fa-envelope-open-text"></i> Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                 <h1><i class="fas fa-inbox"></i> Messages de Contact</h1>
                 <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Admin</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Messages</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-times-circle me-2"></i>
                <div><?= htmlspecialchars($error_message) ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <div><?= htmlspecialchars($success_message) ?></div>
            </div>
        <?php endif; ?>
        <?php display_flash_message(); // Pour les messages de redirection ?>


        <!-- Barre de filtres et de recherche -->
        <div class="filter-bar">
            <form method="GET" action="contact_view_messages.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label">Rechercher :</label>
                    <input type="text" class="form-control form-control-sm" id="search" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Nom, email, sujet, message...">
                </div>
                <div class="col-md-3">
                    <label for="filter_read" class="form-label">Filtrer par statut :</label>
                    <select class="form-select form-select-sm" id="filter_read" name="filter_read">
                        <option value="all" <?= $filterRead === 'all' ? 'selected' : '' ?>>Tous</option>
                        <option value="unread" <?= $filterRead === 'unread' ? 'selected' : '' ?>>Non lus</option>
                        <option value="read" <?= $filterRead === 'read' ? 'selected' : '' ?>>Lus</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Trier par date :</label>
                    <select class="form-select form-select-sm" id="sort" name="sort">
                        <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Plus récents d'abord</option>
                        <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Plus anciens d'abord</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-admin-primary btn-sm w-100"><i class="fas fa-filter"></i> Appliquer</button>
                </div>
            </form>
        </div>


        <?php if (empty($messages)): ?>
            <div class="no-messages">
                <i class="fas fa-comment-slash"></i>
                <p>
                    <?php if (!empty($searchTerm) || $filterRead !== 'all'): ?>
                        Aucun message ne correspond à vos critères de recherche/filtrage.
                    <?php else: ?>
                        Vous n'avez aucun message pour le moment.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message-card <?= $msg['est_lu'] ? 'read' : 'unread' ?>" id="message-<?= (int)$msg['id'] ?>">
                    <div class="message-header">
                        <div class="sender-info">
                            <strong><?= htmlspecialchars($msg['nom']) ?></strong>
                            (<a href="mailto:<?= htmlspecialchars($msg['email']) ?>"><?= htmlspecialchars($msg['email']) ?></a>)
                            <?php if (!$msg['est_lu']): ?>
                                <span class="unread-badge">Nouveau</span>
                            <?php endif; ?>
                        </div>
                        <div class="date-info">
                            Reçu le: <?= date("d/m/Y à H:i", strtotime($msg['date_reception'])) ?>
                            <?php if ($msg['est_lu'] && $msg['date_lecture']): ?>
                                <br>Lu le: <?= date("d/m/Y à H:i", strtotime($msg['date_lecture'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="message-body">
                        <h5 class="subject"><?= htmlspecialchars($msg['sujet']) ?></h5>
                        <div class="content">
<?= nl2br(htmlspecialchars($msg['message'])) ?>
                        </div>
                    </div>
                    <div class="message-actions">
                        <form method="POST" action="contact_view_messages.php?search=<?=urlencode($searchTerm)?>&filter_read=<?=$filterRead?>&sort=<?=$sortOrder?>#message-<?=(int)$msg['id']?>" style="display: inline;">
                            <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                            <input type="hidden" name="action" value="toggle_read">
                            <button type="submit" class="btn btn-sm <?= $msg['est_lu'] ? 'btn-admin-secondary btn-toggle-read read' : 'btn-admin-primary btn-toggle-read' ?>">
                                <i class="fas <?= $msg['est_lu'] ? 'fa-envelope-open' : 'fa-envelope' ?>"></i>
                                <?= $msg['est_lu'] ? 'Marquer non lu' : 'Marquer lu' ?>
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-admin-danger" onclick="confirmDelete(<?= (int)$msg['id'] ?>)">
                            <i class="fas fa-trash-alt"></i> Supprimer
                        </button>
                        <!-- Formulaire caché pour la suppression -->
                        <form id="deleteForm-<?= (int)$msg['id'] ?>" method="POST" action="contact_view_messages.php?search=<?=urlencode($searchTerm)?>&filter_read=<?=$filterRead?>&sort=<?=$sortOrder?>" style="display: none;">
                            <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Ajouter une pagination ici si nécessaire -->

    </div> <!-- fin .container -->

    <footer class="text-center p-4 mt-5 border-top bg-white">
        <p class="mb-0">© <?= date("Y") ?> E-Social Admin. Tous droits réservés.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(messageId) {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.")) {
                document.getElementById('deleteForm-' + messageId).submit();
            }
        }
    </script>
</body>
</html>