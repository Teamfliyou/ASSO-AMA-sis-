<?php

/**
 * Nettoie et normalise une chaîne de caractères (ex: nom, prénom).
 * Supprime les espaces superflus et met en majuscules la première lettre de chaque mot.
 *
 * @param string $name La chaîne à nettoyer.
 * @return string La chaîne normalisée.
 */
function normalizeName($name) {
    return ucwords(strtolower(trim($name)));
}

/**
 * Génère un nom d'utilisateur unique à partir d'un prénom et d'un nom.
 * Ex: "John Doe" -> "jdoe".
 * Une logique pour vérifier l'unicité en base de données serait nécessaire ici.
 *
 * @param string $firstName Le prénom.
 * @param string $lastName Le nom de famille.
 * @return string Le nom d'utilisateur suggéré.
 */
function generate_username($firstName, $lastName) {
    $firstName = strtolower(substr($firstName, 0, 1));
    $lastName = strtolower(preg_replace('/[^a-z]/', '', $lastName));
    return $firstName . $lastName;
}

/**
 * Hache un mot de passe en utilisant l'algorithme sécurisé de PHP.
 *
 * @param string $password Le mot de passe en clair.
 * @return string Le mot de passe haché.
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Vérifie qu'un mot de passe en clair correspond à un hachage.
 *
 * @param string $password Le mot de passe en clair.
 * @param string $hash Le hachage à vérifier.
 * @return bool True si le mot de passe est correct, false sinon.
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Affiche un message de session (succès ou erreur) puis le supprime.
 * Doit être appelé après session_start().
 */
function display_session_message() {
    if (isset($_SESSION['message'])) {
        echo '<div class="session-message">' . htmlspecialchars($_SESSION['message']) . '</div>';
        unset($_SESSION['message']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="session-message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
}

?>