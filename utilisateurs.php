<?php
// Fichier : utilisateurs.php (Gestion des Comptes Utilisateurs)
require 'security.php';
require 'connection.php';

// Rediriger si l'utilisateur n'est pas admin (vérification de sécurité)
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$class = '';
$edit_user_data = null;

// --- FONCTION DE GÉNÉRATION DE MOT DE PASSE TEMPORAIRE ---
function generate_temp_password($length = 8) {
    $chars = 'abcdefghjkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Caractères sans ambiguïté
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

// --- FONCTION DE GÉNÉRATION AUTOMATIQUE D'IDENTIFIANT ---
function generate_username($prenom, $nom) {
    // Nettoyer, convertir en minuscules et créer un format 'prenom.nom'
    $prenom = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $prenom));
    $nom = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $nom));
    $prenom = preg_replace('/[^a-z0-9]/', '', $prenom);
    $nom = preg_replace('/[^a-z0-9]/', '', $nom);
    return substr($prenom, 0, 1) . '.' . $nom; // Ex: d.dupont
}

// Définition des pages et des rôles
$pages = [
    'classes.php' => 'Gestion Classes',
    'eleves.php' => 'Gestion Élèves (CRUD/Liste)',
    'absences.php' => 'Saisie d\'Absences',
    'rapport_absences.php' => 'Rapports Absences',
    'paiements.php' => 'Gestion Paiements',
    'send_email.php' => 'Envoi E-mail',
];
$roles = ['admin', 'personnel'];

// --- GESTION DES ACTIONS (AJOUT/MODIFICATION) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $user_id = $_POST['user_id'] ?? null;
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $role = $_POST['role'] ?? 'personnel';
    $temp_password_input = $_POST['temp_password_input'] ?? ''; 
    
    // NOUVEAU: Si l'action est 'add', on génère le username à partir du nom complet
    if ($action === 'add') {
        $nom_parts = explode(' ', $nom_complet);
        if (count($nom_parts) >= 2) {
             $username = generate_username($nom_parts[0], end($nom_parts)); // Utilise le premier mot comme prénom, le dernier comme nom
        } else {
             $username = generate_username($nom_complet, $nom_complet); // Fallback
        }
    } else {
        $username = trim($_POST['username'] ?? ''); // En édition, on reprend l'ancien
    }
    
    $selected_permissions = implode(',', $_POST['permissions'] ?? []);

    if (empty($username) || empty($nom_complet)) {
        $message = "Le nom complet est requis.";
        $class = 'error';
    } else {
        try {
            if ($action === 'add') {
                // Vérifier si le username généré existe déjà (pour la robustesse)
                $original_username = $username;
                $i = 1;
                while($pdo->prepare("SELECT user_id FROM utilisateurs WHERE username = ?")->execute([$username]) && $pdo->prepare("SELECT user_id FROM utilisateurs WHERE username = ?")->fetch()) {
                    $username = $original_username . $i; // Ajoute un numéro si un conflit existe (d.dupont1, d.dupont2)
                    $i++;
                }

                // Génération automatique du MDP pour l'ajout
                $temp_password = generate_temp_password();
                $password_hash = hash('sha256', $temp_password);
                
                $stmt = $pdo->prepare("
                    INSERT INTO utilisateurs (username, password_hash, nom_complet, role, permissions, must_change_password) 
                    VALUES (?, ?, ?, ?, ?, TRUE)
                ");
                $stmt->execute([$username, $password_hash, $nom_complet, $role, $selected_permissions]);
                $message = "Utilisateur '{$username}' créé. Le mot de passe temporaire est : <strong style='color: var(--error-color);'>{$temp_password}</strong>. Il devra le changer à la connexion.";
                $class = 'success';
            } elseif ($action === 'edit' && $user_id) {
                $sql = "UPDATE utilisateurs SET nom_complet = ?, role = ?, permissions = ? ";
                $params = [$nom_complet, $role, $selected_permissions];
                
                if (!empty($temp_password_input)) {
                    $password_hash = hash('sha256', $temp_password_input);
                    $sql .= ", password_hash = ?, must_change_password = TRUE ";
                    $params[] = $password_hash;
                }

                $sql .= " WHERE user_id = ?";
                $params[] = $user_id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = "Utilisateur '{$username}' mis à jour avec succès.";
                if (!empty($temp_password_input)) {
                    $message .= " Un nouveau mot de passe temporaire a été défini.";
                }
                $class = 'success';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                 $message = "Erreur : L'identifiant '{$username}' existe déjà.";
            } else {
                $message = "Erreur lors de l'opération : " . $e->getMessage();
            }
            $class = 'error';
        }
    }
} elseif (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id && $delete_id != $_SESSION['user_id']) { 
        try {
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE user_id = ?");
            $stmt->execute([$delete_id]);
            $message = "Utilisateur supprimé.";
            $class = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression : " . $e->getMessage();
            $class = 'error';
        }
    } else {
         $message = "Vous ne pouvez pas supprimer votre propre compte.";
         $class = 'error';
    }
}

// --- RECUPERATION DES DONNÉES ---
$utilisateurs = $pdo->query('SELECT user_id, username, nom_complet, role, permissions, is_active, must_change_password FROM utilisateurs ORDER BY user_id')->fetchAll();
$edit_user_data = null;
$edit_user_perms = [];

