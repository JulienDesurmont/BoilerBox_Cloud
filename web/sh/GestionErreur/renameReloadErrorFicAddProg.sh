#!/bin/bash

if test -z "$1" 
then
	echo "Argument du code de l'affaire manquant"
	exit
fi

dateAffaire=$1

# script qui renomme les fichiers du dossier fichiers_origines en remplacant le code donné parametre par C694: 
for file in ${BOILERBOX}/web/uploads/fichiers_origines/*; do
	nouveaunom=$(echo $file|sed "s/00_#SBC_01_01#/00_#SBC_00_01#/")
	mv $file $nouveaunom
done
