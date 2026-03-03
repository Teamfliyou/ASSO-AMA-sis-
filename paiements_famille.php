<?php
require 'security.php';
require 'connection.php';

$groupe_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$groupe_id) die("Erreur : Famille non spécifiée.");

// Traitement d'un nouveau paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['montant'])) {
    $montant = filter_var($_POST['montant'], FILTER_VALIDATE_FLOAT);
    $eleve_id = $_POST['eleve_id']; // On attribue le paiement à un élève de la famille
    $frais_id = $_POST['frais_id'];

    if ($montant > 0) {
        $stmt = $pdo->prepare("INSERT INTO paiements (eleve_id, frais_id, montant_paye) VALUES (?, ?, ?)");
        $stmt->execute([$eleve_id, $frais_id, $montant]);
        $message = "Paiement de $montant € enregistré.";
    }
}

// Récupération du total dû (somme des tarifs des classes des enfants)
$stmt = $pdo->prepare("
    SELECT SUM(c.tarif_scolarite) as total_du 
    FROM eleves e 
    JOIN classes c ON e.classe_id = c.classe_id 
    WHERE e.groupe_id = ?
");
$stmt->execute([$groupe_id]);
$total_du = $stmt->fetchColumn() ?: 0;

// Récupération du total déjà payé par la famille
$stmt_paye = $pdo->prepare("
    SELECT SUM(p.montant_paye) 
    FROM paiements p 
    JOIN eleves e ON p.eleve_id = e.eleve_id 
    WHERE e.groupe_id = ?
");
$stmt_paye->execute([$groupe_id]);
$total_paye = $stmt_paye->fetchColumn() ?: 0;

require 'includes/header.php';
?>
<div class="container">
    <h1>Suivi Famille : <?php 
        $stmt_f = $pdo->prepare("SELECT nom_groupe FROM groupes_paiement WHERE groupe_id = ?");
        $stmt_f->execute([$groupe_id]);
        echo htmlspecialchars($stmt_f->fetchColumn());
    ?></h1>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
        <div class="feature-card" style="background: #f0f4ff;">
            <h3>Total Dû</h3>
            <p style="font-size: 1.5em;"><?= number_format($total_du, 2) ?> €</p>
        </div>
        <div class="feature-card" style="background: #e6f4e6;">
            <h3>Déjà Payé</h3>
            <p style="font-size: 1.5em; color: green;"><?= number_format($total_paye, 2) ?> €</p>
        </div>
        <div class="feature-card" style="background: #fff5f5;">
            <h3>Reste à Payer</h3>
            <p style="font-size: 1.5em; color: red;"><?= number_format($total_du - $total_paye, 2) ?> €</p>
        </div>
    </div>

    <form method="POST">
        <h3>Enregistrer un versement</h3>
        <label>Élève bénéficiaire :</label>
        <select name="eleve_id" required>
            <?php
            $stmt_e = $pdo->prepare("SELECT eleve_id, prenom FROM eleves WHERE groupe_id = ?");
            $stmt_e->execute([$groupe_id]);
            foreach($stmt_e->fetchAll() as $e) echo "<option value='{$e['eleve_id']}'>{$e['prenom']}</option>";
            ?>
        </select>
        <input type="hidden" name="frais_id" value="1"> <label>Montant (€) :</label>
        <input type="number" name="montant" step="0.01" required>
        <button type="submit">Valider le versement</button>
    </form>
</div>
<?php require 'includes/footer.php'; ?>