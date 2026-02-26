### Modules & navigation
1. Menu du haut dynamique  affiche tous les modules activés et autorisés par rôle.
 2. Un compte ROLE_ADMIN voit tous les modules activés, même si des rôles sont définis.
 3. Les liens des modules sont générés automatiquement (ex module_test).

### Administration des modules

  1. Boutons Activer  Désactiver 
      - Page d’accueil
      - Menu Admin (dropdown)
      - Page adminmodules
  2. ActivationDésactivation persistée dans configpackagesapp_modules.yaml.
  3. Accès à adminmodules réservé aux admins.

### Rôles

  1. Rôle admin hérite de ROLE_USER.
  2. Liste des rôles présents en base affichée 
      - Menu Admin (dropdown)
      - Page d’accueil
      - Page adminmodules

### Affichage des modules (page d’accueil + admin)

  1. Nom, version, statut, description.
  2. Rôles requis (ou “public”).
  3. Dépendances (ou “none”).

### Commandes CLI
Commande avec comme préfixe  ```php binconsole ```

  ```erpmoduleadd name [version] [--role=ROLE_X][--dependency=Module]```

  ```erpseedadmin [email] [password]```

  ```erpuseradd email password [--role=ROLE_X]```

  ```erphelp (doc des commandes ERP)```

### Base de données

  1. Connexion MySQL fonctionnelle.
  2. Migrations exécutées avec succès.

### UI

  1. Bordure noire sur chaque module dans la page d’accueil.
  2. Boutons activerdésactiver.
  3. Pills pour les rôles.

