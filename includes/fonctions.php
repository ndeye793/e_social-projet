<?php
/**
 * ==========================================
 * FICHIER : fonctions.php
 * DESCRIPTION : Fonctions réutilisables pour E-Social
 * ==========================================
 */

// ---------------------------------------------
// === 1. Connexion à la base de données (reprise ici au cas où) ===
// ---------------------------------------------


// ---------------------------------------------
// === 2. Sécurisation des entrées utilisateur ===
// ---------------------------------------------
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim(stripslashes($data)));
    }
}

// ---------------------------------------------
// === 3. Hachage et vérification de mot de passe ===
// ---------------------------------------------
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ---------------------------------------------
// === 4. Authentification utilisateur ===
// ---------------------------------------------
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// ---------------------------------------------
// === 5. Gestion des utilisateurs ===
// ---------------------------------------------
function getUserById($id) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getUserDonHistory($userId) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM dons WHERE utilisateur_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

// ---------------------------------------------
// === 6. Campagnes ===
// ---------------------------------------------
function getAllCampaigns($status = null) {
    $conn = connectDB();
    $query = "SELECT * FROM campagnes" . ($status ? " WHERE statut = ?" : "");
    $stmt = $conn->prepare($query);
    if ($status) $stmt->bind_param("s", $status);
    $stmt->execute();
    return $stmt->get_result();
}

function getCampaignById($id) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM campagnes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function createCampaign($titre, $description, $objectif, $beneficiaire_id, $statut = 'en cours') {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO campagnes (titre, description, objectif, beneficiaire_id, statut) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdis", $titre, $description, $objectif, $beneficiaire_id, $statut);
    return $stmt->execute();
}

// ---------------------------------------------
// === 7. Dons ===
// ---------------------------------------------
function addDonation($user_id, $campaign_id, $montant, $moyen_paiement, $transaction_code = null) {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO dons (utilisateur_id, campagne_id, montant, moyen_paiement, transaction_code, date_don) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $user_id, $campaign_id, $montant, $moyen_paiement, $transaction_code);
    return $stmt->execute();
}

function getAllDonations() {
    $conn = connectDB();
    return $conn->query("SELECT * FROM dons ORDER BY date_don DESC");
}

// ---------------------------------------------
// === 8. Bénéficiaires ===
// ---------------------------------------------
function getAllBeneficiaires() {
    $conn = connectDB();
    return $conn->query("SELECT * FROM beneficiaires");
}

function addBeneficiaire($nom, $contact, $situation, $justificatif, $statut = 'en attente') {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO beneficiaires (nom, contact, situation, justificatif, statut) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nom, $contact, $situation, $justificatif, $statut);
    return $stmt->execute();
}

// ---------------------------------------------
// === 9. Statistiques ===
// ---------------------------------------------
function getStats() {
    $conn = connectDB();
    $stats = [];
    
    $stats['total_dons'] = $conn->query("SELECT SUM(montant) as total FROM dons")->fetch_assoc()['total'] ?? 0;
    $stats['nb_donateurs'] = $conn->query("SELECT COUNT(DISTINCT utilisateur_id) as total FROM dons")->fetch_assoc()['total'] ?? 0;
    $stats['nb_beneficiaires'] = $conn->query("SELECT COUNT(*) as total FROM beneficiaires WHERE statut = 'aidé'")->fetch_assoc()['total'] ?? 0;
    $stats['nb_campagnes'] = $conn->query("SELECT COUNT(*) as total FROM campagnes")->fetch_assoc()['total'] ?? 0;

    return $stats;
}

// ---------------------------------------------
// === 10. Newsletter / Partenaires / Contact ===
// ---------------------------------------------
function addNewsletterEmail($email) {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO newsletter (email) VALUES (?)");
    $stmt->bind_param("s", $email);
    return $stmt->execute();
}

function addContactMessage($nom, $email, $message) {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO messages_contact (nom, email, message, date_envoi) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $nom, $email, $message);
    return $stmt->execute();
}

function getAllPartners() {
    $conn = connectDB();
    return $conn->query("SELECT * FROM partenaires");
}

function addPartner($nom, $logo, $description) {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO partenaires (nom, logo, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nom, $logo, $description);
    return $stmt->execute();
}

// ---------------------------------------------
// === 11. Logs et historique admin ===
// ---------------------------------------------
function logAdminAction($admin_id, $action) {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO historique_admin (admin_id, action, date_action) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $admin_id, $action);
    return $stmt->execute();
}

function jours_restants($date_fin) {
    $aujourdhui = new DateTime();
    $fin = new DateTime($date_fin);
    $intervalle = $aujourdhui->diff($fin);
    return $intervalle->days;
}

function formatMontant($montant) {
    return number_format($montant, 0, ',', ' ');
}

function getUtilisateurById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getTotalDonsByUser($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT SUM(montant) AS total FROM dons WHERE utilisateur_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

function getCampagnesSoutenues($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.* 
        FROM campagnes c
        JOIN dons d ON c.id = d.campagne_id
        WHERE d.utilisateur_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getNotifications($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE utilisateur_id = ? 
        ORDER BY date_creation DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDonsRecentsByUser($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT d.*, c.titre AS campagne_titre 
        FROM dons d
        JOIN campagnes c ON d.campagne_id = c.id
        WHERE d.utilisateur_id = ?
        ORDER BY d.date_don DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCampagnesPopulaires($pdo) {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(d.id) AS nombre_dons 
        FROM campagnes c
        LEFT JOIN dons d ON c.id = d.campagne_id
        GROUP BY c.id
        ORDER BY nombre_dons DESC 
        LIMIT 5
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function marquerNotificationCommeLue($pdo, $notificationId) {
    $stmt = $pdo->prepare("UPDATE notifications SET lu = TRUE WHERE id = ?");
    $stmt->execute([$notificationId]);
}

// functions.php
function set_flash_message($message, $type = "info") {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        echo "<div class='alert alert-{$msg['type']} mt-3'>{$msg['message']}</div>";
        unset($_SESSION['flash_message']);
    }
}


?>
