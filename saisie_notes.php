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
            $message = "Erreur : La matière existe déjà ou problème de base de données.";
            $class = 'error';
        }
    }
}

// --- 2. ACTION : ENREGISTRER LES NOTES (CORRIGÉ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'saisie_notes') {
    $notes_saisies = $_POST['notes'] ?? [];
    $coef = $_POST['coefficient'] ?? 1;
    $desc = trim($_POST['description'] ?? 'Contrôle');
    $nb_ajouts = 0;

    if (!empty($notes_saisies) && $selected_matiere_id) {
        try {
            // Début de la transaction pour garantir l'intégrité des données
            $pdo->beginTransaction();

            // REPLACE INTO remplace la note si eleve_id + matiere_id + date_note est déjà présent
            $sql = "REPLACE INTO notes (eleve_id, matiere_id, date_note, note, coefficient, description) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql);
            
            foreach ($notes_saisies as $eleve_id => $valeur) {
                // Nettoyage de la valeur (remplace virgule par point pour le format SQL DECIMAL)
                $valeur = str_replace(',', '.', trim($valeur)); 
                
                // On n'enregistre que si le champ n'est pas vide et est un nombre valide
                if ($valeur !== '' && is_numeric($valeur)) {
                    if ($valeur >= 0 && $valeur <= 20) {
                        $stmt_insert->execute([$eleve_id, $selected_matiere_id, $selected_date, $valeur, $coef, $desc]);
                        $nb_ajouts++;
                    }
                }
            }
            
            $pdo->commit();
            $message = "Succès : $nb_ajouts notes enregistrées avec succès.";
            $class = 'success';
        } catch (Exception $e) {
            // En cas d'erreur, on annule tout ce qui a été fait dans la boucle
            $pdo->rollBack();
            $message = "Erreur lors de l'enregistrement : " . $e->getMessage();
            $class = 'error';
        }
    } else {
        $message = "Erreur : Aucune note saisie ou matière non sélectionnée.";
        $class = 'error';
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

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1>Gestion des Notes</h1>

    <?php if ($message): ?>
        <div class="message" style="padding:15px; margin-bottom:20px; border-radius:5px; border: 1px solid; 
             background:<?= $class=='success'?'#d4edda':'#f8d7da'; ?>; 
             color:<?= $class=='success'?'#155724':'#721c24'; ?>;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 30px;">
        
        <div>
            <form method="GET" action="saisie_notes.php" style="background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #ddd;">
                <h3 style="margin-top:0;">1. Configuration</h3>
                
                <label style="display:block; margin-bottom:5px;">Classe :</label>
                <select name="classe_id" required onchange="this.form.submit()" style="width:100%; padding:8px; margin-bottom:15px;">
                    <option value="">-- Sélectionner la classe --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['classe_id'] ?>" <?= $selected_classe_id == $c['classe_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nom_classe']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label style="display:block; margin-bottom:5px;">Matière :</label>
                <select name="matiere_id" required style="width:100%; padding:8px; margin-bottom:15px;">
                    <option value="">-- Sélectionner la matière --</option>
                    <?php foreach ($matieres as $m): ?>
                        <option value="<?= $m['matiere_id'] ?>" <?= $selected_matiere_id == $m['matiere_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nom_matiere']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label style="display:block; margin-bottom:5px;">Date du contrôle :</label>
                <input type="date" name="date_note" value="<?= $selected_date ?>" style="width:100%; padding:8px; margin-bottom:15px;">

                <button type="submit" style="width:100%; padding:10px; background:#6c757d; color:white; border:none; cursor:pointer; border-radius:4px;">
                    Actualiser la liste
                </button>
            </form>

            <form method="POST" style="margin-top:20px; border: 1px dashed #bbb; padding:15px; border-radius:8px;">
                <input type="hidden" name="action" value="ajouter_matiere">
                <h4 style="margin-top:0;">+ Nouvelle Matière</h4>
                <input type="text" name="nouvelle_matiere" placeholder="Ex: Mathématiques" required style="width:calc(100% - 20px); padding:8px; margin-bottom:10px;">
                <button type="submit" style="background:#28a745; color:white; border:none; padding:8px 15px; cursor:pointer; border-radius:4px; width:100%;">
                    Ajouter
                </button>
            </form>
        </div>

        <div>
            <?php if ($selected_classe_id && $selected_matiere_id && !empty($eleves_classe)): ?>
                <form method="POST" action="saisie_notes.php">
                    <input type="hidden" name="action" value="saisie_notes">
                    <input type="hidden" name="classe_id" value="<?= htmlspecialchars($selected_classe_id) ?>">
                    <input type="hidden" name="matiere_id" value="<?= htmlspecialchars($selected_matiere_id) ?>">
                    <input type="hidden" name="date_note" value="<?= htmlspecialchars($selected_date) ?>">

                    <div style="background:#eef4ff; padding:20px; border-radius:8px; margin-bottom:20px; border:1px solid #b8daff;">
                        <h3 style="margin-top:0;">2. Détails du contrôle</h3>
                        <div style="display:flex; gap:20px; align-items:center;">
                            <div>
                                <label>Coefficient :</label><br>
                                <input type="number" name="coefficient" value="1" min="0.5" step="0.5" style="width:60px; padding:8px;">
                            </div>
                            <div style="flex-grow:1;">
                                <label>Description :</label><br>
                                <input type="text" name="description" placeholder="Ex: Devoir Surveillé n°1" required style="width:100%; padding:8px;">
                            </div>
                        </div>
                    </div>

                    <table style="width:100%; border-collapse: collapse; background:white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <thead>
                            <tr style="background:#343a40; color:white;">
                                <th style="padding:12px; text-align:left;">Nom & Prénom de l'Élève</th>
                                <th style="padding:12px; width:120px; text-align:center;">Note / 20</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves_classe as $index => $eleve): ?>
                                <tr style="border-bottom:1px solid #eee; background: <?= $index % 2 == 0 ? '#fff' : '#f9f9f9' ?>;">
                                    <td style="padding:12px;"><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></td>
                                    <td style="padding:12px; text-align:center;">
                                        <input type="text" 
                                               name="notes[<?= $eleve['eleve_id'] ?>]" 
                                               pattern="[0-9]*[.,]?[0-9]*" 
                                               placeholder="--" 
                                               style="width:70px; padding:8px; text-align:center; border:1px solid #ccc; border-radius:4px;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="margin-top:20px; text-align:right;">
                        <button type="submit" style="padding:15px 40px; background:#007bff; color:white; border:none; border-radius:5px; font-size:18px; font-weight:bold; cursor:pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            💾 ENREGISTRER TOUTE LA CLASSE
                        </button>
                    </div>
                </form>
            <?php elseif($selected_classe_id): ?>
                <div style="text-align:center; padding:40px; background:#fff3cd; border:1px solid #ffeeba; border-radius:8px;">
                    Cette classe ne contient aucun élève.
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:60px; background:#f8f9fa; border:2px dashed #ccc; border-radius:8px; color:#666;">
                    <h3>En attente de sélection</h3>
                    <p>Veuillez choisir une classe et une matière dans le panneau de gauche pour afficher la liste des élèves.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>