<?php
require 'security.php';
require 'connection.php';

$message = '';
$class = '';
$error_messages = [];

// Fonction pour nettoyer et comparer les noms (ignore espaces et casse)
function normalizeName($name) {
    return strtolower(str_replace(' ', '', trim($name)));
}

function import_data($pdo, $fileTmpPath) {
    global $error_messages;
    $delimiters = [';', ',']; // Teste les deux séparateurs courants
    $success_count = 0;
    $annee_scolaire = date('Y');

    foreach ($delimiters as $delimiter) {
        if (($handle = fopen($fileTmpPath, "r")) === FALSE) continue;

        $row = 0;
        $temp_data = [];
        
        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $row++;
            if ($row == 1) continue; // Sauter l'en-tête
            if (count($data) < 2) continue;

            $nom = trim($data[0] ?? '');
            $prenom = trim($data[1] ?? '');
            $nom_classe_csv = trim($data[2] ?? '');
            
            if (empty($nom) || empty($prenom)) continue;

            $classe_id = null;

            if (!empty($nom_classe_csv)) {
                // 1. Chercher si la classe existe déjà (version flexible)
                $stmt_all_classes = $pdo->query("SELECT classe_id, nom_classe FROM classes");
                $classes_en_base = $stmt_all_classes->fetchAll();
                
                $norm_csv = normalizeName($nom_classe_csv);
                
                foreach ($classes_en_base as $c) {
                    if (normalizeName($c['nom_classe']) === $norm_csv) {
                        $classe_id = $c['classe_id'];
                        break;
                    }
                }

                // 2. Si elle n'existe pas, on la CRÉE automatiquement
                if (!$classe_id) {
                    try {
                        $stmt_create_class = $pdo->prepare("INSERT INTO classes (nom_classe, niveau, annee_scolaire) VALUES (?, ?, ?)");
                        $stmt_create_class->execute([$nom_classe_csv, $nom_classe_csv, $annee_scolaire]);
                        $classe_id = $pdo->lastInsertId();
                    } catch (PDOException $e) {
                        $error_messages[] = "Erreur création classe '$nom_classe_csv' : " . $e->getMessage();
                    }
                }
            }

            // 3. Insertion de l'élève
            try {
                $stmt_eleve = $pdo->prepare("INSERT INTO eleves (nom, prenom, classe_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE classe_id = VALUES(classe_id)");
                $stmt_eleve->execute([$nom, $prenom, $classe_id]);
                $success_count++;
            } catch (PDOException $e) {
                $error_messages[] = "Ligne $row : Erreur insertion élève $nom $prenom.";
            }
        }
        fclose($handle);
        if ($success_count > 0) return ['status' => 'success', 'count' => $success_count];
    }
    return ['status' => 'error', 'message' => "Fichier illisible ou vide."];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $result = import_data($pdo, $_FILES['fileToUpload']['tmp_name']);
    if ($result['status'] === 'success') {
        $message = "Importation réussie : " . $result['count'] . " élèves traités.";
        $class = 'success';
    } else {
        $message = $result['message'];
        $class = 'error';
    }
}

require 'includes/header.php';
?>

<div class="container">
    <h1>Importation d'Élèves</h1>
    <?php if ($message): ?>
        <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form action="import_eleves.php" method="post" enctype="multipart/form-data">
        <p>Le système créera automatiquement les classes manquantes trouvées dans votre fichier.</p>
        <label>Fichier CSV (Nom;Prénom;Classe) :</label>
        <input type="file" name="fileToUpload" required>
        <button type="submit">Lancer l'importation</button>
    </form>

    <?php if (!empty($error_messages)): ?>
        <div class="message error">
            <h3>Détails :</h3>
            <ul><?php foreach ($error_messages as $err) echo "<li>".htmlspecialchars($err)."</li>"; ?></ul>
        </div>
    <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>