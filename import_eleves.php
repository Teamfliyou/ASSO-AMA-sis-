<?php
require 'security.php';
require 'connection.php';
// Fichier : import_eleves.php (Final avec Détection du Séparateur CSV)
require 'connection.php';

$message = '';
$class = '';
$error_messages = [];

// Fonction pour détecter le séparateur et importer les données
function import_data($pdo, $fileTmpPath) {
    global $error_messages;
    
    // Définition des séparateurs à tester (priorité à la virgule)
    $delimiters = [',', ';'];
    $imported_successfully = false;
    $success_count = 0;
    
    foreach ($delimiters as $delimiter) {
        if (($handle = fopen($fileTmpPath, "r")) === FALSE) {
            // Ne devrait pas arriver si le fichier est OK
            return ['status' => 'error', 'message' => "Erreur lors de l'ouverture du fichier.", 'count' => 0];
        }

        $row = 0;
        $error_messages = []; // Réinitialiser les erreurs pour chaque essai
        $success_count = 0;
        $temp_data = []; // Stocker les données si le séparateur est le bon

        // 1. Déterminer la Classe (pour les requêtes préparées)
        $stmt_class = $pdo->prepare("SELECT classe_id FROM classes WHERE nom_classe = ?");
        $stmt_eleve = $pdo->prepare("
            INSERT INTO eleves (nom, prenom, classe_id) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                classe_id = VALUES(classe_id)
        ");

        // 2. Tenter la lecture
        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $row++;
            if ($row == 1) continue; // Sauter l'en-tête
            
            // On vérifie qu'il y a au moins 2 colonnes (Nom, Prénom)
            if (count($data) < 2) {
                // Si le séparateur n'est pas le bon, la ligne sera souvent considérée comme une seule colonne.
                // On met de côté l'erreur et on teste le séparateur suivant.
                continue; 
            }

            $nom = trim($data[0] ?? '');
            $prenom = trim($data[1] ?? '');
            $nom_classe = trim($data[2] ?? '');
            
            if (empty($nom) || empty($prenom)) continue; // Ignorer les lignes vides

            $classe_id = null;
            
            // Tenter de trouver la classe
            if (!empty($nom_classe)) {
                $stmt_class->execute([$nom_classe]);
                $classe = $stmt_class->fetch();
                if ($classe) {
                    $classe_id = $classe['classe_id'];
                }
            }
            
            // Stocker pour une insertion groupée si le séparateur est bon
            $temp_data[] = ['nom' => $nom, 'prenom' => $prenom, 'classe_id' => $classe_id, 'nom_classe' => $nom_classe, 'row' => $row];
            $success_count++;
        }
        fclose($handle);
        
        // Si le premier séparateur a échoué (moins de 2 colonnes dans la plupart des lignes), on essaie le suivant.
        // Sinon, si on a trouvé des données, on insère.
        if ($success_count > 0 && count($temp_data) >= 1) {
            // Si le séparateur est le bon, insérer les données collectées
            foreach ($temp_data as $data_row) {
                try {
                    $stmt_eleve->execute([$data_row['nom'], $data_row['prenom'], $data_row['classe_id']]);
                } catch (PDOException $e) {
                    $error_messages[] = "Ligne {$data_row['row']} ({$data_row['nom']} {$data_row['prenom']}): Erreur DB - " . $e->getMessage();
                }
                
                // Si la classe n'a pas été trouvée, on enregistre une erreur
                 if (!empty($data_row['nom_classe']) && is_null($data_row['classe_id'])) {
                     $error_messages[] = "Ligne {$data_row['row']} ({$data_row['nom']} {$data_row['prenom']}): Classe '{$data_row['nom_classe']}' non trouvée. L'élève est importé sans classe.";
                 }
            }
            
            return ['status' => 'success', 'message' => "Importation réussie avec le séparateur '{$delimiter}'.", 'count' => count($temp_data)];
        }
    }
    
    // Si aucun séparateur n'a donné de résultat satisfaisant
    return ['status' => 'error', 'message' => "Le fichier est illisible. Assurez-vous qu'il contient au moins les colonnes Nom et Prénom séparées par une virgule (,) ou un point-virgule (;).", 'count' => 0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $file = $_FILES['fileToUpload'];
    $fileTmpPath = $file['tmp_name'];
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ['csv', 'txt'])) {
        $message = "Erreur : Seuls les fichiers CSV ou TXT sont autorisés.";
        $class = 'error';
    } else {
        $result = import_data($pdo, $fileTmpPath);
        
        if ($result['status'] === 'success') {
            $message = "Importation terminée : {$result['count']} élèves traités. " . $result['message'];
            $class = empty($error_messages) ? 'success' : 'error';
            if (!empty($error_messages)) {
                $message .= " Attention : " . count($error_messages) . " erreurs rencontrées (voir détails).";
            }
        } else {
            $message = $result['message'];
            $class = 'error';
        }
    }
}

// Définition du contenu du modèle CSV (pour le téléchargement)
$csv_content = "Nom;Prénom;NomClasse\n";
$csv_content .= "Dupont;Marie;6ème A\n";
$csv_content .= "Lefevre;Pierre;\n";
$csv_content .= "Martin;Julie;CE2 B";
$csv_data_uri = 'data:text/csv;charset=utf-8,' . rawurlencode($csv_content);

require 'includes/header.php';
?>

    <div class="container">
        <h1>Importation d'Élèves (via Fichier CSV)</h1>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $class ?? ''; ?>"><?php echo nl2br(htmlspecialchars($message)); ?></div>
        <?php endif; ?>

        <p style="margin-top: 20px; font-weight: 500;">
            📥 Vous ne connaissez pas le format ? 
            <a href="<?php echo $csv_data_uri; ?>" download="modele_import_eleves.csv" style="color: var(--success-color); text-decoration: underline;">
                Télécharger le modèle CSV
            </a> (Utilise le point-virgule)
        </p>

        <form action="import_eleves.php" method="post" enctype="multipart/form-data">
            <h2>Téléverser le Fichier CSV</h2>
            <p>Le script gère automatiquement le séparateur (virgule ou point-virgule).</p>
            <p>Colonnes attendues : **Nom, Prénom, NomClasse (Optionnel)**.</p>

            <label for="fileToUpload">Sélectionner un fichier CSV :</label>
            <input type="file" name="fileToUpload" id="fileToUpload" required>

            <button type="submit" name="submit">Importer les Élèves</button>
        </form>

        <?php if (!empty($error_messages)): ?>
            <h2>Détails des Erreurs :</h2>
            <ul>
                <?php foreach ($error_messages as $error): ?>
                    <li style="color: red;"><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <p style="margin-top: 30px;">Les élèves importés sans classe devront être attribués manuellement sur la page <a href="eleves.php">Gestion des Élèves</a>.</p>
    </div>

<?php require 'includes/footer.php'; ?>