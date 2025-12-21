<?php
// Fichier : saisie_notes.php
require 'security.php';
require 'connection.php';

$message = '';
$class = '';

// Récupération des paramètres (POST ou GET)
$selected_classe_id = $_POST['classe_id'] ?? $_GET['classe_id'] ?? null;
$selected_matiere_id = $_POST['matiere_id'] ?? $_GET['matiere_id'] ?? null;
$selected_date = $_POST['date_note'] ?? $_GET['date_note'] ?? date('Y-m-d');

// --- 1. ACTION : AJOUTER UNE MATIÈRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_matiere') {
    $nouvelle_matiere = trim($_POST['nouvelle_matiere'] ?? '');
    if (!empty($nouvelle_matiere)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO matieres (nom_matiere) VALUES (?)");
            $stmt->execute([$nouvelle_matiere]);
            $message = "Matière ajoutée !";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur : La matière existe peut-être déjà.";
            $class = 'error';
        }
    }
}

// --- 2. ACTION : ENREGISTRER LES NOTES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'saisie_notes') {
    $notes_saisies = $_POST['notes'] ?? [];
    $coef = $_POST['coefficient'] ?? 1;
    $desc = trim($_POST['description'] ?? 'Contrôle');
    $nb_ajouts = 0;

    if (!empty($notes_saisies)) {
        $stmt_insert = $pdo->prepare("INSERT INTO notes (eleve_id, matiere_id, date_note, note, coefficient, description) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($notes_saisies as $eleve_id => $valeur) {
            $valeur = str_replace(',', '.', trim($valeur)); // Remplace la virgule par un point
            
            if ($valeur !== '' && is_numeric($valeur) && $valeur >= 0 && $valeur <= 20) {
                $stmt_insert->execute([$eleve_id, $selected_matiere_id, $selected_date, $valeur, $coef, $desc]);
                $nb_ajouts++;
            }
        }
        $message = "Succès : $nb_ajouts notes enregistrées pour '$desc'.";
        $class = 'success';
    }
}

// --- 3. CHARGEMENT DES DONNÉES ---
$classes = $pdo->query('SELECT * FROM classes ORDER BY nom_classe')->fetchAll();
$matieres = $pdo->query('SELECT * FROM matieres ORDER BY nom_matiere')->fetchAll();

$eleves_classe = [];
if ($selected_classe_id) {
    $stmt = $pdo->prepare("SELECT eleve_id, nom, prenom FROM eleves WHERE classe_id = ? ORDER BY nom");
    $stmt->execute([$selected_classe_id]);
    $eleves_classe = $stmt->fetchAll();
}

require 'includes/header.php';
?>

<div class="container">
    <h1>Gestion des Notes</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $class; ?>" style="padding:15px; margin-bottom:20px; border-radius:5px; background:<?php echo $class=='success'?'#d4edda':'#f8d7da'; ?>;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
        
        <div>
            <form method="GET" action="saisie_notes.php" style="background:#f4f4f4; padding:15px; border-radius:8px;">
                <h3>1. Sélection</h3>
                <label>Classe :</label>
                <select name="classe_id" required onchange="this.form.submit()">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['classe_id'] ?>" <?= $selected_classe_id == $c['classe_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nom_classe']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Matière :</label>
                <select name="matiere_id" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($matieres as $m): ?>
                        <option value="<?= $m['matiere_id'] ?>" <?= $selected_matiere_id == $m['matiere_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nom_matiere']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Date :</label>
                <input type="date" name="date_note" value="<?= $selected_date ?>">

                <button type="submit" style="margin-top:10px; width:100%;">Afficher la liste</button>
            </form>

            <form method="POST" style="margin-top:20px; border: 1px dashed #ccc; padding:10px;">
                <input type="hidden" name="action" value="ajouter_matiere">
                <h4>+ Nouvelle Matière</h4>
                <input type="text" name="nouvelle_matiere" placeholder="Nom..." required>
                <button type="submit" style="background:#28a745; color:white;">Ajouter</button>
            </form>
        </div>

        <div>
            <?php if ($selected_classe_id && $selected_matiere_id && !empty($eleves_classe)): ?>
                <form method="POST" action="saisie_notes.php">
                    <input type="hidden" name="action" value="saisie_notes">
                    <input type="hidden" name="classe_id" value="<?= $selected_classe_id ?>">
                    <input type="hidden" name="matiere_id" value="<?= $selected_matiere_id ?>">
                    <input type="hidden" name="date_note" value="<?= $selected_date ?>">

                    <div style="background:#eef; padding:15px; border-radius:8px; margin-bottom:15px;">
                        <h3>2. Détails du contrôle</h3>
                        <label>Coefficient :</label>
                        <input type="number" name="coefficient" value="1" min="1" step="0.5" style="width:60px;">
                        
                        <label>Description :</label>
                        <input type="text" name="description" placeholder="Ex: Interrogation écrite" required>
                    </div>

                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background:#333; color:white;">
                                <th style="padding:10px; text-align:left;">Élève</th>
                                <th style="padding:10px; width:100px;">Note / 20</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves_classe as $eleve): ?>
                                <tr style="border-bottom:1px solid #ddd;">
                                    <td style="padding:8px;"><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></td>
                                    <td>
                                        <input type="number" name="notes[<?= $eleve['eleve_id'] ?>]" 
                                               step="0.25" min="0" max="20" placeholder="-" 
                                               style="width:80px; padding:5px; text-align:center;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" style="margin-top:20px; padding:15px; width:100%; background:#007bff; color:white; font-size:16px;">
                        ENREGISTRER CETTE SÉRIE DE NOTES
                    </button>
                </form>
            <?php elseif($selected_classe_id): ?>
                <p>Aucun élève dans cette classe.</p>
            <?php else: ?>
                <p style="text-align:center; padding:50px; background:#f9f9f9; border:2px dashed #ccc;">
                    Sélectionnez une classe et une matière à gauche pour commencer la saisie.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>