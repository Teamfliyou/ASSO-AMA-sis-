<?php
require 'security.php';
require 'connection.php';

// 1. Total attendu (Élèves inscrits x Tarif de leur classe)
$total_attendu = $pdo->query("
    SELECT SUM(c.tarif_scolarite) 
    FROM eleves e 
    JOIN classes c ON e.classe_id = c.classe_id
")->fetchColumn() ?: 0;

// 2. Total encaissé
$total_encaisse = $pdo->query("SELECT SUM(montant_paye) FROM paiements")->fetchColumn() ?: 0;

$reste = $total_attendu - $total_encaisse;
$taux = $total_attendu > 0 ? ($total_encaisse / $total_attendu) * 100 : 0;

require 'includes/header.php';
?>
<div class="container">
    <h1>Statistiques de Recouvrement</h1>
    
    <div class="feature-grid">
        <div class="feature-card">
            <div class="icon">💰</div>
            <div class="card-title">Objectif Annuel</div>
            <p><?= number_format($total_attendu, 2) ?> €</p>
        </div>
        <div class="feature-card">
            <div class="icon" style="color: green;">✅ Encaissé</div>
            <div class="card-title">Total Reçu</div>
            <p><?= number_format($total_encaisse, 2) ?> €</p>
        </div>
        <div class="feature-card">
            <div class="icon" style="color: red;">⏳ Restant</div>
            <div class="card-title">À percevoir</div>
            <p><?= number_format($reste, 2) ?> €</p>
        </div>
    </div>

    <div style="margin-top: 50px;">
        <h3>Progression des encaissements (<?= round($taux, 1) ?>%)</h3>
        <div style="background: #ddd; border-radius: 10px; height: 30px; width: 100%;">
            <div style="background: var(--success-color); width: <?= $taux ?>%; height: 100%; border-radius: 10px;"></div>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>