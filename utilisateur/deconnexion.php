<?php
session_start();

// Détruire toutes les variables de session
$_SESSION = [];

// Supprimer la session
session_destroy();

// Rediriger vers la page de connexion ou d'accueil
header("Location: connexion.php");
exit;
