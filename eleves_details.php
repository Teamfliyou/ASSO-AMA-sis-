<?php
// Fichier : eleves_details.php (Fiche détaillée de l'élève)
require 'security.php';
require 'connection.php';

$eleve_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$eleve = null;
$absences = [];
$error_message = '';

// Récupération des frais actuels (pour le module Paiement)
$frais_actuels = $pdo->query("SELECT frais_id, montant, nom_frais FROM frais WHERE annee_scolaire = YEAR(CURDATE())")->fetch();

// --- GESTION DU PAIEMENT (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'payer') {
    $paiement_eleve_id = filter_var($_POST['paiement_eleve_id'] ?? null, FILTER_VALIDATE_INT);
    $frais_id = $frais_actuels['frais_id'] ?? null;
    $montant_frais = $frais_actuels['montant'] ?? 0;
    
    if ($paiement_eleve_id && $frais_id && $montant_frais > 0) {
        try {
            // Utilise ON DUPLICATE KEY UPDATE pour gérer la modification si le paiement existe déjà
            $stmt_paiement = $pdo->prepare("
                INSERT INTO paiements (eleve_id, frais_id, date_paiement, montant_paye, methode_paiement)
                VALUES (?, ?, CURDATE(), ?, 'Cash/Virement')
                ON DUPLICATE KEY UPDATE
                    date_paiement = CURDATE(),
                    montant_paye = VALUES(montant_paye)
            ");
            $stmt_paiement->execute([$paiement_eleve_id, $frais_id, $montant_frais]);
            // Redirection pour éviter le renvoi du formulaire (méthode PRG)
            header("Location: eleves_details.php?id={$paiement_eleve_id}&msg=success"); 
            exit;
        } catch (PDOException $e) {
            $error_message = "Erreur d'enregistrement du paiement: " . $e->getMessage();
        }
    } else {
        $error_message = "Impossible d'enregistrer le paiement. Le type de frais n'est pas défini pour cette année.";
    }
}

// --- LOGIQUE D'AFFICHAGE DE LA PAGE ---

if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $success_message = "Paiement enregistré avec succès !";
}

