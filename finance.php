<?php
require 'security.php';
require 'connection.php';
require 'functions.php';

// Détermine la "vue" demandée, avec une vue par défaut
$view = $_GET['view'] ?? 'dashboard'; // dashboard, families, unpaid, stats
$id = $_GET['id'] ?? null; // Pour les vues détaillées (ex: une famille)

$message = '';
$class = '';

// ====================================================================
// TRAITEMENT DES FORMULAIRES (POST)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Action : Créer une famille ---
    if ($action === 'create_family') {
        $nom = trim($_POST['nom_famille']);
        if (!empty($nom)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO groupes_paiement (nom_groupe) VALUES (?)");
                $stmt->execute([$nom]);
                $_SESSION['message'] = "La famille '" . htmlspecialchars($nom) . "' a été créée.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur : Cette famille existe déjà.";
            }
        }
        header('Location: finance.php?view=families');
        exit;
    }

    // --- Action : Enregistrer un versement pour une famille ---
    if ($action === 'save_payment' && isset($_POST['montant'])) {
        $montant = filter_var($_POST['montant'], FILTER_VALIDATE_FLOAT);
        $eleve_id = $_POST['eleve_id']; 
        $groupe_id = $_POST['groupe_id']; 
        $frais_id = 1; // A Rendre dynamique si plusieurs types de frais

        if ($montant > 0) {
            $stmt = $pdo->prepare("INSERT INTO paiements (eleve_id, frais_id, montant_paye) VALUES (?, ?, ?)");
            $stmt->execute([$eleve_id, $frais_id, $montant]);
            $_SESSION['message'] = "Paiement de $montant € enregistré.";
        }
        header('Location: finance.php?view=family_details&id=' . $groupe_id);
        exit;
    }
}

// --- Action : Supprimer une famille (GET) ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_family' && $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM groupes_paiement WHERE groupe_id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Famille supprimée.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression.";
    }
    header('Location: finance.php?view=families');
    exit;
}


// Inclure l'en-tête de la page
require 'includes/header.php';

display_session_message();
?>

