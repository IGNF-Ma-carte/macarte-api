#!/bin/bash
set -e

#-------------------------------------------------------------
# Création et mise à jour de la structure de base de données
# (TODO : adapter pour production)
#-------------------------------------------------------------
#php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force
#php bin/console doctrine:migration:migrate --no-interaction -e prod

# TODO : adapter pour production (secret à injecter à l'exécution
# sinon, clé privée spécifique à chaque instance)
# php bin/console lexik:jwt:generate-keypair

#-------------------------------------------------------------
# Démarrage de apache
#-------------------------------------------------------------
/usr/sbin/apachectl -D FOREGROUND