if (isset($_GET['edit_id'])) {
    $edit_id = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE user_id = ?");
        $stmt->execute([$edit_id]);
        $edit_user_data = $stmt->fetch();
        $edit_user_perms = explode(',', $edit_user_data['permissions']);
    }
}

require 'includes/header.php';
?>

<div class="container">
    <h1>Administration des Utilisateurs</h1>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $class; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">
        <h2><?php echo $edit_user_data ? 'Modifier ' . htmlspecialchars($edit_user_data['username']) : 'Créer un Nouvel Utilisateur'; ?></h2>
        
        <input type="hidden" name="action" value="<?php echo $edit_user_data ? 'edit' : 'add'; ?>">
        <?php if ($edit_user_data): ?>
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user_data['user_id']); ?>">
        <?php endif; ?>

        <label for="nom_complet">Nom Complet :</label>
        <input type="text" id="nom_complet" name="nom_complet" required value="<?php echo htmlspecialchars($edit_user_data['nom_complet'] ?? ''); ?>">
        
        <?php if ($edit_user_data): ?>
             <label for="username_display">Identifiant : <span style="font-weight: 700; color: var(--primary-color);"><?php echo htmlspecialchars($edit_user_data['username']); ?></span></label>
             <input type="hidden" name="username" value="<?php echo htmlspecialchars($edit_user_data['username']); ?>">
        <?php else: ?>
             <p style="font-size: 0.9em; margin-top: -15px;">(L'identifiant de connexion sera généré automatiquement à partir du Nom Complet.)</p>
        <?php endif; ?>

        <label for="role">Rôle :</label>
        <select id="role" name="role" required>
            <?php foreach ($roles as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo (isset($edit_user_data['role']) && $edit_user_data['role'] == $r) ? 'selected' : ''; ?>>
                    <?php echo ucfirst($r); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <?php if ($edit_user_data): ?>
            <label for="temp_password_input">Nouveau Mot de Passe Temporaire (Optionnel) :</label>
            <input type="text" id="temp_password_input" name="temp_password_input" placeholder="Laisser vide pour ne pas changer">
            <p style="font-size: 0.9em; color: var(--error-color); margin-top: -15px; margin-bottom: 25px;">Si un MDP est défini, l'utilisateur devra le changer à la prochaine connexion.</p>
        <?php endif; ?>

        
        <h3>Permissions d'Accès aux Pages</h3>
        <p style="margin-bottom: 5px; font-weight: 700;">Pages accessibles à cet utilisateur (cochez les accès) :</p>
        <div style="border: 1px solid #ccc; padding: 15px; border-radius: 4px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <?php foreach ($pages as $file => $label): ?>
                <?php 
                    $is_checked = false;
                    if ($edit_user_data) {
                        $is_checked = in_array($file, $edit_user_perms);
                    } else {
                        // Par défaut, l'admin a tout, les autres n'ont rien (index.php est toujours accessible via security.php)
                        if ($role === 'admin' || $file === 'index.php') {
                            $is_checked = true;
                        }
                    }
                ?>
                <div>
                    <input type="checkbox" id="<?php echo $file; ?>" name="permissions[]" value="<?php echo $file; ?>"
                           <?php if ($is_checked) echo 'checked'; ?>
                           <?php if ($edit_user_data && $edit_user_data['role'] === 'admin') echo 'disabled'; ?>>
                    <label for="<?php echo $file; ?>"><?php echo $label; ?></label>
                </div>
            <?php endforeach; ?>
            <?php if ($edit_user_data && $edit_user_data['role'] === 'admin'): ?>
                <p style="grid-column: 1 / span 2; color: var(--primary-color); font-style: italic; margin-top: 10px;">*Les administrateurs ont tous les droits par défaut.</p>
            <?php endif; ?>
        </div>

        <button type="submit" style="margin-top: 20px;"><?php echo $edit_user_data ? 'Enregistrer les Modifications' : 'Créer l\'Utilisateur'; ?></button>
        <?php if ($edit_user_data): ?>
            <a href="utilisateurs.php" style="margin-left: 10px; color: var(--text-color); text-decoration: none;">Annuler</a>
        <?php endif; ?>
    </form>
    
    
    <h2>Liste des Comptes Existants</h2>
    <table>
        <thead>
            <tr>
                <th>Identifiant</th>
                <th>Nom Complet</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($utilisateurs as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['nom_complet'] ?? 'N/A'); ?></td>
                    <td><span style="font-weight: 700; color: <?php echo $user['role'] === 'admin' ? 'var(--error-color)' : 'var(--primary-color)'; ?>;"><?php echo ucfirst($user['role']); ?></span></td>
                    <td>
                        <?php if ($user['must_change_password']): ?>
                            <span style="color: var(--error-color); font-weight: 700;">⚠️ MDP Temporaire</span>
                        <?php else: ?>
                            <span style="color: var(--success-color);">Actif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="utilisateurs.php?edit_id=<?php echo $user['user_id']; ?>" class="action-link edit">Modifier</a>
                        <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                            | <a href="utilisateurs.php?delete_id=<?php echo $user['user_id']; ?>" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');" 
                               class="action-link">Supprimer</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'includes/footer.php'; ?>