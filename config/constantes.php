<?php
// === DÉFINITION DU CHEMIN RACINE DU PROJET ===
define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');

// === INFORMATIONS GÉNÉRALES DU SITE ===
define('SITE_NAME', 'E-Social');
define('SITE_URL', 'http://localhost/e_social');
define('BASE_URL', SITE_URL . '/');
define('DEFAULT_CURRENCY', 'FCFA');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ADMIN_EMAIL', 'admin@e-social.sn');

// === CHEMINS DES DOSSIERS POUR LES FICHIERS UPLOADÉS ===
define('UPLOAD_IDENTITE_PATH', ROOT_PATH . 'uploads/identites/');
define('UPLOAD_PREUVES_PATH', ROOT_PATH . 'uploads/preuves_dons/');
define('UPLOAD_TRANSFERTS_PATH', ROOT_PATH . 'uploads/transferts_admin/');
define('UPLOAD_CAMPAGNES_PATH', ROOT_PATH . 'assets/images/campagnes/');
define('LOGO_PATH', ROOT_PATH . 'assets/images/logos/');

// === PARAMÈTRES SMTP POUR L’ENVOI D’EMAILS ===
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_USER', 'contact@e-social.sn');
define('SMTP_PASS', 'votre_mot_de_passe');
define('SMTP_PORT', 587);

// === INITIALISATION DE LA SESSION (SI NON DÉJÀ FAITE) ===
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// === DÉTECTION DE LA LANGUE (MULTILINGUE OPTIONNEL) ===
$lang = 'fr';
if (isset($_GET['lang'])) {
    $lang = in_array($_GET['lang'], ['fr', 'en', 'wo']) ? $_GET['lang'] : 'fr';
    $_SESSION['lang'] = $lang;
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}
define('LANG', $lang);

// === INCLUSION DU FICHIER DE TRADUCTIONS ===
$langFile = __DIR__ . "/lang/$lang.php";
if (file_exists($langFile)) {
    require_once $langFile;
} else {
    require_once __DIR__ . "/lang/fr.php";
}
