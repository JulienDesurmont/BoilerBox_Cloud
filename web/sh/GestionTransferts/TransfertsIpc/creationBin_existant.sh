#!/bin/sh

# Le script lit les fichiers présents dans le dossier_source. 			/fichiers_origines
#  - Crée leur équivalent en binaire qu'il place dans le dossier_destination	/fichiers_binaires/
#  - Déplace les fichiers d'origine dans le dossier_encours 			/fichiers_tmpencours

dossier_source='/srv/www/htdocs/fichiers_traites/'
dossier_destination='/srv/www/htdocs/Symfony/web/uploads/fichiers_binaires/'
dossier_encours='/srv/www/htdocs/Symfony/web/uploads/fichiers_tmpencours/'

log='/srv/www/htdocs/Symfony/web/logs/cronlog.log'

for fichier_source in `find $dossier_source -maxdepth 1 -name '*.lci'`;do
	nom_fichier=`echo "$fichier_source" | awk -F"/" '{print $NF}'` 
	fichier_binaire="$dossier_destination$nom_fichier.bin"
	fichier_encours="$dossier_encours$nom_fichier"
	# Conversion du fichier en binaire
	`xxd -b -c24 $fichier_source > $fichier_binaire`
	# Modification des droits pour autoriser la lecture par symfony
	`chmod 666 $fichier_binaire`
	# Déplacement des fichiers convertit vers le repertoire des fichiers 'en_cours'
	#`cp -p $fichier_source $fichier_encours`
	`mv $fichier_source $fichier_encours`
	if [ $? -ne 0 ]
	then
		echo "transfertBin : Erreur du déplacement de fichier $fichier_source" >> $log
	fi
done
