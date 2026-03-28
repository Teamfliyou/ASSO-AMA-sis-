# Utiliser l'image officielle PHP avec Apache
FROM php:8.2-apache

# Activer le module rewrite d'Apache (utile pour ton .htaccess)
RUN a2enmod rewrite

# Installer l'extension PHP PDO MySQL nécessaire pour ton application
RUN docker-php-ext-install pdo pdo_mysql

# Copier tous les fichiers du projet dans le dossier web du conteneur
COPY . /var/www/html/

# Donner les bons droits à Apache sur les fichiers
RUN chown -R www-data:www-data /var/www/html/