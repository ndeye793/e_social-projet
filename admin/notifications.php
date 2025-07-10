<?php
session_start();
require_once '../config/db.php'; // ta connexion PDO dans $pdo

// Messages flash
$successMsg = '';
$errorMsg = '';

// Récupérer tous les utilisateurs pour le select
$pdo = getPDO();
$usersStmt = $pdo->query("SELECT id, nom FROM utilisateurs ORDER BY nom");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// TRAITEMENT FORMULAIRE AJOUT
if (isset($_POST['addNotification'])) {
    $utilisateur_id = $_POST['utilisateur_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    $lu = isset($_POST['lu']) ? 1 : 0;

    if ($utilisateur_id && $message !== '') {
        $stmt = $pdo->prepare("INSERT INTO notifications (utilisateur_id, message, lu) VALUES (?, ?, ?)");
        if ($stmt->execute([$utilisateur_id, $message, $lu])) {
            $successMsg = "Notification ajoutée avec succès !";
        } else {
            $errorMsg = "Erreur lors de l'ajout.";
        }
    } else {
        $errorMsg = "Veuillez remplir tous les champs.";
    }
}

// TRAITEMENT FORMULAIRE MODIF
if (isset($_POST['editNotification'])) {
    $id = $_POST['id'] ?? null;
    $utilisateur_id = $_POST['utilisateur_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    $lu = isset($_POST['lu']) ? 1 : 0;

    if ($id && $utilisateur_id && $message !== '') {
        $stmt = $pdo->prepare("UPDATE notifications SET utilisateur_id=?, message=?, lu=? WHERE id=?");
        if ($stmt->execute([$utilisateur_id, $message, $lu, $id])) {
            $successMsg = "Notification modifiée avec succès !";
        } else {
            $errorMsg = "Erreur lors de la modification.";
        }
    } else {
        $errorMsg = "Veuillez remplir tous les champs.";
    }
}

// TRAITEMENT SUPPRESSION
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    if ($stmt->execute([$id])) {
        $successMsg = "Notification supprimée.";
    } else {
        $errorMsg = "Erreur lors de la suppression.";
    }
}

// Récupérer toutes les notifications avec le nom utilisateur
$notificationsStmt = $pdo->query("SELECT n.id, n.message, n.lu, n.date_notification, u.nom 
                                 FROM notifications n 
                                 JOIN utilisateurs u ON n.utilisateur_id = u.id
                                 ORDER BY n.date_notification DESC");
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestion Notifications - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
  <style>
    /* Animation icône non-lu */
    .icon-unread {
      color: #dc3545;
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.2); opacity: 0.6; }
      100% { transform: scale(1); opacity: 1; }
    }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">

  <h1 class="mb-4 text-center">Gestion des Notifications</h1>

  <!-- Messages -->
  <?php if ($successMsg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <!-- Bouton Ajouter -->
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="fa fa-plus"></i> Ajouter une notification
  </button>

  <!-- Tableau notifications -->
  <table class="table table-striped table-hover align-middle">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Utilisateur</th>
        <th>Message</th>
        <th>Lu ?</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($notifications as $n): ?>
      <tr>
        <td><?= $n['id'] ?></td>
        <td><?= htmlspecialchars($n['nom']) ?></td>
        <td><?= nl2br(htmlspecialchars($n['message'])) ?></td>
        <td class="text-center">
          <?php if (!$n['lu']): ?>
            <i class="fa fa-envelope icon-unread" title="Non lu"></i>
          <?php else: ?>
            <i class="fa fa-envelope-open text-success" title="Lu"></i>
          <?php endif; ?>
        </td>
        <td><?= (new DateTime($n['date_notification']))->format('d/m/Y H:i') ?></td>
        <td>
          <button 
            class="btn btn-sm btn-info btn-edit" 
            data-id="<?= $n['id'] ?>" 
            data-utilisateur="<?= $users[array_search($n['nom'], array_column($users, 'nom'))]['id'] ?? '' ?>" 
            data-message="<?= htmlspecialchars($n['message'], ENT_QUOTES) ?>" 
            data-lu="<?= $n['lu'] ?>"
            data-bs-toggle="modal" data-bs-target="#editModal"
            title="Modifier">
            <i class="fa fa-pen"></i>
          </button>

          <a href="?delete=<?= $n['id'] ?>" 
             onclick="return confirm('Supprimer cette notification ?');" 
             class="btn btn-sm btn-danger" title="Supprimer">
             <i class="fa fa-trash"></i>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (count($notifications) === 0): ?>
      <tr><td colspan="6" class="text-center">Aucune notification</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal Ajouter -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addModalLabel">Ajouter une notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="utilisateur_id" class="form-label">Utilisateur</label>
          <select id="utilisateur_id" name="utilisateur_id" class="form-select" required>
            <option value="" disabled selected>-- Sélectionner un utilisateur --</option>
            <?php foreach ($users as $user): ?>
              <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="message" class="form-label">Message</label>
          <textarea id="message" name="message" class="form-control" rows="3" required></textarea>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="lu" name="lu" />
          <label class="form-check-label" for="lu">Marquer comme lu</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" name="addNotification" class="btn btn-primary">Ajouter</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Modifier -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="id" id="editId" />
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Modifier la notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="editUtilisateurId" class="form-label">Utilisateur</label>
          <select id="editUtilisateurId" name="utilisateur_id" class="form-select" required>
            <option value="" disabled>-- Sélectionner un utilisateur --</option>
            <?php foreach ($users as $user): ?>
              <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="editMessage" class="form-label">Message</label>
          <textarea id="editMessage" name="message" class="form-control" rows="3" required></textarea>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="editLu" name="lu" />
          <label class="form-check-label" for="editLu">Marquer comme lu</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" name="editNotification" class="btn btn-success">Modifier</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(function(){
    $('.btn-edit').click(function(){
      const id = $(this).data('id');
      const utilisateur = $(this).data('utilisateur');
      const message = $(this).data('message');
      const lu = $(this).data('lu');

      $('#editId').val(id);
      $('#editUtilisateurId').val(utilisateur);
      $('#editMessage').val(message);
      $('#editLu').prop('checked', lu == 1);
    });
  });
</script>
</body>
</html>
