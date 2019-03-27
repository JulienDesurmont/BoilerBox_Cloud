#!/bin/bash

if test -z "$1" 
then
	echo "Argument du code de l'affaire manquant"
	exit
fi

codeAffaire=$1

# script qui renomme les fichiers du dossier fichiers_origines en remplacant le code donné parametre par C694: 
for file in ${BOILERBOX}/web/uploads/fichiers_origines/*; do
	nouveaunom=$(echo $file|sed "s/$codeAffaire/C694/")
	mv $file $nouveaunom
done
