<?php
// Fichier : gestion_controles.php (Liste et gestion des contrôles de notes)
require 'security.php';
require 'connection.php';

$message = '';
$class = '';

// --- GESTION DE LA SUPPRESSION D'UN CONTRÔLE ---
if (isset($_GET['delete_date']) && isset($_GET['delete_matiere'])) {
    $delete_date = $_GET['delete_date'];
    $delete_matiere_id = filter_var($_GET['delete_matiere'], FILTER_VALIDATE_INT);

    if ($delete_matiere_id) {
        try {
            // Supprime toutes les notes liées à cette date et cette matière
            $stmt = $pdo->prepare("DELETE FROM notes WHERE date_note = ? AND matiere_id = ?");
            $stmt->execute([$delete_date, $delete_matiere_id]);
            $message = "Le contrôle du " . date('d/m/Y', strtotime($delete_date)) . " a été supprimé avec succès.";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression du contrôle : " . $e->getMessage();
            $class = 'error';
        }
    }
}

// --- RÉCUPÉRATION DES CONTRÔLES ENREGISTRÉS ---
// On regroupe les notes par Matière, Date et Description pour identifier chaque contrôle unique.
$controles = $pdo->query('
    SELECT 
        n.date_note, 
        m.nom_matiere, 
        n.matiere_id,
        n.description,
        COUNT(n.note_id) AS total_notes_saisies,
        AVG(n.note) AS moyenne_controle
    FROM notes n
    JOIN matieres m ON n.matiere_id = m.matiere_id
    GROUP BY n.date_note, n.matiere_id, n.description
    ORDER BY n.date_note DESC, m.nom_matiere
')->fetchAll();

require 'includes/header.php';
?>

<div class="container">
    <h1>Gestion des Contrôles Enregistrés</h1>

    <p><a href="gestion_notes.php">&larr; Retour au Tableau de Bord Notes</a></p>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <p style="margin-bottom: 20px;">Cette page liste tous les contrôles qui ont été saisis dans le système. Vous pouvez modifier ou supprimer un contrôle complet.</p>

    <h2>Liste des Contrôles (<?php echo count($controles); ?> sessions)</h2>
    
    <?php if (count($controles) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date du Contrôle</th>
                    <th>Matière</th>
                    <th>Description</th>
                    <th>Notes Saisies</th>
                    <th>Moyenne Contrôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($controles as $controle): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($controle['date_note'])); ?></td>
                        <td><?php echo htmlspecialchars($controle['nom_matiere']); ?></td>
                        <td><?php echo htmlspecialchars($controle['description']); ?></td>
                        <td><?php echo $controle['total_notes_saisies']; ?></td>
                        <td><?php echo number_format($controle['moyenne_controle'], 2, ',', ' '); ?> / 20</td>
                        <td>
                            <a href="saisie_notes.php?date_note=<?php echo urlencode($controle['date_note']); ?>&matiere_id=<?php echo $controle['matiere_id']; ?>" 
                               class="action-link edit" style="color: var(--primary-color);">
                               Modifier (Réouvrir Saisie)
                            </a>
                            |
                            <a href="gestion_controles.php?delete_date=<?php echo urlencode($controle['date_note']); ?>&delete_matiere=<?php echo $controle['matiere_id']; ?>" 
                               onclick="return confirm('ATTENTION : Voulez-vous vraiment supprimer toutes les notes de ce contrôle ? Cette action est irréversible.');" 
                               class="action-link">
                               Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="message error">Aucun contrôle n'a encore été saisi dans le système.</p>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>