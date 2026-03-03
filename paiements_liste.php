<?php
require 'security.php';
require 'connection.php';

// 1. Récupération de tous les élèves avec leurs informations de classe, de famille et de paiement
// On calcule la somme des paiements effectués pour chaque élève
$sql = "
    SELECT 
        e.eleve_id,
        e.nom, 
        e.prenom,
        c.nom_classe,
        c.tarif_scolarite,
        g.nom_groupe,
        IFNULL(SUM(p.montant_paye), 0) as total_verse
    FROM eleves e
    LEFT JOIN classes c ON e.classe_id = c.classe_id
    LEFT JOIN groupes_paiement g ON e.groupe_id = g.groupe_id
    LEFT JOIN paiements p ON e.eleve_id = p.eleve_id
    GROUP BY e.eleve_id
    ORDER BY c.nom_classe, e.nom
";

$stmt = $pdo->query($sql);
$liste_paiements = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="container">
    <h1>Tableau Global des Impayés</h1>
    
    <p>Ce tableau liste tous les élèves et compare le tarif de leur classe avec les montants déjà versés.</p>

    <div style="margin-top: 20px; margin-bottom: 20px;">
        <input type="text" id="filterInput" placeholder="Rechercher un élève, une classe ou une famille..." 
               style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
    </div>

    <table>
        <thead>
            <tr>
                <th>Élève</th>
                <th>Classe</th>
                <th>Famille</th>
                <th>Tarif Annuel</th>
                <th>Total Versé</th>
                <th>Reste à Payer</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody id="paiementsTable">
            <?php foreach ($liste_paiements as $row): 
                $tarif = $row['tarif_scolarite'] ?? 0;
                $paye = $row['total_verse'];
                $reste = $tarif - $paye;
                
                // Détermination du statut
                if ($reste <= 0 && $tarif > 0) {
                    $statut = '<span style="color: var(--success-color); font-weight: bold;">✅ À jour</span>';
                    $row_class = '';
                } elseif ($paye > 0) {
                    $statut = '<span style="color: orange; font-weight: bold;">⏳ Partiel</span>';
                    $row_class = '';
                } else {
                    $statut = '<span style="color: var(--error-color); font-weight: bold;">❌ Impayé</span>';
                    $row_class = $tarif > 0 ? 'style="background-color: #fff5f5;"' : '';
                }
            ?>
                <tr <?= $row_class ?>>
                    <td><strong><?= htmlspecialchars($row['nom'] . ' ' . $row['prenom']) ?></strong></td>
                    <td><?= htmlspecialchars($row['nom_classe'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['nom_groupe'] ?? '-') ?></td>
                    <td><?= number_format($tarif, 2, ',', ' ') ?> €</td>
                    <td style="color: var(--success-color); font-weight: bold;"><?= number_format($paye, 2, ',', ' ') ?> €</td>
                    <td style="color: <?= $reste > 0 ? 'var(--error-color)' : 'var(--success-color)' ?>; font-weight: bold;">
                        <?= number_format($reste, 2, ',', ' ') ?> €
                    </td>
                    <td><?= $statut ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
// Système de recherche dynamique en JS
document.getElementById('filterInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#paiementsTable tr');
    
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<?php require 'includes/footer.php'; ?>