<?php
// Fichier : gestion_notes.php (Tableau de Bord Central du Module Notes)
require 'security.php';
require 'connection.php';

// Récupération des données pour l'affichage des liens
$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();
$matieres_disponibles = $pdo->query('SELECT nom_matiere FROM matieres ORDER BY nom_matiere')->fetchAll();

require 'includes/header.php';
?>

<div class="container">
    <h1>Gestion du Module Notes & Bulletins</h1>
    
    <div style="margin-bottom: 30px;">
        <p>Ce tableau de bord centralise toutes les actions relatives aux notes et aux matières. Votre établissement enseigne actuellement : <strong><?php echo count($matieres_disponibles); ?> matières</strong>.</p>
    </div>

    <section class="feature-grid" style="gap: 20px;">
        
        <a href="saisie_notes.php" class="feature-card" style="background-color: #e6f4e6;">
            <div class="icon" style="color: #34a853;">✍️</div>
            <div class="card-title">Saisie des Notes</div>
            <p>Accès aux grilles pour entrer les notes par classe et par contrôle.</p>
        </a>

        <a href="saisie_appreciation.php" class="feature-card" style="background-color: #f0f4ff;">
            <div class="icon" style="color: #1a73e8;">💬</div>
            <div class="card-title">Saisir les Appréciations</div>
            <p>Saisir les appréciations générales et par matière, classe par classe.</p>
        </a>
        
        <a href="bulletin_classe.php" class="feature-card" style="background-color: #fff3e0;">
            <div class="icon" style="color: #fbbc05;">📄</div>
            <div class="card-title">Générer Bulletins</div>
            <p>Exporter les bulletins complets pour une classe entière (format PDF).</p>
        </a>
        
        <a href="gestion_controles.php" class="feature-card" style="background-color: #ffebee;">
            <div class="icon" style="color: var(--error-color);">🗑️</div>
            <div class="card-title">Gérer les Contrôles</div>
            <p>Modifier ou supprimer des sessions de notes déjà enregistrées.</p>
        </a>
        
        <div class="feature-card" style="cursor: default; opacity: 0.9;">
            <div class="icon" style="color: #888;">📖</div>
            <div class="card-title">Matières Actuelles</div>
            <p><?php echo implode(', ', array_column($matieres_disponibles, 'nom_matiere')); ?></p>
        </div>
        
    </section>
</div>

<?php require 'includes/footer.php'; ?>