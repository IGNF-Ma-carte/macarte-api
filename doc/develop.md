# Développement Ma carte

## Assets 

### Environnement

Créer un fichier .env.local et surchargez les paramètre du serveur avec vos valeurs :
```bash
DATABASE_URL="mysql://db_user:db_password@localhost:3306/db_name"

PROXY=http://monproxy.fr:0000
MACARTE_SERVER=https://localhost/macarte-api
EDUGEO_SERVER=https://localhost/macarte-api/edugeo
CONTACT_EMAIL=to@bedefin.ed
GPP_KEY=votre_cle_geoportail
EDUGEO_KEY=votre_cle_geoportail_edugeo
```

### Installer les dépendances node

```bash
npm install
```

### Appliquer les modifications des assets

```bash
npm run <dev|prod|watch>
```

ATTENTION : il peut y avoir des interactions entre charte-bundle et le package mcutils 

## Mettre à jour les routes visibles (FosJsRouting)

```bash
php bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json
```

### Mettre à jour la documentation de l'API

```bash
./vendor/bin/openapi --format yaml --output ./public/swagger/swagger.yaml ./swagger/swagger.php src
```

## Commit 

Avant le commit, lancer les commandes : 

```bash
./vendor/bin/openapi --format yaml --output ./public/swagger/swagger.yaml ./swagger/swagger.php src
npm run prod
php bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json
```

Les fichiers générés sont versionnés pour faciliter le déploiement

## Librairies installées

### npm

- sweetalert2 : [https://sweetalert2.github.io/ ](https://sweetalert2.github.io/ )
- mcutils : https://github.com/IGNF-Ma-carte/mcutils

### composer

- Admin
    - [friendsofsymfony/jsrouting-bundle](https://github.com/FriendsOfSymfony/FOSJsRoutingBundle) (accéder aux @Route avec javascript)
    - [ign/charte-bundle](http://gitlab.dockerforge.ign.fr/ign/charte-bundle) (charte IGN des sites internet)
    - [gregwar/captcha-bundle](https://github.com/Gregwar/CaptchaBundle) (affichage du captcha dans les formulaires)
- Api
    - [lexik/jwt-authentication-bundle](https://github.com/lexik/LexikJWTAuthenticationBundle) (token JWT)
    - [gesdinet/jwt-refresh-token-bundle](https://github.com/markitosgv/JWTRefreshTokenBundle) (refresh_token)
    - [nelmio/cors-bundle](https://github.com/nelmio/NelmioCorsBundle) (gestion des CORS)
    - [zircote/swagger-php](https://github.com/zircote/swagger-php) (documentation de l'API)

## API

Respecter les bonnes pratiques énumérées notamment dans [https://dev-env.sai-experimental.ign.fr/guidelines/api-restfull](https://dev-env.sai-experimental.ign.fr/guidelines/api-restfull)

### Documentation

Doc officielle : [https://swagger.io/docs/specification/about/](https://swagger.io/docs/specification/about/)

Tuto : [https://grafikart.fr/tutoriels/swagger-openapi-php-1160](https://grafikart.fr/tutoriels/swagger-openapi-php-1160)

Bundle : [https://github.com/zircote/swagger-php](https://github.com/zircote/swagger-php)

Documentation du bundle : [https://zircote.github.io/swagger-php/](https://zircote.github.io/swagger-php/)

Tester le swagger.json généré : [https://editor.swagger.io/](https://editor.swagger.io/)

La doc s'affiche sur la page ```/api```

Lancer la commande pour générer le fichier swagger.json affiché dans la page

```cmd
./vendor/bin/openApi --format yaml --output ./public/swagger/swagger.yaml ./swagger/swagger.php src
```

### Modification de l'API

- La documentation des Requests et Responses est documentées dans les controllers
- Afin de laisser les entities propres, les Schemas (représentation des entités) sont dans les annotations des Normalizers ```./src/Seriliazer/Normalizer```. Ceux-ci servent à transformer un objet en tableau, et à encoder ce tableau en json, ledit json est à envoyer dans  les Responses
- Vérifier tous les paramètres envoyés dans la Request et renvoyer une erreur 4xx si un paramètre est non-conforme. La response doit avoir le format json ```{ "code" : 4xx, "message" : "xxx"}```

```php
// ApiXxxController
return $this->returnResponse($code, $message);
```

- Ne pas oublier d'ajouter ```Content-Type``` dans le header quand la réponse est un json

```php
return new Response($json, Response::HTTP_OK, array(
    'Content-Type' => 'application/json',
));
```

La durée de vie du token par défaut est très courte (5 min) et est définie dans le fichier ```.env```

Pour allonger ou réduire la durée de validité du token JWT et faciliter le développement, ajouter : 

```
#.env.local

# 1 journée
JWT_TOKEN_TTL=86400

# 10 secondes
JWT_TOKEN_TTL=10
```
