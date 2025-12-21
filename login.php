<?php
// Fichier : login.php
require 'connection.php';
session_start();

$message = '';
$class = '';
$logo_filename = 'logo_ama.png'; // Assurez-vous que ce fichier existe dans assets/images/

// Si l'utilisateur est déjà connecté, le rediriger vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $hashed_input_password = hash('sha256', $password);

        try {
            $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role, is_active, must_change_password FROM utilisateurs WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['password_hash'] === $hashed_input_password) {
                if ($user['is_active']) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($user['must_change_password']) {
                        header('Location: change_password.php');
                        exit;
                    }
                    
                    $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
                    unset($_SESSION['redirect_url']);
                    header('Location: ' . $redirect_url);
                    exit;

                } else {
                    $message = "Votre compte n'est pas actif.";
                }
            } else {
                $message = "Identifiant ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $message = "Erreur de connexion à la base de données.";
        }
    } else {
        $message = "Veuillez entrer votre identifiant et votre mot de passe.";
    }
    $class = 'error';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SIS AMA</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { 
            background-color: var(--background-color); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            margin: 0;
        }
        .login-box { 
            width: 100%; 
            max-width: 400px; 
            padding: 40px; 
            background-color: white; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); 
            text-align: center;
            border: 1px solid var(--border-color);
        }
        .logo-img { 
            height: 70px; 
            width: 70px; 
            border-radius: 50%; 
            object-fit: cover; 
            margin-bottom: 25px; 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); 
            border: 3px solid white; 
        }
        .login-box input {
            width: 100%; 
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: 20px;
            box-sizing: border-box;
        }
        .login-box button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            width: 100%;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <img src="assets/images/<?php echo $logo_filename; ?>" alt="Logo" class="logo-img">
        <h1 style="color: var(--primary-color); font-weight: 300;">Connexion au SIS</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="username" style="text-align: left; display: block; margin-bottom: 5px;">Identifiant :</label>
            <input type="text" id="username" name="username" required autocomplete="username" value="<?php echo htmlspecialchars($username ?? ''); ?>">

            <label for="password" style="text-align: left; display: block; margin-bottom: 5px;">Mot de passe :</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <button type="submit">Se Connecter</button>
        </form>
    </div>
</body>
</html>