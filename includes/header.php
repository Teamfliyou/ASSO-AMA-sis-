<?php
// Fichier : includes/header.php (Final avec Navigation Contextuelle)
require 'security.php'; 

// REMPLACER 'logo_ama.png' PAR LE NOM RÉEL DE VOTRE FICHIER.
$logo_filename = 'logo_ama.png'; 

$is_homepage = (basename($_SERVER['PHP_SELF']) === 'index.php');

// Récupérer les liens autorisés stockés dans la session
$nav_links = $_SESSION['nav_links'] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIS AMA ÉCOLE</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?php echo $is_homepage ? 'home-page' : 'sub-page'; ?>">
    
    <header class="main-header">
        <nav class="top-nav">
            <a href="index.php" class="logo">
                <img src="assets/images/<?php echo $logo_filename; ?>" alt="Logo de l'ASSO AMA ÉCOLE" class="header-logo">
            </a>
            
            <?php if (!$is_homepage): ?>
            <div class="context-nav-links">
                <a href="javascript:history.back()" class="context-btn back-btn">
                    &larr; Précédent
                </a>
                <a href="index.php" class="context-btn home-btn">
                    Accueil
                </a>
                
                <a href="logout.php" class="logout-btn-minimal">Déconnexion</a>
            </div>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">