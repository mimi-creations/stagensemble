FROM php:8.2-apache

# Installer extensions MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copier les fichiers du projet
COPY . /var/www/html/

# Activer rewrite (optionnel mais utile)
RUN a2enmod rewrite
