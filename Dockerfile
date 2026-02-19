# Utiliser l'image PHP 8.2 avec Apache
FROM php:8.2-apache

# Variables d'environnement
ENV DB_HOST=${DB_HOST:-localhost}
ENV DB_USER=${DB_USER:-root}
ENV DB_PASS=${DB_PASS:-}
ENV DB_NAME=${DB_NAME:-wedding}
ENV APP_URL=${APP_URL:-https://wedplan.onrender.com}
ENV APP_ENV=${APP_ENV:-production}
ENV MYSQL_PORT=${MYSQL_PORT:-3306}

# Installation des dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    mariadb-client \
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

# Créer les dossiers nécessaires avec les bonnes permissions
RUN mkdir -p /var/www/html/logs \
    /var/www/html/uploads \
    /var/www/html/backups \
    /var/www/html/exports \
    && chmod -R 755 /var/www/html

# Copier les fichiers du projet
COPY . /var/www/html/

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installer les dépendances PHP si composer.json existe
RUN if [ -f "/var/www/html/composer.json" ]; then \
    cd /var/www/html && composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Définir les permissions finales
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/logs \
    && chmod -R 755 /var/www/html/uploads \
    && chmod -R 755 /var/www/html/backups \
    && chmod -R 755 /var/www/html/exports

# Exposer le port 80
EXPOSE 80

# Script d'entrée avec importation automatique de la DB
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "🔧 Configuration de WedPlan..."\n\
\n\
# Générer le fichier de configuration\n\
cat > /var/www/html/config.php << EOF\n\
<?php\n\
// Configuration générée automatiquement\n\
define("DB_HOST", "${DB_HOST}");\n\
define("DB_USER", "${DB_USER}");\n\
define("DB_PASS", "${DB_PASS}");\n\
define("DB_NAME", "${DB_NAME}");\n\
define("DB_PORT", "${MYSQL_PORT}");\n\
define("APP_URL", "${APP_URL}");\n\
define("APP_ENV", "${APP_ENV}");\n\
define("SESSION_SECRET", "${SESSION_SECRET:-$(openssl rand -hex 32)}");\n\
define("APP_CURRENCY", "FCFA");\n\
define("APP_TIMEZONE", "Africa/Porto-Novo");\n\
\n\
// Configuration des parrains\n\
define("SPONSOR_SESSION_KEY", "wedding_sponsor_logged_in");\n\
define("SPONSOR_ID_KEY", "sponsor_id");\n\
define("SPONSOR_WEDDING_ID_KEY", "sponsor_wedding_dates_id");\n\
define("SPONSOR_NAME_KEY", "sponsor_name");\n\
define("SPONSOR_ROLE_KEY", "sponsor_role");\n\
?>\n\
EOF\n\
\n\
# Attendre que MySQL soit prêt\n\
echo "⏳ Attente de la base de données..."\n\
timeout=60\n\
while ! mysqladmin ping -h"${DB_HOST}" -P"${MYSQL_PORT}" -u"${DB_USER}" -p"${DB_PASS}" --silent; do\n\
    sleep 2\n\
    timeout=$((timeout-2))\n\
    if [ $timeout -le 0 ]; then\n\
        echo "❌ Délai d\'attente dépassé pour la base de données"\n\
        break\n\
    fi\n\
    echo "   En attente... (${timeout}s restantes)"\n\
done\n\
\n\
# Vérifier si la base de données est vide et importer le SQL\n\
if [ -f "/var/www/html/includes/database.sql" ]; then\n\
    echo "📦 Vérification de la base de données..."\n\
    \n\
    # Vérifier si des tables existent\n\
    TABLE_COUNT=$(mysql -h"${DB_HOST}" -P"${MYSQL_PORT}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e "SHOW TABLES;" 2>/dev/null | wc -l)\n\
    \n\
    if [ "$TABLE_COUNT" -le 1 ]; then\n\
        echo "🗄️  Base de données vide - Importation de database.sql..."\n\
        mysql -h"${DB_HOST}" -P"${MYSQL_PORT}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < /var/www/html/includes/database.sql\n\
        if [ $? -eq 0 ]; then\n\
            echo "✅ Importation réussie !"\n\
            \n\
            # Créer un fichier de marqueur pour éviter la réimportation\n\
            mysql -h"${DB_HOST}" -P"${MYSQL_PORT}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e "\n\
                CREATE TABLE IF NOT EXISTS db_version (\n\
                    id INT PRIMARY KEY AUTO_INCREMENT,\n\
                    version VARCHAR(50),\n\
                    imported_at DATETIME DEFAULT CURRENT_TIMESTAMP\n\
                );\n\
                INSERT INTO db_version (version) VALUES (\x271.0.0\x27);\n\
            "\n\
        else\n\
            echo "❌ Erreur lors de l\'importation"\n\
        fi\n\
    else\n\
        echo "✅ Base de données déjà initialisée ($TABLE_COUNT tables trouvées)"\n\
    fi\n\
else\n\
    echo "⚠️  Fichier database.sql non trouvé dans includes/"\n\
fi\n\
\n\
# Démarrer Apache\n\
echo "🚀 Démarrage de WedPlan..."\n\
apache2-foreground' > /docker-entrypoint.sh \
    && chmod +x /docker-entrypoint.sh

ENTRYPOINT ["/docker-entrypoint.sh"]