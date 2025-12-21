<?php
require 'security.php';
require 'connection.php';

$message = '';
$class = '';
$edit_eleve = null;

// --- NOUVEAU : GESTION DES ACTIONS GROUPÉES ---
if (isset($_POST['action_groupee']) && !empty($_POST['selected_eleves'])) {
    $selected_ids = $_POST['selected_eleves']; // Tableau d'IDs
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

    if ($_POST['action_groupee'] === 'delete_all') {
        try {
            $stmt = $pdo->prepare("DELETE FROM eleves WHERE eleve_id IN ($placeholders)");
            $stmt->execute($selected_ids);
            $message = count($selected_ids) . " élèves supprimés avec succès.";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression groupée.";
            $class = 'error';
        }
    }
}

// --- GESTION DES ACTIONS INDIVIDUELLES (AJOUT/MODIF) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action_groupee'])) {
    $action = $_POST['action'] ?? 'add';
    $eleve_id = $_POST['eleve_id'] ?? null;
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $classe_id = $_POST['classe_id'] ?? null;

    if ($nom && $prenom) {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO eleves (nom, prenom, classe_id) VALUES (?, ?, ?)");
                $stmt->execute([$nom, $prenom, $classe_id]);
                $message = "Élève ajouté !";
            } elseif ($action === 'edit' && $eleve_id) {
                $stmt = $pdo->prepare("UPDATE eleves SET nom = ?, prenom = ?, classe_id = ? WHERE eleve_id = ?");
                $stmt->execute([$nom, $prenom, $classe_id, $eleve_id]);
                $message = "Élève mis à jour !";
            }
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
            $class = 'error';
        }
    }
}

// --- RECUPERATION ET FILTRES (Logique conservée) ---
$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();
$filter_classe_id = $_GET['filter_classe_id'] ?? '';
$where_clause = $filter_classe_id ? " WHERE e.classe_id = ? " : "";
$params = $filter_classe_id ? [$filter_classe_id] : [];

$sql_select = "SELECT e.eleve_id, e.nom, e.prenom, c.nom_classe 
               FROM eleves e LEFT JOIN classes c ON e.classe_id = c.classe_id 
               $where_clause ORDER BY e.nom";
$stmt = $pdo->prepare($sql_select);
$stmt->execute($params);
$eleves = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="container">
    <h1>Gestion des Élèves</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        </form>

    <hr>

    <form method="POST" id="form-liste-eleves">
        <h2>Liste des Élèves</h2>
        
        <?php if (count($eleves) > 0): ?>
            <div style="margin-bottom: 10px;">
                <button type="button" onclick="toggleAll(this)" class="btn-small">Tout sélectionner</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="check-all" onclick="toggleCheckboxes(this)"></th>
                        <th>Nom & Prénom</th>
                        <th>Classe</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eleves as $eleve): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_eleves[]" value="<?php echo $eleve['eleve_id']; ?>">
                            </td>
                            <td><?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($eleve['nom_classe'] ?: 'N/A'); ?></td>
                            <td>
                                <a href="eleves.php?edit_id=<?php echo $eleve['eleve_id']; ?>">Modifier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top: 20px; background: #f4f4f4; padding: 15px; border-radius: 8px;">
                <label>Action groupée sur la sélection :</label>
                <select name="action_groupee">
                    <option value="">-- Choisir une action --</option>
                    <option value="delete_all">Supprimer les élèves sélectionnés</option>
                </select>
                <button type="submit" onclick="return confirm('Appliquer cette action à la sélection ?')" style="background-color: var(--error-color);">Appliquer</button>
            </div>

        <?php else: ?>
            <p>Aucun élève trouvé.</p>
        <?php endif; ?>
    </form>
</div>

<script>
// Petit script JS pour cocher/décocher tout d'un coup
function toggleCheckboxes(source) {
    checkboxes = document.getElementsByName('selected_eleves[]');
    for(var i=0, n=checkboxes.length;i<n;i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

<?php require 'includes/footer.php'; ?>