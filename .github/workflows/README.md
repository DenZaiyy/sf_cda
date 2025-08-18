# ⚙️ Intégration Continue & Déploiement Continu (CI/CD)

Le projet utilise GitHub Actions pour automatiser l'audit de sécurité, l'analyse qualité, les tests et le déploiement sur différents environnements.

### 🔄 Branches et Pipelines

- **dev** : Chaque push ou pull request déclenche le pipeline `CI Dev Pipeline` qui :
    - Exécute l'audit de sécurité, l'analyse qualité et les tests.
    - Si tout est vert, fusionne automatiquement `dev` dans `test`.

- **test** : Chaque push déclenche le pipeline `CI Test Pipeline` qui :
    - Exécute l'audit de sécurité, l'analyse qualité et les tests.
    - Crée automatiquement une Pull Request de `test` vers `main` pour préparer le déploiement en production.

- **main** : Chaque push déclenche le pipeline `CI Master Pipeline` qui :
    - Exécute l'audit de sécurité, l'analyse qualité et les tests.
    - Déploie automatiquement en production via SSH si tout est valide.

### 🛡️ Audit de sécurité

- Utilise `composer audit` pour détecter les vulnérabilités sur les dépendances PHP.
- Le rapport est généré et uploadé comme artefact.

### 🧪 Tests & Qualité

- Les tests sont lancés avec `make run-tests` sur une base MySQL dédiée.
- L'analyse qualité s'effectue via la commande `make quality-check`.

### 🚀 Déploiement

- **Production** : Déploiement automatisé sur le serveur de production après validation sur `master`.
- **Test** : (Commenté, mais prêt à l'emploi) Déploiement possible sur un environnement de test.

### 🔑 Sécurité

- Les secrets (SSH, tokens) sont gérés via les secrets GitHub.

Pour plus de détails, consultez les fichiers dans `.github/workflows/`.
