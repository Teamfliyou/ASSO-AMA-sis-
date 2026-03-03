<?php
// Fichier : familles.php
require 'security.php';
require 'connection.php';

$message = '';
$class = '';

// --- 1. ACTION : CRÉER UNE FAMILLE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_famille'])) {
    $nom = trim($_POST['nom_famille']);
    if (!empty($nom)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO groupes_paiement (nom_groupe) VALUES (?)");
            $stmt->execute([$nom]);
            $message = "La famille '" . htmlspecialchars($nom) . "' a été créée avec succès !";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur : Cette famille existe déjà ou une erreur est survenue.";
            $class = 'error';
        }
    }
}

// --- 2. ACTION : SUPPRIMER UNE FAMILLE ---
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM groupes_paiement WHERE groupe_id = ?");
            $stmt->execute([$delete_id]);
            $message = "Famille supprimée. Les élèves associés n'ont plus de groupe.";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression.";
            $class = 'error';
        }
    }
}

// --- 3. RÉCUPÉRATION DES DONNÉES ---
// On récupère les familles avec le nombre d'élèves et le total dû (somme des tarifs des classes)
$sql = "
    SELECT 
        g.groupe_id, 
        g.nom_groupe, 
        COUNT(e.eleve_id) as nb_enfants,
        IFNULL(SUM(c.tarif_scolarite), 0) as total_du_famille
    FROM groupes_paiement g
    LEFT JOIN eleves e ON e.groupe_id = g.groupe_id
    LEFT JOIN classes c ON e.classe_id = c.classe_id
    GROUP BY g.groupe_id
    ORDER BY g.nom_groupe
";
$familles = $pdo->query($sql)->fetchAll();

require 'includes/header.php';
?>

<div class="container">
    <h1>Gestion des Familles</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $class; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">
        <h2>Créer une nouvelle famille / groupe</h2>
        <p>Regroupez les frères et sœurs pour gérer un seul montant global.</p>
        <label for="nom_famille">Nom de la famille (ex: Famille Agnaou) :</label>
        <input type="text" id="nom_famille" name="nom_famille" required placeholder="Entrez le nom de famille...">
        <button type="submit">Créer la famille</button>
    </form>

    <h2>Liste des Familles Enregistrées</h2>
    <table>
        <thead>
            <tr>
                <th>Nom de la Famille</th>
                <th>Enfants inscrits</th>
                <th>Total Scolarité</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($familles as $f): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($f['nom_groupe']); ?></strong></td>
                    <td><?php echo $f['nb_enfants']; ?> enfant(s)</td>
                    <td style="color: var(--primary-color); font-weight: bold;">
                        <?php echo number_format($f['total_du_famille'], 2, ',', ' '); ?> €
                    </td>
                    <td>
                        <a href="paiements_famille.php?id=<?php echo $f['groupe_id']; ?>" class="action-link edit">Suivi Paiements</a> | 
                        <a href="familles.php?delete_id=<?php echo $f['groupe_id']; ?>" 
                           onclick="return confirm('Supprimer cette famille ? Les élèves resteront mais ne seront plus groupés.');" 
                           style="color: var(--error-color);">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($familles)): ?>
                <tr><td colspan="4" style="text-align: center;">Aucune famille créée pour le moment.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'includes/footer.php'; ?>