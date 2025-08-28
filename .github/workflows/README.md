# ⚙️ Intégration Continue & Déploiement Continu (CI/CD)

Le projet utilise GitHub Actions pour automatiser l'audit de sécurité, l'analyse qualité, les tests et le déploiement sur différents environnements.

### 🔄 Branches et Pipelines

- **dev** : Chaque push ou pull request déclenche le pipeline `CI Dev Pipeline` qui :
    - Exécute l'audit de sécurité, l'analyse qualité et les tests.
    - Si tout est vert, fusionne automatiquement `dev` dans `test`.

- **test** : Chaque push déclenche le pipeline `CI Test Pipeline` qui :
    - Exécute l'audit de sécurité, l'analyse qualité et les tests.
    - Crée automatiquement une **PR (Pull Request)** de `test` vers `main` pour préparer le déploiement en production (a accepté manuellement).

- **main** : Chaque push déclenche le pipeline `CI Main Pipeline` qui :
    - Exécute l'audit de sécurité.
    - Déploie automatiquement en production via SSH si l'audit est validé.

### 🛡️ Audit de sécurité

- Utilise `composer audit` pour détecter les vulnérabilités sur les dépendances PHP.
- Le rapport est généré et uploadé comme artefact.

### 🧪 Tests & Qualité

- Les tests sont lancés avec `make run-tests` sur une base MySQL dédiée avec les tâches suivantes.
  - Suppression de la base de données existante
  - Création de la base de données
  - Exécuté les migrations
  - Charger les fixtures
  - Vide le cache
  - Lance les tests avec PHPUnit
- L'analyse qualité s'effectue via la commande `make quality-check` avec les actions suivantes :
  - Vérification et fixe du code avec ECS (Easy Coding Standard)
  - Vérification et fixe du code avec Rector
  - Exécuter le linter pour les fichiers yaml, twig et conteneur.
  - Vérifier les typages grâce à PHPStan (au niveau max)

### 🚀 Déploiement

- **Production** : Déploiement automatisé sur le serveur de production après validation sur `main`.

### 🔑 Sécurité

- Les secrets (SSH, tokens) sont gérés via les secrets GitHub.

Pour plus de détails, consultez les fichiers dans `.github/workflows/`.
