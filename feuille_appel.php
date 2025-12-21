<?php
// Fichier : feuille_appel.php (Saisie des Absences Journalières par MATIÈRE)
require 'security.php';
require 'connection.php';

$message = '';
$class = '';
// Récupère la classe, la date et la matière sélectionnées
$selected_classe_id = filter_var($_POST['classe_id'] ?? $_GET['classe_id'] ?? null, FILTER_VALIDATE_INT);
$selected_date = $_POST['date_absence'] ?? $_GET['date_absence'] ?? date('Y-m-d');
$selected_matiere_id = filter_var($_POST['matiere_id'] ?? $_GET['matiere_id'] ?? null, FILTER_VALIDATE_INT);


// --- GESTION DE LA SAISIE D'ABSENCE (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'saisie') {
    $absences = $_POST['absent_eleves'] ?? [];
    $absences_enregistrees = 0;
    
    // Vérification : La matière est obligatoire pour l'enregistrement
    if (!$selected_matiere_id) {
         $message = "ERREUR : Veuillez sélectionner une matière pour enregistrer l'appel.";
         $class = 'error';
    } else {
        // 1. Suppression préalable : On supprime toutes les absences de cette classe pour cette date ET cette matière.
        $stmt_delete = $pdo->prepare("
            DELETE FROM absences 
            WHERE date_absence = ? 
            AND matiere_id = ?
            AND eleve_id IN (SELECT eleve_id FROM eleves WHERE classe_id = ?)
        ");
        $stmt_delete->execute([$selected_date, $selected_matiere_id, $selected_classe_id]);

        $stmt_insert = $pdo->prepare("INSERT INTO absences (eleve_id, date_absence, matiere_id, justifie, raison) VALUES (?, ?, ?, ?, ?)");

        // 2. Enregistrement des nouvelles absences
        foreach ($absences as $eleve_id) {
            $justifie = isset($_POST['justifie'][$eleve_id]) ? 1 : 0;
            $raison = $_POST['raison'][$eleve_id] ?? 'Non spécifiée'; 

            try {
                $stmt_insert->execute([$eleve_id, $selected_date, $selected_matiere_id, $justifie, $raison]);
                $absences_enregistrees++;
            } catch (PDOException $e) {
                $message = "Erreur fatale lors de l'enregistrement de l'absence: " . $e->getMessage();
                $class = 'error';
                break; 
            }
        }
        
        if ($class !== 'error') {
            $message = "Saisie d'appel enregistrée pour le " . date('d/m/Y', strtotime($selected_date)) . ". Total: {$absences_enregistrees} absence(s).";
            $class = 'success';
            
            // Redirection PRG pour éviter les re-soumissions (conserve les paramètres)
            header("Location: feuille_appel.php?classe_id={$selected_classe_id}&date_absence={$selected_date}&matiere_id={$selected_matiere_id}&msg=saved");
            exit;
        }
    }
}


// --- RECUPERATION DES DONNÉES (toujours après les POST) ---
$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();
$matieres_disponibles = $pdo->query('SELECT matiere_id, nom_matiere FROM matieres ORDER BY nom_matiere')->fetchAll();
$eleves_classe = [];
$absences_jour = [];

if ($selected_classe_id && $selected_matiere_id) {
    // Récupérer les élèves de la classe sélectionnée
    $stmt_eleves = $pdo->prepare("SELECT eleve_id, nom, prenom FROM eleves WHERE classe_id = ? ORDER BY nom");
    $stmt_eleves->execute([$selected_classe_id]);
    $eleves_classe = $stmt_eleves->fetchAll();

    // Récupérer les absences existantes pour cette classe, cette date ET cette matière
    $stmt_absences = $pdo->prepare("
        SELECT a.eleve_id, a.justifie, a.raison
        FROM absences a
        WHERE a.date_absence = ? AND a.matiere_id = ?
        AND a.eleve_id IN (SELECT eleve_id FROM eleves WHERE classe_id = ?)
    ");
    $stmt_absences->execute([$selected_date, $selected_matiere_id, $selected_classe_id]);
    
    // Format : [eleve_id => ['justifie'=>X, 'raison'=>Y]]
    foreach ($stmt_absences->fetchAll() as $absence) {
        $absences_jour[$absence['eleve_id']] = $absence;
    }
}

// Gérer le message de succès après redirection
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
     $message = "Saisie d'appel enregistrée avec succès pour le " . date('d/m/Y', strtotime($selected_date));
     $class = 'success';
}

