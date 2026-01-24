SIS AMA - Système d'Information Scolaire
Le SIS AMA est une application web de gestion scolaire développée en PHP pour l'Association Musulmane Audomaroise. Elle permet de centraliser la gestion des élèves, des classes, des absences, des notes et des paiements de scolarité.

🚀 Fonctionnalités principales
Gestion des Élèves & Classes : Inscription des élèves, attribution aux classes et stockage des coordonnées des parents.

Suivi des Absences : Saisie journalière des absences par matière et génération de rapports statistiques avec graphiques.

Gestion des Notes & Bulletins : Saisie des notes par contrôle, calcul automatique des moyennes et génération de bulletins scolaires au format PDF stylisé.

Suivi des Paiements : Monitoring des frais de scolarité pour savoir quel élève est à jour ou en situation d'impayé.

Administration & Sécurité : Système de comptes avec rôles (Admin/Personnel) et gestion fine des permissions d'accès aux pages.

Outils d'Import/Export : Importation massive d'élèves via fichiers CSV et export de feuilles d'appel prêtes à l'impression.

🛠️ Installation
Prérequis
Un serveur local (XAMPP, WAMP) ou un VPS avec PHP 7.4+ et MySQL/MariaDB.

L'extension PDO activée pour la base de données.

Étapes
Cloner le projet :

Bash

git clone https://github.com/Teamfliyou/ASSO-AMA-sis-.git
Configurer la base de données :

Créez une base de données nommée ama_sis_db.

Modifiez le fichier connection.php avec vos identifiants (host, user, password).

Initialiser les tables :

Lancez votre navigateur et accédez à votre-site/setup_db.php pour créer automatiquement les tables nécessaires.

Connexion par défaut :

Identifiant : admin (à configurer lors de la création du premier compte dans la base).

📂 Structure du Projet
/assets : Contient le CSS, le JavaScript (Chart.js) et les images/logos.

/includes : Fichiers réutilisables comme le header et le footer.

eleves.php, classes.php : Gestion des ressources principales.

bulletin_pdf.php : Moteur de génération des bulletins.

security.php : Cœur de la gestion des sessions et des droits d'accès.

🔒 Sécurité
Le projet inclut une protection contre les accès non autorisés. Les mots de passe sont hachés en SHA-256 et le système impose un changement de mot de passe lors de la première connexion pour les nouveaux utilisateurs.

Développé par : Zayd fliyou pour l'ASSO AMA.
