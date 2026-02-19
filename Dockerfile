# Utiliser l'image PHP 8.2 avec Apache
FROM php:8.2-apache

# Variables d'environnement par défaut (seront écrasées par Render)
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
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Activer les modules Apache
RUN a2enmod rewrite headers

# Configurer Apache pour pointer vers le dossier public
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Créer les dossiers nécessaires
RUN mkdir -p /var/www/html/backups \
    /var/www/html/exports \
    /var/www/html/logs \
    /var/www/html/uploads \
    && chmod -R 755 /var/www/html

# Copier les fichiers du projet
COPY . /var/www/html/

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/logs /var/www/html/uploads \
    && chmod -R 755 /var/www/html/backups /var/www/html/exports

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installer les dépendances PHP si composer.json existe
RUN if [ -f "/var/www/html/composer.json" ]; then \
    cd /var/www/html && composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Exposer le port 80
EXPOSE 80

# Script d'entrée pour configurer l'environnement
RUN echo '#!/bin/bash\n\
# Remplacer les variables dans config.php si nécessaire\n\
echo "<?php" > /var/www/html/config.php\n\
echo "// Configuration générée automatiquement" >> /var/www/html/config.php\n\
echo "define(\x27DB_HOST\x27, \x27${DB_HOST}\x27);" >> /var/www/html/config.php\n\
echo "define(\x27DB_USER\x27, \x27${DB_USER}\x27);" >> /var/www/html/config.php\n\
echo "define(\x27DB_PASS\x27, \x27${DB_PASS}\x27);" >> /var/www/html/config.php\n\
echo "define(\x27DB_NAME\x27, \x27${DB_NAME}\x27);" >> /var/www/html/config.php\n\
echo "define(\x27APP_URL\x27, \x27${APP_URL}\x27);" >> /var/www/html/config.php\n\
echo "define(\x27APP_ENV\x27, \x27${APP_ENV}\x27);" >> /var/www/html/config.php\n\
echo "?>" >> /var/www/html/config.php\n\
\n\
# Démarrer Apache\n\
apache2-foreground' > /docker-entrypoint.sh \
    && chmod +x /docker-entrypoint.sh

ENTRYPOINT ["/docker-entrypoint.sh"]