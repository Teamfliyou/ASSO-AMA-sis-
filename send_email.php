<?php
require 'security.php';
require 'connection.php';
// Fichier : send_email.php (Formulaire d'Envoi d'E-mail avec Pièce Jointe)
require 'connection.php';

$message = '';
$class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = filter_var($_POST['to'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_STRING);
    $body = filter_var($_POST['body'] ?? '', FILTER_SANITIZE_STRING);
    $from_email = 'no-reply@asso-ama-ecole.fr'; // Remplacez par une adresse valide du domaine

    $file_attached = false;
    $boundary = md5(time()); // Clé unique pour séparer les parties du mail

    // 1. Vérification de l'adresse
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $message = "Erreur : L'adresse e-mail destinataire est invalide.";
        $class = 'error';
    } else {
        // 2. Construction des headers pour le MIME/la pièce jointe
        $headers = "From: " . $from_email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";

        // Début du corps du message
        $final_body = "--" . $boundary . "\r\n";
        $final_body .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
        $final_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $final_body .= $body . "\r\n\r\n";

        // 3. Gestion de la pièce jointe
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file_attached = true;
            $file_name = $_FILES['attachment']['name'];
            $file_tmp = $_FILES['attachment']['tmp_name'];
            $file_type = $_FILES['attachment']['type'];
            
            $file_content = file_get_contents($file_tmp);
            $file_content = chunk_split(base64_encode($file_content));

            $final_body .= "--" . $boundary . "\r\n";
            $final_body .= "Content-Type: " . $file_type . "; name=\"" . $file_name . "\"\r\n";
            $final_body .= "Content-Disposition: attachment; filename=\"" . $file_name . "\"\r\n";
            $final_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $final_body .= $file_content . "\r\n\r\n";
        }

        $final_body .= "--" . $boundary . "--\r\n";

        // 4. Envoi du mail
        if (mail($to, $subject, $final_body, $headers)) {
            $message = "E-mail envoyé avec succès à " . $to . ". (Vérifiez les spams du destinataire).";
            $class = 'success';
        } else {
            $message = "Échec de l'envoi de l'e-mail. Vérifiez la configuration de sendmail sur votre VPS.";
            $class = 'error';
        }
    }
}

require 'includes/header.php';
?>

<div class="container">
    <h1>Envoi d'E-mail Manuel (Test)</h1>
    
    <?php if (isset($message)): ?>
        <div class="message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <h2>Paramètres du Mail</h2>
        
        <p>⚠️ **NOTE CRITIQUE :** L'envoi direct via PHP mail() est non garanti et peut atterrir en spam. Pour un système de production, une configuration SMTP complète (via PHPMailer) serait nécessaire.</p>

        <label for="to">Destinataire (Adresse e-mail) :</label>
        <input type="email" id="to" name="to" required value="<?php echo htmlspecialchars($_POST['to'] ?? ''); ?>">

        <label for="subject">Objet :</label>
        <input type="text" id="subject" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? 'Communication ASSO AMA ECOLE'); ?>">
        
        <label for="body">Corps du Message :</label>
        <textarea id="body" name="body" required style="width: 100%; height: 150px; padding: 10px;"><?php echo htmlspecialchars($_POST['body'] ?? ''); ?></textarea>
        
        <label for="attachment">Joindre un Fichier (Optionnel) :</label>
        <input type="file" id="attachment" name="attachment" style="border: none;">
        
        <button type="submit" style="margin-top: 20px;">Envoyer l'E-mail de Test</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>