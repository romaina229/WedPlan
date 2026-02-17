# 💒 Budget Mariage PJPM — v2.3
> Application PWA de gestion de budget de mariage — Production Ready

---

## 🗂 Structure du projet

```
wedding/
├── index.php               ← Page principale (dashboard + stats + dépenses + paiements)
├── config.php              ← Configuration (BDD, APP_URL, APP_CURRENCY…)
├── AuthManager.php         ← Gestion authentification & sessions
├── ExpenseManager.php      ← Logique métier (dépenses, catégories, stats)
├── install.php             ← Assistant d'installation (1ère utilisation)
├── guide.php               ← Guide interactif mariage
├── database.sql            ← Schéma SQL complet + données par défaut
├── sw.js                   ← Service Worker PWA
├── manifest.json           ← Manifest PWA (icône, thème, standalone)
│
├── api/
│   ├── api.php             ← API REST principale (CRUD dépenses)
│   ├── auth_api.php        ← API authentification (login/logout/register)
│   └── export_api.php      ← API export CSV & PDF
│
├── auth/
│   ├── login.php           ← Page de connexion
│   └── register.php        ← Page d'inscription
│
├── admin/
│   ├── admin.php           ← Tableau de bord admin
│   ├── admin_api.php       ← API administration
│   ├── admin_users.php     ← Gestion utilisateurs
│   ├── admin_logs.php      ← Journaux d'activité
│   ├── admin_backup.php    ← Sauvegardes
│   ├── profile.php         ← Profil utilisateur
│   └── settings.php        ← Paramètres
│
├── includes/
│   ├── header.php          ← En-tête commun (nav, session)
│   └── footer.php          ← Pied de page + scripts PWA
│
├── assets/
│   ├── css/style.css       ← Styles complets (thème violet-or)
│   ├── js/script.js        ← JavaScript principal (CRUD, UI, filtres)
│   ├── js/charts.js        ← Graphiques Chart.js (pie, bar, jauges)
│   └── images/wedding.jpg  ← Logo / icône PWA
│
└── termes/
    ├── terms.php           ← CGU
    ├── privacy.php         ← Politique de confidentialité
    └── legal.php           ← Mentions légales
```

---

## 🚀 Installation

### Prérequis
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.4+
- Apache/Nginx avec mod_rewrite
- Serveur local : XAMPP, Laragon ou WAMP

### Étapes

1. **Copier le projet** dans votre répertoire web :
   ```
   C:/xampp/htdocs/wedding/     (Windows)
   /var/www/html/wedding/       (Linux)
   ```

2. **Créer la base de données** MySQL :
   ```sql
   CREATE DATABASE wedding CHARACTER SET utf8mb4;
   ```

3. **Importer le schéma** :
   ```
   mysql -u root -p wedding < database.sql
   ```
   Ou via phpMyAdmin → Importer → `database.sql`

4. **Configurer** `config.php` :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');          // Votre mot de passe MySQL
   define('DB_NAME', 'wedding');
   define('APP_URL', 'http://localhost/wedding');
   ```

5. **Accéder à l'installation** :
   ```
   http://localhost/wedding/install.php
   ```

6. **Connexion** :
   ```
   http://localhost/wedding/
   ```

---

## ✨ Fonctionnalités

### Tableau de bord
- 4 cartes KPI : Budget total, Payé, Reste, Articles
- Barre de progression globale en temps réel
- Récapitulatif par catégorie avec mini-barres
- Export CSV / PDF depuis le dashboard

### Statistiques & Graphiques
- 🥧 **Camembert** — répartition du budget par catégorie
- 📊 **Barres groupées** — Payé vs Reste par catégorie
- 🎯 **Jauges SVG** — progression individuelle par catégorie
- 📈 **Vue financière globale** — Budget / Payé / Reste superposés

### Gestion des dépenses
- Ajout / Modification / Suppression
- Création de catégorie à la volée
- Calcul automatique : Qté × Prix × Fréquence
- Filtres avancés : catégorie, statut, recherche, montant min/max
- Groupement par catégorie avec sous-totaux

### Paiements
- Liste des éléments payés avec dates
- File d'attente des éléments à payer
- Bascule payé/non payé en un clic

### Export
- **CSV** : tous les articles, payés uniquement, en attente
- **PDF** : rapport complet imprimable avec design professionnel

### Date de mariage
- Compte à rebours dynamique jusqu'au Jour J
- Affichage mois/jours/heures selon la proximité

### PWA
- Installation sur mobile (Add to Home Screen)
- Fonctionne hors ligne (cache Service Worker)
- Icône et splash screen

### Administration
- Gestion des utilisateurs
- Journaux d'activité
- Sauvegardes de la base de données

---

## 🔒 Sécurité
- Mots de passe hashés avec `password_hash()` (bcrypt)
- Sessions sécurisées avec timeout configurable
- Requêtes PDO préparées (protection injection SQL)
- Sanitisation des entrées (`htmlspecialchars`)
- Authentification requise pour toutes les opérations

---

## ⚙️ Configuration avancée

| Constante              | Valeur par défaut          | Description                   |
|------------------------|---------------------------|-------------------------------|
| `APP_URL`              | `http://localhost/wedding` | URL publique de l'application |
| `APP_CURRENCY`         | `FCFA`                    | Devise affichée               |
| `APP_TIMEZONE`         | `Africa/Porto-Novo`       | Fuseau horaire                |
| `SESSION_TIMEOUT`      | `1800`                    | Durée session (secondes)      |
| `MAX_EXPENSES_PER_USER`| `500`                     | Limite dépenses / utilisateur |
| `MAX_CATEGORIES`       | `50`                      | Limite catégories             |

---

## 📱 Compatibilité
- Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- Android 8+, iOS 13+
- Responsive : mobile, tablette, desktop

---

## 📄 Licence
Projet privé — PJPM © 2025. Tous droits réservés.
