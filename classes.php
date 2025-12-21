<?php
require 'security.php';
require 'connection.php';
// Fichier : classes.php (Ajout de l'action "Voir la classe")
require 'connection.php';

$message = '';
$class = '';

// --- GESTION DES ACTIONS (AJOUT/MODIFICATION/SUPPRESSION) ---
// (Logique inchangée, non répétée ici pour la concision)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $classe_id = $_POST['classe_id'] ?? null;
    $nom_classe = $_POST['nom_classe'] ?? '';
    $niveau = $_POST['niveau'] ?? '';
    $annee_scolaire = date('Y');

    if ($nom_classe) {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO classes (nom_classe, niveau, annee_scolaire) VALUES (?, ?, ?)");
                $stmt->execute([$nom_classe, $niveau, $annee_scolaire]);
                $message = "Classe '{$nom_classe}' ajoutée avec succès!";
            } elseif ($action === 'edit' && $classe_id) {
                $stmt = $pdo->prepare("UPDATE classes SET nom_classe = ?, niveau = ? WHERE classe_id = ?");
                $stmt->execute([$nom_classe, $niveau, $classe_id]);
                $message = "Classe '{$nom_classe}' modifiée avec succès!";
            }
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur : Cette classe existe peut-être déjà ou les données sont invalides. (" . $e->getMessage() . ")";
            $class = 'error';
        }
    } else {
        $message = "Le nom de la classe est requis.";
        $class = 'error';
    }
} elseif (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM classes WHERE classe_id = ?");
            $stmt->execute([$delete_id]);
            $message = "La classe a été supprimée. Les élèves associés n'ont plus de classe attribuée.";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression : " . $e->getMessage();
            $class = 'error';
        }
    }
}

// --- RECUPERATION DES CLASSES EXISTANTES ET CLASSE À MODIFIER ---
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
        <h1>Gestion des Classes</h1>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <h2><?php echo $edit_classe ? 'Modifier la Classe : ' . htmlspecialchars($edit_classe['nom_classe']) : 'Ajouter une Nouvelle Classe'; ?></h2>
            
            <input type="hidden" name="action" value="<?php echo $edit_classe ? 'edit' : 'add'; ?>">
            <?php if ($edit_classe): ?>
                <input type="hidden" name="classe_id" value="<?php echo htmlspecialchars($edit_classe['classe_id']); ?>">
            <?php endif; ?>

            <label for="nom_classe">Nom de la Classe (Ex: CE2-A) :</label>
            <input type="text" id="nom_classe" name="nom_classe" required value="<?php echo htmlspecialchars($edit_classe['nom_classe'] ?? ''); ?>">

            <label for="niveau">Niveau (Ex: CE2, 6ème) :</label>
            <input type="text" id="niveau" name="niveau" value="<?php echo htmlspecialchars($edit_classe['niveau'] ?? ''); ?>">

            <button type="submit"><?php echo $edit_classe ? 'Enregistrer les Modifications' : 'Ajouter la Classe'; ?></button>
            <?php if ($edit_classe): ?>
                <a href="classes.php" style="margin-left: 10px; color: var(--text-color); text-decoration: none;">Annuler la Modification</a>
            <?php endif; ?>
        </form>

        <h2>Liste des Classes (<?php echo count($classes); ?>)</h2>
        <?php if (count($classes) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom de la Classe</th>
                        <th>Niveau</th>
                        <th>Élèves</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $classe): ?>
                        <?php 
                            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM eleves WHERE classe_id = ?");
                            $stmt_count->execute([$classe['classe_id']]);
                            $count_eleves = $stmt_count->fetchColumn();
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($classe['nom_classe']); ?></td>
                            <td><?php echo htmlspecialchars($classe['niveau']); ?></td>
                            <td><?php echo $count_eleves; ?></td>
                            <td>
                                <a href="eleves.php?filter_classe_id=<?php echo $classe['classe_id']; ?>" style="color: var(--primary-color);">Voir la classe (<?php echo $count_eleves; ?>)</a> 
                                | 
                                <a href="classes.php?edit_id=<?php echo $classe['classe_id']; ?>" class="action-link edit">Modifier</a> | 
                                <a href="classes.php?delete_id=<?php echo $classe['classe_id']; ?>" 
                                   onclick="return confirm('ATTENTION: Supprimer cette classe détachera <?php echo $count_eleves; ?> élève(s). Continuer ?');" 
                                   class="action-link">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucune classe enregistrée pour le moment. Veuillez en ajouter une.</p>
        <?php endif; ?>
    </div>

<?php require 'includes/footer.php'; ?>