<?php
require 'security.php';
require 'connection.php';

$message = '';
$class = '';
$edit_eleve = null;

// --- 1. TRAITEMENT DES ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION : SUPPRIMER TOUS LES ÉLÈVES (ZONE DE DANGER)
    if (isset($_POST['action']) && $_POST['action'] === 'delete_all') {
        try {
            $pdo->exec("DELETE FROM eleves");
            $message = "Tous les élèves ont été supprimés avec succès.";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression massive : " . $e->getMessage();
            $class = 'error';
        }
    } 
    
    // ACTION : AJOUTER OU MODIFIER UN ÉLÈVE
    elseif (!isset($_POST['action_groupee'])) {
        $action = $_POST['action'] ?? 'add';
        $eleve_id = $_POST['eleve_id'] ?? null;
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $classe_id = $_POST['classe_id'] ?: null;
        $groupe_id = $_POST['groupe_id'] ?: null; // Liaison Famille
        $telephone_parent = trim($_POST['telephone_parent'] ?? '');

        if ($nom && $prenom) {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO eleves (nom, prenom, classe_id, groupe_id, telephone_parent) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nom, $prenom, $classe_id, $groupe_id, $telephone_parent]);
                    $message = "Élève ajouté avec succès !";
                } elseif ($action === 'edit' && $eleve_id) {
                    $stmt = $pdo->prepare("UPDATE eleves SET nom = ?, prenom = ?, classe_id = ?, groupe_id = ?, telephone_parent = ? WHERE eleve_id = ?");
                    $stmt->execute([$nom, $prenom, $classe_id, $groupe_id, $telephone_parent, $eleve_id]);
                    $message = "Fiche élève mise à jour !";
                }
                $class = 'success';
            } catch (PDOException $e) {
                $message = "Erreur : " . $e->getMessage();
                $class = 'error';
            }
        }
    }
}

// --- 2. SUPPRESSION INDIVIDUELLE (GET) ---
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM eleves WHERE eleve_id = ?");
            $stmt->execute([$delete_id]);
            $message = "Élève supprimé.";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression : " . $e->getMessage();
            $class = 'error';
        }
    }
}

// --- 3. RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE ---
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM eleves WHERE eleve_id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_eleve = $stmt->fetch();
}

$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();
$familles = $pdo->query("SELECT * FROM groupes_paiement ORDER BY nom_groupe")->fetchAll();
$eleves = $pdo->query("
    SELECT e.*, c.nom_classe, g.nom_groupe 
    FROM eleves e 
    LEFT JOIN classes c ON e.classe_id = c.classe_id 
    LEFT JOIN groupes_paiement g ON e.groupe_id = g.groupe_id
    ORDER BY e.nom
")->fetchAll();

require 'includes/header.php';
?>

<div class="container">
    <h1>Gestion des Dossiers Élèves</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <h2><?php echo $edit_eleve ? 'Modifier' : 'Ajouter'; ?> un Élève</h2>
        <input type="hidden" name="action" value="<?php echo $edit_eleve ? 'edit' : 'add'; ?>">
        <?php if ($edit_eleve): ?>
            <input type="hidden" name="eleve_id" value="<?php echo $edit_eleve['eleve_id']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label>Nom :</label>
                <input type="text" name="nom" required value="<?php echo htmlspecialchars($edit_eleve['nom'] ?? ''); ?>">
            </div>
            <div>
                <label>Prénom :</label>
                <input type="text" name="prenom" required value="<?php echo htmlspecialchars($edit_eleve['prenom'] ?? ''); ?>">
            </div>
        </div>

        <label>Téléphone Parent :</label>
        <input type="text" name="telephone_parent" value="<?php echo htmlspecialchars($edit_eleve['telephone_parent'] ?? ''); ?>" placeholder="Ex: 0612345678">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label>Classe :</label>
                <select name="classe_id">
                    <option value="">-- Choisir une classe --</option>
                    <?php foreach ($classes_disponibles as $c): ?>
                        <option value="<?php echo $c['classe_id']; ?>" <?php echo (isset($edit_eleve['classe_id']) && $edit_eleve['classe_id'] == $c['classe_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nom_classe']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Famille / Groupe :</label>
                <select name="groupe_id">
                    <option value="">-- Sans groupe / famille --</option>
                    <?php foreach ($familles as $f): ?>
                        <option value="<?php echo $f['groupe_id']; ?>" <?php echo (isset($edit_eleve['groupe_id']) && $edit_eleve['groupe_id'] == $f['groupe_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['nom_groupe']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit"><?php echo $edit_eleve ? 'Enregistrer les modifications' : 'Ajouter l\'élève'; ?></button>
        <?php if ($edit_eleve): ?>
            <a href="eleves.php" style="margin-left:10px;">Annuler</a>
        <?php endif; ?>
    </form>

    <div style="border: 2px solid var(--error-color); padding: 20px; border-radius: 8px; margin-bottom: 40px; background-color: #fff5f5;">
        <h3 style="color: var(--error-color); margin-top: 0;">⚠️ Zone de danger</h3>
        <p>Utilisez ce bouton pour vider entièrement la liste des élèves (utile après une erreur d'importation).</p>
        <form method="POST" onsubmit="return confirm('ÊTES-VOUS ABSOLUMENT SÛR ? Tous les élèves, leurs notes et leurs absences seront supprimés définitivement.');">
            <input type="hidden" name="action" value="delete_all">
            <button type="submit" style="background-color: var(--error-color);">Supprimer TOUS les élèves (<?php echo count($eleves); ?>)</button>
        </form>
    </div>

    <h2>Liste des Élèves</h2>
    <table>
        <thead>
            <tr>
                <th>Nom & Prénom</th>
                <th>Classe</th>
                <th>Famille</th>
                <th>Téléphone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eleves as $e): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($e['nom'] . ' ' . $e['prenom']); ?></strong></td>
                    <td><?php echo htmlspecialchars($e['nom_classe'] ?: 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($e['nom_groupe'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($e['telephone_parent'] ?: '-'); ?></td>
                    <td>
                        <a href="eleves.php?edit_id=<?php echo $e['eleve_id']; ?>" class="action-link edit">Modifier</a> | 
                        <a href="eleves_details.php?id=<?php echo $e['eleve_id']; ?>">Voir Fiche</a> |
                        <a href="eleves.php?delete_id=<?php echo $e['eleve_id']; ?>" 
                           onclick="return confirm('Supprimer cet élève ?');" 
                           style="color: var(--error-color);">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($eleves)): ?>
                <tr><td colspan="5" style="text-align: center;">Aucun élève dans la base.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'includes/footer.php'; ?>