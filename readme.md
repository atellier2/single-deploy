
Fichier unique permettant de réaliser différentes opérations sur les environnements PHP.


ATTENTION : il est nécessaire de bien renseigner les paramètres en en-tête de fichier.

# Actions

## ``delete_directory `` Suppression d'un répertoire

## Paramètres : 
- ``directory_name`` : Nom du répertoire à supprimer.

## Exemple :
``` php
 tools.php?action=delete_directory&directory_name=monrep/sousrep
```


## archive_directory

## Paramètres
- directory_path : chemin du répertoire à archiver
- archive_path : chemin de répertoire dans lequel mettre l'archive