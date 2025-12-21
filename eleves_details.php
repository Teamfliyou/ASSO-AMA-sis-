<?php
require 'security.php';
require 'connection.php';

$eleve_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$eleve = null;

if ($eleve_id) {
    $stmt = $pdo->prepare('
        SELECT e.*, c.nom_classe, c.niveau
        FROM eleves e
        LEFT JOIN classes c ON e.classe_id = c.classe_id
        WHERE e.eleve_id = ?
    ');
    $stmt->execute([$eleve_id]);
    $eleve = $stmt->fetch();
}

require 'includes/header.php';
?>
<div class="container">
    <?php if (!$eleve): ?>
        <p>Élève introuvable.</p>
    <?php else: ?>
        <h1>Fiche Élève : <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></h1>
        <p><a href="eleves.php">&larr; Retour à la liste</a></p>

        <div style="display: flex; gap: 30px; margin-bottom: 40px; padding: 20px; background-color: #f9f9f9; border-radius: 8px;">
            <div>
                <strong>Classe :</strong> <?php echo htmlspecialchars($eleve['nom_classe'] ?: 'N/A'); ?>
            </div>
            <div>
                <strong>📞 Téléphone Parent :</strong> 
                <?php if ($eleve['telephone_parent']): ?>
                    <a href="tel:<?php echo $eleve['telephone_parent']; ?>" style="color: var(--primary-color); font-weight: bold;">
                        <?php echo htmlspecialchars($eleve['telephone_parent']); ?>
                    </a>
                <?php else: ?>
                    <span style="color: gray;">Non renseigné</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>