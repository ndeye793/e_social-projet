<?php
require_once '../config/db.php';

// Charger Dompdf (assure-toi que Composer est installé et que autoload est inclus)
require_once '../vendor/autoload.php'; 

use Dompdf\Dompdf;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de don invalide.');
}

$id = (int) $_GET['id'];
$pdo = getPDO();

$sql = "SELECT d.id, d.montant, d.date_don, d.statut, c.titre AS campagne
        FROM dons d
        LEFT JOIN campagnes c ON d.campagne_id = c.id
        WHERE d.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$don = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$don) {
    die('Don introuvable.');
}

// Création du contenu HTML du reçu (identique à ce que tu avais)
$html = '
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>Reçu de don #' . htmlspecialchars($don['id']) . '</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 2rem auto; padding: 1rem; }
    h1 { text-align: center; color: #2c3e50; }
    .info { margin: 1.5rem 0; }
    .info strong { display: inline-block; width: 150px; }
    .footer { text-align: center; margin-top: 3rem; font-size: 0.85rem; color: #777; }
</style>
</head>
<body>
    <h1>Reçu de don</h1>
    <p>Merci pour votre générosité ! Voici le reçu de votre don :</p>
    <div class="info">
        <p><strong>ID du don :</strong> ' . htmlspecialchars($don['id']) . '</p>
        <p><strong>Montant :</strong> ' . number_format($don['montant'], 0, ",", " ") . ' FCFA</p>
        <p><strong>Date du don :</strong> ' . date('d/m/Y', strtotime($don['date_don'])) . '</p>
        <p><strong>Campagne :</strong> ' . htmlspecialchars($don['campagne'] ?? 'N/A') . '</p>
        <p><strong>Statut :</strong> ' . htmlspecialchars(ucfirst($don['statut'])) . '</p>
    </div>
    <div class="footer">
        <p>E-Social &copy; ' . date('Y') . '</p>
    </div>
</body>
</html>
';

// Initialiser Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Télécharger le PDF automatiquement avec un nom de fichier personnalisé
$dompdf->stream("recu_don_" . $don['id'] . ".pdf", ["Attachment" => true]);
exit;
