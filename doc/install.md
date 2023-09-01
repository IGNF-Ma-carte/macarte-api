# Installation Ma carte API

## Pré-requis

* PHP 7.4+
* PHP composer
* PostgreSQL
* nodejs 16+ / npm 8+

## Paramétrer le serveur Apache

Ajouter dans la définition de l'alias

```
<Directory "D:\...\mon-projet-api\public">
...
    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
...
</Directory>

Alias /mon-projet-api "D:\...\mon-projet\public"
```

## Télécharger le projet

```bash
git clone https://github.com/IGNF-Ma-carte/macarte-api.git
```

## Paramétrer l'application (.env)

```
# Connexion à la BdD
DATABASE_URL=postgresql://{db_user}:{db_password}@{127.0.0.1:5432}/{db_name}?serverVersion={13}&charset=utf8

# Chemin vers le dossier de stockage S3 des fichiers (images et json des données des cartes)
FILE_DIR=/path/to/dir

# url de l'accueil (le S du https n'apparait pas tout le temps avec les fonctionnalités symfony)
MACARTE_SERVER=https://macarte-qualif.ign.fr

# adresse du proxy
PROXY=http://username:password@proxy.example.com:1234

# adresse du serveur de mail
MAILER_DSN=smtp://...

# identifiant de l'utilisateur qui stocke les images de l'éditorial
USER_EDITOR_DEFAULT_ID=123

# identifiant piwik
# si PIWIK_ID == 0, le site n'inclut pas piwik (à utiliser en dev ou qualif)
PIWIK_ID=xxx

# autres parametres
CONTACT_EMAIL=contact_a_definir@ign.fr 
GPP_KEY=0gd4sx9gxx6ves3hf3hfeyhw
EDUGEO_KEY=1mgehldv90vifl6s5ksf900i

# url du CAS Lumni et de validation
LUMNI_CAS=sso-enseignants-preprod.lumni.fr/auth/realms/lumni/protocol/cas/
LUMNI_CAS_VALIDATION=https://sso-enseignants.lumni.fr/auth/realms/lumni/protocol/cas/serviceValidate?

GAR_CAS=idp-auth.partenaire.test-gar.education.fr # sans http://
GAR_CAS_VALIDATION=https://idp-auth.partenaire.test-gar.education.fr/p3/serviceValidate?

##################
# DEV UNIQUEMENT # 
##################
# environnement du projet
APP_ENV=<dev|prod>

# durée de validité des tokens JWT en secondes (défaut : 5 min = 300 sec dans .env versionné)
# si besoin de validité longue, JWT_TOKEN_TTL = 86400 (24h)
# si besoin de validité courte, JWT_TOKEN_TTL = 10
JWT_TOKEN_TTL=86400 

MAILER_DSN=smtp://user:pass@smtp.example.com:port
```

NB : 
Le stockage des images est lié à un utilisateur et est limité en taille (par défaut, 10Mo pour un utilisateur normal et 100Mo pour un admin)
Le nombre d'images peut être important dans le cadre de l'éditorial, et les éditorialistes changer au cours du temps, toutes les images stockées depuis la partie Administration/Editorial stockera les images avec l'utilisateur d'identifiant USER_EDITOR_DEFAULT_ID et ne sera pas limité en taille

## Commandes d'installation

### Installer les dépendances symfony

```bash
composer install
php bin/console assets:install
```

### Mettre à jour la charte graphique (charte-bundle)

```bash
php bin/console ign:charte:update-content 'bundles/igncharte/json/followers.json' '/generated/megamenu.html'
```

### Générer les clés publiques/privées de Lexik JWT Authentication Bundle

```bash
php bin/console lexik:jwt:generate-keypair
```

### Base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console app:user:add <username> <email> <password>
php bin/console app:user:promote <username> <ROLE_>
```

Une fois la base créée, ajoutez les extension à **PostGres** `unaccent` et `fuzzystrmatch` avec les commandes :

```SQL
CREATE EXTENSION unaccent
CREATE EXTENSION fuzzystrmatch
```
