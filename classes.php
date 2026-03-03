<?php
// Fichier : classes.php
require 'security.php';
require 'connection.php';

$message = '';
$class = '';

// --- 1. GESTION DES ACTIONS (AJOUT/MODIFICATION/SUPPRESSION) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $classe_id = $_POST['classe_id'] ?? null;
    $nom_classe = trim($_POST['nom_classe'] ?? '');
    $niveau = trim($_POST['niveau'] ?? '');
    // Récupération du tarif (nouveau champ)
    $tarif = filter_var($_POST['tarif_scolarite'] ?? 0, FILTER_VALIDATE_FLOAT);
    $annee_scolaire = date('Y');

    if ($nom_classe) {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO classes (nom_classe, niveau, annee_scolaire, tarif_scolarite) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom_classe, $niveau, $annee_scolaire, $tarif]);
                $message = "Classe '{$nom_classe}' ajoutée avec un tarif de {$tarif} €.";
            } elseif ($action === 'edit' && $classe_id) {
                $stmt = $pdo->prepare("UPDATE classes SET nom_classe = ?, niveau = ?, tarif_scolarite = ? WHERE classe_id = ?");
                $stmt->execute([$nom_classe, $niveau, $tarif, $classe_id]);
                $message = "Classe '{$nom_classe}' mise à jour.";
            }
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur : La classe existe déjà ou les données sont invalides.";
            $class = 'error';
        }
    }
} elseif (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM classes WHERE classe_id = ?");
            $stmt->execute([$delete_id]);
            $message = "Classe supprimée.";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression.";
            $class = 'error';
        }
    }
}

// --- 2. RÉCUPÉRATION DES DONNÉES ---
$classes = $pdo->query('SELECT * FROM classes ORDER BY nom_classe')->fetchAll();
$edit_classe = null;

if (isset($_GET['edit_id'])) {
    $edit_id = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE classe_id = ?");
        $stmt->execute([$edit_id]);
        $edit_classe = $stmt->fetch();
    }
}

require 'includes/header.php';
?>

<div class="container">
    <h1>Gestion des Classes & Tarifs</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $class; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">
        <h2><?php echo $edit_classe ? 'Modifier la Classe' : 'Ajouter une Classe'; ?></h2>
        <input type="hidden" name="action" value="<?php echo $edit_classe ? 'edit' : 'add'; ?>">
        <?php if ($edit_classe): ?>
            <input type="hidden" name="classe_id" value="<?php echo $edit_classe['classe_id']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label>Nom de la Classe :</label>
                <input type="text" name="nom_classe" required value="<?php echo htmlspecialchars($edit_classe['nom_classe'] ?? ''); ?>">
            </div>
            <div>
                <label>Tarif de Scolarité (€) :</label>
                <input type="number" name="tarif_scolarite" step="0.01" value="<?php echo $edit_classe['tarif_scolarite'] ?? '0.00'; ?>">
            </div>
        </div>

        <label>Niveau :</label>
        <input type="text" name="niveau" value="<?php echo htmlspecialchars($edit_classe['niveau'] ?? ''); ?>">

        <button type="submit"><?php echo $edit_classe ? 'Enregistrer' : 'Ajouter'; ?></button>
    </form>

    <h2>Liste des Classes</h2>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Niveau</th>
                <th>Tarif Annuel</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $c): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($c['nom_classe']); ?></strong></td>
                <td><?php echo htmlspecialchars($c['niveau']); ?></td>
                <td style="font-weight: bold; color: var(--primary-color);">
                    <?php echo number_format($c['tarif_scolarite'], 2, ',', ' '); ?> €
                </td>
                <td>
                    <a href="classes.php?edit_id=<?php echo $c['classe_id']; ?>" class="action-link edit">Modifier</a> | 
                    <a href="classes.php?delete_id=<?php echo $c['classe_id']; ?>" 
                       onclick="return confirm('Supprimer cette classe ?');" 
                       style="color: var(--error-color);">Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'includes/footer.php'; ?>