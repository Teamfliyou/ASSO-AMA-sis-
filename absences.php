<?php
// absences.php - Contrôleur principal pour la gestion des absences
require 'security.php';
require 'connection.php';

// --- Configuration & Initialisation ---
$view = $_GET['view'] ?? 'saisie'; // 'saisie' ou 'rapport'
$message = '';
$class = '';

// --- Logique du Contrôleur ---

// Données communes aux deux vues
$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();
$selected_classe_id = filter_var($_REQUEST['classe_id'] ?? null, FILTER_VALIDATE_INT);


// ====================================================================
// SECTION SAISIE
// ====================================================================
if ($view === 'saisie') {
    // --- Initialisation pour la vue Saisie ---
    $selected_date = $_REQUEST['date_absence'] ?? date('Y-m-d');
    $selected_matiere_id = filter_var($_REQUEST['matiere_id'] ?? null, FILTER_VALIDATE_INT);
    $matieres_disponibles = $pdo->query('SELECT matiere_id, nom_matiere FROM matieres ORDER BY nom_matiere')->fetchAll();
    $eleves_classe = [];
    $absences_jour = [];

    // --- Traitement du formulaire de saisie (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'saisie') {
        $absences = $_POST['absent_eleves'] ?? [];
        $absences_enregistrees = 0;
        
        if (!$selected_matiere_id) {
             $message = "ERREUR : Veuillez sélectionner une matière pour enregistrer l'appel.";
             $class = 'error';
        } else {
            $stmt_delete = $pdo->prepare("DELETE FROM absences WHERE date_absence = ? AND matiere_id = ? AND eleve_id IN (SELECT eleve_id FROM eleves WHERE classe_id = ?)");
            $stmt_delete->execute([$selected_date, $selected_matiere_id, $selected_classe_id]);

            $stmt_insert = $pdo->prepare("INSERT INTO absences (eleve_id, date_absence, matiere_id, justifie, raison) VALUES (?, ?, ?, ?, ?)");

            foreach ($absences as $eleve_id) {
                $justifie = isset($_POST['justifie'][$eleve_id]) ? 1 : 0;
                $raison = $_POST['raison'][$eleve_id] ?? 'Non spécifiée'; 

                try {
                    $stmt_insert->execute([$eleve_id, $selected_date, $selected_matiere_id, $justifie, $raison]);
                    $absences_enregistrees++;
                } catch (PDOException $e) {
                    $message = "Erreur fatale lors de l'enregistrement: " . $e->getMessage();
                    $class = 'error';
                    break; 
                }
            }
            
            if ($class !== 'error') {
                header("Location: absences.php?view=saisie&classe_id={$selected_classe_id}&date_absence={$selected_date}&matiere_id={$selected_matiere_id}&msg=saved");
                exit;
            }
        }
    }

    // --- Récupération des données pour l'affichage de la Saisie ---
    if ($selected_classe_id && $selected_matiere_id) {
        $stmt_eleves = $pdo->prepare("SELECT eleve_id, nom, prenom FROM eleves WHERE classe_id = ? ORDER BY nom");
        $stmt_eleves->execute([$selected_classe_id]);
        $eleves_classe = $stmt_eleves->fetchAll();

        $stmt_absences = $pdo->prepare("SELECT a.eleve_id, a.justifie, a.raison FROM absences a WHERE a.date_absence = ? AND a.matiere_id = ? AND a.eleve_id IN (SELECT eleve_id FROM eleves WHERE classe_id = ?)");
        $stmt_absences->execute([$selected_date, $selected_matiere_id, $selected_classe_id]);
        
        foreach ($stmt_absences->fetchAll() as $absence) {
            $absences_jour[$absence['eleve_id']] = $absence;
        }
    }
    
    if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
         $message = "Saisie d'appel enregistrée avec succès pour le " . date('d/m/Y', strtotime($selected_date));
         $class = 'success';
    }
}

// ====================================================================
// SECTION RAPPORT
// ====================================================================
if ($view === 'rapport') {
    // --- Initialisation pour la vue Rapport ---
    $rapport_absences = [];
    $total_absences = 0;
    $graph_data = [];

    // --- Récupération des données pour le Rapport ---
    if ($selected_classe_id) {
        $stmt_rapport = $pdo->prepare('SELECT e.eleve_id, e.nom, e.prenom, COUNT(a.absence_id) AS total_absences, SUM(CASE WHEN a.justifie = 1 THEN 1 ELSE 0 END) AS justifiees, SUM(CASE WHEN a.justifie = 0 THEN 1 ELSE 0 END) AS non_justifiees FROM eleves e LEFT JOIN absences a ON e.eleve_id = a.eleve_id WHERE e.classe_id = ? GROUP BY e.eleve_id ORDER BY total_absences DESC, e.nom');
        $stmt_rapport->execute([$selected_classe_id]);
        $rapport_absences = $stmt_rapport->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $data_absences = [];
        $data_justifiees = [];
        
        foreach ($rapport_absences as $row) {
            if ($row['total_absences'] > 0) {
                $labels[] = $row['prenom'] . ' ' . substr($row['nom'], 0, 1) . '.';
                $data_absences[] = (int)$row['non_justifiees'];
                $data_justifiees[] = (int)$row['justifiees'];
                $total_absences += (int)$row['total_absences'];
            }
        }

        if (!empty($labels)) {
            $graph_data = ['labels' => $labels, 'non_justifiees' => $data_absences, 'justifiees' => $data_justifiees];
        }
    }
}

// --- Rendu de la Vue ---
require 'includes/header.php';

if ($view === 'saisie') {
    require 'views/absences_saisie.php';
} elseif ($view === 'rapport') {
    require 'views/absences_rapport.php';
}

require 'includes/footer.php';

?>