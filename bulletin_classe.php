<?php
// Fichier : bulletin_classe.php (Contrôle d'exportation par lot)
require 'security.php';
require 'connection.php';

$message = '';
$class = '';
$classes_disponibles = $pdo->query('SELECT classe_id, nom_classe FROM classes ORDER BY nom_classe')->fetchAll();
$selected_classe_id = $_GET['classe_id'] ?? null;
$eleves_classe = [];

if ($selected_classe_id) {
    // Récupérer les élèves de la classe sélectionnée
    $stmt_eleves = $pdo->prepare("SELECT eleve_id, nom, prenom FROM eleves WHERE classe_id = ? ORDER BY nom");
    $stmt_eleves->execute([$selected_classe_id]);
    $eleves_classe = $stmt_eleves->fetchAll();
    
    if (empty($eleves_classe)) {
         $message = "La classe sélectionnée ne contient aucun élève.";
         $class = 'error';
    }
}

require 'includes/header.php';
?>

<div class="container">
    <h1>Génération des Bulletins de Classe</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="GET" action="bulletin_classe.php" style="background-color: var(--secondary-bg);">
        <h2>Sélectionner la Classe à Exporter</h2>
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
        <button type="submit">Afficher les Élèves</button>
    </form>

    <?php if ($selected_classe_id && !empty($eleves_classe)): ?>
        <h2 style="color: var(--primary-color);">Élèves de la Classe (<?php echo count($eleves_classe); ?>)</h2>
        
        <p>
            En cliquant sur le bouton ci-dessous, une nouvelle fenêtre s'ouvrira avec **tous les bulletins** générés en HTML.
        </p>
        
        <div style="margin-top: 30px; text-align: center; margin-bottom: 40px;">
            <button 
                onclick="generateAllBulletins()" 
                style="padding: 15px 30px; font-size: 1.2em; background-color: var(--success-color);">
                Générer TOUS les Bulletins (PDF)
            </button>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Nom & Prénom</th>
                    <th>Action Individuelle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eleves_classe as $eleve): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></td>
                        <td>
                            <a href="bulletin_pdf.php?eleve_id=<?php echo $eleve['eleve_id']; ?>" target="_blank" class="action-link edit" style="color: var(--primary-color);">Voir Bulletin Individuel</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <script>
            // Récupère les IDs des élèves directement dans une variable JS
            const ELEVE_IDS = [
                <?php foreach ($eleves_classe as $eleve) {
                    echo $eleve['eleve_id'] . ',';
                } ?>
            ];

            function generateAllBulletins() {
                if (ELEVE_IDS.length === 0) {
                    alert("Aucun élève à exporter.");
                    return;
                }
                
                // On crée une URL qui va passer tous les IDs à la page bulletin_pdf.php
                const url = 'bulletin_pdf.php?ids=' + ELEVE_IDS.join(',');
                
                const bulletinWindow = window.open(url, '_blank');
                
                // Petite astuce pour lancer la boîte d'impression automatiquement
                bulletinWindow.onload = function() {
                    bulletinWindow.print();
                };
            }
        </script>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>