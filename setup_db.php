<?php
// Fichier : setup_db.php
require 'connection.php';

// Commandes SQL pour créer les tables (simplifiées sans professeur)
$sql = "
-- Table pour les CLASSES
CREATE TABLE IF NOT EXISTS classes (
    classe_id INT AUTO_INCREMENT PRIMARY KEY,
    nom_classe VARCHAR(50) NOT NULL UNIQUE,
    niveau VARCHAR(50),
    annee_scolaire YEAR NOT NULL
);

-- Table pour les ELEVES (liés à une classe)
CREATE TABLE IF NOT EXISTS eleves (
    eleve_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    classe_id INT,
    FOREIGN KEY (classe_id) REFERENCES classes(classe_id) ON DELETE SET NULL
);

-- Table pour les ABSENCES
CREATE TABLE IF NOT EXISTS absences (
    absence_id INT AUTO_INCREMENT PRIMARY KEY,
    eleve_id INT NOT NULL,
    date_absence DATE NOT NULL,
    justifie BOOLEAN DEFAULT FALSE,
    raison TEXT,
    date_enregistrement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (eleve_id) REFERENCES eleves(eleve_id) ON DELETE CASCADE
);
";

try {
    $pdo->exec($sql);
    echo "<h1>✅ Base de données initialisée avec succès.</h1>";
    echo "<p>Les tables 'classes', 'eleves', et 'absences' sont prêtes.</p>";
    echo "<p><a href='index.php'>Aller à la page d'accueil.</a></p>";
} catch (PDOException $e) {
    echo "<h1>❌ ERREUR LORS DE LA CRÉATION DES TABLES</h1>";
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
}
?>