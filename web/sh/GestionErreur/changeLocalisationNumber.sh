#!/bin/bash
# Script qui renomme les fichiers du dossier reloads en modifiant le numéro de la localisation

if [ $# = 0 ]; then
	echo "Paramètres attendus : Code de l'affaire, Numéro de la localisation à définir"
	echo "ex : ./changeLocalisationNumber.sh C660 02"
	exit 1
fi

if [ $# -lt 2 ]; then
    echo "Deux paramètres attendus : Code de l'affaire, Numéro de la localisation à définir"
    echo "ex : ./changeLocalisationNumber.sh C660 02"
    exit 1
fi


if [ $# -gt 2 ]; then
	echo "Deux paramètres attendus : Code de l'affaire, Numéro de la localisation à définir"
	echo "ex : ./changeLocalisationNumber.sh C660 02"
	exit 1
fi

affaire=$1
numeroLocalisation=$2
echo "Affaire : $affaire Numéro de localisation à définir : $numeroLocalisation"

# script qui renomme les fichiers du dossier des fichiers en erreur à rejouer : /fichiers_errors/reloads et les déplace dans le dossiers des fichiers d'origines /fichiers_origines/
for file in ${BOILERBOX}/web/uploads/fichiers_errors/reloads/*; do
	nouveauRepertoire=$(echo $file | sed 's/\.error//' | sed "s/${affaire}_../${affaire}_${numeroLocalisation}/" | sed 's/fichiers_errors\/reloads/fichiers_origines/')
	echo "fichier [ $file ] déplacé vers [ $nouveauRepertoire ]"
	mv $file $nouveauRepertoire
done