require 'includes/header.php';
?>

    <div class="container">
        <h1>Feuille d'Appel (Saisie Journalière)</h1>

        <p style="text-align: right; margin-top: -20px;">
            <a href="export_appel.php" style="color: var(--primary-color); font-weight: 500;">
                Générer Feuille d'Appel Planifiée (Multi-Jour) &rarr;
            </a>
        </p>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="GET" action="feuille_appel.php" style="background-color: var(--secondary-bg);">
            <h2>Sélectionner le Cours et la Date</h2>
            <label for="classe_id">Classe :</label>
            <select id="classe_id" name="classe_id" onchange="this.form.submit()" required>
                <option value="">-- Choisir une classe --</option>
                <?php foreach ($classes_disponibles as $classe): ?>
                    <option value="<?php echo htmlspecialchars($classe['classe_id']); ?>" 
                        <?php if ($selected_classe_id == $classe['classe_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($classe['nom_classe']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="matiere_id">Matière :</label>
            <select id="matiere_id" name="matiere_id" onchange="this.form.submit()" required>
                <option value="">-- Choisir une matière --</option>
                <?php foreach ($matieres_disponibles as $matiere): ?>
                    <option value="<?php echo htmlspecialchars($matiere['matiere_id']); ?>" 
                        <?php if ($selected_matiere_id == $matiere['matiere_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($matiere['nom_matiere']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="date_absence">Date :</label>
            <input type="date" id="date_absence" name="date_absence" value="<?php echo htmlspecialchars($selected_date); ?>" required onchange="this.form.submit()">
            
            <noscript><button type="submit">Afficher la Liste</button></noscript>
        </form>

        <?php if ($selected_classe_id && $selected_matiere_id && !empty($eleves_classe)): ?>
            <form method="POST" action="feuille_appel.php" style="border: 2px solid var(--error-color);">
                <h2>Saisie des Absences du <?php echo date('d/m/Y', strtotime($selected_date)); ?></h2>
                <input type="hidden" name="action" value="saisie">
                <input type="hidden" name="classe_id" value="<?php echo htmlspecialchars($selected_classe_id); ?>">
                <input type="hidden" name="matiere_id" value="<?php echo htmlspecialchars($selected_matiere_id); ?>">
                <input type="hidden" name="date_absence" value="<?php echo htmlspecialchars($selected_date); ?>">

                <p style="font-weight: 700;">Cochez l'élève s'il est ABSENT. Entrez le justificatif/raison si nécessaire.</p>
                
                <div style="overflow-x: auto;">
                    <table style="min-width: 700px;">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Nom & Prénom</th>
                                <th style="width: 10%;">Absent (Cocher)</th>
                                <th style="width: 10%;">Justifié</th>
                                <th style="width: 55%;">Raison / Justificatif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves_classe as $eleve): 
                                $absence_data = $absences_jour[$eleve['eleve_id']] ?? null;
                                $is_absent = $absence_data !== null;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></td>
                                    <td>
                                        <input type="checkbox" name="absent_eleves[]" value="<?php echo $eleve['eleve_id']; ?>" 
                                            <?php if ($is_absent) echo 'checked'; ?>>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="justifie[<?php echo $eleve['eleve_id']; ?>]" value="1" 
                                            <?php if ($is_absent && $absence_data['justifie']) echo 'checked'; ?>>
                                    </td>
                                    <td>
                                        <input type="text" name="raison[<?php echo $eleve['eleve_id']; ?>]" style="width: 95%; margin: 0; padding: 5px;" 
                                            value="<?php echo htmlspecialchars($absence_data['raison'] ?? ''); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" style="margin-top: 20px;">Enregistrer les Absences et Justificatifs</button>
            </form>
        <?php elseif ($selected_classe_id && !empty($eleves_classe) && !$selected_matiere_id): ?>
             <p class="message error">Veuillez sélectionner une matière pour pouvoir saisir l'appel.</p>
        <?php elseif ($selected_classe_id && empty($eleves_classe)): ?>
            <p class="message error">Cette classe ne contient aucun élève. Veuillez en importer ou en ajouter un.</p>
        <?php endif; ?>
    </div>

<?php require 'includes/footer.php'; ?>