<?php
require 'security.php';
require 'connection.php';
require 'functions.php';

$view = $_GET['view'] ?? 'list'; // list, details, import
$id = $_GET['id'] ?? null;

// ====================================================================
// TRAITEMENT DES LOGIQUES MÉTIER
// ====================================================================

// Fonction d'importation (tirée de import_eleves.php)
function import_students_from_csv($pdo, $fileTmpPath) {
    $error_messages = [];
    $success_count = 0;
    $annee_scolaire = date('Y');

    if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
        fgetcsv($handle); // Skip header

        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if (count($data) < 2) continue;

            $nom = trim($data[0] ?? '');
            $prenom = trim($data[1] ?? '');
            $nom_classe_csv = trim($data[2] ?? '');

            if (empty($nom) || empty($prenom)) continue;

            $classe_id = null;
            if (!empty($nom_classe_csv)) {
                $stmt = $pdo->prepare("SELECT classe_id FROM classes WHERE LOWER(nom_classe) = LOWER(?)");
                $stmt->execute([$nom_classe_csv]);
                $classe_id = $stmt->fetchColumn();

                if (!$classe_id) {
                    try {
                        $stmt_create = $pdo->prepare("INSERT INTO classes (nom_classe, niveau, annee_scolaire) VALUES (?, ?, ?)");
                        $stmt_create->execute([$nom_classe_csv, $nom_classe_csv, $annee_scolaire]);
                        $classe_id = $pdo->lastInsertId();
                    } catch (PDOException $e) {
                        $error_messages[] = "Erreur création classe '$nom_classe_csv'.";
                    }
                }
            }

            try {
                $stmt_eleve = $pdo->prepare("INSERT INTO eleves (nom, prenom, classe_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE classe_id = VALUES(classe_id)");
                $stmt_eleve->execute([$nom, $prenom, $classe_id]);
                $success_count++;
            } catch (PDOException $e) {
                $error_messages[] = "Erreur insertion élève $nom $prenom.";
            }
        }
        fclose($handle);
    }
    return ['count' => $success_count, 'errors' => $error_messages];
}

// ====================================================================
// TRAITEMENT DES REQUETES (POST & GET)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        // ... (logique ajout/modif)
        header("Location: eleves.php");
        exit;
    }
    if ($action === 'import' && isset($_FILES['fileToUpload'])) {
        $result = import_students_from_csv($pdo, $_FILES['fileToUpload']['tmp_name']);
        $_SESSION['message'] = $result['count'] . " élèves importés.";
        if (!empty($result['errors'])) {
            $_SESSION['import_errors'] = $result['errors'];
        }
        header("Location: eleves.php?view=import");
        exit;
    }
    // ... (autres actions POST)
}

// ... (actions GET)

require 'includes/header.php';
display_session_message();
?>
<div class="container">
    <h1>Gestion des Élèves</h1>
    
    <nav class="sub-nav">
        <a href="eleves.php?view=list" class="<?= in_array($view, ['list', 'edit']) ? 'active' : '' ?>">Liste & Ajout</a>
        <a href="eleves.php?view=import" class="<?= $view == 'import' ? 'active' : '' ?>">Importer</a>
    </nav>

    <div class="content">
        <?php
        switch($view) {
            case 'list':
            case 'edit':
                $edit_eleve = null;
                if($view === 'edit' && $id) {
                    $stmt = $pdo->prepare("SELECT * FROM eleves WHERE eleve_id = ?");
                    $stmt->execute([$id]);
                    $edit_eleve = $stmt->fetch();
                }

                $classes = $pdo->query('SELECT * FROM classes ORDER BY nom_classe')->fetchAll();
                $familles = $pdo->query("SELECT * FROM groupes_paiement ORDER BY nom_groupe")->fetchAll();
                $eleves = $pdo->query("SELECT e.*, c.nom_classe FROM eleves e LEFT JOIN classes c ON e.classe_id = c.classe_id ORDER BY e.nom")->fetchAll();
                
                include 'views/eleves_form.php'; // Formulaire externe
                include 'views/eleves_table.php'; // Tableau externe
                break;

            case 'details':
                // ... (code vue détaillée)
                break;

            case 'import':
                echo '<h2>Importer des élèves (CSV)</h2>';
                echo '<form action="eleves.php" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="import"> ... </form>';
                if (isset($_SESSION['import_errors'])) {
                    echo '<div class="message error"><ul>';
                    foreach ($_SESSION['import_errors'] as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul></div>';
                    unset($_SESSION['import_errors']);
                }
                break;
        }
        ?>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
