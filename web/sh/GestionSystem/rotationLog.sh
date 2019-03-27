#!/bin/sh
# Rotation des logs
# Sauvegarde et compression des fichiers de log 
repertoireLog=${BOILERBOX}/web/logs
repertoireSauvegardes=${BOILERBOX}/web/logs/backup
listeDesFichiers=`find ${BOILERBOX}/web/logs/ -maxdepth 1 -type f -exec ls {} \;`
dateJour=`date "+%Y%m%d"`
heureScript=`date "+%H"`
dateHier=`date "+%Y%m%d" --date '1 days ago'`
if [ $heureScript == '00' ]
then
	dateJour=${dateHier}
fi
dateFichier=${dateJour}${heureScript}

# Création du dossier si il n'existe pas
if [ ! -d "${repertoireSauvegardes}/${dateJour}" ]
then
	mkdir "${repertoireSauvegardes}/${dateJour}"
fi
for file in $listeDesFichiers;do
    # Création du nom du fichier de sauvegarde
    nomFichier=`echo "$file" | awk -F'/' '{print $NF}'`
    # Copie du fichier
    cp -p "$file" "${repertoireSauvegardes}/${dateJour}/${dateFichier}_${nomFichier}"
    # Compression du fichier
    bzip2 "${repertoireSauvegardes}/${dateJour}/${dateFichier}_${nomFichier}"	
    # Réinitialisation du fichier de log
    echo "" > $file
done
exit 0
