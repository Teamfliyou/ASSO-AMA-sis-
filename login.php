<?php
// Fichier : login.php
require 'connection.php';
session_start();

$message = '';
$class = '';
$logo_filename = 'logo_ama.png'; // Remplacez par votre vrai nom de fichier

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

// Nous utilisons un header/footer minimaliste pour la page de connexion
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
        /* Styles de la page de connexion */
        body { 
            background-color: var(--background-color); /* Utiliser le fond aéré du site */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
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
        .login-box h1 { 
            color: var(--primary-color); 
            margin-top: 0; 
            font-weight: 300;
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
        /* Adapter les champs pour ne pas être 100% de la largeur du .login-box padding inclus */
        .login-box input[type="text"], .login-box input[type="password"] {
            width: calc(100% - 20px); 
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: 15px;
            transition: border-color 0.3s;
        }
        .login-box button {
            background-color: var(--primary-color);
            padding: 12px 20px;
            width: 100%;
        }
        /* Réduire la marge du label si nécessaire */
        .login-box label {
             margin-top: 10px;
        }
        
    </style>
</head>
<body>
    <div class="login-box">
        <img src="assets/images/<?php echo $logo_filename; ?>" alt="Logo" class="logo-img">
        <h1>Connexion au SIS</h1>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="username" style="text-align: left; display: block;">Identifiant :</label>
            <input type="text" id="username" name="username" required>

            <label for="password" style="text-align: left; display: block;">Mot de passe :</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" style="margin-top: 10px;">Se Connecter</button>
        </form>
    </div>
</body>
</html>