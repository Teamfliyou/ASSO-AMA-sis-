<?php
// Fichier : saisie_appreciation.php (Saisie des appréciations par CLASSE et par MATIÈRE)
require 'security.php';
require 'connection.php';

$message = '';
$class = '';
$selected_classe_id = filter_var($_POST['classe_id'] ?? $_GET['classe_id'] ?? null, FILTER_VALIDATE_INT);

// Récupérer les classes disponibles pour la sélection
$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();

// --- 1. GESTION DE LA SAUVEGARDE PAR LOT (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_all') {
    $appreciations_data = $_POST['appreciations'] ?? [];
    $appreciations_generales_data = $_POST['appreciations_generales'] ?? [];
    $total_saved = 0;
    
    // Requêtes de préparation
    $stmt_save_matiere = $pdo->prepare("
        INSERT INTO appreciations_matieres (eleve_id, matiere_id, texte_appreciation) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            texte_appreciation = VALUES(texte_appreciation)
    ");
    
    $stmt_save_general = $pdo->prepare("
        INSERT INTO appreciations (eleve_id, texte_appreciation) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE 
            texte_appreciation = VALUES(texte_appreciation)
    ");
    
    try {
        $pdo->beginTransaction();

        // Sauvegarde des appréciations par matière
        foreach ($appreciations_data as $eleve_id => $matieres) {
            foreach ($matieres as $matiere_id => $texte) {
                $texte = trim($texte);
                // Si le champ est laissé vide, nous ne l'insérons pas ou l'écrasons s'il existait
                // Pour simplifier l'effacement, nous insérons même les vides
                $stmt_save_matiere->execute([$eleve_id, $matiere_id, $texte]);
                $total_saved++;
            }
        }
        
        // Sauvegarde des appréciations Générales
        foreach ($appreciations_generales_data as $eleve_id => $texte) {
            $texte = trim($texte);
            $stmt_save_general->execute([$eleve_id, $texte]);
        }

        $pdo->commit();
        $message = "Appréciations enregistrées avec succès !";
        $class = 'success';
        
        // Redirection vers la même page
        header("Location: saisie_appreciation.php?classe_id={$selected_classe_id}&msg=saved");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Erreur d'enregistrement : " . $e->getMessage();
        $class = 'error';
    }
}

// --- 2. LOGIQUE DE CHARGEMENT POUR LA GRILLE ---
$matieres_classe = [];
$eleves_classe = [];
$moyennes_eleves = [];
$appreciations_existantes = [];
$appreciations_generales_existantes = [];

if ($selected_classe_id) {
    $stmt_eleves = $pdo->prepare("SELECT eleve_id, nom, prenom FROM eleves WHERE classe_id = ? ORDER BY nom, prenom");
    $stmt_eleves->execute([$selected_classe_id]);
    $eleves_classe = $stmt_eleves->fetchAll();
    
    if (empty($eleves_classe)) {
         $message = "La classe sélectionnée ne contient aucun élève.";
         $class = 'error';
         $selected_classe_id = null;
    } else {
        $matieres_classe = $pdo->query("SELECT matiere_id, nom_matiere FROM matieres ORDER BY nom_matiere")->fetchAll();
        $eleve_ids_string = implode(',', array_column($eleves_classe, 'eleve_id'));

        // A. Appréciations par Matière (Batch Fetch)
        if (!empty($eleve_ids_string)) {
             $stmt_app = $pdo->prepare("
                SELECT eleve_id, matiere_id, texte_appreciation 
                FROM appreciations_matieres 
                WHERE eleve_id IN ({$eleve_ids_string})
            ");
            $stmt_app->execute();
            foreach ($stmt_app->fetchAll() as $row) {
                $appreciations_existantes[$row['eleve_id']][$row['matiere_id']] = $row['texte_appreciation'];
            }
            
            // B. Appréciations Générales (Batch Fetch)
             $stmt_gen = $pdo->prepare("
                SELECT eleve_id, texte_appreciation 
                FROM appreciations 
                WHERE eleve_id IN ({$eleve_ids_string})
            ");
            $stmt_gen->execute();
            $appreciations_generales_existantes = $stmt_gen->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        
        // C. Moyennes par Matière (Batch Fetch - AFFICHER POUR AIDE)
        if (!empty($eleve_ids_string) && !empty($matieres_classe)) {
            $stmt_moy = $pdo->query("
                SELECT 
                    n.eleve_id, 
                    n.matiere_id, 
                    SUM(n.note * n.coefficient) / SUM(n.coefficient) AS moyenne
                FROM notes n
                WHERE n.eleve_id IN ({$eleve_ids_string})
                GROUP BY n.eleve_id, n.matiere_id
            ");
            foreach ($stmt_moy->fetchAll() as $row) {
                $moyennes_eleves[$row['eleve_id']][$row['matiere_id']] = $row['moyenne'];
            }
        }
    }
}

// Gérer le message de succès de redirection
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
     $message = "Appréciations enregistrées avec succès !";
     $class = 'success';
}

require 'includes/header.php';
?>

<div class="container">
    <h1>Saisie des Appréciations</h1>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <p style="text-align: right; margin-top: -20px;">
        <a href="saisie_notes.php" style="color: var(--primary-color); font-weight: 700;">Gérer/Ajouter les Matières &rarr;</a>
    </p>

    <form method="GET" action="saisie_appreciation.php" style="background-color: var(--secondary-bg); padding: 20px; border-radius: 8px;">
        <h2>Choisir la Classe</h2>
        <label for="classe_id">Classe :</label>
        <select id="classe_id" name="classe_id" required>
            <option value="">-- Sélectionner une classe --</option>
            <?php foreach ($classes_disponibles as $classe): ?>
                <option value="<?php echo htmlspecialchars($classe['classe_id']); ?>" 
                    <?php if ($selected_classe_id == $classe['classe_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($classe['nom_classe']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Afficher la Grille de Saisie</button>
    </form>

    <?php if ($selected_classe_id && !empty($eleves_classe)): ?>
        <h2 style="margin-top: 40px; border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
            Grille de Saisie pour la Classe 
            <?php 
                $classe_nom = array_filter($classes_disponibles, fn($c) => $c['classe_id'] == $selected_classe_id);
                echo htmlspecialchars($classe_nom[array_key_first($classe_nom)]['nom_classe'] ?? 'Inconnue');
            ?>
        </h2>
        
        <form method="POST" action="saisie_appreciation.php" style="padding: 0; border: none;">
            <input type="hidden" name="action" value="save_all">
            <input type="hidden" name="classe_id" value="<?php echo $selected_classe_id; ?>">

            <div style="overflow-x: auto;">
                <table style="min-width: 1200px;">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 100px; position: sticky; left: 0; background-color: var(--primary-color); z-index: 10;">Élève</th>
                            <th colspan="<?php echo count($matieres_classe); ?>" style="text-align: center;">Appréciations par Matière</th>
                            <th rowspan="2" style="width: 250px;">Appréciation Générale</th>
                        </tr>
                        <tr>
                            <?php foreach ($matieres_classe as $matiere): ?>
                                <th title="Appréciation du professeur pour cette matière" style="height: 50px; text-align: center; vertical-align: middle;">
                                    <?php echo htmlspecialchars($matiere['nom_matiere']); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eleves_classe as $eleve): ?>
                            <tr>
                                <td style="width: 100px; position: sticky; left: 0; font-weight: bold; background-color: #f0f4ff; z-index: 5;">
                                    <?php echo htmlspecialchars($eleve['prenom'] . ' ' . substr($eleve['nom'], 0, 1) . '.'); ?>
                                </td>
                                
                                <?php foreach ($matieres_classe as $matiere): 
                                    $matiere_id = $matiere['matiere_id'];
                                    $moyenne = $moyennes_eleves[$eleve['eleve_id']][$matiere_id] ?? null;
                                    $appreciation = $appreciations_existantes[$eleve['eleve_id']][$matiere_id] ?? '';
                                    
                                    $moyenne_display = $moyenne !== null ? number_format($moyenne, 1, ',', ' ') . '/20' : 'N/A';
                                ?>
                                    <td title="Moyenne : <?php echo $moyenne_display; ?>">
                                        <input type="text" 
                                               name="appreciations[<?php echo $eleve['eleve_id']; ?>][<?php echo $matiere_id; ?>]" 
                                               value="<?php echo htmlspecialchars($appreciation); ?>"
                                               placeholder="<?php echo $moyenne_display; ?>"
                                               style="width: 95%; font-size: 0.85em;">
                                    </td>
                                <?php endforeach; ?>
                                
                                <td>
                                    <?php 
                                        $appreciation_generale = $appreciations_generales_existantes[$eleve['eleve_id']] ?? '';
                                    ?>
                                    <textarea name="appreciations_generales[<?php echo $eleve['eleve_id']; ?>]" 
                                              rows="2" 
                                              placeholder="Texte général du conseil de classe"
                                              style="width: 95%; font-size: 0.85em;"><?php echo htmlspecialchars($appreciation_generale); ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" style="margin-top: 25px; background-color: var(--success-color);">
                💾 Enregistrer TOUTES les Appréciations de cette Classe
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>