<?php
// Fichier : index.php (Final avec correction du placement de la carte Bulletin de Classe)
require 'security.php'; // Imposer l'authentification
require 'connection.php';

// Récupérer le nom complet et les permissions de l'utilisateur
$stmt_user = $pdo->prepare("SELECT nom_complet, permissions, role FROM utilisateurs WHERE user_id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$user_info = $stmt_user->fetch();

$welcome_name = $user_info['nom_complet'] ?? $_SESSION['username']; 
$user_permissions = $user_info['permissions'] ?? '';
$is_admin = ($user_info['role'] === 'admin');

// Si l'utilisateur est admin, il a tous les droits. Sinon, on utilise la chaîne de permissions.
$allowed_pages = $is_admin ? ['all'] : explode(',', $user_permissions);

// Définition de toutes les cartes avec leurs fichiers PHP correspondants
$all_cards = [
    'classes.php' => ['icon' => '🏫', 'title' => 'Classes & Niveaux', 'desc' => 'Créer et visualiser les niveaux et les classes.'],
    'eleves.php' => ['icon' => '👤', 'title' => 'Dossiers Élèves', 'desc' => 'Gérer, modifier les informations et voir les fiches.'],
    'gestion_notes.php' => ['icon' => '💯', 'title' => 'Gestion des Notes', 'desc' => 'Saisie des notes, matières et bulletins.'], // Le hub des notes
    'paiements.php' => ['icon' => '💲', 'title' => 'Gestion des Paiements', 'desc' => 'Consulter les statuts (payé/impayé) et les totaux encaissés.'],
    'feuille_appel.php' => ['icon' => '📝', 'title' => 'Feuille d\'Appel', 'desc' => 'Saisie des absences journalières.'],
    'rapport_absences.php' => ['icon' => '📊', 'title' => 'Rapports & Statistiques', 'desc' => 'Consulter les totaux d\'absences avec des graphiques.'],
    'import_eleves.php' => ['icon' => '📁', 'title' => 'Importation d\'Élèves', 'desc' => 'Importer rapidement les listes d\'élèves par fichier CSV.'],
    'send_email.php' => ['icon' => '📧', 'title' => 'Envoyer un E-mail', 'desc' => 'Formulaire manuel pour les communications rapides.'],
    // REMARQUE: La carte 'bulletin_classe.php' est retirée d'ici.
];


require 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h1 style="border-bottom: none;">Bonjour, <?php echo htmlspecialchars($welcome_name); ?></h1>
    
    <a href="logout.php" style="
        background-color: var(--error-color); 
        color: white; 
        padding: 10px 18px; 
        border-radius: 6px; 
        text-decoration: none;
        font-weight: 500;
        transition: opacity 0.3s;"
        onmouseover="this.style.opacity='0.8';"
        onmouseout="this.style.opacity='1';"
    >
        Déconnexion (🔒)
    </a>
</div>

<form action="eleves.php" method="GET" style="max-width: 600px; margin: 0 auto 50px; background-color: #f0f4ff; border: 1px solid #c5d0ff;">
    <h3 style="color: var(--primary-color); margin-top: 0;">Recherche Rapide d'Élève</h3>
    <label for="search">Nom / Prénom :</label>
    <input type="text" id="search" name="search" placeholder="Entrez le nom ou le prénom de l'élève" required>
    <button type="submit" style="width: 100%;">Rechercher</button>
</form>

<section class="feature-grid">
    
    <?php foreach ($all_cards as $file => $card): ?>
        <?php 
            // Vérifie si l'utilisateur a un accès spécifique OU si c'est un administrateur
            $can_access = $is_admin || in_array($file, $allowed_pages);
            
            // On s'assure que la carte Gestion Utilisateurs ne s'affiche pas ici
            if ($file === 'utilisateurs.php') continue;
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
        <p>Ajouter des administrateurs et définir les permissions.</p>
    </a>
    <?php endif; ?>

</section>

<div class="db-status">
    Connecté en tant que **<?php echo htmlspecialchars($_SESSION['username']); ?>** (Rôle: <?php echo htmlspecialchars($user_info['role']); ?>).
</div>

<?php require 'includes/footer.php'; ?>