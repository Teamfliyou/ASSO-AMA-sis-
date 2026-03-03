<?php
// Fichier : index.php
require 'security.php'; // Imposer l'authentification
require 'connection.php'; // Connexion à la base de données

// Récupérer les informations de l'utilisateur connecté
$stmt_user = $pdo->prepare("SELECT nom_complet, permissions, role FROM utilisateurs WHERE user_id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$user_info = $stmt_user->fetch();

$welcome_name = $user_info['nom_complet'] ?? $_SESSION['username']; 
$user_permissions = $user_info['permissions'] ?? '';
$is_admin = ($user_info['role'] === 'admin');

// Définition des accès
$allowed_pages = $is_admin ? ['all'] : explode(',', $user_permissions);

// --- DÉFINITION DE TOUTES LES CARTES DU MENU ---
$all_cards = [
    // Modules de base
    'classes.php' => ['icon' => '🏫', 'title' => 'Classes & Niveaux', 'desc' => 'Gérer les classes et définir les tarifs de scolarité.'],
    'eleves.php' => ['icon' => '👤', 'title' => 'Dossiers Élèves', 'desc' => 'Gérer les inscriptions et rattacher les élèves aux familles.'],
    'gestion_notes.php' => ['icon' => '💯', 'title' => 'Notes & Bulletins', 'desc' => 'Saisie des notes, appréciations et génération de PDF.'],
    
    // Nouveaux Modules Financiers
    'familles.php' => ['icon' => '👨‍👩‍👧‍👦', 'title' => 'Gestion des Familles', 'desc' => 'Regrouper les élèves par fratrie et gérer les paiements groupés.'],
    'paiements_liste.php' => ['icon' => '📉', 'title' => 'Tableau des Impayés', 'desc' => 'Suivi global de qui a payé et des restes à percevoir.'],
    'stats_paiements.php' => ['icon' => '📊', 'title' => 'Statistiques Financières', 'desc' => 'Vue globale des revenus encaissés et prévisionnels.'],
    
    // Modules Utilitaires
    'feuille_appel.php' => ['icon' => '📝', 'title' => 'Feuille d\'Appel', 'desc' => 'Saisie des absences journalières par cours.'],
    'rapport_absences.php' => ['icon' => '📈', 'title' => 'Rapports Absences', 'desc' => 'Statistiques d\'absentéisme avec graphiques.'],
    'import_eleves.php' => ['icon' => '📁', 'title' => 'Importation CSV', 'desc' => 'Importer des listes d\'élèves et créer des classes automatiquement.'],
];

require 'includes/header.php'; // Inclusion du header stylisé
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h1 style="border-bottom: none;">Bonjour, <?php echo htmlspecialchars($welcome_name); ?></h1>
    
    <a href="logout.php" style="
        background-color: var(--error-color); 
        color: white; 
        padding: 10px 18px; 
        border-radius: 6px; 
        text-decoration: none;
        font-weight: 500;"
    >
        Déconnexion (🔒)
    </a>
</div>

<form action="eleves.php" method="GET" style="max-width: 600px; margin: 0 auto 50px; background-color: #f0f4ff; border: 1px solid #c5d0ff;">
    <h3 style="color: var(--primary-color); margin-top: 0;">Recherche Rapide d'Élève</h3>
    <label for="search">Nom / Prénom :</label>
    <input type="text" id="search" name="search" placeholder="Entrez le nom ou le prénom" required>
    <button type="submit" style="width: 100%;">Rechercher</button>
</form>

<section class="feature-grid">
    
    <?php foreach ($all_cards as $file => $card): ?>
        <?php 
            // Vérification des droits d'accès
            $can_access = $is_admin || in_array($file, $allowed_pages);
        ?>
        
        <?php if ($can_access): ?>
        <a href="<?php echo $file; ?>" class="feature-card">
            <div class="icon"><?php echo $card['icon']; ?></div>
            <div class="card-title"><?php echo $card['title']; ?></div>
            <p><?php echo $card['desc']; ?></p>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <?php if ($is_admin): ?>
    <a href="utilisateurs.php" class="feature-card" style="border: 2px dashed var(--primary-color);">
        <div class="icon">⚙️</div>
        <div class="card-title">Gestion Utilisateurs</div>
        <p>Gérer les comptes personnels et les permissions d'accès.</p>
    </a>
    <?php endif; ?>

</section>

<div class="db-status" style="margin-top: 30px; font-size: 0.9em; color: #666; text-align: center;">
    Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> | Rôle : <?php echo htmlspecialchars($user_info['role']); ?>
</div>

<?php require 'includes/footer.php'; // Inclusion du footer ?>