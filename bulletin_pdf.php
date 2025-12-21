<?php
// Fichier : bulletin_pdf.php (Final avec le design Vert Turquoise et lecture des appréciations par matière)
require 'security.php';
require 'connection.php';

// Nom du fichier du logo (doit être accessible via un chemin absolu pour le PDF)
$logo_filename = 'logo_ama.png'; 
$logo_path_relative = 'assets/images/' . $logo_filename;

// --- LOGIQUE DE RÉCUPÉRATION DES IDs (Individuel ou Lot) ---
$eleve_ids = [];
if (isset($_GET['eleve_id'])) {
    $eleve_ids[] = filter_var($_GET['eleve_id'], FILTER_VALIDATE_INT);
} elseif (isset($_GET['ids'])) {
    // La liste d'IDs vient du script bulletin_classe.php
    $ids_array = explode(',', $_GET['ids']);
    foreach ($ids_array as $id) {
        $id_int = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_int) $eleve_ids[] = $id_int;
    }
}

$bulletins = [];
$error_message = '';

if (empty($eleve_ids)) {
    $error_message = "Aucun identifiant d'élève fourni pour générer le bulletin. Veuillez vérifier l'accès ou la classe.";
} else {
    // Boucle à travers chaque ID pour générer les données
    foreach ($eleve_ids as $eleve_id) {
        // 1. Récupérer les informations de l'élève
        $stmt_eleve = $pdo->prepare('
            SELECT e.nom, e.prenom, c.nom_classe, c.niveau
            FROM eleves e
            LEFT JOIN classes c ON e.classe_id = c.classe_id
            WHERE e.eleve_id = ?
        ');
        $stmt_eleve->execute([$eleve_id]);
        $eleve = $stmt_eleve->fetch();
        
        if (!$eleve) continue; 

        // 2. Récupérer les notes agrégées par matière
        $stmt_notes = $pdo->prepare('
            SELECT 
                m.matiere_id,
                m.nom_matiere,
                SUM(n.note * n.coefficient) / SUM(n.coefficient) AS moyenne,
                COUNT(n.note) AS total_notes
            FROM notes n
            JOIN matieres m ON n.matiere_id = m.matiere_id
            WHERE n.eleve_id = ?
            GROUP BY m.matiere_id, m.nom_matiere
            ORDER BY m.nom_matiere
        ');
        $stmt_notes->execute([$eleve_id]);
        $resultats = $stmt_notes->fetchAll();
        
        // 3. Calculer la moyenne générale
        $stmt_moyenne_generale = $pdo->prepare('
            SELECT SUM(n.note * n.coefficient) / SUM(n.coefficient) AS moyenne_generale
            FROM notes n
            WHERE n.eleve_id = ?
        ');
        $stmt_moyenne_generale->execute([$eleve_id]);
        $moyenne_generale = $stmt_moyenne_generale->fetchColumn();
        
        // 4. Récupérer l'appréciation générale
        $stmt_appreciation = $pdo->prepare("SELECT texte_appreciation FROM appreciations WHERE eleve_id = ?");
        $stmt_appreciation->execute([$eleve_id]);
        $appreciation_texte = $stmt_appreciation->fetchColumn();
        
        // 5. Récupérer les appréciations par matière
        $stmt_appreciation_matieres = $pdo->prepare("SELECT matiere_id, texte_appreciation FROM appreciations_matieres WHERE eleve_id = ?");
        $stmt_appreciation_matieres->execute([$eleve_id]);
        $appreciations_par_matiere = $stmt_appreciation_matieres->fetchAll(PDO::FETCH_KEY_PAIR);


        $bulletins[] = [
            'eleve' => $eleve,
            'resultats' => $resultats,
            'moyenne_generale' => $moyenne_generale,
            'appreciation' => $appreciation_texte,
            'appreciations_par_matiere' => $appreciations_par_matiere
        ];
    }
}

if (empty($bulletins)) {
    $final_error = $error_message ?: "Aucun bulletin n'a pu être généré. Vérifiez que l'élève existe et a des notes.";
    die("Erreur: " . htmlspecialchars($final_error));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Notes - Classe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0; 
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .bulletin-container {
            width: 190mm; /* Largeur A4 */
            margin: 20px auto;
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            page-break-after: always;
        }
        /* Style des coins décoratifs */
        .decoration-top-left, .decoration-bottom-right {
            position: absolute;
            width: 100px;
            height: 100px;
            background-color: #e0f2f1; /* Couleur douce originale */
            opacity: 0.8;
            z-index: 0;
        }
        .decoration-top-left { top: 0; left: 0; clip-path: polygon(0 0, 100% 0, 0 100%); }
        .decoration-bottom-right { bottom: 0; right: 0; clip-path: polygon(100% 0, 100% 100%, 0 100%); }

        /* --- HEADER --- */
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        .school-info-flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .school-logo-container {
            width: 120px;
            height: 120px;
            background-color: #e0f2f1;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #26a69a; /* Couleur du tableau */
            margin-left: 30px;
            overflow: hidden;
            float: right;
        }
        .school-logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .school-name {
            text-align: right;
            font-size: 0.9em;
            color: #777;
            clear: both; 
        }
        
        /* --- INFOS PERSONNELLES --- */
        .info-personnelles {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .info-personnelles h2 {
            color: #555;
            font-size: 1.3em;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .info-personnelles p {
            margin: 5px 0;
            color: #444;
        }
        .info-personnelles strong {
            display: inline-block;
            width: 180px; /* Ajusté pour l'alignement */
            font-weight: bold;
        }

        /* --- TABLEAU DE NOTES --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #26a69a; /* Vert Turquoise Original */
            color: #fff;
            font-weight: bold;
        }
        
        /* --- RÉSULTATS GÉNÉRAUX --- */
        .resultats-generaux {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .resultats-generaux h2 {
            text-align: center;
            color: #555;
            font-size: 1.3em;
            margin-bottom: 20px;
            border-bottom: none;
        }
        .resultats-generaux p {
            margin: 10px 0;
            color: #444;
        }
        .moyenne-finale {
            font-size: 1.4em;
            text-align: right;
            padding-right: 15px;
            font-weight: bold;
            color: #333;
        }
        .moyenne-ok { color: #34a853; } /* Vert (Réussite) */
        .moyenne-ko { color: #dc3545; } /* Rouge (Échec) */

        /* --- SIGNATURES ET CACHET --- */
        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 50px;
        }
        .signature-block {
            text-align: center;
            width: 45%;
            position: relative;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-top: 40px;
            margin-bottom: 10px;
            position: relative;
        }
        .cachet-logo-box {
            /* Positionnement au-dessus de la ligne de signature */
            position: absolute;
            top: -70px; 
            left: 50%;
            transform: translateX(-50%);
            width: 90px;
            height: 90px;
            opacity: 0.6; /* Effet de cachet semi-transparent */
            background-color: #e0f2f1;
            border-radius: 50%;
            overflow: hidden;
            border: 2px dashed #26a69a; /* Cadre doux pour le cachet */
        }
        .cachet-logo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .signature-block p {
            font-size: 0.9em;
            color: #555;
        }

        /* Suppression des marges pour l'impression */
        @media print {
            body { margin: 0; padding: 0; }
            .bulletin-container {
                border: none;
                box-shadow: none;
                margin: 0;
            }
            .decoration-top-left, .decoration-bottom-right { display: none; }
        }
    </style>
</head>
<body>

<?php if (empty($bulletins)): ?>
    <div style="color: red; padding: 20px;">
        Erreur: Aucun bulletin n'a pu être généré. Vérifiez si les élèves existent ou s'ils ont des notes.
    </div>
<?php else: ?>
    <?php foreach ($bulletins as $bulletin): 
        $eleve = $bulletin['eleve'];
        $resultats = $bulletin['resultats'];
        $moyenne_generale = $bulletin['moyenne_generale'];
        $appreciation_texte = $bulletin['appreciation'] ?? 'Aucune appréciation générale n\'a été saisie.';
        
        $nom_ecole = "ASSO AMA ÉCOLE";
    ?>
    <div class="bulletin-container">
        <div class="decoration-top-left"></div>
        <div class="decoration-bottom-right"></div>

        <div class="header">
            <div class="school-info-flex">
                <div class="school-logo-container" style="float: left; margin-left: 0;">
                    <img src="<?php echo htmlspecialchars($logo_path_relative); ?>" alt="Logo École">
                </div>
                
                <div style="margin-left: 150px; text-align: center; flex-grow: 1;">
                    <h1>BULLETIN SCOLAIRE</h1>
                    <p style="font-weight: bold; font-size: 1.1em; color: #333; margin-top: 5px;"><?php echo $nom_ecole; ?></p>
                    <p style="color: #666; margin-top: 2px;">Année Scolaire: <?php echo date('Y'); ?></p>
                    <p style="color: #666;">Trimestre 1</p> </div>
                
                <div style="width: 150px; float: right;"></div>
            </div>
        </div>

        <div class="info-personnelles" style="clear: both;">
            <h2>INFORMATIONS ÉLÈVE</h2>
            <p><strong>Nom :</strong> <?php echo htmlspecialchars($eleve['nom']); ?></p>
            <p><strong>Prénom :</strong> <?php echo htmlspecialchars($eleve['prenom']); ?></p>
            <p><strong>Classe :</strong> <?php echo htmlspecialchars($eleve['nom_classe'] ?? 'N/A'); ?></p>
            <p><strong>Niveau :</strong> <?php echo htmlspecialchars($eleve['niveau'] ?? 'N/A'); ?> </p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Matière</th>
                    <th style="width: 20%;">Moyenne Obtenue</th>
                    <th style="width: 15%;">Nb de Notes</th>
                    <th>Appréciation du Professeur</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultats as $res): 
                    // Tenter de trouver l'ID de la matière pour l'appréciation
                    $stmt_matiere_id = $pdo->prepare("SELECT matiere_id FROM matieres WHERE nom_matiere = ?");
                    $stmt_matiere_id->execute([$res['nom_matiere']]);
                    $matiere_id = $stmt_matiere_id->fetchColumn();
                    
                    // Récupérer l'appréciation spécifique à cette matière
                    $appreciation_matiere = $bulletin['appreciations_par_matiere'][$matiere_id] ?? 'Non saisie.'; 
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($res['nom_matiere']); ?></td>
                        <td style="font-weight: bold; color: <?php echo $res['moyenne'] >= 10 ? '#34a853' : '#dc3545'; ?>;">
                            <?php echo number_format((float)$res['moyenne'], 2, ',', ' '); ?> / 20
                        </td>
                        <td><?php echo $res['total_notes']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($appreciation_matiere); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="resultats-generaux">
            <h2>RÉSULTATS GÉNÉRAUX</h2>
            <p class="moyenne-finale">
                Moyenne Générale : <span class="<?php echo $moyenne_generale >= 10 ? 'moyenne-ok' : 'moyenne-ko'; ?>">
                    <?php echo number_format((float)$moyenne_generale, 2, ',', ' '); ?> / 20
                </span>
            </p>
            <h2 style="text-align: center; margin-top: 30px; margin-bottom: 20px; color: #26a69a;">Appréciation Générale</h2>
            <p style="text-align: center; font-style: italic; border: 1px solid #ddd; padding: 10px; background-color: #fafafa;">
                <?php echo htmlspecialchars($appreciation_texte); ?>
            </p>
        </div>

        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line"></div>
                <p>Signature des Parents</p>
            </div>
            <div class="signature-block">
                <div class="signature-line">
                    <div class="cachet-logo-box">
                        <img src="<?php echo htmlspecialchars($logo_path_relative); ?>" alt="Cachet">
                    </div>
                </div>
                <p>Signature du Directeur et Cachet</p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>