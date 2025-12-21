<?php
// Fichier : export_appel.php (Générateur de Feuille d'Appel Multi-Jours OPTIMISÉ)
require 'security.php';
require 'connection.php';

$message = '';
$class = '';
$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();

$selected_classe_id = $_GET['classe_id'] ?? null;
$start_date_str = $_GET['start_date'] ?? date('Y-m-d');
$num_days = filter_var($_GET['num_days'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 30]]);
$day_of_week_int = filter_var($_GET['day_of_week'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 7]]);

$target_dates = [];
$eleves_classe = [];
$date_check = new DateTime($start_date_str);
$day_names = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
$day_of_week_name = $day_names[$day_of_week_int] ?? 'Jour Inconnu';
$classe_nom = '';


// --- LOGIQUE DE CALCUL DES DATES (PHP) ---
if ($selected_classe_id) {
    // Récupérer le nom de la classe
    $classe_info = $pdo->prepare("SELECT nom_classe FROM classes WHERE classe_id = ?");
    $classe_info->execute([$selected_classe_id]);
    $classe_nom = $classe_info->fetchColumn() ?? 'Classe Inconnue';
    
    // 1. Calculer les jours ciblés
    $counter = 0;
    while ($counter < $num_days && $date_check->format('Y') == date('Y')) { // Limite à l'année en cours
        // Si le jour correspond au jour de la semaine sélectionné
        if ((int)$date_check->format('N') === $day_of_week_int) {
            $target_dates[] = $date_check->format('Y-m-d');
            $counter++;
        }
        // Avancer d'un jour
        $date_check->modify('+1 day');
    }
    
    // 2. Récupérer les élèves de la classe
    $stmt_eleves = $pdo->prepare("SELECT eleve_id, nom, prenom FROM eleves WHERE classe_id = ? ORDER BY nom");
    $stmt_eleves->execute([$selected_classe_id]);
    $eleves_classe = $stmt_eleves->fetchAll();
}

