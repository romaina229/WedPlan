# Utiliser l'image PHP 8.2 avec Apache
FROM php:8.2-apache

# Variables d'environnement
ENV DB_HOST=${DB_HOST:-localhost}
ENV DB_USER=${DB_USER:-root}
ENV DB_PASS=${DB_PASS:-}
ENV DB_NAME=${DB_NAME:-wedding}
ENV APP_URL=${APP_URL:-https://wedplan.onrender.com}
ENV APP_ENV=${APP_ENV:-production}

# Installation des dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Activer les modules Apache
RUN a2enmod rewrite headers

# Configurer Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copier les fichiers du projet
COPY . /var/www/html/

# CRÉATION DES DOSSIERS AVEC GESTION D'ERREUR
RUN mkdir -p /var/www/html/logs \
    /var/www/html/uploads \
    /var/www/html/backups \
    /var/www/html/exports \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    || echo "⚠️  Certaines opérations de permission ont échoué, mais le build continue"

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installer les dépendances PHP si composer.json existe
RUN if [ -f "/var/www/html/composer.json" ]; then \
    cd /var/www/html && composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Exposer le port 80
EXPOSE 80

# Script d'entrée simplifié
RUN echo '#!/bin/bash\n\
# Démarrer Apache\n\
echo "🚀 Démarrage de WedPlan..."\n\
apache2-foreground' > /docker-entrypoint.sh \
    && chmod +x /docker-entrypoint.sh

ENTRYPOINT ["/docker-entrypoint.sh"]