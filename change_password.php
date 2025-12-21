<?php
// Fichier : change_password.php
require 'security.php'; // Vérifie la connexion, mais la redirection est gérée ici.
require 'connection.php';

$message = '';
$class = '';
$user_id = $_SESSION['user_id'];
$must_change = false;

// 1. Vérification si l'utilisateur DOIT changer son mot de passe
$stmt_check = $pdo->prepare("SELECT must_change_password FROM utilisateurs WHERE user_id = ?");
$stmt_check->execute([$user_id]);
$user_data = $stmt_check->fetch();

if ($user_data && $user_data['must_change_password']) {
    $must_change = true;
} elseif (isset($_SESSION['redirect_url'])) {
    // Si l'utilisateur n'a plus besoin de changer, le rediriger
    $redirect_url = $_SESSION['redirect_url'];
    unset($_SESSION['redirect_url']);
    header('Location: ' . $redirect_url);
    exit;
}

// 2. Traitement du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $must_change) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || $new_password !== $confirm_password || strlen($new_password) < 6) {
        $message = "Les mots de passe ne correspondent pas ou sont trop courts (min. 6 caractères).";
        $class = 'error';
    } else {
        try {
            $new_hash = hash('sha256', $new_password);
            
            $stmt = $pdo->prepare("
                UPDATE utilisateurs 
                SET password_hash = ?, must_change_password = FALSE 
                WHERE user_id = ?
            ");
            $stmt->execute([$new_hash, $user_id]);
            
            $message = "Mot de passe mis à jour avec succès. Vous allez être redirigé.";
            $class = 'success';
            
            // Redirection après un court délai pour que l'utilisateur lise le message
            header('Refresh: 3; URL=index.php');
            $must_change = false; // Pour masquer le formulaire
        } catch (PDOException $e) {
            $message = "Erreur lors de la mise à jour du mot de passe.";
            $class = 'error';
        }
    }
}

// Nous utilisons un header/footer simple pour cette page
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changement de Mot de Passe</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .change-box { width: 100%; max-width: 500px; margin: 50px auto; padding: 40px; background-color: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
        .change-box h1 { color: var(--error-color); }
    </style>
</head>
<body>
    <div class="change-box">
        <?php if ($must_change): ?>
            <h1>🔒 Changement de Mot de Passe Obligatoire</h1>
            <p style="color: var(--error-color); font-weight: 700;">Pour des raisons de sécurité, vous devez définir un nouveau mot de passe.</p>
            
            <?php if (isset($message)): ?>
                <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="new_password" style="text-align: left; display: block;">Nouveau Mot de Passe :</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">

                <label for="confirm_password" style="text-align: left; display: block;">Confirmer le Mot de Passe :</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">

                <button type="submit" style="margin-top: 10px; width: 100%;">Mettre à Jour et Continuer</button>
            </form>
        <?php else: ?>
             <div class="message success">
                <?php echo htmlspecialchars($message); ?>
                <p>Si la redirection n'est pas automatique, cliquez <a href="index.php">ici</a>.</p>
             </div>
        <?php endif; ?>
    </div>
</body>
</html>