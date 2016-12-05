![Erbol](http://erbol.fr/themes/jms_megamall/img/logo.png)

[Erbol - Site internet](http://erbol.fr)

# Erbol Synapse #

Erbol Synapse est un projet basé sur la domotique.

Il utilise le projet open Jeedom Core.
Repository: [Jeedom Core](https://github.com/jeedom/core)

# Installation #

## Pre-requis
- mysql installé (en local ou sur une machine distance)
- un serveur web d'installé (apache ou nginx)
- php (5.6 minimum) installé avec les extensions : curl, json, gd et mysql
- ntp et crontab installés
- curl, unzip et sudo installés

TIPS : pour nginx vous trouverez un exemple de la configuration web necessaire dans install/nginx_default.

### Création de la BDD 

Il vous faut créer une base de données sur mysql (en utf8_general_ci).

### Configuration et installation

Allez (avec votre navigateur) sur `install/setup.php`.

Remplissez les informations, validez et attendez la fin de l'installation. Les identifiants par défaut sont admin/admin.

# License #

GNU GENERAL PUBLIC LICENSE
Version 2, June 1991

Copyright (C) 1989, 1991 Free Software Foundation, Inc., <http://fsf.org/> 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA. Everyone is permitted to copy and distribute verbatim copies of this license document, but changing it is not allowed.

