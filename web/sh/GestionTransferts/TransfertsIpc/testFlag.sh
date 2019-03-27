#!/bin/sh

# Le script lit les fichiers présents dans le dossier_source. 			/fichiers_origines
#  - Crée leur équivalent en binaire qu'il place dans le dossier_destination	/fichiers_binaires/
#  - Déplace les fichiers d'origine dans le dossier_encours 			/fichiers_tmpencours


# Importation des fichiers Binaires
flagTest='/tmp/.flagTestSymfony';

# Vérification qu'un flag d' e création des fichiers binaires
if [ -e "$flagTest" ]; then
        echo "Le test de flag est déjà en cours d'execution"
        exit 1
else
    # Création du flag
    touch "$flagTest"
    while [ 1 ]
    do
      sleep 2
    done
    # Libération du flag
    rm "$flagTest"
fi
exit 0