<div class="container">
    <h1>Module Financier</h1>

    <nav class="sub-nav">
        <a href="finance.php?view=dashboard" class="<?= $view == 'dashboard' ? 'active' : '' ?>">Tableau de Bord</a>
        <a href="finance.php?view=families" class="<?= in_array($view, ['families', 'family_details']) ? 'active' : '' ?>">Gestion des Familles</a>
        <a href="finance.php?view=unpaid" class="<?= $view == 'unpaid' ? 'active' : '' ?>">Suivi des Impayés</a>
        <a href="finance.php?view=stats" class="<?= $view == 'stats' ? 'active' : '' ?>">Statistiques</a>
    </nav>

    <div class="content">
        <?php
        switch ($view) {
            // ====================================================================
            case 'dashboard': // Fusion de paiements.php
            // ====================================================================
                $annee_actuelle = date('Y');
                $frais_actuels = $pdo->query("SELECT * FROM frais WHERE annee_scolaire = {$annee_actuelle}")->fetch();
                $montant_du_par_frais = $frais_actuels['montant'] ?? 0;
                $frais_id = $frais_actuels['frais_id'] ?? null;
                
                $total_gagne = 0;
                $nombre_eleves_payes = 0;
                $nombre_eleves_inscrits = 0;

                $sql_paiements = "SELECT e.eleve_id, e.nom, e.prenom, c.nom_classe, p.montant_paye, p.date_paiement FROM eleves e LEFT JOIN classes c ON e.classe_id = c.classe_id LEFT JOIN paiements p ON e.eleve_id = p.eleve_id AND p.frais_id = :frais_id ORDER BY c.nom_classe, e.nom";
                $stmt_paiements = $pdo->prepare($sql_paiements);
                $stmt_paiements->execute([':frais_id' => $frais_id]);
                $rapport_paiements = $stmt_paiements->fetchAll();

                foreach ($rapport_paiements as &$row) {
                    $nombre_eleves_inscrits++;
                    $paye = $row['montant_paye'] ?? 0;
                    $paye_integralement = ($paye >= $montant_du_par_frais) && ($montant_du_par_frais > 0);
                    $row['statut'] = $paye_integralement ? 'Payé' : 'Impayé';
                    if ($paye_integralement) $nombre_eleves_payes++;
                    $total_gagne += $paye;
                }
                unset($row);
                
                echo "<h2>Tableau de Bord des Paiements (Année {$annee_actuelle})</h2>";
                if (!$frais_id) echo "<div class='message error'>Aucun frais de scolarité défini pour cette année.</div>";
                
                echo "<div style='display: flex; justify-content: space-around; padding: 20px; margin-bottom: 30px; background-color: var(--secondary-bg); border-radius: 8px;'>";
                echo "<div><h3>Frais standard</h3><p>{$montant_du_par_frais} €</p></div>";
                echo "<div><h3>Total encaissé</h3><p>{$total_gagne} €</p></div>";
                echo "<div><h3>Statut</h3><p>{$nombre_eleves_payes} / {$nombre_eleves_inscrits} élèves payés</p></div>";
                echo "</div>";

                echo "<table><thead><tr><th>Nom & Prénom</th><th>Classe</th><th>Statut</th><th>Dernier paiement</th></tr></thead><tbody>";
                foreach ($rapport_paiements as $row) {
                    echo "<tr><td><a href='eleves.php?view=details&id={$row['eleve_id']}'>" . htmlspecialchars($row['prenom'] . ' ' . $row['nom']) . "</a></td>";
                    echo "<td>" . htmlspecialchars($row['nom_classe'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($row['statut'] == 'Payé' ? '<span style="color: var(--success-color);">✅ Payé</span>' : '<span style="color: var(--error-color);">❌ Impayé</span>') . "</td>";
                    echo "<td>" . ($row['date_paiement'] ? date('d/m/Y', strtotime($row['date_paiement'])) : 'N/A') . "</td></tr>";
                }
                echo "</tbody></table>";
                break;

            // ====================================================================
            case 'families': // Fusion de familles.php
            // ====================================================================
                $sql_familles = "SELECT g.groupe_id, g.nom_groupe, COUNT(e.eleve_id) as nb_enfants, IFNULL(SUM(c.tarif_scolarite), 0) as total_du_famille FROM groupes_paiement g LEFT JOIN eleves e ON e.groupe_id = g.groupe_id LEFT JOIN classes c ON e.classe_id = c.classe_id GROUP BY g.groupe_id ORDER BY g.nom_groupe";
                $familles = $pdo->query($sql_familles)->fetchAll();

                echo '<h2>Gestion des Familles</h2>';
                echo '<form method="POST"><input type="hidden" name="action" value="create_family"><h2>Créer une famille</h2><label for="nom_famille">Nom :</label><input type="text" id="nom_famille" name="nom_famille" required><button type="submit">Créer</button></form>';
                
                echo '<h2>Liste des Familles</h2><table><thead><tr><th>Nom</th><th>Enfants</th><th>Total Dû</th><th>Actions</th></tr></thead><tbody>';
                foreach ($familles as $f) {
                    echo "<tr><td><strong>" . htmlspecialchars($f['nom_groupe']) . "</strong></td>";
                    echo "<td>{$f['nb_enfants']}</td>";
                    echo "<td>" . number_format($f['total_du_famille'], 2) . " €</td>";
                    echo "<td><a href='finance.php?view=family_details&id={$f['groupe_id']}' class='action-link'>Suivi Paiements</a> | <a href='finance.php?view=families&action=delete_family&id={$f['groupe_id']}' onclick=\"return confirm('Supprimer cette famille ?');\" style='color:red;'>Supprimer</a></td></tr>";
                }
                echo '</tbody></table>';
                break;

            // ====================================================================
            case 'family_details': // Fusion de paiements_famille.php
            // ====================================================================
                if (!$id) die("ID de famille manquant.");

                $stmt = $pdo->prepare("SELECT SUM(c.tarif_scolarite) as total_du FROM eleves e JOIN classes c ON e.classe_id = c.classe_id WHERE e.groupe_id = ?");
                $stmt->execute([$id]);
                $total_du = $stmt->fetchColumn() ?: 0;

                $stmt_paye = $pdo->prepare("SELECT SUM(p.montant_paye) FROM paiements p JOIN eleves e ON p.eleve_id = e.eleve_id WHERE e.groupe_id = ?");
                $stmt_paye->execute([$id]);
                $total_paye = $stmt_paye->fetchColumn() ?: 0;
                
                $stmt_f = $pdo->prepare("SELECT nom_groupe FROM groupes_paiement WHERE groupe_id = ?");
                $stmt_f->execute([$id]);
                $nom_famille = htmlspecialchars($stmt_f->fetchColumn());

                echo "<h2>Suivi de la Famille : {$nom_famille}</h2>";
                echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;'>";
                echo "<div class='feature-card'><h3>Total Dû</h3><p>" . number_format($total_du, 2) . " €</p></div>";
                echo "<div class='feature-card'><h3>Déjà Payé</h3><p>" . number_format($total_paye, 2) . " €</p></div>";
                echo "<div class='feature-card'><h3>Reste à Payer</h3><p>" . number_format($total_du - $total_paye, 2) . " €</p></div>";
                echo "</div>";

                echo '<form method="POST"><h3>Enregistrer un versement</h3>';
                echo '<input type="hidden" name="action" value="save_payment">';
                echo '<input type="hidden" name="groupe_id" value="'.$id.'">';
                echo '<label>Élève :</label><select name="eleve_id" required>';
                $stmt_e = $pdo->prepare("SELECT eleve_id, prenom, nom FROM eleves WHERE groupe_id = ?");
                $stmt_e->execute([$id]);
                foreach($stmt_e->fetchAll() as $e) echo "<option value='{$e['eleve_id']}'>" . htmlspecialchars($e['prenom'] . ' ' . $e['nom']) . "</option>";
                echo '</select><label>Montant (€) :</label><input type="number" name="montant" step="0.01" required><button type="submit">Valider</button></form>';
                break;
                
            // ====================================================================
            case 'unpaid': // Fusion de paiements_liste.php
            // ====================================================================
                $sql_unpaid = "SELECT e.eleve_id, e.nom, e.prenom, c.nom_classe, c.tarif_scolarite, g.nom_groupe, IFNULL(SUM(p.montant_paye), 0) as total_verse FROM eleves e LEFT JOIN classes c ON e.classe_id = c.classe_id LEFT JOIN groupes_paiement g ON e.groupe_id = g.groupe_id LEFT JOIN paiements p ON e.eleve_id = p.eleve_id GROUP BY e.eleve_id ORDER BY c.nom_classe, e.nom";
                $liste_paiements = $pdo->query($sql_unpaid)->fetchAll();

                echo '<h2>Tableau Global des Impayés</h2><input type="text" id="filterInput" placeholder="Rechercher..." style="width:100%;padding:10px;margin-bottom:20px;">';
                echo '<table><thead><tr><th>Élève</th><th>Classe</th><th>Famille</th><th>Tarif</th><th>Versé</th><th>Reste</th><th>Statut</th></tr></thead><tbody id="paiementsTable">';

                foreach ($liste_paiements as $row) {
                    $tarif = $row['tarif_scolarite'] ?? 0;
                    $paye = $row['total_verse'];
                    $reste = $tarif - $paye;
                    $statut = ($reste <= 0 && $tarif > 0) ? '<span style="color:green;">✅ À jour</span>' : ($paye > 0 ? '<span style="color:orange;">⏳ Partiel</span>' : '<span style="color:red;">❌ Impayé</span>');
                    echo "<tr><td>" . htmlspecialchars($row['nom'] . ' ' . $row['prenom']) . "</td><td>{$row['nom_classe']}</td><td>{$row['nom_groupe']}</td><td>" . number_format($tarif, 2) . " €</td><td>" . number_format($paye, 2) . " €</td><td>" . number_format($reste, 2) . " €</td><td>{$statut}</td></tr>";
                }
                echo '</tbody></table>';
                echo '<script>document.getElementById("filterInput").addEventListener("keyup",function(){let e=this.value.toLowerCase();document.querySelectorAll("#paiementsTable tr").forEach(t=>{let n=t.textContent.toLowerCase();t.style.display=n.includes(e)?"":"none"})});</script>';
                break;

            // ====================================================================
            case 'stats': // Fusion de stats_paiements.php
            // ====================================================================
                $total_attendu = $pdo->query("SELECT SUM(c.tarif_scolarite) FROM eleves e JOIN classes c ON e.classe_id = c.classe_id")->fetchColumn() ?: 0;
                $total_encaisse = $pdo->query("SELECT SUM(montant_paye) FROM paiements")->fetchColumn() ?: 0;
                $reste = $total_attendu - $total_encaisse;
                $taux = $total_attendu > 0 ? ($total_encaisse / $total_attendu) * 100 : 0;

                echo '<h1>Statistiques de Recouvrement</h1>';
                echo '<div class="feature-grid">';
                echo '<div class="feature-card"><h3>Objectif Annuel</h3><p>' . number_format($total_attendu, 2) . ' €</p></div>';
                echo '<div class="feature-card"><h3>Total Encaissé</h3><p>' . number_format($total_encaisse, 2) . ' €</p></div>';
                echo '<div class="feature-card"><h3>À percevoir</h3><p>' . number_format($reste, 2) . ' €</p></div>';
                echo '</div>';
                echo '<div style="margin-top: 50px;"><h3>Progression ('.round($taux, 1).'%)</h3><div style="background: #ddd; height: 30px;"><div style="background: var(--success-color); width: '.$taux.'%; height: 100%;"></div></div></div>';
                break;

            default:
                echo "<p>Vue non valide.</p>";
                break;
        }
        ?>
    </div>
</div>

<?php
require 'includes/footer.php';
?>
