# Symfony CDA
Cet exercice m'a permis de mettre en place une architecture Symfony professionnelle pour un projet de gestion de contenu (CDA) avec des fonctionnalités avancées telles que l'authentification par Google/Github, l'audit de sécurité, l'analyse qualité, les tests automatisés (unitaire, fonctionnel, end-to-end, performance) et le déploiement continu.

## 📋 Prérequis
- **PHP 8.2** ou supérieur
- **Composer** pour la gestion des dépendances
- **Docker** pour la conteneurisation
- **MySQL** pour la base de données
- **GitHub** pour le contrôle de version et l'intégration continue
- **Make** pour automatiser les tâches

## 🚀 Fonctionnalités
- **Authentification robuste** : Utilisation d'un client OAuth pour pouvoir se connecter à l'application avec un compte Google/Github ou un compte classique.
- **Sécurité renforcée** : Utilisation de REGEX pour valider les entrées utilisateur et éviter les injections SQL.
- **Application dockeriser** : Facilite le déploiement et la gestion des environnements de développement, test et production.

## 📂 Structure du Projet
Le projet est organisé de manière modulaire pour faciliter la maintenance et l'évolutivité :
- **src/** : Contient le code source de l'application.
- **tests/** : Contient les tests unitaires et fonctionnels.
- **config/** : Contient les configurations de l'application.
- **public/** : Point d'entrée de l'application (index.php).
- **var/** : Contient les fichiers temporaires, logs et cache.
- **vendor/** : Contient les dépendances gérées par Composer.
- **.github/workflows/** : Contient les workflows GitHub Actions pour l'intégration continue et le déploiement continu (CI/CD).
- **Makefile** : Contient les commandes pour automatiser les tâches courantes (tests, audit, qualité, etc.).
- **composer.json** : Gère les dépendances PHP du projet.
- **README.md** : Documentation du projet.
- **LICENSE** : Licence du projet.