// --- MISE EN PAGE IMPRIMABLE (Mode Impression) ---
if (isset($_GET['print'])) {
    if (empty($target_dates) || empty($eleves_classe)) {
         die("Erreur: Aucune date ou élève trouvé pour cette sélection.");
    }
    
    $first_date = date('d/m/Y', strtotime($target_dates[0]));
    $last_date = date('d/m/Y', strtotime(end($target_dates)));
    $nombre_dates = count($target_dates);

    // Début du HTML imprimable
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Feuille d'Appel - <?php echo $classe_nom; ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
            .print-page { 
                width: 270mm; /* Largeur pour paysage */
                margin: 20px auto; 
                padding: 10px; 
                page-break-after: always; 
                box-shadow: 0 0 5px rgba(0,0,0,0.1);
            }
            .title-box { text-align: center; margin-bottom: 25px; }
            .title-box h1 { font-size: 2em; margin: 0; color: #333; }
            .info-bar { font-size: 1em; margin-bottom: 25px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .info-bar strong { font-weight: bold; color: #1a73e8; }
            
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { 
                border: 1px solid #333; 
                padding: 10px 5px; 
                text-align: center; 
                font-size: 0.9em; 
                height: 35px; /* Hauteur suffisante */
            }
            th { 
                background-color: #f0f4ff; 
                color: #333; 
                font-weight: bold; 
                text-transform: uppercase;
                white-space: nowrap;
            }
            
            /* DÉFINITION DES LARGEURS POUR LE DESIGN DEMANDÉ */
            .name-col { width: 15%; text-align: left; }
            .firstname-col { width: 15%; text-align: left; }
            /* Les colonnes de dates se partagent le 70% restant */
            .date-col { width: <?php echo $nombre_dates > 0 ? (70 / $nombre_dates) : 0; ?>%; } 

            .signatures { margin-top: 50px; text-align: right; }
            .signatures p { margin-top: 20px; border-top: 1px solid #333; display: inline-block; padding-top: 5px; }
            
            /* Média print pour un rendu PDF parfait */
            @media print {
                .print-page { box-shadow: none; border: none; }
                body { background-color: white !important; }
            }
        </style>
    </head>
    <body>
        <div class="print-page">
            
            <div class="title-box">
                <h1>FEUILLE D'APPEL – CLASSE <?php echo htmlspecialchars($classe_nom); ?></h1>
            </div>
            
            <div class="info-bar">
                <p>📖 Classe : **<?php echo htmlspecialchars($classe_nom); ?>**</p>
                <p>📅 Période : du **<?php echo $first_date; ?>** au **<?php echo $last_date; ?>**</p>
                <p>🕙 Jour du cours : **<?php echo $day_of_week_name; ?>** (Exemple: Samedi matin)</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th class="name-col">Nom</th>
                        <th class="firstname-col">Prénom</th>
                        <?php foreach ($target_dates as $date): ?>
                            <th class="date-col">
                                <?php echo date('d/m/Y', strtotime($date)); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eleves_classe as $eleve): ?>
                        <tr>
                            <td class="name-col"><?php echo htmlspecialchars($eleve['nom']); ?></td>
                            <td class="firstname-col"><?php echo htmlspecialchars($eleve['prenom']); ?></td>
                            <?php for ($i = 0; $i < $nombre_dates; $i++): ?>
                                <td class="date-col">□</td> <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="signatures">
                <p>Signature du CPE : ......................................................</p>
            </div>
            
        </div>
        <script>
            // Lance la boîte d'impression du navigateur immédiatement
            window.onload = function() { 
                document.title = "Feuille Appel - <?php echo $classe_nom; ?>"; // Titre pour le PDF
                window.print(); 
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}
// --- FIN DE MISE EN PAGE IMPRIMABLE ---
?>

<?php require 'includes/header.php'; ?>

<div class="container">
    <h1>Exportation Feuille d'Appel Planifiée</h1>
    <div style="margin-bottom: 30px;">
        <p>Ce module permet de générer une feuille d'appel prête à imprimer pour plusieurs jours spécifiques (ex: les 4 prochains mardis).</p>
    </div>

    <form method="GET" action="export_appel.php" style="background-color: var(--secondary-bg);">
        <h2>Définir la Période et la Classe</h2>
        
        <label for="classe_id">Classe :</label>
        <select id="classe_id" name="classe_id" required>
            <option value="">-- Choisir une classe --</option>
            <?php foreach ($classes_disponibles as $classe): ?>
                <option value="<?php echo htmlspecialchars($classe['classe_id']); ?>" 
                    <?php if ($selected_classe_id == $classe['classe_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($classe['nom_classe']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="start_date">Date de Début de la Recherche :</label>
        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date_str); ?>" required>

        <label for="day_of_week">Jour Cible (Jour de la Semaine) :</label>
        <select id="day_of_week" name="day_of_week" required>
            <?php foreach ($day_names as $num => $name): ?>
                <option value="<?php echo $num; ?>" <?php if ($day_of_week_int == $num) echo 'selected'; ?>>
                    <?php echo $name; ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label for="num_days">Nombre de Jours à Planifier :</label>
        <input type="number" id="num_days" name="num_days" value="<?php echo $num_days; ?>" min="1" max="30" required>
        
        <button type="submit">Calculer les Dates</button>
    </form>

    <?php if ($selected_classe_id && !empty($target_dates)): ?>
        <h2 style="margin-top: 40px;">Dates Planifiées (<?php echo count($target_dates); ?>)</h2>
        <p style="font-weight: 700;">Les dates calculées pour le **<?php echo $day_of_week_name; ?>** sont :</p>
        
        <ul style="list-style-type: none; padding: 0; display: flex; flex-wrap: wrap; gap: 20px;">
            <?php foreach ($target_dates as $date): ?>
                <li style="background-color: var(--primary-light); color: white; padding: 8px 15px; border-radius: 4px;">
                    <?php echo date('d/m/Y', strtotime($date)); ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($eleves_classe)): ?>
            <div style="margin-top: 40px; text-align: center;">
                <button 
                    onclick="window.open('export_appel.php?classe_id=<?php echo $selected_classe_id; ?>&start_date=<?php echo $start_date_str; ?>&num_days=<?php echo $num_days; ?>&day_of_week=<?php echo $day_of_week_int; ?>&print=true', '_blank')" 
                    style="padding: 15px 30px; font-size: 1.2em; background-color: var(--success-color);">
                    Générer et Imprimer la Feuille d'Appel Planifiée 🖨️
                </button>
            </div>
        <?php else: ?>
             <p class="message error">Cette classe ne contient aucun élève à exporter.</p>
        <?php endif; ?>

    <?php elseif ($selected_classe_id && empty($target_dates)): ?>
        <p class="message error">Aucune date correspondant à vos critères n'a été trouvée (vérifiez l'année ou le nombre de jours).</p>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>