if (!$eleve_id) {
    $error_message = "Identifiant d'élève manquant ou invalide.";
} else {
    try {
        // Récupérer les informations de l'élève
        $stmt_eleve = $pdo->prepare('
            SELECT e.eleve_id, e.nom, e.prenom, c.nom_classe, c.niveau
            FROM eleves e
            LEFT JOIN classes c ON e.classe_id = c.classe_id
            WHERE e.eleve_id = ?
        ');
        $stmt_eleve->execute([$eleve_id]);
        $eleve = $stmt_eleve->fetch();

        if (!$eleve) {
            $error_message = "Aucun élève trouvé avec cet identifiant.";
        } else {
            // Récupérer l'historique des absences
            $stmt_absences = $pdo->prepare('
                SELECT date_absence, justifie, raison, DATEDIFF(CURDATE(), date_absence) AS jours_passes
                FROM absences
                WHERE eleve_id = ?
                ORDER BY date_absence DESC
            ');
            $stmt_absences->execute([$eleve_id]);
            $absences = $stmt_absences->fetchAll();
        }
    } catch (PDOException $e) {
        $error_message = "Erreur de base de données : " . $e->getMessage();
    }
}

require 'includes/header.php';
?>
<div class="container">
    <?php if ($error_message): ?>
        <div class="message error">
            <h1>❌ Erreur</h1>
            <p><?php echo htmlspecialchars($error_message); ?></p>
            <p><a href="eleves.php">Retour à la liste des élèves</a></p>
        </div>
    <?php elseif (!$eleve): ?>
         <div class="message error">
            <h1>❌ Erreur</h1>
            <p>Impossible de charger la fiche élève.</p>
            <p><a href="eleves.php">Retour à la liste des élèves</a></p>
        </div>
    <?php else: ?>
        <h1 style="border-bottom: none; padding-bottom: 0;">Fiche Élève : <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></h1>
        <p style="margin-bottom: 30px;"><a href="eleves.php" style="color: var(--primary-color); text-decoration: none;">&larr; Retour à la gestion des élèves</a></p>
        
        <?php if (isset($success_message)): ?>
             <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
             <div class="message success">Paiement enregistré avec succès !</div>
        <?php endif; ?>

        <div style="display: flex; gap: 30px; margin-bottom: 40px; padding: 20px; background-color: #f9f9f9; border-radius: 8px; box-shadow: var(--box-shadow-subtle);">
            <div>
                <strong>Classe actuelle :</strong> <span><?php echo htmlspecialchars($eleve['nom_classe'] ?: 'Non attribuée'); ?></span>
            </div>
            <div>
                <strong>Niveau :</strong> <span><?php echo htmlspecialchars($eleve['niveau'] ?: 'N/A'); ?></span>
            </div>
            <div>
                <strong>Absences totales :</strong> <span style="font-weight: 700; color: var(--error-color);"><?php echo count($absences); ?></span> jours
            </div>
            
            <div style="margin-left: auto;">
                 <a href="saisie_appreciation.php?eleve_id=<?php echo $eleve['eleve_id']; ?>" 
                    class="action-link edit" 
                    style="color: var(--primary-color); border: 1px solid var(--primary-color); padding: 5px 10px; border-radius: 4px; text-decoration: none;">
                    ✍️ Modifier Appréciations
                 </a>
            </div>
        </div>

        <h2 style="color: var(--success-color);">Statut de Paiement</h2>
        
        <?php 
            $montant_frais = $frais_actuels['montant'] ?? 0;
            
            $paiement_info = $pdo->prepare("
                SELECT montant_paye, date_paiement 
                FROM paiements 
                WHERE eleve_id = ? AND frais_id = ?
            ");
            $paiement_info->execute([$eleve['eleve_id'], $frais_actuels['frais_id'] ?? 0]);
            $statut_paiement = $paiement_info->fetch();

            $montant_paye = $statut_paiement['montant_paye'] ?? 0;
            $est_paye = ($montant_paye >= $montant_frais) && $montant_frais > 0;
        ?>

        <div style="padding: 20px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 30px; background-color: #fff;">
            <strong>Frais dus :</strong> <?php echo number_format($montant_frais, 2, ',', ' '); ?> € (<?php echo htmlspecialchars($frais_actuels['nom_frais'] ?? 'Non défini'); ?>)<br>
            <strong>Statut :</strong> 
            <?php if ($est_paye): ?>
                <span style="color: var(--success-color); font-weight: 700;">✅ PAYÉ (le <?php echo date('d/m/Y', strtotime($statut_paiement['date_paiement'])); ?>)</span>
            <?php elseif ($montant_frais > 0): ?>
                <span style="color: var(--error-color); font-weight: 700;">❌ IMPAYÉ</span>
            <?php else: ?>
                <span style="color: var(--text-color);">N/A (Frais non définis)</span>
            <?php endif; ?>

            <br><br>
            <?php if (!$est_paye && $montant_frais > 0): ?>
                <form method="POST" style="padding: 0; border: none; margin: 0;">
                    <input type="hidden" name="action" value="payer">
                    <input type="hidden" name="paiement_eleve_id" value="<?php echo $eleve['eleve_id']; ?>">
                    <button type="submit" onclick="return confirm('Confirmez-vous l\'enregistrement du paiement de <?php echo $montant_frais; ?> € ?');">
                        Enregistrer le Paiement de <?php echo number_format($montant_frais, 2, ',', ' '); ?> €
                    </button>
                </form>
            <?php elseif ($est_paye): ?>
                 <button disabled style="background-color: #ccc; color: #444; cursor: default;">Paiement déjà enregistré</button>
            <?php endif; ?>
        </div>
        
        <h2>Historique des Absences</h2>
        
        <?php if (count($absences) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date de l'absence</th>
                        <th>Statut</th>
                        <th>Raison</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($absences as $abs): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($abs['date_absence'])); ?></td>
                            <td>
                                <?php if ($abs['justifie']): ?>
                                    <span style="color: var(--success-color); font-weight: 700;">✅ Justifiée</span>
                                <?php else: ?>
                                    <span style="color: var(--error-color); font-weight: 700;">❌ Non Justifiée</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($abs['raison'] ?: 'Non spécifiée'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="message success">Cet élève n'a enregistré aucune absence.</p>
        <?php endif; ?>

        <p style="margin-top: 30px; text-align: center;">
            <a href="bulletin_pdf.php?eleve_id=<?php echo $eleve['eleve_id']; ?>" 
               target="_blank" 
               class="action-link" 
               style="font-size: 1.1em; color: var(--primary-color); border: 1px solid var(--primary-color); padding: 8px 15px; border-radius: 4px; text-decoration: none;">
                Générer Bulletin PDF (Affichage Stylisé) &rarr;
            </a>
            
            <span style="margin: 0 15px;">|</span>
            
            <a href="eleves.php?edit_id=<?php echo $eleve['eleve_id']; ?>" class="action-link edit" style="font-size: 1.1em; color: var(--primary-color);">
                Modifier les informations de l'élève
            </a>
        </p>

    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>