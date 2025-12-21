<?php
require 'security.php';
require 'connection.php';
// Fichier : rapport_absences.php (Consultation Graphique)
require 'connection.php';

$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();
$selected_classe_id = $_GET['classe_id'] ?? null;
$rapport_absences = [];
$total_absences = 0;
$graph_data = [];

if ($selected_classe_id) {
    // 1. Récupérer les données pour le tableau (liste des élèves et leurs totaux d'absences)
    $stmt_rapport = $pdo->prepare('
        SELECT 
            e.nom, 
            e.prenom,
            COUNT(a.absence_id) AS total_absences,
            SUM(CASE WHEN a.justifie = 1 THEN 1 ELSE 0 END) AS justifiees,
            SUM(CASE WHEN a.justifie = 0 THEN 1 ELSE 0 END) AS non_justifiees
        FROM eleves e
        LEFT JOIN absences a ON e.eleve_id = a.eleve_id
        WHERE e.classe_id = ?
        GROUP BY e.eleve_id
        ORDER BY total_absences DESC, e.nom
    ');
    $stmt_rapport->execute([$selected_classe_id]);
    $rapport_absences = $stmt_rapport->fetchAll();
    
    // 2. Préparer les données pour le graphique
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
        // Encoder les données au format JSON pour JavaScript
        $graph_data = [
            'labels' => $labels,
            'non_justifiees' => $data_absences,
            'justifiees' => $data_justifiees
        ];
    }
}

require 'includes/header.php';
?>
    <div class="container">
        <h1>Rapports Graphiques d'Absences</h1>

        <form method="GET" action="rapport_absences.php" style="background-color: var(--secondary-bg);">
            <h2>Sélectionner la Classe</h2>
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
            <noscript><button type="submit">Afficher le Rapport</button></noscript>
        </form>

        <?php if ($selected_classe_id): ?>
            <h2>Résultats pour la Classe : <?php 
                $selected_classe_name = array_filter($classes_disponibles, fn($c) => $c['classe_id'] == $selected_classe_id);
                echo htmlspecialchars($selected_classe_name[array_key_first($selected_classe_name)]['nom_classe'] ?? 'N/A');
            ?></h2>
            
            <?php if (!empty($rapport_absences)): ?>
                
                <div style="margin-bottom: 40px; padding: 20px; background-color: white; border-radius: 8px; box-shadow: var(--box-shadow-subtle);">
                    <h3 style="color: var(--text-color); border: none;">Répartition des Absences (Total: <?php echo $total_absences; ?> jours)</h3>
                    <?php if (!empty($graph_data)): ?>
                        <canvas id="absencesChart" style="max-height: 400px;"></canvas>
                        
                        <script>
                            const ABSENCE_DATA = <?php echo json_encode($graph_data); ?>;
                        </script>
                    <?php else: ?>
                        <p class="message success">Aucune absence enregistrée pour les élèves de cette classe.</p>
                    <?php endif; ?>
                </div>

                <h2>Détails des Absences par Élève</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nom & Prénom</th>
                            <th>Total Jours d'Absence</th>
                            <th>Justifiées</th>
                            <th>Non Justifiées</th>
                            <th>Fiche Élève</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rapport_absences as $row): ?>
                            <tr class="<?php echo $row['total_absences'] > 3 ? 'high-absence' : ''; ?>">
                                <td><?php echo htmlspecialchars($row['prenom'] . ' ' . $row['nom']); ?></td>
                                <td style="font-weight: 700; color: <?php echo $row['total_absences'] > 3 ? 'var(--error-color)' : 'var(--primary-color)'; ?>;"><?php echo $row['total_absences']; ?></td>
                                <td style="color: var(--success-color);"><?php echo $row['justifiees']; ?></td>
                                <td style="color: var(--error-color);"><?php echo $row['non_justifiees']; ?></td>
                                <td><a href="eleves_details.php?id=<?php echo $row['eleve_id']; ?>" class="action-link edit">Voir Fiche</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="message success">La classe sélectionnée ne contient aucun élève ou aucune donnée d'absence.</p>
            <?php endif; ?>

        <?php else: ?>
            <p class="message success">Veuillez sélectionner une classe ci-dessus pour générer le rapport.</p>
        <?php endif; ?>
    </div>

<?php require 'includes/footer.php'; ?>