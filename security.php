<?php
// Dans security.php
session_start();
require 'connection.php';
// Fichier : security.php (Final avec gestion des permissions pour la navigation)
require 'connection.php'; // Inclure la connexion DB pour les vérifications

session_start();

// --- 1. Vérification de la Connexion ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; 
    header('Location: login.php');
    exit;
}

// --- 2. Récupération des Données Utilisateur ---
$user_id = $_SESSION['user_id'];
$stmt_check = $pdo->prepare("SELECT nom_complet, permissions, role, must_change_password FROM utilisateurs WHERE user_id = ?");
$stmt_check->execute([$user_id]);
$user_data = $stmt_check->fetch();

$user_role = $_SESSION['role'];
$is_admin = ($user_role === 'admin');

// Si l'utilisateur est admin, toutes les pages sont autorisées.
if ($is_admin) {
    $user_permissions = 'all';
} else {
    $user_permissions = $user_data['permissions'] ?? '';
}

$allowed_files = explode(',', $user_permissions);
$allowed_files = array_filter($allowed_files); // Retire les éléments vides

// --- 3. Vérification du Changement de Mot de Passe ---
if ($user_data && $user_data['must_change_password'] && basename($_SERVER['PHP_SELF']) !== 'change_password.php') {
    header('Location: change_password.php');
    exit;
}

// --- 4. Définition de TOUTES les pages pour le header ---
// Ces pages seront utilisées pour construire le menu du header.
$all_app_pages = [
    'classes.php' => 'Classes',
    'eleves.php' => 'Élèves',
    'paiements.php' => 'Paiements',
    'feuille_appel.php' => 'Feuille d\'Appel',
    'rapport_absences.php' => 'Rapports',
    'import_eleves.php' => 'Importation',
    'send_email.php' => 'Mail Test',
];

// Stocker les liens autorisés pour le header
$_SESSION['nav_links'] = [];
foreach ($all_app_pages as $file => $label) {
    if ($is_admin || in_array($file, $allowed_files)) {
        $_SESSION['nav_links'][$file] = $label;
    }
}

// Ajout du lien Admin si l'utilisateur est admin
if ($is_admin) {
    $_SESSION['nav_links']['utilisateurs.php'] = 'Admin';
}


// --- 5. Vérification des Permissions pour la Page Actuelle ---
$current_page = basename($_SERVER['PHP_SELF']);
$always_allowed = ['index.php', 'logout.php', 'change_password.php', 'confidentialite.html', 'mentions.html'];

if (!$is_admin && !in_array($current_page, $always_allowed) && !in_array($current_page, $allowed_files)) {
    // Si l'utilisateur n'est pas admin et n'a pas la permission d'accéder à la page
    http_response_code(403);
    die("<h1>403 Accès Refusé</h1><p>Votre compte n'est pas autorisé à accéder à cette page. Contactez l'administrateur.</p><p><a href='index.php'>Retour à l'accueil</a></p>");
}
// Le script continue si toutes les vérifications sont passées.
?>
