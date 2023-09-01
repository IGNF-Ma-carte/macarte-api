FROM php:8.1-apache

# Ajout de https://github.com/mlocati/docker-php-extension-installer pour simplifier
# l'installation des extensions PHP (à qualifier niveau sécurité)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_pgsql intl gd zip

RUN apt-get update \
 && apt-get install -y git nodejs npm \
 && rm -rf /var/lib/apt/lists/*

# Installation de PHP composer
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls &&\
    mv composer.phar /usr/local/bin/composer

# custom php.ini
#COPY .docker/php-uploads.ini /usr/lcoal/etc/php/conf.d/php-uploads.ini
#RUN pear config-set php_ini /usr/lcoal/etc/php/conf.d/php-uploads.ini

# Configuration de apache
COPY .docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/apache-security.conf /etc/apache2/conf-enabled/security.conf
COPY .docker/apache-ports.conf /etc/apache2/ports.conf

# TODO : headers - confirmer qu'il est nécessaire d'ajouter l'entête CORS
# (non traité côté PHP avec nelmio/cors-bundle?)
RUN a2enmod rewrite remoteip headers

# TODO : Un conteneur = un processus, plusieurs processus = plusieurs conteneurs
# Install Cron
# RUN apt-get -y install cron
# Add the cron job
# RUN crontab -l | { cat; echo "0 0 * * * php bin/console ign:charte:update-content 'bundles/igncharte/json/followers.json' '/generated/megamenu.html'"; } | crontab -
# RUN crontab -l | { cat; echo "0 4 * * * php bin/console gesdinet:jwt:clear"; } | crontab -

# Installation de l'application
COPY --chown=www-data:www-data . /opt/macarte-api
WORKDIR /opt/macarte-api
RUN chmod +x .docker/application.sh bin/console
USER www-data

## installation des dépendances et génération des fichiers
ENV COMPOSER_HOME=/tmp/composer
RUN rm -rf /tmp/composer
# TODO : nettoyage avec && rm -rf /tmp/composer
RUN composer config --global gitlab-token.gitlab.gpf-tech.ign.fr "${gitlab_read_token}"
RUN composer install
RUN php bin/console ign:charte:update-content 'bundles/igncharte/json/followers.json' '/generated/megamenu.html'

# TODO : déclarer les volumes
# (i.e. dossier de données à sortir du conteneur)
# (ex : https://github.com/IGNF/validator-api/blob/ac79b7e1d2c3319c12b58eef63810c83c06b1b34/.docker/Dockerfile#L105-L110 )

#----------------------------------------------------------------------
# Prepare data storage
# (Note that /opt/macarte_api/var/data can be shared between containers)
#----------------------------------------------------------------------
RUN mkdir -p /opt/macarte-api/var/data/jwt
RUN mkdir -p /opt/macarte-api/var/data/files
VOLUME /opt/macarte-api/var/data

CMD ["/opt/macarte-api/.docker/application.sh"]
EXPOSE 8000
