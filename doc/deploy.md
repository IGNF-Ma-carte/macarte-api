# DEPLOIEMENT DE MACARTE-API

## Créer l'image docker

- Activer la branche/tag à déployer

```bash
git checkout <branch/tag>
```

- A la racine du projet, lancer la commande 

```bash
docker build . -f ./Dockerfile -t macarte_api
```

- Entrer dans l'image Docker (ligne de commande)

```bash
docker run -it macarte_api bash
```

`exit` pour sortir

### Activer les images (en local)

- Activer l'application et la base de données

```bash
docker compose up -d
```

- Construire et activer l'image

```bash
docker compose build
```

Le site est sur `localhost:8080`

- Stopper l'image

```bash
docker compose stop
```

- Vérifier l'état des images

```bash
docker compose ps
```

- Vérifier les logs de l'image

```bash
docker logs macarte_api-app-1 #nom de l'image
``` 

- Supprimer les images

```bash
docker compose down
```
