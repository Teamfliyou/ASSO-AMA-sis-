<?php
require 'security.php';
require 'connection.php';

$message = '';
$class = '';
$edit_eleve = null;

// --- GESTION DES ACTIONS INDIVIDUELLES (AJOUT/MODIF) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action_groupee'])) {
    $action = $_POST['action'] ?? 'add';
    $eleve_id = $_POST['eleve_id'] ?? null;
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $classe_id = $_POST['classe_id'] ?? null;
    $telephone_parent = $_POST['telephone_parent'] ?? ''; // Nouveau champ

    if ($nom && $prenom) {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO eleves (nom, prenom, classe_id, telephone_parent) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom, $prenom, $classe_id, $telephone_parent]);
                $message = "Élève ajouté !";
            } elseif ($action === 'edit' && $eleve_id) {
                $stmt = $pdo->prepare("UPDATE eleves SET nom = ?, prenom = ?, classe_id = ?, telephone_parent = ? WHERE eleve_id = ?");
                $stmt->execute([$nom, $prenom, $classe_id, $telephone_parent, $eleve_id]);
                $message = "Élève mis à jour !";
            }
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
            $class = 'error';
        }
    }
}

// Récupération pour édition
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM eleves WHERE eleve_id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_eleve = $stmt->fetch();
}

$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();
$eleves = $pdo->query("SELECT e.*, c.nom_classe FROM eleves e LEFT JOIN classes c ON e.classe_id = c.classe_id ORDER BY e.nom")->fetchAll();

require 'includes/header.php';
?>

<div class="container">
    <h1>Gestion des Élèves</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <h2><?php echo $edit_eleve ? 'Modifier' : 'Ajouter'; ?> un Élève</h2>
        <input type="hidden" name="action" value="<?php echo $edit_eleve ? 'edit' : 'add'; ?>">
        <?php if ($edit_eleve): ?>
            <input type="hidden" name="eleve_id" value="<?php echo $edit_eleve['eleve_id']; ?>">
        <?php endif; ?>

        <label>Nom :</label>
        <input type="text" name="nom" required value="<?php echo htmlspecialchars($edit_eleve['nom'] ?? ''); ?>">

        <label>Prénom :</label>
        <input type="text" name="prenom" required value="<?php echo htmlspecialchars($edit_eleve['prenom'] ?? ''); ?>">

        <label>Téléphone Parent :</label>
        <input type="text" name="telephone_parent" value="<?php echo htmlspecialchars($edit_eleve['telephone_parent'] ?? ''); ?>" placeholder="Ex: 0612345678">

        <label>Classe :</label>
        <select name="classe_id">
            <option value="">-- Choisir --</option>
            <?php foreach ($classes_disponibles as $c): ?>
                <option value="<?php echo $c['classe_id']; ?>" <?php echo (isset($edit_eleve['classe_id']) && $edit_eleve['classe_id'] == $c['classe_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['nom_classe']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit"><?php echo $edit_eleve ? 'Enregistrer' : 'Ajouter'; ?></button>
    </form>

    <h2>Liste des Élèves</h2>
    <table>
        <thead>
            <tr>
                <th>Nom & Prénom</th>
                <th>Classe</th>
                <th>Téléphone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eleves as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['nom'] . ' ' . $e['prenom']); ?></td>
                    <td><?php echo htmlspecialchars($e['nom_classe'] ?: 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($e['telephone_parent'] ?: '-'); ?></td>
                    <td><a href="eleves.php?edit_id=<?php echo $e['eleve_id']; ?>">Modifier</a> | <a href="eleves_details.php?id=<?php echo $e['eleve_id']; ?>">Voir Fiche</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require 'includes/footer.php'; ?>