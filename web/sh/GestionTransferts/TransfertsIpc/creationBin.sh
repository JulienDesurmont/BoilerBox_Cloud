#!/bin/sh

# Le script lit les fichiers présents dans le dossier_source. 			/fichiers_origines
#  - Crée leur équivalent en binaire qu'il place dans le dossier_destination	/fichiers_binaires/
#  - Déplace les fichiers d'origine dans le dossier_encours 			/fichiers_tmpencours

# Création des fichiers binaires à partir des fichiers ftp importés (depuis le dossier ${BOILERBOX}/fichiers_traites/

if [ $# != 0 ]; then
	systemLog=$1/web/logs/system.log
	BOILERBOX=$1
else
	systemLog=${BOILERBOX}/web/logs/system.log
fi


# Importation des fichiers Binaires
flagCreationBinaire='/tmp/.flagSymfonyScriptCreationBinaires'

# Vérification qu'un flag d' e création des fichiers binaires
if [ -e "$flagCreationBinaire" ]; then
        echo "La création de fichiers binaires est déjà en cours d'execution"
        exit 1
else
    # Création du flag
    touch "$flagCreationBinaire"
    dossier_source=${BOILERBOX}/web/uploads/fichiers_origines/
    dossier_destination=${BOILERBOX}/web/uploads/fichiers_binaires/
    dossier_encours=${BOILERBOX}/web/uploads/fichiers_tmpencours/
    log=${BOILERBOX}/web/logs/cronlog.log
    for fichier_source in `find $dossier_source -name '*.lci'`;do
	nom_fichier=`echo "$fichier_source" | awk -F"/" '{print $NF}'` 
	fichier_binaire="$dossier_destination$nom_fichier.bin"
	fichier_encours="$dossier_encours$nom_fichier"
	# Conversion du fichier en binaire
	`xxd -b -c24 $fichier_source > $fichier_binaire`
	# Modification des droits pour autoriser la lecture par BoilerBox
	`chmod 666 $fichier_binaire`
	# Déplacement des fichiers convertit vers le repertoire des fichiers 'en_cours'
	#`cp -p $fichier_source $fichier_encours`
	`mv $fichier_source $fichier_encours`
	if [ $? -ne 0 ]
	then
		echo `date +"%Y-%m-%d %T"`";transfertBin : Erreur du deplacement de fichier $fichier_source" >> $systemLog
	fi
    done
    # Libération du flag
    rm "$flagCreationBinaire"
fi
exit 0
