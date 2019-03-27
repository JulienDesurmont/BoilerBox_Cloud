Utilisation de compileLessFiles.php
1. Placer les fichiers avec l'extension less dans le dossier toBeCompiled/lessFiles
# 2. Lancer le scripts system/compileSetPermissions.sh pour permettre la concatenation des fichiers
3. Lancer le script php: http://192.168.0.215/utilitaires/compileLessFiles.php 
4. Lancer le dump assetic: php app/console asset:dump --env=prod

