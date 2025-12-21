<?php
require 'security.php';
require 'connection.php';
// Fichier : paiements.php (Gestion et consultation des paiements)
require 'connection.php';

$annee_actuelle = date('Y');
$message = '';
$frais_actuels = $pdo->query("SELECT * FROM frais WHERE annee_scolaire = {$annee_actuelle}")->fetch();
$montant_du_par_frais = $frais_actuels['montant'] ?? 0;
$frais_id = $frais_actuels['frais_id'] ?? null;

$total_gagne = 0;
$nombre_eleves_payes = 0;
$nombre_eleves_inscrits = 0;

// Requête pour récupérer tous les élèves AVEC leur statut de paiement
$sql_paiements = "
    SELECT 
        e.eleve_id,
        e.nom, 
        e.prenom,
        c.nom_classe,
        p.montant_paye,
        p.date_paiement
    FROM eleves e
    LEFT JOIN classes c ON e.classe_id = c.classe_id
    LEFT JOIN paiements p ON e.eleve_id = p.eleve_id AND p.frais_id = :frais_id
    ORDER BY c.nom_classe, e.nom
";

$stmt_paiements = $pdo->prepare($sql_paiements);
$stmt_paiements->execute([':frais_id' => $frais_id]);
$rapport_paiements = $stmt_paiements->fetchAll();

// Calculer le total et mettre à jour le statut
foreach ($rapport_paiements as &$row) {
    $nombre_eleves_inscrits++;
    $paye = $row['montant_paye'] ?? 0;
    
    // Déterminer le statut
    $paye_integralement = ($paye >= $montant_du_par_frais) && ($montant_du_par_frais > 0);
    $row['statut'] = $paye_integralement ? 'Payé' : 'Impayé';
    
    if ($paye_integralement) {
        $nombre_eleves_payes++;
    }
    
    $total_gagne += $paye;
}
unset($row); // Détruire la référence pour éviter les effets secondaires

require 'includes/header.php';
?>

    <div class="container">
        <h1>Gestion des Paiements (Année <?php echo $annee_actuelle; ?>)</h1>
        
        <?php if (!$frais_id): ?>
            <div class="message error">
                ❌ ERREUR : Aucun frais de scolarité n'est défini pour l'année <?php echo $annee_actuelle; ?>.
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-around; padding: 20px; margin-bottom: 30px; background-color: var(--secondary-bg); border-radius: 8px;">
            <div style="text-align: center;">
                <h3 style="color: var(--primary-color); border: none;">Frais standard</h3>
                <p style="font-size: 1.2em; font-weight: 700;"><?php echo number_format($montant_du_par_frais, 2, ',', ' '); ?> €</p>
            </div>
            <div style="text-align: center;">
                <h3 style="color: var(--success-color); border: none;">Total encaissé</h3>
                <p style="font-size: 1.2em; font-weight: 700;"><?php echo number_format($total_gagne, 2, ',', ' '); ?> €</p>
            </div>
             <div style="text-align: center;">
                <h3 style="color: var(--error-color); border: none;">Statut des paiements</h3>
                <p style="font-size: 1.2em; font-weight: 700;"><?php echo $nombre_eleves_payes; ?> / <?php echo $nombre_eleves_inscrits; ?> élèves payés</p>
            </div>
        </div>

        <h2>Statut des Élèves</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom & Prénom</th>
                    <th>Classe</th>
                    <th>Montant dû</th>
                    <th>Montant payé</th>
                    <th>Statut</th>
                    <th>Dernière action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rapport_paiements as $row): ?>
                    <tr>
                        <td><a href="eleves_details.php?id=<?php echo $row['eleve_id']; ?>" class="action-link edit"><?php echo htmlspecialchars($row['prenom'] . ' ' . $row['nom']); ?></a></td>
                        <td><?php echo htmlspecialchars($row['nom_classe'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($montant_du_par_frais, 2, ',', ' '); ?> €</td>
                        <td style="font-weight: 600;"><?php echo number_format($row['montant_paye'] ?? 0, 2, ',', ' '); ?> €</td>
                        <td>
                            <?php if ($row['statut'] == 'Payé'): ?>
                                <span style="color: var(--success-color); font-weight: 700;">✅ Payé</span>
                            <?php else: ?>
                                <span style="color: var(--error-color); font-weight: 700;">❌ Impayé</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['date_paiement'] ? date('d/m/Y', strtotime($row['date_paiement'])) : 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php require 'includes/footer.php'; ?>